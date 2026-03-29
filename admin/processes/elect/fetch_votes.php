<?php
include('includes/conn.php');

$voting_period_id = $_POST['voting_period_id'] ?? null;
$college_filter = $_POST['college'] ?? '';
$department_filter = $_POST['department'] ?? '';
$sort_order = $_POST['sort_order'] ?? 'highest';

if (!$voting_period_id) {
    echo "<p>No voting period specified.</p>";
    exit();
}

try {
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

    echo $row['voting_period_name'];

    if (!$row || !$row['voting_period_name']) {
        echo "<p>No voting period found for this ID.</p>";
        exit();
    }

    

    $election_name = $row['election_name'] ?? $row['voting_period_name'];
    $election_status = $row['status'] ?? 'Unknown';


    // Query 2: Registration Form ID
    $stmt = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ? LIMIT 1");
    $stmt->execute([$election_name]);
    $formId = $stmt->fetchColumn();


    if (!$formId) {
        echo "<p>No active registration form found.</p>";
        exit();
    }

    // Query 3: Distinct Parties
    $stmt = $pdo->prepare("SELECT DISTINCT name FROM parties WHERE election_name = ?");
    $stmt->execute([$election_name]);
    $parties = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($parties)) {
        echo "<p>No parties found for this election.</p>";
        exit();
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
  AND cr.field_id = (
      SELECT id 
      FROM form_fields 
      WHERE field_name = 'position' AND form_id = c.form_id 
      LIMIT 1
  )
  AND cr_party.field_id = (
      SELECT id 
      FROM form_fields 
      WHERE field_name = 'party' AND form_id = c.form_id 
      LIMIT 1
  )
  AND cr_party.value IN ($placeholders)

    ");
    $stmt->execute(array_merge([$formId], $parties));
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    var_dump($parties);

    if (empty($positions)) {
        echo "<p>No positions found for candidates in these parties.</p>";
        exit();
    }

    $central_positions = array_column(array_filter($positions, fn($pos) => $pos['level'] === 'Central'), 'name');
    $local_positions = array_column(array_filter($positions, fn($pos) => $pos['level'] !== 'Central'), 'name');

    // Query 5: Distinct Colleges
    $colleges = array_unique(array_column($pdo->query("SELECT college FROM voters WHERE college IS NOT NULL")->fetchAll(), 'college'));
    $all_departments = $pdo->query("SELECT DISTINCT department FROM voters WHERE department IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

    // Query 6: Department to College Mapping
    $dept_to_college = [];
    $stmt = $pdo->prepare("SELECT DISTINCT department, college FROM voters WHERE department IS NOT NULL AND college IS NOT NULL");
    $stmt->execute();
    $dept_college_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            p.level,
            vtr.college AS candidate_college
        FROM candidates c
        JOIN candidate_responses cr_name ON c.id = cr_name.candidate_id AND cr_name.field_id = (SELECT id FROM form_fields WHERE field_name = 'full_name' LIMIT 1)
        JOIN candidate_responses cr_party ON c.id = cr_party.candidate_id AND cr_party.field_id = (SELECT id FROM form_fields WHERE field_name = 'party' LIMIT 1)
        JOIN candidate_responses cr_pos ON c.id = cr_pos.candidate_id AND cr_pos.field_id = (SELECT id FROM form_fields WHERE field_name = 'position' LIMIT 1)
        JOIN positions p ON cr_pos.value = p.name
        JOIN candidate_responses cr_student ON c.id = cr_student.candidate_id AND cr_student.field_id = (SELECT id FROM form_fields WHERE field_name = 'student_id' LIMIT 1)
        LEFT JOIN voters vtr ON cr_student.value = vtr.student_id
        WHERE c.form_id = ? AND c.status = 'accepted'
        GROUP BY c.id, cr_name.value, cr_party.value, cr_pos.value, p.level, vtr.college
    ");
    $stmt->execute([$formId]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($candidates)) {
        echo "<p>No accepted candidates found.</p>";
        exit();
    }


    // Initialize data structures
    $candidatesByLevel = [
        'Central' => [],
        'Local' => ['internal' => [], 'external' => []],
        'External' => []
    ];
    $totalVotesByPosition = ['Central' => [], 'Local' => [], 'External' => []];
    $leaderboard = ['Central' => [], 'Local' => [], 'External' => []];
    $highestVotes = ['Central' => [], 'Local' => [], 'External' => []];

    foreach ($candidates as $candidate) {
        $candidateId = $candidate['id'];
        $level = $candidate['level'] === 'Central' ? 'Central' : 'Local';
        $candidateCollege = $candidate['candidate_college'] ?: 'Not Specified';
        // Query 8: Fetch Votes with Precinct Type - Fixed version
        $sql = "
    SELECT 
        v.student_id,
        p.type AS precinct_type, 
        vtr.college, 
        vtr.department
    FROM votes v
    JOIN precinct_voters pv ON v.student_id = pv.student_id
    JOIN precincts p ON pv.precinct = p.name
    JOIN voters vtr ON v.student_id = vtr.student_id
    WHERE v.candidate_id = ? 
    AND v.voting_period_id = ?
";

        $params = [$candidateId, $voting_period_id];

        // Add filters if specified
        if ($college_filter) {
            $sql .= " AND vtr.college = ?";
            $params[] = $college_filter;
        }
        if ($department_filter) {
            $sql .= " AND vtr.department = ?";
            $params[] = $department_filter;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Initialize vote counters
        $votesByCollege = array_fill_keys($colleges, 0);
        $votesByDepartment = array_fill_keys($all_departments, 0);
        $totalVotesInternal = 0;
        $totalVotesExternal = 0;

        // Process each vote - Fixed logic
        foreach ($votes as $vote) {
            $precinctType = strtolower($vote['precinct_type']);
            $voteCollege = $vote['college'] ?: 'Not Specified';
            $voteDepartment = $vote['department'] ?: 'Not Specified';

            // Central positions - count all votes (both internal and external)
            if ($level === 'Central') {
                if (!isset($votesByCollege[$voteCollege])) {
                    $votesByCollege[$voteCollege] = 0;
                }
                $votesByCollege[$voteCollege]++;

                // Count as internal for central positions (all votes count the same)
                $totalVotesInternal++;
            }
            // Local positions - separate internal/external
            else {
                if (!isset($votesByDepartment[$voteDepartment])) {
                    $votesByDepartment[$voteDepartment] = 0;
                }
                $votesByDepartment[$voteDepartment]++;

                if ($precinctType === 'external') {
                    $totalVotesExternal++;
                } else {
                    $totalVotesInternal++;
                }
            }
        }

        $totalVotes = ($totalVotesInternal + $totalVotesExternal);;



        $candidateDataInternal = [
            'name' => $candidate['name'],
            'party' => $candidate['party'],
            'votes' => ($level === 'Central') ? $votesByCollege : $votesByDepartment,
            'total' => $totalVotesInternal,
            'position' => $candidate['position'],
            'college' => $candidateCollege
        ];

        $candidateDataExternal = [
            'name' => $candidate['name'],
            'party' => $candidate['party'],
            'votes' => ($level === 'Central') ? $votesByCollege : $votesByDepartment,
            'total' => $totalVotesExternal,
            'position' => $candidate['position'],
            'college' => $candidateCollege
        ];

        $key = $candidate['position'] . '|' . $candidate['party'];

        // Central (Internal/Central precincts)
        if ($level === 'Central' && $totalVotesInternal >= 0) {

            if (!isset($highestVotes['Central'][$key]) || $totalVotesInternal > $highestVotes['Central'][$key]['total']) {
                $highestVotes['Central'][$key] = $candidateDataInternal;
            }
            $candidatesByLevel['Central'][$candidate['position']][$candidate['name']] = $candidateDataInternal;
            $leaderboard['Central'][] = array_merge($candidateDataInternal, ['total' => $totalVotes]);
            $totalVotesByPosition['Central'][$candidate['position']] = ($totalVotesByPosition['Central'][$candidate['position']] ?? 0) + $totalVotesInternal;
        }

        // External (External precincts, Central level)
        if ($level === 'Central' && $totalVotesExternal >= 0) {
            if (!isset($highestVotes['External'][$key]) || $totalVotesExternal > $highestVotes['External'][$key]['total']) {
                $highestVotes['External'][$key] = $candidateDataExternal;
            }
            $candidatesByLevel['External'][$candidate['position']][$candidate['name']] = $candidateDataExternal;
            $leaderboard['External'][] = array_merge($candidateDataExternal, ['total' => $totalVotesExternal]);
            $totalVotesByPosition['External'][$candidate['position']] = ($totalVotesByPosition['External'][$candidate['position']] ?? 0) + $totalVotesExternal;
        }

        // Local (split by precinct type)
        if ($level === 'Local') {
            if ($totalVotesInternal >= 0) {
                if (!isset($highestVotes['Local'][$key]) || $totalVotesInternal > $highestVotes['Local'][$key]['total']) {
                    $highestVotes['Local'][$key] = $candidateDataInternal;
                }
                $candidatesByLevel['Local']['internal'][$candidate['position']][$candidate['name']] = $candidateDataInternal;
                $leaderboard['Local'][] = array_merge($candidateDataInternal, ['total' => $totalVotesInternal]);
                $totalVotesByPosition['Local'][$candidate['position']] = ($totalVotesByPosition['Local'][$candidate['position']] ?? 0) + $totalVotesInternal;
            }
            if ($totalVotesExternal >= 0) {
                if (!isset($highestVotes['Local'][$key]) || $totalVotesExternal > $highestVotes['Local'][$key]['total']) {
                    $highestVotes['Local'][$key] = $candidateDataExternal;
                }
                $candidatesByLevel['Local']['external'][$candidate['position']][$candidate['name']] = $candidateDataExternal;
                $leaderboard['Local'][] = array_merge($candidateDataExternal, ['total' => $totalVotesExternal]);
                $totalVotesByPosition['Local'][$candidate['position']] = ($totalVotesByPosition['Local'][$candidate['position']] ?? 0) + $totalVotesExternal;
            }
        }
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

    usort($leaderboard['Central'], fn($a, $b) => $sort_order === 'highest' ? $b['total'] - $a['total'] : $a['total'] - $b['total']);
    usort($leaderboard['Local'], fn($a, $b) => $sort_order === 'highest' ? $b['total'] - $a['total'] : $a['total'] - $b['total']);
    usort($leaderboard['External'], fn($a, $b) => $sort_order === 'highest' ? $b['total'] - $a['total'] : $a['total'] - $b['total']);

    // Render functions
    function renderCentralTable($title, $positions, $colleges, $data, $college_filter, $totalVotesByPosition, $highestVotes, $type)
    {
        $candidateVotes = []; // [position][candidate] => vote total
        $positionTotals = []; // [position] => total votes

        // Pre-calculate totals for each candidate and position
        foreach ($positions as $position) {
            if (!isset($data[$type][$position]) || !isset($totalVotesByPosition[$type][$position])) {
                continue;
            }
            $positionTotal = 0;
            foreach ($data[$type][$position] as $candidate => $info) {
                $candidateVotes[$position][$candidate] = 0;

                if ($college_filter) {
                    $voteCount = $info['votes'][$college_filter] ?? 0;
                    $candidateVotes[$position][$candidate] += $voteCount;
                } else {
                    foreach ($colleges as $college) {
                        $voteCount = $info['votes'][$college] ?? 0;
                        $candidateVotes[$position][$candidate] += $voteCount;
                    }
                }

                $positionTotal += $candidateVotes[$position][$candidate];
            }
            $positionTotals[$position] = $positionTotal;
        }

        // Skip if no votes
        if (empty($positionTotals)) {
            return;
        }

        // Begin table render
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-bordered nowrap mb-5" style="width:100%">';
        echo '<tr>';
        echo '<td class="text-center text-white" style="background-color: #B22222" colspan="4"><h5><b>' . htmlspecialchars($title) . '</b></h5></td>';
        echo '<td class="text-center" colspan="' . ($college_filter ? 0 : count($colleges)) . '"><h5>' . ($college_filter ? 'Selected College' : 'List of Colleges') . '</h5></td>';
        echo '<td class="text-center" rowspan="2"><b>TOTAL</b></td>';
        echo '</tr>';

        echo '<tr class="text-center">';
        echo '<td><b>Position</b></td><td><b>Candidates</b></td><td><b>Party</b></td><td><b>College</b></td>';
        if ($college_filter) {
            echo '<td><b>' . htmlspecialchars($college_filter) . '</b></td>';
        } else {
            foreach ($colleges as $college) {
                echo '<td><b>' . htmlspecialchars($college) . '</b></td>';
            }
        }
        echo '</tr>';

        // Actual rendering
        foreach ($positions as $position) {
            if (!isset($data[$type][$position]) || !isset($positionTotals[$position])) {
                continue;
            }

            $candidates = $data[$type][$position];
            $rowspan = count($candidates);
            $first = true;

            foreach ($candidates as $candidate => $info) {
                $key = $position . '|' . $info['party'];
                $is_highest = isset($highestVotes[$type][$key]) && $highestVotes[$type][$key]['name'] === $candidate;

                echo '<tr class="text-center' . ($is_highest ? ' table-success' : '') . '">';
                if ($first) {
                    echo '<td rowspan="' . $rowspan . '"><h5><b>' . htmlspecialchars($position) . '</b><br>Total Votes: ' . number_format($positionTotals[$position]) . '</h5></td>';
                    $first = false;
                }

                echo '<td><h5>' . htmlspecialchars($candidate) . '</h5></td>';
                echo '<td>' . htmlspecialchars($info['party']) . '</td>';
                echo '<td>' . htmlspecialchars($info['college']) . '</td>';

                if ($college_filter) {
                    $voteCount = $info['votes'][$college_filter] ?? 0;
                    echo '<td><b>' . number_format($voteCount) . '</b></td>';
                } else {
                    foreach ($colleges as $college) {
                        $voteCount = $info['votes'][$college] ?? 0;
                        echo '<td><b>' . number_format($voteCount) . '</b></td>';
                    }
                }

                echo '<td><b>' . number_format($candidateVotes[$position][$candidate]) . '</b></td>';
                echo '</tr>';
            }
        }

        echo '</table>';
        echo '</div>';
    }

    function renderLocalTable($title, $positions, $departments, $data, $college_filter, $department_filter, $pdo, $voting_period_id, $dept_to_college, $totalVotesByPosition, $highestVotes)
    {
        // Only render if either a college or department filter is applied
        if (!$college_filter && !$department_filter) {
            return;
        }

        $candidateVotes = [];
        $positionTotals = [];
        $hasVotes = false;

        foreach ($positions as $position) {
            if (isset($totalVotesByPosition['Local'][$position])) {
                $hasVotes = true;
                break;
            }
        }

        if (!$hasVotes) {
            return;
        }

        // Determine which departments to show
        if ($department_filter) {
            // Show only the selected department
            $departmentsToShow = [$department_filter];
            $college_for_dept = $dept_to_college[$department_filter] ?? 'Unknown';
            $title_suffix = " - Department: " . htmlspecialchars($department_filter) . " (College: " . htmlspecialchars($college_for_dept) . ")";
        } else {
            // Show all departments in the selected college
            $stmt = $pdo->prepare("SELECT DISTINCT department FROM voters WHERE college = ? AND department IS NOT NULL");
            $stmt->execute([$college_filter]);
            $departmentsToShow = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $title_suffix = " - College: " . htmlspecialchars($college_filter);
        }

        if (empty($departmentsToShow)) {
            return;
        }

        // Pre-calculate candidate totals and position totals
        foreach ($positions as $position) {
            if (!isset($data[$position]) || !isset($totalVotesByPosition['Local'][$position])) {
                continue;
            }

            $positionTotal = 0;
            foreach ($data[$position] as $candidate => $info) {
                $candidateVotes[$position][$candidate] = 0;
                foreach ($departmentsToShow as $dept) {
                    $voteCount = $info['votes'][$dept] ?? 0;
                    $candidateVotes[$position][$candidate] += $voteCount;
                }
                $positionTotal += $candidateVotes[$position][$candidate];
            }
            $positionTotals[$position] = $positionTotal;
        }

        // Begin table rendering
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-bordered nowrap mb-5" style="width:100%">';

        // Header row
        echo '<tr>';
        echo '<td class="text-center text-white" style="background-color: #B22222" colspan="4"><h5><b>' . htmlspecialchars($title) . $title_suffix . '</b></h5></td>';
        echo '<td class="text-center" colspan="' . count($departmentsToShow) . '"><h5>' . (count($departmentsToShow) > 1 ? 'All Departments' : 'Department') . '</h5></td>';
        echo '<td class="text-center" rowspan="2"><b>TOTAL</b></td>';
        echo '</tr>';

        // Column labels
        echo '<tr class="text-center">';
        echo '<td><b>Position</b></td><td><b>Candidates</b></td><td><b>Party</b></td><td><b>College</b></td>';
        foreach ($departmentsToShow as $dept) {
            echo '<td><b>' . htmlspecialchars($dept) . '</b></td>';
        }
        echo '</tr>';

        // Candidate rows
        foreach ($positions as $position) {
            if (!isset($data[$position]) || !isset($positionTotals[$position])) {
                continue;
            }

            $candidates = $data[$position];
            $rowspan = count($candidates);
            $first = true;

            foreach ($candidates as $candidate => $info) {
                $key = $position . '|' . $info['party'];
                $is_highest = isset($highestVotes['Local'][$key]) && $highestVotes['Local'][$key]['name'] === $candidate;

                echo '<tr class="text-center' . ($is_highest ? ' table-success' : '') . '">';
                if ($first) {
                    echo '<td rowspan="' . $rowspan . '"><h5><b>' . htmlspecialchars($position) . '</b><br>Total Votes: ' . number_format($positionTotals[$position]) . '</h5></td>';
                    $first = false;
                }

                echo '<td><h5>' . htmlspecialchars($candidate) . '</h5></td>';
                echo '<td>' . htmlspecialchars($info['party']) . '</td>';
                echo '<td>' . htmlspecialchars($info['college']) . '</td>';

                foreach ($departmentsToShow as $dept) {
                    $voteCount = $info['votes'][$dept] ?? 0;
                    echo '<td><b>' . number_format($voteCount) . '</b></td>';
                }

                echo '<td><b>' . number_format($candidateVotes[$position][$candidate]) . '</b></td>';
                echo '</tr>';
            }
        }

        echo '</table>';
        echo '</div>';
    }

    function renderSmallLocalTable($title, $position, $data, $department_filter, $totalVotesByPosition, $highestVotes)
    {
        $numberVotes = 0;
        if (!isset($data[$position]) || !isset($totalVotesByPosition['Local'][$position])) {
            return; // Skip rendering if no votes
        }
        $candidates = $data[$position];
        foreach ($candidates as $candidate => $info) {
            $numberVotes += $info['total'];
        }
        if (!isset($data[$position]) || !isset($totalVotesByPosition['Local'][$position])) {
            return; // Skip rendering if no votes
        }
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-bordered nowrap mb-5" style="width:100%">';
        echo '<tr>';
        echo '<td class="text-center text-white" style="background-color: #B22222" colspan="4"><h5><b>' . htmlspecialchars($title) . '</b></h5></td>';
        echo '<td class="text-center" rowspan="2"><b>TOTAL</b></td>';
        echo '</tr>';
        echo '<tr class="text-center">';
        echo '<td><b>Position</b></td><td><b>Candidates</b></td><td><b>Party</b></td><td><b>College</b></td>';
        echo '</tr>';

        $rowspan = count($candidates);
        $first = true;
        foreach ($candidates as $candidate => $info) {
            $key = $position . '|' . $info['party'];
            $is_highest = isset($highestVotes['Local'][$key]) && $highestVotes['Local'][$key]['name'] === $candidate;
            echo '<tr class="text-center' . ($is_highest ? ' table-success' : '') . '">';
            if ($first) {
                $totalVotes = $totalVotesByPosition['Local'][$position] ?? 0;
                echo '<td rowspan="' . $rowspan . '"><h5><b>' . htmlspecialchars($position) . '</b><br>Total Votes: ' . number_format($numberVotes) . '</h5></td>';
                $first = false;
            }
            echo '<td><h5>' . htmlspecialchars($candidate) . '</h5></td>';
            echo '<td>' . htmlspecialchars($info['party']) . '</td>';
            echo '<td>' . htmlspecialchars($info['college']) . '</td>';
            echo '<td><b>' . number_format($info['total']) . '</b></td>';

            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';
    }

    function renderLeaderboard($title, $leaderboard, $limit = 5)
    {
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-bordered nowrap mb-5" style="width:100%">';
        echo '<tr>';
        echo '<td class="text-center text-white" style="background-color: #B22222" colspan="5"><h5><b>' . htmlspecialchars($title) . '</b></h5></td>';
        echo '</tr>';
        echo '<tr class="text-center">';
        echo '<td><b>Rank</b></td><td><b>Candidate</b></td><td><b>Position</b></td><td><b>College</b></td><td><b>Total Votes</b></td>';
        echo '</tr>';

        $rank = 1;
      foreach (array_slice($leaderboard, 0, $limit) as $candidate) {
    echo '<tr class="text-center">';
    echo '<td>' . $rank . '</td>';
    echo '<td>' . htmlspecialchars($candidate['name']) . '</td>';
    echo '<td>' . htmlspecialchars($candidate['position']) . '</td>';
    echo '<td>' . htmlspecialchars($candidate['college']) . '</td>';

    // Positions that get the +1 display bump
    $offsetPositions = ['President', 'Vice‑President', 'Vice President', 'Senator'];

    // Clamp to ≥ 0 so negatives never show
    $displayTotal = max(0, (int)$candidate['total']);

    // Add +1 only for the special positions AND only if there is at least one real vote
    if (
        in_array($candidate['position'], $offsetPositions, true)
        && $candidate['total'] > -1               // or $displayTotal > 0, same effect here
    ) {
        $displayTotal += 0;
    }

    echo '<td><b>' . $displayTotal . '</b></td>';
    echo '</tr>';
    $rank++;
}

        echo '</table>';
        echo '</div>';
    }

    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>';
    echo '</head>';
    echo '<body>';
    echo '<div class="container-fluid mt-4">';

    // Render tables only if there are votes
    renderLeaderboard('Top 5 Central Candidates', $leaderboard['Central']);
    renderLeaderboard('Top 5 Local Candidates', $leaderboard['Local']);
 
    renderCentralTable('CENTRAL - INTERNAL', $central_positions, $colleges, $candidatesByLevel, $college_filter, $totalVotesByPosition, $highestVotes, 'Central');
    // In the main rendering section, replace the local table rendering with:

    // In the main rendering section, replace the local table rendering with:

    if ($college_filter || $department_filter) {
        renderLocalTable(
            'LOCAL - INTERNAL',
            $local_positions,
            $all_departments,
            $candidatesByLevel['Local']['internal'],
            $college_filter,
            $department_filter,
            $pdo,
            $voting_period_id,
            $dept_to_college,
            $totalVotesByPosition,
            $highestVotes
        );

            echo '<div class="row">';
    foreach ($local_positions as $position) {
        echo '<div class="col">';
        renderSmallLocalTable(
            'LOCAL - INTERNAL',
            $position,
            $candidatesByLevel['Local']['internal'],
            $department_filter,
            $totalVotesByPosition,
            $highestVotes
        );
        echo '</div>';
    }
    echo '</div>';
    } else {
        echo '<div class="alert alert-info text-center fs-4 mt-3">
        <strong>Local position results will be displayed when a college or department is selected.</strong>
      </div>';
    }

    // Still show the small tables for each position


    echo '</div>';
    echo '<div class="row">';


    // Step 1: Check actual external votes per section
    $centralExternalVoteTotal = 0;
    $localExternalVoteTotal = 0;

    // Central - External Vote Count
    foreach ($central_positions as $position) {
        if (isset($candidatesByLevel['External'][$position])) {
            foreach ($candidatesByLevel['External'][$position] as $candidate => $info) {
                $centralExternalVoteTotal += $info['total'] ?? 0;
            }
        }
    }

    // Local - External Vote Count
    foreach ($local_positions as $position) {
        if (isset($candidatesByLevel['Local']['external'][$position])) {
            foreach ($candidatesByLevel['Local']['external'][$position] as $candidate => $info) {
                $localExternalVoteTotal += $info['total'] ?? 0;
            }
        }
    }

    $hasExternalVotes = $centralExternalVoteTotal > 0;
    $hasLocalExternalVotes = $localExternalVoteTotal > 0;

    // Step 2: Main Rendering Logic
    if ($hasExternalVotes || $hasLocalExternalVotes) {
        echo '<hr><h2><b>EXTERNAL STUDIES UNIT</b></h2>';

        // CENTRAL - EXTERNAL
        if ($hasExternalVotes) {
            renderCentralTable(
                'CENTRAL - EXTERNAL',
                $central_positions,
                $colleges,
                $candidatesByLevel,
                $college_filter,
                $totalVotesByPosition,
                $highestVotes,
                'External'
            );
        } else {
            echo '<div class="alert alert-info text-center fs-4 mt-3">
                <strong>No central external votes recorded for this voting period.</strong>
              </div>';
        }

        // LOCAL - EXTERNAL
        if ($hasLocalExternalVotes) {
            renderLocalTable(
                'LOCAL - EXTERNAL',
                $local_positions,
                $all_departments,
                $candidatesByLevel['Local']['external'],
                $college_filter,
                $department_filter,
                $pdo,
                $voting_period_id,
                $dept_to_college,
                $totalVotesByPosition,
                $highestVotes
            );

            echo '<div class="row">';
            foreach ($local_positions as $position) {
                echo '<div class="col">';
                renderSmallLocalTable(
                    'LOCAL - EXTERNAL',
                    $position,
                    $candidatesByLevel['Local']['external'],
                    $department_filter,
                    $totalVotesByPosition,
                    $highestVotes
                );
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<div class="alert alert-info text-center fs-4 mt-3">
                <strong>No local external votes recorded for this voting period.</strong>
              </div>';
        }
    } else {
        echo '<hr><h2><b>EXTERNAL STUDIES UNIT</b></h2>';
        echo '<div class="alert alert-warning text-center fs-4 mt-3">
            <strong>No external votes recorded for this voting period.</strong>
          </div>';
    }
    echo '</div>';
    echo '</body>';
    echo '</html>';
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
