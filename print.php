<?php
include('includes/conn.php');

$voting_period_id = $_GET['voting_period_id'] ?? null;
$sort_order = $_POST['sort_order'] ?? 'highest';

if (!$voting_period_id) {
    echo "<p>No voting period specified.</p>";
    exit();
}

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
            vtr.college AS candidate_college,
            vtr.wmsu_campus,
            vtr.external_campus
        FROM candidates c
        JOIN candidate_responses cr_name ON c.id = cr_name.candidate_id AND cr_name.field_id = (SELECT id FROM form_fields WHERE field_name = 'full_name' LIMIT 1)
        JOIN candidate_responses cr_party ON c.id = cr_party.candidate_id AND cr_party.field_id = (SELECT id FROM form_fields WHERE field_name = 'party' LIMIT 1)
        JOIN candidate_responses cr_pos ON c.id = cr_pos.candidate_id AND cr_pos.field_id = (SELECT id FROM form_fields WHERE field_name = 'position' LIMIT 1)
        JOIN positions p ON cr_pos.value = p.name
        JOIN candidate_responses cr_student ON c.id = cr_student.candidate_id AND cr_student.field_id = (SELECT id FROM form_fields WHERE field_name = 'student_id' LIMIT 1)
        LEFT JOIN voters vtr ON cr_student.value = vtr.student_id
        WHERE c.form_id = ? AND c.status = 'accepted'
        GROUP BY c.id, cr_name.value, cr_party.value, cr_pos.value, p.level, vtr.college, vtr.wmsu_campus, vtr.external_campus
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
    'Local' => ['main_campus' => [], 'esu' => []],
    'External' => []
];
$totalVotesByPosition = ['Central' => [], 'Local' => [], 'External' => []];
$leaderboard = ['Central' => [], 'Local' => [], 'External' => []];
$highestVotes = ['Central' => [], 'Local' => [], 'External' => []];

foreach ($candidates as $candidate) {
    $candidateId = $candidate['id'];
    $level = $candidate['level'] === 'Central' ? 'Central' : 'Local';
    $candidateCollege = $candidate['candidate_college'] ?: 'Not Specified';
    $candidateCampus = $candidate['wmsu_campus'] ? 'Main Campus' : ($candidate['external_campus'] ? 'WMSU ESU' : 'Not Specified');

    // Query 8: Fetch Votes with Precinct Type
    $sql = "
    SELECT 
        v.student_id,
        p.type AS precinct_type, 
        vtr.college, 
        vtr.department,
        vtr.wmsu_campus,
        vtr.external_campus
    FROM votes v
    JOIN precinct_voters pv ON v.student_id = pv.student_id
    JOIN precincts p ON pv.precinct = p.name
    JOIN voters vtr ON v.student_id = vtr.student_id
    WHERE v.candidate_id = ? 
    AND v.voting_period_id = ?
    AND (
        (p.type = 'Main Campus' AND (p.college_external IS NULL OR p.college_external = ''))
        OR
        (p.type = 'WMSU ESU' AND p.college_external IS NOT NULL AND p.college_external != '')
    )
";
    $params = [$candidateId, $voting_period_id];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize vote counters
    $votesByCollege = array_fill_keys($colleges, 0);
    $votesByDepartment = array_fill_keys($all_departments, 0);
    $totalVotesMainCampus = 0;
    $totalVotesESU = 0;

    // Process each vote
    foreach ($votes as $vote) {
        $precinctType = $vote['precinct_type'];
        $voteCollege = $vote['college'] ?: 'Not Specified';
        $voteDepartment = $vote['department'] ?: 'Not Specified';
        $isMainCampus = $vote['wmsu_campus'];
        $isESU = $vote['external_campus'];

        if ($level === 'Central') {
            if (!isset($votesByCollege[$voteCollege])) {
                $votesByCollege[$voteCollege] = 0;
            }
            $votesByCollege[$voteCollege]++;

            if ($precinctType === 'Main Campus') {
                $totalVotesMainCampus++;
            } elseif ($precinctType === 'WMSU ESU') {
                $totalVotesESU++;
            }
        } else {
            if (!isset($votesByDepartment[$voteDepartment])) {
                $votesByDepartment[$voteDepartment] = 0;
            }
            $votesByDepartment[$voteDepartment]++;

            if ($precinctType === 'Main Campus') {
                $totalVotesMainCampus++;
            } elseif ($precinctType === 'WMSU ESU') {
                $totalVotesESU++;
            }
        }
    }

    $totalVotes = $totalVotesMainCampus + $totalVotesESU;
    if ($totalVotes <= 0) {
        $totalVotes = 1;
    }

    $candidateDataMainCampus = [
        'name' => $candidate['name'],
        'party' => $candidate['party'],
        'votes' => ($level === 'Central') ? $votesByCollege : $votesByDepartment,
        'total' => $totalVotesMainCampus,
        'position' => $candidate['position'],
        'college' => $candidateCollege,
        'campus' => 'Main Campus'
    ];

    $candidateDataESU = [
        'name' => $candidate['name'],
        'party' => $candidate['party'],
        'votes' => ($level === 'Central') ? $votesByCollege : $votesByDepartment,
        'total' => $totalVotesESU,
        'position' => $candidate['position'],
        'college' => $candidateCollege,
        'campus' => 'WMSU ESU'
    ];

    $key = $candidate['position'] . '|' . $candidate['party'];

    // Central positions
    if ($level === 'Central') {
        if ($totalVotesMainCampus >= 0) {
            if (!isset($highestVotes['Central'][$key]) || $totalVotesMainCampus > $highestVotes['Central'][$key]['total']) {
                $highestVotes['Central'][$key] = $candidateDataMainCampus;
            }
            $candidatesByLevel['Central'][$candidate['position']][$candidate['name']] = $candidateDataMainCampus;
            $leaderboard['Central'][] = array_merge($candidateDataMainCampus, ['total' => $totalVotesMainCampus]);
            $totalVotesByPosition['Central'][$candidate['position']] = ($totalVotesByPosition['Central'][$candidate['position']] ?? 0) + $totalVotesMainCampus;
        }

        if ($totalVotesESU > 0) {
            if (!isset($highestVotes['External'][$key]) || $totalVotesESU > $highestVotes['External'][$key]['total']) {
                $highestVotes['External'][$key] = $candidateDataESU;
            }
            $candidatesByLevel['External'][$candidate['position']][$candidate['name']] = $candidateDataESU;
            $leaderboard['External'][] = array_merge($candidateDataESU, ['total' => $totalVotesESU]);
            $totalVotesByPosition['External'][$candidate['position']] = ($totalVotesByPosition['External'][$candidate['position']] ?? 0) + $totalVotesESU;
        }
    }

    // Local positions
    if ($level === 'Local') {
        if ($totalVotesMainCampus >= 0) {
            if (!isset($highestVotes['Local'][$key]) || $totalVotesMainCampus > $highestVotes['Local'][$key]['total']) {
                $highestVotes['Local'][$key] = $candidateDataMainCampus;
            }
            $candidatesByLevel['Local']['main_campus'][$candidate['position']][$candidate['name']] = $candidateDataMainCampus;
            $leaderboard['Local'][] = array_merge($candidateDataMainCampus, ['total' => $totalVotesMainCampus]);
            $totalVotesByPosition['Local'][$candidate['position']] = ($totalVotesByPosition['Local'][$candidate['position']] ?? 0) + $totalVotesMainCampus;
        }

        if ($totalVotesESU >= 0) {
            if (!isset($highestVotes['Local'][$key]) || $totalVotesESU > $highestVotes['Local'][$key]['total']) {
                $highestVotes['Local'][$key] = $candidateDataESU;
            }
            $candidatesByLevel['Local']['esu'][$candidate['position']][$candidate['name']] = $candidateDataESU;
            $leaderboard['Local'][] = array_merge($candidateDataESU, ['total' => $totalVotesESU]);
            $totalVotesByPosition['Local'][$candidate['position']] = ($totalVotesByPosition['Local'][$candidate['position']] ?? 0) + $totalVotesESU;
        }
    }
}

