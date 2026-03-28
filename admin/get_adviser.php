<?php
include('includes/conn.php');
$id = $_POST['id'];

$stmt = $pdo->prepare("SELECT * FROM advisers WHERE id = ?");
$stmt->execute([$id]);
$adviser = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($adviser);
