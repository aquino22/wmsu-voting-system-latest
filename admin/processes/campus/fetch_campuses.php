<?php
include('../../includes/conn.php');

header('Content-Type: application/json');

$stmt = $pdo->query("
    SELECT c.campus_id, c.campus_name, p.campus_name AS parent_name, c.campus_location, c.parent_id
    FROM campuses c
    LEFT JOIN campuses p ON c.parent_id = p.campus_id
    ORDER BY c.campus_id DESC
");
$campuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $campuses]);
