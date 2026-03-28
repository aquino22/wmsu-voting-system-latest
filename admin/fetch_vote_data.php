<?php
session_start();
include('includes/conn.php');

header('Content-Type: application/json');

try {
    // Get ongoing voting period
    $stmt = $pdo->prepare("SELECT id, name FROM voting_periods WHERE status = 'Ongoing' LIMIT 1");
    $stmt->execute();
    $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$votingPeriod) {
        throw new Exception("No ongoing voting period found.");
    }
    $votingPeriodId = $votingPeriod['id'];
    $votingPeriodName = $votingPeriod['name'];

    // Get active registration form ID
    $stmt = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$votingPeriodName]);
    $formId = $stmt->fetchColumn();
    if (!$formId) {
        throw new Exception("No active registration form found for the ongoing voting period.");
    }

    // Get accepted candidates
    $stmt = $pdo->prepare("SELECT id FROM candidates WHERE form_id = ? AND status = 'accepted'");
    $stmt->execute([$formId]);
    $candidateIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($candidateIds)) {
        throw new Exception("No accepted candidates found.");
    }

    // Fetch candidate data with position levels and vote counts
    $voteData = [];
    foreach ($candidateIds as $candidateId) {
        // Get candidate details
        $stmt = $pdo->prepare("
            SELECT cr.field_id, cr.value, ff.field_name 
            FROM candidate_responses cr 
            JOIN form_fields ff ON cr.field_id = ff.id 
            WHERE cr.candidate_id = ?
        ");
        $stmt->execute([$candidateId]);
        $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $candidateData = [
            'id' => $candidateId,
            'name' => '',
            'party_name' => '',
            'position' => '',
            'level' => '',
            'vote_count' => 0
        ];

        foreach ($responses as $response) {
            if ($response['field_name'] === 'full_name') {
                $candidateData['name'] = $response['value'];
            } elseif ($response['field_name'] === 'party') {
                $candidateData['party_name'] = $response['value'];
            } elseif ($response['field_name'] === 'position') {
                $candidateData['position'] = $response['value'];
                $stmt = $pdo->prepare("SELECT level FROM positions WHERE name = ? LIMIT 1");
                $stmt->execute([$response['value']]);
                $positionLevel = $stmt->fetchColumn();
                $candidateData['level'] = $positionLevel === 'Central' ? 'Central' : 'Local';
            }
        }

        // Get vote count for this candidate
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE candidate_id = ? AND voting_period_id = ?");
        $stmt->execute([$candidateId, $votingPeriodId]);
        $candidateData['vote_count'] = (int)$stmt->fetchColumn();

        $positionName = $candidateData['position'] . ' (' . $candidateData['level'] . ')';
        if (!isset($voteData[$positionName])) {
            $voteData[$positionName] = [
                'level' => $candidateData['level'],
                'candidates' => []
            ];
        }
        $voteData[$positionName]['candidates'][] = $candidateData;
    }

    // Sort candidates by vote count (descending)
    foreach ($voteData as &$position) {
        usort($position['candidates'], function($a, $b) {
            return $b['vote_count'] - $a['vote_count'];
        });
    }

    echo json_encode($voteData);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
exit;
?>