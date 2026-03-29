<?php
session_start();

include('includes/conn.php'); // Connect to Main DB ($pdo)
$main_pdo = $pdo; // Save Main DB connection to $main_pdo

include('includes/conn_archived.php'); // Connect to Archive DB ($pdo_archived)
$pdo = $pdo_archived; // Set $pdo to Archive DB for the rest of the script

$voting_period_id = $_GET['id'] ?? null;
// Fetch election details from archived_elections
$stmt = $pdo->prepare("
    SELECT ae.id, ae.election_name, ae.status, ae.start_period, ae.end_period, ae.parties, ae.semester, ae.school_year_start, ae.school_year_end, ae.turnout,
           aay.year_label, aay.semester AS academic_semester
    FROM archived_elections ae
    LEFT JOIN archived_academic_years aay ON ae.academic_year_id = aay.id
    WHERE ae.voting_period_id = ? 
    LIMIT 1
");
$stmt->execute([$voting_period_id]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$election) {
    die("No archived election found for voting_period_id: $voting_period_id");
}

$election_id = $election['id'];
$election_name = $election['election_name'];


// Fetch participating parties from archived_parties
$stmt = $pdo->prepare("
    SELECT name
    FROM archived_parties
    WHERE voting_period_id = ?
");
$stmt->execute([$voting_period_id]);
$party_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
$parties_display = !empty($party_names) ? implode(', ', $party_names) : ($election['parties'] ?? 'N/A');

// Fetch candidacy stats from archived_candidacies (optional)
$stmt = $pdo->prepare("
    SELECT total_filed, start_period, end_period
    FROM archived_candidacies
    WHERE election_name = ? AND voting_period_id = ?
    LIMIT 1
");

$stmt->execute([$election_name, $voting_period_id]);
$candidacy = $stmt->fetch(PDO::FETCH_ASSOC);
$total_filed = $candidacy['total_filed'] ?? 'N/A';
$candidacy_start = $candidacy['start_period'] ?? null;
$candidacy_end = $candidacy['end_period'] ?? null;

// Fetch voting period details from archived_voting_periods
$stmt = $pdo->prepare("
    SELECT start_period, end_period
    FROM archived_voting_periods
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$voting_period_id]);
$voting_period_details = $stmt->fetch(PDO::FETCH_ASSOC);
$voting_start = $voting_period_details['start_period'] ?? null;
$voting_end = $voting_period_details['end_period'] ?? null;

// Fetch positions and candidates from archived_candidates
$stmt = $pdo->prepare("
    SELECT DISTINCT position AS name, level
    FROM archived_candidates
    WHERE voting_period_id = ? AND position IS NOT NULL
");
$stmt->execute([$voting_period_id]);
$positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$central_positions = array_column(array_filter($positions, fn($pos) => $pos['level'] === 'Central'), 'name');
$local_positions = array_column(array_filter($positions, fn($pos) => $pos['level'] === 'Local'), 'name');



// // Fetch colleges from archived_voters
// $colleges = array_unique(array_column($pdo->query("
//     SELECT college   
//     FROM archived_voters 
//     WHERE college IS NOT NULL AND college != '' AND election_name = '$election_name' AND voting_period_id = $voting_period_id
// ")->fetchAll(), 'college'));

// Define static colleges array
$colleges = [
    'College of Law',
    'College of Agriculture',
    'College of Liberal Arts',
    'College of Architecture',
    'College of Nursing',
    'College of Asian & Islamic Studies',
    'College of Computing Studies',
    'College of Forestry & Environmental Studies',
    'College of Criminal Justice Education',
    'College of Home Economics',
    'College of Engineering',
    'College of Medicine',
    'College of Public Administration & Development Studies',
    'College of Sports Science & Physical Education',
    'College of Science and Mathematics',
    'College of Social Work & Community Development',
    'College of Teacher Education'
];

// Fetch vote breakdown from main DB votes table and archive DB voters
$voteBreakdown = [];
$voteBreakdownDept = [];
$partyBreakdownCollege = [];
$partyBreakdownCollegeLocal = [];
$departmentsByCollege = [];

try {
    // Fetch voters info for mapping
    $stmt = $pdo->prepare("SELECT student_id, college, department FROM archived_voters WHERE voting_period_id = ?");
    $stmt->execute([$voting_period_id]);
    $votersInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $voterMap = [];
    foreach ($votersInfo as $v) {
        $voterMap[$v['student_id']] = [
            'college' => $v['college'] ?? 'Unknown',
            'department' => $v['department'] ?? 'Unknown'
        ];
        if (!empty($v['college']) && !empty($v['department'])) {
            $departmentsByCollege[$v['college']][$v['department']] = true;
        }
    }
    // Sort departments
    foreach ($departmentsByCollege as &$depts) {
        $depts = array_keys($depts);
        sort($depts);
    }
    unset($depts);

    // Fetch candidate info for party mapping
    $stmt = $pdo->prepare("SELECT original_id, party, level FROM archived_candidates WHERE voting_period_id = ?");
    $stmt->execute([$voting_period_id]);
    $candInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $candidatePartyMap = [];
    $candidateLevelMap = [];
    foreach ($candInfo as $c) {
        $candidatePartyMap[$c['original_id']] = trim($c['party']);
        $candidateLevelMap[$c['original_id']] = trim($c['level']);
    }

    // Fetch votes
    $stmt = $main_pdo->prepare("SELECT candidate_id, student_id FROM votes WHERE voting_period_id = ?");
    $stmt->execute([$voting_period_id]);
    $allVotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allVotes as $vote) {
        $cid = $vote['candidate_id'];
        $sid = $vote['student_id'];

        $vInfo = $voterMap[$sid] ?? ['college' => 'Unknown', 'department' => 'Unknown'];
        $col = trim($vInfo['college']);
        $dept = trim($vInfo['department']);

        // Candidate Votes by College
        if (!isset($voteBreakdown[$cid][$col])) $voteBreakdown[$cid][$col] = 0;
        $voteBreakdown[$cid][$col]++;

        // Candidate Votes by Department
        if (!isset($voteBreakdownDept[$cid][$dept])) $voteBreakdownDept[$cid][$dept] = 0;
        $voteBreakdownDept[$cid][$dept]++;

        // Party Votes by College (Central only)
        if (isset($candidateLevelMap[$cid])) {
            $party = $candidatePartyMap[$cid];
            $level = $candidateLevelMap[$cid];
            if ($level === 'Central') {
                if (!isset($partyBreakdownCollege[$party][$col])) $partyBreakdownCollege[$party][$col] = 0;
                $partyBreakdownCollege[$party][$col]++;
            } elseif ($level === 'Local') {
                if (!isset($partyBreakdownCollegeLocal[$party][$col])) $partyBreakdownCollegeLocal[$party][$col] = 0;
                $partyBreakdownCollegeLocal[$party][$col]++;
            }
        }
    }
} catch (Exception $e) { /* Ignore if tables missing */
}

// Fetch candidates and vote counts from archived_candidates
$stmt = $pdo->prepare("
    SELECT 
        MAX(id) as id, 
        candidate_name AS name, 
        party, 
        position,
        MAX(votes_received) AS vote_count,
        level,
        college,
        MAX(original_id) as original_id,
        MAX(outcome) as outcome,
        MAX(picture_path) as picture_path
    FROM archived_candidates
    WHERE voting_period_id = ?
    GROUP BY candidate_name, position, party, level, college
    ORDER BY CASE WHEN level = 'Central' THEN 1 ELSE 2 END, vote_count DESC
");
$stmt->execute([$voting_period_id]);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

$college_filter = $_GET['college'] ?? '';
if ($college_filter) {
    $candidates = array_filter($candidates, function ($candidate) use ($college_filter) {
        return $candidate['level'] === 'Central' || $candidate['college'] === $college_filter;
    });
}

$positionTotals = [];
$maxVotesPerPosition = [];


$candidatesByLevel = ['Central' => [], 'Local' => []];
$highestVotes = ['Central' => [], 'Local' => []];

foreach ($candidates as $candidate) {
    $level = $candidate['level'] === 'Central' ? 'Central' : 'Local';
    $college = $candidate['college'] ?? 'Unknown';
    $key = $candidate['position'] . ($level === 'Local' ? '|' . $college : '');

    // Calculate totals for percentages
    if (!isset($positionTotals[$key])) {
        $positionTotals[$key] = 0;
    }
    $positionTotals[$key] += $candidate['vote_count'];

    // Track max votes for this position/college context
    if (!isset($maxVotesPerPosition[$key])) {
        $maxVotesPerPosition[$key] = 0;
    }
    if ($candidate['vote_count'] > $maxVotesPerPosition[$key]) {
        $maxVotesPerPosition[$key] = $candidate['vote_count'];
    }

    $candidateData = [
        'name' => $candidate['name'],
        'party' => $candidate['party'],
        'total' => $candidate['vote_count'],
        'position' => $candidate['position'],
        'college' => $college,
        'original_id' => $candidate['original_id'],
        'outcome' => $candidate['outcome'],
        'picture_path' => $candidate['picture_path']
    ];

    if (!isset($highestVotes[$level][$key]) || $candidate['vote_count'] > $highestVotes[$level][$key]['total']) {
        $highestVotes[$level][$key] = $candidateData;
    }

    if ($level === 'Central') {
        $candidatesByLevel['Central'][$candidate['position']][$candidate['name']] = $candidateData;
    } else {
        $candidatesByLevel['Local'][$college][$candidate['position']][$candidate['name']] = $candidateData;
    }
}

// Fetch voter stats from archived_voters
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_voters,
        SUM(CASE WHEN has_voted = 1 THEN 1 ELSE 0 END) as voted_count
    FROM archived_voters
    WHERE election_name = ? AND voting_period_id = ?
");
$stmt->execute([$election_name, $voting_period_id]);
$voter_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$total_voters = $voter_stats['total_voters'] ?? 0;
$voted_count = $voter_stats['voted_count'] ?? 0;

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WMSU i-Elect | Election Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css" rel="stylesheet">
    <link href="external/css/styles.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        .nav-linker {
            color: black !important;
        }


        .ballot-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .ballot-header {
            text-align: center;
            border-bottom: 3px solid #dc3545;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .ballot-header h1 {
            color: #dc3545;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .position-section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }

        .position-title {
            background: #dc3545;
            color: white;
            padding: 12px 20px;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            font-size: 1.2rem;
            position: relative;
        }

        .position-winner-info {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .candidate-card {
            border: 2px solid #e9ecef;
            border-radius: 0 0 8px 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            position: relative;
        }

        .winner-card {
            border-color: #28a745;
            background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
        }

        .loser-card {
            opacity: 0.8;
            background: #f8f9fa;
        }

        .winner-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
        }

        .loser-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background: #6c757d;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
        }

        .candidate-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #dee2e6;
            margin-right: 20px;
        }

        .winner-card .candidate-photo {
            border-color: #28a745;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.3);
        }

        .candidate-info {
            flex: 1;
        }

        .candidate-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: #333;
        }

        .winner-card .candidate-name {
            color: #155724;
        }

        .candidate-party {
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .vote-count {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 100%;
            font-weight: 600;
            color: #495057;
            text-align: center;
            min-width: 100px;
        }

        .winner-card .vote-count {
            background: #28a745;
            color: white;
            box-shadow: 0 2px 5px rgba(40, 167, 69, 0.3);
        }

        .loser-card .vote-count {
            background: #6c757d;
            color: white;
        }

        .vote-percentage {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .winner-card .vote-percentage {
            color: #155724;
            font-weight: 600;
        }

        .party-logo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 10px;
        }

        .election-meta {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .meta-item {
            margin-bottom: 10px;
        }

        .meta-label {
            font-weight: 600;
            color: #495057;
        }

        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .candidate-rank {
            position: absolute;
            left: -10px;
            top: 50%;
            transform: translateY(-50%);
            background: #dc3545;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .winner-card .candidate-rank {
            background: #28a745;
        }

        @media print {
            .print-btn {
                display: none;
            }

            .ballot-container {
                box-shadow: none;
                padding: 0;
            }

            .position-section {
                break-inside: avoid;
            }
        }

        @media (max-width: 768px) {
            .ballot-container {
                padding: 15px;
            }

            .candidate-card {
                padding: 15px;
                padding-left: 25px;
            }

            .candidate-photo {
                width: 60px;
                height: 60px;
                margin-right: 15px;
            }

            .candidate-rank {
                width: 25px;
                height: 25px;
                font-size: 0.8rem;
                left: -8px;
            }
        }

        .position-section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }

        .position-title {
            background: #dc3545;
            color: white;
            padding: 12px 20px;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            font-size: 1.2rem;
            position: relative;
        }

        .candidate-card {
            border: 2px solid #e9ecef;
            border-radius: 0 0 8px 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            position: relative;
            background: #fff;
        }

        .winner-card {
            border-color: #28a745;
            background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
        }

        .winner-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
        }

        .candidate-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #dee2e6;
            margin-right: 20px;
        }

        .winner-card .candidate-photo {
            border-color: #28a745;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.3);
        }

        .candidate-info {
            flex: 1;
            text-align: left;
        }

        .candidate-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: #333;
        }

        .winner-card .candidate-name {
            color: #155724;
        }

        .candidate-party {
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .vote-count {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 100%;
            font-weight: 600;
            color: #495057;
            text-align: center;
            min-width: 100px;
        }

        .winner-card .vote-count {
            background: #28a745;
            color: white;
            box-shadow: 0 2px 5px rgba(40, 167, 69, 0.3);
        }

        .candidate-rank {
            position: absolute;
            left: -10px;
            top: 50%;
            transform: translateY(-50%);
            background: #dc3545;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .winner-card .candidate-rank {
            background: #28a745;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="external/img/wmsu-logo.png" class="img velocemente logo">
                WMSU I - Elect |</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            About
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="about_us.php">About Us</a></li>
                            <li><a class="dropdown-item" href="about_system.php">About the System</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php" role="button">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="parties.php" role="button">Parties</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a class="nav-link" href="login/index.php">
                        <i class="bi bi-person-circle"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <?php

    // Check if election parameter is provided
    if (!isset($_GET['election']) || empty($_GET['election'])) {
        echo '<p class="text-center text-danger">No election specified. Please select an election.</p>';
    } else {
        $election_name = $_GET['election'];
        // Fetch election details from archived_elections
        $stmt = $pdo_archived->prepare("
                    SELECT ae.*, aay.year_label, aay.semester AS academic_semester 
                    FROM archived_elections ae
                    LEFT JOIN archived_academic_years aay ON ae.academic_year_id = aay.id
                    WHERE ae.election_name = ?
                ");
        $stmt->execute([$election_name]);
        $election = $stmt->fetch(PDO::FETCH_ASSOC);

        $election_id = $election['id'] ?? null;

        // Fetch total number of voters for this election
        $stmt = $pdo_archived->prepare("
    SELECT COUNT(*) 
    FROM archived_voters 
    WHERE election_name = ?
");
        $stmt->execute([$election_name]);
        $total_voters = (int) $stmt->fetchColumn();

        // Fetch voted count
        $stmt = $pdo_archived->prepare("SELECT COUNT(*) FROM archived_voters WHERE election_name = ? AND has_voted = 1");
        $stmt->execute([$election_name]);
        $voted_count = (int) $stmt->fetchColumn();
        $did_not_vote = $total_voters - $voted_count;

        // Fetch candidacy period
        $stmt = $pdo_archived->prepare("SELECT start_period, end_period FROM archived_candidacies WHERE election_name = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$election_name]);
        $candidacy_period = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch all candidates grouped by position
        $stmt = $pdo_archived->prepare("
    SELECT
        ac.*,
        av.course,
        ap.level
    FROM archived_elections ae
    INNER JOIN archived_positions ap
        ON ap.election_id = ae.id
    INNER JOIN archived_candidates ac
        ON ac.election_name = ae.id
        AND ac.position = ap.name
    LEFT JOIN archived_voters av 
        ON av.election_name = ae.election_name 
        AND (
            ac.candidate_name = CONCAT(av.first_name, ' ', av.last_name)
            OR ac.candidate_name = CONCAT(av.first_name, ' ', av.middle_name, ' ', av.last_name)
            OR ac.candidate_name = TRIM(CONCAT(av.first_name, ' ', COALESCE(av.middle_name, ''), ' ', av.last_name))
        )
    WHERE ae.election_name = ?
    GROUP BY
        ac.id
    ORDER BY
        CASE 
            WHEN ac.position = 'President' THEN 0
            WHEN ac.position = 'Vice President' THEN 1
            ELSE 2
        END,
        CASE
            WHEN ap.level = 'Central' THEN 1
            WHEN ap.level = 'Local' THEN 2
            ELSE 3
        END,
        FIELD(ac.position,
            'Secretary',
            'Treasurer',
            'Auditor',
            'Business Manager',
            'P.I.O',
            'Senator'
        ),
        ac.votes_received DESC
");

        $stmt->execute([$_GET['election']]);

        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group candidates by position and calculate total votes per position
        $candidates_by_position = [];
        $position_totals = [];

        foreach ($candidates as $candidate) {
            $position = $candidate['position'];
            $candidates_by_position[$position][] = $candidate;

            // Calculate total votes for this position
            if (!isset($position_totals[$position])) {
                $position_totals[$position] = 0;
            }
            $position_totals[$position] += $candidate['votes_received'];
        }
    }

    ?>
    <div class="container my-5">
        <div class="card card-rounded mb-5">
            <div class="card-body">
                <div class="row mt-4">
                    <div class="container mb-5">
                        <div class="ballot-header">
                            <h1>WMSU Election Official Result</h1>
                            <h2 class="text-muted text-capitalize fw-bold"><?php echo htmlspecialchars(ucwords(strtolower($election_name))); ?></h2>
                            <h5 class="text-muted"><?php
                                                    $sy = !empty($election['year_label']) ? $election['year_label'] : ($election['school_year_start'] . '-' . $election['school_year_end']);
                                                    $sem = !empty($election['academic_semester']) ? $election['academic_semester'] : $election['semester'];
                                                    echo htmlspecialchars($sy . ' | ' . $sem);
                                                    ?></h5>
                        </div>

                        <div class="card mb-4 border-success">
                            <div class="card-header bg-success text-white fw-bold">
                                Elected Candidates
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php
                                    foreach ($candidates_by_position as $position => $position_candidates) {
                                        $pos_total_votes = $position_totals[$position] ?? 0;

                                        // Determine winner dynamically
                                        $candidateCount = count($position_candidates);
                                        $maxVotes = 0;
                                        foreach ($position_candidates as $c) {
                                            if ($c['votes_received'] > $maxVotes) $maxVotes = $c['votes_received'];
                                        }
                                        $winnersCount = 0;
                                        foreach ($position_candidates as $c) {
                                            if ($c['votes_received'] == $maxVotes) $winnersCount++;
                                        }

                                        foreach ($position_candidates as $candidate) {
                                            $is_winner = false;
                                            if ($candidateCount == 1) {
                                                $is_winner = true;
                                            } elseif ($maxVotes > 0 && $winnersCount == 1 && $candidate['votes_received'] == $maxVotes) {
                                                $is_winner = true;
                                            }

                                            if ($is_winner) {
                                                $percentage = $pos_total_votes > 0 ? round(($candidate['votes_received'] / $pos_total_votes) * 100) : 0;
                                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                                echo '<span><strong>' . htmlspecialchars($position) . ':</strong> ' . htmlspecialchars($candidate['candidate_name']) . '</span>';
                                                echo '<span>' . number_format($candidate['votes_received']) . ' votes (' . $percentage . '%)</span>';
                                                echo '</li>';
                                            }
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Election Metadata -->
                        <div class="election-meta">
                            <div class="row">
                                <div class="col-md-6">

                                    <div class="meta-item">
                                        <span class="meta-label">Election Period:</span>
                                        <?php
                                        if ($election && $election['start_period'] && $candidacy_period['end_period']) {
                                            $start = (new DateTime($election['start_period']))->format('M j, Y');
                                            $end = (new DateTime($election['end_period']))->format('M j, Y');
                                            echo htmlspecialchars($start . ' - ' . $end);
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Candidacy Period:</span>
                                        <?php
                                        if ($candidacy_period && $candidacy_period['start_period'] && $candidacy_period['end_period']) {
                                            $start = (new DateTime($candidacy_period['start_period']))->format('M j, Y');
                                            $end = (new DateTime($candidacy_period['end_period']))->format('M j, Y');
                                            echo htmlspecialchars($start . ' - ' . $end);
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Voting Period:</span>
                                        <?php
                                        if ($election['start_period'] && $election['end_period']) {
                                            $start = (new DateTime($election['start_period']))->format('M j, Y');
                                            $end = (new DateTime($election['end_period']))->format('M j, Y');
                                            echo htmlspecialchars($start . ' - ' . $end);
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Published:</span>
                                        <?php echo htmlspecialchars((new DateTime($election['archived_on']))->format('F j, Y')); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="meta-item">
                                        <span class="meta-label">Total Registered Voters:</span>
                                        <?php echo number_format($total_voters); ?>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Voted:</span>
                                        <?php echo number_format($voted_count); ?>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Did Not Vote:</span>
                                        <?php echo number_format($did_not_vote); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Central Results -->
                    <div class="card mb-5" id="resultsCard">
                        <div class="card-body">
                            <h3 class="text-center"><b>CENTRAL</b></h3>
                            <div class="row">
                                <?php foreach ($central_positions as $position): ?>
                                    <div class="col-12 m-3">
                                        <div class="position-section">
                                            <div class="position-title">
                                                <?php echo htmlspecialchars($position); ?>
                                            </div>
                                            <?php
                                            if (isset($candidatesByLevel['Central'][$position])) {
                                                // Sort candidates descending by total votes
                                                usort($candidatesByLevel['Central'][$position], function ($a, $b) {
                                                    return $b['total'] <=> $a['total'];
                                                });

                                                $posTotal = $positionTotals[$position] ?? 0;

                                                $counter = 1;
                                                $candidateCount = count($candidatesByLevel['Central'][$position]);
                                                foreach ($candidatesByLevel['Central'][$position] as $candidate) {
                                                    $maxVotes = $maxVotesPerPosition[$position] ?? 0;
                                                    if ($candidateCount == 1) {
                                                        $is_winner = true;
                                                    } else {
                                                        $is_winner = ($candidate['total'] == $maxVotes) && ($maxVotes > 0);
                                                    }
                                                    $percentage = $posTotal > 0 ? round(($candidate['total'] / $posTotal) * 100, 1) : 0;
                                                    $photoUrl = !empty($candidate['picture_path']) ? 'login/uploads/candidates/' . $candidate['picture_path'] : 'https://via.placeholder.com/80?text=No+Image';
                                            ?>
                                                    <div class="candidate-card <?php echo $is_winner ? 'winner-card' : ''; ?>">
                                                        <div class="candidate-rank"><?php echo $counter++; ?></div>
                                                        <?php if ($is_winner): ?>
                                                            <div class="winner-badge">WINNER</div>
                                                        <?php endif; ?>

                                                        <div class="d-flex align-items-center">
                                                            <img src="<?php echo htmlspecialchars($photoUrl); ?>" class="candidate-photo" alt="Candidate">

                                                            <div class="candidate-info">
                                                                <div class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></div>
                                                                <div class="candidate-party"><?php echo htmlspecialchars($candidate['party']); ?></div>
                                                                <div class="text-muted small"><?php echo $percentage; ?>% of votes</div>
                                                            </div>

                                                            <div class="vote-count">
                                                                <?php echo number_format($candidate['total']); ?>
                                                            </div>
                                                        </div>
                                                    </div>

                                            <?php
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                            </div>
                        </div>

                        <div class="row">
                            <h3 class="text-center"><b>LOCAL</b></h3>
                            <div class="col text-center">

                            </div>

                        </div>
                        <?php
                        // Filter colleges that have candidates for any position
                        $college_filter = $_GET['college'] ?? '';
                        $has_candidates = array_filter($colleges, function ($college) use ($candidatesByLevel, $local_positions) {
                            foreach ($local_positions as $position) {
                                if (isset($candidatesByLevel['Local'][$college][$position]) && !empty($candidatesByLevel['Local'][$college][$position])) {
                                    return true; // College has at least one candidate for a position
                                }
                            }
                            return false;
                        });

                        // Determine which colleges to display
                        $display_colleges = $college_filter ? (in_array($college_filter, $has_candidates) ? [$college_filter] : []) : $has_candidates;

                        if (empty($display_colleges)) {
                        ?>
                            <div class="row mt-4">
                                <div class="col-12 text-center">
                                    <p>No colleges with candidates available.</p>
                                </div>
                            </div>
                            <?php
                        } else {
                            foreach ($display_colleges as $college): ?>
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h4 class="text-center"><b><?php echo htmlspecialchars($college); ?></b></h4>
                                    </div>
                                </div>
                                <div class="row">
                                    <?php
                                    $has_positions = false; // Track if any positions have candidates
                                    foreach ($local_positions as $position):
                                        // Only display positions with candidates
                                        if (isset($candidatesByLevel['Local'][$college][$position]) && !empty($candidatesByLevel['Local'][$college][$position])):
                                            $has_positions = true;
                                            $posKey = $position . '|' . $college;
                                            $posTotal = $positionTotals[$posKey] ?? 0;
                                    ?>
                                            <div class="col-12 m-3">
                                                <div class="position-section">
                                                    <div class="position-title">
                                                        <?php echo htmlspecialchars($position); ?>
                                                    </div>
                                                    <?php
                                                    // Sort candidates descending by total votes
                                                    usort($candidatesByLevel['Local'][$college][$position], function ($a, $b) {
                                                        return $b['total'] <=> $a['total'];
                                                    });
                                                    $counter = 1;
                                                    $candidateCount = count($candidatesByLevel['Local'][$college][$position]);
                                                    foreach ($candidatesByLevel['Local'][$college][$position] as $candidate) {
                                                        $maxVotes = $maxVotesPerPosition[$posKey] ?? 0;
                                                        if ($candidateCount == 1) {
                                                            $is_winner = true;
                                                        } else {
                                                            $is_winner = ($candidate['total'] == $maxVotes) && ($maxVotes > 0);
                                                        }
                                                        $percentage = $posTotal > 0 ? round(($candidate['total'] / $posTotal) * 100, 1) : 0;
                                                        $photoUrl = !empty($candidate['picture_path']) ? 'login/uploads/candidates/' . $candidate['picture_path'] : 'https://via.placeholder.com/80?text=No+Image';
                                                    ?>
                                                        <div class="candidate-card <?php echo $is_winner ? 'winner-card' : ''; ?>">
                                                            <div class="candidate-rank"><?php echo $counter++; ?></div>
                                                            <?php if ($is_winner): ?>
                                                                <div class="winner-badge">WINNER</div>
                                                            <?php endif; ?>

                                                            <div class="d-flex align-items-center">
                                                                <img src="<?php echo htmlspecialchars($photoUrl); ?>" class="candidate-photo" alt="Candidate">

                                                                <div class="candidate-info">
                                                                    <div class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></div>
                                                                    <div class="candidate-party"><?php echo htmlspecialchars($candidate['party']); ?></div>
                                                                    <div class="text-muted small"><?php echo $percentage; ?>% of votes</div>
                                                                </div>

                                                                <div class="vote-count">
                                                                    <?php echo number_format($candidate['total']); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php if (!$has_positions): ?>
                                        <div class="col-12 text-center">
                                            <p>No candidates available for any positions.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php } ?>
                    </div>
                </div>

                <!-- Detailed Report Summary Tabular View -->
                <div class="row mt-5">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Detailed Report Summary</h4>
                                <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link nav-linker active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview-panel" type="button" role="tab">Overview</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link nav-linker" id="voters-tab" data-bs-toggle="tab" data-bs-target="#voters-panel" type="button" role="tab">Voter Statistics</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link nav-linker" id="candidates-tab" data-bs-toggle="tab" data-bs-target="#candidates-panel" type="button" role="tab">Candidate Summary</button>
                                    </li>
                                </ul>
                                <div class="tab-content pt-3" id="reportTabsContent">
                                    <!-- Overview Panel -->
                                    <div class="tab-pane fade show active" id="overview-panel" role="tabpanel">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th style="width: 30%;">Election Name</th>
                                                    <td><?php echo htmlspecialchars($election_name); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Academic Year</th>
                                                    <td><?php echo htmlspecialchars(!empty($election['year_label']) ? $election['year_label'] : ($election['school_year_start'] . ' - ' . $election['school_year_end'])); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Semester</th>
                                                    <td><?php echo htmlspecialchars(!empty($election['academic_semester']) ? $election['academic_semester'] : $election['semester']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Election Period</th>
                                                    <td><?php echo date('M d, Y h:i A', strtotime($election['start_period'])) . ' - ' . date('M d, Y h:i A', strtotime($election['end_period'])); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Candidacy Period</th>
                                                    <td><?php echo ($candidacy_start && $candidacy_end) ? date('M d, Y h:i A', strtotime($candidacy_start)) . ' - ' . date('M d, Y h:i A', strtotime($candidacy_end)) : 'N/A'; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Voting Period</th>
                                                    <td><?php echo ($voting_start && $voting_end) ? date('M d, Y h:i A', strtotime($voting_start)) . ' - ' . date('M d, Y h:i A', strtotime($voting_end)) : 'N/A'; ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- Voters Panel -->
                                    <div class="tab-pane fade" id="voters-panel" role="tabpanel">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th style="width: 30%;">Total Registered Voters</th>
                                                    <td><?php echo number_format($total_voters); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Total Votes Cast</th>
                                                    <td><?php echo number_format($voted_count); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Did Not Vote</th>
                                                    <td><?php echo number_format($total_voters - $voted_count); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Voter Turnout</th>
                                                    <td><?php echo $total_voters > 0 ? round(($voted_count / $total_voters) * 100, 2) . '%' : '0%'; ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- Candidates Panel -->
                                    <div class="tab-pane fade" id="candidates-panel" role="tabpanel">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th style="width: 30%;">Total Candidates Filed</th>
                                                    <td><?php echo htmlspecialchars($total_filed); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Total Candidates (Final)</th>
                                                    <td><?php echo count($candidates); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Participating Parties</th>
                                                    <td><?php echo htmlspecialchars($parties_display); ?></td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <hr class="my-4">
                                        <h5 class="card-title">Candidate Details</h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Position</th>
                                                        <th>Candidate Name</th>
                                                        <th>Party</th>
                                                        <th>Votes</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($candidates as $cand): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($cand['position']); ?></td>
                                                            <td><?php echo htmlspecialchars($cand['candidate_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($cand['party']); ?></td>
                                                            <td><?php echo number_format($cand['votes_received']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- College Vote Breakdown -->
                <div class="row mt-5">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Votes Breakdown by College (Central Positions)</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Position</th>
                                                <th>Candidate</th>
                                                <th>Party</th>
                                                <?php foreach ($colleges as $college): ?>
                                                    <th style="min-width: 150px;"><?php echo htmlspecialchars($college); ?></th>
                                                <?php endforeach; ?>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            foreach ($central_positions as $position) {
                                                if (isset($candidatesByLevel['Central'][$position])) {
                                                    // Sort candidates by total votes
                                                    usort($candidatesByLevel['Central'][$position], function ($a, $b) {
                                                        return $b['total'] <=> $a['total'];
                                                    });

                                                    foreach ($candidatesByLevel['Central'][$position] as $candidate) {
                                            ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($position); ?></td>
                                                            <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                                            <td><?php echo htmlspecialchars($candidate['party']); ?></td>
                                                            <?php
                                                            $rowTotal = 0;
                                                            foreach ($colleges as $college) {
                                                                $votes = isset($voteBreakdown[$candidate['original_id']][$college]) ? $voteBreakdown[$candidate['original_id']][$college] : 0;
                                                                $rowTotal += $votes;
                                                                echo "<td>" . number_format($votes) . "</td>";
                                                            }
                                                            ?>
                                                            <td class="fw-bold"><?php echo number_format($rowTotal); ?></td>
                                                        </tr>
                                            <?php
                                                    }
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- College Vote Breakdown (Local) -->
                <div class="row mt-5">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Votes Breakdown by College (Local Positions)</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Position</th>
                                                <th>Candidate</th>
                                                <th>Party</th>
                                                <?php foreach ($colleges as $college): ?>
                                                    <th style="min-width: 150px;"><?php echo htmlspecialchars($college); ?></th>
                                                <?php endforeach; ?>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            foreach ($local_positions as $position) {
                                                $localCandidates = [];
                                                foreach ($colleges as $college) {
                                                    if (isset($candidatesByLevel['Local'][$college][$position])) {
                                                        foreach ($candidatesByLevel['Local'][$college][$position] as $candidate) {
                                                            $localCandidates[] = $candidate;
                                                        }
                                                    }
                                                }

                                                if (!empty($localCandidates)) {
                                                    usort($localCandidates, function ($a, $b) {
                                                        return $b['total'] <=> $a['total'];
                                                    });

                                                    foreach ($localCandidates as $candidate) {
                                            ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($position . ' (' . $candidate['college'] . ')'); ?></td>
                                                            <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                                            <td><?php echo htmlspecialchars($candidate['party']); ?></td>
                                                            <?php
                                                            $rowTotal = 0;
                                                            foreach ($colleges as $college) {
                                                                $votes = isset($voteBreakdown[$candidate['original_id']][$college]) ? $voteBreakdown[$candidate['original_id']][$college] : 0;
                                                                $rowTotal += $votes;
                                                                echo "<td>" . number_format($votes) . "</td>";
                                                            }
                                                            ?>
                                                            <td class="fw-bold"><?php echo number_format($rowTotal); ?></td>
                                                        </tr>
                                            <?php
                                                    }
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Local Vote Breakdown by Department -->
                <?php foreach ($colleges as $college):
                    if (isset($candidatesByLevel['Local'][$college]) && !empty($candidatesByLevel['Local'][$college])):
                        $collegeDepts = $departmentsByCollege[$college] ?? [];
                        if (empty($collegeDepts)) continue;
                ?>
                        <div class="row mt-5">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title">Votes Breakdown by Department - <?php echo htmlspecialchars($college); ?></h4>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Position</th>
                                                        <th>Candidate</th>
                                                        <th>Party</th>
                                                        <?php foreach ($collegeDepts as $dept): ?>
                                                            <th style="min-width: 100px;"><?php echo htmlspecialchars($dept); ?></th>
                                                        <?php endforeach; ?>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    foreach ($local_positions as $position) {
                                                        if (isset($candidatesByLevel['Local'][$college][$position])) {
                                                            // Sort candidates by total votes
                                                            usort($candidatesByLevel['Local'][$college][$position], function ($a, $b) {
                                                                return $b['total'] <=> $a['total'];
                                                            });

                                                            foreach ($candidatesByLevel['Local'][$college][$position] as $candidate) {
                                                    ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($position); ?></td>
                                                                    <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                                                    <td><?php echo htmlspecialchars($candidate['party']); ?></td>
                                                                    <?php
                                                                    $rowTotal = 0;
                                                                    foreach ($collegeDepts as $dept) {
                                                                        $votes = isset($voteBreakdownDept[$candidate['original_id']][$dept]) ? $voteBreakdownDept[$candidate['original_id']][$dept] : 0;
                                                                        $rowTotal += $votes;
                                                                        echo "<td>" . number_format($votes) . "</td>";
                                                                    }
                                                                    ?>
                                                                    <td class="fw-bold"><?php echo number_format($rowTotal); ?></td>
                                                                </tr>
                                                    <?php
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                <?php endif;
                endforeach; ?>

                <!-- Party Vote Breakdown -->
                <div class="row mt-5">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Votes Breakdown by Party (Central Positions)</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Party</th>
                                                <?php foreach ($colleges as $college): ?>
                                                    <th style="min-width: 150px;"><?php echo htmlspecialchars($college); ?></th>
                                                <?php endforeach; ?>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Get all parties involved in Central
                                            $centralParties = $party_names;

                                            // Also add parties from candidates to be safe
                                            foreach ($candidates as $cand) {
                                                if ($cand['level'] === 'Central') {
                                                    $centralParties[] = $cand['party'];
                                                }
                                            }

                                            $centralParties = array_unique($centralParties);
                                            $centralParties = array_map('trim', $centralParties);
                                            $centralParties = array_filter($centralParties);
                                            sort($centralParties);

                                            foreach ($centralParties as $party) {
                                            ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($party); ?></td>
                                                    <?php
                                                    $rowTotal = 0;
                                                    foreach ($colleges as $college) {
                                                        $votes = isset($partyBreakdownCollege[$party][$college]) ? $partyBreakdownCollege[$party][$college] : 0;
                                                        $rowTotal += $votes;
                                                        echo "<td>" . number_format($votes) . "</td>";
                                                    }
                                                    ?>
                                                    <td class="fw-bold"><?php echo number_format($rowTotal); ?></td>
                                                </tr>
                                            <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <Br>

                <div class="row mt-5">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Votes Breakdown by Party (Local Positions)</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Party</th>
                                                <?php foreach ($colleges as $college): ?>
                                                    <th style="min-width: 150px;"><?php echo htmlspecialchars($college); ?></th>
                                                <?php endforeach; ?>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Use the same party list logic
                                            foreach ($centralParties as $party) {
                                            ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($party); ?></td>
                                                    <?php
                                                    $rowTotal = 0;
                                                    foreach ($colleges as $college) {
                                                        $votes = isset($partyBreakdownCollegeLocal[$party][$college]) ? $partyBreakdownCollegeLocal[$party][$college] : 0;
                                                        $rowTotal += $votes;
                                                        echo "<td>" . number_format($votes) . "</td>";
                                                    }
                                                    ?>
                                                    <td class="fw-bold"><?php echo number_format($rowTotal); ?></td>
                                                </tr>
                                            <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container-fluid">
            <div class="row mt-5">
                <div class="col-md-6">
                    <h1 class="bold c-red"> <img src="external/img/wmsu-logo.png" class="img-fluid logo"> WMSU I-Elect</h1>
                    <p>Your friendly WMSU Voting Web Application</p>
                </div>
                <div class="col">
                    <h5 class="c-red bold">About</h5>
                    <p><a href="about_us.php" class="linker">About Us</a></p>
                    <p><a href="about_system.php" class="linker">About the System</a></p>
                </div>
                <div class="col">
                    <h5 class="c-red bold">Help</h5>
                    <p><a href="login/index.php" class="linker">Login</a></p>
                    <p><a href="file_candidacy.php" class="linker">Filing of Candidacy</a></p>
                    <p><a href="voting.php" class="linker">Voting</a></p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
// Close PDO connection
$pdo_archived = null;
?>