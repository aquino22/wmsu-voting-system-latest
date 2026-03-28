<?php
require '../../includes/conn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$positionId = $_POST['position_id'] ?? null;
$positionName = $_POST['position'] ?? null;
$level = $_POST['level'] ?? null;
$parties = $_POST['parties'] ?? [];

if (!$positionId || !$positionName || !$level) {
    echo json_encode(['status' => 'error', 'message' => 'Required fields are missing']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Update position
    $stmt = $pdo->prepare("UPDATE positions SET name = ?, level = ? WHERE id = ?");
    $stmt->execute([$positionName, $level, $positionId]);

    // Remove existing party associations
    $stmt = $pdo->prepare("DELETE FROM position_parties WHERE position_id = ?");
    $stmt->execute([$positionId]);

    // Add new party associations
    if (!empty($parties)) {
        $stmt = $pdo->prepare("INSERT INTO position_parties (position_id, party_name) VALUES (?, ?)");
        foreach ($parties as $party) {
            $stmt->execute([$positionId, $party]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Position updated successfully']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>