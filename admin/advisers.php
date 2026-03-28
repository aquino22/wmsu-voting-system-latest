<?php
ini_set('max_execution_time', 3600);
session_start();
include('includes/conn.php');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = "NON_ADMIN";
    header("Location: ../index.php");
    exit();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$voting_periods_stmt = $pdo->prepare("SELECT * FROM voting_periods");
$voting_periods_stmt->execute();
$vp = $voting_periods_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Dynamic Data for Dropdowns
$colleges_list    = $pdo->query("SELECT * FROM colleges ORDER BY college_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$departments_list = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$courses_list     = $pdo->query("SELECT * FROM courses ORDER BY course_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$majors_list      = $pdo->query("SELECT * FROM majors ORDER BY major_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$campuses_list    = $pdo->query("SELECT * FROM campuses ORDER BY campus_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$year_levels_list = $pdo->query("SELECT * FROM year_levels ORDER BY level ASC")->fetchAll(PDO::FETCH_ASSOC);

// Separate Campuses
$main_campuses     = array_filter($campuses_list, fn($c) => empty($c['parent_id']));
$external_campuses = array_filter($campuses_list, fn($c) => !empty($c['parent_id']));

// Prepare Data for JavaScript
$js_colleges    = json_encode($colleges_list);
$js_departments = json_encode($departments_list);
$js_courses     = json_encode($courses_list);
$js_majors      = json_encode($majors_list);
$js_campuses    = json_encode($campuses_list);

// Year level labels
$yearLevelLabels = [
    1 => '1st Year',
    2 => '2nd Year',
    3 => '3rd Year',
    4 => '4th Year',
    5 => '5th Year',
];

// Map department_id → course_id
$departmentToCourse = [];
$stmt = $pdo->query("
    SELECT d.department_id, c.id AS course_id
    FROM departments d
    JOIN courses c ON d.department_name = c.course_code
");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $departmentToCourse[$row['department_id']] = $row['course_id'];
}

// Map course/major → year levels
$courseYearLevels = [];
$majorYearLevels  = [];
$stmt = $pdo->query("SELECT id, course_id, major_id, year_level FROM actual_year_levels");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data = [
        'id'          => $row['id'],
        'level'       => $row['year_level'],
        'description' => $yearLevelLabels[$row['year_level']] ?? "Year {$row['year_level']}"
    ];
    if (is_null($row['major_id'])) {
        $courseYearLevels[$row['course_id']][] = $data;
    } else {
        $majorYearLevels[$row['major_id']][] = $data;
    }
}

$jsDepartmentToCourse = json_encode($departmentToCourse);
$jsCourseYearLevels   = json_encode($courseYearLevels);
$jsMajorYearLevels    = json_encode($majorYearLevels);

// Emails
$stmt = $pdo->prepare("SELECT id, email FROM email ORDER BY email ASC");
$stmt->execute();
$availableEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id, email FROM email WHERE status != 'taken' ORDER BY email ASC");
$stmt->execute();
$chosenAndAvailableEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);

// School year helper
$currentYear  = date('Y');
$currentMonth = date('n');
$startYear    = ($currentMonth >= 6) ? $currentYear : $currentYear - 1;
$schoolYear   = $startYear . '-' . ($startYear + 1);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU i-Elect Admin | Advisers</title>

    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="js/select.dataTables.min.css">
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <link rel="shortcut icon" href="images/favicon.png" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

    <style>
        .custom-padding {
            padding: 20px !important;
        }
    </style>
</head>

