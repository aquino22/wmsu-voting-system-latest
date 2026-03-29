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
$election_name = isset($_GET['election_name']) ? trim($_GET['election_name']) : null;

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
            a.semester,
            a.start_date,
            a.end_date,
            e.start_period
        FROM elections e
        INNER JOIN academic_years a 
            ON e.academic_year_id = a.id
        WHERE e.election_name = :election_name
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':election_name', $election_name, PDO::PARAM_STR);
    $stmt->execute();

    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($election) {
        echo json_encode([
            'status' => 'success',
            'data' => [
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
