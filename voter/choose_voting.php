<?php
date_default_timezone_set('Asia/Manila');
session_start();
include('includes/conn.php');

// ── Auth: must be a voter ──────────────────────────────────────────────────
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'voter') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = "NON_VOTER";
    header("Location: ../index.php");
    exit();
}

// ── Resolve voter from session email ──────────────────────────────────────
$user_email     = $_SESSION['email'] ?? null;
$student_id     = null;
$first_name_student  = '';
$middle_name_student = '';
$last_name_student   = '';

if ($user_email) {
    $stmt = $pdo->prepare("SELECT student_id, first_name, middle_name, last_name FROM voters WHERE email = ?");
    $stmt->execute([$user_email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $student_id          = $result['student_id'];
        $_SESSION['user_id'] = $student_id;
        $first_name_student  = $result['first_name'];
        $middle_name_student = $result['middle_name'];
        $last_name_student   = $result['last_name'];
    }
}

// ── Fetch precinct & voter status ─────────────────────────────────────────
$status        = null;
$college       = null;
$precinct_name = null;
$collegeName   = 'N/A';

if ($student_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.name  AS precinct_name,
                   pv.precinct,
                   pv.status,
                   v.college
            FROM precinct_voters pv
            LEFT JOIN voters v    ON pv.student_id = v.student_id
            INNER JOIN precincts p ON p.id = pv.precinct
            WHERE pv.student_id = ?
        ");
        $stmt->execute([$student_id]);
        $voter = $stmt->fetch(PDO::FETCH_ASSOC);

        $precinct_name = $voter['precinct_name'] ?? null;
        $status        = $voter['status']        ?? null;
        $college       = $voter['college']       ?? null;

        if (!empty($college)) {
            $stmt = $pdo->prepare("SELECT college_name FROM colleges WHERE college_id = ? LIMIT 1");
            $stmt->execute([$college]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) $collegeName = $row['college_name'];
        }
    } catch (PDOException $e) {
        error_log("Precinct fetch error: " . $e->getMessage());
    }
}

// ── Whether this voter is assigned to ANY precinct at all ─────────────────
// $precinct_name === null  →  not in precinct_voters at all
$isInPrecinct = ($precinct_name !== null);

// ── Fetch all voting periods available to this voter ──────────────────────
$votingPeriods = [];
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT vp.*,
               e.id   AS election_id,
               e.election_name,
               ay.semester        AS semester,
               ay.start_date      AS school_year_start,
               ay.end_date        AS school_year_end,
               CASE
                   WHEN vp.status = 'Ongoing'
                        AND NOW() BETWEEN vp.start_period AND vp.end_period THEN 'Available'
                   WHEN vp.status = 'Ongoing'
                        AND NOW() < vp.start_period               THEN 'Ongoing'
                   WHEN vp.status = 'Ongoing'
                        AND NOW() > vp.end_period                 THEN 'Ended'
                   WHEN vp.status = 'Paused'                      THEN 'Paused'
                   WHEN vp.status = 'Scheduled'                   THEN 'Scheduled'
                   ELSE 'Inactive'
               END AS availability,
               COALESCE(v.vote_timestamp, 'Not Voted') AS vote_status
        FROM voting_periods vp
        JOIN elections e      ON vp.election_id = e.id
        JOIN academic_years ay ON e.academic_year_id = ay.id
        LEFT JOIN votes v
               ON vp.id = v.voting_period_id
              AND v.student_id = ?
        WHERE vp.status IN ('Ongoing','Paused','Scheduled')
        ORDER BY
            CASE
                WHEN vp.status = 'Ongoing'
                     AND NOW() BETWEEN vp.start_period AND vp.end_period THEN 1
                WHEN vp.status = 'Paused'    THEN 2
                WHEN vp.status = 'Scheduled' THEN 3
                ELSE 4
            END,
            vp.start_period ASC
    ");
    $stmt->execute([$student_id]);
    $votingPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Voting period fetch error: " . $e->getMessage());
}

