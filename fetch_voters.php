<?php
header('Content-Type: application/json');
include('includes/conn.php');
try {
    // Fetch voter data from the voters table
    $stmt = $pdo->prepare("
        SELECT student_id, email, first_name, middle_name, last_name, 
               course, year_level, college, department 
        FROM voters
    ");
    $stmt->execute();
    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($voters);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>