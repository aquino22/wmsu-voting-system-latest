<?php
require '../../includes/conn.php'; // Database connection

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ["status" => "error", "message" => "Something went wrong"];

try {
    // Ensure database connection is working
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    $stmt = $pdo->query("SELECT id, event_title, cover_image, event_details, registration_enabled, registration_start, registration_deadline, status FROM events WHERE LOWER(status) IN ('published', 'ended') ORDER BY id DESC");


    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($events) {
        $response = ["status" => "success", "events" => $events];
    } else {
        $response = ["status" => "success", "events" => []];
    }
} catch (PDOException $e) {
    $response["message"] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

// Ensure valid JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
