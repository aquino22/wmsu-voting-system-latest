<?php
require '../../includes/conn.php';
session_start();

header('Content-Type: application/json');

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

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Validate required fields
    $required = ['id', 'name', 'election', 'status_party'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize inputs
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $name = htmlspecialchars(trim($_POST['name']));
    $election_id = filter_var($_POST['election'], FILTER_VALIDATE_INT);
    $status = htmlspecialchars(trim($_POST['status_party']));

    if ($id === false || $id <= 0) {
        throw new Exception("Invalid party ID");
    }

    if ($election_id === false || $election_id <= 0) {
        throw new Exception("Invalid election ID");
    }

    if (!in_array($status, ['Unverified', 'Verified'])) {
        throw new Exception("Invalid status value");
    }

    // Check if party exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM parties WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->fetchColumn() == 0) {
        throw new Exception("Party not found");
    }

    $pdo->beginTransaction();

    // Get current party info
    $stmt = $pdo->prepare("SELECT name, election_id FROM parties WHERE id = ?");
    $stmt->execute([$id]);

    $partyData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$partyData) {
        throw new Exception("Could not retrieve party details.");
    }

    $oldPartyName = $partyData['name'];
    $currentElectionId = $partyData['election_id'];

    // Check duplicate party name within same election
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM parties 
        WHERE name = ? 
        AND election_id = ? 
        AND id != ?
    ");
    $stmt->execute([$name, $election_id, $id]);

    if ($stmt->fetchColumn() > 0) {
        throw new Exception("A party with the name '$name' already exists in this election.");
    }

    // Check if voting period started
    $stmt = $pdo->prepare("
        SELECT status 
        FROM voting_periods 
        WHERE election_id = ?
    ");
    $stmt->execute([$currentElectionId]);

    $votingStatus = $stmt->fetchColumn();

    if ($votingStatus && in_array(strtolower($votingStatus), ['ongoing', 'finished'])) {
        throw new Exception("Cannot edit party details after voting has started.");
    }

    // Handle party image upload
    $party_image = null;

    if (!empty($_FILES['editPartyImage']['name'])) {

        $file = $_FILES['editPartyImage'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_types)) {
            throw new Exception("Invalid file type.");
        }

        if ($file['size'] > $max_size) {
            throw new Exception("File exceeds 5MB limit.");
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $party_image = uniqid('party_', true) . '.' . $extension;

        $upload_dir = '../../../Uploads/';

        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $upload_path = $upload_dir . $party_image;

        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception("Failed to upload image");
        }

        // Delete old image
        $stmt = $pdo->prepare("SELECT party_image FROM parties WHERE id = ?");
        $stmt->execute([$id]);

        $old_image = $stmt->fetchColumn();

        if ($old_image && file_exists($upload_dir . $old_image)) {
            unlink($upload_dir . $old_image);
        }
    }

    // Update party
    $sql = "UPDATE parties 
            SET name = ?, election_id = ?, status = ?";

    $params = [$name, $election_id, $status];

    if ($party_image) {
        $sql .= ", party_image = ?";
        $params[] = $party_image;
    }

    $sql .= ", updated_at = NOW() WHERE id = ?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        throw new Exception("No changes were made");
    }

    // Update candidate responses if party name changed
    $updatedResponses = 0;

    if ($oldPartyName !== $name) {

        $stmt = $pdo->prepare("SELECT id FROM form_fields WHERE field_name = 'party'");
        $stmt->execute();

        $partyFieldId = $stmt->fetchColumn();

        if ($partyFieldId) {

            $stmt = $pdo->prepare("
                UPDATE candidate_responses 
                SET value = ?, updated_at = NOW()
                WHERE field_id = ? 
                AND value = ?
            ");

            $stmt->execute([$name, $partyFieldId, $oldPartyName]);
            $updatedResponses = $stmt->rowCount();
        }
    }

    // Log activity
    if (isset($_SESSION['user_id'])) {

        $user_id = $_SESSION['user_id'];

        $stmt = $pdo->prepare("
            INSERT INTO user_activities 
            (user_id, action, timestamp, device_info, ip_address, location, behavior_patterns)
            VALUES
            (:user_id, :action, :timestamp, :device_info, :ip_address, :location, :behavior_patterns)
        ");

        $stmt->execute([
            ':user_id' => $user_id,
            ':action' => 'UPDATE_PARTY',
            ':timestamp' => date('Y-m-d H:i:s'),
            ':device_info' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            ':location' => 'N/A',
            ':behavior_patterns' => "Updated party ID: $id, Name: $name, Election ID: $election_id"
        ]);
    }

    $pdo->commit();

    unset($_SESSION['csrf_token']);

    echo json_encode([
        "status" => "success",
        "message" => "Party successfully updated" .
            ($updatedResponses > 0 ? " and $updatedResponses candidate responses updated" : "")
    ]);
} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" => htmlspecialchars($e->getMessage())
    ]);
}

$pdo = null;
