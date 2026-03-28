<?php
require 'includes/conn.php';
header('Content-Type: application/json');

$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing adviser ID.']);
    exit;
}

// Fetch adviser by ID
$stmt = $pdo->prepare("SELECT email FROM advisers WHERE id = ?");
$stmt->execute([$id]);
$adviser = $stmt->fetch();

if ($adviser) {
    // Deactivate user account
    $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE email = ?");
    $stmt->execute([$adviser['email']]);

    echo json_encode([
        'success' => true,
        'message' => 'Adviser deactivated successfully.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Adviser not found.'
    ]);
}
?>
