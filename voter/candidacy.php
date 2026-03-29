<?php
date_default_timezone_set('Asia/Manila');
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
    <link rel="shortcut icon" href="images/favicon.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
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
                    <li class="nav-item ">
                        <a class="nav-link" href=" index.php">
                            <i class="mdi mdi-grid-large menu-icon"></i>
                            <span class="menu-title">Home</span>
                        </a>
                    </li>
                    <li class="nav-item active-link">
                        <a class="nav-link active-link" href="candidacy.php" style="background-color: #B22222 !important;">
                            <i class="mdi mdi-account menu-icon" style="color: #ffffff !important;"></i>
                            <span class="menu-title" style="color: #ffffff !important;">File Candidacy</span>
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
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Candidacy Filing</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">



                                        <!-- Selected Election Details -->
                                        <?php if ($selectedElection): ?>
                                            <?php
                                            // Fetch candidate data for the selected election
                                            date_default_timezone_set('Asia/Manila');
                                            $current_date = date('Y-m-d H:i:s');

                                            try {
                                                $votingPeriodId = $selectedElection['id'];
                                                $votingPeriodName = $selectedElection['election_name'];
                                                $votingPeriodStart = $selectedElection['start_period'];
                                                $votingPeriodEnd = $selectedElection['end_period'];
                                                $votingPeriodStatus = $selectedElection['status'];
                                                $votingPeriodReStart = $selectedElection['re_start_period'];
                                                $votingPeriodReEnd = $selectedElection['re_end_period'];

                                                // Calculate remaining time
                                                if (isset($votingPeriodReStart) && isset($votingPeriodReEnd)) {
                                                    $remaining_seconds = 0;
                                                    if ($votingPeriodStatus === 'Ongoing') {
                                                        $remaining_seconds = strtotime($votingPeriodReEnd) - strtotime($current_date);
                                                        $remaining_seconds = $remaining_seconds < 0 ? 0 : $remaining_seconds;
                                                    } elseif ($votingPeriodStatus === 'Scheduled') {
                                                        $remaining_seconds = strtotime($votingPeriodReStart) - strtotime($current_date);
                                                        $remaining_seconds = $remaining_seconds < 0 ? 0 : $remaining_seconds;
                                                    }
                                                } else {
                                                    $remaining_seconds = 0;
                                                    if ($votingPeriodStatus === 'Ongoing') {
                                                        $remaining_seconds = strtotime($votingPeriodEnd) - strtotime($current_date);
                                                        $remaining_seconds = $remaining_seconds < 0 ? 0 : $remaining_seconds;
                                                    } elseif ($votingPeriodStatus === 'Scheduled') {
                                                        $remaining_seconds = strtotime($votingPeriodStart) - strtotime($current_date);
                                                        $remaining_seconds = $remaining_seconds < 0 ? 0 : $remaining_seconds;
                                                    }
                                                }

                                                $selectedElectionId = isset($_GET['election_id']) ? intval($_GET['election_id']) : null;
                                                $selectedElection = null;
                                                $candidatesByLevel = ['Central' => [], 'Local' => []];

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

                                                // Fetch candidate data ONLY if an election is selected
                                                if ($selectedElection) {
                                                    try {
                                                        $votingPeriodId = $selectedElection['id'];
                                                        $votingPeriodElectionId = $selectedElection['election_id'];
                                                        $votingPeriodName = $selectedElection['election_name'];
                                                        $votingPeriodStart = $selectedElection['start_period'];
                                                        $votingPeriodEnd = $selectedElection['end_period'];
                                                        $votingPeriodStatus = $selectedElection['status'];
                                                        $votingPeriodReStart = $selectedElection['re_start_period'];
                                                        $votingPeriodReEnd = $selectedElection['re_end_period'];

                                                        // Fetch candidates for the selected election
                                                        $stmt = $pdo->prepare("
            SELECT id 
            FROM registration_forms 
            WHERE election_name = ? 
            LIMIT 1
        ");
                                                        $stmt->execute([$votingPeriodElectionId]);
                                                        $formId = $stmt->fetchColumn();

                                                        if ($formId) {
                                                            // Determine which candidate table to use based on status
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

                                                            foreach ($candidateIds as $candidateId) {
                                                                $stmt = $pdo->prepare("
                    SELECT cr.field_id, cr.value, ff.field_name
                    FROM candidate_responses cr
                    JOIN form_fields ff ON cr.field_id = ff.id
                    WHERE cr.candidate_id = ?
                ");
                                                                $stmt->execute([$candidateId]);
                                                                $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                                $stmt = $pdo->prepare("
                    SELECT file_path 
                    FROM candidate_files 
                    WHERE candidate_id = ? 
                    LIMIT 1
                ");
                                                                $stmt->execute([$candidateId]);
                                                                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                                                                $photoPath = $file ? $file['file_path'] : 'https://cdn.vectorstock.com/i/500p/08/19/gray-photo-placeholder-icon-design-ui-vector-35850819.jpg';

                                                                $candidateData = [
                                                                    'id' => $candidateId,
                                                                    'photo' => $photoPath,
                                                                    'name' => '',
                                                                    'party' => '',
                                                                    'position' => '',
                                                                    'student_id' => null,
                                                                    'platform' => ''
                                                                ];

                                                                foreach ($responses as $response) {
                                                                    if ($response['field_name'] === 'full_name') {
                                                                        $candidateData['name'] = $response['value'];
                                                                    } elseif ($response['field_name'] === 'party') {
                                                                        $candidateData['party'] = $response['value'];
                                                                    } elseif ($response['field_name'] === 'position') {
                                                                        $candidateData['position'] = $response['value'];
                                                                    } elseif ($response['field_name'] === 'student_id') {
                                                                        $candidateData['student_id'] = $response['value'];
                                                                    } elseif ($response['field_name'] === 'platform') {
                                                                        $candidateData['platform'] = $response['value'];
                                                                    }
                                                                }

                                                                $stmt = $pdo->prepare("
                    SELECT level 
                    FROM positions 
                    WHERE name = ? 
                    LIMIT 1
                ");
                                                                $stmt->execute([$candidateData['position']]);
                                                                $positionLevel = $stmt->fetchColumn();
                                                                $level = $positionLevel === 'Central' ? 'Central' : 'Local';

                                                                if ($candidateData['student_id']) {
                                                                    $stmt = $pdo->prepare("
                        SELECT college 
                        FROM voters 
                        WHERE student_id = ? 
                        LIMIT 1
                    ");
                                                                    $stmt->execute([$candidateData['student_id']]);
                                                                    $candidateCollege = $stmt->fetchColumn();
                                                                } else {
                                                                    $candidateCollege = null;
                                                                }

                                                                if ($level === 'Central') {
                                                                    $candidatesByLevel['Central'][$candidateData['position']][] = $candidateData;
                                                                } elseif ($level === 'Local' && $candidateCollege === $college) {
                                                                    $candidatesByLevel['Local'][$candidateData['position']][] = $candidateData;
                                                                }
                                                            }
                                                        }
                                                    } catch (Exception $e) {
                                                        echo "<div class='alert alert-danger'>Error loading election details: " . htmlspecialchars($e->getMessage()) . "</div>";
                                                    }
                                                }
                                            } catch (Exception $e) {
                                                echo "<div class='alert alert-danger'>Error loading election details: " . htmlspecialchars($e->getMessage()) . "</div>";
                                            }
                                            ?>


                                        <?php endif; ?>

                                        <?php
                                        require 'includes/conn.php';

                                        // Check if voting is currently ongoing
                                        $stmt = $pdo->prepare("
    SELECT COUNT(*) AS ongoing_count
    FROM voting_periods
    WHERE status = 'Ongoing'
");
                                        $stmt->execute();
                                        $ongoingVoting = $stmt->fetch(PDO::FETCH_ASSOC)['ongoing_count'] > 0;

                                        if (!$ongoingVoting || $ongoingVoting) {
                                            // No voting currently, show available candidacies
                                            $stmt = $pdo->prepare("
        SELECT id, event_title, cover_image, event_details, registration_enabled, status,  registration_start,
        registration_deadline
        FROM events
        WHERE status = 'published' AND registration_enabled = 1
        ORDER BY created_at ASC
    ");
                                            $stmt->execute();
                                            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        } else {
                                            // Voting ongoing → hide all filing
                                            $events = [];
                                        }
                                        ?>
                                        <div class="card">
                                            <div class="card-header bg-warning text-white">
                                                <?php if (!empty($events)): ?>
                                                    <h4 class="mb-0"><i class="mdi mdi-file me-2"></i>Candidacies to File</h4>
                                                <?php else: ?>
                                                    <h4 class="mb-0"><i class="mdi mdi-vote me-2"></i>No Candidacies Ongoing</h4>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-body">
                                                <?php if (!empty($events)): ?>
                                                    <div class="row g-4">
                                                        <?php foreach ($events as $event):
                                                            $coverImage = !empty($event['cover_image']) && file_exists('../uploads/event_covers/' . $event['cover_image'])
                                                                ? '../uploads/event_covers/' . htmlspecialchars($event['cover_image'])
                                                                : '../uploads/placeholder/ph.jpg';

                                                            $now = new DateTime();
                                                            $start = new DateTime($event['registration_start']);
                                                            $deadline = new DateTime($event['registration_deadline']);

                                                            $isClosed = $now > $deadline;
                                                        ?>
                                                            <div class="col-12 col-md-4">
                                                                <div class="card h-100 shadow-sm">
                                                                    <img src="<?= $coverImage ?>" class="card-img-top" alt="Event Image" style="height:200px; object-fit:cover; object-position:center;">

                                                                    <div class="card-body d-flex flex-column">
                                                                        <h5 class="card-title">
                                                                            <b>Election/Candidacy:</b> <?= htmlspecialchars($event['event_title']) ?>
                                                                        </h5>

                                                                        <small class="text-muted">
                                                                            <b>Start:</b> <?= date('M d, Y h:i A', strtotime($event['registration_start'])) ?><br>
                                                                            <b>Deadline:</b> <?= date('M d, Y h:i A', strtotime($event['registration_deadline'])) ?>
                                                                        </small>

                                                                        <div class="mt-auto pt-3">

                                                                            <?php if ($isClosed): ?>
                                                                                <button class="btn btn-secondary w-100" disabled>
                                                                                    Registration Closed
                                                                                </button>
                                                                            <?php else: ?>
                                                                                <a href="file_candidacy.php?event_id=<?= $event['id'] ?>" class="btn btn-success w-100 text-white">
                                                                                    File for Candidacy
                                                                                </a>
                                                                            <?php endif; ?>

                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-center py-4">
                                                        <i class="mdi mdi-calendar-remove" style="font-size: 4rem; color: #6c757d;"></i>
                                                        <h4 class="text-muted mt-3">No Candidacies Available</h4>
                                                        <p class="text-muted">There are no active candidacies at this time. Please check back later.</p>
                                                    </div>
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


    </div>
    </div>
    </div>
    </div>
    </div>
    </div>
    </div>
    </div>
    </div>



    <script>
        <?php if (isset($_SESSION['STATUS'])): ?>
            const status = '<?php echo $_SESSION['STATUS']; ?>';
            switch (status) {
                case 'SUCCESS_READING':
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'COR successfully verified!',
                        confirmButtonText: 'OK'
                    });
                    break;
                case 'ERROR_FILE_NEW':
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'The specified file could not be found. Please upload a valid file.',
                        confirmButtonText: 'OK'
                    });
                    break;
                case 'ERROR_INVALID_PATH':
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Invalid file path. Please try again.',
                        confirmButtonText: 'OK'
                    });
                    break;
                case 'ERROR_PDF_CONVERSION':
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to convert PDF to image. Please ensure the file is valid.',
                        confirmButtonText: 'OK'
                    });
                    break;
                case 'ERROR_IMAGE_PROCESSING':
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to process the image. Please use a supported format (JPEG or PNG).',
                        confirmButtonText: 'OK'
                    });
                    break;
                case 'ERROR_USER_NOT_LOGGED_IN':
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'You must be logged in to verify a COR.',
                        confirmButtonText: 'OK'
                    });
                    break;
                case 'ERROR_OCR_FAILED':
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not read required data from the COR. Please ensure the document is clear.',
                        confirmButtonText: 'OK'
                    });
                    break;
                default:
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unknown error occurred.',
                        confirmButtonText: 'OK'
                    });
            }
            <?php unset($_SESSION['STATUS']); // Clear the status after displaying 
            ?>
        <?php endif; ?>
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

</body>

</html>