<?php
include('../../includes/conn.php');
header('Content-Type: application/json');

$stmt = $pdo->query("
    SELECT m.major_id, m.major_name, d.department_name, c.college_name, m.department_id
    FROM majors m
    JOIN departments d ON m.department_id = d.department_id
    JOIN colleges c ON d.college_id = c.college_id
    ORDER BY m.major_id DESC
");
$majors = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $majors]);
