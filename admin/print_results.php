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
        AND cr.field_id = (SELECT id FROM form_fields WHERE field_name = 'position' LIMIT 1)
        AND cr_party.field_id = (SELECT id FROM form_fields WHERE field_name = 'party' LIMIT 1)
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
    $highestVotes = ['Central' => [], 'Local' => [], 'External' => []];

    foreach ($candidates as $candidate) {
        $candidateId = $candidate['id'];
        $level = $candidate['level'] === 'Central' ? 'Central' : 'Local';
        $candidateCollege = $candidate['candidate_college'] ?: 'Not Specified';

        if ($level === 'Local' && $college_filter && $candidateCollege !== $college_filter) {
            continue;
        }

        // Log missing college data for debugging
        if ($candidateCollege === 'Not Specified') {
            error_log("Candidate ID $candidateId has no student_id or college in voters table.");
        }

        // Query 8: Fetch Votes with Precinct Type
        $sql = "
            SELECT 
                COUNT(DISTINCT v.id) AS vote_count, 
                p.type AS precinct_type, 
                vtr.college, 
                vtr.department
            FROM votes v
            JOIN precinct_voters pv ON v.student_id = pv.student_id
            JOIN precincts p ON pv.precinct = p.name
            JOIN voters vtr ON v.student_id = vtr.student_id
            WHERE v.candidate_id = ? AND v.voting_period_id = ?
        ";
        $params = [$candidateId, $voting_period_id];
        if ($college_filter) {
            $sql .= " AND vtr.college = ?";
            $params[] = $college_filter;
        }
        if ($department_filter) {
            $sql .= " AND vtr.department = ?";
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

        foreach ($voteData as $row) {
            $precinctType = strtolower($row['precinct_type']);
            $voteCollege = $row['college'] ?: 'Not Specified';
            $voteDepartment = $row['department'] ?: 'Not Specified';
            if (in_array($precinctType, ['internal', 'central', 'main campus'])) {
                if ($level === 'Central') {
                    $votesByCollege[$voteCollege] += $row['vote_count'];
                } else {
                    $votesByDepartment[$voteDepartment] += $row['vote_count'];
                }
                $totalVotesInternal += $row['vote_count'];
            } elseif (in_array($precinctType, ['external', 'wmsu esu'])) {
                if ($level === 'Central') {
                    $votesByCollege[$voteCollege] += $row['vote_count'];
                } else {
                    $votesByDepartment[$voteDepartment] += $row['vote_count'];
                }
                $totalVotesExternal += $row['vote_count'];
            }
        }

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
        if ($level === 'Central') {
            if (!isset($highestVotes['Central'][$key]) || $totalVotesInternal > $highestVotes['Central'][$key]['total']) {
                $highestVotes['Central'][$key] = $candidateDataInternal;
            }
            $candidatesByLevel['Central'][$candidate['position']][$candidate['name']] = $candidateDataInternal;
            $totalVotesByPosition['Central'][$candidate['position']] = ($totalVotesByPosition['Central'][$candidate['position']] ?? 0) + $totalVotesInternal;
        }

        // External (External precincts, Central level)
        if ($level === 'Central') {
            if (!isset($highestVotes['External'][$key]) || $totalVotesExternal > $highestVotes['External'][$key]['total']) {
                $highestVotes['External'][$key] = $candidateDataExternal;
            }
            $candidatesByLevel['External'][$candidate['position']][$candidate['name']] = $candidateDataExternal;
            $totalVotesByPosition['External'][$candidate['position']] = ($totalVotesByPosition['External'][$candidate['position']] ?? 0) + $totalVotesExternal;
        }

        // Local (split by precinct type)
        if ($level === 'Local') {
            if ($totalVotesInternal > 0) {
                if (!isset($highestVotes['Local'][$key]) || $totalVotesInternal > $highestVotes['Local'][$key]['total']) {
                    $highestVotes['Local'][$key] = $candidateDataInternal;
                }
                $candidatesByLevel['Local']['internal'][$candidate['position']][$candidate['name']] = $candidateDataInternal;
            }
            if ($totalVotesExternal > 0) {
                if (!isset($highestVotes['Local'][$key]) || $totalVotesExternal > $highestVotes['Local'][$key]['total']) {
                    $highestVotes['Local'][$key] = $candidateDataExternal;
                }
                $candidatesByLevel['Local']['external'][$candidate['position']][$candidate['name']] = $candidateDataExternal;
            }
            $totalVotesByPosition['Local'][$candidate['position']] = ($totalVotesByPosition['Local'][$candidate['position']] ?? 0) + ($totalVotesInternal + $totalVotesExternal);
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

    // Check for external votes
    $hasExternalVotes = array_sum($totalVotesByPosition['External']) > 0;
    $hasLocalExternalVotes = array_sum($totalVotesByPosition['Local']) > 0;

    // Function to render candidate entry in document format
    function renderCandidateEntry($candidate, $position, $type, $highestVotes, $colleges, $all_departments, $dept_to_college, $college_filter, $department_filter)
    {
        $key = $position . '|' . $candidate['party'];
        $is_highest = isset($highestVotes[$type][$key]) && $highestVotes[$type][$key]['name'] === $candidate['name'];
        $style = $is_highest ? 'font-weight: bold;' : '';

        echo '<div class="candidate-entry mb-4" style="' . $style . '">';
        echo '<p><strong>' . htmlspecialchars($candidate['name']) . '</strong>';
        if ($is_highest) {
            echo ' <span class="badge bg-success">Highest Votes</span>';
        }
        echo '</p>';
        echo '<p><strong>Position:</strong> ' . htmlspecialchars($position) . '</p>';
        echo '<p><strong>College:</strong> ' . htmlspecialchars($candidate['college']) . '</p>';
        echo '<p><strong>Party:</strong> ' . htmlspecialchars($candidate['party']) . '</p>';
        echo '<p><strong>Number of Votes:</strong> ' . number_format($candidate['total']) . '</p>';
        echo '<p><strong>Votes:</strong></p>';
        echo '<ul>';

        if ($type === 'Central' || $type === 'External') {
            // Central positions: show votes by college
            if ($college_filter) {
                echo '<li>' . htmlspecialchars($college_filter) . ': ' . number_format($candidate['votes'][$college_filter]) . '</li>';
            } else {
                foreach ($colleges as $college) {
                    if ($candidate['votes'][$college] > 0) {
                        echo '<li>' . htmlspecialchars($college) . ': ' . number_format($candidate['votes'][$college]) . '</li>';
                    }
                }
            }
        } else {
            // Local positions: show votes by department with college
            if ($department_filter) {
                $college = $dept_to_college[$department_filter] ?? 'Unknown';
                echo '<li>' . htmlspecialchars($college) . ' | ' . htmlspecialchars($department_filter) . ': ' . number_format($candidate['votes'][$department_filter]) . '</li>';
            } elseif ($college_filter) {
                foreach ($all_departments as $dept) {
                    if ($candidate['votes'][$dept] > 0 && ($dept_to_college[$dept] ?? '') === $college_filter) {
                        echo '<li>' . htmlspecialchars($college_filter) . ' | ' . htmlspecialchars($dept) . ': ' . number_format($candidate['votes'][$dept]) . '</li>';
                    }
                }
            } else {
                foreach ($all_departments as $dept) {
                    if ($candidate['votes'][$dept] > 0) {
                        $college = $dept_to_college[$dept] ?? 'Unknown';
                        echo '<li>' . htmlspecialchars($college) . ' | ' . htmlspecialchars($dept) . ': ' . number_format($candidate['votes'][$dept]) . '</li>';
                    }
                }
            }
        }
        echo '</ul>';
        echo '</div>';
    }

    // Start HTML output
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Election Results</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<style>';
    echo '@media print {';
    echo '  body { font-size: 12pt; }';
    echo '  .candidate-entry { page-break-inside: avoid; margin-bottom: 1.5em; }';
    echo '  .badge { display: inline-block; padding: 0.2em 0.4em; }';
    echo '  h1, h2 { text-align: center; }';
    echo '  .container { max-width: 100%; }';
    echo '}';
    echo '@media screen {';
    echo '  .candidate-entry { border-bottom: 1px solid #ddd; padding-bottom: 1em; }';
    echo '}';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<div class="container mt-4">';

    // Header
    echo '<h1>Election Results: ' . htmlspecialchars($election_name) . '</h1>';
    echo '<h2>Voting Period: ' . htmlspecialchars($row['voting_period_name']) . '</h2>';
    echo '<p class="text-center"><strong>Status:</strong> ' . htmlspecialchars($election_status) . '</p>';
    echo '<hr>';

    // Central - Internal
    $hasCentralVotes = array_sum($totalVotesByPosition['Central']) > 0;
    if ($hasCentralVotes) {
        echo '<h2>Central - Internal</h2>';
        foreach ($central_positions as $position) {
            if (isset($candidatesByLevel['Central'][$position]) && $totalVotesByPosition['Central'][$position] > 0) {
                echo '<h3>' . htmlspecialchars($position) . '</h3>';
                echo '<p><strong>Total Votes:</strong> ' . number_format($totalVotesByPosition['Central'][$position]) . '</p>';
                foreach ($candidatesByLevel['Central'][$position] as $candidate) {
                    renderCandidateEntry($candidate, $position, 'Central', $highestVotes, $colleges, $all_departments, $dept_to_college, $college_filter, $department_filter);
                }
            }
        }
    } else {
        echo '<div class="alert alert-info text-center">No central internal votes recorded for this voting period.</div>';
    }
    echo '<hr>';

    // Central - External
    if ($hasExternalVotes) {
        echo '<h2>Central - External</h2>';
        foreach ($central_positions as $position) {
            if (isset($candidatesByLevel['External'][$position]) && $totalVotesByPosition['External'][$position] > 0) {
                echo '<h3>' . htmlspecialchars($position) . '</h3>';
                echo '<p><strong>Total Votes:</strong> ' . number_format($totalVotesByPosition['External'][$position]) . '</p>';
                foreach ($candidatesByLevel['External'][$position] as $candidate) {
                    renderCandidateEntry($candidate, $position, 'External', $highestVotes, $colleges, $all_departments, $dept_to_college, $college_filter, $department_filter);
                }
            }
        }
    } else {
        echo '<div class="alert alert-info text-center">No central external votes recorded for this voting period.</div>';
    }
    echo '<hr>';

    // Local - Internal
    $hasLocalInternalVotes = array_sum($totalVotesByPosition['Local']) > 0;
    if ($hasLocalInternalVotes) {
        echo '<h2>Local - Internal</h2>';
        foreach ($local_positions as $position) {
            if (isset($candidatesByLevel['Local']['internal'][$position]) && $totalVotesByPosition['Local'][$position] > 0) {
                echo '<h3>' . htmlspecialchars($position) . '</h3>';
                echo '<p><strong>Total Votes:</strong> ' . number_format($totalVotesByPosition['Local'][$position]) . '</p>';
                foreach ($candidatesByLevel['Local']['internal'][$position] as $candidate) {
                    renderCandidateEntry($candidate, $position, 'Local', $highestVotes, $colleges, $all_departments, $dept_to_college, $college_filter, $department_filter);
                }
            }
        }
    } else {
        echo '<div class="alert alert-info text-center">No local internal votes recorded for this voting period.</div>';
    }
    echo '<hr>';

    // Local - External
    if ($hasLocalExternalVotes) {
        echo '<h2>Local - External</h2>';
        foreach ($local_positions as $position) {
            if (isset($candidatesByLevel['Local']['external'][$position]) && $totalVotesByPosition['Local'][$position] > 0) {
                echo '<h3>' . htmlspecialchars($position) . '</h3>';
                echo '<p><strong>Total Votes:</strong> ' . number_format($totalVotesByPosition['Local'][$position]) . '</p>';
                foreach ($candidatesByLevel['Local']['external'][$position] as $candidate) {
                    renderCandidateEntry($candidate, $position, 'Local', $highestVotes, $colleges, $all_departments, $dept_to_college, $college_filter, $department_filter);
                }
            }
        }
    } else {
        echo '<div class="alert alert-info text-center">No local external votes recorded for this voting period.</div>';
    }

    echo '</div>';
    echo '</body>';
    echo '</html>';
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
