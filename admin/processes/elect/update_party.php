<?php
session_start();
require_once '../../includes/conn.php'; // Adjust path to your PDO connection

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {
  

    // Sanitize and validate inputs
    $id = filter_var(trim($_POST['id'] ?? ''), FILTER_VALIDATE_INT);
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $election = htmlspecialchars(trim($_POST['election'] ?? ''));
    $platforms = htmlspecialchars(trim($_POST['platforms'] ?? ''));
    $status = htmlspecialchars(trim($_POST['status_party'] ?? ''));
    $party_image = !empty($_FILES['editPartyImage']['name']) ? $_FILES['editPartyImage']['name'] : null;

    // Validate required fields
    if ($id === false || $id <= 0) {
        throw new Exception("Invalid party ID");
    }
    if (empty($name) || empty($election) || empty($status)) {
        throw new Exception("Required fields are missing.");
    }

    // Field length validation
    $maxLengths = [
        'name' => 100,
        'election' => 100,
        'platforms' => 5000,
        'status' => 50
    ];

    if (strlen($name) > $maxLengths['name']) {
        throw new Exception("Party name is too long (max {$maxLengths['name']} characters)");
    }
    if (strlen($election) > $maxLengths['election']) {
        throw new Exception("Election name is too long (max {$maxLengths['election']} characters)");
    }
    if (strlen($platforms) > $maxLengths['platforms']) {
        throw new Exception("Platforms content is too long (max {$maxLengths['platforms']} characters)");
    }
    if (strlen($status) > $maxLengths['status']) {
        throw new Exception("Status is too long (max {$maxLengths['status']} characters)");
    }

    // Validate status
    if (!in_array($status, ['Unverified', 'Verified'])) {
        throw new Exception("Invalid status value");
    }

    // Validate election exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM elections WHERE name = ?");
    $stmt->execute([$election]);
    if ($stmt->fetchColumn() == 0) {
        throw new Exception("Invalid election name");
    }

    // Start a transaction
    $pdo->beginTransaction();

    // Check if the party exists
    $stmt = $pdo->prepare("SELECT name, election_name, party_image FROM parties WHERE id = ?");
    $stmt->execute([$id]);
    $partyData = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$partyData) {
        throw new Exception("Party not found");
    }
    $oldPartyName = $partyData['name'];
    $currentElectionName = $partyData['election_name'];
    $old_image = $partyData['party_image'];

    // Check if voting period has started
    $stmt = $pdo->prepare("SELECT status FROM voting_periods WHERE name = ?");
    $stmt->execute([$currentElectionName]);
    $votingStatus = $stmt->fetchColumn();
    if ($votingStatus && in_array(strtolower($votingStatus), ['ongoing', 'finished'])) {
        throw new Exception("Cannot edit party details after the voting period has started.");
    }

    // Handle party image upload (if provided)
    if ($party_image) {
            $target_dir = '../../../uploads/'; // Adjust to secure path, preferably outside web root
        if (!is_dir($target_dir) || !is_writable($target_dir)) {
            throw new Exception("Upload directory is not accessible or writable.");
        }

        $imageFileType = strtolower(pathinfo($party_image, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        // Verify MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['editPartyImage']['tmp_name']);
        finfo_close($finfo);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mime, $allowed_mimes) || !in_array($imageFileType, $allowed_types)) {
            throw new Exception("Invalid image format. Only JPG, JPEG, PNG, GIF allowed.");
        }

        // Check file size
        if ($_FILES['editPartyImage']['size'] > $max_file_size) {
            throw new Exception("Image size exceeds 5MB limit.");
        }

        // Check for upload errors
        if ($_FILES['editPartyImage']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error: " . $_FILES['editPartyImage']['error']);
        }

        // Generate a secure unique filename
        $unique_filename = uniqid('party_', true) . '.' . $imageFileType;
        $target_file = $target_dir . $unique_filename;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['editPartyImage']['tmp_name'], $target_file)) {
            throw new Exception("Failed to upload image.");
        }

        // Delete old image if it exists
        if ($old_image && file_exists($target_dir . $old_image)) {
            unlink($target_dir . $old_image);
        }
        $party_image = $unique_filename;
    }

    // Update parties table
    $query = "UPDATE parties SET name = :name, election_name = :election_name, platforms = :platforms, status = :status";
    if ($party_image) {
        $query .= ", party_image = :party_image";
    }
    $query .= ", updated_at = NOW() WHERE id = :id";
    
    $stmt = $pdo->prepare($query);
    $params = [
        ':id' => $id,
        ':name' => $name,
        ':election_name' => $election,
        ':platforms' => $platforms,
        ':status' => $status
    ];
    if ($party_image) {
        $params[':party_image'] = $party_image;
    }
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        throw new Exception("No changes were made to the party");
    }

    // Update candidate_responses if party name changed
    $updatedResponses = 0;
    if ($oldPartyName !== $name) {
        $stmt = $pdo->prepare("SELECT id FROM form_fields WHERE field_name = 'party'");
        $stmt->execute();
        $partyFieldId = $stmt->fetchColumn();

        if ($partyFieldId) {
            $stmt = $pdo->prepare("
                UPDATE candidate_responses 
                SET value = ?, updated_at = NOW()
                WHERE field_id = ? AND value = ?
            ");
            $stmt->execute([$name, $partyFieldId, $oldPartyName]);
            $updatedResponses = $stmt->rowCount();
        }
    }

    // Log to user_activities
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $action = 'UPDATE_PARTY';
        $timestamp = date('Y-m-d H:i:s');
        $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $location = 'N/A'; // Can be enhanced with geolocation
        $behavior_patterns = "Updated party ID: $id, Name: $name, Election: $election" . 
                            ($party_image ? ", Image: $party_image" : "") .
                            ($updatedResponses > 0 ? ", Updated $updatedResponses candidate responses" : "");

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
        'message' => 'Party updated successfully' . ($updatedResponses > 0 ? " and $updatedResponses candidate responses updated" : "")
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error in edit_party.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred. Please try again later.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in edit_party.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => htmlspecialchars($e->getMessage())]);
}

// Close connection (optional)
$pdo = null;
?>