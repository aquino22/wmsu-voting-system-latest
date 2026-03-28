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


$stmt = $pdo->prepare("
SELECT 
    vp.id AS voting_period_id,
    e.id AS election_id,
    e.election_name
FROM voting_periods vp
JOIN elections e ON vp.election_id = e.id
WHERE vp.status = 'Ongoing'
LIMIT 1
");
$stmt->execute();

$ongoingElection = $stmt->fetch(PDO::FETCH_ASSOC);

$votingPeriodId = $ongoingElection['voting_period_id'] ?? null;
$electionId     = $ongoingElection['election_id'] ?? null;
$electionName   = $ongoingElection['election_name'] ?? null;

$selectedYear = $_GET['academic_year'] ?? '';
$selectedElection = $_GET['election_filter'] ?? '';
$selectedCollege = $_GET['college_filter'] ?? '';
$selectedDepartment = $_GET['department_filter'] ?? '';
$selectedCourse = $_GET['course_filter'] ?? '';
$selectedWmsuCampus = $_GET['wmsu_campus_filter'] ?? '';
$selectedExternalCampus = $_GET['external_campus_filter'] ?? '';
$selectedYearLevel = $_GET['year_level_filter'] ?? '';
$selectedStudent = $_GET['student_id'] ?? ''; // or $_POST if form submits POST

$whereConditions = [];
$params = [];

$whereConditions[] = "v.status IN ('verified','accepted','confirmed', 'pending')";
// $whereConditions[] = "v.needs_update != 1";

if ($selectedYear) {
    $whereConditions[] = "ay.id = ?";
    $params[] = $selectedYear;
}

if ($selectedElection) {
    $whereConditions[] = "vp.id = ?";
    $params[] = $selectedElection;
}

if ($selectedCollege) {
    $whereConditions[] = "v.college = ?";
    $params[] = $selectedCollege;
}

if ($selectedDepartment) {
    $whereConditions[] = "v.department = ?";
    $params[] = $selectedDepartment;
}

if ($selectedCourse) {
    $whereConditions[] = "v.course = ?";
    $params[] = $selectedCourse;
}

if ($selectedWmsuCampus) {
    $whereConditions[] = "v.wmsu_campus = ?";
    $params[] = $selectedWmsuCampus;
}

if ($selectedExternalCampus) {
    $whereConditions[] = "v.external_campus = ?";
    $params[] = $selectedExternalCampus;
}

if ($selectedYearLevel) {
    $whereConditions[] = "v.year_level = ?";
    $params[] = $selectedYearLevel;
}

if ($selectedStudent) {
    $whereConditions[] = "v.student_id = ?";
    $params[] = $selectedStudent;
}

$whereClause = "";
if ($whereConditions) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);

    $query = "
SELECT 
    vp.id as voting_period_id,
    v.id,
    v.first_name,
    v.middle_name,
    v.last_name,
    v.student_id,
    v.email AS voter_email,

    c.college_name,
    d.department_name,
    co.course_name,
    wc.campus_name AS wmsu_campus_name,
    ec.campus_name AS external_campus_name,
    m.major_name,

    v.year_level,
    v.needs_update,

    ay.id as academic_year_id_real,
    ay.semester,
    ay.year_label AS school_year,
    ay.custom_voter_option,

    e.id as election_id,

    COUNT(DISTINCT q.id) AS total_emails_sent,

    GROUP_CONCAT(
        CONCAT(
            q.status,' (',
            DATE_FORMAT(q.sent_at,'%M %e, %Y %l:%i %p'),
            ' - ', e.election_name, ')'
        )
        ORDER BY q.sent_at DESC
        SEPARATOR '<br>'
    ) AS email_details,

    ep.participated_elections,
    ep.assigned_precincts,
    ep.vote_statuses

FROM voters v

LEFT JOIN qr_sending_log q ON v.student_id = q.student_id
LEFT JOIN voting_periods vp ON q.election_id = vp.id
LEFT JOIN elections e ON vp.election_id = e.id
LEFT JOIN academic_years ay ON v.academic_year_id = ay.id

