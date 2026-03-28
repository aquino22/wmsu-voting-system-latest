<?php
require 'includes/conn.php';

$id = $_POST['id'];

try {
    $pdo->beginTransaction();
    
    // Get adviser email first
    $stmt = $pdo->prepare("SELECT email FROM advisers WHERE id = ?");
    $stmt->execute([$id]);
    $adviser = $stmt->fetch();
    
    if ($adviser) {
        // Delete from users table
        $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
        $stmt->execute([$adviser['email']]);
        
        // Delete from advisers table
        $stmt = $pdo->prepare("DELETE FROM advisers WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
      echo json_encode([
    'success' => true,
    'message' => 'Adviser deleted successfully.'
]);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Adviser not found']);
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>