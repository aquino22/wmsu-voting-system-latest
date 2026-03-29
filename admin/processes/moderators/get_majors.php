<?php
require '../../includes/conn.php'; // Include database connection
header('Content-Type: application/json');

$department_id = $_GET['department_id'] ?? null;

if (!$department_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT major_id, major_name FROM majors WHERE department_id = ? ORDER BY major_name");
    $stmt->execute([$department_id]);
    $majors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($majors);
} catch (PDOException $e) {
    echo json_encode([]);
}
