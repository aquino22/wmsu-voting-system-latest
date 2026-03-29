<?php
session_start();
include('includes/conn.php');

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT id FROM voting_periods WHERE status = 'Ongoing' LIMIT 1");
    $stmt->execute();
    $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$votingPeriod) {
        throw new Exception("No ongoing voting period found.");
    }
    $votingPeriodId = $votingPeriod['id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE voting_period_id = ?");
    $stmt->execute([$votingPeriodId]);
    $totalVotesCast = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM voters");
    $stmt->execute();
    $registeredVoters = $stmt->fetchColumn() ?: 1;

    $voterTurnout = round(($totalVotesCast / $registeredVoters) * 100, 1);

    $stmt = $pdo->prepare("
        SELECT 
            cr_name.value AS candidate_name, 
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
        WHERE c.status = 'accept'
        GROUP BY c.id, cr_name.value, cr_party.value
        ORDER BY vote_count DESC
        LIMIT 1
    ");
    $stmt->execute([$votingPeriodId]);
    $leading = $stmt->fetch(PDO::FETCH_ASSOC);
    $leadingName = $leading ? ($leading['party_name'] ?: $leading['candidate_name']) : 'N/A';

    $availableVotes = $registeredVoters - $totalVotesCast;

    echo json_encode([
        'totalVotesCast' => $totalVotesCast,
        'registeredVoters' => $registeredVoters,
        'voterTurnout' => $voterTurnout,
        'leadingName' => $leadingName,
        'availableVotes' => $availableVotes
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
exit;
?>