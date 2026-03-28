<?php
require_once 'includes/conn.php';
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if this is an admin vote
$isAdminVote = isset($_POST['admin_vote']) && $_POST['admin_vote'] == 1;

if (!$isAdminVote) {
    echo json_encode(['success' => false, 'message' => 'Admin vote flag not set']);
    exit;
}

$votingPeriodId = $_POST['voting_period_id'] ?? 0;
$votes = $_POST['vote'] ?? [];

try {
     $pdo->beginTransaction();
    
    // Create a temporary precinct_voters record for admin votes
    $adminStudentId = 'admin-' . time();
    $precinct = 'admin-precinct';
    
    // Insert into precinct_voters first
    $stmt = $pdo->prepare("
        INSERT INTO precinct_voters 
        (student_id, precinct, status, created_at) 
        VALUES (?, ?, 'voted', NOW())
        ON DUPLICATE KEY UPDATE status='voted'
    ");
    $stmt->execute([$adminStudentId, $precinct]);
    
    // Then insert votes
    foreach ($votes as $position => $candidateId) {
        $stmt = $pdo->prepare("
            INSERT INTO votes 
            (voting_period_id, position, candidate_id, student_id, precinct, vote_timestamp) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $votingPeriodId,
            $position,
            $candidateId,
            $adminStudentId,
            $precinct
        ]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Admin vote recorded successfully'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error recording vote: ' . $e->getMessage()
    ]);
}
?>