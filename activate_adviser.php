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
    // Activate the user with the same email
    $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE email = ?");
    $stmt->execute([$adviser['email']]);

    echo json_encode([
        'success' => true,
        'message' => 'Adviser activated successfully.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Adviser not found.'
    ]);
}
?>