<body>
    <div class="container-scroller">

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
            $admin_full_name    = $admin['full_name'];
            $admin_phone_number = $admin['phone_number'];
            $admin_email        = $admin['email'];
        }
        ?>

        <!-- NAVBAR -->
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
                        <small style="font-size: 16px;"><b>WMSU i-Elect</b></small>
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
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                        <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <img class="img-xs rounded-circle logo" src="images/wmsu-logo.png" style="background-color: white;" alt="Profile image">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                            <div class="dropdown-header text-center">
                                <img class="img-xs rounded-circle" src="images/wmsu-logo.png" alt="Profile image">
                            </div>
                            <p class="mb-1 mt-3 font-weight-semibold dropdown-item"><b>WMSU ADMIN</b></p>
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
        <!-- END NAVBAR -->

        <div class="container-fluid page-body-wrapper">
            <?php include('includes/sidebar.php') ?>

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
                                                Advisers
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                                        <div class="row mt-3">
                                            <div class="card px-5 pt-5">
                                                <div class="d-flex align-items-center">
                                                    <h3 class="mb-0"><b>Advisers</b></h3>
                                                    <div class="ms-auto">
                                                        <button class="btn btn-primary text-white"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#addAdviserModal">
                                                            <i class="bi bi-person-add"></i> Add Adviser
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table id="adviserTable" class="table table-striped table-bordered nowrap" style="width:100%">
                                                            <thead>
                                                                <tr>
                                                                    <th>ID</th>
                                                                    <th>Full Name</th>
                                                                    <th>Email</th>
                                                                    <th>Password</th>
                                                                    <th>Google SMTP Associated</th>
                                                                    <th>College</th>
                                                                    <th>Department</th>
                                                                    <th>Major</th>
                                                                    <th>Year Level</th>
                                                                    <th>WMSU Campus Assigned</th>
                                                                    <th>WMSU ESU Campus Location</th>
                                                                    <th>School Year</th>
                                                                    <th>Semester</th>
                                                                    <th>Manage</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody></tbody>
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
    </div><!-- end container-scroller -->


    <!-- ================================================
     ADD ADVISER MODAL
