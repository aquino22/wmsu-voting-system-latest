<?php
require('includes/conn.php');

$college_id = $_GET['college_id'];

$stmt = $pdo->prepare("
    SELECT id, course_name
    FROM courses
    WHERE college_id = ?
    ORDER BY course_name
");

$stmt->execute([$college_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
