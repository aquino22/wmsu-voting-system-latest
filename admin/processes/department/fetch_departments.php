<?php
include('../../includes/conn.php');
header('Content-Type: application/json');

$stmt = $pdo->query("
    SELECT d.department_id, d.department_name, c.college_name, d.college_id
    FROM departments d
    JOIN colleges c ON d.college_id = c.college_id
    ORDER BY d.department_id DESC
");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $departments]);
