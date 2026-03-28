<?php
require_once '../includes/conn.php'; // Database connection
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_id'], $_POST['action'])) {
    $candidate_id = $_POST['candidate_id'];
    $new_status = ($_POST['action'] === 'accept') ? 'accepted' : 'declined';

    // Fetch existing registration data
    $stmt = $pdo->prepare("SELECT id, registration_data FROM event_registrations WHERE JSON_UNQUOTE(JSON_EXTRACT(registration_data, '$.form_data.candidate id')) = :candidate_id");
    $stmt->bindParam(':candidate_id', $candidate_id, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Decode JSON, update status, and re-encode
        $registration_data = json_decode($row['registration_data'], true);
        $registration_data['form_data']['status'] = $new_status;
        $updated_json = json_encode($registration_data);

        // Update database
        $update_stmt = $pdo->prepare("UPDATE event_registrations SET registration_data = :updated_data WHERE id = :id");
        $update_stmt->bindParam(':updated_data', $updated_json, PDO::PARAM_STR);
        $update_stmt->bindParam(':id', $row['id'], PDO::PARAM_INT);

        if ($update_stmt->execute()) {
            $_SESSION['STATUS'] = 'SUCCESS_STATUS_UPDATE';
        } else {
            $_SESSION['STATUS'] = 'ERROR_STATUS_UPDATE';
        }
    } else {
        $_SESSION['STATUS'] = 'ERROR_CANDIDATE_NOT_FOUND';
    }
} else {
    $_SESSION['STATUS'] = 'INVALID_REQUEST';
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
