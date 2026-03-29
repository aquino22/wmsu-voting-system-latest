<?php
session_start();
require_once '../includes/conn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

if (!isset($_POST['qrData'], $_POST['voting_period_id'], $_POST['precinct_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$qrData = $_POST['qrData'];
$voting_period_id = (int)$_POST['voting_period_id'];
$precinct_name = $_POST['precinct_id'];


try {

    // ---------------------------------------------------------
    // STEP 1: EXTRACT STUDENT ID + VOTING PERIOD ID FROM QR
    // ---------------------------------------------------------
    $parts = array_map('trim', explode('_', $qrData));

    if (count($parts) < 4) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid QR format']);
        exit;
    }

    $student_id    = $parts[0];        // 2020-01524
    $qr_period_id  = (int)$parts[3];   // 41 (correct)







    if ($qr_period_id !== $voting_period_id) {
        echo json_encode([
            'status' => 'error',
            'message' => 'QR voting period does not match selected voting period'
        ]);
        exit;
    }

    // ---------------------------------------------------------
    // STEP 2: GET ELECTION NAME FROM voting_periods
    // ---------------------------------------------------------
    $vp = $pdo->prepare("
  SELECT 
        e.election_name as election_name
    FROM voting_periods vp
    JOIN elections e 
        ON vp.election_id = e.id
    WHERE vp.id = :id
      AND vp.status = 'Ongoing'
    LIMIT 1
    ");
    $vp->execute([':id' => $voting_period_id]);
    $vpData = $vp->fetch(PDO::FETCH_ASSOC);

    if (!$vpData) {
        echo json_encode(['status' => 'error', 'message' => 'Voting period not active']);
        exit;
    }

    $election_name = $vpData['election_name'];

    // ---------------------------------------------------------
    // STEP 3: CHECK IF STUDENT EXISTS IN precinct_voters
    // ---------------------------------------------------------
    // ---------------------------------------------------------
    // STEP 3: CHECK IF STUDENT EXISTS IN THIS SPECIFIC PRECINCT
    // ---------------------------------------------------------
    $stmt = $pdo->prepare("
    SELECT precinct
    FROM precinct_voters
    WHERE student_id = :student_id
      AND precinct = :precinct
");
    $stmt->execute([
        ':student_id' => $student_id,
        ':precinct'   => $precinct_name
    ]);

    $pv = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pv) {
        echo json_encode([
            'status' => 'error',
            'message' => "Student does not belong to precinct: $precinct_name"
        ]);
        exit;
    }


    // ---------------------------------------------------------
    // STEP 5: STUDENT IS VALID → CHECK CURRENT STATUS
    // ---------------------------------------------------------
    $stmtStatus = $pdo->prepare("
    SELECT status
    FROM precinct_voters
    WHERE student_id = :student_id AND precinct = :precinct
    LIMIT 1
");
    $stmtStatus->execute([
        ':student_id' => $student_id,
        ':precinct'   => $precinct_name
    ]);
    $currentStatus = $stmtStatus->fetchColumn();

    if ($currentStatus === 'pending') {
        // Already pending
        echo json_encode([
            'status' => 'info',
            'message' => 'Student is already marked as pending',
            'student_id' => $student_id,
            'precinct' => $precinct_name,
            'election_name' => $election_name,
            'voting_period_id' => $voting_period_id
        ]);
        exit;
    }

    // ---------------------------------------------------------
    // UPDATE STATUS TO PENDING
    // ---------------------------------------------------------
    $update = $pdo->prepare("
    UPDATE precinct_voters
    SET status = 'pending'
    WHERE student_id = :student_id AND precinct = :precinct
");
    $update->execute([
        ':student_id' => $student_id,
        ':precinct'   => $precinct_name
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Student verified and marked pending',
        'student_id' => $student_id,
        'precinct' => $precinct_name,
        'election_name' => $election_name,
        'voting_period_id' => $voting_period_id
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

exit;
