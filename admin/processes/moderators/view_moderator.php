
<?php
require '../../includes/conn.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, email, gender, college, department, precinct, created_at 
            FROM moderators 
            WHERE id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $moderator = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($moderator) {
            echo json_encode(['success' => true, 'data' => $moderator]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Moderator not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>