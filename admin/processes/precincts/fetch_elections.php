<?php
header('Content-Type: application/json');
require '../../includes/conn.php';

try {
    $stmt = $pdo->prepare("
        SELECT 
            e.id,
            e.election_name,
         
            e.academic_year_id,

            ay.semester,
            ay.start_date,
            ay.end_date,

            YEAR(ay.start_date) AS school_year_start,
            YEAR(ay.end_date)   AS school_year_end

        FROM elections e
        INNER JOIN academic_years ay
            ON ay.id = e.academic_year_id
        WHERE e.status = 'Ongoing'
    ");

    $stmt->execute();
    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'elections' => $elections
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch elections'
    ]);
}
