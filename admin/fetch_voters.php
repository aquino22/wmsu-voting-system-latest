<?php
header('Content-Type: application/json');
include('includes/conn.php');
try {
    // Fetch voter data from the voters table
    $stmt = $pdo->prepare("
        SELECT v.student_id, v.email, v.first_name, v.middle_name, v.last_name, 
               v.course, v.year_level, v.college, v.department,
               GROUP_CONCAT(DISTINCT e.election_name SEPARATOR ', ') AS voted_elections
        FROM voters v
        LEFT JOIN votes vo ON v.student_id = vo.student_id
        LEFT JOIN voting_periods vp ON vo.voting_period_id = vp.id
        LEFT JOIN elections e ON vp.election_id = e.id
        GROUP BY v.student_id, v.email, v.first_name, v.middle_name, v.last_name, 
               v.course, v.year_level, v.college, v.department
    ");
    $stmt->execute();
    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $voters]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
