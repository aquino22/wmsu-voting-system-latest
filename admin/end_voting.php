<?php
require_once 'includes/conn.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['election_id'])) {
    $election_id = $_GET['election_id'];

    try {
        // Fetch event_id
        $stmt = $pdo->prepare("SELECT id FROM events WHERE candidacy = (SELECT election_name FROM elections WHERE id = :election_id)");
        $stmt->execute([':election_id' => $election_id]);
        $event_id = $stmt->fetchColumn();
        if (!$event_id) {
            throw new Exception("Event not found for this election.");
        }

        // Update end_date to current time
        $end_date = date('Y-m-d H:i:s'); // Current date and time in database format
        $stmt = $pdo->prepare("UPDATE voting_periods SET end_date = :end_date WHERE event_id = :event_id AND end_date IS NULL");
        $stmt->execute([
            ':event_id' => $event_id,
            ':end_date' => $end_date
        ]);

        if ($stmt->rowCount() == 0) {
            throw new Exception("Voting period already ended or not found.");
        }

        // Format end_date for readable display
        $readable_end_date = new DateTime($end_date);
        $formatted_end_date = $readable_end_date->format('F j, Y, g:i A'); // e.g., "March 5, 2025, 2:30 PM"

        $_SESSION['STATUS'] = "VOTING_ENDED";
        $_SESSION['MESSAGE'] = "Voting ended successfully on $formatted_end_date.";
    } catch (Exception $e) {
        $_SESSION['STATUS'] = "VOTING_END_ERROR";
        $_SESSION['MESSAGE'] = $e->getMessage();
    }
} else {
    $_SESSION['STATUS'] = "VOTING_END_ERROR";
    $_SESSION['MESSAGE'] = "Invalid request method or missing election_id.";
}

$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: $referer");
exit;
?>