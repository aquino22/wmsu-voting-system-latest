<?php
session_start();
require_once '../includes/conn.php';

date_default_timezone_set('Asia/Manila');
$current_date = date('Y-m-d H:i:s');

// Get user info
$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];

// Get moderator assigned precinct(s)
$stmt = $pdo->prepare("SELECT precinct FROM moderators WHERE email = :email");
$stmt->execute(['email' => $email]);
$moderator = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$moderator || empty($moderator['precinct'])) {
    die("<h3 class='text-center'>No assigned precinct found. Please contact your administrator.</h3>");
}

// Decode precincts JSON (could be multiple)
$precincts = json_decode($moderator['precinct'], true);
if (!is_array($precincts)) $precincts = [$moderator['precinct']];

// Get all voting periods assigned to these precincts
$placeholders = implode(',', array_fill(0, count($precincts), '?'));

$stmt = $pdo->prepare("
    SELECT 
        vp.*,
        e.id as election_id,
        e.election_name,
        pe.precinct_id,
        p.name AS precinct_name
    FROM voting_periods vp
    JOIN elections e
        ON vp.election_id = e.id
    JOIN precinct_elections pe
        ON pe.election_name = e.election_name
    JOIN precincts p
        ON p.id = pe.precinct_id
    WHERE pe.precinct_id IN ($placeholders)
      AND vp.status IN ('Ongoing', 'Paused')
    ORDER BY vp.start_period ASC
");

$stmt->execute($precincts);
$assignedVotingPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);




$ongoingVotingPeriods = [];
$currentDate = date('Y-m-d H:i:s');

$ongoingVotingPeriods = [];
$upcomingVotingPeriods = []; // For elections not started yet
$currentDate = date('Y-m-d H:i:s');

foreach ($assignedVotingPeriods as $vp) {
    $start = $vp['start_period'];
    $end = $vp['end_period'] ?? null; // Some periods may not have end_period
    $status = $vp['status'];

    if (($status === 'Ongoing' || $status === 'Paused') && $currentDate >= $start && ($end === null || $currentDate <= $end)) {
        // Election is ongoing or paused and current time is within period
        $ongoingVotingPeriods[] = $vp;

    } elseif ($status === 'Scheduled' && $currentDate < $start) {
        // Election is scheduled but hasn’t started yet
        $upcomingVotingPeriods[] = $vp;

    } elseif (($status === 'Ongoing' || $status === 'Paused') && $currentDate < $start) {
        // Election marked as Ongoing/Paused but its start time is in the future
        $upcomingVotingPeriods[] = $vp; // Treat as upcoming
    }
}




// Get voting period info for the first ongoing election (if any)
$votingPeriod = null;

