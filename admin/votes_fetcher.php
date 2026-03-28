<?php
ob_start(); // Start output buffering
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log('fetch_votes.php started');

require 'includes/conn.php'; // Include database connection

header('Content-Type: application/json');

try {
    // Get POST data
    $voting_period_id = filter_input(INPUT_POST, 'voting_period_id', FILTER_SANITIZE_STRING) ?? null;
    $college_filter = filter_input(INPUT_POST, 'college', FILTER_SANITIZE_STRING) ?? '';
    $department_filter = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING) ?? '';
    $sort_order = filter_input(INPUT_POST, 'sort_order', FILTER_SANITIZE_STRING) ?? 'highest';

    if (!$voting_period_id) {
        throw new Exception('Voting period ID is required');
    }

    // Initialize response data
    $data = [
        'centralCandidates' => [],
        'collegesVoted' => [],
        'centralInternal' => [],
        'localInternal' => [],
        'centralExternal' => [],
        'localExternal' => []
    ];

    // Query 1: Voting Period and Election Details
    $stmt = $pdo->prepare("
        SELECT vp.name AS voting_period_name, e.election_name, e.status 
        FROM voting_periods vp 
        LEFT JOIN elections e ON vp.name = e.election_name 
        WHERE vp.id = ? 
        LIMIT 1
    ");
    $stmt->execute([$voting_period_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !$row['voting_period_name']) {
        throw new Exception('No voting period found for this ID');
    }

    $election_name = $row['election_name'] ?? $row['voting_period_name'];

    // Query 2: Registration Form ID
    $stmt = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ? LIMIT 1");
    $stmt->execute([$election_name]);
    $formId = $stmt->fetchColumn();

    if (!$formId) {
        throw new Exception('No active registration form found');
    }

    // Query 3: Distinct Parties
    $stmt = $pdo->prepare("SELECT DISTINCT name FROM parties WHERE election_name = ?");
    $stmt->execute([$election_name]);
    $parties = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($parties)) {
        throw new Exception('No parties found for this election');
    }

    // Query 4: Positions and Levels
    $placeholders = implode(',', array_fill(0, count($parties), '?'));
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.name, p.level 
        FROM positions p
        JOIN candidate_responses cr ON cr.value = p.name
        JOIN candidates c ON c.id = cr.candidate_id
        JOIN candidate_responses cr_party ON cr_party.candidate_id = c.id
        WHERE c.form_id = ? 
        AND c.status = 'accepted'
        AND cr.field_id = (SELECT id FROM form_fields WHERE field_name = 'position' LIMIT 1)
        AND cr_party.field_id = (SELECT id FROM form_fields WHERE field_name = 'party' LIMIT 1)
        AND cr_party.value IN ($placeholders)
    ");
    $stmt->execute(array_merge([$formId], $parties));
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($positions)) {
        throw new Exception('No positions found for candidates in these parties');
    }

    $central_positions = array_column(array_filter($positions, fn($pos) => $pos['level'] === 'Central'), 'name');
    $local_positions = array_column(array_filter($positions, fn($pos) => $pos['level'] !== 'Central'), 'name');

    // Query 5: Distinct Colleges (Colleges Voted)
    $stmt = $pdo->prepare("SELECT DISTINCT college FROM voters WHERE college IS NOT NULL");
    $stmt->execute();
    $data['collegesVoted'] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'college');

    // Query 6: Department to College Mapping
    $stmt = $pdo->prepare("SELECT DISTINCT department, college FROM voters WHERE department IS NOT NULL AND college IS NOT NULL");
    $stmt->execute();
    $dept_college_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $dept_to_college = [];
    foreach ($dept_college_data as $row) {
        $dept_to_college[$row['department']] = $row['college'];
    }

    // Query 7: Fetch Candidates, Vote Counts, and Candidate's College
    $stmt = $pdo->prepare("
    SELECT 
        c.id, 
        cr_name.value AS name, 
        cr_party.value AS party, 
        cr_pos.value AS position,
        COUNT(v.id) AS vote_count,
        p.level,
        vtr.college AS candidate_college
    FROM candidates c
    JOIN candidate_responses cr_name ON c.id = cr_name.candidate_id AND cr_name.field_id = (SELECT id FROM form_fields WHERE field_name = 'full_name' LIMIT 1)
    JOIN candidate_responses cr_party ON c.id = cr_party.candidate_id AND cr_party.field_id = (SELECT id FROM form_fields WHERE field_name = 'party' LIMIT 1)
    JOIN candidate_responses cr_pos ON c.id = cr_pos.candidate_id AND cr_pos.field_id = (SELECT id FROM form_fields WHERE field_name = 'position' LIMIT 1)
    JOIN positions p ON cr_pos.value = p.name
    JOIN candidate_responses cr_student ON c.id = cr_student.candidate_id AND cr_student.field_id = (SELECT id FROM form_fields WHERE field_name = 'student_id' LIMIT 1)
    LEFT JOIN voters vtr ON cr_student.value = vtr.student_id
    LEFT JOIN votes v ON c.id = v.candidate_id AND v.voting_period_id = ?
    WHERE c.form_id = ? AND c.status = 'accepted'
    GROUP BY c.id, cr_name.value, cr_party.value, cr_pos.value, p.level, vtr.college
");
    $stmt->execute([$voting_period_id, $formId]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($candidates)) {
        throw new Exception('No accepted candidates found');
    }

    // Process Candidates
    $candidatesByLevel = [
        'Central' => [],
        'Local' => ['internal' => [], 'external' => []],
        'External' => []
    ];
    $highestVotes = ['Central' => [], 'Local' => [], 'External' => []];
    $leaderboard = ['Central' => [], 'Local' => [], 'External' => []];

    foreach ($candidates as $candidate) {
        $candidateId = $candidate['id'];
        $level = $candidate['level'] === 'Central' ? 'Central' : 'Local';

        // Query 8: Fetch Votes with Precinct Type
        $sql = "
SELECT 
    COUNT(v.id) AS vote_count,
    CASE
        WHEN v.precinct = 'admin-precinct' THEN 'admin'
        ELSE p.type
    END AS vote_source
FROM votes v
LEFT JOIN precincts p 
    ON v.precinct = p.name
LEFT JOIN voters vtr 
    ON v.student_id = vtr.student_id
WHERE v.candidate_id = ?
  AND v.voting_period_id = ?
";

        $params = [$candidateId, $voting_period_id];

        // Filters apply ONLY to non-admin votes
        if ($college_filter) {
            $sql .= " AND (v.precinct = 'admin-precinct' OR vtr.college = ?)";
            $params[] = $college_filter;
        }
        if ($department_filter) {
            $sql .= " AND (v.precinct = 'admin-precinct' OR vtr.department = ?)";
            $params[] = $department_filter;
        }

        $sql .= " GROUP BY vote_source";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $voteData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalVotesInternal = 0;
        $totalVotesExternal = 0;

        foreach ($voteData as $row) {
            $source = strtolower($row['vote_source'] ?? '');
            if ($source === 'admin') {
                // Admin votes are ALWAYS internal
                $totalVotesInternal += (int)$row['vote_count'];
            } elseif (in_array($source, ['internal', 'central', 'main campus'])) {
                $totalVotesInternal += (int)$row['vote_count'];
            } elseif (in_array($source, ['external', 'wmsu esu'])) {
                $totalVotesExternal += (int)$row['vote_count'];
            }
        }

        $candidateDataInternal = [
            'position' => $candidate['position'],
            'candidate' => $candidate['name'],
            'party' => $candidate['party'],
            'college' => $candidate['candidate_college'] ?: 'Not Specified', // Use candidate's college
            'department' => $department_filter ?: 'Not Specified',
            'total_votes' => $totalVotesInternal,
            'result' => isset($highestVotes[$level][$candidate['position'] . '|' . $candidate['party']]) ? 'Elected' : 'Not Elected'
        ];

        $candidateDataExternal = [
            'position' => $candidate['position'],
            'candidate' => $candidate['name'],
            'party' => $candidate['party'],
            'college' => $candidate['candidate_college'] ?: 'Not Specified', // Use candidate's college
            'department' => $department_filter ?: 'Not Specified',
            'total_votes' => $totalVotesExternal,
            'result' => isset($highestVotes[$level][$candidate['position'] . '|' . $candidate['party']]) ? 'Elected' : 'Not Elected'
        ];

        $key = $candidate['position'] . '|' . $candidate['party'];

        // Central - Internal
        if ($level === 'Central') {
            if (!isset($highestVotes['Central'][$key]) || $totalVotesInternal > $highestVotes['Central'][$key]['total_votes']) {
                $highestVotes['Central'][$key] = $candidateDataInternal;
                $candidateDataInternal['result'] = 'Elected';
            }
            $candidatesByLevel['Central'][] = $candidateDataInternal;
            $leaderboard['Central'][] = $candidateDataInternal;
        }

        // Central - External
        if ($level === 'Central') {
            if (!isset($highestVotes['External'][$key]) || $totalVotesExternal > $highestVotes['External'][$key]['total_votes']) {
                $highestVotes['External'][$key] = $candidateDataExternal;
                $candidateDataExternal['result'] = 'Elected';
            }
            $candidatesByLevel['External'][] = $candidateDataExternal;
            $leaderboard['External'][] = $candidateDataExternal;
        }

        // Local - Internal and External
        if ($level === 'Local') {
            if (!isset($highestVotes['Local'][$key]) || $totalVotesInternal > $highestVotes['Local'][$key]['total_votes']) {
                $highestVotes['Local'][$key] = $candidateDataInternal;
                $candidateDataInternal['result'] = 'Elected';
            }
            $candidatesByLevel['Local']['internal'][] = $candidateDataInternal;
            $leaderboard['Local'][] = $candidateDataInternal;

            if (!isset($highestVotes['Local'][$key]) || $totalVotesExternal > $highestVotes['Local'][$key]['total_votes']) {
                $highestVotes['Local'][$key] = $candidateDataExternal;
                $candidateDataExternal['result'] = 'Elected';
            }
            $candidatesByLevel['Local']['external'][] = $candidateDataExternal;
            $leaderboard['Local'][] = $candidateDataExternal;
        }
    }

    // Sort Leaderboard for Central Candidates
    usort($leaderboard['Central'], fn($a, $b) => $sort_order === 'highest' ? $b['total_votes'] - $a['total_votes'] : $a['total_votes'] - $b['total_votes']);
    usort($leaderboard['External'], fn($a, $b) => $sort_order === 'highest' ? $b['total_votes'] - $a['total_votes'] : $a['total_votes'] - $b['total_votes']);
    usort($leaderboard['Local'], fn($a, $b) => $sort_order === 'highest' ? $b['total_votes'] - $a['total_votes'] : $a['total_votes'] - $b['total_votes']);

    // Populate Central Candidates (Top 5 from Leaderboard)
    $data['centralCandidates'] = array_map(function ($candidate, $index) {
        return [
            'rank' => $index + 1,
            'candidate' => $candidate['candidate'],
            'position' => $candidate['position'],
            'college' => $candidate['college'], // Reflects candidate's college
            'total_votes' => $candidate['total_votes']
        ];
    }, array_slice($leaderboard['Central'], 0, 5), array_keys(array_slice($leaderboard['Central'], 0, 5)));

    // Populate Other Sections
    $data['centralInternal'] = $candidatesByLevel['Central'];
    $data['localInternal'] = $candidatesByLevel['Local']['internal'];
    $data['centralExternal'] = $candidatesByLevel['External'];
    $data['localExternal'] = $candidatesByLevel['Local']['external'];

    // Clear output buffer and send response
    ob_end_clean();
    $response = ['status' => 'success', 'data' => $data];
    if (!json_encode($response)) {
        error_log('JSON encoding failed: ' . json_last_error_msg());
        echo json_encode(['status' => 'error', 'message' => 'JSON encoding failed']);
        exit();
    }
    echo json_encode($response);
    exit();
} catch (Exception $e) {
    ob_end_clean();
    error_log('Error in fetch_votes.php: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit();
}