// ── For each voting period, check if voter's precinct is assigned to it ───
// precinct_elections.election_name  = elections.id  (int)
// precinct_voters.precinct          = precincts.id  (stored as varchar)
$precinctsAssignedToElection = [];   // election_id → bool
foreach ($votingPeriods as $period) {
    $electionId = $period['election_id'];
    if (!isset($precinctsAssignedToElection[$electionId])) {
        if (!$isInPrecinct) {
            $precinctsAssignedToElection[$electionId] = false;
        } else {
            // Get this voter's precinct id from precinct_voters
            $stmt = $pdo->prepare("
                SELECT precinct FROM precinct_voters WHERE student_id = ? LIMIT 1
            ");
            $stmt->execute([$student_id]);
            $voterPrecinctId = $stmt->fetchColumn();

            // Check if that precinct_id is linked to this election
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM precinct_elections
                WHERE precinct_id = ? AND election_name = ?
            ");
            $stmt->execute([$voterPrecinctId, $electionId]);
            $precinctsAssignedToElection[$electionId] = (int)$stmt->fetchColumn() > 0;
        }
    }
}

// ── Handle election selection ──────────────────────────────────────────────
$selectedElectionId = isset($_GET['election_id']) ? intval($_GET['election_id']) : null;
$selectedElection   = null;

if ($selectedElectionId) {
    foreach ($votingPeriods as $period) {
        if ($period['id'] == $selectedElectionId) {
            $selectedElection = $period;
            break;
        }
    }
}
if (!$selectedElection && !empty($votingPeriods)) {
    $selectedElection   = $votingPeriods[0];
    $selectedElectionId = $selectedElection['id'];
}

// ── Candidate data for the selected election ───────────────────────────────
$candidatesByLevel = ['Central' => [], 'Local' => []];
$remaining_seconds = 0;
$votingPeriodId    = null;
$votingPeriodName  = '';
$votingPeriodStart = '';
$votingPeriodEnd   = '';
$votingPeriodStatus = '';
$votingPeriodReStart = null;
$votingPeriodReEnd   = null;

if ($selectedElection) {
    $current_date        = date('Y-m-d H:i:s');
    $votingPeriodId      = $selectedElection['id'];
    $votingPeriodName    = $selectedElection['election_name'];
    $votingPeriodStart   = $selectedElection['start_period'];
    $votingPeriodEnd     = $selectedElection['end_period'];
    $votingPeriodStatus  = $selectedElection['status'];
    $votingPeriodReStart = $selectedElection['re_start_period'];
    $votingPeriodReEnd   = $selectedElection['re_end_period'];
    $votingPeriodElectionId = $selectedElection['election_id'];

    // Countdown seconds
    $useReSchedule = isset($votingPeriodReStart) && isset($votingPeriodReEnd)
        && $votingPeriodReStart && $votingPeriodReEnd;
    if ($votingPeriodStatus === 'Ongoing') {
        $endTs = $useReSchedule ? strtotime($votingPeriodReEnd)   : strtotime($votingPeriodEnd);
    } else {
        $endTs = $useReSchedule ? strtotime($votingPeriodReStart) : strtotime($votingPeriodStart);
    }
    $remaining_seconds = max(0, $endTs - strtotime($current_date));

    // Only load candidates if voter is verified / pending / revoted
    if (in_array($status, ['verified', 'pending', 'revoted'])) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ? LIMIT 1");
            $stmt->execute([$votingPeriodElectionId]);
            $formId = $stmt->fetchColumn();

            if ($formId) {
                $table = ($status === 'revoted') ? 'tied_candidates' : 'candidates';
                $stmt  = $pdo->prepare("SELECT id FROM {$table} WHERE form_id = ? AND status = 'accepted'");
                $stmt->execute([$formId]);
                $candidateIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($candidateIds as $cid) {
                    $stmt = $pdo->prepare("
                        SELECT cr.value, ff.field_name
                        FROM candidate_responses cr
                        JOIN form_fields ff ON cr.field_id = ff.id
                        WHERE cr.candidate_id = ?
                    ");
                    $stmt->execute([$cid]);
                    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $stmt = $pdo->prepare("SELECT file_path FROM candidate_files WHERE candidate_id = ? LIMIT 1");
                    $stmt->execute([$cid]);
                    $file = $stmt->fetch(PDO::FETCH_ASSOC);

                    $cData = [
                        'id'         => $cid,
                        'photo'      => $file['file_path'] ?? '',
                        'name'       => '',
                        'party'    => '',
                        'position'   => '',
                        'student_id' => null,
                        'platform'   => '',
                    ];
                    foreach ($responses as $r) {
                        match ($r['field_name']) {
                            'full_name'  => $cData['name']       = $r['value'],
                            'party'      => $cData['party']      = $r['value'],
                            'position'   => $cData['position']   = $r['value'],
                            'student_id' => $cData['student_id'] = $r['value'],
                            'platform'   => $cData['platform']   = $r['value'],
                            default      => null,
                        };
                    }

                    $stmt = $pdo->prepare("SELECT level FROM positions WHERE name = ? LIMIT 1");
                    $stmt->execute([$cData['position']]);
                    $lvl   = $stmt->fetchColumn();
                    $level = ($lvl === 'Central') ? 'Central' : 'Local';

                    $candidateCollege = null;
                    if ($cData['student_id']) {
                        $stmt = $pdo->prepare("SELECT college FROM voters WHERE student_id = ? LIMIT 1");
                        $stmt->execute([$cData['student_id']]);
                        $candidateCollege = $stmt->fetchColumn();
                    }

                    if ($level === 'Central') {
                        $candidatesByLevel['Central'][$cData['position']][] = $cData;
                    } elseif ($level === 'Local' && $candidateCollege === $college) {
                        $candidatesByLevel['Local'][$cData['position']][] = $cData;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Candidate fetch error: " . $e->getMessage());
        }
    }
}

// ── Events / candidacy filing ──────────────────────────────────────────────
$events = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, event_title, cover_image, event_details,
               registration_enabled, status,
               registration_start, registration_deadline
        FROM events
        WHERE status = 'published' AND registration_enabled = 1
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Events fetch error: " . $e->getMessage());
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
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <link rel="shortcut icon" href="images/favicon.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .card {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .1);
            transition: all .3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, .15);
        }

        .election-card {
            cursor: pointer;
            border: 2px solid transparent;
            transition: all .3s ease;
        }

        .election-card:hover {
            border-color: #B22222;
            transform: translateY(-5px);
        }

        .election-card.selected {
            border-color: #B22222;
            background-color: #f8f9fa;
        }

        .election-card.no-precinct {
            cursor: default;
            opacity: .75;
        }

        .election-card.no-precinct:hover {
            transform: none;
            border-color: transparent;
        }

        .election-status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: .8rem;
        }

        .countdown-container {
            background: #fff;
            padding: 10px 20px;
            border-radius: 50px;
            border: 1px solid #dee2e6;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .1);
        }

        .timer-input {
            border: none;
            background: transparent;
            font-weight: bold;
            color: #B22222;
            font-size: .8rem;
            width: 140px;
            text-align: center;
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, .1);
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
            background: rgba(0, 0, 0, .7);
            color: white;
            padding: 20px 40px;
            border-radius: 10px;
            text-align: center;
            font-size: 1.2rem;
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
            background: linear-gradient(135deg, #950000 0%, #c0392b 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        /* Precinct-not-assigned banner */
        .precinct-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 2px solid #f39c12;
            border-radius: 12px;
            padding: 18px 24px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .precinct-warning i {
            font-size: 2rem;
            color: #e67e22;
            flex-shrink: 0;
        }

        .precinct-warning .pw-title {
            font-weight: 700;
            color: #7d4e00;
            font-size: 1rem;
        }

        .precinct-warning .pw-sub {
            color: #856404;
            font-size: .875rem;
            margin-top: 2px;
        }
    </style>
</head>

<body>
    <div class="container-scroller">

        <!-- ═══ NAVBAR ═══ -->
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
                        <small style="font-size:16px;"><b>WMSU I-Elect</b></small>
                    </a>
                    <a class="navbar-brand brand-logo-mini" href="index.php">
                        <img src="images/wmsu-logo.png" class="logo img-fluid" alt="logo" />
                    </a>
                </div>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-top">
                <ul class="navbar-nav">
                    <li class="nav-item font-weight-semibold d-none d-lg-block ms-0">
                        <h1 class="welcome-text">Welcome,
                            <span class="text-white fw-bold"><?= htmlspecialchars($first_name_student . ' ' . $last_name_student) ?></span>
                        </h1>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                        <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <img class="img-xs rounded-circle logo" src="images/wmsu-logo.png" style="background-color:white;" alt="Profile image">
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
            <!-- ═══ SIDEBAR ═══ -->
            <nav class="sidebar sidebar-offcanvas" id="sidebar">
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="mdi mdi-grid-large menu-icon"></i>
                            <span class="menu-title">Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="candidacy.php">
                            <i class="mdi mdi-account menu-icon"></i>
                            <span class="menu-title">File Candidacy</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="choose_voting.php" style="background-color:#B22222 !important;">
                            <i class="mdi mdi-account-group menu-icon" style="color:white !important;"></i>
                            <span class="menu-title" style="color:white !important;">Vote</span>
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
                                            <a class="nav-link active ps-0" data-bs-toggle="tab" href="#overview" role="tab">Dashboard</a>
                                        </li>
                                    </ul>
                                </div>

                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel">

                                        <!-- ══ NOT IN ANY PRECINCT — global banner ══ -->
                                        <?php if (!$isInPrecinct): ?>
                                            <div class="precinct-warning mt-3">
                                                <i class="mdi mdi-alert-circle-outline"></i>
                                                <div>
                                                    <div class="pw-title">You are not assigned to any precinct</div>
                                                    <div class="pw-sub">Your account has not been assigned to a voting precinct. </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- ══ AVAILABLE ELECTIONS ══ -->
                                        <div class="card card-rounded mb-4 mt-3">
                                            <div class="card-header bg-primary text-white">
                                                <h4 class="mb-0"><i class="mdi mdi-vote me-2"></i>Available Elections</h4>
                                            </div>
                                            <div class="card-body">

                                                <?php
                                                $now = new DateTime('now', new DateTimeZone('Asia/Manila'));

                                                $isRevoting = false;

                                                if (!empty($period['re_start_period']) && !empty($period['re_end_period'])) {
                                                    $reStart = new DateTime($period['re_start_period'], new DateTimeZone('Asia/Manila'));
                                                    $reEnd   = new DateTime($period['re_end_period'], new DateTimeZone('Asia/Manila'));

                                                    if ($now >= $reStart && $now <= $reEnd) {
                                                        // ✅ ACTIVE REVOTING
                                                        $sc = 'bg-primary';
                                                        $st = 'Revoting';
                                                        $isRevoting = true;
                                                    } elseif ($now < $reStart) {
                                                        // ⏳ UPCOMING REVOTING
                                                        $sc = 'bg-info';
                                                        $st = 'Revoting Soon';
                                                        $isRevoting = true;
                                                    } elseif ($now > $reEnd) {
                                                        // 🕓 REVOTING ENDED (optional to show or ignore)
                                                        $sc = 'bg-dark';
                                                        $st = 'Revoting Ended';
                                                        $isRevoting = true;
                                                    }
                                                } elseif (!empty($period['start_period']) && !empty($period['end_period'])) {
                                                    $reStart = new DateTime($period['start_period'], new DateTimeZone('Asia/Manila'));
                                                    $reEnd   = new DateTime($period['end_period'], new DateTimeZone('Asia/Manila'));

                                                    if ($now >= $reStart && $now <= $reEnd) {
                                                        // ✅ ACTIVE REVOTING
                                                        $sc = 'bg-success';
                                                        $st = 'Voting Available';
                                                        $isRevoting = true;
                                                    } elseif ($now < $reStart) {
                                                        // ⏳ UPCOMING REVOTING
                                                        $sc = 'bg-info';
                                                        $st = 'Voting Available Soon';
                                                        $isRevoting = true;
                                                    } elseif ($now > $reEnd) {
                                                        // 🕓 REVOTING ENDED (optional to show or ignore)
                                                        $sc = 'bg-dark';
                                                        $st = 'Voting Ended';
                                                        $isRevoting = true;
                                                    }
                                                }

                                                $now = new DateTime('now', new DateTimeZone('Asia/Manila'));

                                                $isRevotingActive = false;

                                                if (!empty($period['re_start_period']) && !empty($period['re_end_period'])) {
                                                    $reStart = new DateTime($period['re_start_period']);
                                                    $reEnd   = new DateTime($period['re_end_period']);

                                                    $isRevotingActive = ($now >= $reStart && $now <= $reEnd);
                                                }
                                                ?>

                                                <?php if (!empty($votingPeriods)): ?>
                                                    <div class="election-grid">
                                                        <?php foreach ($votingPeriods as $period):

                                                            $now = new DateTime('now', new DateTimeZone('Asia/Manila'));

                                                            $reStart = !empty($period['re_start_period']) ? new DateTime($period['re_start_period'], new DateTimeZone('Asia/Manila')) : null;
                                                            $reEnd   = !empty($period['re_end_period']) ? new DateTime($period['re_end_period'], new DateTimeZone('Asia/Manila')) : null;

                                                            $start = new DateTime($period['start_period'], new DateTimeZone('Asia/Manila'));
                                                            $end   = new DateTime($period['end_period'], new DateTimeZone('Asia/Manila'));

                                                            // ✅ Determine states
                                                            $hasRevoting = ($reStart && $reEnd);
                                                            $isRevotingActive = $hasRevoting && ($now >= $reStart && $now <= $reEnd);
                                                            $isVotingActive   = ($now >= $start && $now <= $end);

                                                            $electionId = $period['election_id'];

                                                            // ✅ FIX: define isAssigned
                                                            $isAssigned = $precinctsAssignedToElection[$electionId] ?? false;

                                                            // ✅ FIX: define isSelected
                                                            $isSelected = $selectedElection && $selectedElection['id'] == $period['id'];

                                                            // ✅ STATUS BADGE (top-right)
                                                            if ($isRevotingActive) {
                                                                $sc = 'bg-primary';
                                                                $st = 'Revoting';
                                                            } elseif ($isVotingActive) {
                                                                $sc = 'bg-success';
                                                                $st = 'Voting Available';
                                                            } elseif ($now < $start) {
                                                                $sc = 'bg-info';
                                                                $st = 'Scheduled';
                                                            } else {
                                                                $sc = 'bg-dark';
                                                                $st = 'Ended';
                                                            }

                                                            // ✅ VOTE STATUS LABEL (bottom)
                                                            if ($isRevotingActive) {

                                                                if ($period['vote_status'] === 'Revoted') {
                                                                    $statusLabel = 'Revoted ✓';
                                                                    $statusClass = 'bg-primary';
                                                                } elseif ($period['vote_status'] !== 'Not Voted') {
                                                                    $statusLabel = 'Revoting Available';
                                                                    $statusClass = 'bg-warning text-dark';
                                                                } else {
                                                                    $statusLabel = 'Not Voted';
                                                                    $statusClass = 'bg-light text-dark';
                                                                }
                                                            } else {
                                                                // ✅ NORMAL voting ALWAYS works if revoting not active
                                                                if ($period['vote_status'] !== 'Not Voted') {
                                                                    $statusLabel = 'Voted ✓';
                                                                    $statusClass = 'bg-success';
                                                                } else {
                                                                    $statusLabel = 'Not Voted';
                                                                    $statusClass = 'bg-light text-dark';
                                                                }
                                                            }

                                                            $hasVoted = $period['vote_status'] !== 'Not Voted';
                                                        ?>
                                                            <div class="card election-card <?= $isSelected ? 'selected' : '' ?> <?= !$isAssigned ? 'no-precinct' : '' ?>"
                                                                <?= $isAssigned ? "onclick=\"selectElection({$period['id']})\"" : '' ?>>
                                                                <div class="card-body position-relative">
                                                                    <span class="badge <?= $sc ?> election-status-badge"><?= $st ?></span>

                                                                    <?php if (!$isAssigned): ?>
                                                                        <span class="badge bg-warning text-dark" style="position:absolute;top:10px;left:10px;font-size:.75rem;">
                                                                            <i class="mdi mdi-lock-outline me-1"></i>Not in Precinct
                                                                        </span>
                                                                    <?php endif; ?>

                                                                    <h5 class="card-title mt-3"><?= htmlspecialchars($period['election_name']) ?></h5>
                                                                    <p class="card-text">
                                                                        <small class="text-muted">
                                                                            <?= htmlspecialchars($period['semester']) ?> &mdash;
                                                                            <?= htmlspecialchars($period['school_year_start']) ?>–<?= htmlspecialchars($period['school_year_end']) ?>
                                                                        </small>
                                                                    </p>
                                                                    <p>
                                                                        <strong>Period:</strong><br>
                                                                        <?= date('M j, Y g:i A', strtotime($period['start_period'])) ?><br>to<br>
                                                                        <?= date('M j, Y g:i A', strtotime($period['end_period'])) ?>
                                                                    </p>
                                                                    <p><strong>Status:</strong>
                                                                        <span class="badge <?php echo $statusClass; ?>">
                                                                            <?php echo $statusLabel; ?>
                                                                        </span>
                                                                    </p>

                                                                    <?php if (!$isAssigned): ?>
                                                                        <div class="text-center mt-3">
                                                                            <button class="btn btn-warning text-dark btn-sm w-100" disabled>
                                                                                <i class="mdi mdi-lock-outline me-1"></i>Not Assigned to This Election's Precinct
                                                                            </button>
                                                                        </div>
                                                                    <?php elseif ($isSelected): ?>
                                                                        <div class="text-center mt-3">
                                                                            <?php if ($isRevotingActive): ?>

                                                                                <!-- REVOTING MODE -->
                                                                                <a href="vote.php?election_id=<?= $period['id'] ?>" class="btn vote-btn text-white">
                                                                                    <i class="mdi mdi-refresh me-2"></i>Revote
                                                                                </a>

                                                                            <?php elseif ($isVotingActive): ?>

                                                                                <!-- NORMAL VOTING ALWAYS WORKS -->
                                                                                <a href="vote.php?election_id=<?= $period['id'] ?>" class="btn vote-btn text-white">
                                                                                    <i class="mdi mdi-ballot me-2"></i>
                                                                                    <?= $hasVoted ? 'View Vote' : 'Vote Now' ?>
                                                                                </a>

                                                                            <?php else: ?>

                                                                                <button class="btn btn-secondary w-100" disabled>
                                                                                    <i class="mdi mdi-block-helper me-1"></i>Voting Unavailable
                                                                                </button>

                                                                            <?php endif; ?>
                                                                        </div>
                                                                    <?php endif; ?>

                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-center py-4">
                                                        <i class="mdi mdi-calendar-remove" style="font-size:4rem;color:#6c757d;"></i>
                                                        <h4 class="text-muted mt-3">No Elections Available</h4>
                                                        <p class="text-muted">There are no active elections at this time. Please check back later.</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- ══ SELECTED ELECTION DETAIL PANEL ══ -->
                                        <?php if ($selectedElection):
                                            $selElectionId = $selectedElection['election_id'];
                                            $selAssigned   = $precinctsAssignedToElection[$selElectionId] ?? false;
                                            $hasVoted      = $selectedElection['vote_status'] !== 'Not Voted';

                                            $showBlur   = true;
                                            $overlayMsg = '';
                                            if (!$selAssigned) {
                                                $overlayMsg = 'You are not assigned to a precinct for this election.';
                                            } elseif ($status === 'voted' || $hasVoted) {
                                                $overlayMsg = 'You have already voted in this election!';
                                            } elseif (!in_array($status, ['verified', 'pending', 'revoted'])) {
                                                $overlayMsg = 'Please verify your COR to view candidates.';
                                            } elseif ($votingPeriodStatus === 'Paused') {
                                                $overlayMsg = 'Candidates hidden while voting is paused.';
                                            } elseif ($votingPeriodStatus === 'Scheduled') {
                                                $overlayMsg = 'Candidates will be revealed when voting starts.';
                                            } else {
                                                $showBlur = false;
                                            }
                                        ?>
                                            <div class="card card-rounded">
                                                <div class="card-body">

                                                    <!-- Election info header -->
                                                    <div class="election-info">
                                                        <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-3">
                                                            <div>
                                                                <h2 class="text-white mb-1">
                                                                    <?= htmlspecialchars($votingPeriodName) ?>
                                                                    <span class="badge bg-light text-dark ms-2"><?= ucfirst($votingPeriodStatus) ?></span>
                                                                </h2>
                                                                <p class="mb-1" style="color:rgba(255,255,255,.8);">
                                                                    <b>Precinct:</b> <?= htmlspecialchars($precinct_name ?? 'Not Assigned') ?>
                                                                </p>
                                                                <p class="mb-1" style="color:rgba(255,255,255,.8);">
                                                                    <b>Your College:</b> <?= htmlspecialchars($collegeName) ?>
                                                                </p>
                                                                <?php if (!$selAssigned): ?>
                                                                    <p class="mb-0 text-warning fw-bold">
                                                                        <i class="mdi mdi-alert me-1"></i>Your precinct is not assigned to this election.
                                                                    </p>
                                                                <?php elseif ($votingPeriodStatus === 'Scheduled'): ?>
                                                                    <p class="text-warning mb-0">
                                                                        Voting Starts: <?= date('F d, Y, g:i A', strtotime($votingPeriodStart)) ?>
                                                                    </p>
                                                                <?php elseif ($votingPeriodStatus === 'Paused'): ?>
                                                                    <p class="text-warning mb-0">Voting is temporarily paused. Please check back later.</p>
                                                                <?php endif; ?>
                                                            </div>

                                                            <?php if (in_array($votingPeriodStatus, ['Ongoing', 'Scheduled']) && $selAssigned): ?>
                                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                                    <div class="countdown-container">
                                                                        <i class="mdi mdi-clock-outline me-2" style="color:#950000;"></i>
                                                                        <input type="text" class="timer-input" id="secondsTimer" value="Loading…" readonly>
                                                                    </div>
                                                                    <div class="countdown-container">
                                                                        <i class="mdi mdi-calendar me-2" style="color:#950000;"></i>
                                                                        <input type="text" class="timer-input"
                                                                            value="<?php
                                                                                    $useRe = !empty($votingPeriodReStart) && !empty($votingPeriodReEnd);
                                                                                    if ($votingPeriodStatus === 'Ongoing')
                                                                                        echo date('F d, Y', strtotime($useRe ? $votingPeriodReEnd : $votingPeriodEnd));
                                                                                    else
                                                                                        echo date('F d, Y', strtotime($useRe ? $votingPeriodReStart : $votingPeriodStart));
                                                                                    ?>" readonly>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <!-- Precinct-not-assigned warning inside detail card -->
                                                    <?php if (!$selAssigned): ?>
                                                        <div class="precinct-warning">
                                                            <i class="mdi mdi-lock-outline"></i>
                                                            <div>
                                                                <div class="pw-title">Voting Disabled — Precinct Not Assigned</div>
                                                                <div class="pw-sub">
                                                                    Your precinct (<strong><?= htmlspecialchars($precinct_name ?? 'None') ?></strong>)
                                                                    is not part of the precincts assigned to
                                                                    "<strong><?= htmlspecialchars($votingPeriodName) ?></strong>".
                                                                    Please contact the election administrator if you believe this is an error.
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Candidate display -->
                                                    <div class="candidate-container">
                                                        <?php if ($showBlur): ?>
                                                            <div class="overlay-text"><?= htmlspecialchars($overlayMsg) ?></div>
                                                        <?php endif; ?>
                                                        <div class="container-fluid text-center <?= $showBlur ? 'blur-overlay' : '' ?>">
                                                            <?php foreach (['Central', 'Local'] as $level): ?>
                                                                <?php if (!empty($candidatesByLevel[$level])): ?>
                                                                    <h1 class="text-center text-primary"><?= strtoupper($level) ?></h1><br>
                                                                    <div class="row">
                                                                        <?php foreach ($candidatesByLevel[$level] as $position => $candidates): ?>
                                                                            <div class="col spacer text-center bordered">
                                                                                <h3 class="text-danger"><b><?= htmlspecialchars(strtoupper($position)) ?></b></h3><br>
                                                                                <div class="row">
                                                                                    <?php foreach ($candidates as $candidate):
                                                                                        $photo     = $candidate['photo'] ?? '';
                                                                                        $loginPath = "../login/uploads/candidates/" . $photo;
                                                                                        $adminPath = "../admin/uploads/candidates/" . $photo;
                                                                                        $imgSrc    = file_exists($loginPath) ? $loginPath
                                                                                            : (file_exists($adminPath) ? $adminPath
                                                                                                : "admin/uploads/candidates/default.jpg");
                                                                                    ?>
                                                                                        <div class="col">
                                                                                            <img src="<?= htmlspecialchars($imgSrc) ?>" class="profiler" alt="Candidate Photo"><br><br>
                                                                                            <h4><b><?= htmlspecialchars($candidate['name']) ?></b></h4>
                                                                                            <h5 class="text-success"><?= htmlspecialchars($candidate['party']) ?></h5>
                                                                                            <?php if ($candidate['platform']): ?>
                                                                                                <button class="btn btn-sm btn-outline-info mt-2 view-platform"
                                                                                                    data-platform="<?= htmlspecialchars($candidate['platform']) ?>">
                                                                                                    View Platform
                                                                                                </button>
                                                                                            <?php endif; ?>
                                                                                        </div>
                                                                                    <?php endforeach; ?>
                                                                                </div><br><br>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                <?php elseif ($level === 'Local' && $selAssigned): ?>
                                                                    <div class="alert alert-info">
                                                                        No local candidates found for your college (<?= htmlspecialchars($collegeName) ?>).
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>

                                                    <!-- Action button -->
                                                    <div class="text-center mt-4">
                                                        <?php if (!$selAssigned): ?>
                                                            <button class="btn btn-warning text-dark btn-lg" disabled>
                                                                <i class="mdi mdi-lock-outline me-2"></i>Voting Unavailable — Not in Precinct
                                                            </button>
                                                        <?php elseif ($hasVoted): ?>
                                                            <button class="btn btn-success btn-lg" disabled>
                                                                <i class="mdi mdi-check-circle me-2"></i>Already Voted
                                                            </button>
                                                            <p class="text-muted mt-2">You have already cast your vote in this election.</p>
                                                        <?php elseif ($selectedElection['availability'] === 'Available' && $status === 'verified'): ?>
                                                            <a href="vote.php?election_id=<?= $selectedElectionId ?>" class="btn vote-btn btn-lg text-white">
                                                                <i class="mdi mdi-ballot me-2"></i>Vote Now
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>

                                                </div>
                                            </div>
                                        <?php endif; /* end selectedElection */ ?>

                                        <!-- ══ CANDIDACY FILING SECTION ══ -->
                                        <div class="card mt-4">
                                            <div class="card-header <?= !empty($events) ? 'bg-warning' : 'bg-secondary' ?> text-white">
                                                <h4 class="mb-0">
                                                    <i class="mdi mdi-file me-2"></i>
                                                    <?= !empty($events) ? 'Candidacies to File' : 'No Candidacies Ongoing' ?>
                                                </h4>
                                            </div>
                                            <div class="card-body">
                                                <?php if (!empty($events)): ?>
                                                    <div class="row g-4">
                                                        <?php foreach ($events as $event):
                                                            $coverImage = (!empty($event['cover_image']) && file_exists('../uploads/event_covers/' . $event['cover_image']))
                                                                ? '../uploads/event_covers/' . htmlspecialchars($event['cover_image'])
                                                                : '../uploads/placeholder/ph.jpg';
                                                            $now      = new DateTime();
                                                            $deadline = new DateTime($event['registration_deadline']);
                                                            $isClosed = $now > $deadline;
                                                        ?>
                                                            <div class="col-12 col-md-4">
                                                                <div class="card h-100 shadow-sm">
                                                                    <img src="<?= $coverImage ?>" class="card-img-top"
                                                                        style="height:200px;object-fit:cover;" alt="Event Image">
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
                                                                                <button class="btn btn-secondary w-100" disabled>Registration Closed</button>
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
                                                        <i class="mdi mdi-calendar-remove" style="font-size:4rem;color:#6c757d;"></i>
                                                        <h4 class="text-muted mt-3">No Candidacies Available</h4>
                                                        <p class="text-muted">There are no active candidacies at this time.</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                    </div><!-- /tab-pane -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /container-scroller -->

    <!-- ═══════════ SCRIPTS ═══════════ -->
    <script>
        function selectElection(id) {
            window.location.href = 'index.php?election_id=' + id;
        }

        document.addEventListener('DOMContentLoaded', function() {
            /* ── Countdown timer ── */
            const timerInput = document.getElementById('secondsTimer');
            if (timerInput) {
                const initialSeconds = <?= (int)$remaining_seconds ?>;

                function fmt(s) {
                    if (s <= 0) return 'Election Ended';
                    const d = Math.floor(s / 86400);
                    const h = Math.floor((s % 86400) / 3600);
                    const m = Math.floor((s % 3600) / 60);
                    const sec = Math.floor(s % 60);
                    return `${d}d ${h}h ${m}m ${sec}s`;
                }
                let left = initialSeconds;
                timerInput.value = fmt(left);
                if (left > 0) {
                    const t = setInterval(() => {
                        timerInput.value = fmt(--left);
                        if (left <= 0) clearInterval(t);
                    }, 1000);
                }
            }

            /* ── Platform modal ── */
            document.querySelectorAll('.view-platform').forEach(btn => {
                btn.addEventListener('click', function() {
                    Swal.fire({
                        title: 'Candidate Platform',
                        html: `<div style="text-align:left;max-height:400px;overflow-y:auto;">
                               ${this.dataset.platform.replace(/\n/g, '<br>')}
                           </div>`,
                        width: '600px',
                        confirmButtonText: 'Close'
                    });
                });
            });

            /* ── SweetAlert status messages ── */
            <?php if (isset($_SESSION['STATUS'])): ?>
                    (function() {
                        const s = '<?= $_SESSION['STATUS'] ?>';
                        const statusMap = {
                            'COR_VERIFIED_SUCCESSFULLY': ['success', 'COR Verified', 'Your COR has been verified successfully!'],
                            'INVALID_COR': ['error', 'Invalid COR', 'One or more required fields are missing.'],
                            'STUDENT_ID_NOT_FOUND': ['error', 'Student ID Not Found', 'The student ID was not detected.'],
                            'SEM_SY_NOT_FOUND': ['error', 'Semester/School Year Not Found', 'The Semester or School Year is missing.'],
                            'SUCCESS_READING': ['success', 'Success', 'COR successfully verified!'],
                            'ERROR_FILE_NEW': ['error', 'Error', 'The specified file could not be found.'],
                            'ERROR_INVALID_PATH': ['error', 'Error', 'Invalid file path. Please try again.'],
                            'ERROR_PDF_CONVERSION': ['error', 'Error', 'Failed to convert PDF. Please ensure the file is valid.'],
                            'ERROR_IMAGE_PROCESSING': ['error', 'Error', 'Failed to process the image.'],
                            'ERROR_USER_NOT_LOGGED_IN': ['error', 'Error', 'You must be logged in to verify a COR.'],
                            'ERROR_OCR_FAILED': ['error', 'Error', 'Could not read required data from the COR.'],
                        };
                        const m = statusMap[s];
                        if (m) Swal.fire({
                            icon: m[0],
                            title: m[1],
                            text: m[2],
                            confirmButtonText: 'OK'
                        });
                    })();
                <?php unset($_SESSION['STATUS'], $_SESSION['MESSAGE']); ?>
            <?php endif; ?>
        });
    </script>

</body>

</html>