LEFT JOIN colleges c ON v.college = c.college_id
LEFT JOIN departments d ON v.department = d.department_id
LEFT JOIN courses co ON v.course = co.id
LEFT JOIN campuses wc ON v.wmsu_campus = wc.campus_id
LEFT JOIN campuses ec ON v.external_campus = ec.campus_id
LEFT JOIN majors m ON v.major = m.major_id

LEFT JOIN (
    SELECT 
        base.student_id,

      GROUP_CONCAT(
    CONCAT(base.election_name, ' — ', IF(base.has_voted > 0,'Voted','Not Voted'))
    ORDER BY base.election_name
    SEPARATOR ' | '
) AS participated_elections,

      GROUP_CONCAT(
    base.precinct_name
    ORDER BY base.precinct_name
    SEPARATOR ', '
) AS assigned_precincts,

      GROUP_CONCAT(
    CONCAT(base.precinct_name, ' — ', IF(base.has_voted > 0,'Voted','Not Voted'))
    ORDER BY base.precinct_name
    SEPARATOR ' | '
) AS vote_statuses

    FROM (
        SELECT 
            pv.student_id,
            el.election_name,
            p.name AS precinct_name,
            COUNT(vo.id) AS has_voted
        FROM precinct_voters pv
        JOIN precincts p ON pv.precinct = p.id
        JOIN precinct_elections pe ON p.id = pe.precinct_id
        JOIN elections el ON pe.election_name = el.id
        JOIN voting_periods vp2 ON el.id = vp2.election_id
        LEFT JOIN votes vo 
            ON vp2.id = vo.voting_period_id 
            AND pv.student_id = vo.student_id
        GROUP BY pv.student_id, el.id, p.id
    ) base

    GROUP BY base.student_id

) ep ON v.student_id = ep.student_id

$whereClause

GROUP BY v.student_id
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
}

