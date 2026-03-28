<?php
include('includes/conn.php'); // Connect to Main DB
$main_pdo = $pdo; // Save main connection
include('includes/conn_archived.php'); // Connects to wmsu_voting_system_archived
$voting_period_id = $_GET['voting_period_id'] ?? null;

if (!$voting_period_id) {
    echo "<p>No voting period specified.</p>";
    exit();
}

try {
    // 1. Get election details from archived data
    $stmt = $pdo->prepare("
         
    SELECT 
    ae.election_name AS voting_period_name, 
    ae.semester,
    ae.school_year_start,
    ae.school_year_end,
    ae.start_period AS election_date,
    aay.year_label,
    aay.semester AS academic_semester
FROM archived_elections ae
LEFT JOIN archived_academic_years aay ON ae.academic_year_id = aay.id
WHERE ae.voting_period_id = ?
LIMIT 1

    ");
    $stmt->execute([$voting_period_id]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$election) {
        echo "<p>No archived election data found for this voting period.</p>";
        exit();
    }

    $election_name = $election['voting_period_name'];
    $semester = !empty($election['academic_semester']) ? $election['academic_semester'] : $election['semester'];
    $school_year_label = !empty($election['year_label']) ? $election['year_label'] : ($election['school_year_start'] . ' - ' . $election['school_year_end']);
    $election_date = $election['election_date'];
    $current_date = date('F j, Y');

    // 2. Get all archived candidates for this voting period
    $stmt = $pdo->prepare("
     SELECT 
    ac.original_id,
    ac.candidate_name AS name,
    ac.position,
    ac.party,
    acol.college_name AS college,  -- ✅ from archived table
    ac.level,
    ac.outcome,
    ac.votes_received AS total_votes,
    ac.internal_votes,
    ac.external_votes
FROM archived_candidates ac
LEFT JOIN archived_colleges acol
    ON ac.college = acol.college_id
WHERE ac.voting_period_id = ?
ORDER BY ac.level, ac.position, ac.outcome DESC, ac.votes_received DESC
    ");
    $stmt->execute([$voting_period_id]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($candidates)) {
        echo "<p>No archived candidates found for this voting period.</p>";
        exit();
    }

    // 3. Organize candidates by level and position
    $candidatesByLevel = [
        'Central' => [],
        'Local' => []
    ];

    $winners = [
        'Central' => [],
        'Local' => []
    ];

    foreach ($candidates as $candidate) {
        $level = $candidate['level'] === 'Central' ? 'Central' : 'Local';
        $position = $candidate['position'];
        $college = $candidate['college'];

        if (!isset($candidatesByLevel[$level][$position])) {
            $candidatesByLevel[$level][$position] = [];
        }

        $candidatesByLevel[$level][$position][] = $candidate;

        // Track winners
        if ($candidate['outcome'] === 'Won') {
            if ($level === 'Central') {
                $winners['Central'][$position][] = $candidate;
            } else {
                $winners['Local'][$college][$position][] = $candidate;
            }
        }
    }

    // 4. Get distinct colleges from archived candidates
    $stmt = $pdo->prepare("
        SELECT DISTINCT college 
        FROM archived_candidates 
        WHERE voting_period_id = ? AND college IS NOT NULL AND college != 'Unknown'
        ORDER BY college
    ");
    $stmt->execute([$voting_period_id]);
    $colleges = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 5. Fetch vote breakdown
    $voteBreakdown = [];
    try {
        // Fetch voters info for mapping
        $stmt = $pdo->prepare("SELECT student_id, college FROM archived_voters WHERE voting_period_id = ?");
        $stmt->execute([$voting_period_id]);
        $votersInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $voterMap = array_column($votersInfo, 'college', 'student_id');

        // Fetch votes from Main DB
        $stmt = $main_pdo->prepare("SELECT candidate_id, student_id FROM votes WHERE voting_period_id = ?");
        $stmt->execute([$voting_period_id]);
        $allVotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allVotes as $vote) {
            $cid = $vote['candidate_id'];
            $col = $voterMap[$vote['student_id']] ?? 'Unknown';
            if (!isset($voteBreakdown[$cid][$col])) $voteBreakdown[$cid][$col] = 0;
            $voteBreakdown[$cid][$col]++;
        }
    } catch (Exception $e) {
    }

    // Generate the PDF report
    ob_start();
?>
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

            .winner {
                background-color: #e6ffe6;
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
            }

            @media print {
                .print-button {
                    display: none;
                }

                .page {
                    page-break-after: always;
                    margin: 0;
                    padding: 20px;
                }

                table {
                    page-break-inside: avoid;
                }
            }
        </style>
    </head>

    <body>
        <!-- Page 1 - Cover Page -->
        <div class="page">
            <div class="header">
                <img src="images/wmsu-logo.png" alt="WMSU Logo" style="height: 100px; width:auto;">
                <div class="header-center">
                    <h1><?= htmlspecialchars($election_name) ?> Elections</h1>
                    <h2>OFFICIAL ELECTION RESULTS</h2>
                    <p><?= htmlspecialchars($semester) ?>, AY <?= htmlspecialchars($school_year_label) ?></p>
                    <p>Election Held: <?= date('F j, Y', strtotime($election_date)) ?></p>
                    <p>Report Generated: <?= $current_date ?></p>
                </div>
                <img src="images/osa_logo.png" alt="OSA Logo" style="height: 100px; width:auto;">
            </div>

            <hr>

            <h2 style="text-align:center">Summary of Results</h2>

            <!-- Central Level Winners -->
            <h1 style="text-align:center">Central Level Election Winners</h1>
            <table>
                <tr>
                    <th>Position</th>
                    <th>Winner</th>
                    <th>Party</th>
                    <th>Total Votes</th>
                    <th>Internal Votes</th>
                    <th>External Votes</th>
                </tr>
                <?php foreach ($winners['Central'] as $position => $candidates): ?>
                    <?php foreach ($candidates as $winner): ?>
                        <tr class="winner">
                            <td><?= htmlspecialchars($position) ?></td>
                            <td><?= htmlspecialchars($winner['name']) ?></td>
                            <td><?= htmlspecialchars($winner['party']) ?></td>
                            <td><?= number_format($winner['total_votes']) ?></td>
                            <td><?= number_format($winner['internal_votes']) ?></td>
                            <td><?= number_format($winner['external_votes']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Local Level Winners by College -->
        <?php foreach ($colleges as $college):
            // Check if this college has any local winners
            if (empty($winners['Local'][$college])) continue;
        ?>
            <div class="page">
                <h2 style="text-align:center"><?= htmlspecialchars($college) ?> - Local Election Winners</h2>
                <table>
                    <tr>
                        <th>Position</th>
                        <th>Winner</th>
                        <th>Party</th>
                        <th>Total Votes</th>
                        <th>Internal Votes</th>
                        <th>External Votes</th>
                    </tr>
                    <?php foreach ($winners['Local'][$college] as $position => $candidates): ?>
                        <?php foreach ($candidates as $winner): ?>
                            <tr class="winner">
                                <td><?= htmlspecialchars($position) ?></td>
                                <td><?= htmlspecialchars($winner['name']) ?></td>
                                <td><?= htmlspecialchars($winner['party']) ?></td>
                                <td><?= number_format($winner['total_votes']) ?></td>
                                <td><?= number_format($winner['internal_votes']) ?></td>
                                <td><?= number_format($winner['external_votes']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>

        <!-- Local Positions Vote Breakdown -->
        <div class="page">
            <h2 style="text-align:center">Votes Breakdown by College (Local Positions)</h2>
            <table style="font-size: 10px;">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Candidate</th>
                        <th>Party</th>
                        <?php foreach ($colleges as $college): ?>
                            <th><?= htmlspecialchars($college) ?></th>
                        <?php endforeach; ?>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidatesByLevel['Local'] as $position => $candidates):
                        foreach ($candidates as $candidate): ?>
                            <tr>
                                <td><?= htmlspecialchars($position . ' (' . $candidate['college'] . ')') ?></td>
                                <td><?= htmlspecialchars($candidate['name']) ?></td>
                                <td><?= htmlspecialchars($candidate['party']) ?></td>
                                <?php
                                $rowTotal = 0;
                                foreach ($colleges as $college):
                                    $votes = $voteBreakdown[$candidate['original_id']][$college] ?? 0;
                                    $rowTotal += $votes;
                                ?>
                                    <td><?= number_format($votes) ?></td>
                                <?php endforeach; ?>
                                <td><strong><?= number_format($rowTotal) ?></strong></td>
                            </tr>
                    <?php endforeach;
                    endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Last Page - Signatures -->
        <div class="page">
            <hr>
            <h2 class="center">Confirmation</h2>
            <p class="center">We, the undersigned, confirm the accuracy of the election results presented in this report.</p>

            <div style="margin-top: 50px; text-align: center;">
                <div class="signature-box">
                    <p>Electoral Commissioner</p>
                    <div class="signature-line"></div>
                    <p>Name and Signature</p>
                    <div class="signature-line"></div>
                    <p>Date</p>
                </div>

                <div class="signature-box">
                    <p>Director of Student Affairs</p>
                    <div class="signature-line"></div>
                    <p>Name and Signature</p>
                    <div class="signature-line"></div>
                    <p>Date</p>
                </div>

                <div class="signature-box">
                    <p>Head of Electoral Board</p>
                    <div class="signature-line"></div>
                    <p>Name and Signature</p>
                    <div class="signature-line"></div>
                    <p>Date</p>
                </div>
            </div>

            <div style="margin-top: 50px; text-align: center;">
                <div class="signature-box">
                    <p>President of Western Mindanao State University</p>
                    <div class="signature-line"></div>
                    <p>Name and Signature</p>
                    <div class="signature-line"></div>
                    <p>Date</p>
                </div>
            </div>
        </div>

        <button class="print-button" onclick="window.print()" title="Print Results">🖨️</button>
    </body>

    </html>
<?php
    $html = ob_get_clean();
    echo $html;
} catch (PDOException $e) {
    echo "<p>Error retrieving archived election data: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>