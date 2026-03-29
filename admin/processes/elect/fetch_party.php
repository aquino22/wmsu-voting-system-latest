<?php
require '../../includes/conn.php';
header('Content-Type: application/json');

try {
    // Prepare a query to fetch approved parties with ongoing elections
    $query = "
    SELECT p.id, p.name
    FROM parties p
    INNER JOIN elections e ON p.election_id = e.id
    WHERE p.status = 'Approved' 
    AND e.status IN ('Ongoing', 'Upcoming')
";


    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $parties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the result as JSON
    echo json_encode($parties);
} catch (PDOException $e) {
    // Handle database errors
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Handle other errors
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
