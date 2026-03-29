<?php
require('includes/conn.php');

$stmt = $pdo->prepare("
    SELECT campus_id, campus_name
    FROM campuses
    WHERE parent_id IS NULL 
");

$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
