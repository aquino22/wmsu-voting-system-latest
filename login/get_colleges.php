<?php
require('includes/conn.php');

$query = $pdo->query("SELECT college_id, college_name FROM colleges ORDER BY college_name");

echo json_encode($query->fetchAll(PDO::FETCH_ASSOC));
