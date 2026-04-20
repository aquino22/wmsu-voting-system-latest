<?php
session_start();
require_once '../includes/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['qrData']) || !isset($_POST['voting_period_id']) || !isset($_POST['candidate_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing QR data, voting period ID, or candidate ID']);
    exit;
}

$qrData = $_POST['qrData'];
$voting_period_id = (int)$_POST['voting_period_id'];
$candidate_id = (int)$_POST['candidate_id'];

try {
    $student_id = $qrData;
    $_SESSION['student_id'] = $student_id;

    // Begin transaction to ensure atomicity
    $pdo->beginTransaction();

    // Check if the voting period is active
    $period_stmt = $pdo->prepare("
        SELECT name, status 
        FROM voting_periods 
        WHERE id = :voting_period_id AND status = 'Ongoing'
    ");
    $period_stmt->execute([':voting_period_id' => $voting_period_id]);
    $voting_period = $period_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voting_period) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Voting period is not active or does not exist']);
        exit;
    }

    $election_name = $voting_period['name'];

    // Verify student precinct
    $precinct_stmt = $pdo->prepare("
        SELECT precinct 
        FROM precinct_voters 
        WHERE student_id = :student_id
    ");
    $precinct_stmt->execute([':student_id' => $student_id]);
    $precinct = $precinct_stmt->fetchColumn();

    if (!$precinct) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Student not assigned to a precinct']);
        exit;
    }

    // Verify candidate eligibility
    $candidate_stmt = $pdo->prepare("
        SELECT c.id 
        FROM candidates c
        JOIN registration_forms rf ON c.form_id = rf.id
        WHERE c.id = :candidate_id 
        AND rf.election_name = :election_name 
        AND rf.status = 'active' 
        AND c.status = 'accept'
    ");
    $candidate_stmt->execute([
        ':candidate_id' => $candidate_id,
        ':election_name' => $election_name
    ]);
    if (!$candidate_stmt->fetchColumn()) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Invalid candidate for this election']);
        exit;
    }

    // Check for existing vote and insert in one step using INSERT IGNORE
    $vote_stmt = $pdo->prepare("
        INSERT IGNORE INTO votes (voting_period_id, precinct, student_id, candidate_id) 
        VALUES (:voting_period_id, :precinct, :student_id, :candidate_id)
    ");
    $vote_stmt->execute([
        ':voting_period_id' => $voting_period_id,
        ':precinct' => $precinct,
        ':student_id' => $student_id,
        ':candidate_id' => $candidate_id
    ]);

    // Check if the insert was successful (rowCount = 1 means new vote, 0 means duplicate ignored)
    if ($vote_stmt->rowCount() == 0) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Student has already voted in this period']);
        exit;
    }

    // Commit the transaction
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Vote recorded successfully']);
} catch (PDOException $e) {
    $pdo->rollBack();
    if ($e->getCode() == '23000') { // Duplicate entry error (shouldn’t trigger with INSERT IGNORE, but kept as fallback)
        echo json_encode(['status' => 'error', 'message' => 'Student has already voted in this period']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
exit;
?>