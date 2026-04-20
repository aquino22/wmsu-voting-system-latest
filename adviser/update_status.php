<?php
session_start();
require 'includes/conn.php';

// Handle status update
if (isset($_GET['action']) && $_GET['action'] === 'update_status') {
    try {
        $student_id = isset($_GET['voter_id']) ? trim($_GET['voter_id']) : '';
        $status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';

        // Validate input
        if (empty($student_id) || empty($status)) {
            throw new Exception('Missing student ID or status.');
        }
        if (!in_array($status, ['unverified', 'verified', 'active', 'pending'])) {
            throw new Exception('Invalid status value.');
        }

        // Update status in precinct_voters
        $stmt = $pdo->prepare("
            UPDATE precinct_voters
            SET status = ?
            WHERE student_id = ?
        ");
        $stmt->execute([$status, $student_id]);

        // Check if any rows were affected
        if ($stmt->rowCount() === 0) {
            throw new Exception('No voter found with the provided student ID.');
        }

        // Set success message
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => "Voter status updated to " . strtoupper($status) . "!"
        ];
    } catch (Exception $e) {
        error_log("Error updating voter status: " . $e->getMessage());
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Error updating status: ' . $e->getMessage()
        ];
    }

    // Redirect to avoid resubmission
    header('Location: voter-list.php');
    exit;
}

// Fetch voters for display
try {
    $stmt = $pdo->prepare("
        SELECT pv.student_id, cr_name.value AS full_name, pv.status
        FROM precinct_voters pv
        LEFT JOIN candidate_responses cr_name 
            ON pv.student_id = cr_name.value
        LEFT JOIN form_fields ff_name 
            ON cr_name.field_id = ff_name.id 
            AND ff_name.field_name = 'student_id'
        WHERE pv.status IN ('unverified', 'verified', 'active', 'pending')
    ");
    $stmt->execute();
    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching voters: " . $e->getMessage());
    $voters = [];
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Error fetching voters: ' . $e->getMessage()
    ];
}
?>