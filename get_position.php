<?php
require '../../includes/conn.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Position ID required']);
    exit;
}

$positionId = filter_var($_GET['id'], FILTER_VALIDATE_INT);

try {
    // Get position data
    $stmt = $pdo->prepare("SELECT * FROM positions WHERE id = ?");
    $stmt->execute([$positionId]);
    $position = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$position) {
        throw new Exception("Position not found");
    }
    
    // Get associated parties (if you have a many-to-many relationship)
    $partyStmt = $pdo->prepare("SELECT party_id FROM position_parties WHERE position_id = ?");
    $partyStmt->execute([$positionId]);
    $partyIds = $partyStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $position['party'] = $partyIds;
    
    echo json_encode($position);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>