// Sort candidates
foreach (['Central', 'External'] as $type) {
    foreach ($candidatesByLevel[$type] as $position => &$candidates) {
        uasort($candidates, fn($a, $b) => $sort_order === 'highest' ? $b['total'] - $a['total'] : $a['total'] - $b['total']);
    }
}
foreach (['main_campus', 'esu'] as $type) {
    foreach ($candidatesByLevel['Local'][$type] as $position => &$candidates) {
        uasort($candidates, fn($a, $b) => $sort_order === 'highest' ? $b['total'] - $a['total'] : $a['total'] - $b['total']);
    }
}

usort($leaderboard['Central'], fn($a, $b) => $sort_order === 'highest' ? $b['total'] - $a['total'] : $a['total'] - $b['total']);
usort($leaderboard['Local'], fn($a, $b) => $sort_order === 'highest' ? $b['total'] - $a['total'] : $a['total'] - $b['total']);
usort($leaderboard['External'], fn($a, $b) => $sort_order === 'highest' ? $b['total'] - $a['total'] : $a['total'] - $b['total']);

// Helper functions
function mapCollegeName($college)
{
    $mapping = [
        'College of Law' => 'CL',
        'College of Agriculture' => 'CA',
        'College of Liberal Arts' => 'CLA',
        'College of Architecture' => 'COA',
        'College of Nursing' => 'CN',
        'College of Asian & Islamic Studies' => 'CAIS',
        'College of Computing Studies' => 'CCS',
        'College of Forestry & Environmental Studies' => 'CFES',
        'College of Criminal Justice Education' => 'CCJE',
        'College of Home Economics' => 'CHE',
        'College of Engineering' => 'COE',
        'College of Medicine' => 'COM',
        'College of Public Administration & Development Studies' => 'CPADS',
        'College of Sports Science & Physical Education' => 'CSSPE',
        'College of Science and Mathematics' => 'CSM',
        'College of Social Work & Community Development' => 'CSWCD',
        'College of Teacher Education' => 'CTE',
    ];
    return $mapping[$college] ?? $college;
}

