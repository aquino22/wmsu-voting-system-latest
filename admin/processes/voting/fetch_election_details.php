<?php
session_start();
require '../../includes/conn.php';

header('Content-Type: application/json');

try {
    $election_name = $_GET['election_name'] ?? '';
    if (empty($election_name)) {
        throw new Exception("Election name is required");
    }

    $stmt = $pdo->prepare("SELECT election_name, semester, school_year_start, school_year_end FROM elections WHERE election_name = ?");
    $stmt->execute([$election_name]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($election) {
        echo json_encode(['status' => 'success', 'data' => $election]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Election not found']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>