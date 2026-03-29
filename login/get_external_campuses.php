<?php
require('includes/conn.php');

$parent_id = $_GET['parent_id'] ?? null;

$stmt = $pdo->prepare("
    SELECT campus_id, campus_name
    FROM campuses
    WHERE parent_id = ?
");

$stmt->execute([$parent_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
