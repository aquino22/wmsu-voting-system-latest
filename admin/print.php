<?php
include('includes/conn.php');

$voting_period_id = $_GET['voting_period_id'] ?? null;
$sort_order = $_POST['sort_order'] ?? 'highest';

if (!$voting_period_id) {
    echo "<p>No voting period specified.</p>";
    exit();
}

// ============================================================
// LOOKUP MAPS — resolve IDs to names up front
// ============================================================

// college_id => ['name', 'abbr']
$collegeMap = [];
foreach ($pdo->query("SELECT college_id, college_name, college_abbreviation FROM colleges ORDER BY college_name ASC")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $collegeMap[(int)$r['college_id']] = [
        'name' => $r['college_name'],
        'abbr' => $r['college_abbreviation'],
    ];
}

// department_id => ['name', 'college_id']
$departmentMap = [];
foreach ($pdo->query("SELECT department_id, department_name, college_id FROM departments ORDER BY department_name ASC")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $departmentMap[(int)$r['department_id']] = [
        'name'       => $r['department_name'],
        'college_id' => (int)$r['college_id'],
    ];
}

// campus_id => ['name', 'parent_id']
$campusMap = [];
foreach ($pdo->query("SELECT campus_id, campus_name, parent_id FROM campuses")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $campusMap[(int)$r['campus_id']] = [
        'name'      => $r['campus_name'],
        'parent_id' => $r['parent_id'],
    ];
}

// Determine if a campus_id is Main Campus or ESU
function campusType(int $campusId, array $campusMap): string
{
    if (!isset($campusMap[$campusId])) return 'Main Campus';
    $c = $campusMap[$campusId];
    if ($c['parent_id'] !== null) return 'ESU';               // sub-campus → ESU
    if (stripos($c['name'], 'ESU') !== false) return 'ESU';   // root ESU campus
    return 'Main Campus';
}

