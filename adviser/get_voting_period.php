<?php
session_start();
require_once 'includes/conn.php';

date_default_timezone_set('Asia/Manila');
$current_date = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['election_name'])) {
    $election_name = $_POST['election_name'];
    
    // Get voting period for the specified election
    $stmt = $pdo->prepare("
        SELECT *
        FROM voting_periods
        WHERE status = 'Ongoing' AND election_name = :election_name
        LIMIT 1
    ");
    $stmt->execute(['election_name' => $election_name]);
    $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$votingPeriod) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM voting_periods
            WHERE status = 'Paused' AND election_name = :election_name
            LIMIT 1
        ");
        $stmt->execute(['election_name' => $election_name]);
        $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$votingPeriod) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM voting_periods
            WHERE status = 'Scheduled' AND start_period >= NOW() AND election_name = :election_name
            ORDER BY start_period ASC
            LIMIT 1
        ");
        $stmt->execute(['election_name' => $election_name]);
        $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($votingPeriod) {
        $votingPeriodId = $votingPeriod['id'];
        $votingPeriodStart = $votingPeriod['start_period'];
        $votingPeriodEnd = $votingPeriod['end_period'] ?: 'TBD';
        $votingPeriodStatus = $votingPeriod['status'];

        $votingPeriodReStart = $votingPeriod['re_start_period'] ?: null;
        $votingPeriodReEnd = $votingPeriod['re_end_period'] ?: 'null';

        if (isset($votingPeriodReStart) && isset($votingPeriodReEnd)) {
            $remaining_seconds = 0;
            if ($votingPeriodStatus === 'Ongoing') {
                $remaining_seconds = strtotime($votingPeriodReEnd) - strtotime($current_date);
                $remaining_seconds = $remaining_seconds < 0 ? 0 : $remaining_seconds;
            } elseif ($votingPeriodStatus === 'Scheduled') {
                $remaining_seconds = strtotime($votingPeriodReStart) - strtotime($current_date);
                $remaining_seconds = $remaining_seconds < 0 ? 0 : $remaining_seconds;
            }
        } else {
            $remaining_seconds = 0;
            if ($votingPeriodStatus === 'Ongoing') {
                $remaining_seconds = strtotime($votingPeriodEnd) - strtotime($current_date);
                $remaining_seconds = $remaining_seconds < 0 ? 0 : $remaining_seconds;
            } elseif ($votingPeriodStatus === 'Scheduled') {
                $remaining_seconds = strtotime($votingPeriodStart) - strtotime($current_date);
                $remaining_seconds = $remaining_seconds < 0 ? 0 : $remaining_seconds;
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'voting_period_id' => $votingPeriodId,
            'remaining_seconds' => $remaining_seconds
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No voting period found for this election'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
}
?>