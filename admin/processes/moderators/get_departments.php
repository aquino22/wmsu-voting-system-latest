<?php
require '../../includes/conn.php'; // Include database connection

header('Content-Type: application/json');

$college_id = $_GET['college_id'] ?? null;

if (!$college_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT department_id, department_name FROM departments WHERE college_id = ? ORDER BY department_name");
    $stmt->execute([$college_id]);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($departments);
} catch (PDOException $e) {
    echo json_encode([]);
}
