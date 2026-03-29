<?php
session_start();
require '../../includes/conn.php'; // Include database connection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM email WHERE email = ?");
    $stmt_check->execute([$_POST['email']]);
    $email_exists = $stmt_check->fetchColumn();
    if ($email_exists > 0) {
        $_SESSION['STATUS'] = "EMAIL_EXISTS";
    } else {
        $_SESSION['STATUS'] = "EMAIL_SUCCESS_CREATED";
        $stmt = $pdo->prepare("INSERT INTO email (email, app_password) VALUES (?, ?)");
        $stmt->execute([
            $_POST['email'],
            $_POST['app_password']
        ]);
    }
    header("Location: ../../emails.php");
    exit;
}
