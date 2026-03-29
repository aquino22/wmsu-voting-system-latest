<?php
header('Content-Type: application/json');

require '../../includes/conn.php'; // Include your database connection

try {
  

    // Query to fetch precinct names
    $stmt = $pdo->query('SELECT name FROM precincts');
    $precincts = $stmt->fetchAll();

    // Extract names into a simple array
    $precinctNames = array_column($precincts, 'name');

    // Return JSON response
    echo json_encode($precinctNames);

} catch (PDOException $e) {
    // Log error (in production, use proper logging)
    error_log('Database error: ' . $e->getMessage());

    // Return empty array on error
    http_response_code(500);
    echo json_encode([]);
}
?>