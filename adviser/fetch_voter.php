<?php
require '../includes/conn.php'; // Adjust path to your PDO connection file
// Get student_id from POST request
$student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';

if (empty($student_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}


    // Query to fetch voter data including cor1 and cor2
    $stmt = $pdo->prepare("SELECT student_id, email, first_name, middle_name, last_name, college, course, department, year_level, wmsu_campus, external_campus, first_cor, second_cor, status FROM voters WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $voter = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($voter) {
        // Optionally prepend base URL to image paths if stored as relative paths
        $baseUrl = '../login/uploads/'; // Adjust to your domain or leave empty if paths are absolute
        $voter['cor1'] = !empty($voter['first_cor']) ? $baseUrl . $voter['first_cor'] : '';
        $voter['cor2'] = !empty($voter['second_cor']) ? $baseUrl . $voter['second_cor'] : '';
        echo json_encode(['success' => true, 'data' => $voter]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Voter not found']);
    }

?>