<?php
ini_set('max_execution_time', 3600); // 300 seconds (5 minutes)
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
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU i-Elect Admin | Moderators </title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet"
        href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet"
        href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="js/select.dataTables.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="images/favicon.png" />

    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

</head>


<style>
    .custom-padding {
        padding: 20px !important;
        /* Adjust padding as needed */
    }

    .form-check {
        padding-left: 10px !important;
        margin-right: 0px !important;
        position: relative;
    }

    .form-check,
    .form-check-label {
        margin-left: 10px !important;
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
        <nav
            class="navbar default-layout col-lg-12 col-12 p-0 d-flex align-items-top flex-row">
            <div
                class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
                <div class="me-3">
                    <button class="navbar-toggler navbar-toggler align-self-center"
                        type="button" data-bs-toggle="minimize">
                        <span class="icon-menu"></span>
                    </button>
                </div>
                <div>
                    <a class="navbar-brand brand-logo" href="index.php">

                        <img src="images/wmsu-logo.png" alt="logo"
                            class="logo img-fluid" />
                        <small style="font-size: 16px;"><b>WMSU i-Elect</b></small>
                    </a>

                    <a class="navbar-brand brand-logo-mini" href="index.php">
                        <img src="images/wmsu-logo.png" class="logo img-fluid"
                            alt="logo" />
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

            <?php
            // Get the current PHP file name
            $current_page = basename($_SERVER['PHP_SELF']);
            ?>

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

            </ul>
            </nav>
            <!-- partial -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div
                                    class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active ps-0" id="home-tab"
                                                data-bs-toggle="tab" href="#overview" role="tab"
                                                aria-controls="overview"
                                                aria-selected="true">Moderators</a>
                                        </li>

                                    </ul>

                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview"
                                        role="tabpanel" aria-labelledby="overview">

                                        <div class="row mt-3">
                                            <div class="card px-5 pt-5">
                                                <div class="d-flex align-items-center">
                                                    <h3><b>Moderators</b></h3>
                                                    <div class=" ms-auto" aria-hidden="true">
                                                        <button class="btn btn-primary text-white"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#addModeratorModal">
                                                            <i class="bi bi-person-add"></i> Add Moderator
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <?php
                                                        try {
                                                            // Fetch all moderators along with their precinct name
                                                            $sql = "
        SELECT 
            m.id,
            m.name,
            m.email,
            m.password,
            m.gender,
            m.department,
            m.precinct AS precinct_id,
            m.status,
            m.college,
            p.name AS precinct_name,
            p.assignment_status
        FROM moderators m
        LEFT JOIN precincts p ON m.precinct = p.id
    ";

                                                            $stmt = $pdo->prepare($sql);
                                                            $stmt->execute();
                                                            $moderators = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                        } catch (PDOException $e) {
                                                            die("Error fetching moderators: " . $e->getMessage());
                                                        }
                                                        ?>


                                                        <table class="table table-striped table-bordered" id="moderatorsTable">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>Name</th>
                                                                    <th>Email</th>
                                                                    <th>Gender</th>
                                                                    <th>College</th>
                                                                    <th>Department</th>
                                                                    <th>Precinct</th>
                                                                    <th>Account Status</th>
                                                                    <th>Manage</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($moderators as $moderator): ?>
                                                                    <tr>
                                                                        <td><?= htmlspecialchars($moderator['name']); ?></td>

                                                                        <td><?= htmlspecialchars($moderator['email']); ?></td>

                                                                        <td><?= htmlspecialchars($moderator['gender']); ?></td>
                                                                        <td><?= htmlspecialchars($moderator['college']); ?></td>
                                                                        <td><?= htmlspecialchars($moderator['department']); ?></td>
                                                                        <td>
                                                                            <?php
                                                                            if (!empty($moderator['precinct_name'])) {
                                                                                $decoded = json_decode($moderator['precinct_name'], true);

                                                                                if (is_array($decoded) && count($decoded) > 0) {
                                                                                    echo '<ul class="mb-0 ps-3">'; // optional Bootstrap spacing
                                                                                    foreach ($decoded as $item) {
                                                                                        echo '<li>' . htmlspecialchars($item) . '</li>';
                                                                                    }
                                                                                    echo '</ul>';
                                                                                } else {
                                                                                    echo htmlspecialchars($moderator['precinct_name']);
                                                                                }
                                                                            } else {
                                                                                echo 'None';
                                                                            }
                                                                            ?>
                                                                        </td>





                                                                        <td>
                                                                            <?php if ($moderator['status'] === 'active'): ?>
                                                                                <button type="button" class="btn btn-success text-white">Activated</button>
                                                                            <?php else: ?>
                                                                                <button type="button" class="btn btn-danger text-white">Unactivated</button>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td>
                                                                            <button type="button" class="btn btn-success view-moderator-btn text-white" data-bs-toggle="modal"
                                                                                data-bs-target="#viewModal"
                                                                                data-name="<?= htmlspecialchars($moderator['name']); ?>"
                                                                                data-email="<?= htmlspecialchars($moderator['email']); ?>"
                                                                                data-gender="<?= htmlspecialchars($moderator['gender']); ?>"
                                                                                data-college="<?= htmlspecialchars($moderator['college']); ?>"
                                                                                data-department="<?= htmlspecialchars($moderator['department']); ?>"
                                                                                data-precinct="<?= htmlspecialchars($moderator['precinct_name']); ?>"
                                                                                onclick="fillViewModal(this)">
                                                                                View
                                                                            </button>

                                                                            <button type="button" class="btn toggle-status-btn text-white <?php echo $moderator['status'] === 'active' ? 'btn-success' : 'btn-warning'; ?>"
                                                                                data-id="<?php echo $moderator['id']; ?>"
                                                                                data-status="<?php echo $moderator['status']; ?>">
                                                                                <?php echo $moderator['status'] === 'active' ? '<i class="bi bi-check-circle"></i> Active' : '<i class="bi bi-x-circle"></i> Inactive'; ?>
                                                                            </button>
                                                                            <button type="button" class="btn btn-warning text-white" data-bs-toggle="modal"
                                                                                data-bs-target="#editModeratorModal"
                                                                                data-id="<?= htmlspecialchars($moderator['id']); ?>"
                                                                                data-name="<?= htmlspecialchars($moderator['name']); ?>"
                                                                                data-email="<?= htmlspecialchars($moderator['email']); ?>"
                                                                                data-gender="<?= htmlspecialchars($moderator['gender']); ?>"
                                                                                data-college="<?= htmlspecialchars($moderator['college']); ?>"
                                                                                data-department="<?= htmlspecialchars($moderator['department']); ?>"
                                                                                data-precinct="<?= htmlspecialchars($moderator['precinct_name']); ?>"
                                                                                onclick="fillEditModal(this)">
                                                                                Edit
                                                                            </button>


                                                                            <button type="button" class="btn btn-danger text-white" onclick="deleteModerator(<?= $moderator['id']; ?>)">Delete</button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- content-wrapper ends -->
                                        <!-- partial:partials/_footer.html -->

                                        <!-- partial -->
                                    </div>
                                    <!-- main-panel ends -->
                                </div>
                                <!-- page-body-wrapper ends -->
                            </div>
                            <!-- container-scroller -->


                            <div class="modal fade" id="addModeratorModal" tabindex="-1"
                                aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-md">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Add
                                                Moderator Profile</h1>
                                            <button type="button" class="btn-close"
                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="addModeratorForm">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <div class="row">
                                                    <div class="col">
                                                        <div class="mb-3">
                                                            <label for="name" class="form-label">Name</label>
                                                            <input type="text" class="form-control" id="name" name="name" required>
                                                        </div>



                                                        <div class="mb-3">
                                                            <label for="email" class="form-label">Email</label>
                                                            <input type="email" class="form-control" id="email" name="email" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="password" class="form-label">Password</label>
                                                            <div class="input-group">
                                                                <input type="password" class="form-control" id="password" name="password"
                                                                    required minlength="8" onkeyup="checkPasswordStrength()">
                                                                <span class="input-group-text" onclick="togglePassword()" style="cursor: pointer;">
                                                                    <i id="toggleIcon" class="bi bi-eye"></i>
                                                                </span>
                                                            </div>
                                                            <div id="password-strength" class="mt-2">
                                                                <small id="strength-text" class="form-text"></small>
                                                                <div id="strength-bar" class="progress" style="height: 5px; display: none;">
                                                                    <div class="progress-bar" role="progressbar" style="width: 0%;"></div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="gender" class="form-label">Gender</label>
                                                            <select class="form-control" id="gender" name="gender" required>
                                                                <option value disabled selected>Select Gender</option>
                                                                <option value="Male">Male</option>
                                                                <option value="Female">Female</option>
                                                                <option value="Other">Other</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="college" class="form-label">College</label>
                                                            <select class="form-control" id="college" name="college" required>
                                                                <option value="" disabled selected>Select College</option>
                                                                <option value="College of Law">College of Law</option>
                                                                <option value="College of Agriculture">College of Agriculture</option>
                                                                <option value="College of Liberal Arts">College of Liberal Arts</option>
                                                                <option value="College of Architecture">College of Architecture</option>
                                                                <option value="College of Nursing">College of Nursing</option>
                                                                <option value="College of Asian & Islamic Studies">College of Asian & Islamic Studies</option>
                                                                <option value="College of Computing Studies">College of Computing Studies</option>
                                                                <option value="College of Forestry & Environmental Studies">College of Forestry & Environmental Studies</option>
                                                                <option value="College of Criminal Justice Education">College of Criminal Justice Education</option>
                                                                <option value="College of Home Economics">College of Home Economics</option>
                                                                <option value="College of Engineering">College of Engineering</option>
                                                                <option value="College of Medicine">College of Medicine</option>
                                                                <option value="College of Public Administration & Development Studies">College of Public Administration & Development Studies</option>
                                                                <option value="College of Sports Science & Physical Education">College of Sports Science & Physical Education</option>
                                                                <option value="College of Science and Mathematics">College of Science and Mathematics</option>
                                                                <option value="College of Social Work & Community Development">College of Social Work & Community Development</option>
                                                                <option value="College of Teacher Education">College of Teacher Education</option>
                                                            </select>

                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="department" class="form-label">Department</label>
                                                            <select class="form-control" id="department" name="department" required>
                                                                <option value="" disabled selected>Select Department</option>
                                                            </select>
                                                        </div>



                                                        <div class="mb-3">
                                                            <label class="form-label">Precincts</label>
                                                            <div id="precinct-container" class="border rounded p-2" style="max-height: 220px; overflow-y: auto;">
                                                                <p class="text-muted">Select a college and department first.</p>
                                                            </div>
                                                        </div>


                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="mdi mdi-alpha-x-circle"></i> &nbsp; Close</button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <small>
                                                            <i class="mdi mdi-plus-circle"></i> &nbsp; Save changes
                                                        </small>
                                                    </button>

                                                </div>
                                            </form>


                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="editModeratorModal" tabindex="-1" aria-labelledby="editModeratorModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-md">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="editModeratorModalLabel">Edit Moderator Profile</h1>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="editModeratorForm">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" id="edit_moderator_id" name="id">
                                                <div class="row">
                                                    <div class="col">
                                                        <div class="mb-3">
                                                            <label for="edit_name" class="form-label">Name</label>
                                                            <input type="text" class="form-control" id="edit_name" name="name" required>
                                                        </div>
                                                        <div class="mb-3" style="display: none;">
                                                            <label for="edit_email_prev" class="form-label">Email</label>
                                                            <input type="email" class="form-control" id="edit_email_prev" name="prev_email" required>
                                                        </div>


                                                        <div class="mb-3">
                                                            <label for="edit_email" class="form-label">Email</label>
                                                            <input type="email" class="form-control" id="edit_email" name="email" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="edit_password" class="form-label">Password (Leave blank to keep unchanged)</label>
                                                            <div class="input-group">
                                                                <input type="password" class="form-control" id="edit_password" name="password"
                                                                    minlength="8" onkeyup="checkPasswordStrengthEdit()">
                                                                <span class="input-group-text" onclick="togglePasswordEdit()" style="cursor: pointer;">
                                                                    <i id="toggleIcon_edit" class="bi bi-eye"></i>
                                                                </span>
                                                            </div>
                                                            <div id="password-strength_edit" class="mt-2">
                                                                <small id="strength-text_edit" class="form-text"></small>
                                                                <div id="strength-bar_edit" class="progress" style="height: 5px; display: none;">
                                                                    <div class="progress-bar_edit" role="progressbar" style="width: 0%;"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="edit_gender" class="form-label">Gender</label>
                                                            <select class="form-control" id="edit_gender" name="gender" required>
                                                                <option value="" disabled>Select Gender</option>
                                                                <option value="Male">Male</option>
                                                                <option value="Female">Female</option>
                                                                <option value="Other">Other</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="edit_college" class="form-label">
                                                                College
                                                                <small id="current_college" class="form-text text-muted ms-2"></small>
                                                            </label>
                                                            <select class="form-control" id="edit_college" name="college" required>
                                                                <option value="" disabled selected>Select College</option>
                                                                <option value="College of Law">College of Law</option>
                                                                <option value="College of Agriculture">College of Agriculture</option>
                                                                <option value="College of Liberal Arts">College of Liberal Arts</option>
                                                                <option value="College of Architecture">College of Architecture</option>
                                                                <option value="College of Nursing">College of Nursing</option>
                                                                <option value="College of Asian & Islamic Studies">College of Asian & Islamic Studies</option>
                                                                <option value="College of Computing Studies">College of Computing Studies</option>
                                                                <option value="College of Forestry & Environmental Studies">College of Forestry & Environmental Studies</option>
                                                                <option value="College of Criminal Justice Education">College of Criminal Justice Education</option>
                                                                <option value="College of Home Economics">College of Home Economics</option>
                                                                <option value="College of Engineering">College of Engineering</option>
                                                                <option value="College of Medicine">College of Medicine</option>
                                                                <option value="College of Public Administration & Development Studies">College of Public Administration & Development Studies</option>
                                                                <option value="College of Sports Science & Physical Education">College of Sports Science & Physical Education</option>
                                                                <option value="College of Science and Mathematics">College of Science and Mathematics</option>
                                                                <option value="College of Social Work & Community Development">College of Social Work & Community Development</option>
                                                                <option value="College of Teacher Education">College of Teacher Education</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="edit_department" class="form-label">
                                                                Department
                                                                <small id="current_department" class="form-text text-muted ms-2"></small>
                                                            </label>
                                                            <select class="form-control" id="edit_department" name="department" required>
                                                                <option value="" disabled>Select Department</option>

                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="edit_precinct" class="form-label">
                                                                Precinct
                                                                <small id="current_precinct" class="form-text text-muted ms-2"></small>
                                                                <small>(Leave blank to keep unchanged)</small>
                                                            </label>

                                                            <div id="edit_precinct_container" class="border rounded p-2" style="max-height: 220px; overflow-y: auto;"></div>
                                                        </div>

                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="mdi mdi-alpha-x-circle"></i> Close
                                                    </button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <small><i class="mdi mdi-content-save"></i> Save Changes</small>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="viewModeratorModal" tabindex="-1" aria-labelledby="viewModeratorModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-md">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewModeratorModalLabel">Moderator Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-bold">Name:</label>
                                                    <p id="viewName" class="form-control-static"></p>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-bold">Email:</label>
                                                    <p id="viewEmail" class="form-control-static"></p>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-bold">Gender:</label>
                                                    <p id="viewGender" class="form-control-static"></p>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-bold">College:</label>
                                                    <p id="viewCollege" class="form-control-static"></p>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-bold">Department:</label>
                                                    <p id="viewDepartment" class="form-control-static"></p>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-bold">Precinct:</label>
                                                    <p id="viewPrecinct" class="form-control-static"></p>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-bold">Created At:</label>
                                                    <p id="viewCreatedAt" class="form-control-static"></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>



                            <script>
                                function showViewModal(moderatorData) {
                                    // Handle precinct formatting
                                    let precinctDisplay = 'N/A';

                                    if (moderatorData.precinct) {
                                        try {
                                            // Try decoding JSON
                                            const parsed = JSON.parse(moderatorData.precinct);

                                            if (Array.isArray(parsed)) {
                                                precinctDisplay = parsed.join(', ');
                                            } else {
                                                precinctDisplay = moderatorData.precinct;
                                            }

                                        } catch (e) {
                                            // Not JSON → treat as plain text
                                            precinctDisplay = moderatorData.precinct;
                                        }
                                    }

                                    // Populate modal fields
                                    document.getElementById('viewName').textContent = moderatorData.name || 'N/A';
                                    document.getElementById('viewEmail').textContent = moderatorData.email || 'N/A';
                                    document.getElementById('viewGender').textContent = moderatorData.gender || 'N/A';
                                    document.getElementById('viewCollege').textContent = moderatorData.college || 'N/A';
                                    document.getElementById('viewDepartment').textContent = moderatorData.department || 'N/A';
                                    document.getElementById('viewPrecinct').textContent = precinctDisplay;
                                    document.getElementById('viewCreatedAt').textContent = moderatorData.created_at || 'N/A';

                                    // Show the modal
                                    const modal = new bootstrap.Modal(document.getElementById('viewModeratorModal'));
                                    modal.show();
                                }


                                // Example usage: You might call this from a table row click
                                document.addEventListener('DOMContentLoaded', function() {
                                    // Example: Button to trigger the modal (you might have this in a table)
                                    const viewButtons = document.querySelectorAll('.view-moderator-btn');
                                    viewButtons.forEach(button => {
                                        button.addEventListener('click', function() {
                                            // This data would typically come from your database via AJAX
                                            const sampleData = {
                                                name: this.dataset.name,
                                                email: this.dataset.email,
                                                gender: this.dataset.gender,
                                                college: this.dataset.college,
                                                department: this.dataset.department,
                                                precinct: this.dataset.precinct,
                                                created_at: this.dataset.createdAt
                                            };
                                            showViewModal(sampleData);
                                        });
                                    });
                                });
                            </script>







                            <!-- Ensure Bootstrap and jQuery are included -->
                            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                            <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
                            <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                            <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                            <!-- Your modal HTML (ensure the id matches) -->

                            <!-- plugins:js -->
                            <script src="vendors/js/vendor.bundle.base.js"></script>
                            <!-- endinject -->
                            <!-- Plugin js for this page -->
                            <script src="vendors/chart.js/Chart.min.js"></script>
                            <script
                                src="vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
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

                            <script>
                                $(document).ready(function() {
                                    $('#moderatorsTable').DataTable({
                                        "paging": true,
                                        "searching": true,
                                        "ordering": true,
                                        "info": true,
                                        order: [
                                            [0, 'asc']
                                        ], // 0 = first column (full_name column index may differ)
                                    });
                                });
                            </script>



                            <!-- jQuery (Required) -->
                            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const collegeSelect = document.getElementById('college');
                                    const departmentSelect = document.getElementById('department');
                                    const precinctSelect = document.getElementById('precinct');
                                    const form = document.getElementById('addModeratorForm');

                                    // Sample department mappings (you might want to load this dynamically too)
                                    const departmentsByCollege = {
                                        'College of Law': ['Law'],
                                        'College of Agriculture': [
                                            'Crop Science', 'Animal Science', 'Agriculture', 'Food Technology', 'Agribusiness',
                                            'Agricultural Technology', 'Agronomy'
                                        ],
                                        'College of Liberal Arts': [
                                            'Accountancy', 'History', 'English', 'Political Science', 'Mass Communication - Journalism',
                                            'Mass Communication - Broadcasting', 'Economics', 'Psychology'
                                        ],
                                        'College of Architecture': ['Architecture'],
                                        'College of Nursing': ['Nursing'],
                                        'College of Asian & Islamic Studies': ['Asian Studies', 'Islamic Studies'],
                                        'College of Computing Studies': ['Computer Science', 'Information Technology', 'Computer Technology'],
                                        'College of Forestry & Environmental Studies': ['Forestry', 'Agroforestry', 'Environmental Science'],
                                        'College of Criminal Justice Education': ['Criminology'],
                                        'College of Home Economics': ['Home Economics', 'Nutrition and Dietetics', 'Hospitality Management'],
                                        'College of Engineering': [
                                            'Civil Engineering', 'Computer Engineering', 'Electrical Engineering',
                                            'Electronics Engineering', 'Environmental Engineering', 'Geodetic Engineering',
                                            'Industrial Engineering', 'Mechanical Engineering', 'Sanitary Engineering'
                                        ],
                                        'College of Medicine': ['Medicine'],
                                        'College of Public Administration & Development Studies': ['Public Administration'],
                                        'College of Sports Science & Physical Education': ['Physical Education', 'Exercise and Sports Sciences'],
                                        'College of Science and Mathematics': ['Biology', 'Chemistry', 'Mathematics', 'Physics', 'Statistics'],
                                        'College of Social Work & Community Development': ['Social Work', 'Community Development'],
                                        'College of Teacher Education': [
                                            'Culture and Arts Education', 'Early Childhood Education', 'Elementary Education', 'Secondary Education',
                                            'Secondary Education major in English', 'Secondary Education Major in Filipino',
                                            'Secondary Education Major in Mathematics', 'Secondary Education Major in Sciences',
                                            'Secondary Education Major in Social Studies', 'Secondary Education Major in Values Education',
                                            'Special Needs Education'
                                        ]
                                    };


                                    // Update departments when college changes
                                    collegeSelect.addEventListener('change', function() {
                                        const college = this.value;
                                        departmentSelect.innerHTML = '<option value="" disabled selected>Select Department</option>';

                                        if (departmentsByCollege[college]) {
                                            departmentsByCollege[college].forEach(dept => {
                                                const option = document.createElement('option');
                                                option.value = dept;
                                                option.text = dept;
                                                departmentSelect.appendChild(option);
                                            });
                                        }
                                        loadPrecincts();
                                    });




                                    // Update precincts when either college or department changes
                                    departmentSelect.addEventListener('change', loadPrecincts);

                                    const precinctContainer = document.getElementById('precinct-container');


                                    function loadPrecincts() {
                                        const college = collegeSelect.value;
                                        const department = departmentSelect.value;

                                        precinctContainer.innerHTML = `<p class="text-muted">Loading...</p>`;


                                        if (college && department) {
                                            fetch(`processes/moderators/get_precincts.php?college=${encodeURIComponent(college)}&department=${encodeURIComponent(department)}`)
                                                .then(response => response.json())
                                                .then(data => {

                                                    precinctContainer.innerHTML = "";

                                                    if (data.success && data.precincts.length > 0) {
                                                        data.precincts.forEach(precinct => {

                                                            // Determine color based on capacity
                                                            let cap = precinct.current_capacity;
                                                            let color = "text-success"; // green default

                                                            if (cap >= 30 && cap < 70) color = "text-warning"; // orange
                                                            if (cap >= 70) color = "text-danger fw-bold"; // red

                                                            const div = document.createElement("div");
                                                            div.classList.add("form-check", "mb-1");

                                                            div.innerHTML = `
    <input class="form-check-input precinct-check" 
           type="radio" 
           name="precinct" 
           value="${precinct.id}">
    <label class="form-check-label">
        <span class="${color}">
            ${precinct.name} (${precinct.location})
            – ${precinct.current_capacity} people
        </span>
    </label>
`;

                                                            precinctContainer.appendChild(div);
                                                        });



                                                    } else {
                                                        precinctContainer.innerHTML = `<p class="text-danger">No precincts found.</p>`;
                                                    }
                                                })
                                                .catch(error => {
                                                    console.error('Error:', error);
                                                    precinctContainer.innerHTML = `<p class="text-danger">Error loading precincts.</p>`;
                                                });
                                        } else {
                                            precinctContainer.innerHTML = `<p class="text-muted">Select a college and department first.</p>`;
                                        }
                                    }


                                    // Form submission
                                    form.addEventListener('submit', function(e) {
                                        e.preventDefault();

                                        const password = document.getElementById('password').value;
                                        const strength = calculatePasswordStrength(password);
                                        const passwordInput = document.getElementById('password');

                                        if (strength < 60) {
                                            e.preventDefault();
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Weak Password',
                                                text: 'Please use a stronger password with at least 8 characters, including uppercase letters, numbers, and special characters.',
                                                confirmButtonColor: '#d33'
                                            });
                                            return;
                                        }

                                        passwordInput.value = password;

                                        const formData = new FormData(this);

                                        fetch('processes/moderators/process_moderators.php', {
                                                method: 'POST',
                                                body: formData
                                            })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.status === 'success') { // Check data.status instead of data.success
                                                    Swal.fire({
                                                        icon: 'success',
                                                        title: 'Success!',
                                                        text: data.message,
                                                        confirmButtonText: 'OK',
                                                        confirmButtonColor: '#3085d6'
                                                    }).then((result) => {
                                                        if (result.isConfirmed) {
                                                            form.reset(); // Reset the form
                                                            location.reload(); // Reload the page
                                                        }
                                                    });
                                                } else {
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Error',
                                                        text: data.message,
                                                        confirmButtonText: 'OK',
                                                        confirmButtonColor: '#d33'
                                                    });
                                                }
                                            })
                                            .catch(error => {
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Oops...',
                                                    text: 'Something went wrong! ' + error.message,
                                                    confirmButtonText: 'OK',
                                                    confirmButtonColor: '#d33'
                                                });
                                                console.error('Error:', error);
                                            });
                                    });






                                    function calculatePasswordStrength(password) {
                                        let strength = 0;
                                        if (password.length > 0) strength += 20;
                                        if (password.length >= 8) strength += 20;
                                        if (/[A-Z]/.test(password)) strength += 20;
                                        if (/[0-9]/.test(password)) strength += 20;
                                        if (/[^A-Za-z0-9]/.test(password)) strength += 20;
                                        return strength;
                                    }
                                });

                                function togglePassword() {
                                    try {
                                        const passwordInput = document.getElementById('password');
                                        const toggleIcon = document.getElementById('toggleIcon');

                                        // Check if elements are found
                                        if (!passwordInput) {
                                            console.error('Password input not found');
                                            return;
                                        }
                                        if (!toggleIcon) {
                                            console.error('Toggle icon not found');
                                            return;
                                        }

                                        // Toggle the password visibility
                                        if (passwordInput.type === 'password') {
                                            passwordInput.type = 'text';
                                            toggleIcon.classList.remove('bi-eye');
                                            toggleIcon.classList.add('bi-eye-slash');
                                        } else {
                                            passwordInput.type = 'password';
                                            toggleIcon.classList.remove('bi-eye-slash');
                                            toggleIcon.classList.add('bi-eye');
                                        }
                                    } catch (error) {
                                        console.error('Error in togglePassword:', error);
                                    }
                                }

                                function checkPasswordStrength() {
                                    const password = document.getElementById('password').value;
                                    const strengthText = document.getElementById('strength-text');
                                    const strengthBar = document.getElementById('strength-bar');
                                    const progressBar = strengthBar.querySelector('.progress-bar');

                                    // Calculate strength
                                    let strength = 0;
                                    if (password.length > 0) strength += 20;
                                    if (password.length >= 8) strength += 20;
                                    if (/[A-Z]/.test(password)) strength += 20;
                                    if (/[0-9]/.test(password)) strength += 20;
                                    if (/[^A-Za-z0-9]/.test(password)) strength += 20;

                                    // Update UI based on strength
                                    strengthBar.style.display = 'block';
                                    progressBar.style.width = `${strength}%`;

                                    if (strength <= 20) {
                                        strengthText.textContent = 'Very Weak';
                                        progressBar.classList.remove('bg-warning', 'bg-success');
                                        progressBar.classList.add('bg-danger');
                                    } else if (strength <= 40) {
                                        strengthText.textContent = 'Weak';
                                        progressBar.classList.remove('bg-danger', 'bg-success');
                                        progressBar.classList.add('bg-warning');
                                    } else if (strength <= 60) {
                                        strengthText.textContent = 'Moderate';
                                        progressBar.classList.remove('bg-danger', 'bg-success');
                                        progressBar.classList.add('bg-warning');
                                    } else if (strength <= 80) {
                                        strengthText.textContent = 'Strong';
                                        progressBar.classList.remove('bg-danger', 'bg-warning');
                                        progressBar.classList.add('bg-success');
                                    } else {
                                        strengthText.textContent = 'Very Strong';
                                        progressBar.classList.remove('bg-danger', 'bg-warning');
                                        progressBar.classList.add('bg-success');
                                    }

                                    // Reset if password is empty
                                    if (password.length === 0) {
                                        strengthBar.style.display = 'none';
                                        strengthText.textContent = '';
                                        progressBar.classList.remove('bg-danger', 'bg-warning', 'bg-success');
                                    }
                                }

                                // Optional: Initialize when page loads
                                document.addEventListener('DOMContentLoaded', function() {
                                    // Ensure the strength bar is hidden initially
                                    const strengthBar = document.getElementById('strength-bar');
                                    strengthBar.style.display = 'none';
                                });
                            </script>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {



                                    const toggleButtons = document.querySelectorAll('.toggle-status-btn');

                                    toggleButtons.forEach(button => {
                                        button.addEventListener('click', function() {
                                            const moderatorId = this.dataset.id;
                                            const currentStatus = this.dataset.status;

                                            Swal.fire({
                                                title: 'Are you sure?',
                                                text: `Do you want to change this moderator's status to ${currentStatus === 'active' ? 'inactive' : 'active'}?`,
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#3085d6',
                                                cancelButtonColor: '#d33',
                                                confirmButtonText: 'Yes, change it!'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    fetch('processes/moderators/toggle_status.php', {
                                                            method: 'POST',
                                                            headers: {
                                                                'Content-Type': 'application/x-www-form-urlencoded',
                                                            },
                                                            body: `id=${encodeURIComponent(moderatorId)}`
                                                        })
                                                        .then(response => {
                                                            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                                                            return response.json();
                                                        })
                                                        .then(data => {
                                                            if (data.success) {
                                                                // Update button appearance
                                                                if (data.new_status === 'active') {
                                                                    button.classList.remove('btn-warning');
                                                                    button.classList.add('btn-success');
                                                                    button.innerHTML = '<i class="bi bi-check-circle"></i> Active';
                                                                } else {
                                                                    button.classList.remove('btn-success');
                                                                    button.classList.add('btn-warning');
                                                                    button.innerHTML = '<i class="bi bi-x-circle"></i> Inactive';
                                                                }
                                                                button.dataset.status = data.new_status;

                                                                Swal.fire({
                                                                    icon: 'success',
                                                                    title: 'Success',
                                                                    text: data.message,
                                                                    confirmButtonColor: '#3085d6',
                                                                    timer: 750, // Auto-close after 1.5 seconds
                                                                    showConfirmButton: false
                                                                });
                                                                // Refresh immediately after showing success message
                                                                setTimeout(() => location.reload(), 750);
                                                            } else {
                                                                throw new Error(data.message || 'Failed to update status');
                                                            }
                                                        })
                                                        .catch(error => {
                                                            Swal.fire({
                                                                icon: 'error',
                                                                title: 'Error',
                                                                text: `Failed to update status: ${error.message}`,
                                                                confirmButtonColor: '#d33'
                                                            });
                                                        });
                                                }
                                            });
                                        });
                                    });
                                });

                                function deleteModerator(moderatorId) {
                                    Swal.fire({
                                        title: "Are you sure?",
                                        text: "You won't be able to undo this action!",
                                        icon: "warning",
                                        showCancelButton: true,
                                        confirmButtonColor: "#d33",
                                        cancelButtonColor: "#3085d6",
                                        confirmButtonText: "Yes, delete it!"
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            fetch('processes/moderators/delete_moderator.php', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/x-www-form-urlencoded'
                                                    },
                                                    body: `id=${moderatorId}`
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.success) {
                                                        Swal.fire({
                                                            title: "Deleted!",
                                                            text: "The moderator has been deleted.",
                                                            icon: "success",
                                                            timer: 1500,
                                                            showConfirmButton: false
                                                        }).then(() => {
                                                            location.reload();
                                                        });
                                                    } else {
                                                        Swal.fire("Error!", data.message, "error");
                                                        console.error("Error:", data.message);
                                                    }
                                                })
                                                .catch(error => {
                                                    Swal.fire("Error!", "Something went wrong.", "error");
                                                    console.error("Error:", error);
                                                });
                                        }
                                    });
                                }
                            </script>

                            <script>
                                function fillEditModal(button) {
                                    const id = button.getAttribute('data-id');
                                    const name = button.getAttribute('data-name');
                                    const email = button.getAttribute('data-email');
                                    const gender = button.getAttribute('data-gender');
                                    const college = button.getAttribute('data-college');
                                    const department = button.getAttribute('data-department');
                                    const precinct = button.getAttribute('data-precinct');

                                    console.log('ID:', id);
                                    console.log('Name:', name);
                                    console.log('Email:', email);
                                    console.log('Gender:', gender);
                                    console.log('College:', college);
                                    console.log('Department:', department);
                                    console.log('Precinct:', precinct);

                                    const idField = document.getElementById('edit_moderator_id');
                                    const nameField = document.getElementById('edit_name');
                                    const emailField = document.getElementById('edit_email');
                                    const prevEmailField = document.getElementById('edit_email_prev');
                                    const genderField = document.getElementById('edit_gender');
                                    const collegeField = document.getElementById('edit_college');
                                    const departmentField = document.getElementById('edit_department');
                                    const precinctField = document.getElementById('edit_precinct');
                                    const passwordField = document.getElementById('edit_password');
                                    const strengthBar = document.getElementById('strength-bar');
                                    const strengthText = document.getElementById('strength-text');

                                    if (idField) idField.value = id;
                                    if (nameField) nameField.value = name;
                                    if (emailField) emailField.value = email;
                                    if (prevEmailField) prevEmailField.value = email;
                                    if (genderField) genderField.value = gender;
                                    if (collegeField) collegeField.value = college;
                                    if (departmentField) departmentField.value = department || '';
                                    if (precinctField) precinctField.value = precinct || 'No Precinct';
                                    if (passwordField) passwordField.value = '';
                                    if (strengthBar) strengthBar.style.display = 'none';
                                    if (strengthText) strengthText.innerText = '';

                                    document.getElementById('current_college').innerText = college || 'Not set';
                                    document.getElementById('current_department').innerText = department || 'Not set';
                                    document.getElementById('current_precinct').innerText = precinct || 'No Precinct';
                                }

                                function togglePasswordEdit() {
                                    const passwordField = document.getElementById('edit_password');
                                    const to