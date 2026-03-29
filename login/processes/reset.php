<?php
session_start();
require_once '../includes/conn.php'; // Your database connection file


$userEmail = $_POST['email'];

// Handle AJAX password reset
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($newPassword) || empty($confirmPassword)) {
        echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match.']);
        exit;
    }

    if (strlen($newPassword) < 8) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long.']);
        exit;
    }

    try {


        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Begin transaction
        if (!$pdo->beginTransaction()) {
            throw new PDOException('Failed to start database transaction.');
        }

        // Update password in users table
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $userEmail]);

        // Update password in voters table
        $stmt = $pdo->prepare("UPDATE voters SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $userEmail]);

        // Commit transaction
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }

        echo json_encode(['success' => true, 'message' => 'Password reset successfully. Please wait for redirection!']);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Password reset failed: ' . $e->getMessage()]);
    }
    exit;
}
