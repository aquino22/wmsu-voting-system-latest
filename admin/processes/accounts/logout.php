<?php
session_start();
require_once '../../includes/conn.php'; // Database connection

// Capture user data before destroying the session
$user_id = $_SESSION['user_id'] ?? null;
// Get location from URL parameter, default to 'N/A'
$location = $_GET['location'] ?? 'N/A';

if ($user_id) {
    try {
        // Set timezone to Philippine Standard Time
        date_default_timezone_set('Asia/Manila');

        // Gather data for user_activities
        $action = 'LOGOUT';
        $timestamp = date('Y-m-d H:i:s'); // Current time in PH
        $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'; // Browser/Device info
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; // User's IP address
        $behavior_patterns = 'Successful logout'; // Basic description

   

        // Log the logout activity
        $activityStmt = $pdo->prepare("
            INSERT INTO user_activities (
                user_id, action, timestamp, device_info, ip_address, location, behavior_patterns
            ) VALUES (
                :user_id, :action, :timestamp, :device_info, :ip_address, :location, :behavior_patterns
            )
        ");
        $activityStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $activityStmt->bindParam(':action', $action, PDO::PARAM_STR);
        $activityStmt->bindParam(':timestamp', $timestamp, PDO::PARAM_STR);
        $activityStmt->bindParam(':device_info', $device_info, PDO::PARAM_STR);
        $activityStmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
        $activityStmt->bindParam(':location', $location, PDO::PARAM_STR);
        $activityStmt->bindParam(':behavior_patterns', $behavior_patterns, PDO::PARAM_STR);
        $activityStmt->execute();
    } catch (PDOException $e) {
        error_log("Failed to log logout activity: " . $e->getMessage());
    }
}

// Clear and destroy the session
session_unset();
session_destroy();

// Start a new session to set the logout status
session_start();
$_SESSION['STATUS'] = "LOGOUT_SUCCESSFUL";

// Redirect to index
header("Location: ../../../index.php");
exit();
?>