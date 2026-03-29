<?php
header('Content-Type: application/json');

try {
    require '../../includes/conn.php';
    if (!$pdo) {
        throw new Exception('Database connection failed.');
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to connect to the database.'
    ]);
    exit;
}

// Validate input
$election_name = isset($_POST['election_id']) ? trim($_POST['election_id']) : null;



if (empty($election_name)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Election name is required.'
    ]);
    exit;
}

try {
    $sql = "
        SELECT 
            e.election_name,
            a.semester,
            a.start_date,
            a.end_date,
            e.start_period
        FROM elections e
        INNER JOIN academic_years a 
            ON e.academic_year_id = a.id
        WHERE e.id = :election_id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':election_id', $election_name, PDO::PARAM_STR);
    $stmt->execute();

    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($election) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'school_year_start' => $election['start_date'],
                'school_year_end'   => $election['end_date'],
                'election_name' => $election['election_name'],
                'semester'   => $election['semester'],
                'start_date' => $election['start_date'],
                'end_date'   => $election['end_date'],
                'start_period' => $election['start_period']
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Election not found.'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error.'
    ]);
    exit;
}
