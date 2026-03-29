<?php
session_start();
include('includes/conn.php');
include('includes/archive_conn.php');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = "NON_ADMIN";
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU i-Elect: Admin | Index </title>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="vendors/chart.js/Chart.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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
                    <li class="nav-item <?php echo $current_page == 'academic_info.php' ? 'active-link' : ''; ?>">
                        <a class="nav-link <?php echo $current_page == 'academic_info.php' ? 'active-link' : ''; ?>" href="academic_info.php" <?php echo $current_page == 'academic_info.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                            <i class="menu-icon mdi mdi-school"
                                <?= $current_page == 'academic_info.php' ? 'style="color: white !important;"' : ''; ?>>
                            </i>
                            <span class="menu-title"
                                <?= $current_page == 'academic_info.php' ? 'style="color: white !important;"' : ''; ?>>
                                Academic Year
                            </span>

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

            <!-- Main Content -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Academic Info</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                                        <div class="card">
                                            <div class="card-body p-5">
                                                <div class="d-flex align-items-center mb-5">
                                                    <h3><b>Academic Information</b></h3>

                                                    <div class=" ms-auto" aria-hidden="true">
                                                        <button type="button" class="btn btn-primary text-white" id="createAcademicYearBtn" data-bs-toggle="modal" data-bs-target="#createAcademicYearModal">
                                                            <i class="mdi mdi-plus me-1"></i> Create Academic Year
                                                        </button>


                                                    </div>
                                                </div>

                                                <div class="modal fade" id="createAcademicYearModal" tabindex="-1" aria-labelledby="createAcademicYearModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form id="createAcademicYearForm" method="POST" action="processes/semester/create_academic_year.php">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="createAcademicYearModalLabel">Create Academic Year</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">

                                                                    <!-- Notice Header -->
                                                                    <div class="alert alert-warning">
                                                                        <strong>Note:</strong> Creating a new academic year may require students to update their accounts before they can continue using the system.
                                                                    </div>

                                                                    <!-- School Year -->
                                                                    <div class="mb-3">
                                                                        <label class="form-label">School Year</label>
                                                                        <div class="d-flex align-items-center">
                                                                            <input type="number" class="form-control me-2" id="startYear" name="start_year" min="2000" max="2099" required>
                                                                            <span class="me-2">-</span>
                                                                            <input type="number" class="form-control" id="endYear" name="end_year" readonly>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Semester -->
                                                                    <div class="mb-3">
                                                                        <label for="semester" class="form-label">Semester</label>
                                                                        <select class="form-select" name="semester" id="semester" required>
                                                                            <option value="">Select Semester</option>
                                                                            <option value="1st Semester">1st Semester</option>
                                                                            <option value="2nd Semester">2nd Semester</option>
                                                                        </select>
                                                                    </div>

                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    <button type="submit" class="btn btn-primary text-white">Save Academic Year</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>


                                                <script>
                                                    document.addEventListener('DOMContentLoaded', function() {
                                                        const startYearInput = document.getElementById('startYear');
                                                        const endYearInput = document.getElementById('endYear');

                                                        // Set default year to current year
                                                        const today = new Date();
                                                        startYearInput.value = today.getFullYear();
                                                        endYearInput.value = today.getFullYear() + 1;

                                                        // Auto-update end year when start year changes
                                                        startYearInput.addEventListener('input', function() {
                                                            const startYear = parseInt(this.value) || today.getFullYear();
                                                            endYearInput.value = startYear + 1;
                                                        });
                                                    });
                                                </script>
                                                <?php
                                                try {
                                                    $stmt = $pdo->query("SELECT * FROM academic_years ORDER BY start_date ASC");
                                                    $academic_years = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                } catch (PDOException $e) {
                                                    die("Database error: " . $e->getMessage());
                                                }
                                                ?>




                                                <!-- Academic Year Table -->
                                                <table id="academicYearTable" class="table table-bordered table-striped datatable">
                                                    <thead>
                                                        <tr>
                                                            <th>School Year </th>
                                                            <th>Semester</th>
                                                            <th>Status</th>
                                                            <th>Details</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (!empty($academic_years)): ?>
                                                            <?php foreach ($academic_years as $ay): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($ay['year_label']) ?></td>
                                                                    <td><?= htmlspecialchars($ay['semester']) ?></td>
                                                                    <td>
                                                                        <?php
                                                                        $status = strtolower(trim($ay['status']));

                                                                        $badgeClass = match ($status) {
                                                                            'active'    => 'badge bg-success',
                                                                            'inactive'  => 'badge bg-secondary',
                                                                            'archived'  => 'badge bg-dark',
                                                                            'published' => 'badge bg-primary',
                                                                            default     => 'badge bg-light text-dark'
                                                                        };
                                                                        ?>
                                                                        <span class="<?= $badgeClass ?>">
                                                                            <?= htmlspecialchars(ucfirst($ay['status'])) ?>
                                                                        </span>
                                                                    </td>


                                                                    <td>
                                                                        <?php
                                                                        // Always load elections list from MAIN DB
                                                                        $stmt = $pdo->prepare("SELECT * FROM elections WHERE academic_year_id = ?");
                                                                        $stmt->execute([$ay['id']]);
                                                                        $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                                        if (!$elections) {
                                                                            echo "<em>No elections found.</em>";
                                                                        }

                                                                        echo "<ul class='election-list'>";

                                                                        foreach ($elections as $election):

                                                                            $isArchived = ($election['status'] === 'Published');

                                                                            echo "<li>";
                                                                            echo "<strong>Election:</strong> " . htmlspecialchars($election['election_name']) . "<br>";
                                                                            echo "<strong>Start Date:</strong> " . date('F j, Y', strtotime($election['start_period'])) . "<br>";
                                                                            echo "<strong>End Date:</strong> " . date('F j, Y', strtotime($election['end_period'])) . "<br>";

                                                                            if ($isArchived) {

                                                                                /* =========================
           ARCHIVED DATABASE QUERIES
           ========================= */

                                                                                $db = $archivePdo;

                                                                                // Voting periods (archived_candidacies)
                                                                                $stmtVP = $db->prepare("
            SELECT *
            FROM archived_candidacies
            WHERE election_name = ?
            ORDER BY start_period ASC
        ");
                                                                                $stmtVP->execute([$election['election_name']]);
                                                                                $votingPeriods = $stmtVP->fetchAll(PDO::FETCH_ASSOC);

                                                                                if ($votingPeriods):
                                                                                    echo "<ul class='voting-period-list'>";

                                                                                    foreach ($votingPeriods as $vp):

                                                                                        // Candidates
                                                                                        $stmtC = $db->prepare("
                    SELECT COUNT(*)
                    FROM archived_candidates
                    WHERE voting_period_id = ?
                ");
                                                                                        $stmtC->execute([
                                                                                            $vp['voting_period_id']
                                                                                        ]);
                                                                                        $candidateCount = $stmtC->fetchColumn();

                                                                                        // Precincts
                                                                                        $stmtP = $db->prepare("
                    SELECT COUNT(*)
                    FROM archived_precincts
                    WHERE election = ?
                ");
                                                                                        $stmtP->execute([$election['election_name']]);
                                                                                        $precinctCount = $stmtP->fetchColumn();

                                                                                        // Voters
                                                                                        $stmtV = $db->prepare("
                    SELECT COUNT(DISTINCT student_id)
                    FROM archived_voters
                    WHERE election_name = ?
                      AND voting_period_id = ?
                ");
                                                                                        $stmtV->execute([
                                                                                            $election['election_name'],
                                                                                            $vp['voting_period_id']
                                                                                        ]);
                                                                                        $voterCount = $stmtV->fetchColumn();

                                                                                        echo "<li>";
                                                                                        echo "<em>Voting Period:</em> " . htmlspecialchars(ucfirst($vp['status'])) . "<br>";
                                                                                        echo "&nbsp;&nbsp;• Candidates: $candidateCount<br>";
                                                                                        echo "&nbsp;&nbsp;• Precincts: $precinctCount<br>";
                                                                                        echo "&nbsp;&nbsp;• Voters: $voterCount<br>";
                                                                                        echo "</li>";

                                                                                    endforeach;

                                                                                    echo "</ul>";
                                                                                else:
                                                                                    echo "<em>No archived voting periods.</em>";
                                                                                endif;
                                                                            } else {

                                                                                /* =====================
           LIVE DATABASE QUERIES
           ===================== */

                                                                                $db = $pdo;

                                                                                $stmtVP = $db->prepare("
            SELECT *
            FROM voting_periods
            WHERE election_id = ?
            ORDER BY start_period ASC
        ");
                                                                                $stmtVP->execute([$election['id']]);
                                                                                $votingPeriods = $stmtVP->fetchAll(PDO::FETCH_ASSOC);

                                                                                if ($votingPeriods):
                                                                                    echo "<ul class='voting-period-list'>";

                                                                                    foreach ($votingPeriods as $vp):

                                                                                        $stmtC = $db->prepare("
                    SELECT COUNT(*)
                    FROM candidates c
                    JOIN registration_forms rf ON c.form_id = rf.id
                    WHERE rf.election_name = ?
                ");
                                                                                        $stmtC->execute([$election['election_name']]);
                                                                                        $candidateCount = $stmtC->fetchColumn();

                                                                                        $stmtP = $db->prepare("
                    SELECT COUNT(DISTINCT p.id)
                    FROM precincts p
                    JOIN precinct_elections pe ON pe.precinct_id = p.id
                    WHERE pe.election_name = ?
                ");
                                                                                        $stmtP->execute([$election['election_name']]);
                                                                                        $precinctCount = $stmtP->fetchColumn();

                                                                                        $stmtV = $db->prepare("
                    SELECT COUNT(DISTINCT pv.student_id)
                    FROM precinct_voters pv
                    JOIN precincts p ON pv.precinct = p.id
                    JOIN precinct_elections pe ON pe.precinct_id = p.id
                    WHERE pe.election_name = ?
                ");
                                                                                        $stmtV->execute([$election['election_name']]);
                                                                                        $voterCount = $stmtV->fetchColumn();

                                                                                        echo "<li>";
                                                                                        echo "<em>Voting Period:</em> " . htmlspecialchars($vp['status']) . "<br>";
                                                                                        echo "&nbsp;&nbsp;• Candidates: $candidateCount<br>";
                                                                                        echo "&nbsp;&nbsp;• Precincts: $precinctCount<br>";
                                                                                        echo "&nbsp;&nbsp;• Voters: $voterCount<br>";
                                                                                        echo "</li>";

                                                                                    endforeach;

                                                                                    echo "</ul>";
                                                                                else:
                                                                                    echo "<em>No voting periods found.</em>";
                                                                                endif;
                                                                            }

                                                                            echo "</li>";

                                                                        endforeach;

                                                                        echo "</ul>";
                                                                        ?>


                                                                    </td>

                                                                    <td>
                                                                        <button type="button" class="btn btn-danger text-white btn-sm deleteAcademicYearBtn" data-id="<?= $ay['id'] ?>">
                                                                            <i class="mdi mdi-trash-can-outline"></i> Delete
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td colspan="5" class="text-center">No academic years found.</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>



                                            </div>
                                        </div>

                                        <!-- jQuery (Required) -->
                                        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

                                        <!-- DataTables CSS -->
                                        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

                                        <!-- DataTables JS -->
                                        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                                        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

                                        <script>
                                            $(document).ready(function() {
                                                $('#academicYearTable').DataTable({
                                                    responsive: true,
                                                    paging: true,
                                                    searching: true,
                                                    ordering: true,
                                                    info: true,
                                                    order: [
                                                        [0, 'asc']
                                                    ]
                                                });

                                                $('#academicYearTable').on('click', '.deleteAcademicYearBtn', function() {
                                                    const row = $(this).closest('tr');
                                                    const id = $(this).data('id'); // Get academic year ID

                                                    Swal.fire({
                                                        title: 'Are you sure?',
                                                        text: `You are about to delete the academic year. This action cannot be undone.`,
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonColor: '#d33',
                                                        cancelButtonColor: '#3085d6',
                                                        confirmButtonText: 'Yes, delete it!',
                                                        cancelButtonText: 'Cancel'
                                                    }).then((result) => {
                                                        if (result.isConfirmed) {
                                                            $.ajax({
                                                                url: 'processes/semester/delete_academic_year.php',
                                                                method: 'POST',
                                                                data: {
                                                                    id: id
                                                                },
                                                                dataType: 'json',
                                                                success: function(response) {
                                                                    if (response.status === 'success') {
                                                                        Swal.fire(
                                                                            'Deleted!',
                                                                            response.message,
                                                                            'success'
                                                                        );
                                                                        row.remove(); // Remove row from table
                                                                    } else {
                                                                        // Show error returned from PHP (e.g., ongoing elections)
                                                                        Swal.fire(
                                                                            'Error!',
                                                                            response.message,
                                                                            'error'
                                                                        );
                                                                    }
                                                                },
                                                                error: function(xhr, status, error) {
                                                                    Swal.fire(
                                                                        'Error!',
                                                                        'Failed to delete the academic year. ' + error,
                                                                        'error'
                                                                    );
                                                                }
                                                            });
                                                        }
                                                    });
                                                });


                                                // Create button handler
                                                $('#createAcademicYearBtn').on('click', function() {
                                                    // Open modal or redirect to create form
                                                    console.log('Open create academic year modal');
                                                });
                                            });
                                        </script>

                                        <!-- Include SweetAlert -->
                                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

                                        <?php

                                        if (isset($_SESSION['status']) && isset($_SESSION['message'])) {
                                            $status = $_SESSION['status'];
                                            $message = $_SESSION['message'];
                                            echo "<script>
        Swal.fire({
            icon: '$status' === 'success' ? 'success' : ('$status' === 'exists' ? 'warning' : 'error'),
            title: '$message',
            timer: 3000,
            showConfirmButton: false
        });
    </script>";
                                            // Clear session messages after displaying
                                            unset($_SESSION['status']);
                                            unset($_SESSION['message']);
                                        }
                                        ?>




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
                                <script src="js/Chart.roundedBarCharts.js"></script>

                                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                                <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
                                <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                                <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

                                <script>
                                    $(document).ready(function() {
                                        $('#userActivityTable').DataTable({
                                            "paging": true,
                                            "searching": true,
                                            "ordering": true,
                                            "info": true
                                        });
                                    });
                                </script>

                                <!-- SweetAlert2 Reminder Script -->
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const daysUntilStart = <?php echo json_encode($daysUntilStart); ?>;
                                        const votingPeriodName = <?php echo json_encode($votingPeriodName); ?>;
                                        const votingStartDate = <?php echo json_encode($votingPeriodStartDate ? date('F j, Y', strtotime($votingPeriodStartDate)) : 'N/A'); ?>;

                                        if (daysUntilStart !== null && votingPeriodStatus === 'Scheduled') {
                                            if (daysUntilStart === 30) {
                                                Swal.fire({
                                                    icon: 'info',
                                                    title: 'Voting Period Approaching',
                                                    text: `The voting period "${votingPeriodName}" is 1 month away! It starts on ${votingStartDate}.`,
                                                    confirmButtonText: 'OK'
                                                });
                                            } else if (daysUntilStart === 15) {
                                                Swal.fire({
                                                    icon: 'info',
                                                    title: 'Voting Period Approaching',
                                                    text: `The voting period "${votingPeriodName}" is 15 days away! It starts on ${votingStartDate}.`,
                                                    confirmButtonText: 'OK'
                                                });
                                            } else if (daysUntilStart === 3) {
                                                Swal.fire({
                                                    icon: 'warning',
                                                    title: 'Voting Period Nearing',
                                                    text: `The voting period "${votingPeriodName}" is 3 days away! It starts on ${votingStartDate}.`,
                                                    confirmButtonText: 'OK'
                                                });
                                            } else if (daysUntilStart === 1) {
                                                Swal.fire({
                                                    icon: 'warning',
                                                    title: 'Voting Period Tomorrow',
                                                    text: `The voting period "${votingPeriodName}" is 1 day away! It starts on ${votingStartDate}.`,
                                                    confirmButtonText: 'OK'
                                                });
                                            }
                                        }
                                    });
                                </script>

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


</body>

<button class="back-to-top" id="backToTop" title="Go to top">
    <i class="mdi mdi-arrow-up"></i>
</button>




</html>

<?php
include('includes/alerts.php');
?>