function formatDate($dateStr)
{
    return date('F j, Y', strtotime($dateStr));
}

// Get current date and election details for header
$currentDate = date('F j, Y');
$electionDate = $pdo->query("SELECT start_period FROM voting_periods WHERE id = $voting_period_id")->fetchColumn();
$semester = $pdo->query("SELECT semester FROM voting_periods WHERE id = $voting_period_id")->fetchColumn();
$schoolYear = $pdo->query("SELECT YEAR(start_period) FROM voting_periods WHERE id = $voting_period_id")->fetchColumn();


// Get voter statistics
$totalVoters = $pdo->query("SELECT COUNT(*) FROM voters")->fetchColumn();
$votedCount = $pdo->query("SELECT COUNT(DISTINCT student_id) FROM votes WHERE voting_period_id = $voting_period_id")->fetchColumn();
$notVotedCount = $totalVoters - $votedCount;
$votedPercentage = round(($votedCount / $totalVoters) * 100, 2);
$notVotedPercentage = 100 - $votedPercentage;

// Generate HTML report
ob_start();
?>

<script>
    function safeCreateChart(chartId, type, labels, data, colors) {
        try {
            const ctx = document.getElementById(chartId);
            if (!ctx) {
                console.error('Canvas element not found:', chartId);
                document.getElementById(chartId + '-no-data').style.display = 'block';
                return null;
            }

            // More thorough data validation
            if (!data || data.length === 0 || data.every(val => val <= 0)) {
                document.getElementById(chartId + '-no-data').style.display = 'block';
                ctx.style.display = 'none'; // Hide the canvas if no data
                return null;
            }

            // Hide no-data message if we have data
            const noDataEl = document.getElementById(chartId + '-no-data');
            if (noDataEl) noDataEl.style.display = 'none';

            // Destroy previous chart if it exists
            if (ctx.chart) {
                ctx.chart.destroy();
            }

            // Create new chart
            ctx.style.display = 'block';
            const chart = new Chart(ctx, {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12
                            }
                        }
                    }
                }
            });

            // Store chart reference on canvas element
            ctx.chart = chart;
            return chart;
        } catch (e) {
            console.error('Error creating chart:', chartId, e);
            const noDataEl = document.getElementById(chartId + '-no-data');
            if (noDataEl) noDataEl.style.display = 'block';
            return null;
        }
    }
