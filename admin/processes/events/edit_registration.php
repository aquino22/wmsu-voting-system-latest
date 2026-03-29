<?php
session_start();
require '../../includes/conn.php'; // Adjust path to your PDO connection

header('Content-Type: application/json');

// Disable error display in production
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in as an admin.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['event_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request or missing event ID.']);
    exit;
}

try {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit;
    }

    // Sanitize and validate inputs
    $event_id = filter_var(trim($_POST['event_id'] ?? ''), FILTER_VALIDATE_INT);
    $start_date = trim($_POST['registration_start_date'] ?? '');
    $deadline = trim($_POST['registration_deadline'] ?? '');

    // Validate required fields
    if ($event_id === false || $event_id <= 0) {
        throw new Exception("Invalid event ID");
    }
    if (empty($start_date) || empty($deadline)) {
        throw new Exception("Registration start date and deadline are required.");
    }

    // Validate date formats
    $start = DateTime::createFromFormat('Y-m-d\TH:i', $start_date);
    $end = DateTime::createFromFormat('Y-m-d\TH:i', $deadline);
    if ($start === false || $end === false) {
        throw new Exception("Invalid date format. Use YYYY-MM-DDThh:mm");
    }
    if ($start >= $end) {
        throw new Exception("Registration deadline must be after start date");
    }

    // Add seconds for database compatibility
    $start->setTime($start->format('H'), $start->format('i'), 0);
    $end->setTime($end->format('H'), $end->format('i'), 0);

    // Check if the event exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    if ($stmt->fetchColumn() == 0) {
        throw new Exception("Event not found");
    }

    // Start a transaction
    $pdo->beginTransaction();

    // Update events table
    $sql = "UPDATE events SET registration_start = :start_date, registration_deadline = :deadline, updated_at = NOW() WHERE id = :event_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start_date' => $start->format('Y-m-d H:i:s'),
        ':deadline' => $end->format('Y-m-d H:i:s'),
        ':event_id' => $event_id
    ]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        throw new Exception("No changes were made to the event");
    }

    // Log to user_activities
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $action = 'UPDATE_EVENT';
        $timestamp = date('Y-m-d H:i:s');
        $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $location = 'N/A'; // Can be enhanced with geolocation
        $behavior_patterns = "Updated event ID: $event_id, Registration Start: {$start->format('Y-m-d H:i:s')}, Deadline: {$end->format('Y-m-d H:i:s')}";

        $activityStmt = $pdo->prepare("
            INSERT INTO user_activities (
                user_id, action, timestamp, device_info, ip_address, location, behavior_patterns
            ) VALUES (
                :user_id, :action, :timestamp, :device_info, :ip_address, :location, :behavior_patterns
            )
        ");
        $activityStmt->execute([
            ':user_id' => $user_id,
            ':action' => $action,
            ':timestamp' => $timestamp,
            ':device_info' => $device_info,
            ':ip_address' => $ip_address,
            ':location' => $location,
            ':behavior_patterns' => $behavior_patterns
        ]);
    }

    // Commit the transaction
    $pdo->commit();

    // Invalidate CSRF token
    unset($_SESSION['csrf_token']);

    echo json_encode([
        'status' => 'success',
        'message' => 'Event registration dates updated successfully'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error in edit_event.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred. Please try again later.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in edit_event.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => htmlspecialchars($e->getMessage())]);
}

// Close connection (optional)
$pdo = null;
?>