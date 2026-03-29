<?php
include('../../includes/conn.php');
header('Content-Type: application/json');

$stmt = $pdo->query("SELECT * FROM colleges ORDER BY college_id DESC");
$colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $colleges]);
