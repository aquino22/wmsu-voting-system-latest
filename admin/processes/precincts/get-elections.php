<?php
require '../../includes/conn.php'; // Include your database connection
header('Content-Type: application/json');
// Fetch elections from the database
$query = "SELECT id, election_name, school_year_start, school_year_end  FROM elections WHERE status = 'Ongoing'";
$stmt = $pdo->prepare($query);
$stmt->execute();
$elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return the elections as JSON

echo json_encode(['elections' => $elections]);
?>
