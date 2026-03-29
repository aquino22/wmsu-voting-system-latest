<?php
session_start();
require '../includes/conn.php'; // Adjust path to your PDO connection file

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (empty($_POST['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing email']);
    exit;
}

$email = $_POST['email'];

try {
    $pdo->beginTransaction();

    // Delete from voters
    $stmt1 = $pdo->prepare("DELETE FROM voters WHERE email = :email");
    $stmt1->execute(['email' => $email]);

    // Delete from users
    $stmt2 = $pdo->prepare("DELETE FROM users WHERE email = :email");
    $stmt2->execute(['email' => $email]);

    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Voter deleted successfully']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete voter', 'error' => $e->getMessage()]);
}
?>
