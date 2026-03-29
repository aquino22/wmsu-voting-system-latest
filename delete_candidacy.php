<?php
require 'includes/conn.php';
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* -------------------------------
   Auth + Method
--------------------------------*/
if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

/* -------------------------------
   Validation + Delete
--------------------------------*/
try {

    if (empty($_POST['id'])) {
        throw new Exception('Missing candidacy ID');
    }

    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception('Invalid candidacy ID');
    }

    /* Ensure candidacy exists */
    $stmt = $pdo->prepare("
        SELECT id
        FROM candidacy
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        throw new Exception('Candidacy record not found');
    }

    /* Delete */
    $stmt = $pdo->prepare("
        DELETE FROM candidacy
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Candidacy period deleted successfully'
    ]);

} catch (Exception $e) {

    error_log('delete_candidacy.php: ' . $e->getMessage());

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$pdo = null;
