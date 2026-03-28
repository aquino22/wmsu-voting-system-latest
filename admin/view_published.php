<?php
session_start();
include('includes/conn_new.php'); // Connects to wmsu_voting_system_archived
include('includes/conn_archived.php'); // Connects to wmsu_voting_system_archived
include('includes/conn.php'); // Connect to Main DB
$main_pdo = $pdo; // Save main connection
include('includes/conn_archived.php'); // Re-connect to Archive DB

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = "NON_ADMIN";
    header("Location: ../index.php");
    exit();
}

$voting_period_id = $_GET['voting_period_id'] ?? null;
if (!$voting_period_id) {
    header("Location: reports.php");
    exit();
}

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
$election_start = $election['start_period'];
$election_end = $election['end_period'];

// Fetch participating parties — try archived_parties first, fall back to
// distinct party values from archived_candidates (parties table may be empty)
$stmt = $pdo->prepare("SELECT name FROM archived_parties WHERE voting_period_id = ?");
$stmt->execute([$voting_period_id]);
$party_names = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($party_names)) {
    // Derive distinct party names directly from candidates
    $stmt = $pdo->prepare("
        SELECT DISTINCT TRIM(party) AS name
        FROM archived_candidates
        WHERE voting_period_id = ? AND party IS NOT NULL AND TRIM(party) != ''
        ORDER BY name ASC
    ");
    $stmt->execute([$voting_period_id]);
    $party_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
$parties_display = !empty($party_names) ? implode(', ', $party_names) : 'N/A';

// Fetch candidacy stats from archived_candidacies
$stmt = $pdo->prepare("
    SELECT total_filed, start_period, end_period
    FROM archived_candidacies
    WHERE election_id = ? AND voting_period_id = ?
    LIMIT 1
");
$stmt->execute([$election_id, $voting_period_id]);
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
$local_positions   = array_column(array_filter($positions, fn($pos) => $pos['level'] === 'Local'),   'name');

// -----------------------------------------------------------------
// FIX: Fetch colleges from archived_colleges (DB-driven, not static)
// archived_voters.college stores college_id (integer), so we build
// a lookup map: college_id => college_name
// -----------------------------------------------------------------
$stmt = $pdo->query("SELECT college_id, college_name FROM archived_colleges ORDER BY college_name ASC");
$collegeRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map: college_id (string key) => college_name
$collegeMap = [];
foreach ($collegeRows as $row) {
    $collegeMap[(string)$row['college_id']] = $row['college_name'];
}

// Ordered list of college names for table headers
$colleges = array_values($collegeMap); // names only, sorted A-Z

// -----------------------------------------------------------------
// FIX: Fetch departments from archived_departments (DB-driven)
// archived_voters.department stores department_id (integer)
// Map: department_id (string key) => ['name' => ..., 'college_id' => ...]
// -----------------------------------------------------------------
$stmt = $pdo->query("SELECT department_id, department_name, college_id FROM archived_departments ORDER BY department_name ASC");
$deptRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$departmentMap = []; // department_id => ['name' => ..., 'college_name' => ...]
foreach ($deptRows as $row) {
    $collegeName = $collegeMap[(string)$row['college_id']] ?? 'Unknown';
    $departmentMap[(string)$row['department_id']] = [
        'name'         => $row['department_name'],
        'college_name' => $collegeName,
    ];
}

// -----------------------------------------------------------------
// Build course map: course_id => ['name' => ..., 'college_id' => ...]
// Build major map:  major_id  => ['name' => ..., 'course_id'  => ...]
// -----------------------------------------------------------------
$stmt = $pdo->query("SELECT id, course_name, college_id FROM archived_courses ORDER BY course_name ASC");
$courseRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$courseMap  = [];
foreach ($courseRows as $row) {
    $courseMap[(string)$row['id']] = [
        'name'       => $row['course_name'],
        'college_id' => (string)$row['college_id'],
    ];
}

$stmt = $pdo->query("SELECT major_id, major_name, course_id FROM archived_majors ORDER BY major_name ASC");
$majorRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$majorMap  = [];
foreach ($majorRows as $row) {
    $majorMap[(string)$row['major_id']] = [
        'name'      => $row['major_name'],
        'course_id' => (string)$row['course_id'],
    ];
}

// Fetch vote breakdown from main DB votes table and archive DB voters
$voteBreakdown              = []; // cand_original_id => college_name  => count
$voteBreakdownDept          = []; // cand_original_id => dept_name     => count
$voteBreakdownCourse        = []; // cand_original_id => course_name   => count
$voteBreakdownMajor         = []; // cand_original_id => major_name    => count
$partyBreakdownCollege      = []; // party => college_name => count (Central)
$partyBreakdownCollegeLocal = []; // party => college_name => count (Local)
$departmentsByCollege       = []; // college_name => [dept_name, ...]
$coursesByCollege           = []; // college_name => [course_name, ...]
$majorsByCourse             = []; // course_name  => [major_name, ...]

try {
    // Fetch voters — all ID fields; resolve to names via maps
    $stmt = $pdo->prepare("
        SELECT student_id, college, department, course, major
        FROM archived_voters
        WHERE voting_period_id = ?
    ");
    $stmt->execute([$voting_period_id]);
    $votersInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $voterMap = []; // student_id => ['college', 'department', 'course', 'major']
    $voterMap = []; // student_id => ['college' => ..., 'department' => ..., 'course' => ..., 'major' => ...]
    foreach ($votersInfo as $v) {
        $collegeKey = (string)trim($v['college']     ?? '');
        $deptKey    = (string)trim($v['department']  ?? '');
        $courseKey  = (string)trim($v['course']      ?? '');
        $majorKey   = (string)trim($v['major']       ?? '');

        // Resolve college
        $collegeName = isset($collegeMap[$collegeKey])
            ? $collegeMap[$collegeKey]
            : ($collegeKey !== '' ? $collegeKey : 'Unknown');

        // Resolve department
        $deptName = isset($departmentMap[$deptKey])
            ? $departmentMap[$deptKey]['name']
            : ($deptKey !== '' ? $deptKey : 'Unknown');

        // Resolve course
        $courseName = isset($courseMap[$courseKey])
            ? $courseMap[$courseKey]['name']
            : ($courseKey !== '' ? $courseKey : 'Unknown');

        // Resolve major (may be NULL/empty meaning no major)
        $majorName = '';
        if ($majorKey !== '' && isset($majorMap[$majorKey])) {
            $majorName = $majorMap[$majorKey]['name'];
        } elseif ($majorKey !== '') {
            $majorName = $majorKey; // legacy raw value
        }

        $voterMap[$v['student_id']] = [
            'college'    => $collegeName,
            'department' => $deptName,
            'course'     => $courseName,
            'major'      => $majorName,
        ];

        if ($collegeName !== 'Unknown') {
            if ($deptName    !== 'Unknown') $departmentsByCollege[$collegeName][$deptName]   = true;
            if ($courseName  !== 'Unknown') $coursesByCollege[$collegeName][$courseName]      = true;
        }
        if ($courseName !== 'Unknown' && $majorName !== '') {
            $majorsByCourse[$courseName][$majorName] = true;
        }
    }

    // Sort all lookup arrays alphabetically
    foreach ($departmentsByCollege as &$items) {
        $items = array_keys($items);
        sort($items);
    }
    foreach ($coursesByCollege      as &$items) {
        $items = array_keys($items);
        sort($items);
    }
    foreach ($majorsByCourse        as &$items) {
        $items = array_keys($items);
        sort($items);
    }
    unset($items);

    // Fetch candidate party/level info for party breakdown
    $stmt = $pdo->prepare("SELECT original_id, party, level FROM archived_candidates WHERE voting_period_id = ?");
    $stmt->execute([$voting_period_id]);
    $candInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $candidatePartyMap = [];
    $candidateLevelMap = [];
    foreach ($candInfo as $c) {
        $candidatePartyMap[$c['original_id']] = trim($c['party']);
        $candidateLevelMap[$c['original_id']] = trim($c['level']);
    }

    // Fetch votes from main DB
    $stmt = $pdo->prepare("SELECT candidate_id, student_id FROM archived_votes WHERE voting_period_id = ?");
    $stmt->execute([$voting_period_id]);
    $allVotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allVotes as $vote) {
        $cid  = $vote['candidate_id'];
        $sid  = $vote['student_id'];

        $vInfo  = $voterMap[$sid] ?? ['college' => 'Unknown', 'department' => 'Unknown', 'course' => 'Unknown', 'major' => ''];
        $col    = $vInfo['college'];
        $dept   = $vInfo['department'];
        $course = $vInfo['course'];
        $major  = $vInfo['major'];

        // Candidate votes by college
        if (!isset($voteBreakdown[$cid][$col]))    $voteBreakdown[$cid][$col] = 0;
        $voteBreakdown[$cid][$col]++;

        // Candidate votes by department
        if (!isset($voteBreakdownDept[$cid][$dept])) $voteBreakdownDept[$cid][$dept] = 0;
        $voteBreakdownDept[$cid][$dept]++;

        // Candidate votes by course
        if (!isset($voteBreakdownCourse[$cid][$course])) $voteBreakdownCourse[$cid][$course] = 0;
        $voteBreakdownCourse[$cid][$course]++;

        // Candidate votes by major
        if ($major !== '') {
            if (!isset($voteBreakdownMajor[$cid][$major])) $voteBreakdownMajor[$cid][$major] = 0;
            $voteBreakdownMajor[$cid][$major]++;
        }

        // Party votes by college
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
        MAX(votes_received) AS vote_count, 
        MAX(internal_votes) AS internal_votes,
        MAX(external_votes) AS external_votes,
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

// -----------------------------------------------------------------
// FIX: Resolve candidate college from ID to name (same as voters)
// archived_candidates.college also stores college_id
// -----------------------------------------------------------------
foreach ($candidates as &$cand) {
    $ck = (string)trim($cand['college'] ?? '');
    if (isset($collegeMap[$ck])) {
        $cand['college'] = $collegeMap[$ck];
    }
    // If not found in map, leave as-is (may already be a name in legacy data)
}
unset($cand);

$college_filter = $_GET['college'] ?? '';
if ($college_filter) {
    $candidates = array_filter($candidates, function ($candidate) use ($college_filter) {
        return $candidate['level'] === 'Central' || $candidate['college'] === $college_filter;
    });
}

$positionTotals       = [];
$maxVotesPerPosition  = [];
$candidatesByLevel    = ['Central' => [], 'Local' => []];
$highestVotes         = ['Central' => [], 'Local' => []];

foreach ($candidates as $candidate) {
    $level   = $candidate['level'] === 'Central' ? 'Central' : 'Local';
    $college = $candidate['college'] ?? 'Unknown';
    $key     = $candidate['position'] . ($level === 'Local' ? '|' . $college : '');

    if (!isset($positionTotals[$key])) $positionTotals[$key] = 0;
    $positionTotals[$key] += $candidate['vote_count'];

    if (!isset($maxVotesPerPosition[$key])) $maxVotesPerPosition[$key] = 0;
    if ($candidate['vote_count'] > $maxVotesPerPosition[$key]) {
        $maxVotesPerPosition[$key] = $candidate['vote_count'];
    }

    $candidateData = [
        'name'        => $candidate['name'],
        'party'       => $candidate['party'],
        'total'       => $candidate['vote_count'],
        'position'    => $candidate['position'],
        'internal_votes' => $candidate['internal_votes'],
        'external_votes' => $candidate['external_votes'],
        'college'     => $college,
        'original_id' => $candidate['original_id'],
        'outcome'     => $candidate['outcome'],
        'picture_path' => $candidate['picture_path'],
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

// Fetch voter stats from archived_voters.
// NOTE: archived_voters.election_name stores the numeric election ID as a string
// (e.g. '127'), NOT the human-readable name. Use voting_period_id only.
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_voters,
        SUM(CASE WHEN has_voted = 1 THEN 1 ELSE 0 END) as voted_count
    FROM archived_voters
    WHERE voting_period_id = ?
");
$stmt->execute([$voting_period_id]);
$voter_stats  = $stmt->fetch(PDO::FETCH_ASSOC);
$total_voters = $voter_stats['total_voters'] ?? 0;
$voted_count  = $voter_stats['voted_count']  ?? 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU i-Elect Admin | Published </title>
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="js/select.dataTables.min.css">
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <link rel="shortcut icon" href="images/favicon.png" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
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
            min-width: 50px;
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

        /* ── Unified report table styles ── */
        :root {
            --wmsu-red: #B22222;
            --wmsu-red-dark: #8B0000;
            --wmsu-red-light: #fdf2f2;
            --wmsu-header-bg: #6c1a1a;
            /* deep maroon for all table headers */
            --wmsu-header-txt: #ffffff;
            --wmsu-subheader-bg: #8b2020;
            /* slightly lighter for sub-headers */
            --wmsu-row-stripe: #fff8f8;
            --wmsu-row-hover: #fce8e8;
            --wmsu-winner-bg: #d4edda;
            --wmsu-winner-txt: #155724;
            --wmsu-border: #d9b8b8;
        }

        /* Every report card gets the same look */
        .report-card {
            border: 1px solid var(--wmsu-border);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(178, 34, 34, .08);
        }

        .report-card .card-header {
            background: var(--wmsu-red);
            color: #fff;
            padding: 14px 20px;
            border-bottom: 2px solid var(--wmsu-red-dark);
        }

        .report-card .card-header h4,
        .report-card .card-header h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1rem;
        }

        .report-card .card-header small {
            opacity: .85;
            font-size: .8rem;
        }

        /* Unified table */
        .report-table {
            margin: 0;
            font-size: .82rem;
            border-collapse: collapse;
            width: 100%;
        }

        .report-table thead tr th {
            background: var(--wmsu-header-bg) !important;
            color: var(--wmsu-header-txt) !important;
            border-color: var(--wmsu-red-dark) !important;
            font-weight: 600;
            vertical-align: middle;
            padding: 8px 10px;
            white-space: nowrap;
        }

        .report-table tbody tr:nth-child(even) {
            background-color: var(--wmsu-row-stripe);
        }

        .report-table tbody tr:hover {
            background-color: var(--wmsu-row-hover) !important;
        }

        .report-table tbody td {
            border-color: var(--wmsu-border) !important;
            vertical-align: middle;
            padding: 6px 10px;
        }

        /* Position/group cell */
        .report-table td.pos-cell {
            background: var(--wmsu-red-light);
            font-weight: 600;
            text-align: center;
            border-right: 2px solid var(--wmsu-border) !important;
            color: var(--wmsu-red-dark);
        }

        /* Level cell */
        .report-table td.level-cell {
            text-align: center;
            border-right: 2px solid var(--wmsu-border) !important;
        }

        /* Winner row */
        .report-table tr.winner-row td {
            background: var(--wmsu-winner-bg) !important;
            color: var(--wmsu-winner-txt);
            font-weight: 600;
        }

        .report-table tr.winner-row:hover td {
            background: #c3e6cb !important;
        }

        /* Null cell */
        .report-table .null-cell {
            color: #bbb;
        }

        /* Total cell */
        .report-table td.total-cell {
            background: #f1e8e8 !important;
            font-weight: 700;
            color: var(--wmsu-red-dark);
            border-left: 2px solid var(--wmsu-border) !important;
        }

        .report-table tr.winner-row td.total-cell {
            background: #a8d5b5 !important;
            color: #155724;
        }

        .report-card>.card-body>h4.card-title,
        .report-card>.card-body>.card-body>h4.card-title {
            color: var(--wmsu-red-dark);
            font-weight: 700;
            border-left: 4px solid var(--wmsu-red);
            padding-left: 10px;
            margin-bottom: 12px;
        }

        .report-card .card-header+.card-body {
            padding: 0;
        }
    </style>
</head>

<body>
    <div class="container-scroller">
        <nav class="navbar default-layout col-lg-12 col-12 p-0  d-flex align-items-top flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
                <div class="me-3">
                    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
                        <span class="icon-menu"></span>
                    </button>
                </div>
                <div>
                    <a class="navbar-brand brand-logo" href="index.php">
                        <img src="images/wmsu-logo.png" alt="logo" class="logo img-fluid" />
                        <small style="font-size: 16px;"><b>WMSU i-Elect</b></small>
                    </a>
                    <a class="navbar-brand brand-logo-mini" href="index.php">
                        <img src="images/wmsu-logo.png" class="logo img-fluid" alt="logo" />
                    </a>
                </div>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-top">
                <ul class="navbar-nav">
                    <li class="nav-item font-weight-semibold d-none d-lg-block ms-0">
                        <h1 class="welcome-text">Welcome, <span class="text-white fw-bold">WMSU Admin</span></h1>
                        <h6>
                            <?php
                            $stmt = $pdo_new->prepare("
                                SELECT e.id, e.election_name, e.academic_year_id, a.year_label, a.semester
                                FROM elections e
                                JOIN academic_years a ON e.academic_year_id = a.id
                                WHERE e.status = :status
                                ORDER BY a.year_label DESC, a.semester DESC
                            ");
                            $stmt->execute(['status' => 'Ongoing']);
                            $ongoingElections = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if ($ongoingElections) {
                                $first = array_shift($ongoingElections);
                                echo "<br><b>School Year:</b> " . htmlspecialchars($first['year_label']) . " | ";
                                echo "<b>Semester:</b> " . htmlspecialchars($first['semester']) . " | ";
                                echo "<b>Election:</b> " . htmlspecialchars($first['election_name']) . "<br>";

                                if ($ongoingElections) {
                                    echo '<div id="moreElections" style="display:none; margin-top:5px;">';
                                    foreach ($ongoingElections as $elec) {
                                        echo "<b>School Year:</b> " . htmlspecialchars($elec['year_label']) . " | ";
                                        echo "<b>Semester:</b> " . htmlspecialchars($elec['semester']) . " | ";
                                        echo "<b>Election:</b> " . htmlspecialchars($elec['election_name']) . "<br>";
                                    }
                                    echo '</div><br>';
                                    echo '<a href="javascript:void(0)" id="toggleElections" class="text-decoration-underline text-white">Show More</a>';
                                }
                            }
                            ?>
                        </h6>

                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                const toggleBtn = document.getElementById("toggleElections");
                                const moreDiv = document.getElementById("moreElections");

                                if (toggleBtn) {
                                    toggleBtn.addEventListener("click", function() {
                                        if (moreDiv.style.display === "none") {
                                            moreDiv.style.display = "block";
                                            toggleBtn.textContent = "Show Less";
                                        } else {
                                            moreDiv.style.display = "none";
                                            toggleBtn.textContent = "Show More";
                                        }
                                    });
                                }

                                const backToTopButton = document.getElementById('backToTop');
                                if (backToTopButton) {
                                    window.addEventListener('scroll', function() {
                                        if (window.pageYOffset > 200) {
                                            backToTopButton.classList.add('show');
                                        } else {
                                            backToTopButton.classList.remove('show');
                                        }
                                    });
                                    backToTopButton.addEventListener('click', function() {
                                        window.scrollTo({
                                            top: 0,
                                            behavior: 'smooth'
                                        });
                                    });
                                }
                            });
                        </script>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                        <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <img class="img-xs rounded-circle logo" src="images/wmsu-logo.png" style="background-color: white;" alt="Profile image">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                            <div class="dropdown-header text-center">
                                <img class="img-xs rounded-circle logoe" src="images/wmsu-logo.png" alt="Profile image">
                            </div>
                            <p class="mb-1 mt-3 font-weight-semibold dropdown-item"><b>WMSU ADMIN</b></p>
                            <a class="dropdown-item" id="logoutLink" href="processes/accounts/logout.php"><i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign Out</a>
                        </div>
                    </li>
                </ul>
                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
                    <span class="mdi mdi-menu"></span>
                </button>
            </div>
        </nav>

        <div class="container-fluid page-body-wrapper">
            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
            <?php include('includes/sidebar.php') ?>

            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Published Results</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                                        <div class="card card-rounded mb-5">
                                            <div class="card-body">
                                                <div class="row mt-4">
                                                    <div class="container mb-5">
                                                        <div class="d-flex align-items-center">
                                                            <h5><b>Election Name: </b><?php echo htmlspecialchars($election_name); ?></h5>
                                                            <div class="ms-auto" aria-hidden="true">
                                                                <a href="print copy.php?voting_period_id=<?php echo htmlspecialchars($_GET['voting_period_id']); ?>" target="_blank">
                                                                    <button class="btn btn-danger text-white">
                                                                        <i class="mdi mdi-pdf-box"></i> PDF
                                                                    </button>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <h5><b>Election Period: </b>
                                                            <?php echo date('M d, Y h:i A', strtotime($election_start)); ?> -
                                                            <?php echo date('M d, Y h:i A', strtotime($election_end)); ?>
                                                        </h5>
                                                        <?php if ($candidacy_start && $candidacy_end): ?>
                                                            <h5><b>Candidacy Period: </b>
                                                                <?php echo date('M d, Y h:i A', strtotime($candidacy_start)); ?> -
                                                                <?php echo date('M d, Y h:i A', strtotime($candidacy_end)); ?>
                                                            </h5>
                                                        <?php endif; ?>
                                                        <?php if ($voting_start && $voting_end): ?>
                                                            <h5><b>Voting Period: </b>
                                                                <?php echo date('M d, Y h:i A', strtotime($voting_start)); ?> -
                                                                <?php echo date('M d, Y h:i A', strtotime($voting_end)); ?>
                                                            </h5>
                                                        <?php endif; ?>
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
                                                                                usort($candidatesByLevel['Central'][$position], fn($a, $b) => $b['total'] <=> $a['total']);
                                                                                $posTotal       = $positionTotals[$position] ?? 0;
                                                                                $counter        = 1;
                                                                                $candidateCount = count($candidatesByLevel['Central'][$position]);

                                                                                foreach ($candidatesByLevel['Central'][$position] as $candidate) {
                                                                                    $maxVotes   = $maxVotesPerPosition[$position] ?? 0;
                                                                                    $is_winner  = ($candidateCount == 1) || (($candidate['total'] == $maxVotes) && ($maxVotes > 0));
                                                                                    $percentage = $posTotal > 0 ? round(($candidate['total'] / $posTotal) * 100, 1) : 0;
                                                                                    $photoUrl   = !empty($candidate['picture_path'])
                                                                                        ? '../login/uploads/candidates/' . $candidate['picture_path']
                                                                                        : 'https://via.placeholder.com/80?text=No+Image';
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
                                                                                            <button class="btn btn-sm btn-info text-white view-voters-btn ms-3"
                                                                                                data-original-id="<?php echo $candidate['original_id']; ?>"
                                                                                                data-voting-period-id="<?php echo $voting_period_id; ?>"
                                                                                                data-candidate-name="<?php echo htmlspecialchars($candidate['name']); ?>">
                                                                                                <i class="mdi mdi-eye"></i>
                                                                                            </button>
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

                                                        <!-- Local Results Header + College Filter -->
                                                        <div class="row">
                                                            <h3 class="text-center"><b>LOCAL</b></h3>
                                                            <div class="col text-end">
                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-danger dropdown-toggle text-white" data-bs-toggle="dropdown" aria-expanded="false">
                                                                        <?php
                                                                        $college_filter = $_GET['college'] ?? '';
                                                                        echo $college_filter ? htmlspecialchars($college_filter) : 'Select College';
                                                                        ?>
                                                                    </button>
                                                                    <ul class="dropdown-menu">
                                                                        <li><a class="dropdown-item" href="?voting_period_id=<?php echo $voting_period_id; ?>">All Colleges</a></li>
                                                                        <?php foreach ($colleges as $college): ?>
                                                                            <li><a class="dropdown-item" href="?voting_period_id=<?php echo $voting_period_id; ?>&college=<?php echo urlencode($college); ?>"><?php echo htmlspecialchars($college); ?></a></li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <?php
                                                        $college_filter = $_GET['college'] ?? '';
                                                        $has_candidates = array_filter($colleges, function ($college) use ($candidatesByLevel, $local_positions) {
                                                            foreach ($local_positions as $position) {
                                                                if (!empty($candidatesByLevel['Local'][$college][$position])) return true;
                                                            }
                                                            return false;
                                                        });

                                                        $display_colleges = $college_filter
                                                            ? (in_array($college_filter, $has_candidates) ? [$college_filter] : [])
                                                            : $has_candidates;

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
                                                                    $has_positions = false;
                                                                    foreach ($local_positions as $position):
                                                                        if (!empty($candidatesByLevel['Local'][$college][$position])):
                                                                            $has_positions = true;
                                                                            $posKey  = $position . '|' . $college;
                                                                            $posTotal = $positionTotals[$posKey] ?? 0;
                                                                    ?>
                                                                            <div class="col-12 m-3">
                                                                                <div class="position-section">
                                                                                    <div class="position-title">
                                                                                        <?php echo htmlspecialchars($position); ?>
                                                                                    </div>
                                                                                    <?php
                                                                                    usort($candidatesByLevel['Local'][$college][$position], fn($a, $b) => $b['total'] <=> $a['total']);
                                                                                    $counter        = 1;
                                                                                    $candidateCount = count($candidatesByLevel['Local'][$college][$position]);

                                                                                    foreach ($candidatesByLevel['Local'][$college][$position] as $candidate) {
                                                                                        $maxVotes   = $maxVotesPerPosition[$posKey] ?? 0;
                                                                                        $is_winner  = ($candidateCount == 1) || (($candidate['total'] == $maxVotes) && ($maxVotes > 0));
                                                                                        $percentage = $posTotal > 0 ? round(($candidate['total'] / $posTotal) * 100, 1) : 0;
                                                                                        $photoUrl   = !empty($candidate['picture_path'])
                                                                                            ? '../login/uploads/candidates/' . $candidate['picture_path']
                                                                                            : 'https://via.placeholder.com/80?text=No+Image';
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
                                                                                                <button class="btn btn-sm btn-info text-white view-voters-btn ms-2"
                                                                                                    data-original-id="<?php echo $candidate['original_id']; ?>"
                                                                                                    data-voting-period-id="<?php echo $voting_period_id; ?>"
                                                                                                    data-candidate-name="<?php echo htmlspecialchars($candidate['name']); ?>">
                                                                                                    <i class="mdi mdi-eye"></i>
                                                                                                </button>
                                                                                            </div>
                                                                                        </div>
                                                                                    <?php } ?>
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
                                                        <?php endforeach;
                                                        } ?>
                                                    </div>
                                                </div>

                                                <!-- Detailed Report Summary -->
                                                <div class="row mt-5">
                                                    <div class="col-12">
                                                        <div class="card report-card">
                                                            <div class="card-body">
                                                                <h4 class="card-title">Detailed Report Summary</h4>
                                                                <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                                                                    <li class="nav-item" role="presentation">
                                                                        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview-panel" type="button" role="tab">Overview</button>
                                                                    </li>
                                                                    <li class="nav-item" role="presentation">
                                                                        <button class="nav-link" id="voters-tab" data-bs-toggle="tab" data-bs-target="#voters-panel" type="button" role="tab">Voter Statistics</button>
                                                                    </li>
                                                                    <li class="nav-item" role="presentation">
                                                                        <button class="nav-link" id="candidates-tab" data-bs-toggle="tab" data-bs-target="#candidates-panel" type="button" role="tab">Candidate Summary</button>
                                                                    </li>
                                                                </ul>
                                                                <div class="tab-content pt-3" id="reportTabsContent">
                                                                    <!-- Overview Panel -->
                                                                    <div class="tab-pane fade show active" id="overview-panel" role="tabpanel">
                                                                        <table class="table report-table">
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
                                                                                    <td><?php echo ($election_start && $election_end) ? date('M d, Y h:i A', strtotime($election_start)) . ' - ' . date('M d, Y h:i A', strtotime($election_end)) : 'N/A'; ?></td>
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
                                                                        <table class="table report-table">
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
                                                                        <table class="table report-table">
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
                                                                            <table class="table table-bordered table-hover report-table">
                                                                                <thead>
                                                                                    <tr>
                                                                                        <th>Position</th>
                                                                                        <th>Candidate Name</th>
                                                                                        <th>Party</th>
                                                                                        <th>Votes</th>
                                                                                        <th>Action</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    <?php foreach ($candidates as $cand): ?>
                                                                                        <tr>
                                                                                            <td><?php echo htmlspecialchars($cand['position']); ?></td>
                                                                                            <td><?php echo htmlspecialchars($cand['name']); ?></td>
                                                                                            <td><?php echo htmlspecialchars($cand['party']); ?></td>
                                                                                            <td><?php echo number_format($cand['vote_count']); ?></td>
                                                                                            <td>
                                                                                                <button class="btn btn-sm btn-info text-white view-voters-btn"
                                                                                                    data-original-id="<?php echo $cand['original_id']; ?>"
                                                                                                    data-voting-period-id="<?php echo $voting_period_id; ?>"
                                                                                                    data-candidate-name="<?php echo htmlspecialchars($cand['name']); ?>">
                                                                                                    View Voters
                                                                                                </button>
                                                                                            </td>
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

                                                <!-- College Vote Breakdown (Central) -->
                                                <div class="row mt-5">
                                                    <div class="col-12">
                                                        <div class="card report-card">
                                                            <div class="card-body">
                                                                <h4 class="card-title">Votes Breakdown by College (Central Positions)</h4>
                                                                <div class="table-responsive">
                                                                    <table class="table table-bordered table-hover report-table">
                                                                        <thead>
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
                                                                                    usort($candidatesByLevel['Central'][$position], fn($a, $b) => $b['total'] <=> $a['total']);
                                                                                    foreach ($candidatesByLevel['Central'][$position] as $candidate) {
                                                                            ?>
                                                                                        <tr>
                                                                                            <td><?php echo htmlspecialchars($position); ?></td>
                                                                                            <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                                                                            <td><?php echo htmlspecialchars($candidate['party']); ?></td>
                                                                                            <?php
                                                                                            $rowTotal = 0;
                                                                                            foreach ($colleges as $college) {
                                                                                                $votes = $voteBreakdown[$candidate['original_id']][$college] ?? 0;
                                                                                                $rowTotal += $votes;
                                                                                                echo "<td>" . number_format($votes) . "</td>";
                                                                                            }
                                                                                            ?>
                                                                                            <td class="total-cell"><?php echo number_format($rowTotal); ?></td>
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
                                                        <div class="card report-card">
                                                            <div class="card-body">
                                                                <h4 class="card-title">Votes Breakdown by College (Local Positions)</h4>
                                                                <div class="table-responsive">
                                                                    <table class="table table-bordered table-hover report-table">
                                                                        <thead>
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
                                                                                        foreach ($candidatesByLevel['Local'][$college][$position] as $cand) {
                                                                                            $localCandidates[] = $cand;
                                                                                        }
                                                                                    }
                                                                                }
                                                                                if (!empty($localCandidates)) {
                                                                                    usort($localCandidates, fn($a, $b) => $b['total'] <=> $a['total']);
                                                                                    foreach ($localCandidates as $candidate) {
                                                                            ?>
                                                                                        <tr>
                                                                                            <td><?php echo htmlspecialchars($position . ' (' . $candidate['college'] . ')'); ?></td>
                                                                                            <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                                                                            <td><?php echo htmlspecialchars($candidate['party']); ?></td>
                                                                                            <?php
                                                                                            $rowTotal = 0;
                                                                                            foreach ($colleges as $college) {
                                                                                                $votes = $voteBreakdown[$candidate['original_id']][$college] ?? 0;
                                                                                                $rowTotal += $votes;
                                                                                                echo "<td>" . number_format($votes) . "</td>";
                                                                                            }
                                                                                            ?>
                                                                                            <td class="total-cell"><?php echo number_format($rowTotal); ?></td>
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

                                                <!-- Department Vote Breakdown per College (Local) -->
                                                <?php foreach ($colleges as $college):
                                                    if (isset($candidatesByLevel['Local'][$college]) && !empty($candidatesByLevel['Local'][$college])):
                                                        $collegeDepts = $departmentsByCollege[$college] ?? [];
                                                        if (empty($collegeDepts)) continue;
                                                ?>
                                                        <div class="row mt-5">
                                                            <div class="col-12">
                                                                <div class="card report-card">
                                                                    <div class="card-body" style="padding:0">
                                                                        <h4 class="card-title" style="padding:16px 20px 0;">Votes Breakdown by Department - <?php echo htmlspecialchars($college); ?></h4>
                                                                        <div class="table-responsive">
                                                                            <table class="table table-bordered table-hover report-table">
                                                                                <thead>
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
                                                                                            usort($candidatesByLevel['Local'][$college][$position], fn($a, $b) => $b['total'] <=> $a['total']);
                                                                                            foreach ($candidatesByLevel['Local'][$college][$position] as $candidate) {
                                                                                    ?>
                                                                                                <tr>
                                                                                                    <td><?php echo htmlspecialchars($position); ?></td>
                                                                                                    <td><?php echo htmlspecialchars($candidate['name']); ?></td>
                                                                                                    <td><?php echo htmlspecialchars($candidate['party']); ?></td>
                                                                                                    <?php
                                                                                                    $rowTotal = 0;
                                                                                                    foreach ($collegeDepts as $dept) {
                                                                                                        $votes = $voteBreakdownDept[$candidate['original_id']][$dept] ?? 0;
                                                                                                        $rowTotal += $votes;
                                                                                                        echo "<td>" . number_format($votes) . "</td>";
                                                                                                    }
                                                                                                    ?>
                                                                                                    <td class="total-cell"><?php echo number_format($rowTotal); ?></td>
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

                                                <!-- Party Vote Breakdown (Central) -->
                                                <div class="row mt-5">
                                                    <div class="col-12">
                                                        <div class="card report-card">
                                                            <div class="card-body">
                                                                <h4 class="card-title">Votes Breakdown by Party (Central Positions)</h4>
                                                                <div class="table-responsive">
                                                                    <table class="table table-bordered table-hover report-table">
                                                                        <thead>
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
                                                                            // Collect all parties present in Central candidates
                                                                            $centralParties = $party_names;
                                                                            foreach ($candidates as $cand) {
                                                                                if ($cand['level'] === 'Central') $centralParties[] = $cand['party'];
                                                                            }
                                                                            $centralParties = array_values(array_unique(array_filter(array_map('trim', $centralParties))));
                                                                            sort($centralParties);

                                                                            foreach ($centralParties as $party) {
                                                                            ?>
                                                                                <tr>
                                                                                    <td class="fw-semibold"><?php echo htmlspecialchars($party); ?></td>
                                                                                    <?php
                                                                                    $rowTotal = 0;
                                                                                    foreach ($colleges as $college) {
                                                                                        $votes = $partyBreakdownCollege[$party][$college] ?? 0;
                                                                                        $rowTotal += $votes;
                                                                                        echo "<td>" . number_format($votes) . "</td>";
                                                                                    }
                                                                                    ?>
                                                                                    <td class="total-cell"><?php echo number_format($rowTotal); ?></td>
                                                                                </tr>
                                                                            <?php } ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- ============================================================ -->
                                                <!-- CANDIDATE vs COLLEGE SUMMARY TABLE (Central)               -->
                                                <!-- Rows: candidates grouped by position                       -->
                                                <!-- Columns: each college + total                              -->
                                                <!-- Highlighted: highest vote getter per position              -->
                                                <!-- ============================================================ -->
                                                <div class="row mt-5">
                                                    <div class="col-12">
                                                        <div class="card report-card">
                                                            <div class="card-body">
                                                                <h4 class="card-title">Candidate Vote Summary by College (Central)</h4>
                                                                <p class="text-muted small mb-3">Rows are grouped by position. The candidate with the highest votes per position is highlighted in green.</p>
                                                                <div class="table-responsive">
                                                                    <table class="table table-bordered table-sm report-table" id="candidateCollegeSummaryTable">
                                                                        <thead>
                                                                            <tr>
                                                                                <th style="min-width:130px;">Position</th>
                                                                                <th style="min-width:180px;">Candidate</th>
                                                                                <th style="min-width:110px;">Party</th>
                                                                                <?php foreach ($colleges as $col): ?>
                                                                                    <th style="min-width:100px; font-size:0.78rem;"><?php echo htmlspecialchars($col); ?></th>
                                                                                <?php endforeach; ?>
                                                                                <th style="min-width:70px;">Total</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php
                                                                            foreach ($central_positions as $position):
                                                                                if (!isset($candidatesByLevel['Central'][$position])) continue;
                                                                                $posCands = $candidatesByLevel['Central'][$position];
                                                                                usort($posCands, fn($a, $b) => $b['total'] <=> $a['total']);
                                                                                $maxInPos = max(array_column($posCands, 'total'));
                                                                                $rowCount = count($posCands);
                                                                                $first    = true;
                                                                                foreach ($posCands as $cand):
                                                                                    $isWinner = ($cand['total'] == $maxInPos && $maxInPos > 0);
                                                                                    $rowClass = $isWinner ? 'winner-row' : '';
                                                                                    $rowTotal = 0;
                                                                            ?>
                                                                                    <tr class="<?php echo $rowClass; ?>">
                                                                                        <?php if ($first): ?>
                                                                                            <td rowspan="<?php echo $rowCount; ?>" class="pos-cell align-middle">
                                                                                                <?php echo htmlspecialchars($position); ?>
                                                                                            </td>
                                                                                        <?php $first = false;
                                                                                        endif; ?>
                                                                                        <td>
                                                                                            <?php if ($isWinner): ?>
                                                                                                <span class="badge bg-success me-1">★ Winner</span>
                                                                                            <?php endif; ?>
                                                                                            <?php echo htmlspecialchars($cand['name']); ?>
                                                                                        </td>
                                                                                        <td><?php echo htmlspecialchars($cand['party']); ?></td>
                                                                                        <?php foreach ($colleges as $col):
                                                                                            $v = $voteBreakdown[$cand['original_id']][$col] ?? 0;
                                                                                            $rowTotal += $v;
                                                                                            echo '<td>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                                                        endforeach; ?>
                                                                                        <td class="total-cell"><?php echo number_format($rowTotal); ?></td>
                                                                                    </tr>
                                                                                <?php endforeach; ?>
                                                                            <?php endforeach; ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Candidate vs College Summary Table (Local per college) -->
                                                <?php foreach ($colleges as $college):
                                                    if (empty($candidatesByLevel['Local'][$college])) continue;
                                                ?>
                                                    <div class="row mt-4">
                                                        <div class="col-12">
                                                            <div class="card report-card">
                                                                <div class="card-body">
                                                                    <h4 class="card-title">Candidate Vote Summary by Department — <?php echo htmlspecialchars($college); ?></h4>
                                                                    <p class="text-muted small mb-3">Votes received per candidate across departments within this college. Winner per position is highlighted.</p>
                                                                    <?php
                                                                    $collegeDepts = $departmentsByCollege[$college] ?? [];
                                                                    if (empty($collegeDepts)) {
                                                                        echo '<p class="text-muted">No department data available.</p>';
                                                                        continue;
                                                                    }
                                                                    ?>
                                                                    <div class="table-responsive">
                                                                        <table class="table table-bordered table-sm report-table">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th style="min-width:130px;">Position</th>
                                                                                    <th style="min-width:180px;">Candidate</th>
                                                                                    <th style="min-width:110px;">Party</th>
                                                                                    <?php foreach ($collegeDepts as $dept): ?>
                                                                                        <th style="min-width:100px; font-size:0.78rem;"><?php echo htmlspecialchars($dept); ?></th>
                                                                                    <?php endforeach; ?>
                                                                                    <th>Total</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                <?php
                                                                                foreach ($local_positions as $position):
                                                                                    if (empty($candidatesByLevel['Local'][$college][$position])) continue;
                                                                                    $posCands = $candidatesByLevel['Local'][$college][$position];
                                                                                    usort($posCands, fn($a, $b) => $b['total'] <=> $a['total']);
                                                                                    $maxInPos = max(array_column($posCands, 'total'));
                                                                                    $rowCount = count($posCands);
                                                                                    $first    = true;
                                                                                    foreach ($posCands as $cand):
                                                                                        $isWinner = ($cand['total'] == $maxInPos && $maxInPos > 0);
                                                                                        $rowClass = $isWinner ? 'winner-row' : '';
                                                                                        $rowTotal = 0;
                                                                                ?>
                                                                                        <tr class="<?php echo $rowClass; ?>">
                                                                                            <?php if ($first): ?>
                                                                                                <td rowspan="<?php echo $rowCount; ?>" class="pos-cell align-middle">
                                                                                                    <?php echo htmlspecialchars($position); ?>
                                                                                                </td>
                                                                                            <?php $first = false;
                                                                                            endif; ?>
                                                                                            <td>
                                                                                                <?php if ($isWinner): ?>
                                                                                                    <span class="badge bg-success me-1">★ Winner</span>
                                                                                                <?php endif; ?>
                                                                                                <?php echo htmlspecialchars($cand['name']); ?>
                                                                                            </td>
                                                                                            <td><?php echo htmlspecialchars($cand['party']); ?></td>
                                                                                            <?php foreach ($collegeDepts as $dept):
                                                                                                $v = $voteBreakdownDept[$cand['original_id']][$dept] ?? 0;
                                                                                                $rowTotal += $v;
                                                                                                echo '<td>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                                                            endforeach; ?>
                                                                                            <td class="total-cell"><?php echo number_format($rowTotal); ?></td>
                                                                                        </tr>
                                                                                    <?php endforeach; ?>
                                                                                <?php endforeach; ?>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>

                                                <!-- ============================================================ -->
                                                <!-- VOTES BY COURSE PER COLLEGE                                 -->
                                                <!-- ============================================================ -->
                                                <?php foreach ($colleges as $college):
                                                    $collegeCourses = $coursesByCollege[$college] ?? [];
                                                    // Gather all candidates (central + local for this college) that have course votes
                                                    $relevantCands  = [];
                                                    foreach ($candidatesByLevel['Central'] as $pos => $cands) {
                                                        foreach ($cands as $c) $relevantCands[] = $c;
                                                    }
                                                    if (!empty($candidatesByLevel['Local'][$college])) {
                                                        foreach ($candidatesByLevel['Local'][$college] as $pos => $cands) {
                                                            foreach ($cands as $c) $relevantCands[] = $c;
                                                        }
                                                    }
                                                    // Only show table if this college has course data AND has voters with votes
                                                    if (empty($collegeCourses)) continue;
                                                    $hasCourseVotes = false;
                                                    foreach ($relevantCands as $rc) {
                                                        foreach ($collegeCourses as $crs) {
                                                            if (!empty($voteBreakdownCourse[$rc['original_id']][$crs])) {
                                                                $hasCourseVotes = true;
                                                                break 2;
                                                            }
                                                        }
                                                    }
                                                    if (!$hasCourseVotes) continue;
                                                ?>
                                                    <div class="row mt-5">
                                                        <div class="col-12">
                                                            <div class="card report-card">
                                                                <div class="card-body">
                                                                    <h4 class="card-title">Votes by Course — <?php echo htmlspecialchars($college); ?></h4>
                                                                    <p class="text-muted small mb-3">Number of votes each candidate received from students enrolled in each course within this college.</p>
                                                                    <div class="table-responsive">
                                                                        <table class="table table-bordered table-hover table-sm report-table">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>Position</th>
                                                                                    <th>Candidate</th>
                                                                                    <th>Party</th>
                                                                                    <?php foreach ($collegeCourses as $crs): ?>
                                                                                        <th style="min-width:110px; font-size:0.78rem;"><?php echo htmlspecialchars($crs); ?></th>
                                                                                    <?php endforeach; ?>
                                                                                    <th>Total</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                <?php
                                                                                // Central candidates in this table
                                                                                foreach ($central_positions as $position):
                                                                                    if (empty($candidatesByLevel['Central'][$position])) continue;
                                                                                    $posCands = $candidatesByLevel['Central'][$position];
                                                                                    usort($posCands, fn($a, $b) => $b['total'] <=> $a['total']);
                                                                                    foreach ($posCands as $cand):
                                                                                        $rowTotal = 0;
                                                                                ?>
                                                                                        <tr>
                                                                                            <td><?php echo htmlspecialchars($position); ?> <span class="badge bg-primary ms-1">Central</span></td>
                                                                                            <td><?php echo htmlspecialchars($cand['name']); ?></td>
                                                                                            <td><?php echo htmlspecialchars($cand['party']); ?></td>
                                                                                            <?php foreach ($collegeCourses as $crs):
                                                                                                $v = $voteBreakdownCourse[$cand['original_id']][$crs] ?? 0;
                                                                                                $rowTotal += $v;
                                                                                                echo '<td>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                                                            endforeach; ?>
                                                                                            <td class="total-cell"><?php echo number_format($rowTotal); ?></td>
                                                                                        </tr>
                                                                                <?php endforeach;
                                                                                endforeach; ?>
                                                                                <?php
                                                                                // Local candidates for this specific college
                                                                                foreach ($local_positions as $position):
                                                                                    if (empty($candidatesByLevel['Local'][$college][$position])) continue;
                                                                                    $posCands = $candidatesByLevel['Local'][$college][$position];
                                                                                    usort($posCands, fn($a, $b) => $b['total'] <=> $a['total']);
                                                                                    foreach ($posCands as $cand):
                                                                                        $rowTotal = 0;
                                                                                ?>
                                                                                        <tr>
                                                                                            <td><?php echo htmlspecialchars($position); ?> <span class="badge bg-warning text-dark ms-1">Local</span></td>
                                                                                            <td><?php echo htmlspecialchars($cand['name']); ?></td>
                                                                                            <td><?php echo htmlspecialchars($cand['party']); ?></td>
                                                                                            <?php foreach ($collegeCourses as $crs):
                                                                                                $v = $voteBreakdownCourse[$cand['original_id']][$crs] ?? 0;
                                                                                                $rowTotal += $v;
                                                                                                echo '<td>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                                                            endforeach; ?>
                                                                                            <td class="total-cell"><?php echo number_format($rowTotal); ?></td>
                                                                                        </tr>
                                                                                <?php endforeach;
                                                                                endforeach; ?>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>

                                                <!-- ============================================================ -->
                                                <!-- VOTES BY MAJOR PER COURSE                                   -->
                                                <!-- ============================================================ -->
                                                <?php if (!empty($majorsByCourse)):
                                                    // Build a flat list of all courses that actually have majors in this election
                                                    $coursesWithMajors = [];
                                                    foreach ($majorsByCourse as $crsName => $majors) {
                                                        $coursesWithMajors[$crsName] = $majors;
                                                    }
                                                ?>
                                                    <div class="row mt-5">
                                                        <div class="col-12">
                                                            <div class="card report-card">
                                                                <div class="card-body">
                                                                    <h4 class="card-title">Votes by Major (per Course)</h4>
                                                                    <p class="text-muted small mb-3">Votes each candidate received broken down by student major, grouped under their respective courses.</p>
                                                                    <?php foreach ($coursesWithMajors as $crsName => $majors):
                                                                        // Check if any candidate has votes from this course's majors
                                                                        $hasMajorVotes = false;
                                                                        $allCands      = [];
                                                                        foreach ($candidatesByLevel['Central'] as $pos => $cands) foreach ($cands as $c) $allCands[] = $c;
                                                                        foreach ($candidatesByLevel['Local']   as $col => $positions) foreach ($positions as $pos => $cands) foreach ($cands as $c) $allCands[] = $c;
                                                                        foreach ($allCands as $c) {
                                                                            foreach ($majors as $m) {
                                                                                if (!empty($voteBreakdownMajor[$c['original_id']][$m])) {
                                                                                    $hasMajorVotes = true;
                                                                                    break 2;
                                                                                }
                                                                            }
                                                                        }
                                                                        if (!$hasMajorVotes) continue;
                                                                    ?>
                                                                        <h5 class="mt-4 mb-2"><strong><?php echo htmlspecialchars($crsName); ?></strong></h5>
                                                                        <div class="table-responsive mb-4">
                                                                            <table class="table table-bordered table-hover table-sm report-table">
                                                                                <thead>
                                                                                    <tr>
                                                                                        <th>Position</th>
                                                                                        <th>Candidate</th>
                                                                                        <th>Party</th>
                                                                                        <?php foreach ($majors as $maj): ?>
                                                                                            <th style="min-width:120px; font-size:0.78rem;"><?php echo htmlspecialchars($maj); ?></th>
                                                                                        <?php endforeach; ?>
                                                                                        <th>Total</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    <?php foreach ($allCands as $cand):
                                                                                        $rowTotal = 0;
                                                                                        $hasAny   = false;
                                                                                        foreach ($majors as $maj) if (!empty($voteBreakdownMajor[$cand['original_id']][$maj])) {
                                                                                            $hasAny = true;
                                                                                            break;
                                                                                        }
                                                                                        if (!$hasAny) continue;
                                                                                    ?>
                                                                                        <tr>
                                                                                            <td><?php echo htmlspecialchars($cand['position']); ?></td>
                                                                                            <td><?php echo htmlspecialchars($cand['name']); ?></td>
                                                                                            <td><?php echo htmlspecialchars($cand['party']); ?></td>
                                                                                            <?php foreach ($majors as $maj):
                                                                                                $v = $voteBreakdownMajor[$cand['original_id']][$maj] ?? 0;
                                                                                                $rowTotal += $v;
                                                                                                echo '<td>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                                                            endforeach; ?>
                                                                                            <td class="total-cell"><?php echo number_format($rowTotal); ?></td>
                                                                                        </tr>
                                                                                    <?php endforeach; ?>
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Party Vote Breakdown (Local) -->
                                                <div class="row mt-5">
                                                    <div class="col-12">
                                                        <div class="card report-card">
                                                            <div class="card-body">
                                                                <h4 class="card-title">Votes Breakdown by Party (Local Positions)</h4>
                                                                <div class="table-responsive">
                                                                    <table class="table table-bordered table-hover report-table">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Party</th>
                                                                                <?php foreach ($colleges as $college): ?>
                                                                                    <th style="min-width: 150px;"><?php echo htmlspecialchars($college); ?></th>
                                                                                <?php endforeach; ?>
                                                                                <th>Total</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php foreach ($centralParties as $party) { ?>
                                                                                <tr>
                                                                                    <td class="fw-semibold"><?php echo htmlspecialchars($party); ?></td>
                                                                                    <?php
                                                                                    $rowTotal = 0;
                                                                                    foreach ($colleges as $college) {
                                                                                        $votes = $partyBreakdownCollegeLocal[$party][$college] ?? 0;
                                                                                        $rowTotal += $votes;
                                                                                        echo "<td>" . number_format($votes) . "</td>";
                                                                                    }
                                                                                    ?>
                                                                                    <td class="total-cell"><?php echo number_format($rowTotal); ?></td>
                                                                                </tr>
                                                                            <?php } ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- ============================================================ -->
                                                <!-- COMBINED (CENTRAL + LOCAL) SUMMARY BY COLLEGE              -->
                                                <!-- ============================================================ -->
                                                <?php
                                                // Build a flat ordered list of all candidates: Central first, then Local sorted by college
                                                // Each entry: ['level', 'college', 'position', 'name', 'party', 'original_id', 'total']
                                                $allCombinedCandidates = [];

                                                // Determine ordering: Central positions first, then all local positions
                                                foreach ($central_positions as $pos) {
                                                    if (empty($candidatesByLevel['Central'][$pos])) continue;
                                                    $posCands = $candidatesByLevel['Central'][$pos];
                                                    usort($posCands, fn($a, $b) => $b['total'] <=> $a['total']);
                                                    foreach ($posCands as $c) {
                                                        $allCombinedCandidates[] = array_merge($c, ['level_label' => 'Central', 'sort_college' => '']);
                                                    }
                                                }
                                                foreach ($colleges as $col) {
                                                    if (empty($candidatesByLevel['Local'][$col])) continue;
                                                    foreach ($local_positions as $pos) {
                                                        if (empty($candidatesByLevel['Local'][$col][$pos])) continue;
                                                        $posCands = $candidatesByLevel['Local'][$col][$pos];
                                                        usort($posCands, fn($a, $b) => $b['total'] <=> $a['total']);
                                                        foreach ($posCands as $c) {
                                                            $allCombinedCandidates[] = array_merge($c, ['level_label' => 'Local', 'sort_college' => $col]);
                                                        }
                                                    }
                                                }
                                                ?>

                                                <div class="row mt-5">
                                                    <div class="col-12">
                                                        <div class="card report-card">
                                                            <div class="card-header">
                                                                <h4 class="mb-0"><i class="mdi mdi-table-large me-2"></i>Combined Candidate Summary — Votes by College (All Positions)</h4>
                                                                <small class="opacity-75">Central and Local candidates together. Winner per position highlighted in green. Zero-vote cells shown as —.</small>
                                                            </div>
                                                            <div class="card-body p-0">
                                                                <div class="table-responsive">
                                                                    <table class="table table-bordered table-sm mb-0" id="combinedCollegeTable">
                                                                        <thead>
                                                                            <tr>
                                                                                <th style="min-width:80px; position:sticky; left:0; z-index:2; background:#212529;">Level</th>
                                                                                <th style="min-width:140px; position:sticky; left:80px; z-index:2; background:#212529;">Position</th>
                                                                                <th style="min-width:170px;">Candidate</th>
                                                                                <th style="min-width:110px;">Party</th>
                                                                                <?php foreach ($colleges as $col): ?>
                                                                                    <th style="min-width:95px; font-size:0.75rem; line-height:1.2;"><?php echo htmlspecialchars($col); ?></th>
                                                                                <?php endforeach; ?>
                                                                                <th style="min-width:70px; background:#343a40; color:#fff;">Total</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php
                                                                            // Group by position key (position|college for local, just position for central)
                                                                            // to track winners and rowspan
                                                                            $posGroupKey   = null;
                                                                            $posGroupCount = 0;
                                                                            $posGroupMax   = 0;
                                                                            $posGroupLabel = '';

                                                                            // Pre-compute group metadata: for each position group, count rows and find max
                                                                            $groupMeta = []; // posKey => ['count' => n, 'max' => m, 'label' => str, 'level' => str]
                                                                            foreach ($allCombinedCandidates as $c) {
                                                                                $pkey = $c['level_label'] . '|' . $c['position'] . '|' . $c['sort_college'];
                                                                                if (!isset($groupMeta[$pkey])) {
                                                                                    $groupMeta[$pkey] = ['count' => 0, 'max' => 0, 'label' => $c['position'], 'level' => $c['level_label'], 'college' => $c['sort_college']];
                                                                                }
                                                                                $groupMeta[$pkey]['count']++;
                                                                                if ($c['total'] > $groupMeta[$pkey]['max']) $groupMeta[$pkey]['max'] = $c['total'];
                                                                            }

                                                                            $emittedPositions = []; // track which posKeys have had their first row rendered

                                                                            foreach ($allCombinedCandidates as $cand):
                                                                                $pkey     = $cand['level_label'] . '|' . $cand['position'] . '|' . $cand['sort_college'];
                                                                                $meta     = $groupMeta[$pkey];
                                                                                $isWinner = ($cand['total'] == $meta['max'] && $meta['max'] > 0);
                                                                                $isFirst  = !isset($emittedPositions[$pkey]);
                                                                                if ($isFirst) $emittedPositions[$pkey] = true;
                                                                                $rowClass = $isWinner ? 'winner-row' : '';
                                                                                $rowTotal = 0;
                                                                                $levelBadge = $cand['level_label'] === 'Central'
                                                                                    ? '<span class="badge bg-primary">Central</span>'
                                                                                    : '<span class="badge bg-warning text-dark">Local</span>';
                                                                            ?>
                                                                                <tr class="<?php echo $rowClass; ?>">
                                                                                    <?php if ($isFirst): ?>
                                                                                        <td rowspan="<?php echo $meta['count']; ?>" class="level-cell align-middle">
                                                                                            <?php echo $levelBadge; ?>
                                                                                            <?php if ($cand['level_label'] === 'Local'): ?>
                                                                                                <div style="font-size:0.7rem; margin-top:3px; color:#6c757d;"><?php echo htmlspecialchars($cand['sort_college']); ?></div>
                                                                                            <?php endif; ?>
                                                                                        </td>
                                                                                        <td rowspan="<?php echo $meta['count']; ?>" class="pos-cell align-middle">
                                                                                            <?php echo htmlspecialchars($cand['position']); ?>
                                                                                        </td>
                                                                                    <?php endif; ?>
                                                                                    <td>
                                                                                        <?php if ($isWinner): ?>
                                                                                            <span class="badge bg-success me-1" title="Highest votes in this position">★</span>
                                                                                        <?php endif; ?>
                                                                                        <?php echo htmlspecialchars($cand['name']); ?>
                                                                                    </td>
                                                                                    <td><?php echo htmlspecialchars($cand['party']); ?></td>
                                                                                    <?php foreach ($colleges as $col):
                                                                                        $v = $voteBreakdown[$cand['original_id']][$col] ?? 0;
                                                                                        $rowTotal += $v;
                                                                                        $cellClass = ($v > 0 && $isWinner) ? ' class="fw-semibold"' : '';
                                                                                        echo '<td' . $cellClass . '>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                                                    endforeach; ?>
                                                                                    <td class="total-cell"><?php echo number_format($rowTotal); ?></td>
                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- ============================================================ -->
                                                <!-- COMBINED (CENTRAL + LOCAL) SUMMARY BY DEPARTMENT           -->
                                                <!-- For each college: all departments of that college as cols  -->
                                                <!-- Central candidates appear in every college's table         -->
                                                <!-- Local candidates only appear in their own college table    -->
                                                <!-- ============================================================ -->
                                                <?php foreach ($colleges as $college):
                                                    $collegeDepts = $departmentsByCollege[$college] ?? [];
                                                    if (empty($collegeDepts)) continue;

                                                    // Gather candidates to show: Central (all) + Local for this college
                                                    $deptTableCands = [];
                                                    foreach ($central_positions as $pos) {
                                                        if (empty($candidatesByLevel['Central'][$pos])) continue;
                                                        $posCands = $candidatesByLevel['Central'][$pos];
                                                        usort($posCands, fn($a, $b) => $b['total'] <=> $a['total']);
                                                        foreach ($posCands as $c) {
                                                            $deptTableCands[] = array_merge($c, ['level_label' => 'Central', 'sort_college' => '']);
                                                        }
                                                    }
                                                    if (!empty($candidatesByLevel['Local'][$college])) {
                                                        foreach ($local_positions as $pos) {
                                                            if (empty($candidatesByLevel['Local'][$college][$pos])) continue;
                                                            $posCands = $candidatesByLevel['Local'][$college][$pos];
                                                            usort($posCands, fn($a, $b) => $b['total'] <=> $a['total']);
                                                            foreach ($posCands as $c) {
                                                                $deptTableCands[] = array_merge($c, ['level_label' => 'Local', 'sort_college' => $college]);
                                                            }
                                                        }
                                                    }

                                                    if (empty($deptTableCands)) continue;

                                                    // Check if any candidate has any dept votes for this college's depts
                                                    $hasDeptVotes = false;
                                                    foreach ($deptTableCands as $tc) {
                                                        foreach ($collegeDepts as $d) {
                                                            if (!empty($voteBreakdownDept[$tc['original_id']][$d])) {
                                                                $hasDeptVotes = true;
                                                                break 2;
                                                            }
                                                        }
                                                    }
                                                    if (!$hasDeptVotes) continue;

                                                    // Pre-compute group metadata for this college's table
                                                    $deptGroupMeta   = [];
                                                    $deptEmitted     = [];
                                                    foreach ($deptTableCands as $c) {
                                                        $pkey = $c['level_label'] . '|' . $c['position'] . '|' . $c['sort_college'];
                                                        if (!isset($deptGroupMeta[$pkey])) {
                                                            $deptGroupMeta[$pkey] = ['count' => 0, 'max' => 0, 'label' => $c['position'], 'level' => $c['level_label']];
                                                        }
                                                        $deptGroupMeta[$pkey]['count']++;
                                                        if ($c['total'] > $deptGroupMeta[$pkey]['max']) $deptGroupMeta[$pkey]['max'] = $c['total'];
                                                    }
                                                ?>
                                                    <div class="row mt-4">
                                                        <div class="col-12">
                                                            <div class="card report-card">
                                                                <div class="card-header">
                                                                    <h5 class="mb-0"><i class="mdi mdi-office-building me-2"></i>Combined Candidate Summary — Votes by Department</h5>
                                                                    <small class="opacity-75"><?php echo htmlspecialchars($college); ?> &nbsp;·&nbsp; Central candidates + Local candidates for this college</small>
                                                                </div>
                                                                <div class="card-body p-0">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-bordered table-sm mb-0">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th style="min-width:80px;">Level</th>
                                                                                    <th style="min-width:140px;">Position</th>
                                                                                    <th style="min-width:170px;">Candidate</th>
                                                                                    <th style="min-width:110px;">Party</th>
                                                                                    <?php foreach ($collegeDepts as $dept): ?>
                                                                                        <th style="min-width:95px; font-size:0.75rem; line-height:1.2;"><?php echo htmlspecialchars($dept); ?></th>
                                                                                    <?php endforeach; ?>
                                                                                    <th style="min-width:70px;">Total</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                <?php foreach ($deptTableCands as $cand):
                                                                                    $pkey     = $cand['level_label'] . '|' . $cand['position'] . '|' . $cand['sort_college'];
                                                                                    $meta     = $deptGroupMeta[$pkey];
                                                                                    $isWinner = ($cand['total'] == $meta['max'] && $meta['max'] > 0);
                                                                                    $isFirst  = !isset($deptEmitted[$pkey]);
                                                                                    if ($isFirst) $deptEmitted[$pkey] = true;
                                                                                    $rowClass   = $isWinner ? 'winner-row' : '';
                                                                                    $rowTotal   = 0;
                                                                                    $levelBadge = $cand['level_label'] === 'Central'
                                                                                        ? '<span class="badge bg-primary">Central</span>'
                                                                                        : '<span class="badge bg-warning text-dark">Local</span>';
                                                                                ?>
                                                                                    <tr class="<?php echo $rowClass; ?>">
                                                                                        <?php if ($isFirst): ?>
                                                                                            <td rowspan="<?php echo $meta['count']; ?>" class="level-cell align-middle">
                                                                                                <?php echo $levelBadge; ?>
                                                                                            </td>
                                                                                            <td rowspan="<?php echo $meta['count']; ?>" class="pos-cell align-middle">
                                                                                                <?php echo htmlspecialchars($cand['position']); ?>
                                                                                            </td>
                                                                                        <?php endif; ?>
                                                                                        <td>
                                                                                            <?php if ($isWinner): ?>
                                                                                                <span class="badge bg-success me-1" title="Highest votes in this position">★</span>
                                                                                            <?php endif; ?>
                                                                                            <?php echo htmlspecialchars($cand['name']); ?>
                                                                                        </td>
                                                                                        <td><?php echo htmlspecialchars($cand['party']); ?></td>
                                                                                        <?php foreach ($collegeDepts as $dept):
                                                                                            $v = $voteBreakdownDept[$cand['original_id']][$dept] ?? 0;
                                                                                            $rowTotal += $v;
                                                                                            $cellClass = ($v > 0 && $isWinner) ? ' class="fw-semibold"' : '';
                                                                                            echo '<td' . $cellClass . '>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                                                        endforeach; ?>
                                                                                        <td class="total-cell"><?php echo number_format($rowTotal); ?></td>
                                                                                    </tr>
                                                                                <?php endforeach; ?>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Voters List Modal -->
    <div class="modal fade" id="votersListModal" tabindex="-1" aria-labelledby="votersListModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="votersListModalLabel">Voters for <span id="modalCandidateName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="votersListContent">
                    <!-- Content loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/chart.js/Chart.min.js"></script>
    <script src="vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script src="vendors/progressbar.js/progressbar.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/hoverable-collapse.js"></script>
    <script src="js/template.js"></script>
    <script src="js/settings.js"></script>
    <script src="js/todolist.js"></script>
    <script src="js/dashboard.js"></script>
    <script src="js/Chart.roundedBarCharts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.view-voters-btn').click(function() {
                var candidateId = $(this).data('original-id');
                var votingPeriodId = $(this).data('voting-period-id');
                var candidateName = $(this).data('candidate-name');

                $('#modalCandidateName').text(candidateName);
                $('#votersListContent').html('<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
                $('#votersListModal').modal('show');

                $.ajax({
                    url: 'get_candidate_voters.php',
                    method: 'POST',
                    data: {
                        candidate_id: candidateId,
                        voting_period_id: votingPeriodId
                    },
                    success: function(response) {
                        $('#votersListContent').html(response);
                    },
                    error: function() {
                        $('#votersListContent').html('<p class="text-danger">Error loading voters data.</p>');
                    }
                });
            });
        });
    </script>

</body>

<button class="back-to-top" id="backToTop" title="Go to top">
    <i class="mdi mdi-arrow-up"></i>
</button>

</html>