if (!empty($ongoingVotingPeriods)) {

    $firstElection = $ongoingVotingPeriods[0];
    $electionId = $firstElection['election_id'];

    // 1️⃣ Ongoing
    $stmt = $pdo->prepare("
        SELECT 
            vp.*,
                e.election_name as name
        FROM voting_periods vp
        JOIN elections e ON vp.election_id = e.id
        WHERE vp.status = 'Ongoing'
          AND vp.election_id = :election_id
        LIMIT 1
    ");
    $stmt->execute(['election_id' => $electionId]);
    $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2️⃣ Paused (fallback)
    if (!$votingPeriod) {
        $stmt = $pdo->prepare("
            SELECT 
                vp.*,
                    e.election_name as name
            FROM voting_periods vp
            JOIN elections e ON vp.election_id = e.id
            WHERE vp.status = 'Paused'
              AND vp.election_id = :election_id
            LIMIT 1
        ");
        $stmt->execute(['election_id' => $electionId]);
        $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 3️⃣ Scheduled (next upcoming)
    if (!$votingPeriod) {
        $stmt = $pdo->prepare("
            SELECT 
                vp.*,
                e.election_name as name
            FROM voting_periods vp
            JOIN elections e ON vp.election_id = e.id
            WHERE vp.status = 'Scheduled'
              AND vp.start_period >= NOW()
              AND vp.election_id = :election_id
            ORDER BY vp.start_period ASC
            LIMIT 1
        ");
        $stmt->execute(['election_id' => $electionId]);
        $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($votingPeriod) {
   
    $votingPeriodId = $votingPeriod['id'];
    $votingPeriodName = $votingPeriod['name'];
    $votingPeriodStart = $votingPeriod['start_period'];
    $votingPeriodEnd = $votingPeriod['end_period'] ?: 'TBD';
    $votingPeriodStatus = $votingPeriod['status'];
    $votingPeriodElectionName = $votingPeriod['name'];

    $votingPeriodReStart = $votingPeriod['re_start_period'] ?: null;
    $votingPeriodReEnd = $votingPeriod['re_end_period'] ?: 'null';

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

} else {
    $votingPeriodId = null;
    $votingPeriodName = "No Active Voting Period";
    $votingPeriodStatus = "None";
    $remaining_seconds = 0;
}

/* ---------------------------------------------
   PLACE YOUR SCANNING LOGIC HERE
----------------------------------------------*/

$isScanningAllowed = false;

if ($votingPeriodStatus === "Ongoing" && $remaining_seconds > 0) {
    $isScanningAllowed = true;
}

   

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU I-Elect - QR Scanner</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="js/select.dataTables.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="images/favicon.png" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="vendors/chart.js/Chart.min.js"></script>
    <script src="vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script src="vendors/progressbar.js/progressbar.min.js"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="js/off-canvas.js"></script>
    <script src="js/hoverable-collapse.js"></script>
    <script src="js/template.js"></script>
    <script src="js/settings.js"></script>
    <script src="js/todolist.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page-->
    <script src="js/dashboard.js"></script>
    <script src="js/Chart.roundedBarCharts.js"></script>
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
            font-size: 1.1rem;
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

        /* Enhanced navigation styling */
        .navbar {
            background: linear-gradient(to right, #950000, #B22222);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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

        /* Election selector styles */
        .election-selector {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #B22222;
        }

        .election-option {
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }

        .election-option:hover {
            background-color: #e9ecef;
        }

        .election-option.active {
            background-color: #B22222;
            color: black;
            border-color: #B22222;
        }

        .election-option.active .namer {
            color: white;
        }

        .election-details {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .no-elections {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }

        .pad {
            width: auto;
            margin: auto;
            padding: 20px;
        }

        #reader video {
            width: 100% !important;
            height: 300px !important;
            object-fit: cover;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            #reader {
                max-width: 300px;
            }

            #reader video {
                height: 250px !important;
            }
        }

        @media (max-width: 480px) {
            #reader {
                max-width: 250px;
            }

            #reader video {
                height: 200px !important;
            }
        }
    </style>
</head>

<body>
    <div class="container-scroller">
        <!-- partial:partials/_navbar.html -->
        <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
                <div class="me-3">
                    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
                        <span class="icon-menu"></span>
                    </button>
                </div>
                <div>
                    <a class="navbar-brand brand-logo" href="index.html">
                        <img src="images/wmsu-logo.png" alt="logo" class="logo img-fluid" />
                        <small style="font-size: 16px;"><b>WMSU I-Elect</b></small>
                    </a>
                    <a class="navbar-brand brand-logo-mini" href="index.html">
                        <img src="images/wmsu-logo.png" class="logo img-fluid" alt="logo" />
                    </a>
                </div>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-top">
                <ul class="navbar-nav">
                    <li class="nav-item font-weight-semibold d-none d-lg-block ms-0">
                        <h1 class="welcome-text">Welcome, <span class="text-white fw-bold">WMSU Moderator</span></h1>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                        <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <img class="img-xs rounded-circle" src="images/wmsu-logo.png" style="background-color: white;"
                                alt="Profile image"> </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                            <a class="dropdown-item" href="processes/accounts/logout.php"><i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign
                                Out</a>
                        </div>
                    </li>
                </ul>
                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
                    <span class="mdi mdi-menu"></span>
                </button>
            </div>
        </nav>
        <!-- partial -->
        <div class="container-fluid page-body-wrapper">
            <nav class="sidebar sidebar-offcanvas" id="sidebar">
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="mdi mdi-grid-large menu-icon"></i>
                            <span class="menu-title">Dashboard</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="voter-list.php">
                            <i class="menu-icon mdi mdi-account-group"></i>
                            <span class="menu-title">Voter List</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active-menu-link" href="vote_qr_code.php">
                            <i class="menu-icon mdi mdi-qrcode-scan" style="color: white !important;"></i>
                            <span class="menu-title" style="color: white !important;">QR Code Scanning</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- partial -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">QR Scanner</a>
                                        </li>
                                    </ul>
                                </div>

                             <div class="tab-content tab-content-basic">
    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
        <div class="row">
            <div class="col-lg mx-auto">
                <div class="card card-rounded pulse">
                    <div class="card-body">

                        <!-- Election Selection Section -->
                        <div class="election-selector">
                            <h4 class="mb-3">Select Election to Scan For:</h4>

                          <?php if (empty($ongoingVotingPeriods) && empty($upcomingVotingPeriods)): ?>
    <div class="no-elections" id="noElectionsMessage">
        <i class="mdi mdi-calendar-remove mdi-48px text-muted mb-3"></i>
        <h5>No Elections</h5>
        <p class="text-muted">There are currently no active or upcoming elections for your assigned precinct(s).</p>
    </div>

<?php elseif (empty($ongoingVotingPeriods) && !empty($upcomingVotingPeriods)): ?>
    <div class="no-elections" id="upcomingElectionsMessage">
        <i class="mdi mdi-calendar-clock mdi-48px text-muted mb-3"></i>
        <h5>Upcoming Elections</h5>
        <p class="text-muted">There are elections scheduled to start soon for your assigned precinct(s):</p>
       
            <?php foreach ($upcomingVotingPeriods as $election): ?>
             
                    <?php echo " ● " . htmlspecialchars($election['election_name']); ?> – starts at 
                    <?php echo date('M d, Y h:i A', strtotime($election['start_period'])); ?>
                
            <?php endforeach; ?>
       
    </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($ongoingVotingPeriods as $index => $election):
                                        $stmt = $pdo->prepare("SELECT id, event_title FROM events WHERE candidacy = :election_name LIMIT 1");
                                        $stmt->execute(['election_name' => $election['election_id']]);
                                        $event = $stmt->fetch(PDO::FETCH_ASSOC);
                                        $event_title = $event['event_title'] ?? "No Event Found";
                                        $is_active = ($index === 0) ? 'active' : '';
                                    ?>
                                        <div class="col-md-6 mb-3">
                                       <div class="election-option <?php echo $is_active; ?>"
     data-election-name="<?php echo htmlspecialchars($election['election_name']); ?>"
     data-precinct-id="<?php echo htmlspecialchars($election['precinct_id']); ?>"
     data-precinct-name="<?php echo htmlspecialchars($election['precinct_name']); ?>"
     data-status="<?php echo $election['status']; ?>"
     data-start-period="<?php echo $election['start_period']; ?>"
     data-end-period="<?php echo $election['end_period']; ?>">

    <h6 class="mb-1 namer"><?php echo htmlspecialchars($election['election_name']); ?></h6>
    <small class="text-muted namer">Precinct: <?php echo htmlspecialchars($election['precinct_name']); ?></small>

    <div class="election-details mt-2">
        <small><strong>Event:</strong> <?php echo htmlspecialchars($event_title); ?></small><br>
        <small><strong>Period:</strong>
            <?php echo date('M d, Y h:i A', strtotime($election['start_period'])); ?> -
            <?php echo date('M d, Y h:i A', strtotime($election['end_period'])); ?>
        </small>
    </div>
</div>

                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Selected Election Info -->
                        <?php if (!empty($ongoingVotingPeriods)): ?>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div>
                                    <h2 class="card-title card-title-dash" id="selectedElectionName"></h2>
                                    <p class="text-muted" id="selectedPrecinctName"></p>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="countdown-container me-3">
                                        <i class="mdi mdi-clock-outline me-2" style="color: #B22222;"></i>
                                        <input type="text" class="timer-input" id="secondsTimer" value="0:00:00" readonly>
                                    </div>
                                    <div class="countdown-container">
                                        <i class="mdi mdi-calendar me-2" style="color: #666;"></i>
                                        <input type="text" class="timer-input" style="color: #666;" id="DateEnding" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- QR Scanner -->
                            <div class="qr-container" id="qrContainer">
                                <div class="scanner-header">
                                    <h2><i class="mdi mdi-qrcode-scan me-2"></i>Scan Student QR Code</h2>
                                </div>
                                <div class="position-relative mb-4">
                                    <div class="pad">
                                        <div id="reader"></div>
                                        <div class="scan-animation"></div>
                                    </div>
                                </div>
                                <div class="scan-guide">
                                    <h5 class="mb-2"><i class="mdi text-center mdi-information-outline me-2"></i>Scanning Instructions</h5>
                                    <ul class="mb-0">
                                        <li>Position the student ID QR code within the scanning frame</li>
                                        <li>Hold steady until verification is complete</li>
                                        <li>Make sure lighting is adequate for better scanning</li>
                                    </ul>
                                </div>
                                <div class="text-center mt-3">
                                    <p>Scanned Result: <span id="qrResult"></span></p>
                                    <form id="redirectForm" action="vote_qr_code.php" method="POST" style="display: none;">
                                        <input type="hidden" name="voting_period_id" id="formVotingPeriodId">
                                        <input type="hidden" name="student_id" id="formStudentId">
                                        <input type="hidden" name="election_name" id="formElectionName">
                                        <input type="hidden" name="precinct_name" id="formPrecinctName">
                                        <input type="hidden" name="precinct_id" id="formPrecinctId">
                                    </form>
                                </div>
                            </div>

                            <div class="no-elections" id="noElectionsMessage" style="display: none;">
                                <i class="mdi mdi-clock-alert mdi-48px text-muted mb-3"></i>
                                <h5>Scanning Unavailable</h5>
                                <p class="text-muted">The voting period for the selected election has ended or is not currently active.</p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const electionsData = <?= json_encode($ongoingVotingPeriods); ?>;
    let currentElection = {};

    document.addEventListener('DOMContentLoaded', () => {
        const electionOptions = document.querySelectorAll('.election-option');

        // Initialize with first election
        if (electionOptions.length > 0) {
            electionOptions[0].click();
        }

        electionOptions.forEach(option => {
            option.addEventListener('click', function () {
                electionOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');

                const selectedName = this.dataset.electionName;
                const selectedPrecinct = this.dataset.precinctName;

                const election = electionsData.find(e => e.election_name === selectedName);

             currentElection = {
                    electionName: election.election_name,
                    precinctName: election.precinct_name,
                    precinctId: election.precinct_id,
                    votingPeriodId: election.id,
                    startPeriod: election.start_period,
                    endPeriod: election.end_period,
                    status: election.status
                };

                document.getElementById('selectedElectionName').innerHTML =
                    currentElection.electionName + ' <span class="election-status">' + currentElection.status + '</span>';
                document.getElementById('selectedPrecinctName').textContent =
                    'Precinct: ' + currentElection.precinctName;

                updateTimerAndScanner();
            });
        });

        // Initialize QR scanner
        initializeScanner();
    });

    let timerInterval;
    function updateTimerAndScanner() {
        const timerDisplay = document.getElementById('secondsTimer');
        const qrContainer = document.getElementById('qrContainer');
        const noElectionsMessage = document.getElementById('noElectionsMessage');
        const dater = document.getElementById('DateEnding');

        let remainingSeconds = 0;
        const now = new Date();

        if (currentElection.status === 'Ongoing') {
            remainingSeconds = Math.floor((new Date(currentElection.endPeriod) - now) / 1000);
        } else if (currentElection.status === 'Scheduled') {
            remainingSeconds = Math.floor((new Date(currentElection.startPeriod) - now) / 1000);
        }

        if (remainingSeconds <= 0 || currentElection.status !== 'Ongoing') {
            qrContainer.style.display = 'none';
            noElectionsMessage.style.display = 'block';
            timerDisplay.value = "Time's Up!";
            timerDisplay.style.color = "#dc3545";
        } else {
            qrContainer.style.display = 'block';
            noElectionsMessage.style.display = 'none';
            timerDisplay.style.color = "#000";
            startDynamicTimer(remainingSeconds);
        }

      const d = new Date(currentElection.endPeriod);
dater.value = d.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric"
});


    }

    function startDynamicTimer(seconds) {
        clearInterval(timerInterval);
        let remaining = seconds;
        const timerDisplay = document.getElementById('secondsTimer');

        timerInterval = setInterval(() => {
            if (remaining <= 0) {
                clearInterval(timerInterval);
                updateTimerAndScanner();
                return;
            }

            const hrs = Math.floor(remaining / 3600);
            const mins = Math.floor((remaining % 3600) / 60);
            const secs = remaining % 60;

            timerDisplay.value = `${hrs}:${mins < 10 ? '0' : ''}${mins}:${secs < 10 ? '0' : ''}${secs}`;
            remaining--;
        }, 1000);
    }

    let html5QrcodeScanner = null;
    let isProcessing = false;
    function initializeScanner() {
        if (html5QrcodeScanner) html5QrcodeScanner.clear();

        html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    }

    function onScanSuccess(decodedText, decodedResult) {
        if (isProcessing) return;
        isProcessing = true;

        document.getElementById("qrResult").innerText = decodedText;

        if (!currentElection.votingPeriodId) {
            Swal.fire({
                icon: 'error',
                title: 'No Active Voting Period',
                text: 'Selected election does not have an active voting period.',
                confirmButtonColor: '#B22222'
            });
            isProcessing = false;
            return;
        }

        Swal.fire({
            title: 'Verifying QR Code...',
            text: 'Please wait.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        fetch("process_qr.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "qrData=" + encodeURIComponent(decodedText) +
                  "&voting_period_id=" + encodeURIComponent(currentElection.votingPeriodId) +
                  "&election_name=" + encodeURIComponent(currentElection.electionName) +
                  "&precinct_name=" + encodeURIComponent(currentElection.precinctId) +
                                 "&precinct_id=" + encodeURIComponent(currentElection.precinctId)
        })
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.status === "success") {
                Swal.fire({
                    icon: 'success',
                    title: 'QR Code Verified!',
                    text: data.message,
                    background: '#f8f9fa',
                    iconColor: '#28a745',
                    confirmButtonColor: '#B22222',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    document.getElementById("formVotingPeriodId").value = currentElection.votingPeriodId;
                    document.getElementById("formStudentId").value = decodedText;
                    document.getElementById("formElectionName").value = currentElection.electionName;
                    document.getElementById("formPrecinctName").value = currentElection.precinctName;
                              document.getElementById("formPrecinctId").value = currentElection.precinctId;
                    document.getElementById("redirectForm").submit();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Verification Failed',
                    text: data.message,
                    background: '#f8f9fa',
                    iconColor: '#dc3545',
                    confirmButtonColor: '#B22222'
                });
            }
            isProcessing = false;
        })
        .catch(error => {
            Swal.close();
            console.error("Error:", error);
            Swal.fire({
                icon: 'error',
                title: 'Request Error',
                text: 'An error occurred while processing your request.',
                confirmButtonColor: '#B22222'
            });
            isProcessing = false;
        });
    }

    function onScanFailure(error) {
        console.warn(`Scan failed: ${error}`);
    }
</script>


    <!-- jQuery (Required) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script src="https://unpkg.com/html5-qrcode/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>