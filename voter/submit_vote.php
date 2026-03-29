<?php
session_start();
include('includes/conn.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Get form data
    $student_id = $_POST['student_id'] ?? null;
    $precinct = $_POST['precinct'] ?? null;
    $voting_period_id = $_POST['voting_period_id'] ?? null;
    $votes = $_POST['vote'] ?? [];

    if (!$student_id || !$precinct || !$voting_period_id || empty($votes)) {
        throw new Exception("Missing required fields.");
    }

    // Check if the voter exists in precinct_voters
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM precinct_voters WHERE student_id = ? AND precinct = ?");
    $stmt->execute([$student_id, $precinct]);
    $voter_exists = $stmt->fetchColumn();

    if (!$voter_exists) {
        throw new Exception("Voter not registered in this precinct. Please contact support.");
    }

    // Check if the voter has already voted
    $stmt = $pdo->prepare("SELECT status FROM precinct_voters WHERE student_id = ? AND precinct = ?");
    $stmt->execute([$student_id, $precinct]);
    $status = $stmt->fetchColumn();

    if ($status === 'voted') {
        throw new Exception("You have already voted.");
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Insert votes
    foreach ($votes as $position => $candidate_id) {
        $stmt = $pdo->prepare("INSERT INTO votes (student_id, precinct, voting_period_id, candidate_id, position) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $precinct, $voting_period_id, $candidate_id, $position]);
    }

    // Update voter status to 'voted'
    $stmt = $pdo->prepare("UPDATE precinct_voters SET status = 'voted' WHERE student_id = ? AND precinct = ?");
    $stmt->execute([$student_id, $precinct]);

    // Commit transaction
    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Vote submitted successfully!']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit vote: ' . $e->getMessage()]);
}
?>