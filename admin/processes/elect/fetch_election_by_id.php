<?php
header('Content-Type: application/json');

require '../../includes/conn.php';

try {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid election ID.']);
        exit;
    }

    $query = "SELECT * FROM elections WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($election) {
        echo json_encode(['status' => 'success', 'data' => $election]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Election not found.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>