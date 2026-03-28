<?php
session_start();
date_default_timezone_set('Asia/Manila');
include('includes/conn.php');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = "NON_ADMIN";
    header("Location: ../index.php");
    exit();
}

// Get all colleges, departments, courses, etc. for filters
$colleges = $pdo->query("SELECT DISTINCT college FROM voters WHERE college IS NOT NULL ORDER BY college")->fetchAll(PDO::FETCH_COLUMN);
$departments = $pdo->query("SELECT DISTINCT department FROM voters WHERE department IS NOT NULL ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
$courses = $pdo->query("SELECT DISTINCT course FROM voters WHERE course IS NOT NULL ORDER BY course")->fetchAll(PDO::FETCH_COLUMN);
$wmsu_campuses = $pdo->query("SELECT DISTINCT wmsu_campus FROM voters WHERE wmsu_campus IS NOT NULL ORDER BY wmsu_campus")->fetchAll(PDO::FETCH_COLUMN);
$external_campuses = $pdo->query("SELECT DISTINCT external_campus FROM voters WHERE external_campus IS NOT NULL AND external_campus != 'None' ORDER BY external_campus")->fetchAll(PDO::FETCH_COLUMN);
$year_levels = $pdo->query("SELECT DISTINCT year_level FROM voters WHERE year_level IS NOT NULL ORDER BY year_level")->fetchAll(PDO::FETCH_COLUMN);



// Get ongoing election info
$stmt = $pdo->prepare("SELECT * FROM voting_periods WHERE status = 'Ongoing' LIMIT 1");
$stmt->execute();
$ongoingElection = $stmt->fetch(PDO::FETCH_ASSOC);

if ($ongoingElection) {
    $electionId = $ongoingElection['id'];
    $electionName = $ongoingElection['election_name'];
} else {
    $electionId = null;
    $electionName = null;
}

$sentLogs = [];
$params = [];

// Build query based on filters
$whereConditions = [];
$havingConditions = [];

// Election filter
$selectedElection = $_GET['election_filter'] ?? '';
if ($selectedElection) {
    $havingConditions[] = "FIND_IN_SET(?, GROUP_CONCAT(DISTINCT vp.id))";
    $params[] = $selectedElection;
}

// College filter
$selectedCollege = $_GET['college_filter'] ?? '';
if ($selectedCollege) {
    $whereConditions[] = "v.college = ?";
    $params[] = $selectedCollege;
}

// Department filter
$selectedDepartment = $_GET['department_filter'] ?? '';
if ($selectedDepartment) {
    $whereConditions[] = "v.department = ?";
    $params[] = $selectedDepartment;
}

// Course filter
$selectedCourse = $_GET['course_filter'] ?? '';
if ($selectedCourse) {
    $whereConditions[] = "v.course = ?";
    $params[] = $selectedCourse;
}

// WMSU Campus filter
$selectedWmsuCampus = $_GET['wmsu_campus_filter'] ?? '';
if ($selectedWmsuCampus) {
    $whereConditions[] = "v.wmsu_campus = ?";
    $params[] = $selectedWmsuCampus;
}

// External Campus filter
$selectedExternalCampus = $_GET['external_campus_filter'] ?? '';
if ($selectedExternalCampus) {
    $whereConditions[] = "v.external_campus = ?";
    $params[] = $selectedExternalCampus;
}

// Year Level filter
$selectedYearLevel = $_GET['year_level_filter'] ?? '';
if ($selectedYearLevel) {
    $whereConditions[] = "v.year_level = ?";
    $params[] = $selectedYearLevel;
}

// Build WHERE clause
$whereClause = "";
if (!empty($whereConditions)) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}

// Build HAVING clause
$havingClause = "";
if (!empty($havingConditions)) {
    $havingClause = "HAVING " . implode(" AND ", $havingConditions);
}

