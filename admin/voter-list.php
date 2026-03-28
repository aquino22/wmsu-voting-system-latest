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

$whereConditions = [];
$params = [];


// $whereConditions[] = "v.status IN ('verified','accepted','confirmed', 'pending', 'archived')";
$whereConditions[] = "v.status IN ('verified','accepted','confirmed')";
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

$whereClause = "";
if ($whereConditions) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);

    $query = "
SELECT 
    vp.id as voting_period_id,
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
        SEPARATOR '||'
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
            CONCAT(
                '<span class=\"badge rounded-pill px-3 py-2 me-1 mb-1 ',
                IF(base.has_voted > 0,'bg-success','bg-danger'),
                '\">',
                base.election_name,' — ',
                IF(base.has_voted > 0,'Voted','Not Voted'),
                '</span>'
            )
        ) AS participated_elections,

        GROUP_CONCAT(
            CONCAT(
                '<span class=\"badge rounded-pill bg-primary px-3 py-2 me-1 mb-1\">',
                base.precinct_name,
                '</span>'
            )
        ) AS assigned_precincts,

        GROUP_CONCAT(
            CONCAT(
                '<span class=\"badge rounded-pill px-3 py-2 me-1 mb-1 ',
                IF(base.has_voted > 0,'bg-success','bg-warning text-dark'),
                '\">',
                base.precinct_name,' — ',
                IF(base.has_voted > 0,'Voted','Not Voted'),
                '</span>'
            )
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
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Voters' List</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="alert alert-warning" role="alert">
                                                    <h6><b>General Information</b></h6>
                                                    <ul>
                                                        <li>
                                                            <h6>N/A appearing on School Year and Semester means that the Academic Year no longer exists so the students are advised to update their account infomration.</h6>
                                                        </li>

                                                        <li>
                                                            N/A on Custom Voter Fields means that the user hasn't updated their account yet.
                                                        </li>
                                                    </ul>

                                                </div>

                                                <div class="container-fluid mt-4">
                                                    <div class="row mb-3">
                                                        <div class="col-md-12">
                                                            <form method="GET" action="voter-list.php" class="d-flex justify-content-between align-items-center">

                                                                <h3 class="mb-0"><b> Voters' List </b></h3>

                                                                <div class="d-flex align-items-center gap-3">

                                                                    <!-- Academic Year -->
                                                                    <div class="d-flex align-items-center">
                                                                        <small>
                                                                            <label class="form-label me-2 mb-0">Academic Year:</label>
                                                                        </small>

                                                                        <select name="academic_year" class="form-select form-select-sm"
                                                                            onchange="this.form.submit()">

                                                                            <option value="">All Years</option>

                                                                            <?php foreach ($academicYears as $year): ?>
                                                                                <option value="<?= $year['id'] ?>"
                                                                                    <?= ($selectedYear == $year['id']) ? 'selected' : '' ?>>
                                                                                    <?= htmlspecialchars($year['year_label']) ?>
                                                                                </option>
                                                                            <?php endforeach; ?>

                                                                        </select>
                                                                    </div>

                                                                    <!-- Election / Voting Period -->
                                                                    <div class="d-flex align-items-center">
                                                                        <small>
                                                                            <label class="form-label me-2 mb-0">Voting Period with Election:</label>
                                                                        </small>

                                                                        <select name="election_filter"
                                                                            class="form-select form-select-sm"
                                                                            onchange="this.form.submit()">

                                                                            <option value="">All Elections</option>

                                                                            <?php foreach ($allElections as $election): ?>
                                                                                <option value="<?= $election['voting_period_id'] ?>"
                                                                                    <?= ($selectedElection == $election['voting_period_id']) ? 'selected' : '' ?>>
                                                                                    <?= htmlspecialchars($election['election_name']) ?>
                                                                                </option>
                                                                            <?php endforeach; ?>

                                                                        </select>

                                                                    </div>

                                                                </div>

                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Filter Section -->


                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped text-center" id="votersTable">
                                                        <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>School Year</th>
                                                                <th>Semester</th>
                                                                <th>Student ID</th>
                                                                <th>First Name</th>
                                                                <th>Middle Name</th>
                                                                <th>Last Name</th>
                                                                <th>Year Level</th>
                                                                <th>College</th>
                                                                <th>Course</th>
                                                                <th>Department</th>
                                                                <th>Major</th>


                                                                <th>Campus</th>
                                                                <th>Elections Participated</th>
                                                                <th>Precinct</th>
                                                                <th>Vote Status</th>
                                                                <th>Email</th>
                                                                <th>Total Emails Sent</th>
                                                                <th>Email Details</th>
                                                                <th>Custom Voter Fields</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $actualYearLevels = $pdo->query("SELECT id, year_level FROM actual_year_levels")->fetchAll(PDO::FETCH_KEY_PAIR);
                                                            $yearLabels = [
                                                                1 => '1st Year',
                                                                2 => '2nd Year',
                                                                3 => '3rd Year',
                                                                4 => '4th Year',
                                                                5 => '5th Year'
                                                            ];
                                                            ?>
                                                            <?php if (!empty($sentLogs)): ?>
                                                                <?php foreach ($sentLogs as $index => $log): ?>
                                                                    <tr>
                                                                        <td><?= $index + 1 ?></td>
                                                                        <td><?= htmlspecialchars($log['school_year'] ?? 'N/A') ?></td>
                                                                        <td><?= htmlspecialchars($log['semester'] ?? 'N/A') ?></td>
                                                                        <td><?= htmlspecialchars($log['student_id'] ?? 'N/A') ?></td>
                                                                        <td><?= htmlspecialchars($log['first_name'] ?? 'N/A') ?></td>
                                                                        <td><?= htmlspecialchars($log['middle_name'] ?? 'N/A') ?></td>
                                                                        <td><?= htmlspecialchars($log['last_name'] ?? 'N/A') ?></td>

                                                                        <td>
                                                                            <?php
                                                                            $yearLevelId = $log['year_level'] ?? null;             // ID from your log
                                                                            $rawYear = $actualYearLevels[$yearLevelId] ?? null;   // lookup the value in actual_year_levels table
                                                                            echo $yearLabels[$rawYear] ?? 'N/A';                  // convert to label or fallback
                                                                            ?>
                                                                        </td>

                                                                        <!-- <td><?= $yearLevels[$log['year_level']] ?? 'N/A' ?></td> -->

                                                                        <td><?= htmlspecialchars($log['college_name'] ?? 'N/A') ?></td>
                                                                        <td><?= htmlspecialchars($log['course_name'] ?? 'N/A') ?></td>
                                                                        <td><?= htmlspecialchars($log['department_name'] ?? 'N/A') ?></td>
                                                                        <td><?= htmlspecialchars($log['major_name'] ?? 'N/A') ?></td>

                                                                        <td>
                                                                            <?php
                                                                            $wmsu = $log['wmsu_campus_name'] ?? '';
                                                                            $external = $log['external_campus_name'] ?? '';

                                                                            if (!empty($wmsu)) {
                                                                                if ($wmsu === 'WMSU ESU') {
                                                                                    echo htmlspecialchars($wmsu . ' - ' . ($external ?: 'N/A'));
                                                                                } else {
                                                                                    echo htmlspecialchars($wmsu);
                                                                                }
                                                                            } else {
                                                                                echo htmlspecialchars($external ?: 'N/A');
                                                                            }
                                                                            ?>
                                                                        </td>

                                                                        <td style="text-align: left;">
                                                                            <?= !empty($log['participated_elections']) ? $log['participated_elections'] : 'N/A' ?>
                                                                        </td>
                                                                        <td style="text-align: left;">
                                                                            <?= !empty($log['assigned_precincts']) ? $log['assigned_precincts'] : 'N/A' ?>
                                                                        </td>
                                                                        <td style="text-align: left;">
                                                                            <?= !empty($log['vote_statuses']) ? $log['vote_statuses'] : 'N/A' ?>
                                                                        </td>

                                                                        <td><?= htmlspecialchars($log['voter_email'] ?? 'N/A') ?></td>
                                                                        <td><?= htmlspecialchars($log['total_emails_sent'] ?? '0') ?></td>

                                                                        <td>
                                                                            <?php
                                                                            if (empty($log['email_details'])) {
                                                                                echo "<span class='badge badge-warning text-black'>N/A</span>";
                                                                            } else {
                                                                                $details = explode('||', $log['email_details']);
                                                                                foreach ($details as $detail) {
                                                                                    $detail = trim($detail);
                                                                                    if ($detail === '') continue;

                                                                                    preg_match('/^(\w+)\s*\(([^)]+)\s*-\s*([^)]+)\)$/', $detail, $matches);

                                                                                    if (count($matches) === 4) {
                                                                                        $status = strtolower($matches[1]);
                                                                                        $timestamp = $matches[2];
                                                                                        $votingPeriodName = $matches[3];

                                                                                        $badgeClass = match ($status) {
                                                                                            'sent'   => 'badge-success',
                                                                                            'failed' => 'badge-danger',
                                                                                            default  => 'badge-secondary',
                                                                                        };

                                                                                        $formattedTime = date('M j, Y g:i A', strtotime($timestamp));
                                                                                        $displayText = strtoupper($status) . ' - ' . $votingPeriodName . ' at ' . $formattedTime . " | VIEW";

                                                                                        $dataAttributes = $status === 'sent'
                                                                                            ? "data-student-id='{$log['student_id']}' data-voting-period='{$log['voting_period_id']}'"
                                                                                            : "";

                                                                                        echo "<span class='badge $badgeClass me-1 mb-1 qr-badge' style='cursor:pointer;' $dataAttributes>
                    $displayText
                  </span><br>";
                                                                                    } else {
                                                                                        echo "<span class='badge badge-secondary'>" . htmlspecialchars($detail ?: 'N/A') . "</span><br>";
                                                                                    }
                                                                                }
                                                                            }
                                                                            ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php if (!empty($log['custom_voter_option']) && $log['custom_voter_option'] == '1'): ?>
                                                                                <a
                                                                                    href="view_voter.php?student_id=<?= urlencode($log['student_id']) ?>"
                                                                                    class="btn btn-sm btn-primary text-white">
                                                                                    View Custom Voter Options
                                                                                </a>
                                                                            <?php else: ?>
                                                                                N/A
                                                                            <?php endif; ?>
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
                order: [
                    [0, 'desc']
                ], // 0 = first column (full_name column index may differ)
                language: {
                    search: "Filter records:",
                    emptyTable: "No voters found matching the selected filters." // ← handles empty state
                },
                columnDefs: [{
                        targets: [13, 14, 15, 18],
                        orderable: false,
                        searchable: false
                    } // Elections, Precinct, Vote Status, Email Details
                ]
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

        $(document).ready(function() {
            const headerCount = $('#votersTable thead tr th').length;
            console.log('Header column count:', headerCount);

            $('#votersTable tbody tr').each(function(i) {
                const cellCount = $(this).find('td').length;
                if (cellCount !== headerCount) {
                    console.warn(`Row ${i + 1} has ${cellCount} cells (expected ${headerCount})`);
                    console.log($(this).html());
                }
            });



        });
    </script>
</body>
<button class="back-to-top" id="backToTop" title="Go to top">
    <i class="mdi mdi-arrow-up"></i>
</button>

</html>