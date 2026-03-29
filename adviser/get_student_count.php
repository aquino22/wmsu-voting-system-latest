<?php
require_once '../includes/conn.php';
session_start();
header('Content-Type: application/json');
// no extra whitespace, no echo, no var_dump


// Verify session
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Get adviser info
    $stmt = $pdo->prepare("SELECT college_id as college, department_id as department, wmsu_campus_id as wmsu_campus, external_campus_id as external_campus, year_level as year FROM advisers WHERE email = ?");
    $stmt->execute([$_SESSION['email']]);
    $adviser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$adviser) {
        echo json_encode(['status' => 'error', 'message' => 'Adviser not found']);
        exit;
    }

    // Build query based on adviser's scope
    $params = [
        $adviser['college'],
        $adviser['department'],
        $adviser['wmsu_campus'],
        $adviser['year']
    ];

    if ($adviser['external_campus'] === 'None' || is_null($adviser['external_campus'])) {
        $query = "SELECT COUNT(*) as total FROM voters 
                 WHERE college = ? AND department = ? AND wmsu_campus = ? 
                 AND (external_campus IS NULL OR external_campus = 'None') 
                 AND year_level = ?
                 AND STATUS = 'confirmed'";
    } else {
        $query = "SELECT COUNT(*) as total FROM voters 
                 WHERE college = ? AND department = ? AND wmsu_campus = ? 
                 AND external_campus = ? 
                 AND year_level = ?
                 AND STATUS = 'confirmed'";
        array_splice($params, 3, 0, [$adviser['external_campus']]);
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $count = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'total' => (int)$count['total']
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
