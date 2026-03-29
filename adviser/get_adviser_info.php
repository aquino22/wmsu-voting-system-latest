<?php
session_start();
require '../includes/conn.php'; // Adjust path to your PDO connection file

header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No user email found in session. Please log in.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT college, department, year FROM advisers WHERE email = ?");
    $stmt->execute([$_SESSION['email']]);
    $adviser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($adviser && !empty($adviser['college']) && !empty($adviser['department'])) {
        echo json_encode([
            'status' => 'success',
            'college' => $adviser['college'],
            'department' => $adviser['department'],
             'year_level' => $adviser['year'],
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No college or department found for this adviser.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    error_log("Get Adviser Info Error: " . $e->getMessage());
}
exit;
?>