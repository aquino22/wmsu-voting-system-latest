<?php
session_start();
include('includes/conn.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

$status = null;
$college = null;
$precinct_name = null;

if ($user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT  p.name AS precinct_name,
            pv.precinct, pv.status, v.college
            FROM precinct_voters pv
            LEFT JOIN voters v ON pv.student_id = v.student_id
               INNER JOIN precincts p ON p.id = pv.precinct
            WHERE pv.student_id = (
                SELECT student_id
                FROM voters
                WHERE email = ?
            )
        ");
        $stmt->execute([$user_email]);
        $voter = $stmt->fetch(PDO::FETCH_ASSOC);

        $precinct_name = $voter ? $voter['precinct_name'] : null;
        $status = $voter ? $voter['status'] : null;

        $college = $voter ? $voter['college'] : null;

        echo "<!-- Debug: User ID = $user_id, Status = " . ($status ?? 'null') . ", College = " . ($college ?? 'null') . " -->";
    } catch (PDOException $e) {
        echo "Database Error: " . $e->getMessage();
        exit;
    }
} else {
    echo "<!-- Debug: No user_id in session -->";
}

// Check if user is a voter
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'voter') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = "NON_VOTER";
    header("Location: ../index.php");
    exit();
}

// Get all available voting periods for the voter
try {
    $stmt = $pdo->prepare("
           SELECT DISTINCT vp.*,
                e.id AS election_id,
               e.election_name,
               ay.semester AS semester,
               ay.start_date AS school_year_start,
               ay.end_date AS school_year_end,
               CASE 
                   WHEN vp.status = 'Ongoing' AND NOW() BETWEEN vp.start_period AND vp.end_period THEN 'Available'
                   WHEN vp.status = 'Ongoing' AND NOW() < vp.start_period THEN 'Ongoing'
                   WHEN vp.status = 'Ongoing' AND NOW() > vp.end_period THEN 'Ended'
                   WHEN vp.status = 'Paused' THEN 'Paused'
                   WHEN vp.status = 'Scheduled' THEN 'Scheduled'
                   ELSE 'Inactive'
               END AS availability,
               COALESCE(v.vote_timestamp, 'Not Voted') AS vote_status
        FROM voting_periods vp
        JOIN elections e ON vp.election_id = e.id
        JOIN academic_years ay ON e.academic_year_id = ay.id
        LEFT JOIN votes v 
               ON vp.id = v.voting_period_id 
               AND v.student_id = ?
        WHERE vp.status IN ('Ongoing', 'Paused', 'Scheduled')
        ORDER BY 
            CASE 
                WHEN vp.status = 'Ongoing' AND NOW() BETWEEN vp.start_period AND vp.end_period THEN 1
                WHEN vp.status = 'Paused' THEN 2
                WHEN vp.status = 'Scheduled' THEN 3
                ELSE 4
            END,
            vp.start_period ASC
    ");
    $stmt->execute([$student_id]);
    $votingPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no voting periods found
    if (empty($votingPeriods)) {
        $_SESSION['STATUS'] = "NO_VOTING_PERIODS";
    }
} catch (PDOException $e) {
    echo "Voting Period Check Error: " . $e->getMessage();
    exit();
}

// Handle election selection
$selectedElectionId = isset($_GET['election_id']) ? intval($_GET['election_id']) : null;
$selectedElection = null;

if ($selectedElectionId) {
    foreach ($votingPeriods as $period) {
        if ($period['id'] == $selectedElectionId) {
            $selectedElection = $period;
            break;
        }
    }
}

// If no election selected, default to the first available one
if (!$selectedElection && !empty($votingPeriods)) {
    $selectedElection = $votingPeriods[0];
    $selectedElectionId = $selectedElection['id'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU I-Elect</title>
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="js/select.dataTables.min.css">
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <link rel="stylesheet" type="text/css" href="../login/css/util.css">
    <link rel="stylesheet" type="text/css" href="../login/css/main.css">
    <link rel="shortcut icon" href="images/favicon.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .input100 {
            border: 1px solid lightgray !important;
            border-radius: 4px;
        }

        .input100:focus,
        .input100:hover,
        .input100:active {
            border: 1px solid lightgray !important;
            outline: none;
            box-shadow: none;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .election-card {
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .election-card:hover {
            border-color: #B22222;
            transform: translateY(-5px);
        }

        .election-card.selected {
            border-color: #B22222;
            background-color: #f8f9fa;
        }

        .election-status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.8rem;
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

        .navbar {
            background: linear-gradient(to right, #950000, #B22222);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav .nav-item.active-link>.nav-link {
            background-color: #B22222 !important;
            color: white !important;
        }

        .voter-status {
            padding: 10px;
            border-radius: 8px;
            font-weight: 500;
        }

        .voter-status.verified {
            background: #d4edda;
            color: #155724;
        }

        .voter-status.voted {
            background: #e2e3e5;
            color: #41464b;
        }

        .action-btn {
            font-size: 1.1rem;
            padding: 10px 20px;
        }

        .candidate-container {
            position: relative;
            min-height: 200px;
        }

        .blur-overlay {
            filter: blur(8px);
            pointer-events: none;
        }

        .overlay-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 20px 40px;
            border-radius: 10px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            z-index: 10;
            max-width: 80%;
        }

        .election-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .vote-btn {
            background: linear-gradient(45deg, #B22222, #ff5a5f);
            border: none;
            padding: 10px 25px;
            font-weight: bold;
        }

        .vote-btn:hover {
            background: linear-gradient(45deg, #950000, #B22222);
            transform: translateY(-2px);
        }

        .election-info {
            background: linear-gradient(135deg, #950000 0%, #FFFFFFFF 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .field-container {
            margin: 15px 0;
        }

        .required::after {
            content: '*';
            color: red;
            margin-left: 5px;
        }

        .error {
            color: red;
        }

        .custom-file {
            margin-top: 10px;
        }

        input[type="file"] {
            background-color: transparent;
            width: 100%;
            cursor: pointer;
        }

        .custom-file-input {
            display: none;
        }

        .file-label {
            background-color: #4CAF50;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .file-label:hover {
            background-color: #45a049;
        }

        .custom-file {
            display: block;


        }

        .card {
            background-color: white !important;
            color: black !important;
        }

        label {
            color: black !important;
        }

        input {
            color: black !important;
        }

        select {
            color: black !important;
        }

        .file-preview {
            display: block;
        }

        /* .input100:active {
        border-bottom: 1px solid black !important;
    } */

        .custom-searchable-select {
            position: relative;
            width: 100%;
        }

        .search-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 5px;
            box-sizing: border-box;
        }

        .custom-searchable {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            min-height: 38px;
            cursor: text;
        }

        .custom-searchable.placeholder {
            color: #999;
        }

        .custom-searchable:focus {
            outline: none;
            border-color: #666;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .hidden-select {
            display: none;
        }

        .dropdown-options {
            display: none;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
        }

        .dropdown-options div {
            padding: 8px 10px;
            cursor: pointer;
        }

        .dropdown-options div:hover {
            background: #f0f0f0;
        }
    </style>
</head>

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
                        <h1 class="welcome-text">Welcome, <span class="text-white fw-bold">
                                <?= $first_name_student . ' ' . $last_name_student ?>
                            </span>
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
                    <li class="nav-item ">
                        <a class="nav-link " href="index.php">
                            <i class="mdi mdi-grid-large menu-icon"></i>
                            <span class="menu-title">Home</span>
                        </a>
                    </li>
                    <li class="nav-item active-link">
                        <a class="nav-link active-link" href="candidacy.php" style="background-color: #B22222 !important;">
                            <i class="mdi mdi-account menu-icon" style="color: white !important;"></i>
                            <span class="menu-title" style="color: white !important;">File Candidacy</span>
                        </a>
                    </li>
                    <li class="nav-item ">
                        <a class="nav-link " href="choose_voting.php">
                            <i class="menu-icon mdi mdi-account-group"></i>
                            <span class="menu-title">Vote</span>
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
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Filing for Candidacy</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                                        <div class="container-fluid">
                                            <div class="container-fluid ">
                                                <?php
                                                include('../includes/conn.php');
                                                $event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
                                                $canRegister = false;

                                                try {
                                                    // Validate event_id
                                                    if ($event_id <= 0) {
                                                        throw new Exception("Invalid event ID");
                                                    }

                                                    // Fetch event details to check registration status and get candidacy
                                                    $event_stmt = $pdo->prepare("
                    SELECT candidacy, registration_deadline 
                    FROM events 
                    WHERE id = ? AND status = 'published' AND registration_enabled = 1
                ");
                                                    $event_stmt->execute([$event_id]);
                                                    $event = $event_stmt->fetch(PDO::FETCH_ASSOC);

                                                    if (!$event || empty($event['candidacy'])) {
                                                        throw new Exception("Event not found, inactive, or registration not enabled");
                                                    }

                                                    // Check if registration is still open
                                                    $current_date = new DateTime();
                                                    $registration_deadline = new DateTime($event['registration_deadline']);
                                                    $canRegister = $current_date <= $registration_deadline;

                                                    if ($canRegister) {
                                                        $candidacy = $event['candidacy'];



                                                        // Fetch form_id from registration_forms using election_name (candidacy)
                                                        $candidacy_stmt = $pdo->prepare("
                        SELECT election_name 
                        FROM elections 
                        WHERE id = ?
                    ");
                                                        $candidacy_stmt->execute([$candidacy]);
                                                        $candidacy_results = $candidacy_stmt->fetch(PDO::FETCH_ASSOC);

                                                        if (!$candidacy_results) {
                                                            throw new Exception("No active registration form found for this candidacy");
                                                        }

                                                        $candidacy_name = $candidacy_results['election_name'];

                                                        // Fetch form_id from registration_forms using election_name (candidacy)
                                                        $form_stmt = $pdo->prepare("
                        SELECT id, form_name 
                        FROM registration_forms 
                        WHERE election_name = ? AND status = 'active'
                    ");
                                                        $form_stmt->execute([$candidacy]);
                                                        $form = $form_stmt->fetch(PDO::FETCH_ASSOC);

                                                        if (!$form) {
                                                            throw new Exception("No active registration form found for this candidacy");
                                                        }

                                                        $form_id = $form['id'];
                                                        $form_name = $form['form_name'];

                                                        // Check if already filed
                                                        $hasFiled = false;
                                                        $candidateStatus = '';
                                                        $existingCandidate = null;
                                                        $filedResponses = [];

                                                        if (isset($student_id)) {
                                                            $checkFiledStmt = $pdo->prepare("
                                                                SELECT c.id, c.status, c.created_at, c.admin_config
                                                                FROM candidates c
                                                                JOIN candidate_responses cr ON c.id = cr.candidate_id
                                                                JOIN form_fields ff ON cr.field_id = ff.id
                                                                WHERE c.form_id = ? 
                                                                AND ff.field_name = 'student_id' 
                                                                AND cr.value = ?
                                                                LIMIT 1
                                                            ");
                                                            $checkFiledStmt->execute([$form_id, $student_id]);
                                                            $existingCandidate = $checkFiledStmt->fetch(PDO::FETCH_ASSOC);

                                                            if ($existingCandidate) {
                                                                $hasFiled = true;
                                                                $candidateStatus = $existingCandidate['status'];

                                                                $responsesStmt = $pdo->prepare("
                                                                    SELECT ff.field_name, cr.value
                                                                    FROM candidate_responses cr
                                                                    JOIN form_fields ff ON cr.field_id = ff.id
                                                                    WHERE cr.candidate_id = ?
                                                                ");
                                                                $responsesStmt->execute([$existingCandidate['id']]);
                                                                $filedResponses = $responsesStmt->fetchAll(PDO::FETCH_KEY_PAIR);

                                                                $fileStmt = $pdo->prepare("
    SELECT ff.field_name, cf.file_path
    FROM candidate_files cf
    JOIN form_fields ff ON cf.field_id = ff.id
    WHERE cf.candidate_id = ?
");
                                                                $fileStmt->execute([$existingCandidate['id']]);

                                                                $fileResponses = $fileStmt->fetchAll(PDO::FETCH_KEY_PAIR);
                                                            }
                                                        }

                                                        // Fetch fields
                                                        $fields_stmt = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY id ASC");
                                                        $fields_stmt->execute([$form_id]);
                                                        $fields = $fields_stmt->fetchAll(PDO::FETCH_ASSOC);

                                                        // Fetch parties for this election
                                                        $party_stmt = $pdo->prepare("
                        SELECT name 
                        FROM parties 
                        WHERE election_id = ? AND status = 'Approved' 
                        ORDER BY name
                    ");
                                                        $party_stmt->execute([$candidacy]);
                                                        $parties = $party_stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    }
                                                } catch (Exception $e) {
                                                    $error_message = $e->getMessage();
                                                }
                                                ?>

                                                <div class=" container-fluid mt-5 wrap-login100" style="width: 1000px">


                                                    <script>
                                                        function goBack() {
                                                            if (document.referrer) {
                                                                window.location.href = 'index.php';
                                                            } else {
                                                                window.location.href = 'index.php';
                                                            }
                                                        }
                                                    </script>

                                                    <br>


                                                    <?php if ($canRegister):

                                                        $eventId = $_GET['event_id'] ?? null;

                                                        if (!$eventId) {
                                                            die("Missing election ID.");
                                                        }

                                                        $stmt = $pdo->prepare("
                        SELECT e.election_name, ev.candidacy 
                        FROM events ev
                        JOIN elections e ON ev.candidacy = e.id
                        WHERE ev.id = :id 
                        LIMIT 1
                    ");
                                                        $stmt->execute(['id' => $eventId]);
                                                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                                        $electionName = $row['election_name'];
                                                        $electionId = $row['candidacy'];



                                                        $stmt = $pdo->prepare("
SELECT 
    v.id,
    v.student_id,
    TRIM(CONCAT(v.first_name, ' ', COALESCE(v.middle_name, ''), ' ', v.last_name)) AS full_name
FROM voters v
LEFT JOIN candidates c
    ON c.id = (
        SELECT c2.id
        FROM candidates c2
        JOIN registration_forms rf2 ON c2.form_id = rf2.id
        JOIN candidate_responses cr2 ON cr2.candidate_id = c2.id
        JOIN form_fields ff2 ON cr2.field_id = ff2.id
        WHERE ff2.field_name = 'student_id'
          AND cr2.value = v.student_id
          AND rf2.election_name = :election_id   -- 🔥 filter by election via form_id
        LIMIT 1
    )
WHERE v.status = 'confirmed' AND v.student_id = :student_id
  AND c.id IS NULL;

 
");
                                                        $stmt->bindParam(':election_id', $electionId, PDO::PARAM_INT);
                                                        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
                                                        $stmt->execute();
                                                        $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                        $stmt = $pdo->query("SELECT DATABASE()");
                                                        $currentDb = $stmt->fetchColumn();

                                                    ?>

                                                        <h2 class="text-center text-light m-2 mb-5"> Filing of Candidacy for <?php echo $electionName; ?> </h2>
                                                        <div class="card mx-auto">
                                                            <div class="card-body p-4">
                                                                <h4 class="text-center mb-4"><?php echo htmlspecialchars($form_name); ?></h4>
                                                                <?php if ($canRegister): ?>

                                                                    <?php if (isset($hasFiled) && $hasFiled): ?>



                                                                        <?php if (!empty($existingCandidate['admin_config']) && $existingCandidate['admin_config'] == 1): ?>
                                                                            <div class="alert alert-warning text-center">
                                                                                <h3>You have added as a candidate by the admin.</h3>
                                                                            </div>
                                                                        <?php endif; ?>


                                                                        <div class="alert alert-info text-center">
                                                                            <h4>You have already filed your candidacy for this election.</h4>
                                                                            <p class="mb-1"><strong>Status:</strong>
                                                                                <span class="badge <?php echo $candidateStatus === 'accepted' ? 'bg-success' : ($candidateStatus === 'rejected' ? 'bg-danger' : 'bg-warning'); ?>">
                                                                                    <?php echo ucfirst(htmlspecialchars($candidateStatus)); ?>
                                                                                </span>
                                                                            </p>
                                                                            <p><strong>Date Filed:</strong> <?php echo date('F j, Y g:i A', strtotime($existingCandidate['created_at'])); ?></p>
                                                                        </div>

                                                                        <!-- ── Withdraw — only if self-filed ── -->
                                                                        <?php if (empty($existingCandidate['admin_config']) || (int)$existingCandidate['admin_config'] === 0): ?>
                                                                            <div class="text-center my-3">
                                                                                <button type="button" class="btn btn-outline-danger withdraw-btn px-4"
                                                                                    onclick="confirmWithdraw(<?= (int)$existingCandidate['id'] ?>)">
                                                                                    <i class="mdi mdi-account-remove me-1"></i>Withdraw My Candidacy
                                                                                </button>
                                                                                <p class="text-muted small mt-1">Withdrawing will permanently remove your filing.</p>
                                                                            </div>
                                                                            <!-- Hidden form submitted by JS -->
                                                                            <form id="withdrawForm" method="POST" action="candidacy.php?event_id=<?= $event_id ?>">
                                                                                <input type="hidden" name="action" value="withdraw">
                                                                                <input type="hidden" name="candidate_id" value="<?= (int)$existingCandidate['id'] ?>">
                                                                            </form>
                                                                        <?php endif; ?>


                                                                        <div class="mt-4">
                                                                            <h5>Submitted Information</h5>
                                                                            <ul class="list-group">
                                                                                <?php foreach ($fields as $field): ?>
                                                                                    <?php
                                                                                    $fieldName = $field['field_name'];
                                                                                    $value = $filedResponses[$fieldName] ?? null;
                                                                                    $fileValue = $fileResponses[$fieldName] ?? null;
                                                                                    $label = $fieldName === 'full_name' ? 'Full Name' : ($fieldName === 'student_id' ? 'Student ID' : ucfirst(str_replace('_', ' ', $fieldName)));
                                                                                    ?>
                                                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                        <strong><?php echo htmlspecialchars($label); ?></strong>
                                                                                        <span>
                                                                                            <?php if ($field['field_type'] === 'file' || $fieldName === 'picture'): ?>
                                                                                                <?php if (!empty($fileValue)): ?>
                                                                                                    <button
                                                                                                        class="btn btn-sm btn-success view-file-btn"
                                                                                                        data-bs-toggle="modal"
                                                                                                        data-bs-target="#filePreviewModal"
                                                                                                        data-file="<?php echo htmlspecialchars($fileValue); ?>">
                                                                                                        View File
                                                                                                    </button>
                                                                                                <?php else: ?>
                                                                                                    <span class="text-muted">No file</span>
                                                                                                <?php endif; ?>
                                                                                            <?php else: ?>
                                                                                                <?php echo htmlspecialchars($value ?? 'N/A'); ?>
                                                                                            <?php endif; ?>
                                                                                        </span>
                                                                                    </li>
                                                                                <?php endforeach; ?>
                                                                            </ul>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <form method="POST" action="processes/register.php?form_id=<?php echo $form_id; ?>&event_id=<?php echo $eventId; ?>" id="registrationForm" enctype="multipart/form-data">
                                                                            <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
                                                                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                                                            <input type="hidden" id="lat" name="lat">
                                                                            <input type="hidden" id="lon" name="lon">

                                                                            <span>Note: File types only include PDFs, DOCXs, JPGs, and PNGs</span>

                                                                            <?php foreach ($fields as $field): ?>
                                                                                <div class="field-container">
                                                                                    <label class="p-b-10 <?php echo $field['is_required'] ? 'required' : ''; ?>">
                                                                                        <?php
                                                                                        if ($field['field_name'] === 'full_name') {
                                                                                            echo 'Full Name';
                                                                                        } elseif ($field['field_name'] === 'student_id') {
                                                                                            echo 'Student ID';
                                                                                        } else {
                                                                                            echo ucfirst(htmlspecialchars($field['field_name']));
                                                                                        }
                                                                                        ?>
                                                                                    </label>

                                                                                    <?php switch ($field['field_name']):
                                                                                        case 'full_name': ?>

                                                                                            <div class="wrap-input100 validate-input">
                                                                                                <div class="custom-searchable" contenteditable="true" id="search_select" data-placeholder="Select Full Name"></div>

                                                                                                <?php foreach ($voters as $voter): ?>

                                                                                                <?php endforeach ?>
                                                                                                <select class="input100 hidden-select" name="fields[<?php echo $field['id']; ?>]" id="full_name_select" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                                                                    <option value="">Select Full Name</option>
                                                                                                    <?php foreach ($voters as $voter):
                                                                                                        $isSelected = ($voter['student_id'] == $_SESSION['user_id']) ? 'selected' : '';
                                                                                                    ?>
                                                                                                        <option
                                                                                                            value="<?php echo htmlspecialchars($voter['full_name']); ?>"
                                                                                                            data-student-id="<?php echo htmlspecialchars($voter['student_id']); ?>"
                                                                                                            <?php echo $isSelected; ?>>
                                                                                                            <?php echo htmlspecialchars($voter['full_name']); ?>
                                                                                                        </option>
                                                                                                    <?php endforeach; ?>
                                                                                                </select>
                                                                                            </div>

                                                                                            <script>
                                                                                                // Automatically set the contenteditable div to the selected option
                                                                                                const hiddenSelect = document.getElementById('full_name_select');
                                                                                                const customSearchable = document.getElementById('search_select');

                                                                                                const selectedOption = hiddenSelect.querySelector('option[selected]');
                                                                                                if (selectedOption) {
                                                                                                    customSearchable.textContent = selectedOption.textContent;
                                                                                                }

                                                                                                // Optional: sync contenteditable with select
                                                                                                customSearchable.addEventListener('input', () => {
                                                                                                    const value = customSearchable.textContent.trim();
                                                                                                    for (let opt of hiddenSelect.options) {
                                                                                                        opt.selected = (opt.text === value);
                                                                                                    }
                                                                                                });
                                                                                            </script>
                                                                                        <?php break;

                                                                                        case 'student_id': ?>
                                                                                            <div class="wrap-input100 validate-input" style="border: 1px solid lightgray; border-radius: 4px;">
                                                                                                <input class="input100" type="text" name="fields[<?php echo $field['id']; ?>]" id="student_id_field" readonly>
                                                                                            </div>
                                                                                        <?php break;

                                                                                        case 'party': ?>
                                                                                            <div class="wrap-input100 validate-input">
                                                                                                <select class="input100" name="fields[<?php echo $field['id']; ?>]" id="party_select" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                                                                    <option value="">Select Party</option>
                                                                                                    <?php foreach ($parties as $party): ?>
                                                                                                        <option value="<?php echo htmlspecialchars($party['name']); ?>">
                                                                                                            <?php echo htmlspecialchars($party['name']); ?>
                                                                                                        </option>
                                                                                                    <?php endforeach; ?>
                                                                                                </select>
                                                                                            </div>
                                                                                        <?php break;

                                                                                        case 'position': ?>
                                                                                            <div class="wrap-input100 validate-input">
                                                                                                <select class="input100" name="fields[<?php echo $field['id']; ?>]" id="position_select" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                                                                    <option value="">Select Party First</option>
                                                                                                </select>
                                                                                            </div>
                                                                                        <?php break;

                                                                                        case 'picture': ?>
                                                                                            <div class="d-flex justify-content-center align-items-center wrap-input100 validate-input custom-file mt-10">
                                                                                                <input required class="custom-file-input" type="file" id="field_<?php echo $field['id']; ?>" name="fields[<?php echo $field['id']; ?>]" accept=".jpg, .jpeg, .png" ?> <label class="file-label" for="field_<?php echo $field['id']; ?>">Choose Profile Picture</label>
                                                                                            </div>
                                                                                            <div class="d-flex flex-column justify-content-center align-items-center file-preview" id="preview_<?php echo $field['id']; ?>" style="margin: 10px;"></div>
                                                                                            <?php break;

                                                                                        default:
                                                                                            switch ($field['field_type']):
                                                                                                case 'text': ?>
                                                                                                    <div class="wrap-input100 validate-input">
                                                                                                        <input style="border: 1px solid lightgray; border-radius: 4px;" class="input100" type="text" name="fields[<?php echo $field['id']; ?>]" <?php echo $field['is_required'] ? 'required' : ''; ?> maxlength="255">
                                                                                                    </div>
                                                                                                <?php break;

                                                                                                case 'textarea': ?>
                                                                                                    <div class="wrap-input100 validate-input">
                                                                                                        <textarea style="border: 1px solid lightgray; border-radius: 4px;" class="input100" style="height: 100px;" name="fields[<?php echo $field['id']; ?>]" <?php echo $field['is_required'] ? 'required' : ''; ?>></textarea>
                                                                                                    </div>
                                                                                                <?php break;

                                                                                                case 'dropdown': ?>
                                                                                                    <div class="wrap-input100 validate-input">
                                                                                                        <select style="border: 1px solid lightgray; border-radius: 4px;" class="input100" name="fields[<?php echo $field['id']; ?>]" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                                                                            <option value="">Select an option</option>
                                                                                                            <?php $options = $field['options'] ? explode(',', $field['options']) : [];
                                                                                                            foreach ($options as $option):
                                                                                                                $option = trim($option);
                                                                                                                if (!empty($option)): ?>
                                                                                                                    <option value="<?php echo htmlspecialchars($option); ?>">
                                                                                                                        <?php echo htmlspecialchars($option); ?>
                                                                                                                    </option>
                                                                                                                <?php endif; ?>
                                                                                                            <?php endforeach; ?>
                                                                                                        </select>
                                                                                                    </div>
                                                                                                <?php break;

                                                                                                case 'checkbox': ?>
                                                                                                    <div style="border: 1px solid lightgray; border-radius: 4px;" class="wrap-input100 validate-input">
                                                                                                        <input type="checkbox" name="fields[<?php echo $field['id']; ?>]" value="1">
                                                                                                        <label><?php echo htmlspecialchars($field['field_name']); ?></label>
                                                                                                    </div>
                                                                                                <?php break;

                                                                                                case 'radio': ?>
                                                                                                    <div class="wrap-input100 validate-input">
                                                                                                        <?php $options = $field['options'] ? explode(',', $field['options']) : [];
                                                                                                        foreach ($options as $option):
                                                                                                            $option = trim($option);
                                                                                                            if (!empty($option)): ?>
                                                                                                                <label style="margin-right: 15px;">
                                                                                                                    <input type="radio" name="fields[<?php echo $field['id']; ?>]" value="<?php echo htmlspecialchars($option); ?>" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                                                                                    <?php echo htmlspecialchars($option); ?>
                                                                                                                </label>
                                                                                                            <?php endif; ?>
                                                                                                        <?php endforeach; ?>
                                                                                                    </div>
                                                                                                <?php break;

                                                                                                case 'file': ?>
                                                                                                    <div class="d-flex justify-content-center align-items-center wrap-input100 validate-input custom-file mt-10">
                                                                                                        <input class="custom-file-input" type="file" id="field_<?php echo $field['id']; ?>" name="fields[<?php echo $field['id']; ?>]" accept=".pdf, .docx, .jpg, .png" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                                                                        <label class="file-label" for="field_<?php echo $field['id']; ?>">Choose File</label>
                                                                                                    </div>
                                                                                                    <div class="d-flex justify-content-center align-items-center wrap-input100 validate-input custom-file mt-10">
                                                                                                        <div class="file-preview" id="preview_<?php echo $field['id']; ?>" style="margin: 10px;"></div>
                                                                                                    </div>
                                                                                    <?php break;
                                                                                            endswitch;
                                                                                            break;
                                                                                    endswitch; ?>

                                                                                    <?php if (!empty($field['template_path'])): ?>
                                                                                        <div class="mt-2">
                                                                                            <label class="mb-3" style="font-style: italic; font-size: 12px;">Template Provided to Follow:</label>
                                                                                            <a href="../uploads/templates/<?php echo htmlspecialchars($field['template_path']); ?>" class="btn btn-sm btn-outline-primary" download target="_blank">
                                                                                                <i class="bi bi-download"></i> Download Template
                                                                                            </a>
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                            <?php endforeach; ?>

                                                                            <button type="submit" class="login100-form-btn w-100">Submit Registration</button>
                                                                        </form>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <p class="text-center text-danger"><?php echo htmlspecialchars($error_message ?? 'Registration is closed or not available.'); ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>



                                                    <?php else: ?>
                                                        <p class="alert alert-danger text-center"><?php echo isset($error_message) ? htmlspecialchars($error_message) : "Registration is closed for this event."; ?></p>
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

    <div class="modal fade" id="filePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">File Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center">
                    <div id="filePreviewContainer"></div>
                </div>

            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const buttons = document.querySelectorAll(".view-file-btn");
            const previewContainer = document.getElementById("filePreviewContainer");
            const basePath = "../login/uploads/candidates/";

            buttons.forEach(btn => {
                btn.addEventListener("click", function() {

                    const file = this.dataset.file;

                    if (!file) {
                        previewContainer.innerHTML = "<p class='text-muted'>No file available</p>";
                        return;
                    }

                    const filePath = basePath + file;
                    const ext = file.split('.').pop().toLowerCase();

                    previewContainer.innerHTML = "";

                    if (["jpg", "jpeg", "png", "gif", "webp"].includes(ext)) {

                        previewContainer.innerHTML = `
                    <img src="${filePath}" class="img-fluid rounded shadow">
                `;

                    } else {

                        previewContainer.innerHTML = `
                    <iframe src="${filePath}" 
                            style="width:100%; height:600px; border:none;">
                    </iframe>
                `;
                    }

                });
            });

            // Optional: clear preview when modal closes
            const modal = document.getElementById("filePreviewModal");
            if (modal) {
                modal.addEventListener("hidden.bs.modal", function() {
                    previewContainer.innerHTML = "";
                });
            }

        });
    </script>

    <script>
        function selectElection(electionId) {
            window.location.href = 'index.php?election_id=' + electionId;
        }

        // Timer functionality
        document.addEventListener('DOMContentLoaded', function() {
            const timerInput = document.getElementById('secondsTimer');
            if (timerInput) {
                function formatTime(seconds) {
                    if (seconds <= 0) return "Election Ended";

                    const days = Math.floor(seconds / (3600 * 24));
                    const hours = Math.floor((seconds % (3600 * 24)) / 3600);
                    const minutes = Math.floor((seconds % 3600) / 60);
                    const secs = Math.floor(seconds % 60);

                    return `${days}d ${hours}h ${minutes}m ${secs}s`;
                }

                let timeLeft = initialSeconds;
                timerInput.value = formatTime(timeLeft);

                if (timeLeft > 0) {
                    const countdown = setInterval(() => {
                        timeLeft--;
                        timerInput.value = formatTime(timeLeft);

                        if (timeLeft <= 0) {
                            clearInterval(countdown);
                            timerInput.value = "Election Ended";
                        }
                    }, 1000);
                }
            }

            // Platform view buttons
            document.querySelectorAll('.view-platform').forEach(button => {
                button.addEventListener('click', function() {
                    const platform = this.getAttribute('data-platform');
                    Swal.fire({
                        title: 'Candidate Platform',
                        html: `<div style="text-align: left; max-height: 400px; overflow-y: auto;">${platform.replace(/\n/g, '<br>')}</div>`,
                        width: '600px',
                        confirmButtonText: 'Close'
                    });
                });
            });
        });
    </script>
    <!-- JavaScript for COR Upload Modal (unchanged) -->
    <!-- Load pdf.js from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
    <script>
        if (typeof pdfjsLib === 'undefined') {
            console.error('pdf.js failed to load. Please check the CDN or network connection.');
            alert('Failed to load required library for PDF processing. Please try again later.');
        } else {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.worker.min.js';

            const corImageInput = document.getElementById('corImage');
            const corUploadForm = document.getElementById('corUploadForm');
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadBtnText = document.getElementById('uploadBtnText');
            const uploadBtnSpinner = document.getElementById('uploadBtnSpinner');
            const previewDiv = document.getElementById('preview');
            const imagePreview = document.getElementById('imagePreview');
            const convertedImageInput = document.getElementById('convertedImage');

            const maxFileSize = 5 * 1024 * 1024; // 5 MB

            corImageInput.addEventListener('change', async function(event) {
                const file = event.target.files[0];
                if (!file) {
                    uploadBtn.disabled = true;
                    previewDiv.style.display = 'none';
                    return;
                }

                if (file.size > maxFileSize) {
                    alert('File size exceeds 5MB limit. Please upload a smaller file.');
                    corImageInput.value = '';
                    uploadBtn.disabled = true;
                    previewDiv.style.display = 'none';
                    return;
                }

                uploadBtn.disabled = true;
                uploadBtnText.textContent = 'Upload';
                uploadBtnSpinner.style.display = 'none';
                previewDiv.style.display = 'none';
                imagePreview.src = '';
                convertedImageInput.value = '';

                try {
                    uploadBtnText.textContent = 'Processing...';
                    uploadBtnSpinner.style.display = 'inline-block';

                    if (file.type === 'application/pdf') {
                        const arrayBuffer = await file.arrayBuffer();
                        const pdf = await pdfjsLib.getDocument({
                            data: arrayBuffer
                        }).promise;

                        if (pdf.numPages === 0) {
                            throw new Error('No pages found in the PDF.');
                        }

                        const page = await pdf.getPage(1);
                        const scale = 2;
                        const viewport = page.getViewport({
                            scale: scale
                        });

                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;

                        await page.render({
                            canvasContext: context,
                            viewport: viewport
                        }).promise;

                        const jpgDataUrl = canvas.toDataURL('image/jpeg', 0.9);

                        imagePreview.src = jpgDataUrl;
                        previewDiv.style.display = 'block';
                        convertedImageInput.value = jpgDataUrl;

                        uploadBtn.disabled = false;

                    } else if (['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            previewDiv.style.display = 'block';
                            uploadBtn.disabled = false;
                        };
                        reader.onerror = function() {
                            throw new Error('Error reading the image file.');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        throw new Error('Unsupported file type. Please upload a PDF, JPEG, PNG, or GIF.');
                    }

                } catch (error) {
                    console.error('Error processing file:', error);
                    alert('Error: ' + error.message);
                    corImageInput.value = '';
                } finally {
                    uploadBtnText.textContent = 'Upload';
                    uploadBtnSpinner.style.display = 'none';
                }
            });

            corUploadForm.addEventListener('submit', function(event) {
                event.preventDefault();

                uploadBtn.disabled = true;
                uploadBtnText.textContent = 'Uploading...';
                uploadBtnSpinner.style.display = 'inline-block';

                // Show SweetAlert2 loading popup for upload
                Swal.fire({
                    title: 'Uploading COR',
                    text: 'Please wait while the file is being uploaded...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                try {
                    const formData = new FormData();

                    if (convertedImageInput.value) {
                        const byteString = atob(convertedImageInput.value.split(',')[1]);
                        const mimeString = convertedImageInput.value.split(',')[0].split(':')[1].split(';')[0];
                        const ab = new ArrayBuffer(byteString.length);
                        const ia = new Uint8Array(ab);
                        for (let i = 0; i < byteString.length; i++) {
                            ia[i] = byteString.charCodeAt(i);
                        }
                        const blob = new Blob([ab], {
                            type: mimeString
                        });
                        const file = new File([blob], 'cor_image.jpg', {
                            type: 'image/jpeg'
                        });
                        formData.append('cor_image', file);
                    } else {
                        const file = corImageInput.files[0];
                        if (!file) {
                            throw new Error('No file selected.');
                        }
                        formData.append('cor_image', file);
                    }

                    fetch(corUploadForm.action, {
                            method: 'POST',
                            body: formData
                        })
                        .then(async response => {
                            const contentType = response.headers.get('content-type');
                            let responseData;

                            if (contentType && contentType.includes('application/json')) {
                                responseData = await response.json();
                            } else {
                                const text = await response.text();
                                throw new Error('Server returned non-JSON response: ' + text);
                            }

                            if (response.ok && responseData.success) {
                                // Close upload SweetAlert and show processing SweetAlert
                                Swal.close();
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Processing COR',
                                    text: 'Please wait while the COR is being processed...',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    allowEnterKey: false,
                                    showConfirmButton: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });

                                // Proceed with form reset and navigation
                                corUploadForm.reset();
                                previewDiv.style.display = 'none';
                                imagePreview.src = '';
                                convertedImageInput.value = '';
                                uploadBtn.disabled = true;

                                const modal = bootstrap.Modal.getInstance(document.getElementById('corUploadModal'));
                                if (modal) modal.hide();

                                if (responseData.redirect) {
                                    window.location.href = responseData.redirect;
                                } else {
                                    window.location.reload();
                                }
                            } else {
                                throw new Error(responseData.message || 'Upload failed.');
                            }
                        })
                        .catch(error => {
                            console.error('Error uploading file:', error);
                            // Close loading SweetAlert and show error
                            Swal.close();
                            Swal.fire({
                                title: 'Error!',
                                text: 'Error uploading file: ' + error.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        })
                        .finally(() => {
                            uploadBtn.disabled = false;
                            uploadBtnText.textContent = 'Upload';
                            uploadBtnSpinner.style.display = 'none';
                        });

                } catch (error) {
                    console.error('Error preparing form data:', error);
                    // Close loading SweetAlert and show error
                    Swal.close();
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error: ' + error.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    uploadBtn.disabled = false;
                    uploadBtnText.textContent = 'Upload';
                    uploadBtnSpinner.style.display = 'none';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            try {
                if (typeof bootstrap === 'undefined') {
                    throw new Error('Bootstrap JavaScript is not loaded.');
                }
                const modalElement = document.getElementById('corUploadModal');
                if (!modalElement) {
                    throw new Error('Modal element not found.');
                }
                const modal = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: false
                });
                modal.show();
            } catch (e) {
                console.error('Failed to show modal:', e);
                alert('Error displaying modal: ' + e.message);
            }
        });
    </script>


    <!-- SweetAlert Notifications -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            <?php if (isset($_SESSION['STATUS'])): ?>
                <?php if ($_SESSION['STATUS'] === "COR_VERIFIED_SUCCESSFULLY"): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Certificate of Registration Verified',
                        text: 'Your COR has been verified successfully!',
                        confirmButtonText: 'OK'
                    });
                <?php elseif ($_SESSION['STATUS'] === "INVALID_COR"): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid COR',
                        text: 'One or more required fields are missing.',
                        confirmButtonText: 'Try Again'
                    }).then(() => {
                        var modal = new bootstrap.Modal(document.getElementById('corUploadModal'));
                        modal.show();
                    });
                <?php elseif ($_SESSION['STATUS'] === "STUDENT_ID_NOT_FOUND"): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Student ID Not Found',
                        text: 'The student ID was not detected.',
                        confirmButtonText: 'Try Again'
                    }).then(() => {
                        var modal = new bootstrap.Modal(document.getElementById('corUploadModal'));
                        modal.show();
                    });
                <?php elseif ($_SESSION['STATUS'] === "SEM_SY_NOT_FOUND"): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Semester/School Year Not Found',
                        text: 'The Semester or School Year is missing.',
                        confirmButtonText: 'Try Again'
                    }).then(() => {
                        var modal = new bootstrap.Modal(document.getElementById('corUploadModal'));
                        modal.show();
                    });
                <?php elseif ($_SESSION['STATUS'] === "DB_ERROR"): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Database Error',
                        text: '<?= htmlspecialchars($_SESSION['MESSAGE'] ?? 'Unknown error') ?>',
                        confirmButtonText: 'Try Again'
                    }).then(() => {
                        var modal = new bootstrap.Modal(document.getElementById('corUploadModal'));
                        modal.show();
                    });
                <?php elseif ($_SESSION['STATUS'] === "EXCEPTION"): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Unexpected Error',
                        text: '<?= htmlspecialchars($_SESSION['MESSAGE'] ?? 'Unknown error') ?>',
                        confirmButtonText: 'Try Again'
                    }).then(() => {
                        var modal = new bootstrap.Modal(document.getElementById('corUploadModal'));
                        modal.show();
                    });
                <?php endif; ?>
                <?php unset($_SESSION['STATUS'], $_SESSION['MESSAGE']); ?>
            <?php endif; ?>
        });
    </script>



    <!-- Load pdf.js from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
    <script>
        if (typeof pdfjsLib === 'undefined') {
            console.error('pdf.js failed to load. Please check the CDN or network connection.');
            alert('Failed to load required library for PDF processing. Please try again later.');
        } else {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.worker.min.js';

            const corImageInput = document.getElementById('corImage');
            const corUploadForm = document.getElementById('corUploadForm');
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadBtnText = document.getElementById('uploadBtnText');
            const uploadBtnSpinner = document.getElementById('uploadBtnSpinner');
            const previewDiv = document.getElementById('preview');
            const imagePreview = document.getElementById('imagePreview');
            const convertedImageInput = document.getElementById('convertedImage');

            const maxFileSize = 5 * 1024 * 1024; // 5 MB

            corImageInput.addEventListener('change', async function(event) {
                const file = event.target.files[0];
                if (!file) {
                    uploadBtn.disabled = true;
                    previewDiv.style.display = 'none';
                    return;
                }

                if (file.size > maxFileSize) {
                    alert('File size exceeds 5MB limit. Please upload a smaller file.');
                    corImageInput.value = '';
                    uploadBtn.disabled = true;
                    previewDiv.style.display = 'none';
                    return;
                }

                uploadBtn.disabled = true;
                uploadBtnText.textContent = 'Upload';
                uploadBtnSpinner.style.display = 'none';
                previewDiv.style.display = 'none';
                imagePreview.src = '';
                convertedImageInput.value = '';

                try {
                    uploadBtnText.textContent = 'Processing...';
                    uploadBtnSpinner.style.display = 'inline-block';

                    if (file.type === 'application/pdf') {
                        const arrayBuffer = await file.arrayBuffer();
                        const pdf = await pdfjsLib.getDocument({
                            data: arrayBuffer
                        }).promise;

                        if (pdf.numPages === 0) {
                            throw new Error('No pages found in the PDF.');
                        }

                        const page = await pdf.getPage(1);
                        const scale = 2;
                        const viewport = page.getViewport({
                            scale: scale
                        });

                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;

                        await page.render({
                            canvasContext: context,
                            viewport: viewport
                        }).promise;

                        const jpgDataUrl = canvas.toDataURL('image/jpeg', 0.9);

                        imagePreview.src = jpgDataUrl;
                        previewDiv.style.display = 'block';
                        convertedImageInput.value = jpgDataUrl;

                        uploadBtn.disabled = false;

                    } else if (['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            previewDiv.style.display = 'block';
                            uploadBtn.disabled = false;
                        };
                        reader.onerror = function() {
                            throw new Error('Error reading the image file.');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        throw new Error('Unsupported file type. Please upload a PDF, JPEG, PNG, or GIF.');
                    }

                } catch (error) {
                    console.error('Error processing file:', error);
                    alert('Error: ' + error.message);
                    corImageInput.value = '';
                } finally {
                    uploadBtnText.textContent = 'Upload';
                    uploadBtnSpinner.style.display = 'none';
                }
            });

            corUploadForm.addEventListener('submit', function(event) {
                event.preventDefault();

                uploadBtn.disabled = true;
                uploadBtnText.textContent = 'Uploading...';
                uploadBtnSpinner.style.display = 'inline-block';

                // Show SweetAlert2 loading popup for upload
                Swal.fire({
                    title: 'Uploading COR',
                    text: 'Please wait while the file is being uploaded...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                try {
                    const formData = new FormData();

                    if (convertedImageInput.value) {
                        const byteString = atob(convertedImageInput.value.split(',')[1]);
                        const mimeString = convertedImageInput.value.split(',')[0].split(':')[1].split(';')[0];
                        const ab = new ArrayBuffer(byteString.length);
                        const ia = new Uint8Array(ab);
                        for (let i = 0; i < byteString.length; i++) {
                            ia[i] = byteString.charCodeAt(i);
                        }
                        const blob = new Blob([ab], {
                            type: mimeString
                        });
                        const file = new File([blob], 'cor_image.jpg', {
                            type: 'image/jpeg'
                        });
                        formData.append('cor_image', file);
                    } else {
                        const file = corImageInput.files[0];
                        if (!file) {
                            throw new Error('No file selected.');
                        }
                        formData.append('cor_image', file);
                    }

                    fetch(corUploadForm.action, {
                            method: 'POST',
                            body: formData
                        })
                        .then(async response => {
                            const contentType = response.headers.get('content-type');
                            let responseData;

                            if (contentType && contentType.includes('application/json')) {
                                responseData = await response.json();
                            } else {
                                const text = await response.text();
                                throw new Error('Server returned non-JSON response: ' + text);
                            }

                            if (response.ok && responseData.success) {
                                // Close upload SweetAlert
                                Swal.close();
                                // Show success SweetAlert
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'COR uploaded successfully!',
                                    icon: 'success',
                                    confirmButtonText: 'OK',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Show processing SweetAlert
                                        Swal.fire({
                                            title: 'Processing COR',
                                            text: 'Please wait while the COR is being processed...',
                                            allowOutsideClick: false,
                                            allowEscapeKey: false,
                                            allowEnterKey: false,
                                            showConfirmButton: false,
                                            didOpen: () => {
                                                Swal.showLoading();
                                            }
                                        });

                                        // Proceed with form reset and navigation
                                        corUploadForm.reset();
                                        previewDiv.style.display = 'none';
                                        imagePreview.src = '';
                                        convertedImageInput.value = '';
                                        uploadBtn.disabled = true;

                                        const modal = bootstrap.Modal.getInstance(document.getElementById('corUploadModal'));
                                        if (modal) modal.hide();

                                        if (responseData.redirect) {
                                            window.location.href = responseData.redirect;
                                        } else {
                                            window.location.reload();
                                        }
                                    }
                                });
                            } else {
                                throw new Error(responseData.message || 'Upload failed.');
                            }
                        })
                        .catch(error => {
                            console.error('Error uploading file:', error);
                            // Close loading SweetAlert and show error
                            Swal.close();
                            Swal.fire({
                                title: 'Error!',
                                text: 'Error uploading file: ' + error.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        })
                        .finally(() => {
                            uploadBtn.disabled = false;
                            uploadBtnText.textContent = 'Upload';
                            uploadBtnSpinner.style.display = 'none';
                        });

                } catch (error) {
                    console.error('Error preparing form data:', error);
                    // Close loading SweetAlert and show error
                    Swal.close();
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error: ' + error.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    uploadBtn.disabled = false;
                    uploadBtnText.textContent = 'Upload';
                    uploadBtnSpinner.style.display = 'none';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            try {
                if (typeof bootstrap === 'undefined') {
                    throw new Error('Bootstrap JavaScript is not loaded.');
                }
                const modalElement = document.getElementById('corUploadModal');
                if (!modalElement) {
                    throw new Error('Modal element not found.');
                }
                const modal = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: false
                });
                modal.show();
            } catch (e) {
                console.error('Failed to show modal:', e);
                alert('Error displaying modal: ' + e.message);
            }
        });
    </script>



    <!-- COR Upload Handling -->
    <?php
    // Ensure no output before JSON response
    ob_start(); // Start output buffering to catch any unexpected output

    // Enable error reporting for debugging (disable in production)
    ini_set('display_errors', 0); // Suppress errors in production
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);

    // Set content type to JSON
    header('Content-Type: application/json; charset=UTF-8');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cor_image'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']; // Only images, since PDFs are converted to JPG client-side
        $fileType = mime_content_type($_FILES['cor_image']['tmp_name']);
        $fileSizeLimit = 5 * 1024 * 1024; // 5 MB

        // Validate file type
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode([
                'success' => false,
                'message' => 'Only JPEG, PNG, and GIF images are allowed.'
            ]);
            ob_end_flush();
            exit;
        }

        // Validate file size
        if ($_FILES['cor_image']['size'] > $fileSizeLimit) {
            echo json_encode([
                'success' => false,
                'message' => 'File size exceeds 5MB limit.'
            ]);
            ob_end_flush();
            exit;
        }

        // Define upload directory
        $uploadDir = '../cor_reader/test/uploads/cor/';
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create upload directory. Check server permissions.'
                ]);
                ob_end_flush();
                exit;
            }
        }

        // Validate directory writability
        if (!is_writable($uploadDir)) {
            echo json_encode([
                'success' => false,
                'message' => 'Upload directory is not writable. Check server permissions.'
            ]);
            ob_end_flush();
            exit;
        }

        // Generate unique file name
        $fileExt = strtolower(pathinfo($_FILES['cor_image']['name'], PATHINFO_EXTENSION));
        $tempFileName = 'cor_' . $user_id . '_' . time() . '.' . $fileExt;
        $tempFilePath = $uploadDir . $tempFileName;

        // Move the uploaded file
        if (move_uploaded_file($_FILES['cor_image']['tmp_name'], $tempFilePath)) {
            $relativePath = 'uploads/cor/' . $tempFileName;
            $redirectUrl = "../../cor_reader/Test/test.php?file=" . urlencode($relativePath);

            echo json_encode([
                'success' => true,
                'message' => 'File uploaded successfully. Processing...',
                'redirect' => $redirectUrl
            ]);
            ob_end_flush();
            exit;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to upload file. Check server permissions.'
            ]);
            ob_end_flush();
            exit;
        }
    } else {

        ob_end_flush();
    }
    ?>

    </div>
    </div>
    </div>
    </div>
    </div>
    </div>
    </div>
    </div>
    </div>



    <!--===============================================================================================-->
    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <!--===============================================================================================-->
    <script src="vendor/animsition/js/animsition.min.js"></script>
    <!--===============================================================================================-->
    <script src="vendor/bootstrap/js/popper.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <!--===============================================================================================-->
    <script src="vendor/select2/select2.min.js"></script>
    <!--===============================================================================================-->
    <script src="vendor/daterangepicker/moment.min.js"></script>
    <script src="vendor/daterangepicker/daterangepicker.js"></script>
    <!--===============================================================================================-->
    <script src="vendor/countdowntime/countdowntime.js"></script>
    <!--===============================================================================================-->
    <script src="js/main.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($fields as $field): ?>
                <?php if ($field['field_type'] === 'file'): ?>
                    const fileInput_<?php echo $field['id']; ?> = document.getElementById('field_<?php echo $field['id']; ?>');
                    const preview_<?php echo $field['id']; ?> = document.getElementById('preview_<?php echo $field['id']; ?>');

                    fileInput_<?php echo $field['id']; ?>.addEventListener('change', function() {
                        preview_<?php echo $field['id']; ?>.innerHTML = ''; // Clear previous preview
                        const file = this.files[0];
                        if (file) {
                            const fileName = file.name;
                            const fileType = file.type;
                            const fileSize = (file.size / 1024).toFixed(2); // Size in KB

                            // Display file details
                            const details = `
                        <p style="margin: 5px 0;"><strong>File:</strong> ${fileName}</p>
                        <p style="margin: 5px 0;"><strong>Type:</strong> ${fileType}</p>
                        <p style="margin: 5px 0;"><strong>Size:</strong> ${fileSize} KB</p>
                    `;
                            preview_<?php echo $field['id']; ?>.innerHTML = details;

                            // Preview for images
                            if (fileType === 'image/jpeg' || fileType === 'image/png') {
                                const img = document.createElement('img');
                                img.src = URL.createObjectURL(file);
                                img.style.maxWidth = '200px';
                                img.style.marginTop = '10px';
                                img.style.display = 'block';
                                img.style.border = '1px solid #ccc';
                                img.style.borderRadius = '4px';
                                preview_<?php echo $field['id']; ?>.appendChild(img);
                            } else if (fileType === 'application/pdf') {
                                const pdfLink = document.createElement('p');
                                pdfLink.innerHTML = '<em style="color: #666;">PDF preview not available in browser</em>';
                                pdfLink.style.margin = '5px 0';
                                preview_<?php echo $field['id']; ?>.appendChild(pdfLink);
                            } else if (fileType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                                const docxNote = document.createElement('p');
                                docxNote.innerHTML = '<em style="color: #666;">DOCX preview not available in browser</em>';
                                docxNote.style.margin = '5px 0';
                                preview_<?php echo $field['id']; ?>.appendChild(docxNote);
                            }
                        }
                    });
                <?php endif; ?>
            <?php endforeach; ?>
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchSelect = document.getElementById('search_select');
            const fullNameSelect = document.getElementById('full_name_select');
            const studentIdField = document.getElementById('student_id_field');
            let allOptions = Array.from(fullNameSelect.options);
            const dropdown = document.createElement('div');
            dropdown.className = 'dropdown-options';

            // Store original options
            const originalOptions = allOptions.map(option => ({
                value: option.value,
                text: option.text,
                studentId: option.getAttribute('data-student-id')
            }));

            // Set placeholder
            searchSelect.textContent = searchSelect.getAttribute('data-placeholder');
            searchSelect.classList.add('placeholder');

            // Create dropdown
            document.body.appendChild(dropdown);
            let isDropdownVisible = false;

            // Position dropdown
            function positionDropdown() {
                const rect = searchSelect.getBoundingClientRect();
                dropdown.style.position = 'absolute';
                dropdown.style.top = `${rect.bottom + window.scrollY}px`;
                dropdown.style.left = `${rect.left + window.scrollX}px`;
                dropdown.style.width = `${rect.width}px`;
            }

            // Show/hide dropdown
            function updateDropdown(options) {
                dropdown.innerHTML = '';
                options.forEach(opt => {
                    const div = document.createElement('div');
                    div.textContent = opt.text;
                    div.dataset.value = opt.value;
                    div.dataset.studentId = opt.studentId;
                    div.addEventListener('click', () => {
                        searchSelect.textContent = opt.text;
                        searchSelect.classList.remove('placeholder');
                        fullNameSelect.value = opt.value;
                        if (studentIdField) studentIdField.value = opt.studentId || '';
                        hideDropdown();
                    });
                    dropdown.appendChild(div);
                });
                positionDropdown();
                dropdown.style.display = 'block';
                isDropdownVisible = true;
            }

            // Pre-select the contenteditable div based on selected option
            const preSelectedOption = fullNameSelect.querySelector('option[selected]');
            if (preSelectedOption) {
                searchSelect.textContent = preSelectedOption.textContent;
                searchSelect.classList.remove('placeholder');
                if (studentIdField) {
                    studentIdField.value = preSelectedOption.getAttribute('data-student-id');
                }
            } else {
                searchSelect.textContent = searchSelect.getAttribute('data-placeholder');
                searchSelect.classList.add('placeholder');
            }

            function hideDropdown() {
                dropdown.style.display = 'none';
                isDropdownVisible = false;
            }

            // Handle input
            searchSelect.addEventListener('input', function() {
                this.classList.remove('placeholder');
                const searchTerm = this.textContent.toLowerCase();
                const filteredOptions = originalOptions.filter(option =>
                    option.text.toLowerCase().includes(searchTerm)
                );
                updateDropdown(filteredOptions);
            });

            // Show all options on focus
            searchSelect.addEventListener('focus', function() {
                if (this.textContent === this.getAttribute('data-placeholder')) {
                    this.textContent = '';
                }
                updateDropdown(originalOptions);
            });

            // Handle blur
            searchSelect.addEventListener('blur', function() {
                setTimeout(() => {
                    if (!this.textContent.trim() || !fullNameSelect.value) {
                        this.textContent = this.getAttribute('data-placeholder');
                        this.classList.add('placeholder');
                    }
                    hideDropdown();
                }, 100);
            });

            // Handle select change
            if (fullNameSelect && studentIdField) {
                fullNameSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const studentId = selectedOption.getAttribute('data-student-id');
                    studentIdField.value = studentId || '';
                    searchSelect.textContent = selectedOption.text;
                    searchSelect.classList.remove('placeholder');
                });
            }

            // Keyboard navigation
            searchSelect.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && isDropdownVisible) {
                    e.preventDefault();
                    const firstOption = dropdown.querySelector('div');
                    if (firstOption) firstOption.click();
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Update position options based on party selection
            $('#party_select').change(function() {
                const partyName = $(this).val();
                const electionName = '<?php echo isset($candidacy) ? addslashes($candidacy) : ''; ?>';
                if (partyName && electionName) {
                    $.ajax({
                        url: 'processes/get_positions.php',
                        method: 'POST',
                        data: {
                            election_name: electionName,
                            party_name: partyName
                        },
                        success: function(response) {
                            $('#position_select').html(response);
                        },
                        error: function() {
                            $('#position_select').html('<option value="">Error loading positions</option>');
                        }
                    });
                } else {
                    $('#position_select').html('<option value="">Select Party First</option>');
                }
            });


        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            const formId = <?php echo $form_id; ?>;
            const eventId = <?php echo $event_id; ?>;

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Detect if the user is on a mobile device
                const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

                if (isMobile && navigator.geolocation) {
                    // Prompt for geolocation on mobile devices
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const lat = position.coords.latitude;
                            const lon = position.coords.longitude;
                            document.getElementById('lat').value = lat;
                            document.getElementById('lon').value = lon;

                            form.action = `processes/register.php?form_id=${formId}&event_id=${eventId}&lat=${lat}&lon=${lon}`;
                            form.submit();
                        },
                        function(error) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Geolocation Error',
                                text: 'Could not get your location. Proceeding without it.',
                                showConfirmButton: true
                            }).then(() => {
                                form.action = `processes/register.php?form_id=${formId}&event_id=${eventId}&lat=0&lon=0`;
                                form.submit();
                            });
                        }, {
                            enableHighAccuracy: true,
                            timeout: 5000,
                            maximumAge: 0
                        }
                    );
                } else {
                    // Proceed without geolocation for non-mobile devices or if geolocation is unsupported
                    if (!navigator.geolocation) {
                        console.warn('Geolocation is not supported by this browser.');
                    }
                    form.action = `processes/register.php?form_id=${formId}&event_id=${eventId}&lat=0&lon=0 `;
                    form.submit();
                }
            });
        });
    </script>

    <?php
    if (isset($_SESSION['STATUS'])) {
        switch ($_SESSION['STATUS']) {
            case 'SUCCESS_CANDIDACY':
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Candidacy Successful!',
                        text: 'Your candidacy has been successfully registered!',
                        showConfirmButton: true
                    });
                </script>";
                break;

            case 'ERROR_CANDIDACY':
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Candidacy Error',
                        text: 'There was an error while registering your candidacy. Please try again!',
                        showConfirmButton: true
                    });
                </script>";
                break;

            case 'ERROR_PARTY_POSITION_DUPLICATION':
                echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Candidacy Registration Error!',
                            text: 'The party and position has already been taken. Please try again!',
                            showConfirmButton: true
                        });
                    </script>";
                break;



            case 'ERROR_CANDIDACY_DUPLICATION':
                echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Candidate already exists!',
                            text: 'There was an error while registering your candidacy for it already exists!',
                            showConfirmButton: true
                        });
                    </script>";
                break;


            case 'LOGOUT_SUCCESSFUL':
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Logged Out',
                        text: 'You have successfully logged out!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                </script>";
                break;
        }
        unset($_SESSION['STATUS']);
    }
    ?>

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

    <?php
    if (isset($_SESSION['STATUS'])) {
        switch ($_SESSION['STATUS']) {
            case 'SUCCESS_CANDIDACY':
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Candidacy Successful!',
                        text: 'Your candidacy has been successfully registered!',
                        showConfirmButton: true
                    });
                </script>";
                break;

            case 'ERROR_CANDIDACY':
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Candidacy Error',
                        text: 'There was an error while registering your candidacy. Please try again!',
                        showConfirmButton: true
                    });
                </script>";
                break;

            case 'ERROR_PARTY_POSITION_DUPLICATION':
                echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Candidacy Registration Error!',
                            text: 'The party and position has already been taken. Please try again!',
                            showConfirmButton: true
                        });
                    </script>";
                break;



            case 'ERROR_CANDIDACY_DUPLICATION':
                echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Candidate already exists!',
                            text: 'There was an error while registering your candidacy for it already exists!',
                            showConfirmButton: true
                        });
                    </script>";
                break;


            case 'LOGOUT_SUCCESSFUL':
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Logged Out',
                        text: 'You have successfully logged out!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                </script>";
                break;
        }
        unset($_SESSION['STATUS']);
    }
    ?>

</body>

</html>