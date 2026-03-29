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

// -------------------------------------------------------
// FIX 1: Use LEFT JOIN on voting_periods so elections
//         without a voting period row still appear.
//         Also fetch vp.status separately from e.status
//         so we can handle both correctly.
// -------------------------------------------------------
$placeholders = implode(',', array_fill(0, count($precincts), '?'));

$stmt = $pdo->prepare("
    SELECT
        vp.id                  AS voting_period_id,
        vp.election_id,
        vp.start_period,
        vp.end_period,
        vp.re_start_period,
        vp.re_end_period,
        vp.status              AS vp_status,
        e.election_name,
        e.status               AS election_status,
        pe.precinct_id,
        p.name                 AS precinct_name
    FROM elections e
    JOIN precinct_elections pe
        ON pe.election_name = e.id
    JOIN precincts p
        ON p.id = pe.precinct_id
    LEFT JOIN voting_periods vp
        ON vp.election_id = e.id
          AND vp.status IN ('Ongoing', 'Paused', 'Scheduled')
    WHERE pe.precinct_id IN ($placeholders)
      AND e.status IN ('Ongoing', 'Paused', 'Scheduled')
    ORDER BY COALESCE(vp.start_period, e.start_period) ASC
");

$stmt->execute($precincts);
$assignedVotingPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ongoingVotingPeriods  = [];
$upcomingVotingPeriods = [];
$currentDate           = date('Y-m-d H:i:s');

foreach ($assignedVotingPeriods as $vp) {
    // Use vp_status if a voting period exists, otherwise fall back to election_status
    $status = $vp['vp_status'] ?? $vp['election_status'];
    $start  = $vp['start_period'] ?? null;

    if ($status === 'Ongoing' || $status === 'Paused') {
        $ongoingVotingPeriods[] = $vp;
    } elseif ($status === 'Scheduled' && $start && $currentDate < $start) {
        $upcomingVotingPeriods[] = $vp;
    }
}

// -------------------------------------------------------
// FIX 2: Deduplicate by voting_period_id (not election+precinct)
//         so two distinct voting periods for different elections
//         on the same precinct both appear as separate cards.
//         Elections with no voting period are keyed by
//         "noPeriod_electionId_precinctId" so they also show once.
// -------------------------------------------------------
$seenKeys = [];
$deduped  = [];
foreach ($ongoingVotingPeriods as $vp) {
    if (!empty($vp['voting_period_id'])) {
        $key = 'vp_' . $vp['voting_period_id'];
    } else {
        // Election without a voting period — show once per election+precinct
        $key = 'noPeriod_' . $vp['election_id'] . '_' . $vp['precinct_id'];
    }

    if (!isset($seenKeys[$key])) {
        $seenKeys[$key] = true;
        $deduped[] = $vp;
    }
}
$ongoingVotingPeriods = $deduped;

$hasOngoing = !empty($ongoingVotingPeriods);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU I-Elect - QR Scanner</title>
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
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    <style>
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

        .election-status.paused {
            background: linear-gradient(45deg, #e67e00, #f39c12);
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
            background: #fff;
        }

        .election-option:hover {
            background-color: #f0f0f0;
            border-color: #B22222;
        }

        .election-option.active {
            background-color: #B22222;
            color: white;
            border-color: #B22222;
        }

        .election-option.active .namer,
        .election-option.active small,
        .election-option.active strong {
            color: white !important;
        }

        .election-option.active .election-details {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .election-details {
            background: white;
            border-radius: 8px;
            padding: 10px 12px;
            margin-top: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
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

        /* Election cards grid — responsive */
        .elections-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 12px;
        }

        /* Badge for status */
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 4px;
        }

        .status-ongoing {
            background: #d4edda;
            color: #155724;
        }

        .status-paused {
            background: #fff3cd;
            color: #856404;
        }

        .status-no-period {
            background: #f8d7da;
            color: #721c24;
        }

        .election-option.active .status-badge {
            background: rgba(255, 255, 255, 0.25);
            color: white;
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

        <!-- Navbar -->
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
                        <small style="font-size:16px;"><b>WMSU I-Elect</b></small>
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
                            <img class="img-xs rounded-circle" src="images/wmsu-logo.png" style="background-color:white;" alt="Profile image">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                            <a class="dropdown-item" href="processes/accounts/logout.php">
                                <i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign Out
                            </a>
                        </div>
                    </li>
                </ul>
                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
                    <span class="mdi mdi-menu"></span>
                </button>
            </div>
        </nav>

        <div class="container-fluid page-body-wrapper">

            <!-- Sidebar -->
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
                            <i class="menu-icon mdi mdi-qrcode-scan" style="color:white !important;"></i>
                            <span class="menu-title" style="color:white !important;">QR Code Scanning</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Main Panel -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab"
                                                href="#overview" role="tab" aria-controls="overview" aria-selected="true">
                                                QR Scanner
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                                        <div class="row">
                                            <div class="col-lg mx-auto">
                                                <div class="card card-rounded pulse">
                                                    <div class="card-body">

                                                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                                            <h3>Please select an <b>election</b> first before scanning to ensure proper working functionality of QR scanning.</h3>
                                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                        </div>

                                                        <!-- ===== Election Selection Section ===== -->
                                                        <div class="election-selector">
                                                            <h4 class="mb-3">Select Election to Scan For:</h4>

                                                            <?php if (empty($ongoingVotingPeriods) && empty($upcomingVotingPeriods)): ?>

                                                                <div class="no-elections">
                                                                    <i class="mdi mdi-calendar-remove mdi-48px text-muted mb-3"></i>
                                                                    <h5>No Elections</h5>
                                                                    <p class="text-muted">There are currently no active or upcoming elections for your assigned precinct(s).</p>
                                                                </div>

                                                            <?php elseif (empty($ongoingVotingPeriods) && !empty($upcomingVotingPeriods)): ?>

                                                                <div class="no-elections">
                                                                    <i class="mdi mdi-calendar-clock mdi-48px text-muted mb-3"></i>
                                                                    <h5>Upcoming Elections</h5>
                                                                    <p class="text-muted">There are elections scheduled to start soon for your assigned precinct(s):</p>
                                                                    <?php foreach ($upcomingVotingPeriods as $election): ?>
                                                                        <p>
                                                                            ● <?= htmlspecialchars($election['election_name']) ?>
                                                                            – starts at <?= date('M d, Y h:i A', strtotime($election['start_period'])) ?>
                                                                        </p>
                                                                    <?php endforeach; ?>
                                                                </div>

                                                            <?php else: ?>

                                                                <!-- All ongoing/paused elections as selectable cards -->
                                                                <div class="elections-grid">
                                                                    <?php foreach ($ongoingVotingPeriods as $index => $election):
                                                                        // Fetch linked event title
                                                                        $stmt2 = $pdo->prepare("SELECT event_title FROM events WHERE candidacy = :election_id LIMIT 1");
                                                                        $stmt2->execute(['election_id' => $election['election_id']]);
                                                                        $event = $stmt2->fetch(PDO::FETCH_ASSOC);
                                                                        $event_title = $event['event_title'] ?? 'No Event Found';

                                                                        $is_active   = ($index === 0) ? 'active' : '';
                                                                        $hasPeriod   = !empty($election['voting_period_id']);

                                                                        // Determine status label and badge class
                                                                        if (!$hasPeriod) {
                                                                            $statusLabel = 'No Period Set';
                                                                            $statusClass = 'status-no-period';
                                                                        } else {
                                                                            $statusLabel = htmlspecialchars($election['vp_status']);
                                                                            $statusClass = strtolower($election['vp_status']) === 'paused'
                                                                                ? 'status-paused'
                                                                                : 'status-ongoing';
                                                                        }

                                                                        // Use re_start/re_end if available, otherwise normal period
                                                                        $displayStart = $election['re_start_period'] ?: ($election['start_period'] ?? null);
                                                                        $displayEnd   = $election['re_end_period']   ?: ($election['end_period']   ?? null);
                                                                    ?>
                                                                        <div class="election-option <?= $is_active ?>"
                                                                            data-election-name="<?= htmlspecialchars($election['election_name']) ?>"
                                                                            data-precinct-id="<?= htmlspecialchars($election['precinct_id']) ?>"
                                                                            data-precinct-name="<?= htmlspecialchars($election['precinct_name']) ?>"
                                                                            data-voting-period-id="<?= htmlspecialchars($election['voting_period_id'] ?? '') ?>"
                                                                            data-status="<?= htmlspecialchars($election['vp_status'] ?? $election['election_status']) ?>"
                                                                            data-start-period="<?= htmlspecialchars($displayStart ?? '') ?>"
                                                                            data-end-period="<?= htmlspecialchars($displayEnd ?? '') ?>">

                                                                            <h6 class="mb-1 namer"><?= htmlspecialchars($election['election_name']) ?></h6>
                                                                            <div><span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span></div>
                                                                            <small class="namer">Precinct: <?= htmlspecialchars($election['precinct_name']) ?></small>

                                                                            <div class="election-details mt-2">
                                                                                <small><strong>Event:</strong> <?= htmlspecialchars($event_title) ?></small><br>
                                                                                <small><strong>Period:</strong>
                                                                                    <?php if ($displayStart): ?>
                                                                                        <?= date('M d, Y h:i A', strtotime($displayStart)) ?>
                                                                                        <?php if ($displayEnd): ?>
                                                                                            – <?= date('M d, Y h:i A', strtotime($displayEnd)) ?>
                                                                                        <?php else: ?>
                                                                                            – TBD
                                                                                        <?php endif; ?>
                                                                                    <?php else: ?>
                                                                                        Not yet configured
                                                                                    <?php endif; ?>
                                                                                </small>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>

                                                            <?php endif; ?>
                                                        </div><!-- /.election-selector -->

                                                        <!-- ===== Selected Election Info + Timer ===== -->
                                                        <?php if ($hasOngoing): ?>

                                                            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                                                                <div>
                                                                    <h2 class="card-title card-title-dash" id="selectedElectionName"></h2>
                                                                    <p class="text-muted mb-0" id="selectedPrecinctName"></p>
                                                                </div>
                                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                                    <div class="countdown-container">
                                                                        <i class="mdi mdi-clock-outline me-2" style="color:#B22222;"></i>
                                                                        <input type="text" class="timer-input" id="secondsTimer" value="0:00:00" readonly>
                                                                    </div>
                                                                    <div class="countdown-container">
                                                                        <i class="mdi mdi-calendar me-2" style="color:#666;"></i>
                                                                        <input type="text" class="timer-input" style="color:#666;" id="DateEnding" readonly>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- QR Scanner (shown when period is active) -->
                                                            <div class="qr-container" id="qrContainer" style="display:none;">
                                                                <div class="scanner-header">
                                                                    <h2><i class="mdi mdi-qrcode-scan me-2"></i>Scan Student QR Code</h2>
                                                                </div>
                                                                <div class="position-relative mb-4">
                                                                    <div class="pad">
                                                                        <div id="reader"></div>
                                                                    </div>
                                                                </div>
                                                                <div class="scan-guide">
                                                                    <h5 class="mb-2"><i class="mdi mdi-information-outline me-2"></i>Scanning Instructions</h5>
                                                                    <ul class="mb-0">
                                                                        <li>Position the student ID QR code within the scanning frame</li>
                                                                        <li>Hold steady until verification is complete</li>
                                                                        <li>Make sure lighting is adequate for better scanning</li>
                                                                    </ul>
                                                                </div>
                                                                <div class="text-center mt-3">
                                                                    <p>Scanned Result: <span id="qrResult" class="fw-bold"></span></p>
                                                                    <form id="redirectForm" action="vote_qr_code.php" method="POST" style="display:none;">
                                                                        <input type="hidden" name="voting_period_id" id="formVotingPeriodId">
                                                                        <input type="hidden" name="student_id" id="formStudentId">
                                                                        <input type="hidden" name="election_name" id="formElectionName">
                                                                        <input type="hidden" name="precinct_name" id="formPrecinctName">
                                                                        <input type="hidden" name="precinct_id" id="formPrecinctId">
                                                                    </form>
                                                                </div>
                                                            </div>

                                                            <!-- Shown when period is inactive / paused / ended / no period -->
                                                            <div class="no-elections" id="scanningUnavailable" style="display:none;">
                                                                <i class="mdi mdi-clock-alert mdi-48px text-muted mb-3"></i>
                                                                <h5 id="scanningUnavailableTitle">Scanning Unavailable</h5>
                                                                <p class="text-muted" id="scanningUnavailableMsg">
                                                                    The voting period for the selected election has ended or is not currently active.
                                                                </p>
                                                            </div>

                                                        <?php endif; ?>

                                                    </div><!-- /.card-body -->
                                                </div><!-- /.card -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /.content-wrapper -->
            </div><!-- /.main-panel -->
        </div><!-- /.page-body-wrapper -->
    </div><!-- /.container-scroller -->

    <!-- jQuery & DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // -------------------------------------------------------
        // All ongoing elections passed from PHP
        // -------------------------------------------------------
        const electionsData = <?= json_encode($ongoingVotingPeriods) ?>;
        let currentElection = {};
        let timerInterval = null;
        let html5QrcodeScanner = null;
        let isProcessing = false;
        let scannerRunning = false;

        // -------------------------------------------------------
        // DOMContentLoaded
        // -------------------------------------------------------
        document.addEventListener('DOMContentLoaded', () => {
            const electionOptions = document.querySelectorAll('.election-option');
            if (!electionOptions.length) return;

            // Click handler for each card
            electionOptions.forEach(option => {
                option.addEventListener('click', function() {
                    electionOptions.forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    selectElection(this);
                });
            });

            // Auto-select first card
            electionOptions[0].click();
        });

        // -------------------------------------------------------
        // selectElection — reads data attributes and updates UI
        // -------------------------------------------------------
        function selectElection(cardEl) {
            const electionName = cardEl.dataset.electionName;
            const precinctId = cardEl.dataset.precinctId;
            const votingPeriodId = cardEl.dataset.votingPeriodId;

            // Match by both election_name AND precinct_id to handle
            // the same election appearing in multiple precincts
            const election = electionsData.find(e =>
                e.election_name === electionName &&
                String(e.precinct_id) === String(precinctId) &&
                String(e.voting_period_id ?? '') === String(votingPeriodId)
            );
            if (!election) return;

            // Use re_start/re_end if available (paused elections re-open window)
            const effectiveStart = election.re_start_period || election.start_period || null;
            const effectiveEnd = election.re_end_period || election.end_period || null;

            // Determine working status
            const workingStatus = election.vp_status || election.election_status || 'Unknown';

            currentElection = {
                electionName: election.election_name,
                precinctName: election.precinct_name,
                precinctId: election.precinct_id,
                votingPeriodId: election.voting_period_id || null,
                startPeriod: effectiveStart,
                endPeriod: effectiveEnd,
                status: workingStatus
            };

            // Update header
            const statusClass = workingStatus.toLowerCase() === 'paused' ? 'paused' : '';
            document.getElementById('selectedElectionName').innerHTML =
                currentElection.electionName +
                ` <span class="election-status ${statusClass}">${workingStatus}</span>`;
            document.getElementById('selectedPrecinctName').textContent =
                'Precinct: ' + currentElection.precinctName;

            // Update ending date display
            const dater = document.getElementById('DateEnding');
            if (currentElection.endPeriod) {
                const d = new Date(currentElection.endPeriod);
                dater.value = d.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } else {
                dater.value = 'TBD';
            }

            updateTimerAndScanner();
        }

        // -------------------------------------------------------
        // updateTimerAndScanner
        // -------------------------------------------------------
        function updateTimerAndScanner() {
            clearInterval(timerInterval);

            const qrContainer = document.getElementById('qrContainer');
            const scanningUnavailable = document.getElementById('scanningUnavailable');
            const timerDisplay = document.getElementById('secondsTimer');

            if (!qrContainer) return; // No ongoing elections at all

            const now = new Date();
            const status = currentElection.status;
            const end = currentElection.endPeriod ? new Date(currentElection.endPeriod) : null;

            let remainingSeconds = 0;
            let scanAllowed = false;

            if (status === 'Ongoing') {
                if (!currentElection.votingPeriodId) {
                    // Election is ongoing but has no voting period row yet
                    scanAllowed = false;
                } else {
                    remainingSeconds = end ? Math.floor((end - now) / 1000) : Infinity;
                    scanAllowed = remainingSeconds === Infinity || remainingSeconds > 0;
                }
            } else if (status === 'Paused') {
                scanAllowed = false;
                remainingSeconds = 0;
            }

            if (scanAllowed) {
                qrContainer.style.display = 'flex';
                scanningUnavailable.style.display = 'none';
                timerDisplay.style.color = '#B22222';

                if (remainingSeconds !== Infinity) {
                    startDynamicTimer(remainingSeconds);
                } else {
                    timerDisplay.value = 'No End Set';
                }

                initializeScanner();

            } else {
                // Cannot scan
                qrContainer.style.display = 'none';
                scanningUnavailable.style.display = 'block';

                if (!currentElection.votingPeriodId) {
                    document.getElementById('scanningUnavailableTitle').textContent = 'No Voting Period Configured';
                    document.getElementById('scanningUnavailableMsg').textContent =
                        'This election does not have a voting period set up yet. Please contact your administrator.';
                    timerDisplay.value = 'N/A';
                    timerDisplay.style.color = '#6c757d';
                } else if (status === 'Paused') {
                    document.getElementById('scanningUnavailableTitle').textContent = 'Election is Paused';
                    document.getElementById('scanningUnavailableMsg').textContent =
                        'This election is currently paused. Scanning will resume when the moderator restarts it.';
                    timerDisplay.value = 'Paused';
                    timerDisplay.style.color = '#e67e00';
                } else {
                    document.getElementById('scanningUnavailableTitle').textContent = 'Scanning Unavailable';
                    document.getElementById('scanningUnavailableMsg').textContent =
                        'The voting period for the selected election has ended or is not currently active.';
                    timerDisplay.value = "Time's Up!";
                    timerDisplay.style.color = '#dc3545';
                }

                stopScanner();
            }
        }

        // -------------------------------------------------------
        // startDynamicTimer
        // -------------------------------------------------------
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

        // -------------------------------------------------------
        // initializeScanner
        // -------------------------------------------------------
        function initializeScanner() {
            if (scannerRunning && html5QrcodeScanner) return; // Already running

            stopScanner(); // Clear any existing instance first

            html5QrcodeScanner = new Html5QrcodeScanner('reader', {
                fps: 10,
                qrbox: 250
            });
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
            scannerRunning = true;
        }

        // -------------------------------------------------------
        // stopScanner
        // -------------------------------------------------------
        function stopScanner() {
            if (html5QrcodeScanner) {
                try {
                    html5QrcodeScanner.clear();
                } catch (e) {
                    // Ignore errors during cleanup
                }
                html5QrcodeScanner = null;
            }
            scannerRunning = false;
        }

        // -------------------------------------------------------
        // onScanSuccess
        // -------------------------------------------------------
        function onScanSuccess(decodedText, decodedResult) {
            if (isProcessing) return;
            isProcessing = true;

            stopScanner();

            document.getElementById('qrResult').innerText = decodedText;

            if (!currentElection.votingPeriodId) {
                Swal.fire({
                    icon: 'error',
                    title: 'No Active Voting Period',
                    text: 'The selected election does not have an active voting period.',
                    confirmButtonColor: '#B22222'
                }).then(() => {
                    // Re-start scanner after dismissal so moderator can retry
                    isProcessing = false;
                    initializeScanner();
                });
                return; // early return — no isProcessing = false here
            }

            Swal.fire({
                title: 'Verifying QR Code...',
                text: 'Please wait.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('process_qr.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'qrData=' + encodeURIComponent(decodedText) +
                        '&voting_period_id=' + encodeURIComponent(currentElection.votingPeriodId) +
                        '&election_name=' + encodeURIComponent(currentElection.electionName) +
                        '&precinct_name=' + encodeURIComponent(currentElection.precinctId) +
                        '&precinct_id=' + encodeURIComponent(currentElection.precinctId)
                })
                .then(res => res.json())
                .then(data => {
                    Swal.close();
                    if (data.status === 'success') {
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
                            document.getElementById('formVotingPeriodId').value = currentElection.votingPeriodId;
                            document.getElementById('formStudentId').value = decodedText;
                            document.getElementById('formElectionName').value = currentElection.electionName;
                            document.getElementById('formPrecinctName').value = currentElection.precinctName;
                            document.getElementById('formPrecinctId').value = currentElection.precinctId;
                            document.getElementById('redirectForm').submit();
                        });
                    } else {
                        isProcessing = false;
                        initializeScanner();
                        Swal.fire({
                            icon: 'error',
                            title: 'Verification Failed',
                            text: data.message,
                            background: '#f8f9fa',
                            iconColor: '#dc3545',
                            confirmButtonColor: '#B22222'
                        });
                    }

                })
                .catch(error => {
                    Swal.close();
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Error',
                        text: 'An error occurred while processing your request.',
                        confirmButtonColor: '#B22222'
                    });
                    isProcessing = false;
                });
        }

        // -------------------------------------------------------
        // onScanFailure (silent)
        // -------------------------------------------------------
        function onScanFailure(error) {
            // Suppress noisy console warnings from the scanner
        }
    </script>

</body>

</html>