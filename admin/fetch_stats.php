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
    $votingPeriodName = $votingPeriod['name'];

    // 1. Total Votes Cast (Unique Students)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM votes WHERE voting_period_id = ?");
    $stmt->execute([$votingPeriodId]);
    $totalVotesCast = $stmt->fetchColumn() ?: 0;

    // 2. Registered Voters (Based on precincts assigned to this election)
    // Get election name to link to precinct_elections
    $stmt = $pdo->prepare("SELECT election_name FROM elections WHERE id = (SELECT election_id FROM voting_periods WHERE id = ?)");
    $stmt->execute([$votingPeriodId]);
    $electionName = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(pv.id) 
        FROM precinct_voters pv
        JOIN precinct_elections pe ON pv.precinct = pe.precinct_name
        WHERE pe.election_name = ?
    ");
    $stmt->execute([$electionName]);
    $registeredVoters = $stmt->fetchColumn() ?: 0;
    if ($registeredVoters == 0) $registeredVoters = 1; // Prevent division by zero

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

    // 3. Available Votes (Voters in assigned precincts who haven't voted yet)
    $stmt = $pdo->prepare("
        SELECT COUNT(pv.id) 
        FROM precinct_voters pv
        JOIN precinct_elections pe ON pv.precinct = pe.precinct_name
        WHERE pe.election_name = ? AND pv.status != 'voted'
    ");
    $stmt->execute([$electionName]);
    $availableVotes = $stmt->fetchColumn() ?: 0;

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
