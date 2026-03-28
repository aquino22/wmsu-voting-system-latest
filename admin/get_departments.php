<?php
require 'includes/conn.php';

$collegeId = $_GET['college_id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT department_id, department_name
    FROM departments
    WHERE college_id = ?
    ORDER BY department_name ASC
");
$stmt->execute([$collegeId]);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($departments);
