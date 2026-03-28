<?php
require '../includes/conn.php'; 

try {
    // Check for ongoing elections
    $stmt = $pdo->prepare("SELECT id, name FROM voting_periods WHERE status = 'Ongoing' LIMIT 1");
    $stmt->execute();
    $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$votingPeriod) {
        echo json_encode(['error' => 'No ongoing elections']);
        exit;
    }

    $votingPeriodId = $votingPeriod['id'];
    $votingPeriodName = $votingPeriod['name'];

    // Fetch all positions
    $stmt = $pdo->prepare("SELECT id, name, level FROM positions ORDER BY level, name");
    $stmt->execute();
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch vote tallies and candidate details for each position
    $voteData = [];
    foreach ($positions as $position) {
        $stmt = $pdo->prepare("
            SELECT 
                c.id, 
                cr_name.value AS name, 
                cr_party.value AS party_name, 
                COUNT(v.id) AS vote_count
            FROM candidates c
            JOIN candidate_responses cr_name 
                ON c.id = cr_name.candidate_id 
            JOIN form_fields ff_name 
                ON cr_name.field_id = ff_name.id 
                AND ff_name.field_name = 'full_name'
            LEFT JOIN candidate_responses cr_party 
                ON c.id = cr_party.candidate_id 
            JOIN form_fields ff_party 
                ON cr_party.field_id = ff_party.id 
                AND ff_party.field_name = 'party'
            LEFT JOIN votes v 
                ON c.id = v.candidate_id 
                AND v.voting_period_id = ?
            JOIN candidate_responses cr_pos 
                ON c.id = cr_pos.candidate_id 
            JOIN form_fields ff_pos 
                ON cr_pos.field_id = ff_pos.id 
                AND ff_pos.field_name = 'position' 
                AND cr_pos.value = ?
            WHERE c.status = 'accept'
            GROUP BY c.id, cr_name.value, cr_party.value
        ");
        
        $stmt->execute([$votingPeriodId, $position['name']]);
        $voteData[$position['name']] = [
            'level' => $position['level'],
            'candidates' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    echo json_encode($voteData);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
