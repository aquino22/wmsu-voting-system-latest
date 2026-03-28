<?php

$course_id = $_GET['course_id'];
$stmt = $pdo->prepare("SELECT year_level_id FROM course_year_levels WHERE course_id = ?");
$stmt->execute([$course_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
