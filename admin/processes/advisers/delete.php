<?php
session_start();
require '../../includes/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {

    $adviserId = (int) $_POST['id'];

    try {

        $pdo->beginTransaction();

        // Get adviser email
        $fetchEmail = $pdo->prepare("
            SELECT email 
            FROM advisers 
            WHERE id = ?
        ");
        $fetchEmail->execute([$adviserId]);

        $email = $fetchEmail->fetchColumn();

        if (!$email) {
            throw new Exception("Adviser not found.");
        }

        // Release SMTP accounts
        $resetEmails = $pdo->prepare("
            UPDATE email
            SET status = 'available',
                adviser_id = NULL
            WHERE adviser_id = ?
        ");
        $resetEmails->execute([$adviserId]);

        // Delete adviser
        $deleteAdviser = $pdo->prepare("
            DELETE FROM advisers
            WHERE id = ?
        ");
        $deleteAdviser->execute([$adviserId]);

        // Delete linked user
        $deleteUser = $pdo->prepare("
            DELETE FROM users
            WHERE email = ?
        ");
        $deleteUser->execute([$email]);

        $pdo->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Adviser deleted successfully"
        ]);
    } catch (Exception $e) {

        $pdo->rollBack();

        error_log("Delete adviser failed: " . $e->getMessage());

        http_response_code(500);

        echo json_encode([
            "status" => "error",
            "message" => "Failed to delete adviser"
        ]);
    }
} else {

    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" => "Invalid request"
    ]);
}
