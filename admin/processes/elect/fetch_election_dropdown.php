<?php
require '../../includes/conn.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $stmt = $pdo->prepare("
        SELECT 
            e.id AS election_id,
            e.election_name,
            e.start_period,
            e.end_period,
            a.year_label,
            a.start_date,
            a.end_date,
            a.semester
        FROM elections e
        INNER JOIN academic_years a 
            ON e.academic_year_id = a.id
        WHERE e.status = 'Ongoing'
        ORDER BY a.year_label DESC, a.semester DESC
    ");

    $stmt->execute();
    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($elections);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
