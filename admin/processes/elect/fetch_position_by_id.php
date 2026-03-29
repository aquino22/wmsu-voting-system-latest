<?php

require '../../includes/conn.php';
header('Content-Type: application/json');

try {
  

    // Check if position_id is provided
    if (!isset($_GET['position_id']) || empty($_GET['position_id'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Position ID is required'
        ]);
        exit;
    }

    // Check if position_id is provided
    if (!isset($_GET['position_id']) || empty($_GET['position_id'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Position ID is required'
        ]);
        exit;
    }

    $positionId = $_GET['position_id'];

    // Fetch position data
    $stmt = $pdo->prepare("SELECT name AS position_name, level, party FROM positions WHERE id = ?");
    $stmt->execute([$positionId]);
    $position = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$position) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Position not found'
        ]);
        exit;
    }

    // Handle parties
    $parties = [];
    if (!empty($position['party'])) {
        // Assuming 'party' is a comma-separated list of party names
        $parties = array_map('trim', explode(',', $position['party']));
    }

    // Return the data
    echo json_encode([
        'status' => 'success',
        'position_name' => $position['position_name'],
        'level' => $position['level'],
        'parties' => $parties
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

