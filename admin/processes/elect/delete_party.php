<?php
require '../../includes/conn.php';
session_start();
header('Content-Type: application/json');

// Disable error display in production
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

$input = json_decode(file_get_contents("php://input"), true);
// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in as an admin.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

try {


    if (!isset($input['csrf_token']) || !isset($_SESSION['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit;
    }


    // The user wants to delete an election. The provided script deletes a party.
    // The script will be modified to delete the entire election associated with the given party_id.
    // The input is still party_id, which will be used to find the election_name.
    if (!isset($input['party_id']) || !filter_var($input['party_id'], FILTER_VALIDATE_INT)) {
        throw new Exception('Invalid ID for identifying the election to delete.');
    }

    $party_id = (int)$input['party_id'];

    // Fetch election_name from the party to identify which election to delete
    $stmt = $pdo->prepare("SELECT election_id FROM parties WHERE id = ?");
    $stmt->execute([$party_id]);
    $election_name = $stmt->fetchColumn();

    if (!$election_name) {
        throw new Exception('Could not find the election to delete based on the provided party.');
    }

    // Start a transaction
    $pdo->beginTransaction();

    // 1. Find registration forms linked to the election
    $stmt = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ?");
    $stmt->execute([$election_name]);
    $form_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($form_ids)) {
        // 2. Find candidates linked to these forms
        $placeholders = str_repeat('?,', count($form_ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id FROM candidates WHERE form_id IN ($placeholders)");
        $stmt->execute($form_ids);
        $candidate_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($candidate_ids)) {
            $candidate_placeholders = str_repeat('?,', count($candidate_ids) - 1) . '?';
            // 3. Delete candidate_files linked to these candidates
            $stmt = $pdo->prepare("DELETE FROM candidate_files WHERE candidate_id IN ($candidate_placeholders)");
            $stmt->execute($candidate_ids);

            // 4. Delete candidate_responses linked to these candidates
            $stmt = $pdo->prepare("DELETE FROM candidate_responses WHERE candidate_id IN ($candidate_placeholders)");
            $stmt->execute($candidate_ids);

            // 5. Delete votes linked to these candidates
            $stmt = $pdo->prepare("DELETE FROM votes WHERE candidate_id IN ($candidate_placeholders)");
            $stmt->execute($candidate_ids);

            // 6. Delete the candidates
            $stmt = $pdo->prepare("DELETE FROM candidates WHERE id IN ($candidate_placeholders)");
            $stmt->execute($candidate_ids);
        }
        // 7. Delete registration forms
        $stmt = $pdo->prepare("DELETE FROM registration_forms WHERE election_name = ?");
        $stmt->execute([$election_name]);
    }

    // 8. Delete parties and their images
    $stmt = $pdo->prepare("SELECT party_image FROM parties WHERE election_id = ?");
    $stmt->execute([$election_name]);
    $party_images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($party_images as $image) {
        if ($image && file_exists("../../$image")) {
            unlink("../../$image");
        }
    }
    $stmt = $pdo->prepare("DELETE FROM parties WHERE election_id = ?");
    $stmt->execute([$election_name]);

    // 9. Delete positions for the election
    $stmt = $pdo->prepare("SELECT id FROM elections WHERE id = ?");
    $stmt->execute([$election_name]);
    $election_id_val = $stmt->fetchColumn();

    if ($election_id_val) {
        $stmt = $pdo->prepare("SELECT id FROM positions WHERE election_id = ?");
        $stmt->execute([$election_id_val]);
        $posIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($posIds)) {
            $inPos = implode(',', $posIds);
            $pdo->exec("DELETE FROM position_parties WHERE position_id IN ($inPos)");
            $pdo->exec("DELETE FROM positions WHERE id IN ($inPos)");
        }
    }

    // 10. Delete voting period for the election
    $stmt = $pdo->prepare("DELETE FROM voting_periods WHERE name = ?");
    $stmt->execute([$election_name]);

    // 11. Delete events for the election (assuming 'events' table has 'election_name' column)
    $stmt = $pdo->prepare("DELETE FROM events WHERE election_name = ?");
    $stmt->execute([$election_name]);

    // 12. Delete the election record itself (assuming 'elections' table has 'name' column)
    $stmt = $pdo->prepare("DELETE FROM elections WHERE name = ?");
    $stmt->execute([$election_name]);


    // Log to user_activities
    $user_id = $_SESSION['user_id'];
    $action = 'DELETE_ELECTION';
    $timestamp = date('Y-m-d H:i:s');
    $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $location = 'N/A';
    $behavior_patterns = "Deleted election: $election_name";

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

    // Commit the transaction
    $pdo->commit();

    // Invalidate CSRF token
    unset($_SESSION['csrf_token']);

    echo json_encode(['status' => 'success', 'message' => 'Election and all related data deleted successfully.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in election deletion: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => htmlspecialchars($e->getMessage())]);
}

// Close connection
$pdo = null;
