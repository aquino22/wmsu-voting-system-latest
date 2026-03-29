<?php
session_start();
require_once '../includes/conn.php'; // Your database connection file

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Check if token exists and is not expired
        $stmt = $pdo->prepare("
            SELECT email FROM voters 
            WHERE activation_token = ? 
            AND activation_expiry > NOW() 
            AND is_active = 0
        ");
        $stmt->execute([$token]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            $email = $user['email'];
            
            // Activate account in both tables
            $pdo->beginTransaction();
            
            // Update voters table
            $stmt = $pdo->prepare("
                UPDATE voters 
                SET is_active = 1, 
                    activation_token = NULL, 
                    activation_expiry = NULL 
                WHERE activation_token = ?
            ");
            $stmt->execute([$token]);
            
            // Update users table
            $stmt = $pdo->prepare("
                UPDATE users 
                SET is_active = 1 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            
            $pdo->commit();
            
            $_SESSION['STATUS'] = 'success';
            $_SESSION['MESSAGE'] = 'Account activated successfully! You can now login.';
        } else {
            $_SESSION['STATUS'] = 'error';
            $_SESSION['MESSAGE'] = 'Invalid or expired activation link.';
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['STATUS'] = 'error';
        $_SESSION['MESSAGE'] = 'Activation failed: ' . $e->getMessage();
    }
} else {
    $_SESSION['STATUS'] = 'error';
    $_SESSION['MESSAGE'] = 'No activation token provided.';
}

header("Location: ../index.php");
exit();
?>