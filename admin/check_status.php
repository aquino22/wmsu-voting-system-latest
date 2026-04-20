<?php
// Database connection - This provides the $pdo variable
include('includes/conn.php');

// Set timezone to ensure accuracy
date_default_timezone_set('Asia/Manila');
$current_time = date('Y-m-d H:i:s');

// Initialize with an empty array for expired elections
$response = ['expired' => false, 'elections' => []];

try {
    // Removed LIMIT 1 to get all concluded elections
    $query = "SELECT vp.id AS voting_period_id, e.election_name 
              FROM voting_periods vp
              JOIN elections e ON vp.election_id = e.id
              WHERE vp.end_period <= :current_time 
              AND e.status = 'Ongoing'";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['current_time' => $current_time]);
    $expired_elections = $stmt->fetchAll();

    if ($expired_elections) {
        $response['expired'] = true;
        foreach ($expired_elections as $row) {
            $response['elections'][] = [
                'election_name' => $row['election_name'],
                'redirect_to' => 'publish_results.php?voting_period_id=' . $row['voting_period_id']
            ];
        }
    }
} catch (PDOException $e) {
    // Log error if needed
}

header('Content-Type: application/json');
echo json_encode($response);
