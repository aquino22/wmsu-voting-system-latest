<?php
require '../../includes/conn.php'; // Include your database connection
$query = "SELECT * FROM precincts ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$precincts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($precincts as &$row) {
    $row['created_at'] = date('F j, Y g:i A', strtotime($row['created_at']));
    $row['updated_at'] = date('F j, Y g:i A', strtotime($row['updated_at']));
}

echo json_encode($precincts);
?>
