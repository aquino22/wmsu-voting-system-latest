<?php
header('Content-Type: application/json');
require_once 'includes/conn.php';

try {
    // Create PDO connection
   

    // Check if ID is provided
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'Voter ID is required']);
        exit;
    }

    $voterId = $_POST['id'];

    // Prepare and execute the delete query
    $stmt = $pdo->prepare("DELETE FROM voters WHERE id = :id");
    $stmt->bindParam(':id', $voterId, PDO::PARAM_INT);
    $stmt->execute();

    // Check if any rows were affected
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Voter deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Voter not found']);
    }
} catch (PDOException $e) {
    // Handle database errors
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Handle other errors
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>