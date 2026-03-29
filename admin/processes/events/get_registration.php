<?php
require '../../includes/conn.php'; // Include database connection

if (isset($_GET['event_id']) && is_numeric($_GET['event_id'])) {
    $event_id = $_GET['event_id'];

    try {
        $stmt = $pdo->prepare("SELECT registration_start, registration_deadline FROM events WHERE id = :event_id");
        $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            echo json_encode(["success" => true, "data" => $event]);
        } else {
            echo json_encode(["success" => false, "error" => "Event not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid event ID"]);
}
?>