// ============================================================
// 1. Voting Period + Election
// ============================================================
$stmt = $pdo->prepare("
    SELECT e.election_name AS voting_period_name, e.id AS election_id, e.status
    FROM voting_periods vp
    LEFT JOIN elections e ON vp.election_id = e.id
    WHERE vp.id = ?
    LIMIT 1
");
$stmt->execute([$voting_period_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || !$row['voting_period_name']) {
    echo "<p>No voting period found for this ID.</p>";
    exit();
}

$election_name   = $row['voting_period_name'];
$election_status = $row['status'] ?? 'Unknown';
$election_id     = (int)($row['election_id'] ?? 0);

// ============================================================
// 2. Registration Form
// ============================================================
$stmt = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ? LIMIT 1");
$stmt->execute([$election_id]);
$formId = (int)$stmt->fetchColumn();

if (!$formId) {
    echo "<p>No active registration form found.</p>";
    exit();
}

// ============================================================
// 3. Parties
// ============================================================
$stmt = $pdo->prepare("SELECT DISTINCT name FROM parties WHERE election_id = ?");
$stmt->execute([$election_id]);
$parties = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($parties)) {
    echo "<p>No parties found for this election.</p>";
    exit();
}

// ============================================================
// 4. Positions
// ============================================================
$placeholders = implode(',', array_fill(0, count($parties), '?'));
$stmt = $pdo->prepare("
    SELECT DISTINCT p.name, p.level
    FROM positions p
    JOIN candidate_responses cr        ON cr.value = p.name
    JOIN candidates c                  ON c.id = cr.candidate_id
    JOIN candidate_responses cr_party  ON cr_party.candidate_id = c.id
    WHERE c.form_id = ?
      AND c.status  = 'accepted'
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

$central_positions = array_column(array_filter($positions, fn($p) => $p['level'] === 'Central'), 'name');
$local_positions   = array_column(array_filter($positions, fn($p) => $p['level'] !== 'Central'), 'name');

// ============================================================
// 5. Precincts for this election
// ============================================================
$stmt = $pdo->prepare("
    SELECT id, name, college AS college_id, department AS dept_id, type AS campus_type_id
    FROM precincts
    WHERE election = ?
");
$stmt->execute([$election_id]);
$precinctRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map precinct name-string (used in votes.precinct) => precinct row
$precinctByStrId = [];
foreach ($precinctRows as $pr) {
    $precinctByStrId[(string)$pr['id']] = $pr;
}

// Collect unique college IDs from precincts (ordered A-Z by name)
$collegeIdsInElection = [];
foreach ($precinctRows as $pr) {
    $cid = (int)$pr['college_id'];
    if ($cid && isset($collegeMap[$cid])) {
        $collegeIdsInElection[$cid] = $collegeMap[$cid]['name'];
    }
}
asort($collegeIdsInElection); // sort by name

// ============================================================
// 6. Voter map: student_id => demographics (resolved names)
// ============================================================
$voterInfo = []; // student_id => ['college_id','college_name','dept_id','dept_name','campus_type']

$stmt = $pdo->query("
    SELECT student_id, college AS college_id, department AS dept_id, wmsu_campus, external_campus
    FROM voters
    WHERE status IN ('confirmed','active')
");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $v) {
    $cid  = (int)($v['college_id'] ?? 0);
    $did  = (int)($v['dept_id']    ?? 0);
    $wmsu = (int)($v['wmsu_campus']      ?? 0);
    $ext  = (int)($v['external_campus']  ?? 0);

    // Determine campus type from the voter's assigned campus
    $campType = 'Main Campus';
    if ($ext > 0) {
        $campType = campusType($ext, $campusMap);
    } elseif ($wmsu > 0) {
        $campType = campusType($wmsu, $campusMap);
    }

    $voterInfo[(string)$v['student_id']] = [
        'college_id'   => $cid,
        'college_name' => $collegeMap[$cid]['name']     ?? 'Unknown',
        'dept_id'      => $did,
        'dept_name'    => $departmentMap[$did]['name']  ?? 'Unknown',
        'campus_type'  => $campType,
    ];
}

// ============================================================
// 7. Candidates
// ============================================================
$stmt = $pdo->prepare("
    SELECT
        c.id,
        cr_name.value   AS name,
        cr_party.value  AS party,
        cr_pos.value    AS position,
        p.level,
        cr_sid.value    AS student_id
    FROM candidates c
    JOIN candidate_responses cr_name  ON c.id = cr_name.candidate_id
        AND cr_name.field_id  = (SELECT id FROM form_fields WHERE field_name='full_name'   AND form_id=c.form_id LIMIT 1)
    JOIN candidate_responses cr_party ON c.id = cr_party.candidate_id
        AND cr_party.field_id = (SELECT id FROM form_fields WHERE field_name='party'       AND form_id=c.form_id LIMIT 1)
    JOIN candidate_responses cr_pos   ON c.id = cr_pos.candidate_id
        AND cr_pos.field_id   = (SELECT id FROM form_fields WHERE field_name='position'    AND form_id=c.form_id LIMIT 1)
    JOIN candidate_responses cr_sid   ON c.id = cr_sid.candidate_id
        AND cr_sid.field_id   = (SELECT id FROM form_fields WHERE field_name='student_id'  AND form_id=c.form_id LIMIT 1)
    JOIN positions p ON cr_pos.value = p.name AND p.election_id = ?
    WHERE c.form_id = ? AND c.status = 'accepted'
    GROUP BY c.id, cr_name.value, cr_party.value, cr_pos.value, p.level, cr_sid.value
");
$stmt->execute([$election_id, $formId]);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($candidates)) {
    echo "<p>No accepted candidates found.</p>";
    exit();
}

// Resolve each candidate's own college from their voter record
foreach ($candidates as &$cand) {
    $vi = $voterInfo[(string)$cand['student_id']] ?? null;
    $cand['college_id']   = $vi['college_id']   ?? 0;
    $cand['college_name'] = $vi['college_name'] ?? 'Unknown';
}
unset($cand);

// ============================================================
// 8. Votes — aggregate per candidate with demographics
// ============================================================
$stmt = $pdo->prepare("
    SELECT candidate_id, student_id, precinct
    FROM votes
    WHERE voting_period_id = ?
");
$stmt->execute([$voting_period_id]);
$allVotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// voteAgg[candidate_id] => { total, mainCampus, esu, byCollege[name=>n], byDept[name=>n] }
$voteAgg = [];

foreach ($allVotes as $vote) {
    $cid   = (int)$vote['candidate_id'];
    $sid   = (string)$vote['student_id'];
    $pname = (string)$vote['precinct'];

    $vi = $voterInfo[$sid] ?? [
        'college_name' => 'Unknown',
        'dept_name'    => 'Unknown',
        'campus_type'  => 'Main Campus',
    ];

    // Use precinct's campus type (more reliable than voter's registered campus)
    $precinctRow = $precinctByStrId[$pname] ?? null;
    $campType = 'Main Campus';
    if ($precinctRow) {
        $campType = campusType((int)($precinctRow['campus_type_id'] ?? 0), $campusMap);
    } else {
        $campType = $vi['campus_type'];
    }

    if (!isset($voteAgg[$cid])) {
        $voteAgg[$cid] = ['total' => 0, 'mainCampus' => 0, 'esu' => 0, 'byCollege' => [], 'byDept' => []];
    }

    $cn = $vi['college_name'];
    $dn = $vi['dept_name'];

    $voteAgg[$cid]['total']++;
    $voteAgg[$cid]['byCollege'][$cn] = ($voteAgg[$cid]['byCollege'][$cn] ?? 0) + 1;
    $voteAgg[$cid]['byDept'][$dn]    = ($voteAgg[$cid]['byDept'][$dn]    ?? 0) + 1;

    if ($campType === 'ESU') {
        $voteAgg[$cid]['esu']++;
    } else {
        $voteAgg[$cid]['mainCampus']++;
    }
}

// ============================================================
// 9. Build candidatesByLevel
// ============================================================
// Central: position => [name => data]
// Local:   college_name => position => [name => data]
$candidatesByLevel = ['Central' => [], 'Local' => []];
$positionTotals    = ['Central' => [], 'Local' => []];

foreach ($candidates as $cand) {
    $cid   = (int)$cand['id'];
    $level = $cand['level'] === 'Central' ? 'Central' : 'Local';
    $pos   = $cand['position'];
    $name  = $cand['name'];
    $cname = $cand['college_name'];

    $agg = $voteAgg[$cid] ?? ['total' => 0, 'mainCampus' => 0, 'esu' => 0, 'byCollege' => [], 'byDept' => []];

    $data = [
        'id'         => $cid,
        'name'       => $name,
        'party'      => $cand['party'],
        'position'   => $pos,
        'college'    => $cname,
        'total'      => $agg['total'],
        'mainCampus' => $agg['mainCampus'],
        'esu'        => $agg['esu'],
        'byCollege'  => $agg['byCollege'],
        'byDept'     => $agg['byDept'],
    ];

    if ($level === 'Central') {
        $candidatesByLevel['Central'][$pos][$name] = $data;
        $positionTotals['Central'][$pos] = ($positionTotals['Central'][$pos] ?? 0) + $agg['total'];
    } else {
        if (!isset($candidatesByLevel['Local'][$cname])) {
            $candidatesByLevel['Local'][$cname] = [];
        }
        $candidatesByLevel['Local'][$cname][$pos][$name] = $data;
        $positionTotals['Local'][$pos] = ($positionTotals['Local'][$pos] ?? 0) + $agg['total'];
    }
}

// Sort within each position
foreach ($candidatesByLevel['Central'] as $pos => &$cands) {
    uasort($cands, fn($a, $b) => $b['total'] <=> $a['total']);
}
unset($cands);
foreach ($candidatesByLevel['Local'] as $col => &$posList) {
    foreach ($posList as $pos => &$cands) {
        uasort($cands, fn($a, $b) => $b['total'] <=> $a['total']);
    }
}
unset($posList, $cands);

// ============================================================
// 10. Overall voter statistics
// ============================================================
$totalVoters = (int)$pdo->query(
    "SELECT COUNT(*) FROM voters WHERE status IN ('confirmed','active')"
)->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM votes WHERE voting_period_id = ?");
$stmt->execute([$voting_period_id]);
$votedCount = (int)$stmt->fetchColumn();

$notVotedCount      = $totalVoters - $votedCount;
$votedPercentage    = $totalVoters > 0 ? round(($votedCount / $totalVoters) * 100, 2) : 0;
$notVotedPercentage = round(100 - $votedPercentage, 2);

// ============================================================
// 11. Election dates + academic year
// ============================================================
$currentDate = date('F j, Y');

$stmt = $pdo->prepare("SELECT start_period FROM voting_periods WHERE id = ?");
$stmt->execute([$voting_period_id]);
$electionDate = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT ay.semester, ay.year_label, ay.start_date AS school_year_start, ay.end_date AS school_year_end
    FROM voting_periods vp
    JOIN elections e        ON vp.election_id = e.id
    JOIN academic_years ay  ON e.academic_year_id = ay.id
    WHERE vp.id = ?
    LIMIT 1
");
$stmt->execute([$voting_period_id]);
$academicYear    = $stmt->fetch(PDO::FETCH_ASSOC);
$semester        = $academicYear['semester']    ?? 'N/A';
$schoolYearLabel = $academicYear['year_label']  ?? 'N/A';

// ============================================================
// 12. Precinct summary from DB (by college_name)
// ============================================================
$precinctSummary = []; // college_name => ['main'=>n, 'esu'=>n]
foreach ($precinctRows as $pr) {
    $cid   = (int)$pr['college_id'];
    $cname = $collegeMap[$cid]['name'] ?? "College $cid";
    $ct    = campusType((int)($pr['campus_type_id'] ?? 0), $campusMap);

    if (!isset($precinctSummary[$cname])) {
        $precinctSummary[$cname] = ['main' => 0, 'esu' => 0];
    }
    if ($ct === 'ESU') {
        $precinctSummary[$cname]['esu']++;
    } else {
        $precinctSummary[$cname]['main']++;
    }
}
ksort($precinctSummary);

// ============================================================
// Helpers
// ============================================================
function formatDate($dateStr): string
{
    return $dateStr ? date('F j, Y', strtotime($dateStr)) : 'N/A';
}
function safeChartId(string ...$parts): string
{
    return 'chart_' . preg_replace('/[^a-zA-Z0-9]/', '_', implode('_', $parts));
}

$CHART_COLORS = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF', '#E7E9ED'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results — <?= htmlspecialchars($election_name) ?></title>
    <link rel="shortcut icon" href="images/favicon.png" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 13px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header-center {
            text-align: center;
            flex-grow: 1;
        }

        .page {
            page-break-after: always;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: center;
            font-size: 12px;
        }

        th {
            background-color: #f0f0f0;
        }

        .winner-row td {
            background-color: #d4edda;
            font-weight: bold;
        }

        .chart-container {
            width: 280px;
            height: 280px;
            margin: 0 auto;
            position: relative;
        }

        .two-columns {
            display: flex;
            justify-content: space-between;
            gap: 16px;
        }

        .column {
            width: 48%;
        }

        .no-data-chart {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 280px;
            height: 80px;
            border: 1px solid #ddd;
            font-style: italic;
            color: #999;
            margin: 0 auto;
            font-size: 12px;
        }

        .signature-box {
            display: inline-block;
            width: 260px;
            margin: 10px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 30px;
            padding-top: 4px;
            font-size: 11px;
        }

        h2,
        h3 {
            color: #333;
        }

        .center {
            text-align: center;
        }

        .overflow-table {
            overflow-x: auto;
        }

        .print-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, .2);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .3s;
        }

        .print-button:hover {
            background-color: #0a58ca;
            transform: scale(1.05);
        }

        @media print {
            .print-button {
                display: none;
            }

            body {
                padding: 0;
            }

            .page {
                page-break-after: always;
                padding: 15px;
            }
        }
    </style>
</head>

<body>

    <!-- ═══════════════════════════════════════════════
     PAGE 1 — Overview
═══════════════════════════════════════════════ -->
    <div class="page">
        <div class="header">
            <img src="images/wmsu-logo.png" alt="WMSU Logo" style="height:100px;width:auto;">
            <div class="header-center">
                <h1><?= htmlspecialchars($election_name) ?> Elections</h1>
                <h2>OFFICIAL ELECTION RESULTS</h2>
                <p><?= htmlspecialchars($semester) ?>, AY <?= htmlspecialchars($schoolYearLabel) ?></p>
                <p>Election Held: <?= formatDate($electionDate) ?></p>
                <p>Report Generated: <?= $currentDate ?></p>
            </div>
            <img src="images/osa_logo.png" alt="OSA Logo" style="height:100px;width:auto;">
        </div>
        <hr>

        <!-- Overall Voter Participation -->
        <h3 class="center">Overall Voter Participation</h3><br>
        <div class="two-columns">
            <div class="column">
                <table>
                    <tr>
                        <th>Category</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                    <tr>
                        <td>Total Registered Voters</td>
                        <td><?= number_format($totalVoters) ?></td>
                        <td>100%</td>
                    </tr>
                    <tr>
                        <td>Voted</td>
                        <td><?= number_format($votedCount)    ?></td>
                        <td><?= $votedPercentage ?>%</td>
                    </tr>
                    <tr>
                        <td>Did Not Vote</td>
                        <td><?= number_format($notVotedCount) ?></td>
                        <td><?= $notVotedPercentage ?>%</td>
                    </tr>
                </table>
            </div>
            <div class="column">
                <?php if ($totalVoters > 0): ?>
                    <div class="chart-container"><canvas id="participationChart"></canvas></div>
                <?php else: ?>
                    <div class="no-data-chart">No voter data available</div>
                <?php endif; ?>
            </div>
        </div>
        <hr>

        <!-- Central Level — Elected Officials summary -->
        <h3 class="center">Central Level — Elected Officials</h3>
        <table>
            <tr>
                <th>Position</th>
                <th>Elected Candidate</th>
                <th>Party</th>
                <th>Total Votes</th>
                <th>Status</th>
            </tr>
            <?php foreach ($central_positions as $position):
                $cands = array_values($candidatesByLevel['Central'][$position] ?? []);
                if (empty($cands)) continue;
                usort($cands, fn($a, $b) => $b['total'] <=> $a['total']);
                $topVotes  = $cands[0]['total'];
                $topCands  = array_filter($cands, fn($c) => $c['total'] === $topVotes);
                $isTie     = count($topCands) > 1;
                $firstRow  = true;
            ?>
                <?php foreach ($topCands as $tc): ?>
                    <tr class="winner-row">
                        <?php if ($firstRow): ?>
                            <td rowspan="<?= count($topCands) ?>"><?= htmlspecialchars($position) ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($tc['name']) ?></td>
                        <td><?= htmlspecialchars($tc['party']) ?></td>
                        <td><?= number_format($tc['total']) ?></td>
                        <td><?= $isTie ? 'Tie' : 'Winner' ?></td>
                    </tr>
                    <?php $firstRow = false; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </table>
        <hr>

        <!-- Central Level — Detailed per position (two-column layout with charts) -->
        <h3 class="center">Central Level — Detailed Results by Position</h3><br>
        <div class="two-columns">
            <?php
            $half = (int)ceil(count($central_positions) / 2);
            foreach (array_chunk($central_positions, max(1, $half)) as $colPositions):
            ?>
                <div class="column">
                    <?php foreach ($colPositions as $position):
                        $cands = array_values($candidatesByLevel['Central'][$position] ?? []);
                        if (empty($cands)) continue;
                        usort($cands, fn($a, $b) => $b['total'] <=> $a['total']);
                        $posTotal = array_sum(array_column($cands, 'total')) ?: 1;
                        $topVotes = $cands[0]['total'];
                        $cid = safeChartId('central', $position, (string)$voting_period_id);

                        // Build chart data
                        $cLabels = $cData = [];
                        foreach ($cands as $c) {
                            if ($c['total'] > 0) {
                                $lbl = (strlen($c['name']) > 18 ? substr($c['name'], 0, 18) . '…' : $c['name'])
                                    . ' (' . $c['party'] . ')';
                                $cLabels[] = $lbl;
                                $cData[]   = $c['total'];
                            }
                        }
                    ?>
                        <table>
                            <tr>
                                <th colspan="5"><?= htmlspecialchars($position) ?></th>
                            </tr>
                            <tr>
                                <th>Candidate</th>
                                <th>Party</th>
                                <th>Votes</th>
                                <th>%</th>
                                <th>Rank</th>
                            </tr>
                            <?php $rank = 1;
                            foreach ($cands as $c):
                                $pct = round(($c['total'] / $posTotal) * 100, 2);
                                $win = ($c['total'] === $topVotes && $topVotes > 0);
                            ?>
                                <tr <?= $win ? 'class="winner-row"' : '' ?>>
                                    <td><?= htmlspecialchars($c['name'])  ?></td>
                                    <td><?= htmlspecialchars($c['party']) ?></td>
                                    <td><?= number_format($c['total'])    ?></td>
                                    <td><?= $pct ?>%</td>
                                    <td><?= $rank++ ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>

                        <?php if (!empty($cData)): ?>
                            <div class="chart-container"><canvas id="<?= $cid ?>"></canvas></div>
                        <?php else: ?>
                            <div class="no-data-chart">No votes yet</div>
                        <?php endif; ?>
                        <br>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <hr>

        <!-- Central Level — Breakdown by College -->
        <h3 class="center">Central Level — Votes Breakdown by College</h3>
        <div class="overflow-table">
            <table border="1">
                <thead>
                    <tr>
                        <th rowspan="1">Position</th>
                        <th rowspan="1">Candidate</th>
                        <th rowspan="1">Party</th>
                        <?php foreach ($collegeIdsInElection as $cid => $cname): ?>
                            <th><?= htmlspecialchars($collegeMap[$cid]['abbr']) ?></th>
                        <?php endforeach; ?>
                        <th rowspan="1">Total</th>
                        <th rowspan="1">Rank</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($central_positions as $position):
                        $cands = array_values($candidatesByLevel['Central'][$position] ?? []);
                        if (empty($cands)) continue;

                        // Sort candidates by total votes descending
                        usort($cands, fn($a, $b) => ($b['total'] ?? 0) <=> ($a['total'] ?? 0));

                        $topVotes = $cands[0]['total'] ?? 0;
                        $rank = 1;
                        foreach ($cands as $index => $c):
                            $isWinner = ($c['total'] === $topVotes && $topVotes > 0);
                    ?>
                            <tr <?= $isWinner ? 'class="winner-row"' : '' ?>>
                                <?php if ($index === 0): // Only show position for the first candidate 
                                ?>
                                    <td rowspan="<?= count($cands) ?>">
                                        <?= htmlspecialchars($position) ?>
                                    </td>
                                <?php endif; ?>

                                <td><?= htmlspecialchars($c['name']) ?></td>
                                <td><?= htmlspecialchars($c['party']) ?></td>

                                <?php
                                $rowTotal = 0;
                                foreach ($collegeIdsInElection as $cid2 => $cname2):
                                    $v = $c['byCollege'][$cname2] ?? 0;
                                    $rowTotal += $v;
                                    echo '<td>' . number_format($v) . '</td>';
                                endforeach;
                                ?>

                                <td><strong><?= number_format($rowTotal) ?></strong></td>
                                <td><?= $rank++ ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div><!-- end page 1 -->


    <!-- ═══════════════════════════════════════════════
     PAGES 2..N — Local Level per College
═══════════════════════════════════════════════ -->
    <?php foreach ($candidatesByLevel['Local'] as $collegeName => $localByPosition):

        // Find college integer ID from name
        $collegeIdNum = 0;
        foreach ($collegeMap as $k => $v) {
            if ($v['name'] === $collegeName) {
                $collegeIdNum = $k;
                break;
            }
        }

        // College voter stats via precinct_voters
        $stmt = $pdo->prepare("
        SELECT
            COUNT(pv.id)                                                     AS total,
            SUM(CASE WHEN pv.status = 'voted' THEN 1 ELSE 0 END)            AS voted
        FROM precinct_voters pv
        JOIN precincts pr ON pr.id = pv.precinct
        WHERE pr.college = ? AND pr.election = ?
    ");
        $stmt->execute([$collegeIdNum, $election_id]);
        $cs     = $stmt->fetch(PDO::FETCH_ASSOC);
        $cTotal = (int)($cs['total'] ?? 0);
        $cVoted = (int)($cs['voted'] ?? 0);
        $cNotVoted = $cTotal - $cVoted;
        $cPct      = $cTotal > 0 ? round(($cVoted / $cTotal) * 100, 2) : 0;
        $cNotPct   = round(100 - $cPct, 2);

        // Precinct counts for this college from precinctSummary
        $colPrecincts = $precinctSummary[$collegeName] ?? ['main' => 0, 'esu' => 0];

        // Departments in this college (from departmentMap)
        $collegeDeptNames = [];
        foreach ($departmentMap as $did => $dm) {
            if ($dm['college_id'] === $collegeIdNum) {
                $collegeDeptNames[$dm['name']] = true;
            }
        }
        // Also add depts that appear in actual vote breakdown for candidates of this college
        foreach ($local_positions as $pos) {
            foreach ($localByPosition[$pos] ?? [] as $c) {
                foreach ($c['byDept'] as $dn => $v) {
                    $collegeDeptNames[$dn] = true;
                }
            }
        }
        $collegeDeptNames = array_keys($collegeDeptNames);
        sort($collegeDeptNames);

        $colPartChartId = safeChartId('participation', $collegeName, (string)$voting_period_id);
    ?>
        <hr>
        <div class="page">
            <h2 class="center"><?= htmlspecialchars($collegeName) ?> — Local Election Results</h2>

            <h3 class="center">Voter Participation</h3><br>
            <div class="two-columns">
                <div class="column">
                    <table>
                        <tr>
                            <th>Category</th>
                            <th>Count</th>
                            <th>%</th>
                        </tr>
                        <tr>
                            <td>Total Registered Voters</td>
                            <td><?= number_format($cTotal)    ?></td>
                            <td>100%</td>
                        </tr>
                        <tr>
                            <td>Voted</td>
                            <td><?= number_format($cVoted)    ?></td>
                            <td><?= $cPct ?>%</td>
                        </tr>
                        <tr>
                            <td>Did Not Vote</td>
                            <td><?= number_format($cNotVoted) ?></td>
                            <td><?= $cNotPct ?>%</td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <th>Precinct Type</th>
                            <th>Count</th>
                        </tr>
                        <tr>
                            <td>Main Campus Precincts</td>
                            <td><?= $colPrecincts['main'] ?></td>
                        </tr>
                        <tr>
                            <td>WMSU ESU Precincts</td>
                            <td><?= $colPrecincts['esu']  ?></td>
                        </tr>
                        <tr>
                            <th>Total</th>
                            <td><?= $colPrecincts['main'] + $colPrecincts['esu'] ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?php if ($cTotal > 0): ?>
                        <div class="chart-container"><canvas id="<?= $colPartChartId ?>"></canvas></div>
                    <?php else: ?>
                        <div class="no-data-chart">No voter data</div>
                    <?php endif; ?>
                </div>
            </div>
            <br>

            <!-- Elected Officials -->
            <h3 class="center">Elected Officials</h3>
            <table>
                <tr>
                    <th>Position</th>
                    <th>Elected Candidate</th>
                    <th>Party</th>
                    <th>Total Votes</th>
                </tr>
                <?php foreach ($local_positions as $pos):
                    $cands = array_values($localByPosition[$pos] ?? []);
                    if (empty($cands)) continue;
                    usort($cands, fn($a, $b) => $b['total'] <=> $a['total']);
                    $top = $cands[0];
                ?>
                    <tr class="winner-row">
                        <td><?= htmlspecialchars($pos)         ?></td>
                        <td><?= htmlspecialchars($top['name'])  ?></td>
                        <td><?= htmlspecialchars($top['party']) ?></td>
                        <td><?= number_format($top['total'])    ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <hr>

            <!-- Detailed Results by Position -->
            <h3 class="center">Detailed Results by Position</h3><br>
            <div class="two-columns">
                <?php
                $half = (int)ceil(count($local_positions) / 2);
                foreach (array_chunk($local_positions, max(1, $half)) as $colPosChunk):
                ?>
                    <div class="column">
                        <?php foreach ($colPosChunk as $pos):
                            $cands = array_values($localByPosition[$pos] ?? []);
                            if (empty($cands)) continue;
                            usort($cands, fn($a, $b) => $b['total'] <=> $a['total']);
                            $posTotal = array_sum(array_column($cands, 'total')) ?: 1;
                            $topVotes = $cands[0]['total'];
                            $lcid = safeChartId('local', $pos, $collegeName, (string)$voting_period_id);

                            $lLabels = $lData = [];
                            foreach ($cands as $c) {
                                if ($c['total'] > 0) {
                                    $lbl = (strlen($c['name']) > 18 ? substr($c['name'], 0, 18) . '…' : $c['name'])
                                        . ' (' . $c['party'] . ')';
                                    $lLabels[] = $lbl;
                                    $lData[]   = $c['total'];
                                }
                            }
                        ?>
                            <table>
                                <tr>
                                    <th colspan="5"><?= htmlspecialchars($pos) ?></th>
                                </tr>
                                <tr>
                                    <th>Candidate</th>
                                    <th>Party</th>
                                    <th>Votes</th>
                                    <th>%</th>
                                    <th>Rank</th>
                                </tr>
                                <?php $rank = 1;
                                foreach ($cands as $c):
                                    $pct = round(($c['total'] / $posTotal) * 100, 2);
                                    $win = ($c['total'] === $topVotes && $topVotes > 0);
                                ?>
                                    <tr <?= $win ? 'class="winner-row"' : '' ?>>
                                        <td><?= htmlspecialchars($c['name'])  ?></td>
                                        <td><?= htmlspecialchars($c['party']) ?></td>
                                        <td><?= number_format($c['total'])    ?></td>
                                        <td><?= $pct ?>%</td>
                                        <td><?= $rank++ ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>

                            <?php if (!empty($lData)): ?>
                                <div class="chart-container"><canvas id="<?= $lcid ?>"></canvas></div>
                            <?php else: ?>
                                <div class="no-data-chart">No votes yet</div>
                            <?php endif; ?>
                            <br>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <hr>

            <!-- Votes Breakdown by Department -->
            <h3 class="center">Votes Breakdown by Department</h3>
            <div class="overflow-table">
                <table>
                    <tr>
                        <th rowspan="2">Position</th>
                        <th rowspan="2">Candidate</th>
                        <th rowspan="2">Party</th>
                        <th colspan="<?= count($collegeDeptNames) ?>">Votes Per Department</th>
                        <th rowspan="2">Total</th>
                        <th rowspan="2">Rank</th>
                    </tr>
                    <tr>
                        <?php foreach ($collegeDeptNames as $dn): ?>
                            <th style="font-size:11px;"><?= htmlspecialchars($dn) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <?php foreach ($local_positions as $pos):
                        $cands = array_values($localByPosition[$pos] ?? []);
                        if (empty($cands)) continue;
                        usort($cands, fn($a, $b) => $b['total'] <=> $a['total']);
                        $topVotes = $cands[0]['total'];
                        $rank = 1;
                        $first = true;
                    ?>
                        <?php foreach ($cands as $c): ?>
                            <tr <?= ($c['total'] === $topVotes && $topVotes > 0) ? 'class="winner-row"' : '' ?>>
                                <?php if ($first): ?>
                                    <td rowspan="<?= count($cands) ?>"><?= htmlspecialchars($pos) ?></td>
                                    <?php $first = false; ?>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($c['name'])  ?></td>
                                <td><?= htmlspecialchars($c['party']) ?></td>
                                <?php
                                $rowTotal = 0;
                                foreach ($collegeDeptNames as $dn):
                                    $v = $c['byDept'][$dn] ?? 0;
                                    $rowTotal += $v;
                                    echo '<td>' . number_format($v) . '</td>';
                                endforeach;
                                ?>
                                <td><?= number_format($rowTotal) ?></td>
                                <td><?= $rank++ ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </table>
            </div>
        </div><!-- end college page -->

    <?php endforeach; // end local colleges loop 
    ?>


    <!-- ═══════════════════════════════════════════════
     LAST PAGE — Precinct Summary & Signatures
═══════════════════════════════════════════════ -->
    <hr>
    <div class="page">
        <h2 class="center">Precinct Summary</h2>
        <table>
            <tr>
                <th>College</th>
                <th>Main Campus Precincts</th>
                <th>WMSU ESU Precincts</th>
                <th>Total</th>
            </tr>
            <?php if (!empty($precinctSummary)): ?>
                <?php foreach ($precinctSummary as $cname => $ps): ?>
                    <tr>
                        <td><?= htmlspecialchars($cname) ?></td>
                        <td><?= $ps['main'] ?></td>
                        <td><?= $ps['esu']  ?></td>
                        <td><?= $ps['main'] + $ps['esu'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No precinct data available.</td>
                </tr>
            <?php endif; ?>
        </table>
        <hr>

        <h2 class="center">Confirmation</h2>
        <p class="center">
            We, the undersigned, confirm the accuracy of the election results presented in this report for
            <strong><?= htmlspecialchars($election_name) ?></strong>, held on <?= formatDate($electionDate) ?>.
        </p>

        <div style="margin-top:30px;text-align:center;">
            <div class="signature-box">
                <p>Electoral Commissioner</p>
                <div class="signature-line">Name and Signature</div>
                <div class="signature-line">Date</div>
            </div>
            <div class="signature-box">
                <p>Director of Student Affairs</p>
                <div class="signature-line">Name and Signature</div>
                <div class="signature-line">Date</div>
            </div>
            <div class="signature-box">
                <p>Head of Electoral Board</p>
                <div class="signature-line">Name and Signature</div>
                <div class="signature-line">Date</div>
            </div>
        </div>
        <div style="margin-top:20px;text-align:center;">
            <div class="signature-box">
                <p>President of Western Mindanao State University</p>
                <div class="signature-line">Name and Signature</div>
                <div class="signature-line">Date</div>
            </div>
        </div>
    </div>

    <button class="print-button" onclick="window.print()" title="Print Results">🖨️</button>

    <!-- ═══════════════════════════════════════════════
     ALL CHARTS — single block AFTER all canvases are in the DOM
═══════════════════════════════════════════════ -->
    <script>
        (function() {
            var COLORS = <?= json_encode($CHART_COLORS) ?>;

            function makeChart(id, labels, data) {
                var el = document.getElementById(id);
                if (!el) return;
                var hasData = data.some(function(v) {
                    return v > 0;
                });
                if (!hasData) {
                    el.parentNode.style.display = 'none';
                    return;
                }
                new Chart(el, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: COLORS.slice(0, data.length)
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // — Overall participation —
            makeChart(
                'participationChart',
                ['Voted (<?= $votedPercentage ?>%)', 'Did Not Vote (<?= $notVotedPercentage ?>%)'],
                [<?= $votedCount ?>, <?= $notVotedCount ?>]
            );

            // — Central position charts —
            <?php foreach ($central_positions as $position):
                $cands = array_values($candidatesByLevel['Central'][$position] ?? []);
                usort($cands, fn($a, $b) => $b['total'] <=> $a['total']);
                $labels = $data = [];
                foreach ($cands as $c) {
                    if ($c['total'] > 0) {
                        $lbl = (strlen($c['name']) > 18 ? substr($c['name'], 0, 18) . '…' : $c['name']) . ' (' . $c['party'] . ')';
                        $labels[] = $lbl;
                        $data[]   = $c['total'];
                    }
                }
                if (empty($data)) continue;
                $cid = safeChartId('central', $position, (string)$voting_period_id);
            ?>
                makeChart(<?= json_encode($cid) ?>, <?= json_encode($labels) ?>, <?= json_encode($data) ?>);
            <?php endforeach; ?>

            // — College participation + local position charts —
            <?php foreach ($candidatesByLevel['Local'] as $collegeName => $localByPosition2):
                // College participation stats
                $collegeIdNum3 = 0;
                foreach ($collegeMap as $k => $v) {
                    if ($v['name'] === $collegeName) {
                        $collegeIdNum3 = $k;
                        break;
                    }
                }
                $stmt3 = $pdo->prepare("
            SELECT COUNT(pv.id) AS total,
                   SUM(CASE WHEN pv.status='voted' THEN 1 ELSE 0 END) AS voted
            FROM precinct_voters pv
            JOIN precincts pr ON pr.id = pv.precinct
            WHERE pr.college = ? AND pr.election = ?
        ");
                $stmt3->execute([$collegeIdNum3, $election_id]);
                $cs3 = $stmt3->fetch(PDO::FETCH_ASSOC);
                $ct3 = (int)($cs3['total'] ?? 0);
                $cv3 = (int)($cs3['voted'] ?? 0);
                $cnv3 = $ct3 - $cv3;
                $cpct3 = $ct3 > 0 ? round(($cv3 / $ct3) * 100, 2) : 0;
                $cnpct3 = round(100 - $cpct3, 2);
                $cpChartId = safeChartId('participation', $collegeName, (string)$voting_period_id);
            ?>
                <?php if ($ct3 > 0): ?>
                    makeChart(
                        <?= json_encode($cpChartId) ?>,
                        ['Voted (<?= $cpct3 ?>%)', 'Did Not Vote (<?= $cnpct3 ?>%)'],
                        [<?= $cv3 ?>, <?= $cnv3 ?>]
                    );
                <?php endif; ?>

                <?php foreach ($local_positions as $pos):
                    $cands2 = array_values($localByPosition2[$pos] ?? []);
                    if (empty($cands2)) continue;
                    usort($cands2, fn($a, $b) => $b['total'] <=> $a['total']);
                    $labels2 = $data2 = [];
                    foreach ($cands2 as $c2) {
                        if ($c2['total'] > 0) {
                            $lbl2 = (strlen($c2['name']) > 18 ? substr($c2['name'], 0, 18) . '…' : $c2['name']) . ' (' . $c2['party'] . ')';
                            $labels2[] = $lbl2;
                            $data2[]   = $c2['total'];
                        }
                    }
                    if (empty($data2)) continue;
                    $lcid2 = safeChartId('local', $pos, $collegeName, (string)$voting_period_id);
                ?>
                    makeChart(<?= json_encode($lcid2) ?>, <?= json_encode($labels2) ?>, <?= json_encode($data2) ?>);
                <?php endforeach; ?>

            <?php endforeach; // end Local loop 
            ?>

        })(); // self-invoking — no DOMContentLoaded needed since script is at end of body
    </script>

</body>

</html>