// Query to get all voter data with QR sending logs
$query = "
SELECT 
    v.first_name,
    v.middle_name,
    v.last_name,
    v.student_id,
    v.email AS voter_email,
    v.college,
    v.department,
    v.wmsu_campus,
    v.external_campus,
    v.year_level,
    v.course,
    COUNT(q.id) AS total_emails_sent,
    q.election_id,
    GROUP_CONCAT(
        CONCAT(
            q.status,
            ' (',
            DATE_FORMAT(q.sent_at, '%M %e, %Y %l:%i %p'),
            ' - ',
            COALESCE(vp.name, 'N/A'),
            ')'
        )
        ORDER BY q.sent_at DESC
        SEPARATOR '<br>'
    ) AS email_details,
    GROUP_CONCAT(DISTINCT vp.name ORDER BY vp.name SEPARATOR ', ') AS voting_period_names,
    GROUP_CONCAT(DISTINCT vp.id) AS election_ids
FROM voters v
LEFT JOIN qr_sending_log q
    ON v.student_id = q.student_id
LEFT JOIN voting_periods vp
    ON q.election_id = vp.id
$whereClause
GROUP BY 
    v.student_id
$havingClause
ORDER BY v.student_id ASC
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $sentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $sentLogs = [];
}

// Get all elections for filter dropdown
$allElections = $pdo->query("SELECT id, election_name FROM voting_periods ORDER BY created_at ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU i-Elect Admin | Voter List </title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="js/select.dataTables.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="images/favicon.png" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>

<style>
    .button-send-qr.disabled {
        opacity: 0.6;
        cursor: not-allowed;
        pointer-events: none;
    }

    .filter-section {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .filter-row {
        margin-bottom: 10px;
    }
</style>

<body>
    <div class="container-scroller">
        <!-- partial:partials/_navbar.html -->
        <?php
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT a.full_name, a.phone_number, u.email 
                               FROM admin a 
                               JOIN users u ON a.user_id = u.id 
                               WHERE u.id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            $admin_full_name = $admin['full_name'];
            $admin_phone_number = $admin['phone_number'];
            $admin_email = $admin['email'];
        }
        ?>
        <nav class="navbar default-layout col-lg-12 col-12 p-0 d-flex align-items-top flex-row">
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
                        <h1 class="welcome-text">Welcome, <span class="text-white fw-bold">WMSU Admin</span></h1>
        <h6>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM elections WHERE status = :status");
                        $stmt->execute(['status' => 'Ongoing']);
                        $ongoingElections = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if ($ongoingElections) {

                            // Show first election
                            $first = array_shift($ongoingElections);
                            echo "<br><b>Election: </b> " . $first['election_name'] . " | ";
                            echo "<b>Semester: </b> " . $first['semester'] . " | ";
                            echo "<b>School Year:</b> " . $first['school_year_start'] . " - " . $first['school_year_end'] . "<br>";

                            if ($ongoingElections) {
                                echo '<div id="moreElections" style="display:none; margin-top: 5px !important">';
                            
                                foreach ($ongoingElections as $election) {
                                  
                                    echo "<b>Election: </b> " . $election['election_name'] . " | ";
                                    echo "<b>Semester:</b> " . $election['semester'] . " | ";
                                    echo "<b>School Year:</b> " . $election['school_year_start'] . " - " . $election['school_year_end'] . "<br>";
                                }
                                echo '</div> <br>';
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
                            });
                        </script>

                        <script>
                            // Back to Top Button Functionality
                            document.addEventListener('DOMContentLoaded', function() {
                                const backToTopButton = document.getElementById('backToTop');

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
                            });
                        </script>

                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                        <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <img class="img-xs rounded-circle logo" src="images/wmsu-logo.png" style="background-color: white;" alt="Profile image"> </a>
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
        <!-- partial -->
        <div class="container-fluid page-body-wrapper">
            <!-- partial:partials/_settings-panel.html -->
            <nav class="sidebar sidebar-offcanvas" id="sidebar">
                <?php
                // Get the current PHP file name
                $current_page = basename($_SERVER['PHP_SELF']);
                ?>
                <ul class="nav">
                    <li class="nav-item <?php echo $current_page == 'index.php' ? 'active-link' : ''; ?>">
                        <a class="nav-link <?php echo $current_page == 'index.php' ? 'active-link' : ''; ?>" href="index.php" <?php echo $current_page == 'index.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                            <i class="mdi mdi-grid-large menu-icon" <?php echo $current_page == 'index.php' ? 'style="color: white !important;"' : ''; ?>></i>
                            <span class="menu-title" <?php echo $current_page == 'index.php' ? 'style="color: white !important;"' : ''; ?>>Index</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'election.php' ? 'active-link' : ''; ?>">
                        <a class="nav-link <?php echo $current_page == 'election.php' ? 'active-link' : ''; ?>" href="election.php" <?php echo $current_page == 'election.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                            <i class="menu-icon mdi mdi-vote" <?php echo $current_page == 'election.php' ? 'style="color: white !important;"' : ''; ?>></i>
                            <span class="menu-title" <?php echo $current_page == 'election.php' ? 'style="color: white !important;"' : ''; ?>>Election</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'candidacy.php' ? 'active-link' : ''; ?>">
                        <a class="nav-link <?php echo $current_page == 'candidacy.php' ? 'active-link' : ''; ?>" href="candidacy.php" <?php echo $current_page == 'candidacy.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                            <i class="menu-icon mdi mdi-account-tie" <?php echo $current_page == 'candidacy.php' ? 'style="color: white !important;"' : ''; ?>></i>
                            <span class="menu-title" <?php echo $current_page == 'candidacy.php' ? 'style="color: white !important;"' : ''; ?>>Candidacy</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'events.php' ? 'active-link' : ''; ?>">
                        <a class="nav-link <?php echo $current_page == 'events.php' ? 'active-link' : ''; ?>" href="events.php" <?php echo $current_page == 'events.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                            <i class="menu-icon mdi mdi-calendar" <?php echo $current_page == 'events.php' ? 'style="color: white !important;"' : ''; ?>></i>
                            <span class="menu-title" <?php echo $current_page == 'events.php' ? 'style="color: white !important;"' : ''; ?>>Events</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'emails.php' ? 'active-link' : ''; ?>">
                        <a class="nav-link <?php echo $current_page == 'emails.php' ? 'active-link' : ''; ?>" href="emails.php" <?php echo $current_page == 'emails.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                            <i class="mdi mdi-email menu-icon" <?php echo $current_page == 'emails.php' ? 'style="color: white !important;"' : ''; ?>></i>
                            <span class="menu-title" <?php echo $current_page == 'emails.php' ? 'style="color: white !important;"' : ''; ?>>Emails</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'advisers.php' ? 'active-link' : ''; ?>">
                        <a class="nav-link <?php echo $current_page == 'advisers.php' ? 'active-link' : ''; ?>" href="advisers.php" <?php echo $current_page == 'advisers.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                            <i class="mdi mdi-account-tie menu-icon" <?php echo $current_page == 'advisers.php' ? 'style="color: white !important;"' : ''; ?>></i>
                            <span class="menu-title" <?php echo $current_page == 'advisers.php' ? 'style="color: white !important;"' : ''; ?>>Advisers</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'voter-list.php' ? 'active-link' : ''; ?>">
                        <a class="nav-link <?php echo $current_page == 'voter-list.php' ? 'active-link' : ''; ?>" href="voter-list.php" <?php echo $current_page == 'voter-list.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                            <i class="menu-icon mdi mdi-account-group" <?php echo $current_page == 'voter-list.php' ? 'style="color: white !important;"' : ''; ?>></i>
                            <span class="menu-title" <?php echo $current_page == 'voter-list.php' ? 'style="color: white !important;"' : ''; ?>>Voter List</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'moderators.php' ? 'active-link' : ''; ?>">
                        <a class="nav-link <?php echo $current_page == 'moderators.php' ? 'active-link' : ''; ?>" href="moderators.php" <?php echo $current_page == 'moderators.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                            <i class="mdi mdi-pac-man menu-icon" <?php echo $current_page == 'moderators.php' ? 'style="color: white !important;"' : ''; ?>></i>
                            <span class="menu-title" <?php echo $current_page == 'moderators.php' ? 'style="color: white !important;"' : ''; ?>>Moderators</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'precincts.php' ? 'active-link' : ''; ?>">
                        <a class="nav-link <?php echo $current_page == 'precincts.php' ? 'active-link' : ''; ?>" href="precincts.php" <?php echo $current_page == 'precincts.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                            <i class="mdi mdi-room-service menu-icon" <?php echo $current_page == 'precincts.php' ? 'style="color: white !important;"' : ''; ?>></i>
                            <span class="menu-title" <?php echo $current_page == 'precincts.php' ? 'style="color: white !important;"' : ''; ?>>Precincts</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'voting.php' ? 'active-link' : ''; ?>">
                        <a class="nav-link <?php echo $current_page == 'voting.php' ? 'active-link' : ''; ?>" href="voting.php" <?php echo $current_page == 'voting.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                            <i class="menu-icon mdi mdi-ballot" <?php echo $current_page == 'voting.php' ? 'style="color: white !important;"' : ''; ?>></i>
                            <span class="menu-title" <?php echo $current_page == 'voting.php' ? 'style="color: white !important;"' : ''; ?>>Voting</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'view_sent_qrs.php' ? 'active-link' : ''; ?>">
                        <a class="nav-link <?php echo $current_page == 'view_sent_qrs.php' ? 'active-link' : ''; ?>" href="view_sent_qrs.php" <?php echo $current_page == 'view_sent_qrs.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                            <i class="menu-icon mdi mdi-qrcode" <?php echo $current_page == 'view_sent_qrs.php' ? 'style="color: white !important;"' : ''; ?>></i>
                            <span class="menu-title" <?php echo $current_page == 'view_sent_qrs.php' ? 'style="color: white !important;"' : ''; ?>>Sent QR Codes</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'reports.php' ? 'active-link' : ''; ?>">
                        <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active-link' : ''; ?>" href="reports.php" <?php echo $current_page == 'reports.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                            <i class="menu-icon mdi mdi-file-chart" <?php echo $current_page == 'reports.php' ? 'style="color: white !important;"' : ''; ?>></i>
                            <span class="menu-title" <?php echo $current_page == 'reports.php' ? 'style="color: white !important;"' : ''; ?>>Reports</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page == 'history.php' ? 'active-link' : ''; ?>">
                        <a class="nav-link <?php echo $current_page == 'history.php' ? 'active-link' : ''; ?>" href="history.php" <?php echo $current_page == 'history.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                            <i class="menu-icon mdi mdi-history" <?php echo $current_page == 'history.php' ? 'style="color: white !important;"' : ''; ?>></i>
                            <span class="menu-title" <?php echo $current_page == 'history.php' ? 'style="color: white !important;"' : ''; ?>>History</span>
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
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Voters with Verified QRs</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="container-fluid mt-4">
                                                    <div class="row mb-3">
                                                        <div class="col-md-12">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <h3 class="mb-0"><b>All Verified Voters with QR Details</b></h3>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Filter Section -->
                                                

                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-striped text-center" id="votersTable">
                                                            <thead>
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Student ID</th>
                                                                    <th>Full Name</th>
                                                                    <th>College</th>
                                                                    <th>WMSU Campus</th>
                                                                    <th>External Campus</th>
                                                                    <th>Course</th>
                                                                    <th>Department</th>
                                                                    <th>Year Level</th>
                                                                    <th>Email</th>
                                                                    <th>Total Emails Sent</th>
                                                                    <th>Elections</th>
                                                                    <th>Email Details</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if (empty($sentLogs)): ?>
                                                                    <tr>
                                                                        <td colspan="13" class="text-center">No voters found matching the selected filters.</td>
                                                                    </tr>
                                                                <?php else: ?>
                                                                    <?php foreach ($sentLogs as $index => $log): ?>
                                                                        <tr>
                                                                            <td><?= $index + 1 ?></td>
                                                                            <td><?= htmlspecialchars($log['student_id']) ?></td>
                                                                            <td><?= htmlspecialchars($log['first_name'] . ' ' . $log['middle_name'] . ' ' . $log['last_name']) ?></td>
                                                                            <td><?= htmlspecialchars($log['college']) ?></td>
                                                                            <td><?= htmlspecialchars($log['wmsu_campus']) ?></td>
                                                                            <td><?= htmlspecialchars($log['external_campus'] ?? '') ?: '-' ?></td>
                                                                            <td><?= htmlspecialchars($log['course']) ?></td>
                                                                            <td><?= htmlspecialchars($log['department']) ?></td>
                                                                            <td><?= htmlspecialchars($log['year_level']) ?></td>
                                                                            <td><?= htmlspecialchars($log['voter_email'] ?? '') ?></td>
                                                                            <td><?= htmlspecialchars($log['total_emails_sent']) ?></td>
                                                                            <td><?= htmlspecialchars($log['voting_period_names'] ?? 'No elections') ?></td>
                                                                            <td>
                                                                                <?php
                                                                                $details = explode('<br>', $log['email_details'] ?? '');
                                                                                foreach ($details as $detail) {
                                                                                    if (!empty(trim($detail))) {
                                                                                        preg_match('/^(\w+)\s*\(([^)]+)\s*-\s*([^)]+)\)$/', $detail, $matches);

                                                                                        if (count($matches) === 4) {
                                                                                            $status = strtolower($matches[1]);
                                                                                            $timestamp = $matches[2];
                                                                                            $votingPeriodName = $matches[3];

                                                                                            $badgeClass = $status === 'sent' ? 'badge-success' : ($status === 'failed' ? 'badge-danger' : 'badge-secondary');
                                                                                            $formattedTime = date('M j, Y g:i A', strtotime($timestamp));
                                                                                            $displayText = strtoupper($status) . ' - ' . $votingPeriodName . ' at ' . $formattedTime;

                                                                                            // Only add clickable data for SENT badges
                                                                                            $dataAttributes = $status === 'sent'
                                                                                                ? "data-student-id='{$log['student_id']}' data-voting-period='{$log['election_id']}'"
                                                                                                : "";

                                                                                            echo "<span class='badge $badgeClass me-1 mb-1 qr-badge' style='cursor: pointer;' $dataAttributes>$displayText</span><br>";
                                                                                        } else {
                                                                                            echo "<span class='badge rounded-pill text-bg-primary' style='color:black; border: 1px solid black'>$detail</span><br>";
                                                                                        }
                                                                                    }
                                                                                }
                                                                                ?>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrModalLabel">Student QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="qrModalImg" src="" alt="QR Code" class="img-fluid">
                    <p id="qrModalStudentId" class="mt-2"></p>
                    <p id="qrModalVotingPeriod" class="mb-0"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- plugins:js -->
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

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTables
            var table = $('#votersTable').DataTable({
                responsive: true,
                pageLength: 10,
                language: {
                    search: "Filter records:"
                }
            });

            // QR Code Modal functionality
            document.addEventListener('click', function(e) {
                const badge = e.target.closest('.qr-badge');
                if (!badge) return;

                const studentId = badge.dataset.studentId;
                const votingPeriod = badge.dataset.votingPeriod;

                const qrModalImg = document.getElementById('qrModalImg');
                const qrModalStudentId = document.getElementById('qrModalStudentId');
                const qrModalVotingPeriod = document.getElementById('qrModalVotingPeriod');

                qrModalImg.src = '';
                qrModalStudentId.textContent = 'Loading...';
                qrModalVotingPeriod.textContent = '';

                const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
                qrModal.show();

                fetch('get_student_qr.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `student_id=${encodeURIComponent(studentId)}&voting_period_name=${encodeURIComponent(votingPeriod)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            qrModalImg.src = data.qr_url;
                            qrModalStudentId.textContent = 'Student ID: ' + studentId;
                            qrModalVotingPeriod.textContent = 'Voting Period: ' + data.voting_period_name;
                        } else {
                            qrModalStudentId.textContent = data.message || 'Failed to generate QR';
                        }
                    })
                    .catch(() => {
                        qrModalStudentId.textContent = 'Error generating QR code';
                    });
            });
        });
    </script>
</body>
<button class="back-to-top" id="backToTop" title="Go to top">
    <i class="mdi mdi-arrow-up"></i>
</button>

</html>