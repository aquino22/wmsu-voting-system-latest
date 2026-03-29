<?php
session_start();
include('includes/conn.php');

// Enable error reporting during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---------------------------------------------
// Step 0: Check session role
// ---------------------------------------------
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'voter') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = "NON_VOTER";
    header("Location: ../index.php");
    exit();
}



$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : null;
$user_id = isset($_SESSION['email']) ? $_SESSION['email'] : null;
$student_id = null;

if ($user_email) {
    $stmt = $pdo->prepare("SELECT student_id, first_name, middle_name, last_name FROM voters WHERE email = ?");
    $stmt->execute([$user_email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $student_id = $result['student_id'];
        $_SESSION['user_id'] = $student_id;
        $first_name_student = $result['first_name'];
        $middle_name_student = $result['middle_name'];
        $last_name_student = $result['last_name'];
    }
}


// ---------------------------------------------
// Step 1: Get student's student_id and college
// ---------------------------------------------
$stmt = $pdo->prepare("SELECT student_id, college FROM voters WHERE email = ? LIMIT 1");
$stmt->execute([$user_email]);
$voterAccount = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$voterAccount) {
    die("Voter account not found.");
}

$student_id = $voterAccount['student_id'];
$college    = $voterAccount['college'];



// ---------------------------------------------
// Step 2: Get election_id from URL and validate
// ---------------------------------------------
$election_id = isset($_GET['election_id']) ? intval($_GET['election_id']) : 0;
if ($election_id === 0) {
    die("Invalid election ID.");
}

// ---------------------------------------------
// Step 3: Fetch voting_period row along with election and academic_year
// ---------------------------------------------
$stmt = $pdo->prepare("
    SELECT vp.*, 
           e.election_name, 
           ay.semester, 
           ay.start_date AS school_year_start, 
           ay.end_date AS school_year_end
    FROM voting_periods vp
    JOIN elections e ON vp.election_id = e.id
    JOIN academic_years ay ON e.academic_year_id = ay.id
    WHERE vp.id = ?
    LIMIT 1
");
$stmt->execute([$election_id]);
$votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$votingPeriod) {
    die("Voting period not found.");
}

// Assign variables
$election_name      = $votingPeriod['election_name'];
$votingPeriodStart  = $votingPeriod['start_period'];
$votingPeriodEnd    = $votingPeriod['end_period'];
$votingPeriodStatus = $votingPeriod['status'];

// Optional: additional info from academic_years
$semester           = $votingPeriod['semester'];
$schoolYearStart    = $votingPeriod['school_year_start'];
$schoolYearEnd      = $votingPeriod['school_year_end'];


// ---------------------------------------------
// Step 4: Fetch the voter’s correct precinct for this election
// ---------------------------------------------
$id = $_GET['election_id'];

$stmt = $pdo->prepare("
    SELECT 
        pv.precinct,
        p.name AS precinct_name,
        pv.status,
        v.college,
        v.department
    FROM precinct_voters pv
    LEFT JOIN voters v ON pv.student_id = v.student_id
    INNER JOIN precincts p ON p.id = pv.precinct
    INNER JOIN elections e ON p.election = e.id
    INNER JOIN voting_periods vp ON vp.election_id = e.id
    WHERE pv.student_id = ?
      AND vp.id = ?
      AND vp.status = 'Ongoing'
    LIMIT 1
");

$stmt->execute([$student_id, $election_id]);
$voter = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$voter) {

    echo "not a voter";
    $_SESSION['STATUS'] = "NOT_VOTER";

    $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: $redirect");
    exit();
}

// Extract final info
$precinct_name = $voter['precinct'];
$precinct_name_real = $voter['precinct_name'];
$status        = $voter['status']; // Not Voted / Voted / Revoted

// Load colleges (college_id => college_name)
$college_stmt = $pdo->query("SELECT college_id, college_name FROM colleges");
$colleges = $college_stmt->fetchAll(PDO::FETCH_KEY_PAIR); // id => name

// Load departments (department_id => department_name)
$department_stmt = $pdo->query("SELECT department_id, department_name FROM departments");
$departments = $department_stmt->fetchAll(PDO::FETCH_KEY_PAIR); // id => name

$college    = $colleges[$voter['college']] ?? 'N/A';
$department = $departments[$voter['department']] ?? 'N/A';

// ---------------------------------------------
// Step 5: Calculate remaining time for countdown (optional)
// ---------------------------------------------
date_default_timezone_set('Asia/Manila');
$current_date = date('Y-m-d H:i:s');

if (!empty($votingPeriod['re_start_period']) && !empty($votingPeriod['re_end_period'])) {
    $remaining_seconds = strtotime($votingPeriod['re_end_period']) - strtotime($current_date);
} else {
    $remaining_seconds = strtotime($votingPeriodEnd) - strtotime($current_date);
}

if ($remaining_seconds < 0) {
    $remaining_seconds = 0; // Election ended
}

// ---------------------------------------------
// Debugging info (remove or comment out in production)
// ---------------------------------------------


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU I-Elect </title>
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>




</head>

