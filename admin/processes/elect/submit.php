<?php
date_default_timezone_set('Asia/Manila');
require '../../includes/conn.php';
session_start();
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

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

try {
    // Validate required fields
    $required = [
        'election_name',
        'academic_year_id',
        'start_period',
        'end_period',
        'status'
    ];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize and validate inputs
    $election_name = trim($_POST['election_name']);
    if (preg_match('/[<>]/', $election_name)) {
        die("Error: Election name cannot contain < or > symbols.");
    }
    $academic_year_id = filter_var($_POST['academic_year_id'], FILTER_VALIDATE_INT);
    $start_period = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['start_period']);
    $end_period = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['end_period']);
    $status = htmlspecialchars(trim($_POST['status']));
    $created_at = date("Y-m-d H:i:s");

    // Field length validation
    $maxLengths = [
        'election_name' => 100,
        'status' => 50
    ];

    if (strlen($election_name) > $maxLengths['election_name']) {
        throw new Exception("Election name is too long (max {$maxLengths['election_name']} characters)");
    }

    if (strlen($status) > $maxLengths['status']) {
        throw new Exception("Status is too long (max {$maxLengths['status']} characters)");
    }

    // Additional validation
    if ($academic_year_id === false) {
        throw new Exception("Invalid academic year ID");
    }
    if ($start_period === false || $end_period === false) {
        throw new Exception("Invalid date format. Use YYYY-MM-DDThh:mm");
    }
    // if ($start_period >= $end_period) {
    //     throw new Exception("End period must be after start period");
    // }

    if (!in_array($status, ['Ongoing', 'Upcoming'])) {
        throw new Exception("Invalid status value");
    }

    // Fetch academic year dates for validation
    $stmt_ay = $pdo->prepare("SELECT start_date, end_date FROM academic_years WHERE id = ?");
    $stmt_ay->execute([$academic_year_id]);
    $academic_year = $stmt_ay->fetch(PDO::FETCH_ASSOC);

    if (!$academic_year) {
        throw new Exception("Invalid academic year selected.");
    }

    $ay_start_date = new DateTime($academic_year['start_date']);
    $ay_end_date = new DateTime($academic_year['end_date']);
    $ay_end_date->setTime(23, 59, 59); // Include the whole end day

    // Check if election dates are within the academic year range
    if ($start_period < $ay_start_date || $end_period > $ay_end_date) {
        throw new Exception("Election dates must be within the selected academic year's period (" . $ay_start_date->format('M j, Y') . " to " . $ay_end_date->format('M j, Y') . ").");
    }

    // Add seconds for database compatibility
    $start_period->setTime($start_period->format('H'), $start_period->format('i'), 0);
    $end_period->setTime($end_period->format('H'), $end_period->format('i'), 0);

    // Start a transaction
    $pdo->beginTransaction();

    // Check election name conflict
    // $stmt = $pdo->prepare("SELECT COUNT(*) FROM elections WHERE election_name = ?");
    // $stmt->execute([$election_name]);
    // if ($stmt->fetchColumn() > 0) {
    //     throw new Exception("Election name already exists");
    // }

    // Insert election
    $stmt = $pdo->prepare("INSERT INTO elections 
        (election_name, academic_year_id, start_period, end_period, status, created_at) 
        VALUES (:election_name, :academic_year_id, 
                :start_period, :end_period, :status, :created_at)");
    $stmt->execute([
        ':election_name' => $election_name,
        ':academic_year_id' => $academic_year_id,
        ':start_period' => $start_period->format('Y-m-d H:i:s'),
        ':end_period' => $end_period->format('Y-m-d H:i:s'),
        ':status' => $status,
        ':created_at' => $created_at
    ]);

    // Log to user_activities if user is authenticated
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT year_label, semester FROM academic_years WHERE id = ?");
        $stmt->execute([$academic_year_id]);
        $ay_info = $stmt->fetch(PDO::FETCH_ASSOC);
        $year_label = $ay_info['year_label'] ?? 'N/A';
        $semester = $ay_info['semester'] ?? 'N/A';

        $user_id = $_SESSION['user_id'];
        $action = 'ADD_ELECTION';
        $timestamp = date('Y-m-d H:i:s');
        $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $location = 'N/A';
        $behavior_patterns = "Added election: Name: $election_name, School Year: $year_label, Semester: $semester, Status: $status";

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

    // Invalidate CSRF token after successful submission
    unset($_SESSION['csrf_token']);

    $_SESSION['STATUS'] = 'ELECTION_CREATED';
    $response = ["status" => "success", "message" => "Election successfully added"];
    echo json_encode($response);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in add_election.php: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => htmlspecialchars($e->getMessage())
    ]);
}

// Close connection (optional)
$pdo = null;
