<?php
session_start();
require '../../includes/conn.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['STATUS'] = "NON_ADMIN";
    header("Location: ../index.php");
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    $event_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0);
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    if ($event_id <= 0) {
        throw new Exception("Invalid event ID");
    }
    if (!in_array($status, ['accept', 'decline'])) {
        throw new Exception("Invalid status value");
    }

    // Verify event exists and get candidates
    $update_stmt = $pdo->prepare("
        UPDATE candidates c
        JOIN registration_forms rf ON c.form_id = rf.id
        JOIN events e ON rf.election_name = e.candidacy
        SET c.status = ?
        WHERE e.id = ? AND c.status = 'pending'
    ");
    $update_stmt->execute([$status === 'accept' ? 'accepted' : 'declined', $event_id]);

  
    
    $affected_rows = $update_stmt->rowCount();
    
    if ($affected_rows > 0) {
        $_SESSION['STATUS'] = "ACCEPT_CANDIDACY_ALL_ADMIN";
    
    } else {
        $_SESSION['STATUS'] = "REJECT_CANDIDACY_ALL_ADMIN";
      
    }
    
    header("Location:" . $_SERVER['HTTP_REFERER']);
    exit();

} catch (Exception $e) {
    $_SESSION['STATUS'] = "CANDIDATE_UPDATE_ERROR";
    $_SESSION['ERROR_MESSAGE'] = $e->getMessage();
    header("Location:" . $_SERVER['HTTP_REFERER']);
    exit();
}
?>