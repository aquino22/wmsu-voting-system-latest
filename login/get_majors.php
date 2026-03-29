<?php
require('includes/conn.php');

$course_id = $_GET['course_id'];

$stmt = $pdo->prepare("SELECT major_id, major_name FROM majors WHERE course_id = ?");
$stmt->execute([$course_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