$academicYears = $pdo->query("
SELECT *
FROM academic_years
ORDER BY start_date DESC
")->fetchAll(PDO::FETCH_ASSOC);


$params2 = [];
$sql = "
SELECT 
    vp.id AS voting_period_id,
    e.election_name,
    ay.year_label,
    ay.semester
FROM voting_periods vp
JOIN elections e ON e.id = vp.election_id
JOIN academic_years ay ON ay.id = e.academic_year_id
";

if ($selectedYear) {
    $sql .= " WHERE ay.id = ?";
    $params2[] = $selectedYear;
}

$sql .= " ORDER BY vp.start_period DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params2);
$allElections = $stmt->fetchAll(PDO::FETCH_ASSOC);


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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
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
                            // Join with academic_years to get semester and year_label
                            $stmt = $pdo->prepare("
                SELECT e.id, e.election_name, e.academic_year_id, a.year_label, a.semester
                FROM elections e
                JOIN academic_years a ON e.academic_year_id = a.id
                WHERE e.status = :status
                ORDER BY a.year_label DESC, a.semester DESC
            ");
                            $stmt->execute(['status' => 'Ongoing']);
                            $ongoingElections = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if ($ongoingElections) {
                                // Show first election
                                $first = array_shift($ongoingElections);
                                echo "<br><b>School Year:</b> " . htmlspecialchars($first['year_label']) . " | ";
                                echo "<b>Semester:</b> " . htmlspecialchars($first['semester']) . " | ";
                                echo "<b>Election:</b> " . htmlspecialchars($first['election_name']) . "<br>";

                                if ($ongoingElections) {
                                    echo '<div id="moreElections" style="display:none; margin-top:5px;">';

                                    foreach ($ongoingElections as $election) {
                                        echo "<b>School Year:</b> " . htmlspecialchars($election['year_label']) . " | ";
                                        echo "<b>Semester:</b> " . htmlspecialchars($election['semester']) . " | ";
                                        echo "<b>Election:</b> " . htmlspecialchars($election['election_name']) . "<br>";
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

                                // Back to Top Button
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
            <?php include('includes/sidebar.php') ?>
            <!-- partial -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Viewing Voter</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                                        <a class="mb-3 btn btn-primary text-white" href="voter-list.php"><i class="bi bi-arrow-left-circle-fill"></i> Go back</a>
                                        <div class="card mb-4 shadow-sm">
                                            <?php $log = $sentLogs[0] ?? null; ?>
                                            <?php if ($log): ?>
                                                <div class="card-header bg-primary text-white">
                                                    <h5 class="mb-0"><?= htmlspecialchars($log['first_name'] . ' ' . $log['middle_name'] . ' ' . $log['last_name']) ?></h5>
                                                    <small>Student ID: <?= htmlspecialchars($log['student_id']) ?></small>
                                                </div>
                                                <div class="card-body">
                                                    <!-- Basic Info -->
                                                    <div class="row mb-2">
                                                        <div class="col-md-6"><strong>Email:</strong> <?= htmlspecialchars($log['voter_email'] ?? 'N/A') ?></div>
                                                        <div class="col-md-6"><strong>College:</strong> <?= htmlspecialchars($log['college_name'] ?? 'N/A') ?></div>
                                                    </div>

                                                    <div class="row mb-2">
                                                        <div class="col-md-6"><strong>Department:</strong> <?= htmlspecialchars($log['department_name'] ?? 'N/A') ?></div>
                                                        <div class="col-md-6"><strong>Course:</strong> <?= htmlspecialchars($log['course_name'] ?? 'N/A') ?></div>
                                                    </div>

                                                    <div class="row mb-2">
                                                        <div class="col-md-6"><strong>Major:</strong> <?= htmlspecialchars($log['major_name'] ?? 'N/A') ?></div>
                                                        <div class="col-md-6"><strong>Year Level:</strong>
                                                            <?php
                                                            $actualYearLevels = $pdo->query("SELECT id, year_level FROM actual_year_levels")->fetchAll(PDO::FETCH_KEY_PAIR);
                                                            $yearLabels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year', 5 => '5th Year'];
                                                            $yearLevelId = $log['year_level'] ?? null;
                                                            $rawYear = $actualYearLevels[$yearLevelId] ?? null;
                                                            echo $yearLabels[$rawYear] ?? 'N/A';
                                                            ?>
                                                        </div>
                                                    </div>

                                                    <div class="row mb-2">
                                                        <div class="col-md-6"><strong>Campus:</strong>
                                                            <?php
                                                            $wmsu = $log['wmsu_campus_name'] ?? '';
                                                            $external = $log['external_campus_name'] ?? '';
                                                            if (!empty($wmsu)) {
                                                                echo htmlspecialchars($wmsu === 'WMSU ESU' ? $wmsu . ' - ' . ($external ?: 'N/A') : $wmsu);
                                                            } else {
                                                                echo htmlspecialchars($external ?: 'N/A');
                                                            }
                                                            ?>
                                                        </div>
                                                        <div class="col-md-6"><strong>Semester / School Year:</strong> <?= htmlspecialchars($log['semester'] ?? 'N/A') ?> / <?= htmlspecialchars($log['school_year'] ?? 'N/A') ?></div>
                                                    </div>

                                                    <!-- Email Summary -->
                                                    <div class="row mb-2">
                                                        <div class="col-md-6"><strong>Total Emails Sent:</strong> <?= htmlspecialchars($log['total_emails_sent'] ?? 0) ?></div>
                                                        <div class="col-md-6"><strong>Email Details:</strong>
                                                            <?= !empty($log['email_details']) ? nl2br(htmlspecialchars($log['email_details'])) : 'N/A' ?>
                                                        </div>
                                                    </div>

                                                    <!-- Participated Elections & Precincts -->
                                                    <div class="row mb-2">
                                                        <div class="col-md-6"><strong>Participated Elections:</strong> <?= !empty($log['participated_elections']) ? $log['participated_elections'] : 'N/A' ?></div>
                                                        <div class="col-md-6"><strong>Assigned Precincts:</strong> <?= !empty($log['assigned_precincts']) ? $log['assigned_precincts'] : 'N/A' ?></div>
                                                    </div>

                                                    <div class="row mb-2">
                                                        <div class="col-md-6"><strong>Vote Statuses:</strong> <?= !empty($log['vote_statuses']) ? $log['vote_statuses'] : 'N/A' ?></div>

                                                    </div>

                                                    <br><br><br>
                                                    <!-- QRs Sent Per Election -->
                                                    <div class="row mb-3 mt-3">
                                                        <div class="col-12">
                                                            <strong class="d-block mb-2">QRs Sent Per Election:</strong>
                                                            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
                                                                <?php
                                                                if (!empty($log['email_details'])) {
                                                                    $emails = explode('<br>', $log['email_details']);
                                                                    foreach ($emails as $email) {
                                                                        $email = trim(strip_tags($email));
                                                                        if ($email === '') continue;

                                                                        if (preg_match('/(\w+)\s*\(([^)]+)\s*-\s*(.+)\)/', $email, $matches)) {
                                                                            $status = $matches[1];
                                                                            $timestamp = $matches[2];
                                                                            $electionName = $matches[3];

                                                                            $badgeClass = strtolower($status) === 'sent' ? 'bg-success' : 'bg-danger';
                                                                            $formattedTime = date('M j, Y g:i A', strtotime($timestamp));
                                                                ?>
                                                                            <div class="col">
                                                                                <div class="card h-100 shadow-sm border-0 hover-shadow">
                                                                                    <div class="card-body">
                                                                                        <h6 class="card-title fw-bold mb-1 text-truncate">Election Name: <?= htmlspecialchars($electionName) ?></h6>
                                                                                        <p class="mb-1">
                                                                                            <span class="badge <?= $badgeClass ?> py-2 px-3 text-uppercase"><?= htmlspecialchars($status) ?></span>
                                                                                        </p>
                                                                                        <small class="text-muted d-block text-truncate mb-2"><?= htmlspecialchars($formattedTime) ?></small>

                                                                                        <div class="text-muted small mb-2">
                                                                                            <div>Semester / Year: <?= htmlspecialchars($log['semester'] ?? 'N/A') ?> / <?= htmlspecialchars($log['school_year'] ?? 'N/A') ?></div>
                                                                                            <div>Total Emails Sent: <?= htmlspecialchars($log['total_emails_sent'] ?? 0) ?></div>
                                                                                        </div>

                                                                                        <!-- Button to fetch QR dynamically -->
                                                                                        <button
                                                                                            class="btn btn-sm btn-primary w-100 view-qr-btn"
                                                                                            data-student-id="<?= htmlspecialchars($log['student_id']) ?>"
                                                                                            data-voting-period-id="<?= htmlspecialchars($log['voting_period_id']) ?>">
                                                                                            View QR
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                <?php
                                                                        } else {
                                                                            echo '<div class="col"><div class="card h-100 shadow-sm border-0"><div class="card-body"><span class="badge bg-secondary py-2 px-3">N/A</span></div></div></div>';
                                                                        }
                                                                    }
                                                                } else {
                                                                    echo '<div class="col-12"><span class="text-muted">No QR emails sent</span></div>';
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <br>

                                                    <?php
                                                    if (isset($log['custom_voter_option']) && $log['custom_voter_option'] == '1') {
                                                        $academic_year_id = $log['academic_year_id_real'];


                                                        $academic_year_id = $log['academic_year_id_real'] ?? null;
                                                        $voter_id = $log['id'] ?? null;



                                                        if ($academic_year_id && $voter_id) {

                                                            // Fields to exclude
                                                            $exclude_labels = [
                                                                'Student ID',
                                                                'First Name',
                                                                'Middle Name',
                                                                'Last Name',
                                                                'College',
                                                                'Course',
                                                                'Department',
                                                                'Major',
                                                                'Year Level',
                                                                'WMSU Campus',
                                                                'External Campus',
                                                                'Email',
                                                                'Password',
                                                                'Confirm Password',
                                                                'COR from WMSU Portal',
                                                                'Validated COR from Student Affairs'
                                                            ];

                                                            // Get visible custom fields for this academic year, excluding standard labels
                                                            $placeholders = str_repeat('?,', count($exclude_labels) - 1) . '?';
                                                            $fields_stmt = $pdo->prepare("
        SELECT *
        FROM voter_custom_fields
        WHERE academic_year_id = ?
        AND is_visible = 1
        AND field_label NOT IN ($placeholders)
        ORDER BY sort_order ASC, field_order ASC
    ");

                                                            $fields_stmt->execute(array_merge([$academic_year_id], $exclude_labels));
                                                            $fields = $fields_stmt->fetchAll(PDO::FETCH_ASSOC);

                                                            if ($fields) {
                                                                echo '<h5><strong>Custom Voter Fields and Responses</strong></h5>';
                                                                echo '<table class="table table-bordered">';
                                                                echo '<thead><tr><th>Field Label</th><th>Response</th></tr></thead>';
                                                                echo '<tbody>';

                                                                foreach ($fields as $field) {
                                                                    // Get the response for this voter
                                                                    $resp_stmt = $pdo->prepare("
                SELECT field_value
                FROM voter_custom_responses
                WHERE voter_id = ? AND field_id = ?
                LIMIT 1
            ");
                                                                    $resp_stmt->execute([$voter_id, $field['id']]);
                                                                    $response = $resp_stmt->fetchColumn();

                                                                    // Check if this field is a file upload
                                                                    if ($field['field_type'] === 'file') {
                                                                        $file_stmt = $pdo->prepare("
                    SELECT file_path
                    FROM voter_custom_files
                    WHERE voter_id = ? AND field_id = ?
                ");
                                                                        $file_stmt->execute([$voter_id, $field['id']]);
                                                                        $file = $file_stmt->fetchColumn();
                                                                        if ($file) {
                                                                            $response = '<a href="' . htmlspecialchars($file) . '" target="_blank">View File</a>';
                                                                        } else {
                                                                            $response = 'No file uploaded';
                                                                        }
                                                                    }

                                                                    echo '<tr>';
                                                                    echo '<td>' . htmlspecialchars($field['field_label']) . '</td>';
                                                                    echo '<td>' . htmlspecialchars($response) . '</td>';
                                                                    echo '</tr>';
                                                                }

                                                                echo '</tbody></table>';
                                                            } else {
                                                                echo '<p>No custom fields found for this academic year.</p>';
                                                            }
                                                        } else {
                                                            echo '<p>Voter or academic year not specified.</p>';
                                                        }
                                                    ?>


                                                    <?php } ?>



                                                    <!-- Modal (place somewhere in your page, outside the loop) -->
                                                    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="qrModalLabel">QR Code</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body text-center">
                                                                    <img id="qrModalImg" src="" alt="QR Code" class="img-fluid">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <script>
                                                        document.addEventListener('click', function(e) {
                                                            if (e.target.classList.contains('view-qr-btn')) {
                                                                const btn = e.target;
                                                                const studentId = btn.dataset.studentId;
                                                                const votingPeriodId = btn.datasetVotingPeriodId || btn.getAttribute('data-voting-period-id');

                                                                btn.disabled = true;
                                                                btn.innerText = 'Loading...';

                                                                fetch('get_student_qr.php', {
                                                                        method: 'POST',
                                                                        headers: {
                                                                            'Content-Type': 'application/x-www-form-urlencoded'
                                                                        },
                                                                        body: `student_id=${encodeURIComponent(studentId)}&voting_period_name=${encodeURIComponent(votingPeriodId)}`
                                                                    })
                                                                    .then(res => res.json())
                                                                    .then(data => {
                                                                        btn.disabled = false;
                                                                        btn.innerText = 'View QR';

                                                                        if (data.status === 'success') {
                                                                            // set the image src
                                                                            const img = document.getElementById('qrModalImg');
                                                                            img.src = data.qr_url;

                                                                            // set modal title to the election name
                                                                            document.getElementById('qrModalLabel').innerText = "QR for " + data.voting_period_name;

                                                                            // show modal (Bootstrap 5)
                                                                            const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
                                                                            qrModal.show();
                                                                        } else {
                                                                            alert('QR not found: ' + (data.message || 'Unknown error'));
                                                                        }
                                                                    })
                                                                    .catch(err => {
                                                                        btn.disabled = false;
                                                                        btn.innerText = 'View QR';
                                                                        console.error(err);
                                                                        alert('Error fetching QR.');
                                                                    });
                                                            }
                                                        });
                                                    </script>

                                                    <style>
                                                        .hover-shadow:hover {
                                                            transform: translateY(-3px);
                                                            box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, .15);
                                                            transition: all 0.2s ease-in-out;
                                                        }

                                                        .text-truncate {
                                                            overflow: hidden;
                                                            white-space: nowrap;
                                                            text-overflow: ellipsis;
                                                        }
                                                    </style>
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
    </div>

    <!-- QR Code Modal -->


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


</body>
<button class="back-to-top" id="backToTop" title="Go to top">
    <i class="mdi mdi-arrow-up"></i>
</button>

</html>