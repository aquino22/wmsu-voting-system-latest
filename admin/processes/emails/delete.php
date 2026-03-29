<?php
session_start();
require '../../includes/conn.php';

if (isset($_GET['id'])) {
    $emailId = $_GET['id'];

    // First, unset the smtp_email_id in advisers that reference this email
    $updateStmt = $pdo->prepare("UPDATE advisers SET smtp_email_id = 'NONE' WHERE smtp_email_id = ?");
    $updateStmt->execute([$emailId]);

    // Now delete the email from the email table
    $deleteStmt = $pdo->prepare("DELETE FROM email WHERE id = ?");
    if ($deleteStmt->execute([$emailId])) {
        $_SESSION['STATUS'] = "EMAIL_SUCCESS_DELETED";
    } else {
        $_SESSION['STATUS'] = "EMAIL_DELETE_FAILED";
    }
} else {
    $_SESSION['STATUS'] = "EMAIL_DELETE_FAILED";
}

header("Location: ../../emails.php");
exit;
?>
