<?php
require '../../includes/conn.php';

header('Content-Type: application/json');

if (!isset($_GET['position_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Position ID not provided']);
    exit;
}

$positionId = $_GET['position_id'];

try {
    // Fetch position details
    $stmt = $pdo->prepare("SELECT * FROM positions WHERE id = ?");
    $stmt->execute([$positionId]);
    $position = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$position) {
        echo json_encode(['status' => 'error', 'message' => 'Position not found']);
        exit;
    }

    // Fetch associated parties for this position
    $stmt = $pdo->prepare("SELECT party_name FROM position_parties WHERE position_id = ?");
    $stmt->execute([$positionId]);
    $associatedParties = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'status' => 'success',
        'position' => [
            'id' => $position['id'],
            'name' => $position['name'],
            'level' => $position['level']
        ],
        'parties' => $associatedParties
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>