</script>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - <?= htmlspecialchars($election_name) ?></title>
    <link rel="shortcut icon" href="images/favicon.png" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
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

        .logo {
            height: 100px;
        }

        .page {
            page-break-after: always;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        .chart-container {
            width: 300px;
            height: 300px;
            margin: 0 auto;
            border: 1px solid black;
            position: relative;
        }

        .chart-no-data {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #999;
            font-style: italic;
        }

        .two-columns {
            display: flex;
            justify-content: space-between;
        }

        .column {
            width: 48%;
        }

        .signature-box {
            display: inline-block;
            width: 300px;
            margin: 10px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 5px;
            padding-top: 5px;
        }

        .center {
            text-align: center;
        }

        h2,
        h3 {
            color: #333;
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .print-button:hover {
            background-color: grey;
            transform: scale(1.1);
        }

        .print-button:active {
            transform: scale(0.95);
        }

        @media print {
            .print-button {
                display: none;
            }

            body {
                padding: 0;
                margin: 0;
            }

            .page {
                page-break-after: always;
                margin: 0;
                padding: 20px;
            }

            table {
                page-break-inside: avoid;
            }

            .chart-container {
                page-break-inside: avoid;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <!-- Page 1 -->
    <div class="page">
        <div class="header">
            <img src="images/wmsu-logo.png" alt="Logo 1" style="height: 100px; width:auto;">
            <div class="header-center">
                <h1><?= htmlspecialchars($election_name) ?> Elections</h1>
                <h2>OFFICIAL ELECTIONS</h2>
                <p><?= htmlspecialchars($semester) ?>, AY <?= htmlspecialchars($schoolYear) ?> - <?= $schoolYear + 1 ?></p>
                <p>Election Held: <?= formatDate($electionDate) ?></p>
                <p>Report Generated: <?= $currentDate ?></p>
            </div>
            <img src="images/osa_logo.png" alt="Logo 1" style="height: 100px; width:auto;">
        </div>

        <hr>
        <!-- Voter Distribution -->
        <br>
        <h3 style="text-align:center">Voter Participation</h3>
        <br>
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
                        <td><?= $totalVoters ?></td>
                        <td>100%</td>
                    </tr>
                    <tr>
                        <td>Voted</td>
                        <td><?= $votedCount ?></td>
                        <td><?= $votedPercentage ?>%</td>
                    </tr>
                    <tr>
                        <td>Did Not Vote</td>
                        <td><?= $notVotedCount ?></td>
                        <td><?= $notVotedPercentage ?>%</td>
                    </tr>
                </table>
            </div>
            <div class="column">
                <div class="chart-container">
                    <canvas id="participationChart"></canvas>
                    <div id="participationChart-no-data" class="chart-no-data" style="display: none;">No data available</div>
                </div>
            </div>
        </div>
        <br>
        <hr>
        <!-- Central Level Officials -->
        <br>
        <h3 style="text-align:center">Central Level Election Results</h3>
        <br>
        <table>
            <tr>
                <th>Position</th>
                <th>Elected Candidate(s)</th>
                <th>Party</th>
                <th>Total Votes</th>
                <th>Status</th>
            </tr>
            <?php foreach ($central_positions as $position): ?>
                <?php
                $candidates = $candidatesByLevel['Central'][$position] ?? [];

                if (!empty($candidates)) {
                    // Sort candidates by total votes descending
                    usort($candidates, fn($a, $b) => $b['total'] <=> $a['total']);

                    // Get top vote count
                    $topVotes = $candidates[0]['total'];

                    // Skip if topVotes is 0 (optional, remove this if you want to show even those with 0 votes)
                    if ($topVotes == 0) continue;

                    // Get all candidates with the top vote count
                    $topCandidates = array_filter($candidates, fn($c) => $c['total'] === $topVotes);

                    // Determine if it's a tie
                    $isTie = count($topCandidates) > 1;

                    // Flag to control rowspan
                    $firstRow = true;
                ?>

                    <?php foreach ($topCandidates as $topCandidate): ?>
                        <tr>
                            <?php if ($firstRow): ?>
                                <td rowspan="<?= count($topCandidates) ?>"><?= htmlspecialchars($position) ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($topCandidate['name']) ?></td>
                            <td><?= htmlspecialchars($topCandidate['party']) ?></td>
                            <td><?= htmlspecialchars($topCandidate['total']) ?></td>
                            <td><?= $isTie ? 'Tie' : 'Winner' ?></td>
                        </tr>
                        <?php $firstRow = false; ?>
                    <?php endforeach; ?>
                <?php } ?>
            <?php endforeach; ?>
        </table>

        <br>

        <!-- Central Level Detailed Results -->
     <div class="two-columns">
    <?php
    $half = ceil(count($central_positions) / 2);
    foreach (array_chunk($central_positions, $half) as $column): ?>
        <div class="column">
            <?php foreach ($column as $position):
                // Check both Central and External candidates
                $centralCandidates = $candidatesByLevel['Central'][$position] ?? [];
                $externalCandidates = $candidatesByLevel['External'][$position] ?? [];
                $allCandidates = array_merge($centralCandidates, $externalCandidates);
                
                if (empty($allCandidates)) continue;
                
                // Calculate total votes from both precincts
                $centralVotes = $totalVotesByPosition['Central'][$position] ?? 0;
                $externalVotes = $totalVotesByPosition['External'][$position] ?? 0;
                $positionVotes = $centralVotes + $externalVotes;
            ?>
                <table>
                    <tr>
                        <th colspan="5"><?= htmlspecialchars($position) ?></th>
                    </tr>
                    <tr>
                        <th>Candidate</th>
                        <th>Party</th>
                        <th colspan="2">Votes Statistics</th>
                        <th>Rank</th>
                    </tr>
                    <?php
                    $rank = 1;
                    foreach ($allCandidates as $candidate):
                        $percentage = ($positionVotes > 0)
                            ? round(($candidate['total'] / $positionVotes) * 100, 2)
                            : 0;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($candidate['name']) ?></td>
                            <td><?= htmlspecialchars($candidate['party']) ?></td>
                            <td><?= number_format(max(0, $candidate['total'])) ?></td>
                            <td><?= $percentage ?>%</td>
                            <td><?= $rank++ ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <br>

                <?php
                $chartLabels = [];
                $chartData = [];
                $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];
                $hasVotes = false;

                foreach ($allCandidates as $candidate) {
                    if ($candidate['total'] > 0) {
                        $hasVotes = true;
                        $name = strlen($candidate['name']) > 15 ? substr($candidate['name'], 0, 15) . '...' : $candidate['name'];
                        $chartLabels[] = $name . ' (' . $candidate['party'] . ')';
                        $chartData[] = $candidate['total'];
                    }
                }

                $chartId = 'chart_' . preg_replace('/[^a-zA-Z0-9]/', '_', $position . '_Combined_' . $voting_period_id);
                ?>

                <div class="chart-container">
                    <?php if ($hasVotes): ?>
                        <canvas id="<?= $chartId ?>"></canvas>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                safeCreateChart(
                                    '<?= $chartId ?>',
                                    'pie',
                                    <?= json_encode($chartLabels) ?>,
                                    <?= json_encode($chartData) ?>,
                                    <?= json_encode(array_slice($colors, 0, count($chartData))) ?>
                                );
                            });
                        </script>
                    <?php else: ?>
                        <p style="text-align: center; padding: 5px; border: 1px solid black;">
                            No chart since there are no votes, yet.
                        </p>
                    <?php endif; ?>
                </div>
                <br>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
        <hr>
        <!-- Central Level Votes Breakdown by College -->
        <h3 style="text-align: center;">Central Level Votes Breakdown by College</h3>
        <table>
            <tr>
                <th rowspan="2">Position</th>
                <th rowspan="2">Candidate Name</th>
                <th colspan="<?= count($colleges) ?>">Number of Votes Per College</th>
                <th rowspan="2">Total</th>
                <th rowspan="2">Rank</th>
            </tr>
            <tr>
                <?php foreach ($colleges as $college): ?>
                    <th><?= mapCollegeName($college) ?></th>
                <?php endforeach; ?>
            </tr>
            <?php
            $prevPosition = null;
            foreach ($central_positions as $position):
                if (!isset($candidatesByLevel['Central'][$position])) continue;

                $positionRowCount = count($candidatesByLevel['Central'][$position]);
                $rank = 1;

                foreach ($candidatesByLevel['Central'][$position] as $candidate):
                    // Recalculate total to ensure accuracy
                    $calculatedTotal = 0;
                    foreach ($colleges as $college) {
                        $calculatedTotal += $candidate['votes'][$college] ?? 0;
                    }
            ?>
                    <tr>
                        <?php if ($position !== $prevPosition): ?>
                            <td rowspan="<?= $positionRowCount ?>"><?= $position ?></td>
                        <?php endif; ?>
                        <td><?= $candidate['name'] ?></td>
                        <?php foreach ($colleges as $college): ?>
                            <td><?= $candidate['votes'][$college] ?? 0 ?></td>
                        <?php endforeach; ?>
                        <td><?= $calculatedTotal ?></td>
                        <td><?= $rank++ ?></td>
                    </tr>
            <?php
                    $prevPosition = $position;
                endforeach;
            endforeach;
            ?>
        </table>

        <!-- Pages 2 to N - Local Level (Main Campus and WMSU ESU) -->
        <?php
        $pageCount = 2;
        foreach ($colleges as $college):
            // Get voter statistics for this college (combined main campus and ESU)
            $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_voters,
            COUNT(DISTINCT v.student_id) as voted_count
        FROM voters vr
        LEFT JOIN votes v ON vr.student_id = v.student_id AND v.voting_period_id = ?
        WHERE vr.college = ?
    ");
            $stmt->execute([$voting_period_id, $college]);
            $collegeStats = $stmt->fetch(PDO::FETCH_ASSOC);

            $collegeTotalVoters = $collegeStats['total_voters'] ?? 0;
            $collegeVotedCount = $collegeStats['voted_count'] ?? 0;
            $collegeNotVotedCount = $collegeTotalVoters - $collegeVotedCount;
            $collegeVotedPercentage = $collegeTotalVoters > 0 ? round(($collegeVotedCount / $collegeTotalVoters) * 100, 2) : 0;

            // Get precinct count for this college
            $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT CASE WHEN p.type = 'Main Campus' THEN p.name END) as main_campus_precincts,
            COUNT(DISTINCT CASE WHEN p.type = 'WMSU ESU' THEN p.name END) as esu_precincts
        FROM precincts p
        JOIN precinct_voters pv ON p.name = pv.precinct
        JOIN voters v ON pv.student_id = v.student_id
        WHERE p.college = ?
    ");
            $stmt->execute([$college]);
            $precinctStats = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
            <hr>
            <div class="page">
                <h2 style="text-align:center"><?= htmlspecialchars($college) ?> - Local Election Results</h2>

                <!-- College Voter Statistics -->
                <h3 style="text-align: center;">Voter Participation</h3>
                <br>
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
                                <td><?= $collegeTotalVoters ?></td>
                                <td>100%</td>
                            </tr>
                            <tr>
                                <td>Voted</td>
                                <td><?= $collegeVotedCount ?></td>
                                <td><?= $collegeVotedPercentage ?>%</td>
                            </tr>
                            <tr>
                                <td>Did Not Vote</td>
                                <td><?= $collegeNotVotedCount ?></td>
                                <td><?= 100 - $collegeVotedPercentage ?>%</td>
                            </tr>
                        </table>
                        <table>
                            <tr>
                                <th>Precinct Types</th>
                                <th>Count</th>
                            </tr>
                            <tr>
                                <td>Main Campus Precincts</td>
                                <td><?= $precinctStats['main_campus_precincts'] ?? 0 ?></td>
                            </tr>
                            <tr>
                                <td>WMSU ESU Precincts</td>
                                <td><?= $precinctStats['esu_precincts'] ?? 0 ?></td>
                            </tr>
                            <tr>
                                <th><b>TOTAL:</b></th>
                                <td><?= ($precinctStats['main_campus_precincts'] ?? 0) + ($precinctStats['esu_precincts'] ?? 0) ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="column">
                        <div class="chart-container">
                            <canvas id="participationChart_<?= preg_replace('/[^a-zA-Z0-9]/', '_', $college) ?>"></canvas>
                            <div id="participationChart_<?= preg_replace('/[^a-zA-Z0-9]/', '_', $college) ?>-no-data" class="chart-no-data" style="display: none;">No data available</div>
                        </div>
                    </div>
                </div>
                <br>

                <!-- Combined Local Election Results (all precinct types) -->
                <h3 style="text-align:center">Elected Officials</h3>
                <table>
                    <tr>
                        <th>Position</th>
                        <th>Elected Candidate</th>
                        <th>Party</th>
                        <th>Total Votes</th>
                    </tr>
                    <?php
                    $electedOfficials = [];
                    foreach ($local_positions as $position):
                        $allCandidates = [];
                        foreach (['main_campus', 'esu'] as $precinctType) {
                            if (isset($candidatesByLevel['Local'][$precinctType][$position])) {
                                foreach ($candidatesByLevel['Local'][$precinctType][$position] as $candidate) {
                                    if ($candidate['college'] === $college) {
                                        $allCandidates[] = $candidate;
                                    }
                                }
                            }
                        }

                        if (!empty($allCandidates)) {
                            usort($allCandidates, fn($a, $b) => $b['total'] - $a['total']);
                            $topCandidate = $allCandidates[0];
                            $displayVotes = max(1, $topCandidate['total']);
                    ?>
                            <tr>
                                <td><?= htmlspecialchars($position) ?></td>
                                <td><?= htmlspecialchars($topCandidate['name']) ?></td>
                                <td><?= htmlspecialchars($topCandidate['party']) ?></td>
                                <td><?= $displayVotes ?></td>
                            </tr>
                        <?php
                            $electedOfficials[$position] = $topCandidate;
                        } else {
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($position) ?></td>
                                <td colspan="3">No candidates</td>
                            </tr>
                    <?php
                        }
                    endforeach;
                    ?>
                </table>

                <hr>

                <br>
                <h3 style="text-align:center">Detailed Results by Position</h3>
                <br>
                <div class="two-columns">
                    <?php
                    $half = ceil(count($local_positions) / 2);
                    foreach (array_chunk($local_positions, $half) as $column): ?>
                        <div class="column">
                            <?php foreach ($column as $position):
                                $allCandidates = [];
                                $positionVotes = $totalVotesByPosition['Local'][$position] ?? 1;

                                foreach (['main_campus', 'esu'] as $precinctType) {
                                    if (isset($candidatesByLevel['Local'][$precinctType][$position])) {
                                        foreach ($candidatesByLevel['Local'][$precinctType][$position] as $candidate) {
                                            if ($candidate['college'] === $college) {
                                                $key = $candidate['name'] . '|' . $candidate['position'];
                                                if (!isset($allCandidates[$key])) {
                                                    $allCandidates[$key] = $candidate;
                                                } else {
                                                    $allCandidates[$key]['total'] += $candidate['total'];
                                                    foreach ($candidate['votes'] as $dept => $votes) {
                                                        $allCandidates[$key]['votes'][$dept] = ($allCandidates[$key]['votes'][$dept] ?? 0) + $votes;
                                                    }
                                                }
                                            }
                                        }
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
                                        <th colspan="2">Votes Statistics</th>
                                        <th>Rank</th>
                                    </tr>
                                    <?php if (empty($allCandidates)): ?>
                                        <tr>
                                            <td colspan="5">No candidates</td>
                                        </tr>
                                        <?php else:
                                        $rank = 1;
                                        foreach ($allCandidates as $candidate):
                                            // Safely calculate percentage (handles division by zero)
                                            $percentage = ($positionVotes > 0)
                                                ? round(($candidate['total'] / $positionVotes) * 100, 2)
                                                : 0; // Default to 0% if no votes exist
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($candidate['name']) ?></td>
                                                <td><?= htmlspecialchars($candidate['party']) ?></td>
                                                <td><?= number_format(max(0, $candidate['total'])) ?></td>
                                                <td><?= $percentage ?>%</td>
                                                <td><?= $rank++ ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </table>
                                <br>

                                <?php
                                $chartLabels = [];
                                $chartData = [];
                                $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];

                                $hasVotes = false;
                                foreach ($allCandidates as $candidate) {
                                    if ($candidate['total'] > 0) {
                                        $hasVotes = true;

                                        $name = strlen($candidate['name']) > 15 ? substr($candidate['name'], 0, 15) . '...' : $candidate['name'];
                                        $chartLabels[] = $name . ' (' . $candidate['party'] . ')';
                                        $chartData[] = $candidate['total'];
                                    }
                                }

                                if ($hasVotes):
                                    $chartId = 'chart_' . preg_replace('/[^a-zA-Z0-9]/', '_', $position . '_' . $college . '_' . $voting_period_id);
                                ?>
                                    <div class="chart-container">
                                        <canvas id="<?= $chartId ?>"></canvas>
                                    </div>
                                    <br>

                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            safeCreateChart(
                                                '<?= $chartId ?>',
                                                'pie',
                                                <?= json_encode($chartLabels) ?>,
                                                <?= json_encode($chartData) ?>,
                                                <?= json_encode(array_slice($colors, 0, count($chartData))) ?>
                                            );
                                        });
                                    </script>
                                <?php else: ?>
                                    <p style="text-align: center; padding: 5px; border: 1px solid black;">No chart since there are no votes, yet.</p>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <br>
                <hr>
                <br>

                <!-- Comprehensive Votes Breakdown by Department -->
                <h3 style="text-align:center">Votes Breakdown by Department</h3>
                <br>
                <table>
                    <tr>
                        <th rowspan="2">Position</th>
                        <th rowspan="2">Candidate Name</th>
                        <th rowspan="2">Party</th>
                        <th colspan="<?= count($collegeDepartments = array_keys(array_filter($dept_to_college, fn($c) => $c === $college))) ?>">Number of Votes Per Department</th>
                        <th rowspan="2">Total Votes</th>
                        <th rowspan="2">Rank</th>
                    </tr>
                    <tr>
                        <?php foreach ($collegeDepartments as $dept): ?>
                            <th><?= htmlspecialchars($dept) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <?php
                    $prevPosition = null;
                    foreach ($local_positions as $position):
                        $candidateMap = [];
                        foreach (['main_campus', 'esu'] as $precinctType) {
                            $list = $candidatesByLevel['Local'][$precinctType][$position] ?? [];
                            foreach ($list as $candidate) {
                                if ($candidate['college'] !== $college) continue;
                                $key = $candidate['name'] . '|' . $position;
                                if (!isset($candidateMap[$key])) {
                                    $candidateMap[$key] = $candidate;
                                    $candidateMap[$key]['votes'] = array_fill_keys($collegeDepartments, 0);
                                }
                                $candidateMap[$key]['total'] += $candidate['total'];
                                foreach ($candidate['votes'] as $dept => $votes) {
                                    if (in_array($dept, $collegeDepartments)) {
                                        $candidateMap[$key]['votes'][$dept] += $votes;
                                    }
                                }
                            }
                        }

                        if (empty($candidateMap)) {
                    ?>
                            <tr>
                                <td><?= htmlspecialchars($position) ?></td>
                                <td colspan="<?= 3 + count($collegeDepartments) ?>">No candidates</td>
                            </tr>
                        <?php
                            continue;
                        }

                        $allCandidates = array_values($candidateMap);
                        usort($allCandidates, fn($a, $b) => $b['total'] - $a['total']);
                        $positionRowCount = count($allCandidates);
                        $rank = 1;

                        foreach ($allCandidates as $candidate):
                            $calculatedTotal = array_sum($candidate['votes']);
                        ?>
                            <tr>
                                <?php if ($position !== $prevPosition): ?>
                                    <td rowspan="<?= $positionRowCount ?>"><?= htmlspecialchars($position) ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($candidate['name']) ?></td>
                                <td><?= htmlspecialchars($candidate['party']) ?></td>
                                <?php foreach ($collegeDepartments as $dept): ?>
                                    <td><?= $candidate['votes'][$dept] ?? 0 ?></td>
                                <?php endforeach; ?>
                                <td><?= $calculatedTotal ?></td>
                                <td><?= $rank++ ?></td>
                            </tr>
                    <?php
                            $prevPosition = $position;
                        endforeach;
                    endforeach;
                    ?>
                </table>
            </div>
        <?php endforeach; ?>

        <hr>

        <!-- Last Page -->
        <div class="page">
            <h2 style="text-align:center">Precinct Summary</h2>
            <table>
                <tr>
                    <th>College</th>
                    <th>Main Campus Precincts</th>
                    <th>WMSU ESU Precincts</th>
                    <th>Total</th>
                </tr>
                <?php
                $stmt = $pdo->prepare("
                    SELECT 
                        v.college,
                        COUNT(DISTINCT CASE WHEN p.type = 'Main Campus' THEN p.name END) as main_campus,
                        COUNT(DISTINCT CASE WHEN p.type = 'WMSU ESU' THEN p.name END) as esu
                    FROM precinct_voters pv
                    JOIN precincts p ON pv.precinct = p.name
                    JOIN voters v ON pv.student_id = v.student_id
                    WHERE v.college IS NOT NULL
                    GROUP BY v.college
                ");
                $stmt->execute();
                $precincts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($precincts as $row): ?>
                    <tr>
                        <td><?= $row['college'] ?></td>
                        <td><?= $row['main_campus'] ?></td>
                        <td><?= $row['esu'] ?></td>
                        <td><?= $row['main_campus'] + $row['esu'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <hr>
            <h2 class="center">Confirmation</h2>

            <p class="center">We, the undersigned, confirm the accuracy of the election results presented in this report of election: <?= htmlspecialchars($election_name) ?> held on <?= formatDate($electionDate) ?>.</p>

            <div style="margin-top: 10px; text-align: center;">
                <div style="display: inline-block; width: 100%;">
                    <div class="signature-box">
                        <p>Electoral Commissioner</p>
                        <br>
                        <div class="signature-line"></div>
                        <p>Name and Signature</p>
                        <div class="signature-line"></div>
                        <p>Date</p>
                    </div>
                    <div class="signature-box">
                        <p>Director of Student Affairs</p>
                        <br>
                        <div class="signature-line"></div>
                        <p>Name and Signature</p>
                        <div class="signature-line"></div>
                        <p>Date</p>
                    </div>
                    <div class="signature-box">
                        <p>Head of Electoral Board</p>
                        <br>
                        <div class="signature-line"></div>
                        <p>Name and Signature</p>
                        <div class="signature-line"></div>
                        <p>Date</p>
                    </div>
                </div>
            </div>

            <div style="margin-top: 0px; text-align: center;">
                <div class="signature-box">
                    <p>President of Western Mindanao State University</p>
                    <br>
                    <div class="signature-line"></div>
                    <p>Name and Signature</p>
                    <div class="signature-line"></div>
                    <p>Date</p>
                </div>
            </div>
        </div>

        <script>
            // Global Participation Chart
            <?php if ($totalVoters > 0): ?>
                safeCreateChart(
                    'participationChart',
                    'pie',
                    ['Voted (<?= $votedPercentage ?>%)', 'Did Not Vote (<?= $notVotedPercentage ?>%)'],
                    [<?= $votedCount ?>, <?= $notVotedCount ?>],
                    ['#4CAF50', '#F44336']
                );
            <?php endif; ?>

            // Central Position Charts
            <?php foreach ($central_positions as $position):
                if (!isset($candidatesByLevel['Central'][$position])) continue;
                $candidates = $candidatesByLevel['Central'][$position];
                $totalVotes = array_sum(array_column($candidates, 'total'));
                if ($totalVotes <= 0) continue;

                $topCandidates = array_slice($candidates, 0, 6);
                $labels = [];
                $data = [];
                $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];

                foreach ($topCandidates as $candidate) {
                    $name = $candidate['name'];
                    if (strlen($name) > 15) {
                        $name = substr($name, 0, 15) . '...';
                    }
                    $labels[] = "{$name} ({$candidate['party']})";
                    $data[] = max(1, $candidate['total']);
                }
            ?>
                safeCreateChart(
                    'chart_<?= preg_replace("/[^a-zA-Z0-9]/", "_", $position) ?>',
                    'pie',
                    <?= json_encode($labels) ?>,
                    <?= json_encode($data) ?>,
                    <?= json_encode(array_slice($colors, 0, count($labels))) ?>
                );
            <?php endforeach; ?>

            <?php foreach ($colleges as $college):
                $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_voters,
            COUNT(DISTINCT v.student_id) as voted_count
        FROM voters vr
        LEFT JOIN votes v ON vr.student_id = v.student_id AND v.voting_period_id = ?
        WHERE vr.college = ?
    ");
                $stmt->execute([$voting_period_id, $college]);
                $collegeStats = $stmt->fetch(PDO::FETCH_ASSOC);
                $collegeTotalVoters = $collegeStats['total_voters'] ?? 0;
                $collegeVotedCount = $collegeStats['voted_count'] ?? 0;
                $collegeNotVotedCount = $collegeTotalVoters - $collegeVotedCount;
                $collegeVotedPercentage = $collegeTotalVoters > 0 ? round(($collegeVotedCount / $collegeTotalVoters) * 100, 2) : 0;

                if ($collegeTotalVoters >= 0):
            ?>
                    safeCreateChart(
                        'participationChart_<?= preg_replace("/[^a-zA-Z0-9]/", "_", $college) ?>',
                        'pie',
                        ['Voted (<?= $collegeVotedPercentage ?>%)', 'Did Not Vote (<?= 100 - $collegeVotedPercentage ?>%)'],
                        [<?= $collegeVotedCount ?>, <?= $collegeNotVotedCount ?>],
                        ['#4CAF50', '#F44336']
                    );
                <?php endif; ?>
            <?php endforeach; ?>


            // Local Position Charts
            <?php foreach ($colleges as $college):
                foreach ($local_positions as $position):
                    $allCandidates = [];

                    foreach (['main_campus', 'esu'] as $precinctType) {
                        if (isset($candidatesByLevel['Local'][$precinctType][$position])) {
                            foreach ($candidatesByLevel['Local'][$precinctType][$position] as $candidate) {
                                if ($candidate['college'] === $college) {
                                    $allCandidates[] = $candidate;
                                }
                            }
                        }
                    }

                    if (!empty($allCandidates)):
                        $positionTotal = array_sum(array_column($allCandidates, 'total'));
                        if ($positionTotal <= 0) continue;

                        usort($allCandidates, fn($a, $b) => $b['total'] - $a['total']);
                        $topCandidates = array_slice($allCandidates, 0, 5);
                        $labels = [];
                        $data = [];
                        $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'];

                        foreach ($topCandidates as $candidate) {
                            $name = $candidate['name'];
                            if (strlen($name) > 15) {
                                $name = substr($name, 0, 15) . '...';
                            }
                            $labels[] = "{$name} ({$candidate['party']})";
                            $data[] = $candidate['total'];
                        }

            ?>
                        safeCreateChart(
                            'chart_<?= preg_replace("/[^a-zA-Z0-9]/", "_", $position . "_" . $college) ?>',
                            'pie',
                            <?= json_encode($labels) ?>,
                            <?= json_encode($data) ?>,
                            <?= json_encode(array_slice($colors, 0, count($labels))) ?>
                        );
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </script>
        <button class="print-button" onclick="window.print()" title="Print Results">🖨️</button>
</body>

</html>
<?php
$html = ob_get_clean();
echo $html;
?>