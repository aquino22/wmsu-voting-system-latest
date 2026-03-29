<?php
session_start();
require_once '../../includes/conn.php';

header('Content-Type: application/json');

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

    // Sanitize inputs
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $election_id = filter_var($_POST['election'] ?? '', FILTER_VALIDATE_INT);
    $platforms = $_POST['platforms'] ?? '';
    $status = htmlspecialchars(trim($_POST['status_party'] ?? ''));

    if ($id === false || $id <= 0) {
        throw new Exception("Invalid party ID");
    }

    if ($election_id === false || $election_id <= 0) {
        throw new Exception("Invalid election ID");
    }

    if (empty($name) || empty($status)) {
        throw new Exception("Required fields are missing.");
    }

    // Field length validation
    if (strlen($name) > 50000) {
        throw new Exception("Party name is too long.");
    }

    if (strlen($platforms) > 50000) {
        throw new Exception("Platforms content is too long.");
    }

    if (strlen($status) > 50000) {
        throw new Exception("Status is too long.");
    }

    $pdo->beginTransaction();

    // Get current party data
    $stmt = $pdo->prepare("
        SELECT name, election_id, party_image 
        FROM parties 
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    $partyData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$partyData) {
        throw new Exception("Party not found");
    }

    $oldPartyName = $partyData['name'];
    $currentElectionId = $partyData['election_id'];
    $old_image = $partyData['party_image'];

    // Check duplicate party name in same election
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

    // Check voting status
    $stmt = $pdo->prepare("
        SELECT status 
        FROM voting_periods 
        WHERE election_id = ?
    ");

    $stmt->execute([$currentElectionId]);
    $votingStatus = $stmt->fetchColumn();

    if ($votingStatus && in_array(strtolower($votingStatus), ['ongoing', 'finished'])) {
        throw new Exception("Cannot edit party after voting has started.");
    }

    // Handle image upload
    $party_image = null;

    if (!empty($_FILES['editPartyImage']['name'])) {

        $target_dir = '../../../uploads/';

        if (!is_dir($target_dir) || !is_writable($target_dir)) {
            throw new Exception("Upload directory not writable.");
        }

        $imageFileType = strtolower(pathinfo($_FILES['editPartyImage']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($imageFileType, $allowed_types)) {
            throw new Exception("Invalid image format.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['editPartyImage']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_mimes)) {
            throw new Exception("Invalid image MIME type.");
        }

        if ($_FILES['editPartyImage']['size'] > 5 * 1024 * 1024) {
            throw new Exception("Image exceeds 5MB.");
        }

        if ($_FILES['editPartyImage']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error.");
        }

        $unique_filename = uniqid('party_', true) . '.' . $imageFileType;
        $target_file = $target_dir . $unique_filename;

        if (!move_uploaded_file($_FILES['editPartyImage']['tmp_name'], $target_file)) {
            throw new Exception("Failed to upload image.");
        }

        if ($old_image && file_exists($target_dir . $old_image)) {
            unlink($target_dir . $old_image);
        }

        $party_image = $unique_filename;
    }

    // Update party
    $query = "
        UPDATE parties
        SET name = :name,
            election_id = :election_id,
            platforms = :platforms,
            status = :status
    ";

    if ($party_image) {
        $query .= ", party_image = :party_image";
    }

    $query .= ", updated_at = NOW() WHERE id = :id";

    $stmt = $pdo->prepare($query);

    $params = [
        ':id' => $id,
        ':name' => $name,
        ':election_id' => $election_id,
        ':platforms' => $platforms,
        ':status' => $status
    ];

    if ($party_image) {
        $params[':party_image'] = $party_image;
    }

    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        throw new Exception("No changes were made.");
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
                WHERE field_id = ? AND value = ?
            ");

            $stmt->execute([$name, $partyFieldId, $oldPartyName]);
            $updatedResponses = $stmt->rowCount();
        }
    }

    // Log user activity
    if (isset($_SESSION['user_id'])) {

        $stmt = $pdo->prepare("
            INSERT INTO user_activities
            (user_id, action, timestamp, device_info, ip_address, location, behavior_patterns)
            VALUES
            (:user_id, :action, :timestamp, :device_info, :ip_address, :location, :behavior_patterns)
        ");

        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
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
        'status' => 'success',
        'message' => 'Party updated successfully' .
            ($updatedResponses > 0 ? " and $updatedResponses candidate responses updated" : "")
    ]);
} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Database error: " . $e->getMessage());

    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred.'
    ]);
} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'status' => 'error',
        'message' => htmlspecialchars($e->getMessage())
    ]);
}

$pdo = null;
