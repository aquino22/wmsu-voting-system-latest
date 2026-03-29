<?php
require('includes/conn.php');

$college_id = $_GET['college_id'];

$stmt = $pdo->prepare("
    SELECT department_id, department_name
    FROM departments
    WHERE college_id = ?
    ORDER BY department_name
");

$stmt->execute([$college_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
