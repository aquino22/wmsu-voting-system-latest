<?php
session_start();
include('includes/conn.php');
include('includes/archive_conn.php');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = 'NON_ADMIN';
    header("Location: ../index.php");
    exit();
}
?>

<?php


$stmt = $pdo->query("
    SELECT c.*, p.campus_name AS parent_name
    FROM campuses c
    LEFT JOIN campuses p ON c.parent_id = p.campus_id
    ORDER BY 
        COALESCE(c.parent_id, c.campus_id),
        c.parent_id IS NULL DESC,
        c.campus_name ASC
");
$campuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT c.*, cc.coordinate_id, cc.latitude, cc.longitude, cc.campus_id, camp.campus_name
    FROM colleges c
    LEFT JOIN college_coordinates cc ON c.college_id = cc.college_id
    LEFT JOIN campuses camp ON cc.campus_id = camp.campus_id
    ORDER BY c.college_name ASC
");
$colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT co.*, c.college_name
    FROM courses co
    JOIN colleges c ON co.college_id = c.college_id
    ORDER BY c.college_name, co.course_name
");
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT d.*, c.college_name
    FROM departments d
    JOIN colleges c ON d.college_id = c.college_id
    ORDER BY c.college_name, d.department_name
");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT 
        m.major_id,
        m.major_name,
        m.course_id,
        c.course_name,
        c.course_code,
        co.college_id,
        co.college_name
    FROM majors m
    JOIN courses c ON m.course_id = c.id
    JOIN colleges co ON c.college_id = co.college_id
    ORDER BY co.college_name, c.course_name, m.major_name
");

$majors_new = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT 
        c.id AS course_id,
        c.course_name,
        col.college_name,
        m.major_id,
        m.major_name,
        GROUP_CONCAT(DISTINCT ayl.year_level ORDER BY ayl.year_level ASC) AS year_levels
    FROM courses c
    JOIN colleges col ON c.college_id = col.college_id
    JOIN actual_year_levels ayl ON c.id = ayl.course_id
    LEFT JOIN majors m ON ayl.major_id = m.major_id
    GROUP BY c.id, ayl.major_id
    ORDER BY col.college_name, c.course_name, m.major_name
");
$coursesWithLevels = $stmt->fetchAll(PDO::FETCH_ASSOC);


$query = "
    SELECT c.*, col.college_name
    FROM courses c
    JOIN colleges col ON c.college_id = col.college_id
    ORDER BY col.college_name ASC, c.course_name ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute();

$courses_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, level, description FROM year_levels ORDER BY level ASC");
$year_levels_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
$status = $_SESSION['status'] ?? null;
$message = $_SESSION['message'] ?? null;
unset($_SESSION['status'], $_SESSION['message']);
$activeTab = $_GET['tab'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU i-Elect: Admin | Academic Details </title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet"
        href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="images/favicon.png" />

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<style>
    /* Back to Top Button */
    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background-color: #B22222;
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1000;
        opacity: 0;
        transform: translateY(20px) scale(0.8);
        visibility: hidden;
    }

    .back-to-top.show {
        opacity: 1;
        transform: translateY(0) scale(1);
        visibility: visible;
    }

    .back-to-top:hover {
        background-color: #8B1A1A;
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    }

    .back-to-top:active {
        transform: translateY(-1px) scale(1.02);
    }
</style>


<body>
    <div class="container-scroller">
        <!-- Navbar -->
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

        <nav class="navbar default-layout col-lg-12 col-12 p-0  d-flex align-items-center justify-content-center flex-row">
            <!-- [Existing navbar code unchanged] -->
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
                <div class="me-3">
                    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
                        <span class="icon-menu"></span>
                    </button>
                </div>
                <div>
                    <a class="navbar-brand brand-logo" href="index.php">
                        <img src="images/wmsu-logo.png" alt="logo" class="logo img-fluid" />
                        <small style="font-size: 16px;"><b>WMSU i-Elect</b></small>
                    </a>
                    <a class="navbar-brand brand-logo-mini" href="index.html">
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

        <!-- Sidebar -->
        <div class="container-fluid page-body-wrapper">
            <?php include('includes/sidebar.php') ?>

            <!-- Main Content -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active ps-0" id="campuses-tab" data-bs-toggle="tab" href="#campuses" role="tab" aria-controls="campuses" aria-selected="true">WMSU Campuses</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="college-tab" data-bs-toggle="tab" href="#college" role="tab" aria-controls="college" aria-selected="false">College</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="department-tab" data-bs-toggle="tab" href="#department" role="tab" aria-controls="department" aria-selected="false">Department</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="courses-tab" data-bs-toggle="tab" href="#courses" role="tab" aria-controls="courses" aria-selected="false">Courses</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="major-tab" data-bs-toggle="tab" href="#majors" role="tab" aria-controls="major" aria-selected="false">Major</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="yearlevel-tab" data-bs-toggle="tab" href="#yearlevel" role="tab" aria-controls="yearlevel" aria-selected="false">Year Level</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="campuses" role="tabpanel" aria-labelledby="campuses-tab">
                                        <div class="card">
                                            <div class="card-body p-4">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h4 class="card-title">WMSU Campuses</h4>
                                                    <button class="btn btn-primary text-white" data-bs-toggle="modal" data-bs-target="#addCampusModal">
                                                        <i class="mdi mdi-plus"></i> Add Campus
                                                    </button>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover align-middle datatable" id="campusTable">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th width="60">#</th>
                                                                <th>Campus Name</th>

                                                                <th>Parent Campus</th>
                                                                <th>Location with Latitude and Longitude</th>
                                                                <th width="200" class="text-center">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($campuses)): ?>
                                                                <?php foreach ($campuses as $index => $row): ?>

                                                                    <?php
                                                                    $isChild = !is_null($row['parent_id']);
                                                                    $indent = $isChild ? '&nbsp;&nbsp;&nbsp;&nbsp;↳ ' : '';
                                                                    ?>

                                                                    <tr>
                                                                        <td><?= $index + 1 ?></td>

                                                                        <!-- Campus Name with hierarchy -->
                                                                        <td>
                                                                            <?= $indent . htmlspecialchars($row['campus_name']) ?>
                                                                        </td>

                                                                        <!-- Parent Name -->
                                                                        <td>
                                                                            <?= $row['parent_name'] ? htmlspecialchars($row['parent_name']) : '—' ?>
                                                                        </td>

                                                                        <td>
                                                                            <?= ($row['campus_name'] === 'WMSU ESU') ? '—' : htmlspecialchars('•' . $row['campus_location']) ?> <br>
                                                                            <?= ($row['campus_name'] === 'WMSU ESU') ? '' : 'X: ' . $row['latitude'] . ',' ?>
                                                                            <?= ($row['campus_name'] === 'WMSU ESU') ? '' : 'Y: ' . $row['longitude'] ?>
                                                                        </td>

                                                                        <td class="text-center">

                                                                            <!-- Edit -->
                                                                            <button
                                                                                class="btn btn-warning btn-sm"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#editCampusModal"
                                                                                onclick="setEditCampus(
                                <?= $row['campus_id'] ?>,
                                '<?= htmlspecialchars($row['campus_name'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($row['campus_location'], ENT_QUOTES) ?>',
                                        '<?= $row['campus_type'] ?>',
                                '<?= $row['parent_id'] ?? '' ?>',
                                '<?= $row['latitude'] ?? '' ?>',
                                '<?= $row['longitude'] ?? '' ?>'
                            )">
                                                                                Edit
                                                                            </button>

                                                                            <!-- Delete -->
                                                                            <?php if ($row['campus_name'] !== 'WMSU ESU' && $row['campus_name'] !== 'Main Campus'): ?>

                                                                                <button
                                                                                    class="btn btn-danger btn-sm"
                                                                                    onclick="confirmDeleteCampus(<?= $row['campus_id'] ?>)">
                                                                                    Delete
                                                                                </button>

                                                                            <?php else: ?>

                                                                                <button class="btn btn-secondary btn-sm" disabled>
                                                                                    Protected
                                                                                </button>

                                                                            <?php endif; ?>

                                                                        </td>
                                                                    </tr>

                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <tr>
                                                                    <td colspan="5" class="text-center text-muted">
                                                                        No campuses found.
                                                                    </td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="college" role="tabpanel" aria-labelledby="college-tab">
                                        <div class="card">
                                            <div class="card-body p-4">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h4 class="card-title">Colleges</h4>
                                                    <button class="btn btn-primary text-white" data-bs-toggle="modal" data-bs-target="#addCollegeModal">
                                                        <i class="mdi mdi-plus"></i> Add College
                                                    </button>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover align-middle datatable">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>College Name</th>
                                                                <th>College Abbreviation</th>
                                                                <th>Campus</th>
                                                                <th>Coordinates</th>

                                                                <th class="text-center" width="200">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            if ($colleges): foreach ($colleges as $index => $c):
                                                            ?>
                                                                    <tr>
                                                                        <td><?= $index + 1 ?></td>
                                                                        <td><?= htmlspecialchars($c['college_name']) ?></td>
                                                                        <td><?= htmlspecialchars($c['college_abbreviation']) ?></td>
                                                                        <td><?= htmlspecialchars($c['campus_name'] ?? 'N/A') ?></td>
                                                                        <td>
                                                                            <?php if (!empty($c['latitude']) && !empty($c['longitude'])): ?>
                                                                                <?= htmlspecialchars($c['latitude']) ?>, <?= htmlspecialchars($c['longitude']) ?>
                                                                            <?php else: ?>
                                                                                <span class="text-muted">N/A</span>
                                                                            <?php endif; ?>
                                                                        </td>

                                                                        <td class="text-center">
                                                                            <button class="btn btn-warning btn-sm"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#editCollegeModal"
                                                                                onclick="setEditCollege(
            <?= $c['college_id'] ?>,
            <?= $c['college_abbreviation'] ?>,
            '<?= htmlspecialchars($c['college_name'], ENT_QUOTES) ?>',
            '<?= $c['latitude'] ?? '' ?>',
            '<?= $c['longitude'] ?? '' ?>',
            '<?= $c['campus_id'] ?? '' ?>',
            <?= $c['coordinate_id'] ?? 'null' ?>
        )">
                                                                                Edit
                                                                            </button>
                                                                            <button class="btn btn-danger btn-sm"
                                                                                onclick="confirmDeleteCollege(<?= $c['college_id'] ?>)">
                                                                                Delete
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <tr>
                                                                    <td colspan="4" class="text-center text-muted">No colleges found.</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="courses" role="tabpanel" aria-labelledby="courses-tab">
                                        <div class="card">
                                            <div class="card-body p-4">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h4 class="card-title">Courses</h4>
                                                    <button class="btn btn-primary text-white" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                                                        <i class="mdi mdi-plus"></i> Add Course
                                                    </button>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover align-middle datatable">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Course Name</th>
                                                                <th>College</th>
                                                                <th>Course Code</th>
                                                                <th class="text-center" width="200">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if ($courses): foreach ($courses as $index => $co): ?>
                                                                    <tr>
                                                                        <td><?= $index + 1 ?></td>
                                                                        <td><?= htmlspecialchars($co['course_name']) ?></td>
                                                                        <td><?= htmlspecialchars($co['college_name']) ?></td>
                                                                        <td><?= htmlspecialchars($co['course_code']) ?></td>
                                                                        <td class="text-center">
                                                                            <button class="btn btn-warning btn-sm"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#editCourseModal"
                                                                                onclick="setEditCourse(<?= $co['id'] ?>, '<?= htmlspecialchars($co['course_name'], ENT_QUOTES) ?>', <?= $co['college_id'] ?>, '<?= $co['course_code'] ?? '' ?>')">
                                                                                Edit
                                                                            </button>
                                                                            <button class="btn btn-danger btn-sm"
                                                                                onclick="confirmDeleteCourse(<?= $co['id'] ?>)">
                                                                                Delete
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach;
                                                            else: ?>
                                                                <tr>
                                                                    <td colspan="4" class="text-center text-muted">No courses found.</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="department" role="tabpanel" aria-labelledby="department-tab">
                                        <div class="card">
                                            <div class="card-body p-4">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h4 class="card-title">Departments</h4>
                                                    <button class="btn btn-primary text-white" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                                                        <i class="mdi mdi-plus"></i> Add Department
                                                    </button>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover align-middle datatable">
                                                        <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Department Name</th>
                                                                <th>College</th>
                                                                <th class="text-center" width="200">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if ($departments): foreach ($departments as $index => $d): ?>
                                                                    <tr>
                                                                        <td><?= $index + 1 ?></td>
                                                                        <td><?= htmlspecialchars($d['department_name']) ?></td>
                                                                        <td><?= htmlspecialchars($d['college_name']) ?></td>
                                                                        <td class="text-center">
                                                                            <button class="btn btn-warning btn-sm"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#editDepartmentModal"
                                                                                onclick="setEditDepartment(<?= $d['department_id'] ?>, '<?= htmlspecialchars($d['department_name'], ENT_QUOTES) ?>', <?= $d['college_id'] ?>)">
                                                                                Edit
                                                                            </button>
                                                                            <button class="btn btn-danger btn-sm"
                                                                                onclick="confirmDeleteDepartment(<?= $d['department_id'] ?>)">
                                                                                Delete
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach;
                                                            else: ?>
                                                                <tr>
                                                                    <td colspan="4" class="text-center text-muted">No departments found.</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="majors" role="tabpanel" aria-labelledby="major-tab">
                                        <div class="card">
                                            <div class="card-body p-4">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h4 class="card-title">Majors</h4>
                                                    <button class="btn btn-primary text-white" data-bs-toggle="modal" data-bs-target="#addMajorModal">
                                                        <i class="mdi mdi-plus"></i> Add Major
                                                    </button>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover align-middle datatable">
                                                        <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Major Name</th>
                                                                <th>Course</th>
                                                                <th>College</th>
                                                                <th class="text-center" width="200">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            // Fetch majors with course and college info
                                                            $stmt = $pdo->query("
                            SELECT 
                                m.major_id,
                                m.major_name,
                                c.course_name,
                                col.college_name
                            FROM majors m
                            JOIN courses c ON m.course_id = c.id
                            JOIN colleges col ON c.college_id = col.college_id
                            ORDER BY col.college_name ASC, c.course_name ASC, m.major_name ASC
                        ");
                                                            $majors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                            ?>

                                                            <?php if ($majors): foreach ($majors as $index => $major): ?>
                                                                    <tr>
                                                                        <td><?= $index + 1 ?></td>
                                                                        <td><?= htmlspecialchars($major['major_name']) ?></td>
                                                                        <td><?= htmlspecialchars($major['course_name']) ?></td>
                                                                        <td><?= htmlspecialchars($major['college_name']) ?></td>
                                                                        <td class="text-center">
                                                                            <button class="btn btn-warning btn-sm edit-major-btn"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#editMajorModal"
                                                                                data-major-id="<?= $major['major_id'] ?>"
                                                                                data-major-name="<?= htmlspecialchars($major['major_name'], ENT_QUOTES) ?>"
                                                                                data-course-name="<?= htmlspecialchars($major['course_name'], ENT_QUOTES) ?>">
                                                                                Edit
                                                                            </button>
                                                                            <button class="btn btn-danger btn-sm" onclick="confirmDeleteMajor(<?= $major['major_id'] ?>)">
                                                                                Delete
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach;
                                                            else: ?>
                                                                <tr>
                                                                    <td colspan="5" class="text-center text-muted">No majors found.</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="yearlevel" role="tabpanel" aria-labelledby="yearlevel-tab">
                                        <div class="card">
                                            <div class="card-body p-4">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h4 class="card-title">Year Levels per Course and Major</h4>
                                                    <button class="btn btn-primary text-white" data-bs-toggle="modal" data-bs-target="#addYearLevelModal">
                                                        <i class="mdi mdi-plus"></i> Assign Year Levels
                                                    </button>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover align-middle datatable">
                                                        <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Course Name</th>
                                                                <th>Major</th>
                                                                <th>College</th>
                                                                <th>Year Levels</th>
                                                                <th class="text-center" width="200">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if ($coursesWithLevels): foreach ($coursesWithLevels as $index => $course): ?>
                                                                    <tr>
                                                                        <td><?= $index + 1 ?></td>
                                                                        <td><?= htmlspecialchars($course['course_name']) ?></td>
                                                                        <td>
                                                                            <?= $course['major_name']
                                                                                ? htmlspecialchars($course['major_name'])
                                                                                : '<span class="text-muted">No Major (General)</span>' ?>
                                                                        </td>

                                                                        <td><?= htmlspecialchars($course['college_name']) ?></td>
                                                                        <td>
                                                                            <?= $course['year_levels']
                                                                                ? htmlspecialchars($course['year_levels'])
                                                                                : '<span class="text-muted">Not set</span>' ?>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <button class="btn btn-warning btn-sm edit-year-level-btn"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#editYearLevelModal"
                                                                                data-course-id="<?= $course['course_id'] ?>"
                                                                                data-course-name="<?= htmlspecialchars($course['course_name'], ENT_QUOTES) ?>"
                                                                                data-major-id="<?= $course['major_id'] ?? '' ?>"
                                                                                data-major-name="<?= htmlspecialchars($course['major_name'] ?? '', ENT_QUOTES) ?>"
                                                                                data-year-level-ids="<?= htmlspecialchars($course['year_level_ids'] ?? '', ENT_QUOTES) ?>">
                                                                                Edit
                                                                            </button>
                                                                            <!-- <button class="btn btn-danger btn-sm" onclick="confirmDeleteYearLevel( <?= $course['course_id'] ?>, '<?= $course['major_id'] ?? '' ?>')">
                                                                                Delete
                                                                            </button> -->
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach;
                                                            else: ?>
                                                                <tr>
                                                                    <td colspan="6" class="text-center text-muted">No courses found.</td>
                                                                </tr>
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

                <!-- Plugins and Scripts -->
                <script src="vendors/js/vendor.bundle.base.js"></script>
                <script src="vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
                <script src="vendors/progressbar.js/progressbar.min.js"></script>
                <script src="js/off-canvas.js"></script>
                <script src="js/hoverable-collapse.js"></script>
                <script src="js/template.js"></script>
                <script src="js/settings.js"></script>
                <script src="js/todolist.js"></script>
                <script src="js/dashboard.js"></script>


                <!-- jQuery and DataTables are already included in the head -->
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

                <!-- Leaflet JS -->
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>




                <!-- SweetAlert2 Reminder Script -->


                <!-- Logout Script -->
                <script>
                    document.getElementById('logoutLink').addEventListener('click', function(e) {
                        e.preventDefault();
                        if (navigator.geolocation) {
                            navigator.geolocation.getCurrentPosition(
                                (position) => {
                                    const location = `${position.coords.latitude},${position.coords.longitude}`;
                                    window.location.href = `processes/accounts/logout.php?location=${encodeURIComponent(location)}`;
                                },
                                (error) => {
                                    console.log('Geolocation declined or unavailable:', error.message);
                                    window.location.href = 'processes/accounts/logout.php?location=N/A';
                                }, {
                                    timeout: 10000
                                }
                            );
                        } else {
                            window.location.href = 'processes/accounts/logout.php?location=N/A';
                        }
                    });
                </script>

                <!-- Your existing scripts -->
                <script>
                    function setEditCampus(id, name, location, type, parentId, latitude, longitude) {

                        document.getElementById('editCampusId').value = id;
                        document.getElementById('editCampusName').value = name;
                        document.getElementById('editCampusLocation').value = location ?? '';
                        document.getElementById('editCampusParentId').value = parentId ?? '';
                        document.getElementById('editCampusLatitude').value = latitude ?? '';
                        document.getElementById('editCampusLongitude').value = longitude ?? '';

                        const campusNameInput = document.getElementById("editCampusName");

                        if (name === "WMSU ESU" || name === "Main Campus") {
                            campusNameInput.setAttribute("readonly", true);
                        } else {
                            campusNameInput.removeAttribute("readonly");
                        }
                    }

                    let addMap, editMap, editCollegeMap;
                    let addMarker, editMarker, editCollegeMarker;

                    function initLeafletMap(mapId, latInputId, lngInputId, isEdit) {
                        const defaultLat = 6.912972;
                        const defaultLng = 122.063213;

                        const map = L.map(mapId).setView([defaultLat, defaultLng], 15);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(map);

                        let marker;

                        map.on('click', function(e) {
                            const lat = e.latlng.lat;
                            const lng = e.latlng.lng;

                            if (marker) {
                                marker.setLatLng([lat, lng]);
                            } else {
                                marker = L.marker([lat, lng]).addTo(map);
                            }

                            document.getElementById(latInputId).value = lat.toFixed(6);
                            document.getElementById(lngInputId).value = lng.toFixed(6);

                            if (isEdit) editMarker = marker;
                            else addMarker = marker;
                        });

                        return {
                            map,
                            marker
                        };
                    }

                    document.addEventListener('DOMContentLoaded', function() {
                        const addModal = document.getElementById('addCampusModal');
                        addModal.addEventListener('shown.bs.modal', function() {
                            if (!addMap) {
                                const res = initLeafletMap('addCampusMap', 'addCampusLatitude', 'addCampusLongitude', false);
                                addMap = res.map;
                                addMarker = res.marker;
                            }
                            addMap.invalidateSize();
                        });

                        const editModal = document.getElementById('editCampusModal');
                        editModal.addEventListener('shown.bs.modal', function() {
                            if (!editMap) {
                                const res = initLeafletMap('editCampusMap', 'editCampusLatitude', 'editCampusLongitude', true);
                                editMap = res.map;
                                editMarker = res.marker;
                            }
                            editMap.invalidateSize();

                            const lat = parseFloat(document.getElementById('editCampusLatitude').value);
                            const lng = parseFloat(document.getElementById('editCampusLongitude').value);

                            if (!isNaN(lat) && !isNaN(lng)) {
                                const latLng = [lat, lng];
                                if (editMarker) {
                                    editMarker.setLatLng(latLng);
                                } else {
                                    editMarker = L.marker(latLng).addTo(editMap);
                                }
                                editMap.setView(latLng, 16);
                            } else {
                                if (editMarker) {
                                    editMap.removeLayer(editMarker);
                                    editMarker = null;
                                }
                                editMap.setView([6.912972, 122.063213], 15);
                            }
                        });

                        const editCollegeModal = document.getElementById('editCollegeModal');
                        editCollegeModal.addEventListener('shown.bs.modal', function() {
                            if (!editCollegeMap) {
                                const res = initLeafletMap('editCollegeMap', 'editCollegeLatitude', 'editCollegeLongitude', true);
                                editCollegeMap = res.map;
                                editCollegeMarker = res.marker;
                            }
                            editCollegeMap.invalidateSize();

                            const lat = parseFloat(document.getElementById('editCollegeLatitude').value);
                            const lng = parseFloat(document.getElementById('editCollegeLongitude').value);

                            if (!isNaN(lat) && !isNaN(lng)) {
                                const latLng = [lat, lng];
                                if (editCollegeMarker) {
                                    editCollegeMarker.setLatLng(latLng);
                                } else {
                                    editCollegeMarker = L.marker(latLng).addTo(editCollegeMap);
                                }
                                editCollegeMap.setView(latLng, 16);
                            } else {
                                if (editCollegeMarker) {
                                    editCollegeMap.removeLayer(editCollegeMarker);
                                    editCollegeMarker = null;
                                }
                                editCollegeMap.setView([6.912972, 122.063213], 15);
                            }
                        });
                    });
                </script>

</body>

<!-- Modals -->
<!-- Add Campus Modal -->
<div class="modal fade" id="addCampusModal" tabindex="-1" aria-labelledby="addCampusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addCampusForm" method="POST" action="processes/campus/campus_crud.php?action=add">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCampusModalLabel">Add New Campus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <!-- Left: Form Fields -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="addCampusName" class="form-label">Campus Name</label>
                                <input type="text" class="form-control" id="addCampusName" name="campus_name" required>
                            </div>

                            <div class="mb-3">
                                <label for="addCampusLocation" class="form-label">Location</label>
                                <input type="text" class="form-control" id="addCampusLocation" name="campus_location">
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label for="addCampusLatitude" class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="addCampusLatitude" name="latitude">
                                </div>
                                <div class="col-6 mb-3">
                                    <label for="addCampusLongitude" class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="addCampusLongitude" name="longitude">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Parent Campus (Optional)</label>
                                <select name="parent_id" class="form-select">
                                    <option value="">None</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT campus_id, campus_name FROM campuses WHERE parent_id IS NULL ORDER BY campus_name ASC");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                        <option value="<?= $row['campus_id'] ?>">
                                            <?= htmlspecialchars($row['campus_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Right: Leaflet Map -->
                        <div class="col-md-6">
                            <label class="form-label d-block mb-2">Select Location on Map</label>
                            <div id="addCampusMap" style="height: 300px; width: 100%; border: 1px solid #dee2e6; border-radius: 4px;"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Campus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Campus Modal -->
<div class="modal fade" id="editCampusModal" tabindex="-1" aria-labelledby="editCampusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editCampusForm" method="POST" action="processes/campus/campus_crud.php?action=update">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCampusModalLabel">Edit Campus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editCampusId" name="campus_id">
                    <div class="row">
                        <!-- Left: Form Fields -->
                        <div class="col-md-6">
                            <!-- Name -->
                            <div class="mb-3">
                                <label for="editCampusName" class="form-label">Campus Name</label>
                                <input type="text" class="form-control" id="editCampusName" name="campus_name" required>
                            </div>

                            <!-- Location -->
                            <div class="mb-3">
                                <label for="editCampusLocation" class="form-label">Location</label>
                                <input type="text" class="form-control" id="editCampusLocation" name="campus_location">
                            </div>

                            <!-- Latitude / Longitude -->
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label for="editCampusLatitude" class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="editCampusLatitude" name="latitude">
                                </div>
                                <div class="col-6 mb-3">
                                    <label for="editCampusLongitude" class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="editCampusLongitude" name="longitude">
                                </div>
                            </div>

                            <!-- Parent Campus -->
                            <div class="mb-3">
                                <label class="form-label">Parent Campus (Optional)</label>
                                <select id="editCampusParentId" class="form-select" name="parent_id">
                                    <option value="">None</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT campus_id, campus_name FROM campuses WHERE parent_id IS NULL ORDER BY campus_name ASC");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                        <option value="<?= $row['campus_id'] ?>" <?= isset($campus['parent_id']) && $campus['parent_id'] == $row['campus_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($row['campus_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Right: Map -->
                        <div class="col-md-6">
                            <label class="form-label d-block mb-2">Select Location on Map</label>
                            <div id="editCampusMap" style="height: 100%; min-height: 300px; width: 100%; border: 1px solid #dee2e6; border-radius: 4px;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Campus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add College Modal -->
<div class="modal fade" id="addCollegeModal" tabindex="-1" aria-labelledby="addCollegeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="addCollegeForm" action="processes/college/college_crud.php?action=add" method="POST">

                <div class="modal-header">
                    <h5 class="modal-title" id="addCollegeModalLabel">Add College to Campus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">

                        <!-- LEFT SIDE -->
                        <div class="col-md-6">

                            <!-- College Name -->
                            <div class="mb-3">
                                <label class="form-label">College Name</label>
                                <input type="text" class="form-control" name="college_name" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">College Abbreviation</label>
                                <input type="text" class="form-control" name="college_abbreviation" required>
                            </div>

                            <!-- Campus -->
                            <div class="mb-3">
                                <label class="form-label">Campus</label>
                                <select name="campus_id" class="form-select" required>
                                    <option value="">Select Campus</option>

                                    <?php
                                    $stmt = $pdo->query("
    SELECT campus_id, campus_name, latitude, longitude 
    FROM campuses 
    WHERE campus_name != 'WMSU ESU'
    ORDER BY campus_name ASC
");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                    ?>

                                        <option value="<?= $row['campus_id'] ?>" data-lat="<?= $row['latitude'] ?>" data-lng="<?= $row['longitude'] ?>">
                                            <?= htmlspecialchars($row['campus_name']) ?>
                                        </option>

                                    <?php endwhile; ?>

                                </select>
                            </div>

                            <!-- Coordinates -->
                            <div class="row">

                                <div class="col-6 mb-3">
                                    <label class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="collegeLatitude" name="latitude">
                                </div>

                                <div class="col-6 mb-3">
                                    <label class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="collegeLongitude" name="longitude">
                                </div>

                            </div>

                        </div>

                        <!-- RIGHT SIDE MAP -->
                        <div class="col-md-6">

                            <label class="form-label d-block mb-2">Select Location on Map</label>

                            <div id="collegeMap"
                                style="height:300px;width:100%;border:1px solid #dee2e6;border-radius:4px;">
                            </div>

                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save & Add to Another Campus</button>
                </div>

            </form>

        </div>
    </div>
</div>

<script>
    let newMap;
    let newMarker;
    let collegeAdded = false; // Flag to check if we need to reload

    // Reload page when modal is closed if a college was added
    document.getElementById('addCollegeModal').addEventListener('hidden.bs.modal', function() {
        if (collegeAdded) {
            location.reload();
        }
    });

    document.getElementById('addCollegeModal').addEventListener('shown.bs.modal', function() {

        setTimeout(function() {
            newMap.invalidateSize();
        }, 200);

        // trigger campus change automatically
        campusSelect.dispatchEvent(new Event('change'));

    });

    document.addEventListener("DOMContentLoaded", function() {



        newMap = L.map('collegeMap').setView([6.912972, 122.063213], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(newMap);

        newMap.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            document.getElementById('collegeLatitude').value = lat.toFixed(6);
            document.getElementById('collegeLongitude').value = lng.toFixed(6);

            if (newMarker) {
                newMarker.setLatLng([lat, lng]);
            } else {
                newMarker = L.marker([lat, lng]).addTo(newMap);
            }
        });

        const campusSelect = document.querySelector('#addCollegeForm select[name="campus_id"]');

        campusSelect.addEventListener('change', function() {

            const selected = this.options[this.selectedIndex];

            const lat = parseFloat(selected.dataset.lat);
            const lng = parseFloat(selected.dataset.lng);

            if (!isNaN(lat) && !isNaN(lng)) {

                // Populate inputs
                document.getElementById('collegeLatitude').value = lat;
                document.getElementById('collegeLongitude').value = lng;

                const position = [lat, lng];

                // Move map
                newMap.setView(position, 17);

                // Add or move marker
                if (newMarker) {
                    newMarker.setLatLng(position);
                } else {
                    newMarker = L.marker(position).addTo(newMap);
                }

            }

        });

    });
</script>

<!-- Edit College Modal -->
<div class="modal fade" id="editCollegeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editCollegeForm" action="processes/college/college_crud.php?action=update" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit College</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="hidden" name="college_id" id="editCollegeId">
                            <input type="hidden" name="coordinate_id" id="editCoordinateId">
                            <div class="mb-3">
                                <label class="form-label">College Name</label>
                                <input type="text" name="college_name" id="editCollegeName" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">College Abbreviation</label>
                                <input type="text" name="college_abbreviation" id="editCollegeAbbreviation" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Campus</label>
                                <select name="campus_id" id="editCollegeCampus" class="form-select" required>
                                    <option value="">Select Campus</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT campus_id, campus_name FROM campuses ORDER BY campus_name ASC");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                        <option value="<?= $row['campus_id'] ?>">
                                            <?= htmlspecialchars($row['campus_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="editCollegeLatitude" name="latitude">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="editCollegeLongitude" name="longitude">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-block mb-2">Select Location on Map</label>
                            <div id="editCollegeMap" style="height:300px;width:100%;border:1px solid #dee2e6;border-radius:4px;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update College</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addCourseForm" action="processes/course/course_crud.php?action=add" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Course Name</label>
                        <input type="text" name="course_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">College</label>
                        <select name="college_id" class="form-select" required>
                            <option value="">Select College</option>
                            <?php
                            $stmt = $pdo->query("SELECT college_id, college_name FROM colleges ORDER BY college_name ASC");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$row['college_id']}'>" . htmlspecialchars($row['college_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Course Code (usually just the name after the degree)</label>
                        <input type="text" name="course_code" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCourseForm" action="processes/course/course_crud.php?action=update" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="course_id" id="editCourseId">
                    <div class="mb-3">
                        <label class="form-label">Course Name</label>
                        <input type="text" name="course_name" id="editCourseName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">College</label>
                        <select name="college_id" id="editCourseCollege" class="form-select" required>
                            <option value="">Select College</option>
                            <?php
                            $stmt = $pdo->query("SELECT college_id, college_name FROM colleges ORDER BY college_name ASC");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$row['college_id']}'>{$row['college_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Course Code (usually just the name after the degree)</label>
                        <input type="text" name="course_code" id="editCourseCode" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addDepartmentForm" action="processes/department/department_crud.php?action=add" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Department Name</label>
                        <input type="text" name="department_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">College</label>
                        <select name="college_id" class="form-select" required>
                            <option value="">Select College</option>
                            <?php
                            $stmt = $pdo->query("SELECT college_id, college_name FROM colleges ORDER BY college_name ASC");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$row['college_id']}'>" . htmlspecialchars($row['college_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editDepartmentForm" action="processes/department/department_crud.php?action=update" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="department_id" id="editDepartmentId">
                    <div class="mb-3">
                        <label class="form-label">Department Name</label>
                        <input type="text" name="department_name" id="editDepartmentName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">College</label>
                        <select name="college_id" id="editDepartmentCollege" class="form-select" required>
                            <option value="">Select College</option>
                            <?php
                            $stmt = $pdo->query("SELECT college_id, college_name FROM colleges ORDER BY college_name ASC");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$row['college_id']}'>{$row['college_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Department</button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
    function setEditDepartment(id, name, collegeId) {
        document.getElementById('editDepartmentId').value = id;
        document.getElementById('editDepartmentName').value = name;
        document.getElementById('editDepartmentCollege').value = collegeId;
    }

    function setEditCourse(id, name, collegeId, collegeCode) {
        document.getElementById('editCourseId').value = id;
        document.getElementById('editCourseName').value = name;
        document.getElementById('editCourseCollege').value = collegeId;
        document.getElementById('editCourseCode').value = collegeCode;
    }
</script>
<!-- Add Major Modal -->
<div class="modal fade" id="addMajorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addMajorForm" action="processes/major/major_crud.php?action=add" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Major</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Major Name</label>
                        <input type="text" name="major_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Course</label>
                        <select name="course_id" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses_list as $course): ?>
                                <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['course_name']) ?> (<?= htmlspecialchars($course['college_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Major</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Major Modal -->
<div class="modal fade" id="editMajorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editMajorForm" action="processes/major/major_crud.php?action=update" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Major</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="major_id" id="editMajorId">
                    <div class="mb-3">
                        <label class="form-label">Major Name</label>
                        <input type="text" name="major_name" id="editMajorName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Course</label>
                        <select name="course_id" id="editMajorCourse" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses_list as $course): ?>
                                <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['course_name']) ?> (<?= htmlspecialchars($course['college_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Major</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Year Level Modal -->
<div class="modal fade" id="addYearLevelModal" tabindex="-1" aria-labelledby="addYearLevelLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addYearLevelForm" method="POST" action="processes/year_level/yearlevel_crud.php?action=update">
                <div class="modal-header">
                    <h5 class="modal-title" id="addYearLevelLabel">Add Year Levels to Course & Major</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label for="courseSelect" class="form-label">Select Course</label>
                        <select class="form-select" id="courseSelect" name="course_id" required>
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses_list as $c):
                                // Check if this course has majors
                                $hasMajors = false;
                                foreach ($majors_new as $m) {
                                    if ($m['course_id'] == $c['id']) {
                                        $hasMajors = true;
                                        break;
                                    }
                                }
                            ?>
                                <option value="<?= $c['id'] ?>" data-has-majors="<?= $hasMajors ? 'true' : 'false' ?>"><?= htmlspecialchars($c['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Section 1: Assign to Course (No Major) -->
                    <div id="courseYearLevelDiv">
                        <div class="mb-3">
                            <label class="form-label">Assign Year Levels to Course (All Students)</label>
                            <select class="form-select" id="courseYearLevels" name="course_year_level_ids[]" multiple>
                                <?php foreach ($year_levels_list as $yl): ?>
                                    <option value="<?= $yl['id'] ?>"><?= $yl['level'] ?> - <?= htmlspecialchars($yl['description']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">These year levels will apply to the course as a whole (no major).</small>
                        </div>
                    </div>

                    <!-- Section 2: Assign to Major -->
                    <div id="majorSectionDiv" style="display:none;">
                        <div class="mb-3">
                            <label for="majorSelect" class="form-label">Select Major</label>
                            <select class="form-select" name="major_id" id="majorSelect">
                                <option value="">-- Select Major --</option>
                                <?php foreach ($majors_new as $m): ?>
                                    <option value="<?= $m['major_id'] ?>" data-course="<?= $m['course_id'] ?>">
                                        <?= htmlspecialchars($m['major_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Assign Year Levels to Major</label>
                            <select class="form-select" id="majorYearLevels" name="major_year_level_ids[]" multiple>
                                <?php foreach ($year_levels_list as $yl): ?>
                                    <option value="<?= $yl['id'] ?>"><?= $yl['level'] ?> - <?= htmlspecialchars($yl['description']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">These year levels apply only to the selected major.</small>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const courseSelect = document.getElementById('courseSelect');
        const majorSectionDiv = document.getElementById('majorSectionDiv');
        const majorSelect = document.getElementById('majorSelect');

        // Function to toggle major section
        function toggleMajorSection() {
            const selectedOption = courseSelect.options[courseSelect.selectedIndex];
            const hasMajors = selectedOption.getAttribute('data-has-majors') === 'true';

            if (hasMajors) {
                majorSectionDiv.style.display = 'block';

                // Optionally, filter the majors dropdown to only show majors for the selected course
                Array.from(majorSelect.options).forEach(option => {
                    if (!option.value) return; // keep the placeholder
                    option.style.display = option.getAttribute('data-course') === selectedOption.value ? 'block' : 'none';
                });
                majorSelect.value = ''; // reset selection
            } else {
                majorSectionDiv.style.display = 'none';
                majorSelect.value = '';
            }
        }

        // Initial check in case modal opens with a pre-selected course
        toggleMajorSection();

        // Listen for changes
        courseSelect.addEventListener('change', toggleMajorSection);
    });
</script>

<!-- Edit Year Level Modal -->
<div class="modal fade" id="editYearLevelModal" tabindex="-1" aria-labelledby="editYearLevelLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editYearLevelForm" method="POST" action="processes/year_level/yearlevel_crud.php?action=update">
                <input type="hidden" name="course_id" id="editCourseIdNew">
                <input type="hidden" name="major_id" id="editMajorIdNew">
                <div class="modal-header">
                    <h5 class="modal-title" id="editYearLevelLabel">Edit Year Levels for Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Course</label>
                        <h5 id="editCourseNameYear" class="fw-bold "></h5>
                    </div>

                    <div class="mb-3" id="editMajorDiv" style="display: none;">
                        <label class="form-label">Major</label>
                        <h5 id="editMajorNameYear" class="fw-bold "></h5>
                    </div>

                    <div class="mb-3">
                        <label for="editYearLevelsSelect" class="form-label">Select Year Levels</label>
                        <select class="form-select" id="editYearLevelsSelect" name="year_level_ids[]" multiple required>
                            <?php foreach ($year_levels_list as $yl): ?>
                                <option value="<?= $yl['id'] ?>"><?= $yl['level'] ?> - <?= htmlspecialchars($yl['description']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple year levels.</small>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<button class="back-to-top" id="backToTop" title="Go to top">
    <i class="mdi mdi-arrow-up"></i>
</button>

<script>
    function setEditMajor(id, name, courseId) {
        document.getElementById('editMajorId').value = id;
        document.getElementById('editMajorName').value = name;
        document.getElementById('editMajorCourse').value = courseId;
    }


    let editMapNew;
    let editMarkerNew;

    // Initialize the map once when DOM is ready
    document.addEventListener("DOMContentLoaded", function() {
        editMapNew = L.map('editCollegeMap').setView([6.912972, 122.063213], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(editMapNew);

        // Add a marker placeholder
        editMarkerNew = L.marker([6.912972, 122.063213]).addTo(editMapNew);

        // Map click updates inputs
        editMapNew.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            document.getElementById('editCollegeLatitude').value = lat.toFixed(6);
            document.getElementById('editCollegeLongitude').value = lng.toFixed(6);
            editMarkerNew.setLatLng([lat, lng]);
        });

        // Campus change logic
        const campusSelect = document.getElementById('editCollegeCampus');
        campusSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const lat = parseFloat(selected.dataset.lat);
            const lng = parseFloat(selected.dataset.lng);

            if (!isNaN(lat) && !isNaN(lng)) {
                document.getElementById('editCollegeLatitude').value = lat;
                document.getElementById('editCollegeLongitude').value = lng;

                const position = [lat, lng];
                editMapNew.setView(position, 17);
                editMarkerNew.setLatLng(position);
            }
        });
    });

    // Function called when Edit button is clicked
    function setEditCollege(id, name, collegeAbbreviation, lat, lng, campusId, coordinateId) {
        document.getElementById('editCollegeId').value = id;
        document.getElementById('editCollegeName').value = name;
        document.getElementById('editCollegeAbbreviation').value = collegeAbbreviation;
        document.getElementById('editCollegeLatitude').value = lat || '';
        document.getElementById('editCollegeLongitude').value = lng || '';
        document.getElementById('editCollegeCampus').value = campusId || '';

        // Set marker & map view
        const position = [
            parseFloat(lat) || 6.912972,
            parseFloat(lng) || 122.063213
        ];
        editMap.setView(position, 17);
        editMarker.setLatLng(position);

        // Fix map size for Bootstrap modal
        setTimeout(() => editMap.invalidateSize(), 200);
        document.getElementById('editCoordinateId').value = coordinateId || '';
    }

    // Handle Edit Year Level button click
    $(document).on('click', '.edit-year-level-btn', function() {
        var courseId = $(this).data('course-id');
        var courseName = $(this).data('course-name');
        var majorId = $(this).data('major-id');
        var majorName = $(this).data('major-name');
        var rawYearLevels = $(this).attr('data-year-level-ids');
        var yearLevelIds = rawYearLevels ? rawYearLevels.split(',') : [];

        // Fill hidden inputs
        $('#editCourseIdNew').val(courseId);
        $('#editMajorIdNew').val(majorId || ''); // use empty string if null/undefined

        // Fill display labels
        $('#editCourseNameYear').text(courseName);
        if (majorId) {
            $('#editMajorDiv').show();
            $('#editMajorNameYear').text(majorName);
            $('#editYearLevelLabel').text('Edit Year Levels for Major');
            $('#editYearLevelsSelect').attr('name', 'major_year_level_ids[]');
        } else {
            $('#editMajorDiv').hide();
            $('#editMajorNameYear').text('');
            $('#editYearLevelLabel').text('Edit Year Levels for Course');
            $('#editYearLevelsSelect').attr('name', 'course_year_level_ids[]');
        }

        // Pre-select the year levels
        $('#editYearLevelsSelect').val(yearLevelIds);
    });

    function confirmDeleteYearLevel(yearId, courseId, majorId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will remove all assigned year levels from this course/major!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, clear them!'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'processes/year_level/yearlevel_crud.php?action=clear';

                const yearInput = document.createElement('input');
                yearInput.type = 'hidden';
                yearInput.name = 'year_id';
                yearInput.value = courseId;
                form.appendChild(yearInput);

                const courseInput = document.createElement('input');
                courseInput.type = 'hidden';
                courseInput.name = 'course_id';
                courseInput.value = courseId;
                form.appendChild(courseInput);

                if (majorId) {
                    const majorInput = document.createElement('input');
                    majorInput.type = 'hidden';
                    majorInput.name = 'major_id';
                    majorInput.value = majorId;
                    form.appendChild(majorInput);
                }

                document.body.appendChild(form);
                form.submit();
            }
        });
    }


    // Generic Delete Function
    function confirmDelete(url, data) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {

                // Create a temporary form and submit it
                const form = document.createElement("form");
                form.method = "POST";
                form.action = url;

                // Add data as hidden inputs
                for (const key in data) {
                    const input = document.createElement("input");
                    input.type = "hidden";
                    input.name = key;
                    input.value = data[key];
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function confirmDeleteCampus(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {

            if (result.isConfirmed) {

                const form = document.createElement("form");
                form.method = "POST";
                form.action = "processes/campus/campus_crud.php?action=delete";

                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "campus_id";
                input.value = id;

                form.appendChild(input);
                document.body.appendChild(form);

                form.submit();
            }

        });
    }

    function confirmDeleteCollege(id) {
        confirmDelete('processes/college/college_crud.php?action=delete', {
            college_id: id
        });
    }

    function confirmDeleteCourse(id) {
        confirmDelete('processes/course/course_crud.php?action=delete', {
            course_id: id
        });
    }

    function confirmDeleteDepartment(id) {
        confirmDelete('processes/department/department_crud.php?action=delete', {
            department_id: id
        });
    }

    function confirmDeleteMajor(id) {
        confirmDelete('processes/major/major_crud.php?action=delete', {
            major_id: id
        });
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const activeTab = '<?= htmlspecialchars($activeTab) ?>';
        if (activeTab) {
            const tabEl = document.querySelector(`#${activeTab}-tab`);
            if (tabEl) {
                const tab = new bootstrap.Tab(tabEl);
                tab.show();
            }
        }
    });
</script>

<script>
    let addCampusMap, addCampusMarker;

    const addCampusModal = document.getElementById('addCampusModal');

    addCampusModal.addEventListener('shown.bs.modal', () => {
        if (!addCampusMap) {
            const defaultPos = [6.913753955430403, 122.06136186726808]; // default coordinates

            addCampusMap = L.map('addCampusMap').setView(defaultPos, 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(addCampusMap);

            addCampusMarker = L.marker(defaultPos, {
                draggable: true
            }).addTo(addCampusMap);

            addCampusMarker.on('dragend', function(e) {
                const pos = e.target.getLatLng();
                document.getElementById('addCampusLatitude').value = pos.lat.toFixed(6);
                document.getElementById('addCampusLongitude').value = pos.lng.toFixed(6);
            });

            // Ensure map renders correctly inside modal
            setTimeout(() => addCampusMap.invalidateSize(), 100);
        }
    });
</script>

</html>

<script>
    $(document).ready(function() {
        $('.datatable').each(function() {
            var table = $(this).DataTable({
                responsive: true, // Make table responsive
                paging: true, // Enable pagination
                searching: true, // Enable search box
                ordering: true, // Enable column sorting
                lengthChange: true, // Allow changing page length
                pageLength: 10, // Default rows per page
                dom: 'Bfrtip', // Show buttons on top

            });
        });
    });

    $(document).ready(function() {
        $('#campusTable').DataTable();
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const status = <?= json_encode($status) ?>;
        const message = <?= json_encode($message) ?>;
        const activeTab = <?= json_encode($activeTab) ?>;

        // Show SweetAlert if there is a message
        if (status && message) {
            Swal.fire({
                icon: status === 'success' ? 'success' : 'error',
                title: status === 'success' ? 'Success' : 'Error',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        }

        // Auto-open tab if ?tab=courses
        if (activeTab) {
            const tabEl = document.querySelector(`#${activeTab}-tab`);
            if (tabEl) {
                const tab = new bootstrap.Tab(tabEl);
                tab.show();
            }
        }
    });
</script>


<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- DataTables Buttons JS -->
<!-- <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script> -->

<?php
include('includes/alerts.php');
?>