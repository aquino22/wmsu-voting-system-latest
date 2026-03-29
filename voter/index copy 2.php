<?php
session_start();
include('includes/conn.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : null;
$user_id = isset($_SESSION['email']) ? $_SESSION['email'] : null;
$student_id = null;

if ($user_email) {
    $stmt = $pdo->prepare("SELECT student_id FROM voters WHERE email = ?");
    $stmt->execute([$user_email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $student_id = $result['student_id'];
        $_SESSION['user_id'] = $student_id;
    }
}

$status = null;
$college = null;
$precinct_name = null;

if ($user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT pv.precinct, pv.status, v.college
            FROM precinct_voters pv
            LEFT JOIN voters v ON pv.student_id = v.student_id
            WHERE pv.student_id = (
                SELECT student_id
                FROM voters
                WHERE email = ?
            )
        ");
        $stmt->execute([$user_email]);
        $voter = $stmt->fetch(PDO::FETCH_ASSOC);

        $precinct_name = $voter ? $voter['precinct'] : null;
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

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'voter') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = "NON_VOTER";
    header("Location: ../index.php");
    exit();
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
                        <h1 class="welcome-text">Welcome, <span class="text-white fw-bold">WMSU Student</span></h1>
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
                    <li class="nav-item active-link">
                        <a class="nav-link active-link" href="index.php" style="background-color: #B22222 !important;">
                            <i class="mdi mdi-grid-large menu-icon" style="color: white !important;"></i>
                            <span class="menu-title" style="color: white !important;">Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="vote.php">
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
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Dashboard</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                                        <?php
                                        date_default_timezone_set('Asia/Manila');
                                        $current_date = date('Y-m-d H:i:s');

                                        try {
                                            $stmt = $pdo->prepare("
                                                SELECT *
                                                FROM voting_periods
                                                WHERE status = 'Ongoing'
                                                LIMIT 1
                                            ");
                                            $stmt->execute();
                                            $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);

                                            if (!$votingPeriod) {
                                                $stmt = $pdo->prepare("
                                                    SELECT id, *
                                                    FROM voting_periods
                                                    WHERE status = 'Paused'
                                                    LIMIT 1
                                                ");
                                                $stmt->execute();
                                                $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);
                                            }

                                            if (!$votingPeriod) {
                                                $stmt = $pdo->prepare("
                                                    SELECT *
                                                    FROM voting_periods
                                                    WHERE status = 'Scheduled' AND start_period >= NOW()
                                                    ORDER BY start_period ASC
                                                    LIMIT 1
                                                ");
                                                $stmt->execute();
                                                $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);
                                            }

                                            if ($votingPeriod) {
                                                $votingPeriodId = $votingPeriod['id'];
                                                $votingPeriodName = $votingPeriod['name'];
                                                $votingPeriodStart = $votingPeriod['start_period'];
                                                $votingPeriodEnd = $votingPeriod['end_period'] ?: 'TBD';
                                                $votingPeriodStatus = $votingPeriod['status'];

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


                                                $stmt = $pdo->prepare("
                                                    SELECT id 
                                                    FROM registration_forms 
                                                    WHERE election_name = ? 
                                                    LIMIT 1
                                                ");
                                                $stmt->execute([$votingPeriodName]);
                                                $formId = $stmt->fetchColumn();

                                                $candidatesByLevel = ['Central' => [], 'Local' => []];
                                                if ($formId) {

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
                                            } else {
                                                $votingPeriodId = null;
                                                $votingPeriodName = 'No Active Voting Period';
                                                $votingPeriodStart = null;
                                                $votingPeriodEnd = null;
                                                $votingPeriodStatus = 'None';
                                                $remaining_seconds = 0;
                                                $candidatesByLevel = ['Central' => [], 'Local' => []];
                                            }
                                        } catch (Exception $e) {
                                            echo "<div class='container-fluid text-center'><h3>Error: " . htmlspecialchars($e->getMessage()) . "</h3></div>";
                                            exit;
                                        }
                                        ?>

                                        <div class="card card-rounded">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between mb-4">
                                                    <div>
                                                        <h2 class="card-title card-title-dash">
                                                            <?php echo htmlspecialchars($votingPeriodName); ?>
                                                            <span class="election-status">
                                                                <?php
                                                                echo $votingPeriodStatus === 'Ongoing' ? 'Ongoing' : ($votingPeriodStatus === 'Paused' ? 'Paused' : ($votingPeriodStatus === 'Scheduled' ? 'Scheduled' : 'Inactive'));
                                                                ?>
                                                            </span>
                                                        </h2>
                                                        <p class="text-muted">Precinct: <?php echo htmlspecialchars($precinct_name ?? 'N/A'); ?></p>
                                                        <p class="text-muted">Your College: <?php echo htmlspecialchars($college ?? 'N/A'); ?></p>
                                                        <?php if ($votingPeriodStatus === 'Scheduled'): ?>
                                                            <p class="text-info">Voting Starts: <?php echo date('F d, Y, g:i A', strtotime($votingPeriodStart)); ?></p>
                                                        <?php elseif ($votingPeriodStatus === 'Paused'): ?>
                                                            <h1 class="text-warning">Voting is temporarily paused. Please check back later.</h1>
                                                        <?php elseif ($votingPeriodStatus === 'None'): ?>
                                                            <h1 class="text-muted">No elections are currently active. Stay tuned!</h1>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (in_array($votingPeriodStatus, ['Ongoing', 'Scheduled'])): ?>
                                                        <div class="d-flex align-items-center">
                                                            <div class="countdown-container me-3">
                                                                <script>
                                                                    const initialSeconds = <?= $remaining_seconds ?>;
                                                                </script>
                                                                <i class="mdi mdi-clock-outline me-2" style="color: #B22222;"></i>
                                                                <input type="text" class="timer-input" id="secondsTimer" value="0:00:00" readonly>
                                                            </div>
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
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Candidate Display with Blur -->
                                                <!-- Candidate Display with Blur -->
                                                <?php
                                                $showBlur = ($status === 'voted' || !in_array($status, ['verified', 'pending', 'revoted']) || $votingPeriodStatus !== 'Ongoing');
                                                $overlayText = '';
                                                if ($status === 'voted') {
                                                    $overlayText = 'You have already voted!';
                                                } elseif (!in_array($status, ['verified', 'pending'])) {
                                                    $overlayText = 'Please verify your COR to view candidates';
                                                } elseif ($votingPeriodStatus === 'Paused') {
                                                    $overlayText = 'Candidates hidden while voting is paused';
                                                } elseif ($votingPeriodStatus === 'Scheduled') {
                                                    $overlayText = 'Candidates will be revealed when voting starts';
                                                } elseif ($votingPeriodStatus === 'None') {
                                                    $overlayText = 'No candidates available at this time';
                                                }
                                                ?>
                                                <div class="candidate-container">
                                                    <?php if ($showBlur): ?>
                                                        <div class="overlay-text"><?php echo htmlspecialchars($overlayText); ?></div>
                                                    <?php endif; ?>
                                                    <div class="container-fluid text-center <?php echo $showBlur ? 'blur-overlay' : ''; ?>">
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
                                                                                        <h5 class="text-success"><?php echo htmlspecialchars($candidate['party']); ?></h5>
                                                                                        <?php if ($candidate['platform']): ?>
                                                                                            <button class="btn btn-sm btn-outline-info mt-2 view-platform" data-platform="<?php echo htmlspecialchars($candidate['platform']); ?>">View Platform</button>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                            <br><br>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php elseif ($level === 'Local'): ?>
                                                                <div class="alert alert-info">
                                                                    No local candidates found for your college (<?php echo htmlspecialchars($college ?? 'N/A'); ?>).
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- Update COR Upload Modal -->
                                                <?php if ($user_id && $status === 'active'): ?>
                                                    <div class="modal fade" id="corUploadModal" tabindex="-1" aria-labelledby="corUploadModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="corUploadModalLabel">Upload Certificate of Registration</h5>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Your voter status is <b>'unverified'</b>. Please upload a picture of your Certificate of Registration (COR) for verification.</p>

                                                                    <form method="POST" enctype="multipart/form-data" id="corUploadForm" action="upload_cor.php">
                                                                        <div class="mb-3">
                                                                            <label for="corImage" class="form-label">Select COR Image or PDF</label>
                                                                            <input type="file" class="form-control" id="corImage" name="cor_image" accept="image/jpeg,image/png,image/gif,application/pdf" required>
                                                                            <!-- Hidden input to store the converted JPG -->
                                                                            <input type="hidden" name="converted_image" id="convertedImage">
                                                                        </div>
                                                                        <div id="preview" class="mb-3" style="display: none;">
                                                                            <p><strong>Preview:</strong></p>
                                                                            <img id="imagePreview" style="max-width: 100%; max-height: 200px;" alt="Preview">
                                                                        </div>
                                                                        <button type="submit" class="btn btn-primary" id="uploadBtn" disabled>
                                                                            <span id="uploadBtnText">Upload</span>
                                                                            <span id="uploadBtnSpinner" class="spinner-border spinner-border-sm" role="status" style="display: none;"></span>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

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
                                                <?php endif; ?>

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