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

$adviser_id = $_GET['id'] ?? null;
if (!$adviser_id) {
    $_SESSION['STATUS'] = "ADVISER_NOT_FOUND";
    header("Location: advisers.php");
    exit();
}

// ── 1. Adviser details ──────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT
        a.full_name,
        a.email,
        a.year_level AS year,
        a.school_year,
        a.semester,
        c.college_name,
        d.department_name,
        m.major_name,
        w.campus_name  AS wmsu_campus,
        ec.campus_name AS external_campus
    FROM advisers a
    LEFT JOIN colleges    c  ON a.college_id          = c.college_id
    LEFT JOIN departments d  ON a.department_id        = d.department_id
    LEFT JOIN majors      m  ON a.major_id             = m.major_id
    LEFT JOIN campuses    w  ON a.wmsu_campus_id       = w.campus_id
    LEFT JOIN campuses    ec ON a.external_campus_id   = ec.campus_id
    WHERE a.id = :adviser_id
");
$stmt->execute([':adviser_id' => $adviser_id]);
$adviser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adviser) {
    echo "Adviser not found";
    exit;
}

// ── 2. SMTP emails for this adviser ────────────────────────────────────────
$emailStmt = $pdo->prepare("
    SELECT email, app_password, capacity, status
    FROM email
    WHERE adviser_id = :adviser_id
");
$emailStmt->execute([':adviser_id' => $adviser_id]);
$emails = $emailStmt->fetchAll(PDO::FETCH_ASSOC);

// ── 3. Voters handled by this adviser ──────────────────────────────────────
// Match voters to this adviser by:
//   college, department, wmsu_campus (or external_campus), year_level, and
//   academic_year (semester + school_year label matched via academic_years).
//
// voters.year_level is FK → actual_year_levels.id
// actual_year_levels.year_level holds the numeric level (1–5)
// advisers.year_level stores the numeric level directly.
//
// academic_years.year_label  e.g. '2025-2026'  matches advisers.school_year
// academic_years.semester    e.g. '1st Semester' matches advisers.semester
$voterStmt = $pdo->prepare("
    SELECT
        v.student_id,
        v.first_name,
        v.middle_name,
        v.last_name,
        v.email,
        v.status,
        cr.course_name,
        ayl.year_level                      AS year_level_num,
        yl.description                      AS year_level_label
    FROM voters v
    JOIN actual_year_levels ayl ON ayl.id = v.year_level
    LEFT JOIN year_levels yl    ON yl.level = ayl.year_level
    LEFT JOIN courses cr        ON cr.id = v.course
    JOIN academic_years ay      ON ay.id = v.academic_year_id
    JOIN advisers a             ON a.id = :adviser_id
    WHERE
        v.college     = a.college_id
        AND v.department = a.department_id
        AND v.year_level = a.year_level
        AND (
            v.wmsu_campus = a.wmsu_campus_id
            OR (a.external_campus_id IS NOT NULL AND v.external_campus = a.external_campus_id)
        )
    ORDER BY v.last_name ASC, v.first_name ASC
");
$voterStmt->execute([':adviser_id' => $adviser_id]);
$voters = $voterStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch the raw pairs (e.g., [1 => 1, 2 => 2])
$yearLevelMap = $pdo->query("SELECT id, year_level FROM actual_year_levels")
    ->fetchAll(PDO::FETCH_KEY_PAIR);

/**
 * Helper function to turn 1 into 1st, 2 into 2nd, etc.
 */
function formatYearLevel($number)
{
    if (!is_numeric($number)) return $number;

    $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
    if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
        return $number . 'th Year';
    } else {
        return $number . $ends[$number % 10] . ' Year';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU i-Elect Admin | View Adviser</title>
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <link rel="shortcut icon" href="images/favicon.png" />
</head>

<body>
    <div class="container-scroller">

        <?php
        // Navbar admin info
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT a.full_name, a.phone_number, u.email
                           FROM admin a JOIN users u ON a.user_id = u.id
                           WHERE u.id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        $admin_full_name   = $admin['full_name']    ?? '';
        $admin_email       = $admin['email']         ?? '';
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
                        <small style="font-size:16px;"><b>WMSU i-Elect</b></small>
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
                            SELECT e.id, e.election_name, a.year_label, a.semester
                            FROM elections e
                            JOIN academic_years a ON e.academic_year_id = a.id
                            WHERE e.status = 'Ongoing'
                            ORDER BY a.year_label DESC, a.semester DESC
                        ");
                            $stmt->execute();
                            $ongoingElections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if ($ongoingElections) {
                                $first = array_shift($ongoingElections);
                                echo "<br><b>School Year:</b> " . htmlspecialchars($first['year_label']) . " | ";
                                echo "<b>Semester:</b> " . htmlspecialchars($first['semester']) . " | ";
                                echo "<b>Election:</b> " . htmlspecialchars($first['election_name']) . "<br>";
                                if ($ongoingElections) {
                                    echo '<div id="moreElections" style="display:none;margin-top:5px;">';
                                    foreach ($ongoingElections as $el) {
                                        echo "<b>School Year:</b> " . htmlspecialchars($el['year_label']) . " | ";
                                        echo "<b>Semester:</b> " . htmlspecialchars($el['semester']) . " | ";
                                        echo "<b>Election:</b> " . htmlspecialchars($el['election_name']) . "<br>";
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
                            <img class="img-xs rounded-circle logo" src="images/wmsu-logo.png" style="background-color:white;" alt="Profile image">
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
                                                View Adviser
                                            </a>
                                        </li>
                                    </ul>
                                    <div>
                                        <a href="advisers.php" class="btn btn-light bg-white btn-sm">Back to Advisers</a>
                                    </div>
                                </div>

                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel">

                                        <!-- ── Adviser Information ── -->
                                        <div class="card mt-3">
                                            <div class="card-body">
                                                <h4 class="card-title">Adviser Information</h4>
                                                <p><strong>Full Name:</strong> <?= htmlspecialchars($adviser['full_name']) ?></p>
                                                <p><strong>Email:</strong> <?= htmlspecialchars($adviser['email']) ?></p>
                                                <p><strong>College:</strong> <?= htmlspecialchars($adviser['college_name']) ?></p>
                                                <p><strong>Department:</strong> <?= htmlspecialchars($adviser['department_name']) ?></p>
                                                <p><strong>Major:</strong> <?= htmlspecialchars($adviser['major_name'] ?? 'N/A') ?></p>
                                                <?php
                                                // 1. Get the raw ID from the adviser record (e.g., 1)
                                                $rawYearId = $adviser['year'];

                                                // 2. Get the value from the map (e.g., "1")
                                                $yearValue = $yearLevelMap[$rawYearId] ?? null;

                                                // 3. Format it (e.g., "1st Year")
                                                $displayYear = $yearValue ? formatYearLevel($yearValue) : 'N/A';
                                                ?>



                                                <p>
                                                    <strong>Year Level Handled:</strong>
                                                    <?= htmlspecialchars($displayYear) ?>
                                                </p>
                                                <p><strong>School Year:</strong> <?= htmlspecialchars($adviser['school_year']) ?></p>
                                                <p><strong>Semester:</strong> <?= htmlspecialchars($adviser['semester']) ?></p>
                                                <p><strong>Campus:</strong> <?= htmlspecialchars($adviser['wmsu_campus']) ?></p>
                                                <p><strong>External Campus:</strong> <?= htmlspecialchars($adviser['external_campus'] ?? 'N/A') ?></p>
                                                <p><strong>SMTP Emails:</strong>
                                                    <?php if (!empty($emails)): ?>
                                                <ul class="mb-0">
                                                    <?php foreach ($emails as $em): ?>
                                                        <li>
                                                            <?= htmlspecialchars($em['email']) ?>
                                                            <span class="badge bg-<?= $em['status'] === 'active' ? 'success' : 'warning' ?>">
                                                                <?= htmlspecialchars(ucfirst($em['status'])) ?>
                                                            </span>
                                                            &nbsp;Capacity: <?= htmlspecialchars($em['capacity']) ?> / 500
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                            </p>
                                            </div>
                                        </div>

                                        <!-- ── Assigned Voters Table ── -->
                                        <div class="card mt-3">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h4 class="card-title mb-0">
                                                        Assigned Voters
                                                        <span class="badge bg-primary ms-2"><?= count($voters) ?></span>
                                                    </h4>
                                                </div>

                                                <?php if (empty($voters)): ?>
                                                    <div class="alert alert-info">No voters assigned to this adviser yet.</div>
                                                <?php else: ?>
                                                    <div class="table-responsive">
                                                        <table id="votersTable"
                                                            class="table table-striped table-bordered nowrap"
                                                            style="width:100%">
                                                            <thead>
                                                                <tr>
                                                                    <th>Student ID</th>
                                                                    <th>Full Name</th>
                                                                    <th>Email</th>
                                                                    <th>Course</th>
                                                                    <th>Year Level</th>
                                                                    <th>Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($voters as $voter): ?>
                                                                    <tr>
                                                                        <td><?= htmlspecialchars($voter['student_id']) ?></td>
                                                                        <td><?= htmlspecialchars(trim($voter['first_name'] . ' ' . ($voter['middle_name'] ? $voter['middle_name'] . ' ' : '') . $voter['last_name'])) ?></td>
                                                                        <td><?= htmlspecialchars($voter['email']) ?></td>
                                                                        <td><?= htmlspecialchars($voter['course_name'] ?? 'N/A') ?></td>
                                                                        <td><?= htmlspecialchars($voter['year_level_label'] ?? $voter['year_level_num'] ?? 'N/A') ?></td>
                                                                        <td>
                                                                            <?php
                                                                            $status = strtolower($voter['status'] ?? '');
                                                                            $badgeClass = match ($status) {
                                                                                'confirmed', 'active' => 'bg-success',
                                                                                'pending'             => 'bg-warning text-dark',
                                                                                'rejected'            => 'bg-danger',
                                                                                'archived'            => 'bg-secondary',
                                                                                default               => 'bg-secondary',
                                                                            };
                                                                            ?>
                                                                            <span class="badge <?= $badgeClass ?>">
                                                                                <?= htmlspecialchars(ucfirst($voter['status'] ?? 'Unknown')) ?>
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                    </div><!-- tab-pane -->
                                </div><!-- tab-content -->
                            </div><!-- home-tab -->
                        </div><!-- col -->
                    </div><!-- row -->
                </div><!-- content-wrapper -->
            </div><!-- main-panel -->
        </div><!-- page-body-wrapper -->
    </div><!-- container-scroller -->

    <!-- ── Scripts ── -->
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
        $(document).ready(function() {

            // Voters DataTable
            $('#votersTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [
                    [1, 'asc']
                ],
                columnDefs: [{
                        orderable: false,
                        targets: 5
                    } // don't sort status column by default
                ]
            });

            // Navbar show-more toggle
            const toggleBtn = document.getElementById('toggleElections');
            const moreDiv = document.getElementById('moreElections');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const visible = moreDiv.style.display !== 'none';
                    moreDiv.style.display = visible ? 'none' : 'block';
                    toggleBtn.textContent = visible ? 'Show More' : 'Show Less';
                });
            }

            // Back to Top
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
            <?php elseif ($_SESSION['STATUS'] === 'ADVISER_UPDATED_SUCCESSFULLY'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: 'Adviser information updated successfully.',
                    confirmButtonColor: '#3085d6'
                });
            <?php elseif ($_SESSION['STATUS'] === 'ADVISER_DELETED_SUCCESSFULLY'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: 'Adviser deleted successfully.',
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
                    text: 'The email address already exists.',
                    confirmButtonColor: '#d33'
                });
            <?php elseif ($_SESSION['STATUS'] === 'ADVISER_ALREADY_EXISTS_FOR_COMBINATION'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An adviser already exists for that combination.',
                    confirmButtonColor: '#d33'
                });
            <?php elseif ($_SESSION['STATUS'] === 'ADVISER_UPDATE_FAILED'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: 'There was an error updating the adviser.',
                    confirmButtonColor: '#d33'
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
        <?php unset($_SESSION['STATUS']); ?>
    <?php endif; ?>

    <button class="back-to-top" id="backToTop" title="Go to top">
        <i class="mdi mdi-arrow-up"></i>
    </button>

</body>

</html>