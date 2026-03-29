<?php
session_start();
require 'includes/conn.php';

$voting_period_id = $_POST['voting_period_id'] ?? null;
$college_filter = $_POST['college'] ?? '';
$department_filter = $_POST['department'] ?? '';
$sort_order = $_POST['sort_order'] ?? 'highest';

header('Content-Type: application/json');

if (!$voting_period_id) {
    echo json_encode(['error' => 'No voting period specified.']);
    exit();
}

try {
    // Fetch voting period and election
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
        echo json_encode(['error' => 'No voting period found for this ID.']);
        exit();
    }

    $election_name = $row['election_name'] ?? $row['voting_period_name'];
    $election_status = $row['status'] ?? 'Unknown';

    // Fetch registration form
    $stmt = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ? LIMIT 1");
    $stmt->execute([$election_name]);
    $formId = $stmt->fetchColumn();

    if (!$formId) {
        echo json_encode(['error' => 'No active registration form found.']);
        exit();
    }

    // Fetch parties
    $stmt = $pdo->prepare("SELECT DISTINCT name FROM parties WHERE election_name = ?");
    $stmt->execute([$election_name]);
    $parties = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($parties)) {
        echo json_encode(['error' => 'No parties found for this election.']);
        exit();
    }

    // Fetch positions
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
        echo json_encode(['error' => 'No positions found for candidates in these parties.']);
        exit();
    }

    $central_positions = array_column(array_filter($positions, fn($pos) => $pos['level'] === 'Central'), 'name');
    $local_positions = array_column(array_filter($positions, fn($pos) => $pos['level'] !== 'Central'), 'name');

    // Fetch colleges and departments
    $colleges = array_unique(array_column($pdo->query("SELECT college FROM voters WHERE college IS NOT NULL")->fetchAll(), 'college'));
    $all_departments = $pdo->query("SELECT DISTINCT department FROM voters WHERE department IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

    $dept_to_college = [];
    $stmt = $pdo->prepare("SELECT DISTINCT department, college FROM voters WHERE department IS NOT NULL AND college IS NOT NULL");
    $stmt->execute();
    $dept_college_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($dept_college_data as $row) {
        $dept_to_college[$row['department']] = $row['college'];
    }

    // Fetch all accepted candidates
    $stmt = $pdo->prepare("
        SELECT 
            c.id, 
            cr_name.value AS name, 
            cr_party.value AS party, 
            cr_pos.value AS position,
            COALESCE(COUNT(v.id), 0) AS vote_count,
            p.level
        FROM candidates c
        JOIN candidate_responses cr_name ON c.id = cr_name.candidate_id AND cr_name.field_id = (SELECT id FROM form_fields WHERE field_name = 'full_name' LIMIT 1)
        JOIN candidate_responses cr_party ON c.id = cr_party.candidate_id AND cr_party.field_id = (SELECT id FROM form_fields WHERE field_name = 'party' LIMIT 1)
        JOIN candidate_responses cr_pos ON c.id = cr_pos.candidate_id AND cr_pos.field_id = (SELECT id FROM form_fields WHERE field_name = 'position' LIMIT 1)
        JOIN positions p ON cr_pos.value = p.name
        LEFT JOIN votes v ON c.id = v.candidate_id AND v.voting_period_id = ?
        WHERE c.form_id = ? AND c.status = 'accepted'
        GROUP BY c.id, cr_name.value, cr_party.value, cr_pos.value, p.level
    ");
    $stmt->execute([$voting_period_id, $formId]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($candidates)) {
        echo json_encode(['error' => 'No accepted candidates found.']);
        exit();
    }

    // Initialize response data
    $response = [
        'election_name' => $election_name,
        'election_status' => $election_status,
        'voting_period_id' => $voting_period_id,
        'colleges' => $colleges,
        'departments' => $all_departments,
        'central_positions' => $central_positions,
        'local_positions' => $local_positions,
        'candidates' => [],
        'leaderboard' => ['Central' => [], 'Local' => [], 'External' => []],
        'highest_votes' => ['Central' => [], 'Local' => [], 'External' => []],
        'total_votes_by_position' => ['Central' => [], 'Local' => [], 'External' => []]
    ];

    $candidatesByLevel = [
        'Central' => [],
        'Local' => ['internal' => [], 'external' => []],
        'External' => []
    ];

    foreach ($candidates as $candidate) {
        $candidateId = $candidate['id'];
        $level = $candidate['level'] === 'Central' ? 'Central' : 'Local';

        // Fetch votes with precinct type filtering
        $sql = "
            SELECT 
                COALESCE(COUNT(DISTINCT v.id), 0) AS vote_count, 
                p.type AS precinct_type, 
                vtr.college, 
                vtr.department
            FROM candidates c
            LEFT JOIN votes v ON c.id = v.candidate_id AND v.voting_period_id = ?
            LEFT JOIN precinct_voters pv ON v.student_id = pv.student_id
            LEFT JOIN precincts p ON pv.precinct = p.name
            LEFT JOIN voters vtr ON pv.student_id = vtr.student_id
            WHERE c.id = ?
        ";
        $params = [$voting_period_id, $candidateId];
        if ($college_filter) {
            $sql .= " AND (vtr.college = ? OR vtr.college IS NULL)";
            $params[] = $college_filter;
        }
        if ($department_filter) {
            $sql .= " AND (vtr.department = ? OR vtr.department IS NULL)";
            $params[] = $department_filter;
        }
        $sql .= " GROUP BY p.type, vtr.college, vtr.department";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $voteData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $votesByCollege = array_fill_keys($colleges, 0);
        $votesByDepartment = array_fill_keys($all_departments, 0);
        $totalVotesInternal = 0;
        $totalVotesExternal = 0;
        $collegeVotesInternal = [];
        $collegeVotesExternal = [];

        foreach ($voteData as $row) {
            $precinctType = $row['precinct_type'] ? strtolower($row['precinct_type']) : null;
            if ($precinctType === 'internal' || $precinctType === 'central') {
                if ($level === 'Central') {
                    if ($row['college']) $votesByCollege[$row['college']] += $row['vote_count'];
                    $collegeVotesInternal[$row['college']] = ($collegeVotesInternal[$row['college']] ?? 0) + $row['vote_count'];
                } else {
                    if ($row['department']) $votesByDepartment[$row['department']] += $row['vote_count'];
                    $collegeVotesInternal[$row['college']] = ($collegeVotesInternal[$row['college']] ?? 0) + $row['vote_count'];
                }
                $totalVotesInternal += $row['vote_count'];
            } elseif ($precinctType === 'external') {
                if ($level === 'Central') {
                    if ($row['college']) $votesByCollege[$row['college']] += $row['vote_count'];
                    $collegeVotesExternal[$row['college']] = ($collegeVotesExternal[$row['college']] ?? 0) + $row['vote_count'];
                } else {
                    if ($row['department']) $votesByDepartment[$row['department']] += $row['vote_count'];
                    $collegeVotesExternal[$row['college']] = ($collegeVotesExternal[$row['college']] ?? 0) + $row['vote_count'];
                }
                $totalVotesExternal += $row['vote_count'];
            }
        }

        $primaryCollegeInternal = !empty($collegeVotesInternal) ? array_search(max($collegeVotesInternal), $collegeVotesInternal) : 'Unknown';
        $primaryCollegeExternal = !empty($collegeVotesExternal) ? array_search(max($collegeVotesExternal), $collegeVotesExternal) : 'Unknown';

        $candidateDataInternal = [
            'name' => $candidate['name'],
            'party' => $candidate['party'],
            'votes' => ($level === 'Central') ? $votesByCollege : $votesByDepartment,
            'total' => $totalVotesInternal,
            'position' => $candidate['position'],
            'college' => $primaryCollegeInternal
        ];
        $candidateDataExternal = [
            'name' => $candidate['name'],
            'party' => $candidate['party'],
            'votes' => ($level === 'Central') ? $votesByCollege : $votesByDepartment,
            'total' => $totalVotesExternal,
            'position' => $candidate['position'],
            'college' => $primaryCollegeExternal
        ];

        $key = $candidate['position'] . '|' . $candidate['party'];

        // Central (Internal/Central precincts)
        if ($level === 'Central') {
            if (!isset($response['highest_votes']['Central'][$key]) || $totalVotesInternal > $response['highest_votes']['Central'][$key]['total']) {
                $response['highest_votes']['Central'][$key] = $candidateDataInternal;
            }
            $candidatesByLevel['Central'][$candidate['position']][$candidate['name']] = $candidateDataInternal;
            if ($totalVotesInternal > 0) {
                $response['leaderboard']['Central'][] = $candidateDataInternal;
            }
            $response['total_votes_by_position']['Central'][$candidate['position']] = ($response['total_votes_by_position']['Central'][$candidate['position']] ?? 0) + $totalVotesInternal;
        }

        // External (External precincts, Central level)
        if ($level === 'Central') {
            if (!isset($response['highest_votes']['External'][$key]) || $totalVotesExternal > $response['highest_votes']['External'][$key]['total']) {
                $response['highest_votes']['External'][$key] = $candidateDataExternal;
            }
            $candidatesByLevel['External'][$candidate['position']][$candidate['name']] = $candidateDataExternal;
            if ($totalVotesExternal > 0) {
                $response['leaderboard']['External'][] = $candidateDataExternal;
            }
            $response['total_votes_by_position']['External'][$candidate['position']] = ($response['total_votes_by_position']['External'][$candidate['position']] ?? 0) + $totalVotesExternal;
        }

        // Local (split by precinct type)
        if ($level === 'Local') {
            if (!isset($response['highest_votes']['Local'][$key]) || $totalVotesInternal > $response['highest_votes']['Local'][$key]['total']) {
                $response['highest_votes']['Local'][$key] = $candidateDataInternal;
            }
            $candidatesByLevel['Local']['internal'][$candidate['position']][$candidate['name']] = $candidateDataInternal;
            if ($totalVotesInternal > 0) {
                $response['leaderboard']['Local'][] = $candidateDataInternal;
            }
            $candidatesByLevel['Local']['external'][$candidate['position']][$candidate['name']] = $candidateDataExternal;
            if ($totalVotesExternal > 0) {
                $response['leaderboard']['Local'][] = $candidateDataExternal;
            }
            $response['total_votes_by_position']['Local'][$candidate['position']] = ($response['total_votes_by_position']['Local'][$candidate['position']] ?? 0) + ($totalVotesInternal + $totalVotesExternal);
        }

        $response['candidates'][] = [
            'id' => $candidateId,
            'name' => $candidate['name'],
            'party' => $candidate['party'],
            'position' => $candidate['position'],
            'level' => $level,
            'internal' => $candidateDataInternal,
            'external' => $candidateDataExternal
        ];
    }

    // Sort candidates
    foreach (['Central', 'External'] as $type) {
        foreach ($candidatesByLevel[$type] as $position => &$candidates) {
            uasort($candidates, fn($a, $b) => $sort_order === 'highest' ? $b['total'] - $a['total'] : $a['total'] - $b['total']);
        }
    }
    foreach (['internal', 'external'] as $type) {
        foreach ($candidatesByLevel['Local'][$type] as $position => &$candidates) {
            uasort($candidates, fn($a, $b) => $sort_order === 'highest' ? $b['total'] - $a['total'] : $a['total'] - $b['total']);
        }
    }

    usort($response['leaderboard']['Central'], fn($a, $b) => $b['total'] - $a['total']);
    usort($response['leaderboard']['Local'], fn($a, $b) => $b['total'] - $a['total']);
    usort($response['leaderboard']['External'], fn($a, $b) => $b['total'] - $a['total']);

    // Add candidates by level to response
    $response['candidatesByLevel'] = $candidatesByLevel;

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error: ' . htmlspecialchars($e->getMessage())]);
}
