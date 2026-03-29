<?php
require '../../includes/conn.php'; // Include database connection

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT college_id, college_name FROM colleges ORDER BY college_name");
    $stmt->execute();
    $colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($colleges);
} catch (PDOException $e) {
    echo json_encode([]);
}
