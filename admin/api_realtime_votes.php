<?php

/**
 * api_realtime_votes.php
 * Place this file in your admin/ directory (same level as index.php).
 * Endpoint: admin/api_realtime_votes.php?period_ids=1,2,3
 */

session_start();
include('includes/conn.php');

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Accept comma-separated voting_period_ids
$rawIds = $_GET['period_ids'] ?? '';
$periodIds = array_filter(array_map('intval', explode(',', $rawIds)));

if (empty($periodIds)) {
    echo json_encode(['error' => 'No period IDs provided']);
    exit();
}

$results = [];

foreach ($periodIds as $votingPeriodId) {

    // ── 1. Get election_id for this voting period ──────────────────────────
    $stmt = $pdo->prepare("
        SELECT vp.election_id, vp.status
        FROM voting_periods vp
        WHERE vp.id = ?
        LIMIT 1
    ");
    $stmt->execute([$votingPeriodId]);
    $vpRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vpRow) continue;

    $electionId      = $vpRow['election_id'];
    $votingPeriodStatus = $vpRow['status'];

    // ── 2. Total votes cast (unique students) ─────────────────────────────
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT student_id)
        FROM votes
        WHERE voting_period_id = ?
    ");
    $stmt->execute([$votingPeriodId]);
    $totalVotesCast = (int) $stmt->fetchColumn();

    // ── 3. Registered voters via precincts ───────────────────────────────
    $stmt = $pdo->prepare("SELECT precinct_id FROM precinct_elections WHERE election_name = ?");
    $stmt->execute([$electionId]);
    $assignedPrecincts = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $registeredVoters = 0;
    if (!empty($assignedPrecincts)) {
        $inQuery = implode(',', array_fill(0, count($assignedPrecincts), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM precinct_voters WHERE precinct IN ($inQuery)");
        $stmt->execute($assignedPrecincts);
        $registeredVoters = (int) $stmt->fetchColumn();
    }

    $voterTurnout  = round(($totalVotesCast / max($registeredVoters, 1)) * 100, 1);
    $availableVotes = $registeredVoters - $totalVotesCast;

    // ── 4. Leading party ─────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT cr_party.value AS party_name, COUNT(v.id) AS vote_count
        FROM candidates c
        LEFT JOIN candidate_responses cr_party ON c.id = cr_party.candidate_id
        JOIN form_fields ff_party
            ON cr_party.field_id = ff_party.id AND ff_party.field_name = 'party'
        LEFT JOIN votes v
            ON c.id = v.candidate_id AND v.voting_period_id = ?
        JOIN registration_forms rf ON c.form_id = rf.id
        WHERE c.status = 'accepted' AND rf.election_name = ?
        GROUP BY cr_party.value
        ORDER BY vote_count DESC
        LIMIT 1
    ");
    $stmt->execute([$votingPeriodId, $electionId]);
    $leadingParty = $stmt->fetch(PDO::FETCH_ASSOC);
    $leadingPartyDisplay = $leadingParty['party_name'] ?? 'N/A';

    // ── 5. Per-position vote data ─────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT DISTINCT name, MIN(level) AS level
        FROM positions
        GROUP BY name
        ORDER BY level, name
    ");
    $stmt->execute();
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $voteData = [];
    foreach ($positions as $pos) {
        $stmt = $pdo->prepare("
            SELECT
                c.id,
                cr_name.value  AS name,
                cr_party.value AS party_name,
                vtrs.college,
                vtrs.department,
                COUNT(v.id)    AS vote_count
            FROM candidates c
            JOIN candidate_responses cr_name
                ON c.id = cr_name.candidate_id
            JOIN form_fields ff_name
                ON cr_name.field_id = ff_name.id AND ff_name.field_name = 'full_name'
            LEFT JOIN candidate_responses cr_party
                ON c.id = cr_party.candidate_id
            JOIN form_fields ff_party
                ON cr_party.field_id = ff_party.id AND ff_party.field_name = 'party'
            JOIN candidate_responses cr_pos
                ON c.id = cr_pos.candidate_id
            JOIN form_fields ff_pos
                ON cr_pos.field_id = ff_pos.id
                AND ff_pos.field_name = 'position'
                AND cr_pos.value = ?
            LEFT JOIN candidate_responses cr_sid
                ON c.id = cr_sid.candidate_id
            JOIN form_fields ff_sid
                ON cr_sid.field_id = ff_sid.id AND ff_sid.field_name = 'student_id'
            LEFT JOIN voters vtrs
                ON vtrs.student_id = cr_sid.value
            LEFT JOIN votes v
                ON c.id = v.candidate_id AND v.voting_period_id = ?
            JOIN registration_forms rf ON c.form_id = rf.id
            WHERE c.status = 'accepted' AND rf.election_name = ?
            GROUP BY c.id, cr_name.value, cr_party.value, vtrs.college, vtrs.department
        ");
        $stmt->execute([$pos['name'], $votingPeriodId, $electionId]);

        $voteData[$pos['name']] = [
            'level'      => $pos['level'],
            'candidates' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    // ── 6. Bundle result ─────────────────────────────────────────────────
    $results[$votingPeriodId] = [
        'period_id'        => $votingPeriodId,
        'status'           => $votingPeriodStatus,
        'total_votes_cast' => $totalVotesCast,
        'registered_voters' => $registeredVoters,
        'voter_turnout'    => $voterTurnout,
        'leading_party'    => $leadingPartyDisplay,
        'available_votes'  => $availableVotes,
        'vote_data'        => $voteData,
    ];
}

echo json_encode($results);
