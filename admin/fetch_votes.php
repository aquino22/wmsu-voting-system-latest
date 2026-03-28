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
    // 1️⃣ Election Details
    $stmt = $pdo->prepare("
        SELECT e.id as election_id, e.election_name AS voting_period_name, e.status
        FROM voting_periods vp
        JOIN elections e ON vp.election_id = e.id
        WHERE vp.id = :voting_period_id
        LIMIT 1
    ");
    $stmt->execute(['voting_period_id' => $voting_period_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !$row['voting_period_name']) {
        echo "<p>No voting period found for this ID.</p>";
        exit();
    }

    $election_name = $row['voting_period_name'];
    $election_status = $row['status'] ?? 'Unknown';
    $election_id = $row['election_id'];

    // 2️⃣ Registration Form
    $stmt = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ? LIMIT 1");
    $stmt->execute([$election_id]);
    $formId = $stmt->fetchColumn();

    if (!$formId) {
        echo "<p>No active registration form found.</p>";
        exit();
    }

    // 3️⃣ Distinct Parties
    $stmt = $pdo->prepare("SELECT DISTINCT name FROM parties WHERE election_id = ?");
    $stmt->execute([$election_id]);
    $parties = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($parties)) {
        echo "<p>No parties found for this election.</p>";
        exit();
    }

    // 4️⃣ Positions and Levels
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
              SELECT id FROM form_fields WHERE field_name = 'position' AND form_id = c.form_id LIMIT 1
          )
          AND cr_party.field_id = (
              SELECT id FROM form_fields WHERE field_name = 'party' AND form_id = c.form_id LIMIT 1
          )
          AND cr_party.value IN ($placeholders)
    ");
    $stmt->execute(array_merge([$formId], $parties));
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($positions)) {
        echo "<p>No positions found for candidates in these parties.</p>";
        exit();
    }

    $central_positions = array_column(array_filter($positions, fn($pos) => $pos['level'] === 'Central'), 'name');
    $local_positions = array_column(array_filter($positions, fn($pos) => $pos['level'] !== 'Central'), 'name');

    // 5️⃣ Colleges and Departments
    $colleges = $pdo->query("SELECT DISTINCT college FROM voters WHERE college IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);


    $all_departments = $pdo->query("SELECT DISTINCT department FROM voters WHERE department IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

    // 6️⃣ Department → College map
    $dept_to_college = [];
    $stmt = $pdo->prepare("SELECT DISTINCT department, college FROM voters WHERE department IS NOT NULL AND college IS NOT NULL");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $dept_to_college[$row['department']] = $row['college'];
    }

    // 7️⃣ All accepted candidates
    $stmt = $pdo->prepare("
       SELECT 
    c.id, 
    cr_name.value AS name, 
    cr_party.value AS party, 
    cr_pos.value AS position,
    p.level,
    col.college_name AS candidate_college
FROM candidates c
JOIN candidate_responses cr_name 
    ON c.id = cr_name.candidate_id 
    AND cr_name.field_id = (
        SELECT id 
        FROM form_fields 
        WHERE field_name = 'full_name' AND form_id = c.form_id 
        LIMIT 1
    )
JOIN candidate_responses cr_party 
    ON c.id = cr_party.candidate_id 
    AND cr_party.field_id = (
        SELECT id 
        FROM form_fields 
        WHERE field_name = 'party' AND form_id = c.form_id 
        LIMIT 1
    )
JOIN candidate_responses cr_pos 
    ON c.id = cr_pos.candidate_id 
    AND cr_pos.field_id = (
        SELECT id 
        FROM form_fields 
        WHERE field_name = 'position' AND form_id = c.form_id 
        LIMIT 1
    )
JOIN positions p ON cr_pos.value = p.name
JOIN candidate_responses cr_student 
    ON c.id = cr_student.candidate_id 
    AND cr_student.field_id = (
        SELECT id 
        FROM form_fields 
        WHERE field_name = 'student_id' AND form_id = c.form_id 
        LIMIT 1
    )
LEFT JOIN voters vtr ON cr_student.value = vtr.student_id
LEFT JOIN colleges col ON vtr.college = col.college_id
WHERE c.form_id = ? AND c.status = 'accepted'
GROUP BY c.id, cr_name.value, cr_party.value, cr_pos.value, p.level, col.college_name

    ");
    $stmt->execute([$formId]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($candidates)) {
        echo "<p>No accepted candidates found.</p>";
        exit();
    }

    // Initialize structures
    $candidatesByLevel = [
        'Central' => [],
        'Local' => ['internal' => [], 'external' => []],
        'External' => []
    ];
    $totalVotesByPosition = ['Central' => [], 'Local' => [], 'External' => []];
    $leaderboard = ['Central' => [], 'Local' => [], 'External' => []];
    $highestVotes = ['Central' => [], 'Local' => [], 'External' => []];

    // 8️⃣ Process votes per candidate
    foreach ($candidates as $candidate) {
        $candidateId = $candidate['id'];
        $level = ($candidate['level'] === 'Central') ? 'Central' : 'Local';
        $candidateCollege = $candidate['candidate_college'] ?: 'Not Specified';

        // Query to get votes grouped by Precinct Type (WMSU ESU vs Others)
        $sql = "
        SELECT 
            p.type AS precinct_type,
            vtr.college,
            vtr.department,
            COUNT(v.id) as vote_count
        FROM votes v
        LEFT JOIN precincts p ON v.precinct = p.id
        LEFT JOIN voters vtr ON v.student_id = vtr.student_id
        WHERE v.candidate_id = ? AND v.voting_period_id = ?
    ";

        $params = [$candidateId, $voting_period_id];
        if ($college_filter) {
            $sql .= " AND vtr.college = ?";
            $params[] = $college_filter;
        }

        $sql .= " GROUP BY p.type, vtr.college, vtr.department";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $voteRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Initialize temporary counters for this specific candidate
        $internalVotes = 0;
        $externalVotes = 0;
        $votesByCollegeInternal = array_fill_keys($colleges, 0);
        $votesByCollegeExternal = array_fill_keys($colleges, 0);
        $votesByDeptInternal = array_fill_keys($all_departments, 0);
        $votesByDeptExternal = array_fill_keys($all_departments, 0);

        foreach ($voteRows as $vr) {


            $isESU = ((int)$vr['precinct_type'] === 10);
            $count = (int)$vr['vote_count'];




            if ($isESU) {
                $externalVotes += $count;
                if ($level === 'Central') {
                    $votesByCollegeExternal[$vr['college']] = ($votesByCollegeExternal[$vr['college']] ?? 0) + $count;
                } else {
                    $votesByDeptExternal[$vr['department']] = ($votesByDeptExternal[$vr['department']] ?? 0) + $count;
                }
            } else {
                $internalVotes += $count;
                if ($level === 'Central') {
                    $votesByCollegeInternal[$vr['college']] = ($votesByCollegeInternal[$vr['college']] ?? 0) + $count;
                } else {
                    $votesByDeptInternal[$vr['department']] = ($votesByDeptInternal[$vr['department']] ?? 0) + $count;
                }
            }
        }

        // Prepare Common Data Base
        $baseData = [
            'name'     => $candidate['name'],
            'party'    => $candidate['party'],
            'position' => $candidate['position'],
            'college'  => $candidateCollege
        ];

        $posKey = $candidate['position'] . '|' . $candidate['party'];

        $combinedTotal = $internalVotes + $externalVotes;
        $combinedVotes = [];

        // Merge vote arrays together
        foreach ($colleges as $col) {
            $combinedVotes[$col] = ($votesByCollegeInternal[$col] ?? 0) + ($votesByCollegeExternal[$col] ?? 0);
        }

        $combinedData = array_merge($baseData, [
            'total' => $combinedTotal,
            'votes' => $combinedVotes
        ]);
        // --- INTERNAL BUCKET ---
        if ($internalVotes > 0) {
            $internalData = array_merge($baseData, [
                'total' => $internalVotes,
                'votes' => ($level === 'Central') ? $votesByCollegeInternal : $votesByDeptInternal
            ]);

            if ($level === 'Central') {

                $candidatesByLevel['Central'][$candidate['position']][$candidate['name']] = $internalData;
                $leaderboard['Central'][] = $combinedData;

                if (!isset($highestVotes['Central'][$posKey]) || $internalVotes > $highestVotes['Central'][$posKey]['total']) {
                    $highestVotes['Central'][$posKey] = $internalData;
                }

                $totalVotesByPosition['Central'][$candidate['position']] =
                    ($totalVotesByPosition['Central'][$candidate['position']] ?? 0) + $internalVotes;
            } else {

                $candidatesByLevel['Local']['internal'][$candidate['position']][$candidate['name']] = $internalData;
                $leaderboard['Local'][] = $combinedData;

                if (!isset($highestVotes['Local'][$posKey]) || $internalVotes > $highestVotes['Local'][$posKey]['total']) {
                    $highestVotes['Local'][$posKey] = $internalData;
                }

                $totalVotesByPosition['Local'][$candidate['position']] =
                    ($totalVotesByPosition['Local'][$candidate['position']] ?? 0) + $internalVotes;
            }
        }

        // --- EXTERNAL (ESU) BUCKET ---
        if ($externalVotes > 0) {
            $externalData = array_merge($baseData, [
                'total' => $externalVotes,
                'votes' => ($level === 'Central') ? $votesByCollegeExternal : $votesByDeptExternal
            ]);

            if ($level === 'Central') {
                $candidatesByLevel['External'][$candidate['position']][$candidate['name']] = $externalData;

                // ✅ Use combinedTotal here, not just $externalVotes
                if (!isset($highestVotes['External'][$posKey]) || $combinedTotal > $highestVotes['External'][$posKey]['total']) {
                    $highestVotes['External'][$posKey] = $externalData;
                }
                $totalVotesByPosition['External'][$candidate['position']] = ($totalVotesByPosition['External'][$candidate['position']] ?? 0) + $externalVotes;
            } else {
                $candidatesByLevel['Local']['external'][$candidate['position']][$candidate['name']] = $externalData;

                // ✅ Same fix for Local_ESU
                if (!isset($highestVotes['Local_ESU'][$posKey]) || $combinedTotal > $highestVotes['Local_ESU'][$posKey]['total']) {
                    $highestVotes['Local_ESU'][$posKey] = $externalData;
                }
                $totalVotesByPosition['Local_ESU'][$candidate['position']] = ($totalVotesByPosition['Local_ESU'][$candidate['position']] ?? 0) + $externalVotes;
            }
        }
    }

    function renderCentralTable($pdo, $title, $positions, $colleges, $data, $college_filter, $totalVotesByPosition, $highestVotes, $type)
    {
        $candidateVotes = [];
        $positionTotals = [];

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
                    foreach ($colleges as $college_id) {
                        $voteCount = $info['votes'][$college_id] ?? 0;
                        $candidateVotes[$position][$candidate] += $voteCount;
                    }
                }

                $positionTotal += $candidateVotes[$position][$candidate];
            }
            $positionTotals[$position] = $positionTotal;
        }

        if (empty($positionTotals)) return;

        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-bordered nowrap mb-5" style="width:100%">';
        echo '<tr>';
        echo '<td class="text-center text-white" style="background-color: #B22222" colspan="4"><h5><b>' . htmlspecialchars($title) . '</b></h5></td>';
        echo '<td class="text-center" colspan="' . ($college_filter ? 0 : count($colleges)) . '">';
        if ($college_filter) {
            $college_name = $pdo->query("SELECT college_name FROM colleges WHERE college_id = $college_filter")->fetchColumn();
            echo '<h5>' . htmlspecialchars($college_name ?: 'Selected College') . '</h5>';
        } else {
            echo '<h5>List of Colleges</h5>';
        }
        echo '</td>';
        echo '<td class="text-center" rowspan="2"><b>TOTAL</b></td>';
        echo '</tr>';

        echo '<tr class="text-center">';
        echo '<td><b>Position</b></td><td><b>Candidates</b></td><td><b>Party</b></td><td><b>College</b></td>';
        if ($college_filter) {
            $college_name = $pdo->query("SELECT college_name FROM colleges WHERE college_id = $college_filter")->fetchColumn();
            echo '<td><b>' . htmlspecialchars($college_name ?: $college_filter) . '</b></td>';
        } else {
            foreach ($colleges as $college_id) {
                $college_name = $pdo->query("SELECT college_name FROM colleges WHERE college_id = $college_id")->fetchColumn();
                echo '<td><b>' . htmlspecialchars($college_name ?: $college_id) . '</b></td>';
            }
        }
        echo '</tr>';

        foreach ($positions as $position) {
            if (!isset($data[$type][$position]) || !isset($positionTotals[$position])) continue;

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
                    foreach ($colleges as $college_id) {
                        $voteCount = $info['votes'][$college_id] ?? 0;
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
        if (!$college_filter && !$department_filter) return;

        $candidateVotes = [];
        $positionTotals = [];
        $filtered_candidates_by_pos = [];
        $hasVotes = false;

        foreach ($positions as $position) {
            if (isset($totalVotesByPosition['Local'][$position])) {
                $hasVotes = true;
                break;
            }
        }
        if (!$hasVotes) return;

        // Fetch college names
        $stmt = $pdo->query("SELECT college_id, college_name FROM colleges");
        $colleges_lookup = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Fetch department names
        $stmt = $pdo->query("SELECT department_id, department_name FROM departments");
        $departments_lookup = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Map filters to names
        $college_name = $college_filter ? ($colleges_lookup[$college_filter] ?? 'Unknown College') : null;
        $department_name = $department_filter ? ($departments_lookup[$department_filter] ?? 'Unknown Department') : null;

        // ✅ Keep IDs for vote lookups, names only for display
        if ($department_filter) {
            $deptIdsToShow = [$department_filter]; // ID
            $title_suffix = " - Department: " . htmlspecialchars($department_name) . " (College: " . htmlspecialchars($college_name ?? '') . ")";
        } else {
            $stmt = $pdo->prepare("SELECT DISTINCT department FROM voters WHERE college = ? AND department IS NOT NULL");
            $stmt->execute([$college_filter]);
            $deptIdsToShow = $stmt->fetchAll(PDO::FETCH_COLUMN); // IDs
            $title_suffix = " - College: " . htmlspecialchars($college_name);
        }

        if (empty($deptIdsToShow)) return;

        // Pre-calc candidate totals using IDs
        foreach ($positions as $position) {
            if (!isset($data[$position])) continue;

            $candidates_for_college = array_filter($data[$position], function ($candidate_info) use ($college_name) {
                return isset($candidate_info['college']) && $candidate_info['college'] === $college_name;
            });

            if (empty($candidates_for_college)) continue;
            $filtered_candidates_by_pos[$position] = $candidates_for_college;

            $positionTotal = 0;
            foreach ($candidates_for_college as $candidate => $info) {
                $candidateVotes[$position][$candidate] = 0;
                foreach ($deptIdsToShow as $dept_id) { // ✅ Use ID for lookup
                    $voteCount = $info['votes'][$dept_id] ?? 0;
                    $candidateVotes[$position][$candidate] += $voteCount;
                }
                $positionTotal += $candidateVotes[$position][$candidate];
            }
            $positionTotals[$position] = $positionTotal;
        }

        if (empty($positionTotals)) return;

        // Table render
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-bordered nowrap mb-5" style="width:100%">';
        echo '<tr>';
        echo '<td class="text-center text-white" style="background-color: #B22222" colspan="4"><h5><b>' . htmlspecialchars($title) . $title_suffix . '</b></h5></td>';
        echo '<td class="text-center" colspan="' . count($deptIdsToShow) . '"><h5>' . (count($deptIdsToShow) > 1 ? 'All Departments' : 'Department') . '</h5></td>';
        echo '<td class="text-center" rowspan="2"><b>TOTAL</b></td>';
        echo '</tr>';

        echo '<tr class="text-center">';
        echo '<td><b>Position</b></td><td><b>Candidates</b></td><td><b>Party</b></td><td><b>College</b></td>';
        foreach ($deptIdsToShow as $dept_id) {
            $dept_display = $departments_lookup[$dept_id] ?? $dept_id; // ✅ Name only for display
            echo '<td><b>' . htmlspecialchars($dept_display) . '</b></td>';
        }
        echo '</tr>';

        foreach ($positions as $position) {
            if (!isset($filtered_candidates_by_pos[$position]) || !isset($positionTotals[$position])) continue;

            $candidates = $filtered_candidates_by_pos[$position];
            if (empty($candidates)) continue;

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

                foreach ($deptIdsToShow as $dept_id) { // ✅ Use ID for vote lookup
                    $voteCount = $info['votes'][$dept_id] ?? 0;
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

    // DEBUG: Show vote counts to verify
    echo '<div class="alert alert-info d-none">';
    echo '<h4>Debug Information</h4>';
    echo '<p>Central Positions: ' . count($central_positions) . '</p>';
    echo '<p>Local Positions: ' . count($local_positions) . '</p>';
    echo '<p>Total Candidates: ' . count($candidates) . '</p>';
    echo '</div>';

    // Render tables
    renderLeaderboard('Top 5 Central Candidates', $leaderboard['Central']);
    renderLeaderboard('Top 5 Local Candidates', $leaderboard['Local']);

    renderCentralTable($pdo, 'CENTRAL - INTERNAL', $central_positions, $colleges, $candidatesByLevel, $college_filter, $totalVotesByPosition, $highestVotes, 'Central');

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
                $pdo,
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