================================================ -->
    <div class="modal fade" id="addAdviserModal" tabindex="-1" aria-labelledby="addAdviserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form action="processes/advisers/add.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAdviserModalLabel">Add an Adviser</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">

                            <!-- Row 1: Names + Email -->
                            <div class="col-md-2">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="firstName" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" name="middleName">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="lastName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="user_email" required>
                            </div>

                            <!-- Row 2: Password + SMTP -->
                            <div class="col-md-6">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="add_passwordInput" name="password" required>
                                    <button class="btn btn-outline-secondary btn-sm" type="button" id="add_togglePassword">
                                        <i class="bi bi-eye fs-6" id="add_togglePasswordIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Google SMTP Email <small>(Multiple selectable) <span class="text-danger">*</span></small></label>
                                <select class="form-select" id="add_emailSelect" name="email_ids[]" multiple required>
                                    <option value="" disabled>-- Choose an email --</option>
                                    <?php foreach ($chosenAndAvailableEmails as $email): ?>
                                        <option value="<?= htmlspecialchars($email['id']) ?>">
                                            <?= htmlspecialchars($email['email']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Row 3: College + Department + Major + Year Level -->
                            <div class="col-md-3">
                                <label class="form-label">College <span class="text-danger">*</span></label>
                                <select class="form-select" id="add_college" name="college_id" required>
                                    <option value="" disabled selected>Select College</option>
                                    <?php foreach ($colleges_list as $col): ?>
                                        <option value="<?= htmlspecialchars($col['college_id']) ?>">
                                            <?= htmlspecialchars($col['college_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <select class="form-select" id="add_department" name="department_id" required>
                                    <option value="" disabled selected>Select Department</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="add_majorWrapper" style="display:none;">
                                <label class="form-label">Major</label>
                                <select class="form-select" id="add_major" name="major_id">
                                    <option value="">-- No Major --</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Year Level <span class="text-danger">*</span></label>
                                <select class="form-select" id="add_year_level" name="year_level" required>
                                    <option value="" disabled selected>Select Year Level</option>
                                </select>
                            </div>

                            <!-- Row 4: Semester + School Year -->
                            <div class="col-md-6">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select class="form-select" name="semester" required>
                                    <option value="" disabled selected>Choose Semester</option>
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester">2nd Semester</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">School Year <span class="text-danger">*</span></label>
                                <select class="form-select" name="school_year" required>
                                    <option value="<?= $schoolYear ?>" selected><?= $schoolYear ?></option>
                                </select>
                            </div>

                            <!-- Row 5: Campus -->
                            <div class="col-md-6">
                                <label class="form-label">WMSU Campus <span class="text-danger">*</span></label>
                                <select class="form-select" id="add_wmsu_campus" name="wmsu_campus_id" required>
                                    <option value="" disabled selected>Select WMSU Campus</option>
                                    <?php foreach ($main_campuses as $mc): ?>
                                        <option value="<?= htmlspecialchars($mc['campus_id']) ?>">
                                            <?= htmlspecialchars($mc['campus_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6" id="add_external_campus_container" style="display:none;">
                                <label class="form-label">WMSU ESU Campus Location</label>
                                <select class="form-select" id="add_external_campus" name="external_campus_id">
                                    <option value="0">None</option>
                                    <?php foreach ($external_campuses as $ec): ?>
                                        <option value="<?= htmlspecialchars($ec['campus_id']) ?>">
                                            <?= htmlspecialchars($ec['campus_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Adviser</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- ================================================
     EDIT ADVISER MODAL
================================================ -->
    <div class="modal fade" id="editAdviserModal" tabindex="-1" aria-labelledby="editAdviserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="editAdviserForm" method="POST" action="processes/advisers/edit.php">
                    <input type="hidden" name="adviser_id" id="edit_adviser_id">

                    <div class="modal-header">
                        <h5 class="modal-title" id="editAdviserModalLabel">Edit Adviser</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">

                            <!-- Row 1: Names + Email -->
                            <div class="col-md-2">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_firstName" name="firstName" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="edit_middleName" name="middleName">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_lastName" name="lastName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="edit_emailInput" name="user_email" required>
                            </div>

                            <!-- Row 2: Password + SMTP -->
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="edit_passwordInput" name="password">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" id="edit_togglePassword">
                                        <i class="bi bi-eye fs-6" id="edit_togglePasswordIcon"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Leave blank to keep current password</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Google SMTP Email <small>(Multiple selectable) <span class="text-danger">*</span></small></label>
                                <select class="form-select" id="edit_emailSelect" name="email_ids[]" multiple required>
                                    <option value="" disabled>-- Choose an email --</option>
                                    <?php foreach ($availableEmails as $email): ?>
                                        <option value="<?= htmlspecialchars($email['id']) ?>">
                                            <?= htmlspecialchars($email['email']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Row 3: College + Department + Major + Year Level -->
                            <div class="col-md-3">
                                <label class="form-label">College <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_college" name="college_id" required>
                                    <option value="" disabled selected>Select College</option>
                                    <?php foreach ($colleges_list as $col): ?>
                                        <option value="<?= htmlspecialchars($col['college_id']) ?>">
                                            <?= htmlspecialchars($col['college_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_department" name="department_id" required>
                                    <option value="" disabled selected>Select Department</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="edit_majorWrapper" style="display:none;">
                                <label class="form-label">Major</label>
                                <select class="form-select" id="edit_major" name="major_id">
                                    <option value="">-- No Major --</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Year Level <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_year_level" name="year_level" required>
                                    <option value="" disabled selected>Select Year Level</option>
                                </select>
                            </div>

                            <!-- Row 4: Semester + School Year -->
                            <div class="col-md-6">
                                <label class="form-label">Semester <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_semester" name="semester" required>
                                    <option value="" disabled selected>Choose Semester</option>
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester">2nd Semester</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">School Year <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_school_year" name="school_year" required>
                                    <option value="<?= $schoolYear ?>" selected><?= $schoolYear ?></option>
                                </select>
                            </div>

                            <!-- Row 5: Campus -->
                            <div class="col-md-6">
                                <label class="form-label">WMSU Campus <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_wmsu_campus" name="wmsu_campus_id" required>
                                    <option value="" disabled selected>Select WMSU Campus</option>
                                    <?php foreach ($main_campuses as $mc): ?>
                                        <option value="<?= htmlspecialchars($mc['campus_id']) ?>">
                                            <?= htmlspecialchars($mc['campus_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6" id="edit_external_campus_container" style="display:none;">
                                <label class="form-label">WMSU ESU Campus Location</label>
                                <select class="form-select" id="edit_external_campus" name="external_campus_id">
                                    <option value="0">None</option>
                                    <?php foreach ($external_campuses as $ec): ?>
                                        <option value="<?= htmlspecialchars($ec['campus_id']) ?>">
                                            <?= htmlspecialchars($ec['campus_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Adviser</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- ================================================
     SCRIPTS — load order matters:
     1. jQuery  2. vendor bundle  3. DataTables  4. SweetAlert2  5. app scripts
================================================ -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ─── PHP → JS data ───────────────────────────────────────────────────────────
        const departmentsData = <?= $js_departments ?>;
        const coursesData = <?= $js_courses ?>;
        const majorsData = <?= $js_majors ?>;
        const courseYearLevels = <?= $jsCourseYearLevels ?>;
        const majorYearLevels = <?= $jsMajorYearLevels ?>;
        const departmentToCourse = <?= $jsDepartmentToCourse ?>;

        // ─── Generic cascade helpers ─────────────────────────────────────────────────

        /**
         * Populate a year-level <select> from a levels array.
         * @param {string} selectId  - The element ID of the year-level dropdown
         * @param {Array}  levels    - Array of { id, description }
         * @param {*}      selectedVal - Value to pre-select (optional)
         */
        function fillYearLevels(selectId, levels, selectedVal = null) {
            const $sel = $('#' + selectId);
            $sel.html('<option value="" disabled selected>Select Year Level</option>');
            (levels || []).forEach(l => {
                $sel.append(`<option value="${l.id}" ${l.id == selectedVal ? 'selected' : ''}>${l.description}</option>`);
            });
        }

        /**
         * Populate a department <select> filtered by college.
         */
        function fillDepartments(selectId, collegeId, selectedVal = null) {
            const $sel = $('#' + selectId);
            $sel.html('<option value="" disabled selected>Select Department</option>');
            departmentsData
                .filter(d => d.college_id == collegeId)
                .forEach(d => {
                    $sel.append(`<option value="${d.department_id}" ${d.department_id == selectedVal ? 'selected' : ''}>${d.department_name}</option>`);
                });
        }

        /**
         * Populate a major <select> and year-level dropdown based on a department.
         * @param {string} prefix  - 'add' or 'edit'
         * @param {*}      deptId
         * @param {*}      selectedMajorId
         * @param {*}      selectedYearLevelId
         */
        function handleDepartmentChange(prefix, deptId, selectedMajorId = null, selectedYearLevelId = null) {
            const $major = $('#' + prefix + '_major');
            const $majorWrapper = $('#' + prefix + '_majorWrapper');

            $major.html('<option value="">-- No Major --</option>');
            fillYearLevels(prefix + '_year_level', []);

            if (!deptId) {
                $majorWrapper.hide();
                return;
            }

            const dept = departmentsData.find(d => d.department_id == deptId);
            const matchCourse = dept ? coursesData.find(c => c.course_code === dept.department_name) : null;
            const courseId = matchCourse ? matchCourse.id : null;

            // Load course-based year levels first
            const courseLevels = courseId ? (courseYearLevels[courseId] || []) : [];
            fillYearLevels(prefix + '_year_level', courseLevels, selectedYearLevelId);

            // Load majors
            const filteredMajors = majorsData.filter(m => m.course_id == courseId);
            if (filteredMajors.length > 0) {
                filteredMajors.forEach(m => {
                    const mId = m.major_id || m.id;
                    $major.append(`<option value="${mId}" ${mId == selectedMajorId ? 'selected' : ''}>${m.major_name}</option>`);
                });
                $majorWrapper.show();

                // If a major was pre-selected, override year levels with major-specific ones
                if (selectedMajorId && majorYearLevels[selectedMajorId]) {
                    fillYearLevels(prefix + '_year_level', majorYearLevels[selectedMajorId], selectedYearLevelId);
                }
            } else {
                $majorWrapper.hide();
            }
        }

        // ─── ADD modal cascades ───────────────────────────────────────────────────────

        $('#add_college').on('change', function() {
            fillDepartments('add_department', $(this).val());
            $('#add_major').html('<option value="">-- No Major --</option>');
            $('#add_majorWrapper').hide();
            fillYearLevels('add_year_level', []);
        });

        $('#add_department').on('change', function() {
            handleDepartmentChange('add', $(this).val());
        });

        $('#add_major').on('change', function() {
            const majorId = $(this).val();
            if (majorId && majorYearLevels[majorId]) {
                fillYearLevels('add_year_level', majorYearLevels[majorId]);
            } else {
                // Revert to course-level year levels
                $('#add_department').trigger('change');
            }
        });

        $('#add_wmsu_campus').on('change', function() {
            const isEsu = $(this).find('option:selected').text().trim() === 'WMSU ESU';
            $('#add_external_campus_container').toggle(isEsu);
            $('#add_external_campus').prop('required', isEsu);
        });

        // ─── EDIT modal cascades ──────────────────────────────────────────────────────

        $('#edit_college').on('change', function() {
            fillDepartments('edit_department', $(this).val());
            $('#edit_major').html('<option value="">-- No Major --</option>');
            $('#edit_majorWrapper').hide();
            fillYearLevels('edit_year_level', []);
        });

        $('#edit_department').on('change', function() {
            handleDepartmentChange('edit', $(this).val());
        });

        $('#edit_major').on('change', function() {
            const majorId = $(this).val();
            if (majorId && majorYearLevels[majorId]) {
                fillYearLevels('edit_year_level', majorYearLevels[majorId]);
            } else {
                $('#edit_department').trigger('change');
            }
        });

        $('#edit_wmsu_campus').on('change', function() {
            const isEsu = $(this).find('option:selected').text().trim() === 'WMSU ESU';
            $('#edit_external_campus_container').toggle(isEsu);
            $('#edit_external_campus').prop('required', isEsu);
        });

        // ─── Password toggles ─────────────────────────────────────────────────────────

        $('#add_togglePassword').on('click', function() {
            const $input = $('#add_passwordInput');
            const $icon = $('#add_togglePasswordIcon');
            const show = $input.attr('type') === 'password';
            $input.attr('type', show ? 'text' : 'password');
            $icon.toggleClass('bi-eye', !show).toggleClass('bi-eye-slash', show);
        });

        $('#edit_togglePassword').on('click', function() {
            const $input = $('#edit_passwordInput');
            const $icon = $('#edit_togglePasswordIcon');
            const show = $input.attr('type') === 'password';
            $input.attr('type', show ? 'text' : 'password');
            $icon.toggleClass('bi-eye', !show).toggleClass('bi-eye-slash', show);
        });

        // ─── Navbar: Show More / Back to Top ─────────────────────────────────────────

        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggleElections');
            const moreDiv = document.getElementById('moreElections');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const open = moreDiv.style.display === 'none';
                    moreDiv.style.display = open ? 'block' : 'none';
                    toggleBtn.textContent = open ? 'Show Less' : 'Show More';
                });
            }

            const backToTopButton = document.getElementById('backToTop');
            if (backToTopButton) {
                window.addEventListener('scroll', function() {
                    backToTopButton.classList.toggle('show', window.pageYOffset > 200);
                });
                backToTopButton.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
        });

        // ─── DataTable + all button handlers ─────────────────────────────────────────

        $(document).ready(function() {

            const table = $('#adviserTable').DataTable({
                responsive: true,
                ordering: true,
                order: [
                    [0, 'desc']
                ],
                ajax: 'fetch_advisers.php',
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'full_name'
                    },
                    {
                        data: 'email'
                    },
                    {
                        data: 'password',
                        render: () => '••••••••'
                    },
                    {
                        data: 'smtp_email',
                        render: d => d || 'N/A'
                    },
                    {
                        data: 'college'
                    },
                    {
                        data: 'department'
                    },
                    {
                        data: 'major',
                        render: (d) => (!d || d.trim() === '') ? 'N/A' : d
                    },
                    {
                        data: 'year_level_display',
                        render: (d) => {
                            const labels = {
                                1: '1st Year',
                                2: '2nd Year',
                                3: '3rd Year',
                                4: '4th Year',
                                5: '5th Year'
                            };
                            return labels[d] || d;
                        }
                    },
                    {
                        data: 'wmsu_campus',
                        render: d => d || 'N/A'
                    },
                    {
                        data: 'external_campus',
                        render: d => d || 'N/A'
                    },
                    {
                        data: 'school_year'
                    },
                    {
                        data: 'semester'
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            let btns = `
                        <button class="btn btn-sm btn-primary viewBtn text-white" data-id="${row.id}">
                            <i class="bi bi-eye"></i> View
                        </button>
                        <button class="btn btn-sm btn-warning editBtn text-white ms-1" data-id="${row.id}">
                            <i class="bi bi-pen"></i> Edit
                        </button>`;

                            if (row.is_active == 1) {
                                btns += `
                        <button class="btn btn-sm btn-danger deactivateBtn text-white ms-1" data-id="${row.id}">
                            <i class="bi bi-x-circle"></i> Deactivate
                        </button>`;
                            } else {
                                btns += `
                        <button class="btn btn-sm btn-success activateBtn text-white ms-1" data-id="${row.id}">
                            <i class="bi bi-check-circle"></i> Activate
                        </button>
                        <button class="btn btn-sm btn-danger deleteBtn text-white ms-1" data-id="${row.id}">
                            <i class="bi bi-trash"></i> Delete
                        </button>`;
                            }
                            return btns;
                        }
                    }
                ]
            });

            // ── View ──────────────────────────────────────────────────────────────────
            $('#adviserTable').on('click', '.viewBtn', function() {
                const id = $(this).data('id');
                if (id) window.location.href = `view_adviser.php?id=${id}`;
            });

            // ── Edit — auto-populate the modal ───────────────────────────────────────
            $('#adviserTable').on('click', '.editBtn', function() {
                const row = table.row($(this).closest('tr')).data();

                // ── Helper: show hint under a label ──────────────────────────────
                function setHint(fieldId, text) {
                    const $field = $('#' + fieldId);
                    $field.siblings('.edit-hint').remove(); // remove old hint if any
                    if (text && text !== 'N/A' && text !== '') {
                        $field.closest('.col-md, .col-md-3, .col-md-6')
                            .find('label')
                            .first()
                            .after(`<small class="edit-hint text-muted d-block mb-1">
                              <i class="bi bi-arrow-right-short"></i> Current: <b>${text}</b>
                          </small>`);
                    }
                }

                // ── Basic fields ──────────────────────────────────────────────────
                $('#edit_adviser_id').val(row.id);
                $('#edit_firstName').val(row.first_name);
                $('#edit_middleName').val(row.middle_name);
                $('#edit_lastName').val(row.last_name);
                $('#edit_emailInput').val(row.email);
                $('#edit_semester').val(row.semester);

                // ── School year ───────────────────────────────────────────────────
                const $sy = $('#edit_school_year');
                if ($sy.find(`option[value="${row.school_year}"]`).length === 0) {
                    $sy.append(`<option value="${row.school_year}">${row.school_year}</option>`);
                }
                $sy.val(row.school_year);
                setHint('edit_school_year', row.school_year);

                // ── SMTP emails ───────────────────────────────────────────────────
                $('#edit_emailSelect').val(row.selected_email_ids || []);

                // ── College ───────────────────────────────────────────────────────
                $('#edit_college').val(row.college_id);
                setHint('edit_college', row.college);

                // ── College ───────────────────────────────────────────────────────
                $('#edit_semester').val(row.semester);
                setHint('edit_semester', row.semester);

                // ── Department ───────────────────────────────────────────────────
                fillDepartments('edit_department', row.college_id);
                $('#edit_department').val(row.department_id);
                setHint('edit_department', row.department);

                // ── Major + Year Level ────────────────────────────────────────────
                const dept = departmentsData.find(d => d.department_id == row.department_id);
                const matchCourse = dept ? coursesData.find(c => c.course_code === dept.department_name) : null;
                const courseId = matchCourse ? matchCourse.id : null;

                const $major = $('#edit_major');
                const $majorWrapper = $('#edit_majorWrapper');
                $major.html('<option value="">-- No Major --</option>');

                const filteredMajors = majorsData.filter(m => m.course_id == courseId);
                if (filteredMajors.length > 0) {
                    filteredMajors.forEach(m => {
                        const mId = m.major_id || m.id;
                        $major.append(`<option value="${mId}">${m.major_name}</option>`);
                    });
                    $majorWrapper.show();
                    $major.val(row.major_id || '');
                    setHint('edit_major', row.major && row.major.trim() !== '' ? row.major : null);
                } else {
                    $majorWrapper.hide();
                }

                const useMajorLevels = row.major_id && majorYearLevels[row.major_id];
                const levels = useMajorLevels ?
                    majorYearLevels[row.major_id] :
                    (courseId ? (courseYearLevels[courseId] || []) : []);

                fillYearLevels('edit_year_level', levels, row.year_level_id);
                setHint('edit_year_level', row.year_level_display ?
                    (['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'][row.year_level_display - 1] || row.year_level_display) :
                    null);

                // ── Campus ────────────────────────────────────────────────────────
                $('#edit_wmsu_campus').val(row.wmsu_campus_id).trigger('change');
                setHint('edit_wmsu_campus', row.wmsu_campus);

                const selectedCampusText = $('#edit_wmsu_campus option:selected').text().trim();
                if (selectedCampusText === 'WMSU ESU') {
                    $('#edit_external_campus_container').show();
                    $('#edit_external_campus').prop('required', true);
                    $('#edit_external_campus').val(row.external_campus_id || 0);
                } else {
                    $('#edit_external_campus_container').hide();
                    $('#edit_external_campus').prop('required', false);
                }

                // Show ESU hint regardless of container visibility so it's
                // always injected into the DOM — it becomes visible when the
                // container is shown
                const esuValue = (row.external_campus && row.external_campus !== 'N/A' && row.external_campus !== '') ?
                    row.external_campus :
                    null;
                setHint('edit_external_campus', esuValue);

                // ── Open modal ────────────────────────────────────────────────────
                $('#editAdviserModal').modal('show');
            });

            // ── Delete ────────────────────────────────────────────────────────────────
            $(document).on('click', '.deleteBtn', function() {
                const adviserId = $(this).data('id');
                Swal.fire({
                    title: 'Delete Adviser?',
                    text: 'This will permanently remove the adviser and release their email accounts.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    $.ajax({
                        url: 'processes/advisers/delete.php',
                        type: 'POST',
                        data: {
                            id: adviserId
                        },
                        dataType: 'json',
                        success: function(res) {
                            if (res.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: res.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: res.message
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Delete Failed',
                                text: xhr.responseJSON?.message || 'Server error occurred.'
                            });
                        }
                    });
                });
            });

            // ── Activate / Deactivate ─────────────────────────────────────────────────
            function confirmAction(title, text, confirmBtn, callback) {
                Swal.fire({
                    title,
                    text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: confirmBtn
                }).then(result => {
                    if (result.isConfirmed) callback();
                });
            }

            function handleResponse(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message || 'Operation completed.',
                                timer: 1500,
                                showConfirmButton: false
                            })
                            .then(() => table.ajax.reload(null, false));
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Something went wrong.'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Invalid response from server.'
                    });
                }
            }

            $('#adviserTable tbody').on('click', '.activateBtn', function() {
                const id = $(this).data('id');
                confirmAction('Activate Adviser?', 'This will allow the adviser to login again.', 'Yes, activate!', () => {
                    $.post('activate_adviser.php', {
                        id
                    }, handleResponse);
                });
            });

            $('#adviserTable tbody').on('click', '.deactivateBtn', function() {
                const id = $(this).data('id');
                confirmAction('Deactivate Adviser?', 'The adviser will no longer be able to log in.', 'Yes, deactivate!', () => {
                    $.post('deactivate_adviser.php', {
                        id
                    }, handleResponse);
                });
            });

        });
    </script>

    <?php if (isset($_SESSION['STATUS'])): ?>
        <script>
            <?php if ($_SESSION['STATUS'] === 'ADVISER_ADDED_SUCCESSFULLY'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Adviser added successfully.',
                    confirmButtonColor: '#3085d6'
                });
            <?php elseif ($_SESSION['STATUS'] === 'ADVISER_ADD_FAILED'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to add adviser. Please try again.',
                    confirmButtonColor: '#d33'
                });
            <?php elseif ($_SESSION['STATUS'] === 'USER_EMAIL_ALREADY_EXISTS'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to add adviser. The email already exists.',
                    confirmButtonColor: '#d33'
                });
            <?php elseif ($_SESSION['STATUS'] === 'ADVISER_ALREADY_EXISTS_FOR_COMBINATION'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to add adviser. The adviser already exists.',
                    confirmButtonColor: '#d33'
                });
            <?php elseif ($_SESSION['STATUS'] === 'ADVISER_UPDATED_SUCCESSFULLY'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: 'Adviser information updated successfully.',
                    confirmButtonColor: '#3085d6'
                });
            <?php elseif ($_SESSION['STATUS'] === 'ADVISER_UPDATE_FAILED'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: 'There was an error updating the adviser.',
                    confirmButtonColor: '#d33'
                });
            <?php elseif ($_SESSION['STATUS'] === 'ADVISER_DELETED_SUCCESSFULLY'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: 'Adviser deleted successfully.',
                    confirmButtonColor: '#3085d6'
                });
            <?php elseif ($_SESSION['STATUS'] === 'ADVISER_DELETE_FAILED'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Delete Failed',
                    text: 'There was an error deleting the adviser.',
                    confirmButtonColor: '#d33'
                });
            <?php endif; ?>
        </script>
    <?php unset($_SESSION['STATUS']);
    endif; ?>

    <button class="back-to-top" id="backToTop" title="Go to top">
        <i class="mdi mdi-arrow-up"></i>
    </button>

</body>

</html>