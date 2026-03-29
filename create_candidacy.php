<?php
require 'includes/conn.php';
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* -------------------------------
   1. Auth + Method
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
   2. Validation
--------------------------------*/
try {

    $required = ['election_id', 'start_period', 'end_period', 'status'];

    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing field: {$field}");
        }
    }

    $electionId = filter_var($_POST['election_id'], FILTER_VALIDATE_INT);
    $status     = trim($_POST['status']);

    if (!$electionId) {
        throw new Exception('Invalid election');
    }

    if (!in_array($status, ['Ongoing', 'Upcoming'], true)) {
        throw new Exception('Invalid status value');
    }

    $start = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['start_period']);
    $end   = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['end_period']);

    if (!$start || !$end) {
        throw new Exception('Invalid datetime format');
    }

    if ($start >= $end) {
        throw new Exception('End period must be after start period');
    }

    $start->setTime($start->format('H'), $start->format('i'), 0);
    $end->setTime($end->format('H'), $end->format('i'), 0);

    /* -------------------------------
       3. Election Validation
    --------------------------------*/
    $stmt = $pdo->prepare("
        SELECT start_period, end_period
        FROM elections
        WHERE id = ?
    ");
    $stmt->execute([$electionId]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$election) {
        throw new Exception('Election not found');
    }

    if (
        $start < new DateTime($election['start_period']) ||
        $end   > new DateTime($election['end_period'])
    ) {
        throw new Exception('Candidacy period must be within election schedule');
    }

    /* -------------------------------
       4. Insert
    --------------------------------*/
    $stmt = $pdo->prepare("
        INSERT INTO candidacy (
            election_id,
            start_period,
            end_period,
            status,
            created_at,
            updated_at
        ) VALUES (
            :election_id,
            :start_period,
            :end_period,
            :status,
            NOW(),
            NOW()
        )
    ");

    $stmt->execute([
        ':election_id'  => $electionId,
        ':start_period' => $start->format('Y-m-d H:i:s'),
        ':end_period'   => $end->format('Y-m-d H:i:s'),
        ':status'       => $status
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Candidacy period created successfully'
    ]);

} catch (Exception $e) {

    error_log('create_candidacy.php: ' . $e->getMessage());

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$pdo = null;
