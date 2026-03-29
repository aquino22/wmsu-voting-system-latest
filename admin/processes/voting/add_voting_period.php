<?php
session_start();
require '../../includes/conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit;
}

try {
    // Expected POST fields
    $election_id  = $_POST['election_id'] ?? null;
    $start_period = $_POST['start_period'] ?? null;
    $end_period   = $_POST['end_period'] ?? null;
    $status       = $_POST['status'] ?? null;

    // Validation
    if (!$election_id || !$start_period || !$end_period || !$status) {
        throw new Exception('All fields are required.');
    }

    $start = new DateTime($start_period);
    $end   = new DateTime($end_period);

    if ($start >= $end) {
        throw new Exception('End period must be after start period.');
    }

    // Fetch academic year dates for validation
    $stmt_ay = $pdo->prepare("
        SELECT ay.start_date, ay.end_date 
        FROM academic_years ay
        JOIN elections e ON ay.id = e.academic_year_id
        WHERE e.id = ?
    ");
    $stmt_ay->execute([$election_id]);
    $academic_year = $stmt_ay->fetch(PDO::FETCH_ASSOC);

    if (!$academic_year) {
        throw new Exception("Invalid academic year selected for the election.");
    }

    $ay_start_date = new DateTime($academic_year['start_date']);
    $ay_end_date = new DateTime($academic_year['end_date']);
    $ay_end_date->setTime(23, 59, 59); // Include the whole end day

    // Prevent duplicate voting period per election
    $check = $pdo->prepare("
        SELECT COUNT(*) 
        FROM voting_periods 
        WHERE election_id = ?
    ");
    $check->execute([$election_id]);

    if ($check->fetchColumn() > 0) {
        throw new Exception('A voting period already exists for this election.');
    }

    // Check if voting dates are within the academic year range
    if ($start < $ay_start_date || $end > $ay_end_date) {
        throw new Exception("Voting dates must be within the selected academic year's period (" . $ay_start_date->format('M j, Y') . " to " . $ay_end_date->format('M j, Y') . ").");
    }

    // Insert
    $stmt = $pdo->prepare("
        INSERT INTO voting_periods (
            election_id,
            start_period,
            end_period,
            status
        ) VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $election_id,
        $start_period,
        $end_period,
        $status
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Voting period added successfully.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

exit;
