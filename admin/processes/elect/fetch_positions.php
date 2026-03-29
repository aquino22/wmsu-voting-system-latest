<?php
require '../../includes/conn.php';

header('Content-Type: application/json');

try {
    $query = "
        SELECT 
            ay.year_label AS school_year,
            ay.semester,
            p.*,
            p.allowed_colleges,
            p.allowed_departments,
            e.election_name,
            e.status
        FROM positions p
        INNER JOIN elections e 
            ON p.election_id = e.id
        INNER JOIN academic_years ay
            ON e.academic_year_id = ay.id
        WHERE e.status IN ('Ongoing', 'Upcoming')
        ORDER BY e.start_period ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ensure restriction columns are never null so JS JSON.parse never throws
    foreach ($positions as &$row) {
        $row['allowed_colleges']    = $row['allowed_colleges']    ?? '[]';
        $row['allowed_departments'] = $row['allowed_departments'] ?? '[]';
    }
    unset($row);

    echo json_encode($positions);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred',
        'error' => $e->getMessage() // optional, for debugging
    ]);
}