<style>
    /* Custom styles */
    .card {
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .qr-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px;
        max-width: 600px;
        margin: 0 auto;
    }

    #reader {
        width: 100% !important;
        max-width: 1000px;
        border-radius: 12px;
        overflow: hidden;
        border: 2px solid #e0e0e0;
        margin-bottom: 20px;
    }

    .scan-guide {
        background: linear-gradient(to right, #f8f9fa, #e9ecef);
        padding: 15px;
        border-radius: 10px;
        margin: 15px 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .countdown-container {
        background-color: #fff;
        padding: 10px 20px;
        border-radius: 50px;
        border: 1px solid #dee2e6;
        display: inline-flex;
        align-items: center;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }

    .timer-input {
        border: none;
        background: transparent;
        font-weight: bold;
        color: #B22222;
        font-size: 0.8rem;
        width: 100px;
        text-align: center;
    }

    .election-status {
        background: linear-gradient(45deg, #B22222, #ff5a5f);
        color: white;
        padding: 4px 15px;
        border-radius: 30px;
        font-size: 0.9rem;
        font-weight: 500;
        margin-left: 15px;
    }

    .scanner-header {
        position: relative;
        text-align: center;
        margin-bottom: 30px;
    }

    .scanner-header h2 {
        position: relative;
        display: inline-block;
        padding-bottom: 10px;
    }

    .scanner-header h2:after {
        content: '';
        position: absolute;
        left: 25%;
        bottom: 0;
        width: 50%;
        height: 3px;
        background: linear-gradient(to right, #B22222, transparent);
        border-radius: 2px;
    }

    .result-badge {
        background: #f8f9fa;
        padding: 10px 20px;
        border-radius: 8px;
        display: inline-block;
        border-left: 4px solid #B22222;
        font-weight: 500;
    }

    .scan-animation {
        width: 100%;
        height: 5px;
        background: linear-gradient(to right, transparent, #B22222, transparent);
        position: absolute;
        top: 0;
        left: 0;
        animation: scanMove 2s infinite;
        opacity: 0.7;
        border-radius: 2px;
    }

    @keyframes scanMove {
        0% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(300px);
        }

        100% {
            transform: translateY(0);
        }
    }

    /* Enhanced navigation styling */
    .navbar {
        background: linear-gradient(to right, #950000, #B22222);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .navbar .navbar-brand {
        color: white;
    }

    .sidebar .nav .nav-item.active-link>.nav-link {
        background-color: #B22222 !important;
        color: white !important;
    }

    .sidebar .nav .nav-item>.nav-link:hover {
        background-color: rgba(178, 34, 34, 0.1);
    }

    .active-menu-link {
        background-color: #B22222 !important;
        color: white !important;
    }

    /* Pulse effect for the active scanner */
    .pulse {
        position: relative;
    }

    .pulse:after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 15px;
        box-shadow: 0 0 0 0 rgba(178, 34, 34, 0.7);
        animation: pulse 2s infinite;
        z-index: -1;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(178, 34, 34, 0.7);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(178, 34, 34, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(178, 34, 34, 0);
        }
    }

    .profiler {
        max-width: 150px;
        height: auto;
        border-radius: 50%;
    }

    .bordered {
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 20px;
    }

    .spacer {
        margin-right: 10px;
    }

    .swal2-modal .swal2-icon,
    .swal2-modal .swal2-success-ring {
        margin-top: 15px !important;
        margin-bottom: 0px !important;
    }

    .election-ended-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.95);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        backdrop-filter: blur(5px);
    }

    .overlay-content {
        max-width: 500px;
        padding: 30px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        border-top: 5px solid #B22222;
    }

    .overlay-content h2 {
        color: #B22222;
        margin: 20px 0 10px;
    }

    .overlay-content p {
        color: #666;
        margin-bottom: 20px;
    }
</style>




<body>
    <div class="container-scroller">
        <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
                <div class="me-3">
                    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
                        <span class="icon-menu"></span>
                    </button>
                </div>
                <div>
                    <a class="navbar-brand brand-logo" href="index.php">
                        <img src="images/wmsu-logo.png" alt="logo" class="logo img-fluid" />
                        <small style="font-size: 16px;"><b>WMSU I-Elect</b></small>
                    </a>
                    <a class="navbar-brand brand-logo-mini" href="index.php">
                        <img src="images/wmsu-logo.png" class="logo img-fluid" alt="logo" />
                    </a>
                </div>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-top">
                <ul class="navbar-nav">
                    <li class="nav-item font-weight-semibold d-none d-lg-block ms-0">
                        <h1 class="welcome-text">
                            Welcome,
                            <span class="text-white fw-bold">
                                <?= $first_name_student . ' ' . $last_name_student ?>
                            </span>
                        </h1>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                        <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <img class="img-xs rounded-circle logo" src="images/wmsu-logo.png" style="background-color: white;" alt="Profile image">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">

                            <a class="dropdown-item" href="processes/accounts/logout.php"><i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign Out</a>
                        </div>
                    </li>
                </ul>
                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
                    <span class="mdi mdi-menu"></span>
                </button>
            </div>
        </nav>
        <div class="container-fluid page-body-wrapper">
            <nav class="sidebar sidebar-offcanvas" id="sidebar">
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link active-link" href="index.php">
                            <i class="mdi mdi-grid-large menu-icon"></i>
                            <span class="menu-title">Home</span>
                        </a>
                    </li>
                    <li class="nav-item ">
                        <a class="nav-link" href="candidacy.php">
                            <i class="mdi mdi-account menu-icon"></i>
                            <span class="menu-title">File Candidacy</span>
                        </a>
                    </li>
                    <li class="nav-item  active-link">
                        <a class="nav-link" href="choose_voting.php" style=" background-color: #B22222 !important;"">
                            <i class=" menu-icon mdi mdi-account-group" style="color: white !important;"></i>
                            <span class="menu-title" style="color: white !important;">Vote</span>
                        </a>
                    </li>
                </ul>
            </nav>


            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Voting</a>
                                        </li>
                                    </ul>
                                </div>


                                <?php if ($user_id && $status === 'verified'): ?>
                                    <div class="tab-content tab-content-basic">
                                        <div class="tab-pane fade show active" id="overview" role="tabpanel"
                                            aria-labelledby="overview">
                                            <?php

                                            $id = $_GET['election_id'];

                                            try {
                                                $stmt = $pdo->prepare("
    SELECT * 
    FROM voting_periods 
    WHERE status = 'Ongoing' AND id = ?
");

                                                $stmt->execute([$id]); // pass ID here

                                                $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC); // <-- fetch the row

                                                if (!$votingPeriod) {
                                                    echo "No ongoing voting period found.";
                                                    exit;
                                                }

                                                // Step 1: Get voting_period row
                                                $votingPeriodId = $votingPeriod['id'];

                                                $stmt = $pdo->prepare("
    SELECT vp.*, e.election_name, e.id AS election_id
    FROM voting_periods vp
    JOIN elections e ON vp.election_id = e.id
    WHERE vp.id = ?
    LIMIT 1
");
                                                $stmt->execute([$votingPeriodId]);
                                                $votingPeriodData = $stmt->fetch(PDO::FETCH_ASSOC);

                                                if (!$votingPeriodData) {
                                                    die("Voting period not found.");
                                                }

                                                // Now you can access:
                                                $electionName      = $votingPeriodData['election_id']; // election name
                                                $votingPeriodName      = $votingPeriodData['election_name']; // election name
                                                $votingPeriodStart  = $votingPeriodData['start_period'];
                                                $votingPeriodEnd    = $votingPeriodData['end_period'];
                                                $votingPeriodStatus = $votingPeriodData['status'];




                                                // Step 2: Get active registration form ID
                                                $stmt = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ?  LIMIT 1");
                                                $stmt->execute([$electionName]);
                                                $formId = $stmt->fetchColumn();
                                                if (!$formId) {
                                                    throw new Exception("No active registration form found for the ongoing voting period.");
                                                }



                                                // Step 3: Get accepted candidates' IDs
                                                if ($status == 'revoted') {

                                                    $stmt = $pdo->prepare(query: "
                                                        SELECT id 
                                                        FROM tied_candidates 
                                                        WHERE form_id = ? AND status = 'accepted'
                                                    ");
                                                } else {
                                                    $stmt = $pdo->prepare("
                                                        SELECT id 
                                                        FROM candidates 
                                                        WHERE form_id = ? AND status = 'accepted'
                                                    ");
                                                }
                                                $stmt->execute([$formId]);
                                                $candidateIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                                if (empty($candidateIds)) {
                                                    throw new Exception("No accepted candidates found.");
                                                }

                                                // Step 4: Fetch candidate data with position levels, department, and college
                                                $candidatesByLevel = ['Central' => [], 'Local' => []];
                                                foreach ($candidateIds as $candidateId) {
                                                    // Get candidate responses (including position)
                                                    $stmt = $pdo->prepare("
            SELECT cr.field_id, cr.value, ff.field_name 
            FROM candidate_responses cr 
            JOIN form_fields ff ON cr.field_id = ff.id 
            WHERE cr.candidate_id = ?
        ");
                                                    $stmt->execute([$candidateId]);
                                                    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                    // Get candidate files (e.g., photo)
                                                    $stmt = $pdo->prepare("
            SELECT file_path 
            FROM candidate_files 
            WHERE candidate_id = ? 
            LIMIT 1
        ");
                                                    $stmt->execute([$candidateId]);
                                                    $file = $stmt->fetch(PDO::FETCH_ASSOC);
                                                    $photoPath = $file ? $file['file_path'] : 'https://cdn.vectorstock.com/i/500p/08/19/gray-photo-placeholder-icon-design-ui-vector-35850819.jpg';

                                                    // Extract candidate details
                                                    $candidateData = [
                                                        'id' => $candidateId,
                                                        'photo' => $photoPath,
                                                        'name' => '',
                                                        'party' => '',
                                                        'position' => '',
                                                        'college' => '',
                                                        'department' => ''
                                                    ];

                                                    foreach ($responses as $response) {
                                                        if ($response['field_name'] === 'full_name') {
                                                            $candidateData['name'] = $response['value'];
                                                        } elseif ($response['field_name'] === 'party') {
                                                            $candidateData['party'] = $response['value'];
                                                        } elseif ($response['field_name'] === 'position') {
                                                            $candidateData['position'] = $response['value'];
                                                        }
                                                    }

                                                    // Determine position level
                                                    $stmt = $pdo->prepare("SELECT level FROM positions WHERE name = ? LIMIT 1");
                                                    $stmt->execute([$candidateData['position']]);
                                                    $positionLevel = $stmt->fetchColumn();
                                                    $level = $positionLevel === 'Central' ? 'Central' : 'Local';

                                                    // Fetch candidate's college and department from voters table by matching full name
                                                    $stmt = $pdo->prepare("
            SELECT college, department 
            FROM voters 
            WHERE CONCAT(
                TRIM(first_name), ' ',
                CASE WHEN middle_name IS NOT NULL AND middle_name != '' THEN CONCAT(TRIM(middle_name), ' ') ELSE '' END,
                TRIM(last_name)
            ) = ?
            LIMIT 1
        ");
                                                    $stmt->execute([$candidateData['name']]);
                                                    $voterData = $stmt->fetch(PDO::FETCH_ASSOC);

                                                    if ($voterData) {
                                                        $candidateData['college'] = $voterData['college'] ?: 'N/A';
                                                        $candidateData['department'] = $voterData['department'] ?: 'N/A';
                                                    } else {
                                                        $candidateData['college'] = 'N/A';
                                                        $candidateData['department'] = 'N/A';
                                                    }



                                                    // Assign candidates to levels, filtering Local by user's college
                                                    if ($level === 'Central') {
                                                        $candidatesByLevel['Central'][$candidateData['position']][] = $candidateData;
                                                    } elseif (
                                                        $level === 'Local'
                                                        || $voterData['college'] === $candidateData['college']
                                                    ) {   // filter by voter’s college
                                                        $candidatesByLevel['Local'][$candidateData['position']][] = $candidateData;
                                                    }
                                                }
                                            } catch (Exception $e) {
                                                echo "<div class='container-fluid text-center'><h3>Error: " . htmlspecialchars($e->getMessage()) . "</h3></div>";
                                                exit;
                                            }
                                            ?>


                                            <div class="card card-rounded">
                                                <div class="card-body">
                                                    <div class="">
                                                        <div class="d-flex align-items-center justify-content-between mb-4">
                                                            <div>
                                                                <h2 class="card-title card-title-dash">
                                                                    <?php echo htmlspecialchars($votingPeriodName); ?>
                                                                    <span class="election-status">Ongoing</span>
                                                                </h2>
                                                                <p class="text-muted">Precinct: <?php echo $precinct_name_real; ?></p>
                                                                <p class="text-muted">Your College: <?php echo htmlspecialchars($college ?? 'N/A'); ?></p>
                                                            </div>
                                                            <div class="d-flex align-items-center">
                                                                <div class="countdown-container me-3">
                                                                    <i class="mdi mdi-clock-outline me-2" style="color: #B22222;"></i>
                                                                    <input type="text" class="timer-input" id="secondsTimer" value="Loading..." readonly>
                                                                </div>
                                                                <script>
                                                                    const initialSeconds = <?= $remaining_seconds ?>;
                                                                </script>
                                                                <div class="countdown-container">
                                                                    <i class="mdi mdi-calendar me-2" style="color: #666;"></i>
                                                                    <input type="text" class="timer-input" style="color: #666;" id="DateEnding" value="<?php echo date('F d, Y'); ?>" readonly>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <br>

                                                        <?php



                                                        $stmt = $pdo->prepare("
    SELECT * 
    FROM precincts
    WHERE id = :id
    LIMIT 1
");
                                                        $stmt->execute(['id' => $precinct_name]);
                                                        $precinct = $stmt->fetch(PDO::FETCH_ASSOC);

                                                        ?>

                                                        <div class="container-fluid text-center">
                                                            <?php if ($precinct): ?>
                                                                <h3 class="text-center text-primary">Your Designated Precinct: <br><b>
                                                                        <?php echo strtoupper($precinct['name']); ?></b></h3>
                                                                <br>

                                                                <div class="row justify-content-center">
                                                                    <div class="col-md-8 spacer text-center bordered">
                                                                        <h3 class="text-danger"><b>Please Proceed to Your Designated Precinct!</b></h3>
                                                                        <br>

                                                                        <p class="lead">
                                                                            Please proceed to the <b><?php echo htmlspecialchars($precinct['name']); ?></b> precinct to cast your vote.
                                                                            Kindly present your QR code to the election moderator for verification and to proceed with voting!
                                                                        </p>

                                                                        <div class="mt-4">
                                                                            <i class="fas fa-map-marker-alt fa-5x text-primary"></i>
                                                                            <br><br>

                                                                            <div id="precinctMap" style="height: 400px; width: 100%; margin-top: 20px; margin-bottom: 20px;"></div>

                                                                            <!-- Display Precinct Details -->
                                                                            <h4 class="text-success">Precinct Location: <?php echo htmlspecialchars($precinct['location']); ?></h4>
                                                                            <p>College: <?php echo htmlspecialchars($college); ?></p>
                                                                            <p>Department: <?php echo htmlspecialchars($department); ?></p>


                                                                            <br>

                                                                        </div>

                                                                        <br><br>
                                                                    </div>
                                                                </div>
                                                            <?php else: ?>
                                                                <h1 class="text-center text-danger">Precinct Not Found</h1>
                                                                <p>No precinct found for the specified level.</p>
                                                            <?php endif; ?>
                                                        </div>

                                                    <?php endif; ?>



                                                    <?php if ($user_id && $status === 'pending' || $user_id && $status === 'revoted'): ?>

                                                        <?php
                                                        $vp_ongoing_id = $_GET['election_id'];
                                                        try {
                                                            // Step 1: Get ongoing voting period info
                                                            $stmt = $pdo->prepare("
        SELECT vp.id AS voting_period_id, vp.election_id, vp.start_period, vp.end_period
        FROM voting_periods vp
        WHERE vp.status = 'Ongoing' AND vp.id = ? 
        LIMIT 1
    ");
                                                            $stmt->execute([$vp_ongoing_id]);
                                                            $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);
                                                            if (!$votingPeriod) {
                                                                throw new Exception("No ongoing voting period found.");
                                                            }

                                                            $votingPeriodId = $votingPeriod['voting_period_id'];
                                                            $electionId = $votingPeriod['election_id'];

                                                            // Step 1a: Get election name from elections table
                                                            $stmt = $pdo->prepare("SELECT election_name FROM elections WHERE id = ? LIMIT 1");
                                                            $stmt->execute([$electionId]);
                                                            $votingPeriodName = $stmt->fetchColumn();
                                                            if (!$votingPeriodName) {
                                                                throw new Exception("Election not found for this voting period.");
                                                            }

                                                            // Step 2: Get active registration form ID
                                                            $stmt = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ? LIMIT 1");
                                                            $stmt->execute([$electionId]);
                                                            $formId = $stmt->fetchColumn();
                                                            if (!$formId) {
                                                                throw new Exception("No active registration form found for the ongoing voting period.");
                                                            }

                                                            // Step 3: Get accepted candidates' IDs
                                                            if ($status == 'revoted') {
                                                                $stmt = $pdo->prepare("
            SELECT id 
            FROM tied_candidates 
            WHERE form_id = ? AND status = 'accepted'
        ");
                                                            } else {
                                                                $stmt = $pdo->prepare("
            SELECT id 
            FROM candidates 
            WHERE form_id = ? AND status = 'accepted'
        ");
                                                            }
                                                            $stmt->execute([$formId]);
                                                            $candidateIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                                            if (empty($candidateIds)) {
                                                                throw new Exception("No accepted candidates found.");
                                                            }

                                                            // Step 4: Fetch candidate data with position levels, college, and department
                                                            $candidatesByLevel = ['Central' => [], 'Local' => []];
                                                            foreach ($candidateIds as $candidateId) {
                                                                // Candidate responses
                                                                $stmt = $pdo->prepare("
            SELECT cr.field_id, cr.value, ff.field_name 
            FROM candidate_responses cr 
            JOIN form_fields ff ON cr.field_id = ff.id 
            WHERE cr.candidate_id = ?
        ");
                                                                $stmt->execute([$candidateId]);
                                                                $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                                // Candidate photo
                                                                $stmt = $pdo->prepare("
            SELECT file_path 
            FROM candidate_files 
            WHERE candidate_id = ? 
            LIMIT 1
        ");
                                                                $stmt->execute([$candidateId]);
                                                                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                                                                $photoPath = $file ? $file['file_path'] : 'https://cdn.vectorstock.com/i/500p/08/19/gray-photo-placeholder-icon-design-ui-vector-35850819.jpg';

                                                                // Extract candidate details
                                                                $candidateData = [
                                                                    'id' => $candidateId,
                                                                    'photo' => $photoPath,
                                                                    'name' => '',
                                                                    'party' => '',
                                                                    'position' => '',
                                                                    'college' => 'N/A',
                                                                    'department' => 'N/A',
                                                                    'student_id' => null
                                                                ];

                                                                foreach ($responses as $response) {
                                                                    if ($response['field_name'] === 'full_name') $candidateData['name'] = $response['value'];
                                                                    elseif ($response['field_name'] === 'party') $candidateData['party'] = $response['value'];
                                                                    elseif ($response['field_name'] === 'position') $candidateData['position'] = $response['value'];
                                                                    elseif ($response['field_name'] === 'student_id') $candidateData['student_id'] = $response['value'];
                                                                }

                                                                // Position level
                                                                $stmt = $pdo->prepare("SELECT level FROM positions WHERE name = ? LIMIT 1");
                                                                $stmt->execute([$candidateData['position']]);
                                                                $positionLevel = $stmt->fetchColumn();
                                                                $level = $positionLevel === 'Central' ? 'Central' : 'Local';

                                                                // Candidate college & department
                                                                if ($candidateData['student_id']) {

                                                                    $stmt = $pdo->prepare("
        SELECT 
            c.college_name,
            d.department_name
        FROM voters v
        LEFT JOIN colleges c ON v.college = c.college_id
        LEFT JOIN departments d ON v.department = d.department_id
        WHERE v.student_id = ?
        LIMIT 1
    ");

                                                                    $stmt->execute([$candidateData['student_id']]);
                                                                    $voterData = $stmt->fetch(PDO::FETCH_ASSOC);

                                                                    if ($voterData) {
                                                                        $candidateData['college'] = $voterData['college_name'] ?: 'N/A';
                                                                        $candidateData['department'] = $voterData['department_name'] ?: 'N/A';
                                                                    }
                                                                }


                                                                // Assign to level (filter Local by user's college)
                                                                if ($level === 'Central') {
                                                                    $candidatesByLevel['Central'][$candidateData['position']][] = $candidateData;
                                                                } elseif ($level === 'Local' && $candidateData['college'] === $college) {
                                                                    $candidatesByLevel['Local'][$candidateData['position']][] = $candidateData;
                                                                }
                                                            }
                                                        } catch (Exception $e) {
                                                            echo "<div class='alert alert-danger'>Error loading candidates: " . htmlspecialchars($e->getMessage()) . "</div>";
                                                        } catch (Exception $e) {
                                                            echo "<div class='container-fluid text-center'><h3>Error: " . htmlspecialchars($e->getMessage()) . "</h3></div>";
                                                            exit;
                                                        }

                                                        // Set timezone
                                                        date_default_timezone_set('Asia/Manila');
                                                        $current_date = date('Y-m-d H:i:s');

                                                        // Fetch ongoing voting period
                                                        $stmt = $pdo->prepare("
    SELECT vp.*, e.election_name 
    FROM voting_periods vp
    JOIN elections e ON vp.election_id = e.id
    WHERE vp.status = 'Ongoing' AND vp.id = ?
    LIMIT 1
");
                                                        $stmt->execute([$vp_ongoing_id]);
                                                        $voting = $stmt->fetch(PDO::FETCH_ASSOC);

                                                        if ($voting) {
                                                            $votingPeriodId     = $voting['id'];
                                                            $electionId         = $voting['election_id'];
                                                            $votingPeriodName   = $voting['election_name'];
                                                            $votingPeriodStart  = $voting['start_period'];
                                                            $votingPeriodEnd    = $voting['end_period'] ?: 'TBD';
                                                            $votingPeriodStatus = $voting['status'];
                                                            $votingPeriodReStart = $voting['re_start_period'] ?: null;
                                                            $votingPeriodReEnd   = $voting['re_end_period'] ?: null;

                                                            // Calculate remaining seconds
                                                            if ($votingPeriodReStart && $votingPeriodReEnd) {
                                                                if ($votingPeriodStatus === 'Ongoing') {
                                                                    $remaining_seconds = strtotime($votingPeriodReEnd) - strtotime($current_date);
                                                                } elseif ($votingPeriodStatus === 'Scheduled') {
                                                                    $remaining_seconds = strtotime($votingPeriodReStart) - strtotime($current_date);
                                                                } else {
                                                                    $remaining_seconds = 0;
                                                                }
                                                            } else {
                                                                if ($votingPeriodStatus === 'Ongoing') {
                                                                    $remaining_seconds = strtotime($votingPeriodEnd) - strtotime($current_date);
                                                                } elseif ($votingPeriodStatus === 'Scheduled') {
                                                                    $remaining_seconds = strtotime($votingPeriodStart) - strtotime($current_date);
                                                                } else {
                                                                    $remaining_seconds = 0;
                                                                }
                                                            }

                                                            // Ensure non-negative
                                                            $remaining_seconds = max(0, $remaining_seconds);
                                                        } else {
                                                            $votingPeriodId     = null;
                                                            $votingPeriodName   = null;
                                                            $votingPeriodStart  = null;
                                                            $votingPeriodEnd    = null;
                                                            $votingPeriodStatus = null;
                                                            $votingPeriodReStart = null;
                                                            $votingPeriodReEnd   = null;
                                                            $remaining_seconds   = 0;
                                                        }


                                                        ?>

                                                        <div class="card card-rounded" style="margin-top: 20px">
                                                            <div class="card-body">
                                                                <div class="">
                                                                    <div class="d-flex align-items-center justify-content-between mb-4">
                                                                        <div>
                                                                            <h2 class="card-title card-title-dash">
                                                                                <?php echo htmlspecialchars($votingPeriodName); ?>
                                                                                <span class="election-status">Ongoing</span>
                                                                            </h2>
                                                                            <p class="text-muted">Precinct: <?php echo htmlspecialchars($precinct_name_real); ?></p>
                                                                            <p class="text-muted">Your College: <?php echo htmlspecialchars($college ?? 'N/A'); ?></p>
                                                                        </div>
                                                                        <div class="d-flex align-items-center">
                                                                            <div class="countdown-container me-3">
                                                                                <i class="mdi mdi-clock-outline me-2" style="color: #B22222;"></i>
                                                                                <input type="text" class="timer-input" id="secondsTimer" value="Loading..." readonly>
                                                                            </div>
                                                                            <script>
                                                                                const initialSeconds = <?= $remaining_seconds ?>;
                                                                            </script>
                                                                            <div class="countdown-container">
                                                                                <i class="mdi mdi-calendar me-2" style="color: #666;"></i>
                                                                                <input type="text" class="timer-input" style="color: #666;" id="DateEnding"
                                                                                    value="<?php
                                                                                            if (isset($votingPeriodReStart) && isset($votingPeriodReEnd)) {
                                                                                                echo $votingPeriodStatus === 'Ongoing'
                                                                                                    ? date('F d, Y', strtotime($votingPeriodReEnd))
                                                                                                    : date('F d, Y', strtotime($votingPeriodReStart));
                                                                                            } else {
                                                                                                echo $votingPeriodStatus === 'Ongoing'
                                                                                                    ? date('F d, Y', strtotime($votingPeriodEnd))
                                                                                                    : date('F d, Y', strtotime($votingPeriodStart));
                                                                                            }
                                                                                            ?>"
                                                                                    readonly>

                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <br>

                                                                    <form id="voteForm" method="POST" action="submit_vote.php">
                                                                        <input type="hidden" name="voting_period_id" value="<?php echo $votingPeriodId; ?>">
                                                                        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                                                                        <input type="hidden" name="precinct" value="<?php echo $precinct_name; ?>">

                                                                        <div class="container-fluid text-center">
                                                                            <?php foreach (['Central', 'Local'] as $level): ?>
                                                                                <?php if (!empty($candidatesByLevel[$level])): ?>
                                                                                    <h1 class="text-center text-primary"><?php echo strtoupper($level); ?></h1>
                                                                                    <br>
                                                                                    <div class="row">
                                                                                        <?php foreach ($candidatesByLevel[$level] as $position => $candidates): ?>
                                                                                            <div class="col spacer text-center bordered mr-2">
                                                                                                <h3 class="text-danger"><b><?php echo htmlspecialchars(strtoupper($position)); ?></b></h3>
                                                                                                <br>
                                                                                                <div class="row">
                                                                                                    <?php foreach ($candidates as $candidate): ?>
                                                                                                        <div class="col">
                                                                                                            <?php
                                                                                                            $photo = $candidate['photo'] ?? '';
                                                                                                            $loginPath = "../login/uploads/candidates/" . $photo;
                                                                                                            $adminPath = "../admin/uploads/candidates/" . $photo;

                                                                                                            if (!empty($photo) && file_exists($loginPath)) {
                                                                                                                $candidateImage = $loginPath;
                                                                                                            } elseif (!empty($photo) && file_exists($adminPath)) {
                                                                                                                $candidateImage = $adminPath;
                                                                                                            } else {
                                                                                                                $candidateImage = "admin/uploads/candidates/default.jpg"; // Fallback image
                                                                                                            }
                                                                                                            ?>

                                                                                                            <img src="<?= htmlspecialchars($candidateImage) ?>" class="profiler" alt="Candidate Photo">
                                                                                                            <br><br>
                                                                                                            <h4><b><?php echo htmlspecialchars($candidate['name']); ?></b></h4>
                                                                                                            <h6 class="text-success text-small"><?php echo htmlspecialchars($candidate['party']); ?></h6>
                                                                                                            <?php if ($level === 'Central'): ?>
                                                                                                                <p>
                                                                                                                    <b>
                                                                                                                        <?php echo htmlspecialchars($candidate['college']); ?> | <?php echo htmlspecialchars($candidate['department']); ?>
                                                                                                                    </b>
                                                                                                                </p>
                                                                                                            <?php elseif ($level === 'Local'): ?>
                                                                                                                <p>
                                                                                                                    <b>
                                                                                                                        <?php echo htmlspecialchars($candidate['college']); ?> | <?php echo htmlspecialchars($candidate['department']); ?>
                                                                                                                    </b>
                                                                                                                </p>
                                                                                                            <?php endif; ?>

                                                                                                            <label>
                                                                                                                <input type="radio"
                                                                                                                    name="vote[<?php echo htmlspecialchars($position); ?>]"
                                                                                                                    value="<?php echo $candidate['id']; ?>">
                                                                                                                Vote
                                                                                                            </label>
                                                                                                        </div>
                                                                                                    <?php endforeach; ?>
                                                                                                </div>
                                                                                            </div>
                                                                                        <?php endforeach; ?>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                            <?php endforeach; ?>
                                                                            <br>
                                                                            <button type="submit" class="btn btn-success text-white">Submit Vote</button>
                                                                        </div>
                                                                    </form>

                                                                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                                                    <script>
                                                                        document.getElementById('voteForm').addEventListener('submit', function(e) {
                                                                            e.preventDefault();

                                                                            const form = this;
                                                                            const formData = new FormData(form);
                                                                            let receiptHtml = `
<div style="overflow-x:auto; max-width:100%;">
    <h3 style="font-size:18px;">Your Voting Receipt</h3><br>
    <table style="width:100%; border-collapse:collapse; word-break:break-word;">
        <thead>
            <tr>
                <th style="border:1px solid #ddd; padding:8px;">Position</th>
                <th style="border:1px solid #ddd; padding:8px;">Candidate</th>
                <th style="border:1px solid #ddd; padding:8px;">Party</th>
            </tr>
        </thead>
        <tbody>
`;

                                                                            const candidates = {
                                                                                <?php
                                                                                foreach (['Central', 'Local'] as $level) {
                                                                                    foreach ($candidatesByLevel[$level] as $position => $candidates) {
                                                                                        foreach ($candidates as $candidate) {
                                                                                            echo "'{$candidate['id']}': { name: '" . addslashes($candidate['name']) . "', party: '" . addslashes($candidate['party']) . "', position: '" . addslashes($position) . "' },";
                                                                                        }
                                                                                    }
                                                                                }
                                                                                ?>
                                                                            };

                                                                            for (const [key, value] of formData.entries()) {
                                                                                if (key.startsWith('vote[')) {
                                                                                    const position = key.replace('vote[', '').replace(']', '');
                                                                                    const candidateId = value;
                                                                                    if (candidates[candidateId]) {
                                                                                        receiptHtml += `
<tr>
    <td style="border:1px solid #ddd; padding:8px; word-break:break-word; font-size:12px;">${candidates[candidateId].position}</td>
    <td style="border:1px solid #ddd; padding:8px; word-break:break-word; font-size:14px;">${candidates[candidateId].name}</td>
    <td style="border:1px solid #ddd; padding:8px; word-break:break-word; font-size:12px;">${candidates[candidateId].party}</td>
</tr>`;
                                                                                        hasVotes = true;
                                                                                    }
                                                                                }
                                                                            }

                                                                            receiptHtml += '</tbody></table>';

                                                                            if (!hasVotes) {
                                                                                Swal.fire({
                                                                                    title: 'No Votes Selected',
                                                                                    text: 'Please select at least one candidate before submitting.',
                                                                                    icon: 'warning',
                                                                                    confirmButtonText: 'OK'
                                                                                });
                                                                                return;
                                                                            }

                                                                            Swal.fire({
                                                                                title: 'Please Review Your Vote',
                                                                                html: receiptHtml,
                                                                                icon: 'info',
                                                                                showCancelButton: true,
                                                                                confirmButtonText: 'Yes, Submit',
                                                                                cancelButtonText: 'No, Edit',
                                                                                allowOutsideClick: false,
                                                                            }).then((result) => {
                                                                                if (result.isConfirmed) {
                                                                                    Swal.fire({
                                                                                        title: 'Submitting...',
                                                                                        text: 'Please wait while your vote is being processed.',
                                                                                        icon: 'info',
                                                                                        allowOutsideClick: false,
                                                                                        showConfirmButton: false,
                                                                                        willOpen: () => {
                                                                                            Swal.showLoading();
                                                                                        }
                                                                                    });

                                                                                    fetch('submit_vote.php', {
                                                                                            method: 'POST',
                                                                                            body: formData
                                                                                        })
                                                                                        .then(response => response.json())
                                                                                        .then(data => {
                                                                                            if (data.status === 'success') {
                                                                                                Swal.fire({
                                                                                                    title: 'Success',
                                                                                                    text: data.message,
                                                                                                    icon: 'success',
                                                                                                    allowOutsideClick: false,
                                                                                                    confirmButtonText: 'OK'
                                                                                                }).then(() => {
                                                                                                    window.location.reload();
                                                                                                });
                                                                                            } else {
                                                                                                Swal.fire({
                                                                                                    title: 'Error',
                                                                                                    text: data.message,
                                                                                                    icon: 'error',
                                                                                                    confirmButtonText: 'OK'
                                                                                                });
                                                                                            }
                                                                                        })
                                                                                        .catch(error => {
                                                                                            Swal.fire({
                                                                                                title: 'Error',
                                                                                                text: 'Failed to submit vote: ' + error.message,
                                                                                                icon: 'error',
                                                                                                confirmButtonText: 'OK'
                                                                                            });
                                                                                        });
                                                                                }
                                                                            });
                                                                        });
                                                                    </script>

                                                                    <style>
                                                                        .profiler {
                                                                            max-width: 150px;
                                                                            height: auto;
                                                                            border-radius: 50%;
                                                                        }

                                                                        .bordered {
                                                                            border: 1px solid #ddd;
                                                                            padding: 15px;
                                                                            margin-bottom: 20px;
                                                                        }

                                                                        .spacer {
                                                                            margin-right: 10px;
                                                                        }
                                                                    </style>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>


                                                    <?php if ($user_id && $status === 'voted'): ?>
                                                        <div class="container-fluid">
                                                            <div class="card card-rounded">
                                                                <div class="card-body">
                                                                    <div class="voted-container text-center">
                                                                        <span class="voted-icon">✔</span>
                                                                        <h1 class="mt-3">Thank You for Voting!</h1>
                                                                        <p class="lead">Your vote has been successfully recorded.</p>
                                                                        <?php
                                                                        try {
                                                                            $current_date = date('Y-m-d H:i:s');

                                                                            // Get the ongoing voting period based on status AND current time
                                                                            $stmt = $pdo->prepare("
        SELECT name, start_period, end_period 
        FROM voting_periods 
        WHERE status = 'Ongoing' 
          AND :current_date BETWEEN start_period AND end_period
        LIMIT 1
    ");
                                                                            $stmt->execute(['current_date' => $current_date]);
                                                                            $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);
                                                                            $votingPeriodName = $votingPeriod ? $votingPeriod['name'] : 'Current Election';

                                                                            // Get precinct from precinct_voters (only students who haven't voted yet, or voted? adjust status as needed)
                                                                            $stmt = $pdo->prepare("
        SELECT precinct 
        FROM precinct_voters 
        WHERE student_id = ? 
        LIMIT 1
    ");
                                                                            $stmt->execute([$student_id]);
                                                                            $precinct = $stmt->fetchColumn() ?: 'Unknown Precinct';
                                                                        } catch (Exception $e) {
                                                                            $votingPeriodName = 'Current Election';
                                                                            $precinct = 'Unknown Precinct';
                                                                        }
                                                                        ?>

                                                                        <p><strong>Election:</strong> <?php echo htmlspecialchars($votingPeriodName); ?></p>
                                                                        <p><strong>Precinct:</strong> <?php echo htmlspecialchars($precinct_name_real); ?></p>
                                                                        <p class="text-muted">Your participation helps shape the future. Thank you!</p>
                                                                        <a href="index.php" class="btn btn-primary btn-home text-white">Return Home</a>
                                                                    </div>
                                                                </div>
                                                                <div id="electionEndedOverlay" class="election-ended-overlay" style="display: none;">
                                                                    <div class="overlay-content">
                                                                        <i class="mdi mdi-calendar-remove" style="font-size: 4rem; color: #B22222;"></i>
                                                                        <h2>Voting Period Has Ended</h2>
                                                                        <p>The election voting period is now closed. You can no longer submit votes.</p>
                                                                        <a href="index.php" class="btn btn-primary">Return to Dashboard</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <style>
                                                            .voted-container {
                                                                padding: 40px;
                                                                background: white;
                                                                border-radius: 10px;
                                                                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                                                                max-width: 600px;
                                                                margin: 0 auto;
                                                            }

                                                            .voted-icon {
                                                                font-size: 60px;
                                                                color: #28a745;
                                                            }

                                                            .btn-home {
                                                                margin-top: 20px;
                                                            }
                                                        </style>
                                                    <?php endif; ?>

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
    </div>


    <?php if ($user_id && $status === 'unverified'): ?>
        <div class="modal fade" id="corUploadModal" tabindex="-1" aria-labelledby="corUploadModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="corUploadModalLabel">Upload Certificate of Registration</h5>
                    </div>
                    <div class="modal-body">
                        <p>Your voter status is <b>'unverified'</b>. Please upload a picture of your Certificate of Registration (COR) for verification.</p>
                        <form method="POST" enctype="multipart/form-data" id="corUploadForm">
                            <div class="mb-3">
                                <label for="corImage" class="form-label">Select COR Image</label>
                                <input type="file" class="form-control" id="corImage" name="cor_image" accept="image/jpeg,image/png,image/gif" required>
                            </div>
                            <button type="submit" class="btn btn-primary" id="uploadBtn">Upload</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log('Modal should show for user_id: <?php echo $user_id; ?>, status: <?php echo $status; ?>');
                try {
                    var modal = new bootstrap.Modal(document.getElementById('corUploadModal'), {
                        backdrop: 'static',
                        keyboard: false
                    });
                    modal.show();
                } catch (e) {
                    console.error('Failed to show modal:', e);
                }
            });
        </script>
    <?php endif; ?>

    <?php
    // Handle file upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cor_image'])) {
        // Define allowed mime types
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['cor_image']['tmp_name']);
        $fileSizeLimit = 5 * 1024 * 1024; // 5MB limit

        // Validate file
        if (!in_array($fileType, $allowedTypes)) {
            echo "<script>
                Swal.fire({
                    title: 'Error',
                    text: 'Only JPEG, PNG, and GIF images are allowed.',
                    icon: 'error',
                    confirmButtonText: 'Try Again'
                });
              </script>";
            exit;
        }

        if ($_FILES['cor_image']['size'] > $fileSizeLimit) {
            echo "<script>
                Swal.fire({
                    title: 'Error',
                    text: 'File size exceeds 5MB limit.',
                    icon: 'error',
                    confirmButtonText: 'Try Again'
                });
              </script>";
            exit;
        }

        // Define upload directory
        $uploadDir = __DIR__ . '/../cor_reader/test/uploads/cor/';
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                echo "<script>
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to create upload directory.',
                        icon: 'error',
                        confirmButtonText: 'Contact Support'
                    });
                  </script>";
                exit;
            }
        }

        // Sanitize and create unique filename
        $fileExt = strtolower(pathinfo($_FILES['cor_image']['name'], PATHINFO_EXTENSION));
        $tempFileName = 'cor_' . $user_id . '_' . time() . '.' . $fileExt;
        $tempFilePath = $uploadDir . $tempFileName;

        // Move uploaded file
        if (move_uploaded_file($_FILES['cor_image']['tmp_name'], $tempFilePath)) {
            // Construct relative path for redirect
            $relativePath = 'uploads/cor/' . $tempFileName;
            $redirectUrl = "../cor_reader/test/test.php?file=" . urlencode($relativePath);

            echo "<script>
                Swal.fire({
                    title: 'Success',
                    text: 'File uploaded successfully. Processing...',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '$redirectUrl';
                });
              </script>";
            exit;
        } else {
            echo "<script>
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to upload file. Check server permissions.',
                    icon: 'error',
                    confirmButtonText: 'Try Again'
                });
              </script>";
            exit;
        }
    }
    ?>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            <?php if (isset($_SESSION['STATUS'])): ?>

                // ✅ Successful Verification
                <?php if ($_SESSION['STATUS'] === "COR_VERIFIED_SUCCESFULLY"): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Certificate of Registration has been verified succesfully!',
                        text: 'Your Certificate of Registration has been scanned and detected to be of the right grading and semester in line with this voting period!',
                        confirmButtonText: 'OK'

                    });

                    // ✅ Invalid COR
                <?php elseif ($_SESSION['STATUS'] === "INVALID_COR"): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Certificate of Registration',
                        text: 'One or more required fields are missing.',
                        confirmButtonText: 'Go Back'
                    }).then(() => {
                        modal.show();
                    });

                    // ✅ Student ID Not Found
                <?php elseif ($_SESSION['STATUS'] === "STUDENT_ID_NOT_FOUND"): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Student ID Not Found',
                        text: 'The student ID was not detected.',
                        confirmButtonText: 'Go Back'
                    }).then(() => {
                        modal.show();
                    });

                    // ✅ Semester/School Year Not Found
                <?php elseif ($_SESSION['STATUS'] === "SEM_SY_NOT_FOUND"): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Semester/School Year Not Found',
                        text: 'The Semester or School Year is missing.',
                        confirmButtonText: 'Go Back'
                    }).then(() => {
                        modal.show();
                    });

                    // ✅ Database Error
                <?php elseif ($_SESSION['STATUS'] === "DB_ERROR"): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Database Error',
                        text: '<?= $_SESSION['MESSAGE'] ?>',
                        confirmButtonText: 'Go Back'
                    }).then(() => {
                        modal.show();
                    });

                    // ✅ Exception
                <?php elseif ($_SESSION['STATUS'] === "EXCEPTION"): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Unexpected Error',
                        text: '<?= $_SESSION['MESSAGE'] ?>',
                        confirmButtonText: 'Go Back'
                    }).then(() => {
                        modal.show();
                    });

                <?php endif; ?>

                <?php $_SESSION['STATUS'] = ""; ?>
            <?php endif; ?>
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the timer input element
            const timerInput = document.getElementById('secondsTimer');

            // Function to format time into days, hours, minutes, seconds
            function formatTime(seconds) {
                if (seconds <= 0) return "Election Ended";

                const days = Math.floor(seconds / (3600 * 24));
                const hours = Math.floor((seconds % (3600 * 24)) / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const secs = Math.floor(seconds % 60);

                return `${days}d ${hours}h ${minutes}m ${secs}s`;
            }

            // Set initial value
            let timeLeft = initialSeconds;
            timerInput.value = formatTime(timeLeft);

            // Update timer every second
            if (timeLeft > 0) {
                const countdown = setInterval(() => {
                    timeLeft--;
                    timerInput.value = formatTime(timeLeft);

                    // Stop timer when it reaches 0
                    if (timeLeft <= 0) {
                        clearInterval(countdown);
                        timerInput.value = "Election Ended";
                    }
                }, 1000);
            }
        });
    </script>



    </div>
    <script>
        <?php if ($precinct && !empty($precinct['latitude']) && !empty($precinct['longitude'])): ?>
            var lat = <?= $precinct['latitude'] ?>;
            var lng = <?= $precinct['longitude'] ?>;
            var precinctName = "<?= addslashes($precinct['name']) ?>";

            // Initialize the map
            var map = L.map('precinctMap').setView([lat, lng], 16); // Zoom 16 is good for buildings

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            // Add marker
            L.marker([lat, lng])
                .addTo(map)
                .bindPopup("<b>" + precinctName + "</b><br>Precinct Location")
                .openPopup();
        <?php endif; ?>
    </script>
</body>

</html>