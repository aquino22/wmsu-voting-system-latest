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


$voting_periods_stmt = $pdo->prepare("SELECT * FROM voting_periods");
$voting_periods_stmt->execute();
$vp = $voting_periods_stmt->fetch(PDO::FETCH_ASSOC);




?>


<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU i-Elect Admin | Advisers </title>
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
                            $stmt = $pdo->prepare("SELECT * FROM elections WHERE status = :status");
                            $stmt->execute(['status' => 'Ongoing']);
                            $ongoingElections = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if ($ongoingElections) {

                                // Show first election
                                $first = array_shift($ongoingElections);
                                echo "<b>Election: </b> " . $first['election_name'] . " | ";
                                echo "<b>Semester: </b> " . $first['semester'] . " | ";
                                echo "<b>School Year:</b> " . $first['school_year_start'] . " - " . $first['school_year_end'] . "<br>";

                                if ($ongoingElections) {
                                    echo '<div id="moreElections" style="display:none;">';
                                    foreach ($ongoingElections as $election) {
                                        echo "<b>Election: </b> " . $election['election_name'] . " | ";
                                        echo "<b>Semester:</b> " . $election['semester'] . " | ";
                                        echo "<b>School Year:</b> " . $election['school_year_start'] . " - " . $election['school_year_end'] . "<br><br>";
                                    }
                                    echo '</div>';
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
                                                aria-selected="true">Advisers</a>
                                        </li>

                                    </ul>

                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview"
                                        role="tabpanel" aria-labelledby="overview">

                                        <div class="row mt-3">
                                            <div class="card px-5 pt-5">
                                                <div class="d-flex align-items-center">
                                                    <h3 class="mb-0"><b>Advisers</b></h3>
                                                    <div class=" ms-auto" aria-hidden="true">

                                                        <?php
                                                        $disabled = (isset($vp) && isset($vp['status']) && $vp['status'] === 'Ongoing') ? 'disabled' : '';
                                                        ?>
                                                        <button
                                                            class="btn btn-primary text-white"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#addAdviserModal"
                                                            <?= $disabled ?>>
                                                            <i class="bi bi-person-add"></i> Add Adviser
                                                        </button>


                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table id="adviserTable" class="table table-striped table-bordered nowrap" style="width:100%">
                                                            <thead>
                                                                <tr>
                                                                    <th>Full Name</th>
                                                                    <th>Email</th>
                                                                    <th>Password</th>
                                                                    <th>Google SMTP Associated</th>
                                                                    <th>College</th>
                                                                    <th>Department</th>
                                                                    <th>Year Level </th>
                                                                    <th>WMSU Campus Assigned</th>
                                                                    <th>WMSU ESU Campus Location</th>
                                                                    <th>School Year</th>
                                                                    <th>Semester</th>

                                                                    <th>Manage</th>

                                                            </thead>
                                                            </tr>

                                                            <tbody>

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

                            <?php
                            $stmt = $pdo->prepare("SELECT id, email FROM email ORDER BY email ASC");
                            $stmt->execute();
                            $availableEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            $stmt = $pdo->prepare("SELECT id, email FROM email WHERE status !='taken' ORDER BY email ASC");
                            $stmt->execute();
                            $chosenAndAvailableEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>

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
                                                    <div class="row">
                                                        <div class="col-md-2">
                                                            <label for="firstName" class="form-label">First Name <span style="color:red">*</span></label>
                                                            <input type="text" class="form-control" id="firstName" name="firstName" required>
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label for="middleName" class="form-label">Middle Name</label>
                                                            <input type="text" class="form-control" id="middleName" name="middleName" required>
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label for="lastName" class="form-label">Last Name <span style="color:red">*</span></label>
                                                            <input type="text" class="form-control" id="lastName" name="lastName" required>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label for="emailInput" class="form-label">Email <span style="color:red">*</span></label>
                                                            <input type="email" class="form-control" id="emailInput" name="user_email" required>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label for="passwordInput" class="form-label">Password <span style="color:red">*</span></label>
                                                            <div class="input-group">
                                                                <input type="password" class="form-control" id="passwordInput" name="password" required>
                                                                <button class="btn btn-outline-secondary btn-sm" type="button" id="togglePassword">
                                                                    <i class="bi bi-eye fs-6" id="togglePasswordIcon"></i>
                                                                </button>
                                                            </div>
                                                        </div>



                                                        <div class="col-md-6">


                                                            <label for="emailSelect" class="form-label">Google SMTP Email <small>(Multiple selectable) <span style="color:red">*</span></small></label>
                                                            <select class="form-select" id="emailSelect" name="email_ids[]" multiple required>
                                                                <option value="" disabled selected>-- Choose an email --</option>
                                                                <?php foreach ($chosenAndAvailableEmails as $email): ?>
                                                                    <option value="<?= htmlspecialchars($email['id']) ?>">
                                                                        <?= htmlspecialchars($email['email']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>

                                                        <br> <br> <br>

                                                        <div class="col-md">
                                                            <label for="college" class="form-label">College <span style="color:red">*</span></label>
                                                            <select class="form-select" id="college" name="college" required>
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

                                                        <div class="col-md">
                                                            <label for="course" class="form-label">Course <span style="color:red">*</span></label>
                                                            <select class="form-select" id="course" name="course" required>
                                                                <option value="" disabled selected>Select Course</option>
                                                            </select>
                                                        </div>


                                                        <div class="col-md">
                                                            <label for="department" class="form-label">Department <span style="color:red">*</span></label>
                                                            <select class="form-select" id="department" name="department" required>
                                                                <option value="" disabled selected>Select Department</option>
                                                            </select>
                                                        </div>


                                                        <div class="col-md" id="major-container" style="display: none;">
                                                            <label for="major" class="form-label">Major <span style="color:red">*</span></label>
                                                            <select class="form-select" id="major" name="major">
                                                                <option value="" disabled selected>Select Major</option>
                                                            </select>
                                                        </div>

                                                        <div class="col-md">
                                                            <label for="edit_semester" class="form-label">Semester <span style="color:red">*</span></label>
                                                            <select class="form-select" id="new_semester" name="semester" required>
                                                                <option>Choose Semester</option>
                                                                <option value="1st Semester">1st Semester</option>
                                                                <option value="2nd Semester">2nd Semester</option>
                                                            </select>
                                                        </div>

                                                        <script>
                                                            const courseOptions = {
                                                                "College of Law": [{
                                                                    course: "Bachelor of Science in Law",
                                                                    dept: "Law"
                                                                }],

                                                                "College of Liberal Arts": [{
                                                                        course: "Bachelor of Science in Accountancy",
                                                                        dept: "Accountancy"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Arts in History",
                                                                        dept: "History"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Arts in English",
                                                                        dept: "English"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Arts in Political Science",
                                                                        dept: "Political Science"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Arts in Mass Communication",
                                                                        dept: "Mass Communication"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Economics",
                                                                        dept: "Economics"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Psychology",
                                                                        dept: "Psychology"
                                                                    },
                                                                ],

                                                                "College of Agriculture": [{
                                                                        course: "Bachelor of Science in Crop Science",
                                                                        dept: "Crop Science"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Animal Science",
                                                                        dept: "Animal Science"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Food Technology",
                                                                        dept: "Food Technology"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Agribusiness",
                                                                        dept: "Agribusiness"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Agricultural Technology",
                                                                        dept: "Agricultural Technology"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Agronomy",
                                                                        dept: "Agronomy"
                                                                    },
                                                                ],

                                                                "College of Computing Studies": [{
                                                                        course: "Bachelor of Science in Computer Science",
                                                                        dept: "Computer Science"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Information Technology",
                                                                        dept: "Information Technology"
                                                                    },
                                                                    {
                                                                        course: "Associate in Computer Technology",
                                                                        dept: "Computer Technology"
                                                                    }
                                                                ],

                                                                "College of Architecture": [{
                                                                    course: "Bachelor of Science in Architecture",
                                                                    dept: "Architecture"
                                                                }],

                                                                "College of Nursing": [{
                                                                    course: "Bachelor of Science in Nursing",
                                                                    dept: "Nursing"
                                                                }],

                                                                "College of Asian & Islamic Studies": [{
                                                                        course: "Bachelor of Science in Asian Studies",
                                                                        dept: "Asian Studies"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Islamic Studies",
                                                                        dept: "Islamic Studies"
                                                                    },
                                                                ],

                                                                "College of Home Economics": [{
                                                                        course: "Bachelor of Science in Home Economics",
                                                                        dept: "Home Economics"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Nutrition and Dietetics",
                                                                        dept: "Nutrition and Dietetics"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Hospitality Management",
                                                                        dept: "Hospitality Management"
                                                                    },
                                                                ],

                                                                "College of Public Administration & Development Studies": [{
                                                                    course: "Bachelor of Science in Public Administration",
                                                                    dept: "Public Administration"
                                                                }],

                                                                "College of Engineering": [{
                                                                        course: "Bachelor of Science in Civil Engineering",
                                                                        dept: "Civil Engineering"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Computer Engineering",
                                                                        dept: "Computer Engineering"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Electrical Engineering",
                                                                        dept: "Electrical Engineering"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Environmental Engineering",
                                                                        dept: "Environmental Engineering"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Geodetic Engineering",
                                                                        dept: "Geodetic Engineering"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Industrial Engineering",
                                                                        dept: "Industrial Engineering"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Mechanical Engineering",
                                                                        dept: "Mechanical Engineering"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Sanitary Engineering",
                                                                        dept: "Sanitary Engineering"
                                                                    }
                                                                ],

                                                                "College of Medicine": [{
                                                                    course: "Bachelor of Science in Medicine",
                                                                    dept: "Medicine"
                                                                }],

                                                                "College of Criminal Justice Education": [{
                                                                    course: "Bachelor of Science in Criminology",
                                                                    dept: "Criminology"
                                                                }],

                                                                "College of Sports Science & Physical Education": [{
                                                                    course: "Bachelor of Science in Physical Education",
                                                                    dept: "Physical Education"
                                                                }],

                                                                "College of Science and Mathematics": [{
                                                                        course: "Bachelor of Science in Biology",
                                                                        dept: "Biology"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Chemistry",
                                                                        dept: "Chemistry"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Mathematics",
                                                                        dept: "Mathematics"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Physics",
                                                                        dept: "Physics"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Statistics",
                                                                        dept: "Statistics"
                                                                    }
                                                                ],

                                                                "College of Social Work & Community Development": [{
                                                                        course: "Bachelor of Science in Social Work",
                                                                        dept: "Social Work"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Community Development",
                                                                        dept: "Community Development"
                                                                    },
                                                                ],

                                                                "College of Teacher Education": [{
                                                                        course: "Bachelor of Science in Culture and Arts Education",
                                                                        dept: "Culture and Arts Education"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Early Childhood Education",
                                                                        dept: "Early Childhood Education"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Elementary Education",
                                                                        dept: "Elementary Education"
                                                                    },
                                                                    {
                                                                        course: "Bachelor of Science in Secondary Education",
                                                                        dept: "Secondary Education"
                                                                    },
                                                                ],
                                                            };

                                                            // Major options
                                                            const educationMajors = [
                                                                "English",
                                                                "Filipino",
                                                                "Mathematics",
                                                                "Science",
                                                                "Social Studies"
                                                            ];

                                                            const massCommMajors = [
                                                                "Journalism",
                                                                "Broadcasting"
                                                            ];

                                                            // Initialize elements
                                                            const collegeSelect = document.getElementById("college");
                                                            const courseSelect = document.getElementById("course");
                                                            const deptSelect = document.getElementById("department");
                                                            const majorContainer = document.getElementById("major-container");
                                                            const majorSelect = document.getElementById("major");

                                                            // Update courses and departments when college changes
                                                            collegeSelect.addEventListener("change", function() {
                                                                const college = this.value;

                                                                // Clear current options
                                                                courseSelect.innerHTML = `<option value="" disabled selected>Select Course</option>`;
                                                                deptSelect.innerHTML = `<option value="" disabled selected>Select Department</option>`;
                                                                majorSelect.innerHTML = `<option value="" disabled selected>Select Major</option>`;
                                                                majorContainer.style.display = "none";

                                                                if (courseOptions[college]) {
                                                                    courseOptions[college].forEach(item => {
                                                                        const courseOpt = new Option(item.course, item.course);
                                                                        courseSelect.add(courseOpt);

                                                                        const deptOpt = new Option(item.dept, item.dept);
                                                                        deptSelect.add(deptOpt);
                                                                    });
                                                                }
                                                            });

                                                            // Single event listener for course changes that handles both department and major updates
                                                            courseSelect.addEventListener("change", function() {
                                                                const selectedCourse = this.value;
                                                                const college = collegeSelect.value;

                                                                // Update department
                                                                if (college && selectedCourse && courseOptions[college]) {
                                                                    const selectedOption = courseOptions[college].find(item => item.course === selectedCourse);
                                                                    if (selectedOption) {
                                                                        deptSelect.value = selectedOption.dept;
                                                                    }
                                                                }

                                                                // Update majors
                                                                majorSelect.innerHTML = '<option value="" disabled selected>Select Major</option>';
                                                                majorContainer.style.display = "none";

                                                                if (selectedCourse === "Bachelor of Science in Elementary Education" ||
                                                                    selectedCourse === "Bachelor of Science in Secondary Education") {
                                                                    educationMajors.forEach(major => {
                                                                        const option = new Option(major, major);
                                                                        majorSelect.add(option);
                                                                    });
                                                                    majorContainer.style.display = "block";
                                                                } else if (selectedCourse === "Bachelor of Arts in Mass Communication") {
                                                                    massCommMajors.forEach(major => {
                                                                        const option = new Option(major, major);
                                                                        majorSelect.add(option);
                                                                    });
                                                                    majorContainer.style.display = "block";
                                                                }
                                                            });
                                                        </script>
                                                        <div class="col-md">
                                                            <label for="external_campus" class="form-label">Year Level <span style="color:red">*</span></label>
                                                            <select class="form-select" id="year_level" name="year_level" required>
                                                                <option value="1">1st Year</option>
                                                                <option value="2">2nd Year</option>
                                                                <option value="3">3rd Year</option>
                                                                <option value="4">4th Year</option>

                                                            </select>
                                                        </div>

                                                        <div class="col-md">
                                                            <label for="school_year" class="form-label">School Year <span style="color:red">*</span></label>
                                                            <select class="form-select" id="school_year" name="school_year" required>
                                                                <?php
                                                                $currentYear = date('Y');
                                                                $currentMonth = date('n');

                                                                // Determine current school year (assume it starts in June)
                                                                $startYear = ($currentMonth >= 6) ? $currentYear : $currentYear - 1;
                                                                $schoolYear = $startYear . '-' . ($startYear + 1);
                                                                ?>
                                                                <option value="<?= $schoolYear ?>" selected><?= $schoolYear ?></option>
                                                            </select>
                                                        </div>


                                                    </div>

                                                    <br>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <label for="wmsu_campus" class="form-label">WMSU Campus <span style="color:red">*</span></label>
                                                            <select class="form-select" id="wmsu_campus" name="wmsu_campus" required>
                                                                <option value="" disabled selected>Select WMSU Campus</option>
                                                                <option value="Main Campus" selected>Main Campus</option>
                                                                <option value="WMSU ESU">ESU Campus</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="external_campus" class="form-label">WMSU ESU Campus Location</label>
                                                            <select class="form-select" id="external_campus" name="external_campus" required>
                                                                <option value="None">Leave as none if Main Campus</option>
                                                                <option value="WMSU Curuan">WMSU Curuan</option>
                                                                <option value="WMSU Imelda">WMSU Imelda</option>
                                                                <option value="WMSU Siay">WMSU Siay</option>
                                                                <option value="WMSU Naga">WMSU Naga</option>
                                                                <option value="WMSU Molave">WMSU Molave</option>
                                                                <option value="WMSU Diplahan">WMSU Diplahan</option>
                                                                <option value="WMSU Olutanga">WMSU Olutanga</option>
                                                                <option value="WMSU Malangas">WMSU Malangas</option>
                                                                <option value="WMSU Ipil">WMSU Ipil</option>
                                                                <option value="WMSU Mabuhay">WMSU Mabuhay</option>
                                                                <option value="WMSU Pagadian">WMSU Pagadian</option>
                                                                <option value="WMSU Tungawan">WMSU Tungawan</option>
                                                                <option value="WMSU Alicia">WMSU Alicia</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>




                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Associate</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="editAdviserModal" tabindex="-1" aria-labelledby="editAdviserModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <form action="processes/advisers/update.php" method="POST" id="editAdviserForm">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editAdviserModalLabel">Edit Adviser</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body">
                                                <input type="hidden" name="adviser_id" id="edit_adviser_id" value="<?= htmlspecialchars($adviser['id'] ?? '') ?>">

                                                <div class="row g-3">
                                                    <!-- Personal Information Fields -->
                                                    <div class="col-md-6">
                                                        <label for="edit_firstName" class="form-label">First Name <span style="color:red">*</span></label>
                                                        <input type="text" class="form-control" id="edit_firstName" name="firstName" value="<?= htmlspecialchars($adviser['first_name'] ?? '') ?>" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="edit_middleName" class="form-label">Middle Name</label>
                                                        <input type="text" class="form-control" id="edit_middleName" name="middleName" value="<?= htmlspecialchars($adviser['middle_name'] ?? '') ?>">
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="edit_lastName" class="form-label">Last Name <span style="color:red">*</span></label>
                                                        <input type="text" class="form-control" id="edit_lastName" name="lastName" value="<?= htmlspecialchars($adviser['last_name'] ?? '') ?>" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="edit_email" class="form-label">Email <span style="color:red">*</span></label>
                                                        <input type="email" class="form-control" id="edit_email" name="user_email" value="<?= htmlspecialchars($adviser['email'] ?? '') ?>" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="edit_password" class="form-label">Password <span style="color:red">*</span> <small>(Leave blank if unchanged) </small></label>
                                                        <div class="input-group">
                                                            <input type="password" class="form-control" id="edit_password" name="password">
                                                            <button class="btn btn-outline-secondary btn-sm" type="button" id="toggleEditPassword">
                                                                <i class="bi bi-eye fs-6" id="toggleEditPasswordIcon"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">


                                                        <label for="edit_email_ids" class="form-label">Google SMTP Email <small>(Multiple selectable and you can only add new ones) <span style="color:red">*</span></small></label>
                                                        <select class="form-select" id="edit_email_ids" name="email_ids[]" multiple>
                                                            <?php foreach ($availableEmails as $email): ?>
                                                                <option value="<?= htmlspecialchars($email['id']) ?>" <?= in_array($email['id'], $adviser['email_ids'] ?? []) ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($email['email']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <!-- Academic Information Fields -->
                                                    <div class="col-md">
                                                        <label for="edit_college" class="form-label">College <span style="color:red">*</span></label>
                                                        <select class="form-select" id="edit_college" name="college" required>
                                                            <option value="" disabled>Select College</option>
                                                            <?php
                                                            $colleges = [
                                                                'College of Law',
                                                                'College of Agriculture',
                                                                'College of Liberal Arts',
                                                                'College of Architecture',
                                                                'College of Nursing',
                                                                'College of Asian & Islamic Studies',
                                                                'College of Computing Studies',
                                                                'College of Forestry & Environmental Studies',
                                                                'College of Criminal Justice Education',
                                                                'College of Home Economics',
                                                                'College of Engineering',
                                                                'College of Medicine',
                                                                'College of Public Administration & Development Studies',
                                                                'College of Sports Science & Physical Education',
                                                                'College of Science and Mathematics',
                                                                'College of Social Work & Community Development',
                                                                'College of Teacher Education'
                                                            ];

                                                            foreach ($colleges as $collegeName) {
                                                                $selected = ($collegeName === ($adviser['college'] ?? '')) ? 'selected' : '';
                                                                echo "<option value=\"$collegeName\" $selected>$collegeName</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-md">
                                                        <label for="edit_department" class="form-label">Department <span style="color:red">*</span></label>
                                                        <select class="form-select" id="edit_department" name="department" required>
                                                            <option value="" disabled selected>Select Department</option>
                                                            <!-- Will be populated dynamically -->
                                                        </select>
                                                    </div>

                                                    <div class="col-md">
                                                        <label for="edit_major" class="form-label">Major <span style="color:red">*</span></label>
                                                        <select class="form-select" id="edit_major" name="major">
                                                            <option value="" selected>Select Major</option>
                                                            <!-- Will be populated dynamically -->
                                                        </select>
                                                    </div>
                                                    <div class="col-md">
                                                        <label for="edit_semester" class="form-label">Semester <span style="color:red">*</span></label>
                                                        <select class="form-select" id="edit_semester" name="semester" required>
                                                            <option value="1st Semester">1st Semester</option>
                                                            <option value="2nd Semester">2nd Semester</option>
                                                        </select>
                                                    </div>


                                                    <div class="col-md">
                                                        <label for="edit_year_level" class="form-label">Year Level <span style="color:red">*</span></label>
                                                        <select class="form-select" id="edit_year_level" name="year_level" required>
                                                            <?php
                                                            $levels = ['1' => '1st Year', '2' => '2nd Year', '3' => '3rd Year', '4' => '4th Year'];
                                                            foreach ($levels as $val => $label) {
                                                                $selected = ($adviser['year_level'] ?? '') == $val ? 'selected' : '';
                                                                echo "<option value=\"$val\">$label</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>


                                                    <div class="col-md">
                                                        <label for="edit_school_year" class="form-label">School Year <span style="color:red">*</span></label>
                                                        <select class="form-select" id="edit_school_year" name="school_year" required>
                                                            <?php
                                                            $currentYear = date('Y');
                                                            $currentMonth = date('n');
                                                            $startYear = ($currentMonth >= 6) ? $currentYear : $currentYear - 1;
                                                            $schoolYear = $startYear . '-' . ($startYear + 1);
                                                            ?>
                                                            <option value="<?= $schoolYear ?>" selected><?= $schoolYear ?></option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <br>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label for="edit_wmsu_campus" class="form-label">WMSU Campus <span style="color:red">*</span></label>
                                                        <select class="form-select" id="edit_wmsu_campus" name="wmsu_campus" required>
                                                            <option value="Main Campus" <?= ($adviser['wmsu_campus'] ?? '') === 'Main Campus' ? 'selected' : '' ?>>Main Campus</option>
                                                            <option value="WMSU ESU" <?= ($adviser['wmsu_campus'] ?? '') === 'WMSU ESU' ? 'selected' : '' ?>>ESU Campus</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="edit_external_campus" class="form-label">WMSU ESU Campus Location</label>
                                                        <select class="form-select" id="edit_external_campus" name="external_campus" required>
                                                            <?php
                                                            $campuses = ['None', 'WMSU Curuan', 'WMSU Imelda', 'WMSU Siay', 'WMSU Naga', 'WMSU Molave', 'WMSU Diplahan', 'WMSU Olutanga', 'WMSU Malangas', 'WMSU Ipil', 'WMSU Mabuhay', 'WMSU Pagadian', 'WMSU Tungawan', 'WMSU Alicia'];
                                                            foreach ($campuses as $campus) {
                                                                $selected = ($adviser['external_campus'] ?? '') === $campus ? 'selected' : '';
                                                                echo "<option value=\"$campus\" $selected>$campus</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update Adviser</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <script>
                                $(document).ready(function() {
                                    // Departments and majors by college
                                    const departmentsByCollege = {
                                        'College of Law': {
                                            departments: ['Law'],
                                            majors: []
                                        },
                                        'College of Agriculture': {
                                            departments: ['Crop Science', 'Animal Science', 'Agriculture', 'Food Technology', 'Agribusiness', 'Agricultural Technology', 'Agronomy'],
                                            majors: []
                                        },
                                        'College of Liberal Arts': {
                                            departments: ['Accountancy', 'History', 'English', 'Political Science', 'Mass Communication', 'Economics', 'Psychology'],
                                            majors: {
                                                'Mass Communication': ['Journalism', 'Broadcasting']
                                            }
                                        },
                                        'College of Architecture': {
                                            departments: ['Architecture'],
                                            majors: []
                                        },
                                        'College of Nursing': {
                                            departments: ['Nursing'],
                                            majors: []
                                        },
                                        'College of Asian & Islamic Studies': {
                                            departments: ['Asian Studies', 'Islamic Studies'],
                                            majors: []
                                        },
                                        'College of Computing Studies': {
                                            departments: ['Computer Science', 'Information Technology', 'Computer Technology'],
                                            majors: []
                                        },
                                        'College of Forestry & Environmental Studies': {
                                            departments: ['Forestry', 'Agroforestry', 'Environmental Science'],
                                            majors: []
                                        },
                                        'College of Criminal Justice Education': {
                                            departments: ['Criminology'],
                                            majors: []
                                        },
                                        'College of Home Economics': {
                                            departments: ['Home Economics', 'Nutrition and Dietetics', 'Hospitality Management'],
                                            majors: []
                                        },
                                        'College of Engineering': {
                                            departments: ['Civil Engineering', 'Computer Engineering', 'Electrical Engineering', 'Electronics Engineering', 'Environmental Engineering', 'Geodetic Engineering', 'Industrial Engineering', 'Mechanical Engineering', 'Sanitary Engineering'],
                                            majors: []
                                        },
                                        'College of Medicine': {
                                            departments: ['Medicine'],
                                            majors: []
                                        },
                                        'College of Public Administration & Development Studies': {
                                            departments: ['Public Administration'],
                                            majors: []
                                        },
                                        'College of Sports Science & Physical Education': {
                                            departments: ['Physical Education', 'Exercise and Sports Sciences'],
                                            majors: []
                                        },
                                        'College of Science and Mathematics': {
                                            departments: ['Biology', 'Chemistry', 'Mathematics', 'Physics', 'Statistics'],
                                            majors: []
                                        },
                                        'College of Social Work & Community Development': {
                                            departments: ['Social Work', 'Community Development'],
                                            majors: []
                                        },
                                        'College of Teacher Education': {
                                            departments: ['Culture and Arts Education', 'Early Childhood Education', 'Elementary Education', 'Secondary Education', 'Special Needs Education'],
                                            majors: {
                                                'Secondary Education': [
                                                    'English',
                                                    'Filipino',
                                                    'Mathematics',
                                                    'Science',
                                                    'Social Studies',
                                                    'Values Education'
                                                ]
                                            }
                                        }
                                    };

                                    // Initialize form elements
                                    const collegeSelect = $('#edit_college');
                                    const departmentSelect = $('#edit_department');
                                    const majorSelect = $('#edit_major');
                                    const majorContainer = $('#edit_major').closest('.col-md');

                                    // Function to populate departments based on selected college
                                    function populateDepartments(college) {
                                        departmentSelect.empty().append('<option value="" disabled selected>Select Department</option>');

                                        if (college && departmentsByCollege[college]) {
                                            departmentsByCollege[college].departments.forEach(dept => {
                                                departmentSelect.append(`<option value="${dept}">${dept}</option>`);
                                            });
                                        }
                                    }

                                    // Function to populate majors based on selected department
                                    function populateMajors(college, department) {
                                        majorSelect.empty().append('<option value="">Select Major</option>');

                                        if (college && department && departmentsByCollege[college]?.majors?.[department]) {
                                            departmentsByCollege[college].majors[department].forEach(major => {
                                                majorSelect.append(`<option value="${major}">${major}</option>`);
                                            });
                                            majorContainer.show();
                                        } else {
                                            majorContainer.hide();
                                        }
                                    }

                                    // When college changes, update departments and reset majors
                                    collegeSelect.on('change', function() {
                                        const selectedCollege = $(this).val();
                                        populateDepartments(selectedCollege);
                                        majorSelect.empty().append('<option value="">Select Major</option>');
                                        majorContainer.hide();
                                    });

                                    // When department changes, update majors if applicable
                                    departmentSelect.on('change', function() {
                                        const selectedCollege = collegeSelect.val();
                                        const selectedDepartment = $(this).val();
                                        populateMajors(selectedCollege, selectedDepartment);
                                    });

                                    // When modal is shown, initialize the department and major based on existing values
                                    $('#editAdviserModal').on('show.bs.modal', function() {
                                        const currentCollege = collegeSelect.val();
                                        const currentDepartment = departmentSelect.val();
                                        const currentMajor = majorSelect.val();

                                        if (currentCollege) {
                                            populateDepartments(currentCollege);

                                            // Set the department value after populating options
                                            setTimeout(() => {
                                                if (currentDepartment) {
                                                    departmentSelect.val(currentDepartment).trigger('change');

                                                    // Set the major value after populating options
                                                    setTimeout(() => {
                                                        if (currentMajor) {
                                                            majorSelect.val(currentMajor);
                                                        }
                                                    }, 100);
                                                }
                                            }, 100);
                                        }
                                    });

                                    // Update selected emails display
                                    function updateSelectedEmailsDisplay() {
                                        const selectedEmails = $('#edit_email_ids option:selected')
                                            .map(function() {
                                                return $(this).text().trim();
                                            })
                                            .get()
                                            .join(', ');
                                        $('#selectedEmailsDisplay').text(selectedEmails || 'None selected');
                                    }

                                    // Initialize email display
                                    updateSelectedEmailsDisplay();
                                    $('#edit_email_ids').on('change', updateSelectedEmailsDisplay);

                                    // Password toggle
                                    $('#toggleEditPassword').on('click', function() {
                                        const passwordField = $('#edit_password');
                                        const icon = $('#toggleEditPasswordIcon');

                                        if (passwordField.attr('type') === 'password') {
                                            passwordField.attr('type', 'text');
                                            icon.removeClass('bi-eye').addClass('bi-eye-slash');
                                        } else {
                                            passwordField.attr('type', 'password');
                                            icon.removeClass('bi-eye-slash').addClass('bi-eye');
                                        }
                                    });

                                    // Form submission
                                    $('#editAdviserForm').on('submit', function(e) {
                                        e.preventDefault();

                                        const formData = $(this).serialize();
                                        const selectedEmails = $('#edit_email_ids option:selected').map(function() {
                                            return $(this).text();
                                        }).get().join(', ');
                                        const selectedDepartment = $('#edit_department option:selected').text();

                                        Swal.fire({
                                            title: 'Confirm Changes',
                                            html: `Save changes for adviser with emails: <strong>${selectedEmails || 'None'}</strong> and department: <strong>${selectedDepartment || 'None'}</strong>?`,
                                            icon: 'question',
                                            showCancelButton: true,
                                            confirmButtonColor: '#3085d6',
                                            cancelButtonColor: '#6c757d',
                                            confirmButtonText: 'Yes, save changes!'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                $.ajax({
                                                    url: 'processes/advisers/update.php',
                                                    type: 'POST',
                                                    data: formData,
                                                    dataType: 'json',
                                                    success: function(response) {
                                                        if (response && response.status === 'success') {
                                                            Swal.fire({
                                                                icon: 'success',
                                                                title: 'Adviser Updated!',
                                                                html: `Adviser updated with emails: <strong>${selectedEmails || 'None'}</strong> and department: <strong>${selectedDepartment || 'None'}</strong>`,
                                                                timer: 2000,
                                                                showConfirmButton: false
                                                            }).then(() => {
                                                                $('#editAdviserModal').modal('hide');
                                                                location.reload();
                                                            });
                                                        } else {
                                                            Swal.fire({
                                                                icon: 'error',
                                                                title: 'Update Failed',
                                                                text: response.message || 'There was an error updating the adviser.'
                                                            });
                                                        }
                                                    },
                                                    error: function(xhr, status, error) {
                                                        console.log('AJAX Error:', status, error, xhr.responseText);
                                                        Swal.fire({
                                                            icon: 'error',
                                                            title: 'Error',
                                                            text: 'Failed to connect to the server: ' + error
                                                        });
                                                    }
                                                });
                                            }
                                        });
                                    });
                                });

                                // Delete functionality remains the same
                                $(document).on('click', '.deleteBtn', function() {
                                    const $button = $(this);
                                    const id = $button.data('id');

                                    Swal.fire({
                                        title: 'Are you sure?',
                                        text: 'This adviser will be deleted permanently.',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonColor: '#d33',
                                        cancelButtonColor: '#6c757d',
                                        confirmButtonText: 'Yes, delete it!'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            $button.prop('disabled', true);
                                            $.ajax({
                                                url: 'processes/advisers/delete.php',
                                                type: 'POST',
                                                data: {
                                                    id
                                                },
                                                dataType: 'json',
                                                success: function(response) {
                                                    if (response && response.status === 'success') {
                                                        Swal.fire({
                                                            icon: 'success',
                                                            title: 'Deleted!',
                                                            text: 'Adviser deleted successfully.',
                                                            timer: 1500,
                                                            showConfirmButton: false
                                                        }).then(() => {
                                                            location.reload();
                                                        });
                                                    } else {
                                                        Swal.fire({
                                                            icon: 'error',
                                                            title: 'Delete Failed',
                                                            text: response.message || 'There was an error deleting the adviser.'
                                                        });
                                                    }
                                                },
                                                error: function() {
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Error',
                                                        text: 'Failed to connect to the server.'
                                                    });
                                                },
                                                complete: function() {
                                                    $button.prop('disabled', false);
                                                }
                                            });
                                        }
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
                                        "info": true
                                    });
                                });
                            </script>



                            <!-- jQuery (Required) -->
                            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>




                            <!-- DataTables CSS -->
                            <link rel="stylesheet"
                                href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

                            <!-- DataTables JS -->
                            <script
                                src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                            <script
                                src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


                            <script>
                                $(document).ready(function() {
                                    // Consolidated departments and majors data structure
                                    const departmentsByCollege = {
                                        'College of Law': {
                                            departments: ['Law'],
                                            majors: []
                                        },
                                        'College of Agriculture': {
                                            departments: ['Crop Science', 'Animal Science', 'Agriculture', 'Food Technology', 'Agribusiness', 'Agricultural Technology', 'Agronomy'],
                                            majors: []
                                        },
                                        'College of Liberal Arts': {
                                            departments: ['Accountancy', 'History', 'English', 'Political Science', 'Mass Communication', 'Economics', 'Psychology'],
                                            majors: {
                                                'Mass Communication': ['Journalism', 'Broadcasting']
                                            }
                                        },
                                        'College of Architecture': {
                                            departments: ['Architecture'],
                                            majors: []
                                        },
                                        'College of Nursing': {
                                            departments: ['Nursing'],
                                            majors: []
                                        },
                                        'College of Asian & Islamic Studies': {
                                            departments: ['Asian Studies', 'Islamic Studies'],
                                            majors: []
                                        },
                                        'College of Computing Studies': {
                                            departments: ['Computer Science', 'Information Technology', 'Computer Technology'],
                                            majors: []
                                        },
                                        'College of Forestry & Environmental Studies': {
                                            departments: ['Forestry', 'Agroforestry', 'Environmental Science'],
                                            majors: []
                                        },
                                        'College of Criminal Justice Education': {
                                            departments: ['Criminology'],
                                            majors: []
                                        },
                                        'College of Home Economics': {
                                            departments: ['Home Economics', 'Nutrition and Dietetics', 'Hospitality Management'],
                                            majors: []
                                        },
                                        'College of Engineering': {
                                            departments: ['Civil Engineering', 'Computer Engineering', 'Electrical Engineering', 'Electronics Engineering', 'Environmental Engineering', 'Geodetic Engineering', 'Industrial Engineering', 'Mechanical Engineering', 'Sanitary Engineering'],
                                            majors: []
                                        },
                                        'College of Medicine': {
                                            departments: ['Medicine'],
                                            majors: []
                                        },
                                        'College of Public Administration & Development Studies': {
                                            departments: ['Public Administration'],
                                            majors: []
                                        },
                                        'College of Sports Science & Physical Education': {
                                            departments: ['Physical Education', 'Exercise and Sports Sciences'],
                                            majors: []
                                        },
                                        'College of Science and Mathematics': {
                                            departments: ['Biology', 'Chemistry', 'Mathematics', 'Physics', 'Statistics'],
                                            majors: []
                                        },
                                        'College of Social Work & Community Development': {
                                            departments: ['Social Work', 'Community Development'],
                                            majors: []
                                        },
                                        'College of Teacher Education': {
                                            departments: ['Culture and Arts Education', 'Early Childhood Education', 'Elementary Education', 'Secondary Education', 'Special Needs Education'],
                                            majors: {
                                                'Secondary Education': [
                                                    'English',
                                                    'Filipino',
                                                    'Mathematics',
                                                    'Science',
                                                    'Social Studies',
                                                    'Values Education'
                                                ]
                                            }
                                        }
                                    };

                                    // Initialize form elements
                                    const collegeSelect = $('#edit_college');
                                    const departmentSelect = $('#edit_department');
                                    const majorSelect = $('#edit_major');
                                    const majorContainer = $('#edit_major').closest('.col-md');

                                    // Function to populate departments based on selected college
                                    function populateDepartments(college) {
                                        departmentSelect.empty().append('<option value="" disabled selected>Select Department</option>');

                                        if (college && departmentsByCollege[college]) {
                                            departmentsByCollege[college].departments.forEach(dept => {
                                                departmentSelect.append(`<option value="${dept}">${dept}</option>`);
                                            });
                                        }
                                    }

                                    // Function to populate majors based on selected department
                                    function populateMajors(college, department) {
                                        majorSelect.empty().append('<option value="">Select Major</option>');

                                        if (college && department && departmentsByCollege[college]?.majors?.[department]) {
                                            departmentsByCollege[college].majors[department].forEach(major => {
                                                majorSelect.append(`<option value="${major}">${major}</option>`);
                                            });
                                            majorContainer.show();
                                        } else {
                                            majorContainer.hide();
                                        }
                                    }

                                    // When college changes, update departments and reset majors
                                    collegeSelect.on('change', function() {
                                        const selectedCollege = $(this).val();
                                        populateDepartments(selectedCollege);
                                        majorSelect.empty().append('<option value="">Select Major</option>');
                                        majorContainer.hide();
                                    });

                                    // When department changes, update majors if applicable
                                    departmentSelect.on('change', function() {
                                        const selectedCollege = collegeSelect.val();
                                        const selectedDepartment = $(this).val();
                                        populateMajors(selectedCollege, selectedDepartment);
                                    });

                                    // When modal is shown, initialize the department and major based on existing values
                                    $('#editAdviserModal').on('show.bs.modal', function() {
                                        const currentCollege = collegeSelect.val();
                                        const currentDepartment = "<?= htmlspecialchars($adviser['department'] ?? '') ?>";
                                        const currentMajor = "<?= htmlspecialchars($adviser['major'] ?? '') ?>";

                                        if (currentCollege) {
                                            populateDepartments(currentCollege);

                                            // Set the department value after populating options
                                            setTimeout(() => {
                                                if (currentDepartment) {
                                                    departmentSelect.val(currentDepartment).trigger('change');

                                                    // Set the major value after populating options
                                                    setTimeout(() => {
                                                        if (currentMajor) {
                                                            majorSelect.val(currentMajor);
                                                        }
                                                    }, 100);
                                                }
                                            }, 100);
                                        }
                                    });

                                    // Update selected emails display
                                    function updateSelectedEmailsDisplay() {
                                        const selectedEmails = $('#edit_email_ids option:selected')
                                            .map(function() {
                                                return $(this).text().trim();
                                            })
                                            .get()
                                            .join(', ');
                                        $('#selectedEmailsDisplay').text(selectedEmails || 'None selected');
                                    }

                                    // Initialize email display
                                    updateSelectedEmailsDisplay();
                                    $('#edit_email_ids').on('change', updateSelectedEmailsDisplay);

                                    // Password toggle
                                    $('#toggleEditPassword').on('click', function() {
                                        const passwordField = $('#edit_password');
                                        const icon = $('#toggleEditPasswordIcon');

                                        if (passwordField.attr('type') === 'password') {
                                            passwordField.attr('type', 'text');
                                            icon.removeClass('bi-eye').addClass('bi-eye-slash');
                                        } else {
                                            passwordField.attr('type', 'password');
                                            icon.removeClass('bi-eye-slash').addClass('bi-eye');
                                        }
                                    });



                                    // Delete functionality
                                    $(document).on('click', '.deleteBtn', function() {
                                        const $button = $(this);
                                        const id = $button.data('id');

                                        Swal.fire({
                                            title: 'Are you sure?',
                                            text: 'This adviser will be deleted permanently.',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#d33',
                                            cancelButtonColor: '#6c757d',
                                            confirmButtonText: 'Yes, delete it!'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                $button.prop('disabled', true);
                                                $.ajax({
                                                    url: 'processes/advisers/delete.php',
                                                    type: 'POST',
                                                    data: {
                                                        id
                                                    },
                                                    dataType: 'json',
                                                    success: function(response) {
                                                        if (response && response.status === 'success') {
                                                            Swal.fire({
                                                                icon: 'success',
                                                                title: 'Deleted!',
                                                                text: 'Adviser deleted successfully.',
                                                                timer: 1500,
                                                                showConfirmButton: false
                                                            }).then(() => {
                                                                location.reload();
                                                            });
                                                        } else {
                                                            Swal.fire({
                                                                icon: 'error',
                                                                title: 'Delete Failed',
                                                                text: response.message || 'There was an error deleting the adviser.'
                                                            });
                                                        }
                                                    },
                                                    error: function() {
                                                        Swal.fire({
                                                            icon: 'error',
                                                            title: 'Error',
                                                            text: 'Failed to connect to the server.'
                                                        });
                                                    },
                                                    complete: function() {
                                                        $button.prop('disabled', false);
                                                    }
                                                });
                                            }
                                        });
                                    });
                                });

                                // Password toggle for other forms
                                document.getElementById('togglePassword').addEventListener('click', function() {
                                    const passwordInput = document.getElementById('passwordInput');
                                    const icon = document.getElementById('togglePasswordIcon');

                                    const isPasswordVisible = passwordInput.type === 'text';
                                    passwordInput.type = isPasswordVisible ? 'password' : 'text';

                                    icon.classList.toggle('bi-eye');
                                    icon.classList.toggle('bi-eye-slash');
                                });

                                document.getElementById('togglePassword2').addEventListener('click', function() {
                                    const passwordInput = document.getElementById('passwordInput2');
                                    const icon = document.getElementById('togglePasswordIcon2');
                                    const isVisible = passwordInput.type === 'text';

                                    passwordInput.type = isVisible ? 'password' : 'text';
                                    icon.classList.toggle('bi-eye');
                                    icon.classList.toggle('bi-eye-slash');
                                });
                            </script>








                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                            <script>
                                $(document).ready(function() {
                                    // Initialize DataTable and store reference
                                    const table = $('#adviserTable').DataTable({
                                        "ajax": "fetch_advisers.php",
                                        "columns": [{
                                                "data": "full_name"
                                            },
                                            {
                                                "data": "email"
                                            },
                                            {
                                                "data": "password",
                                                "render": () => '••••••••'
                                            },
                                            {
                                                "data": "smtp_email",
                                                "render": (data) => data || 'N/A'
                                            },
                                            {
                                                "data": "college"
                                            },
                                            {
                                                "data": "department"
                                            },
                                            {
                                                "data": "year",
                                                "render": function(data) {
                                                    const labels = {
                                                        1: '1st',
                                                        2: '2nd',
                                                        3: '3rd',
                                                        4: '4th'
                                                    };
                                                    return labels[data] || data || 'N/A';
                                                }
                                            },
                                            {
                                                "data": "wmsu_campus",
                                                "render": (data) => data || 'N/A'
                                            },
                                            {
                                                "data": "external_campus",
                                                "render": (data) => data || 'N/A'
                                            },
                                            {
                                                "data": "school_year"
                                            },
                                            {
                                                "data": "semester"
                                            },
                                            {
                                                "data": null,
                                                "render": function(data, type, row) {
                                                    let buttons = `<button class="btn btn-sm btn-primary editBtn text-white" data-id="${row.id}">
                        <i class='bi bi-pen'></i> Edit</button> `;

                                                    if (row.is_active == 1) {
                                                        buttons += `<button class="btn btn-sm btn-danger deactivateBtn text-white" data-id="${row.id}">
                            <i class='bi bi-x-circle'></i> Deactivate</button>`;
                                                    } else {
                                                        buttons += `<button class="btn btn-sm btn-success activateBtn text-white" data-id="${row.id}">
                            <i class='bi bi-check-circle'></i> Activate</button>`;
                                                    }

                                                    return buttons;
                                                }
                                            }
                                        ]
                                    });

                                    // Departments by college
                                    const departmentsByCollegeEditable = {
                                        'College of Law': ['Law'],
                                        'College of Agriculture': ['Crop Science', 'Animal Science', 'Agriculture', 'Food Technology', 'Agribusiness', 'Agricultural Technology', 'Agronomy'],
                                        'College of Liberal Arts': ['Accountancy', 'History', 'English', 'Political Science', 'Mass Communication - Journalism', 'Mass Communication - Broadcasting', 'Economics', 'Psychology'],
                                        'College of Architecture': ['Architecture'],
                                        'College of Nursing': ['Nursing'],
                                        'College of Asian & Islamic Studies': ['Asian Studies', 'Islamic Studies'],
                                        'College of Computing Studies': ['Computer Science', 'Information Technology', 'Computer Technology'],
                                        'College of Forestry & Environmental Studies': ['Forestry', 'Agroforestry', 'Environmental Science'],
                                        'College of Criminal Justice Education': ['Criminology'],
                                        'College of Home Economics': ['Home Economics', 'Nutrition and Dietetics', 'Hospitality Management'],
                                        'College of Engineering': ['Civil Engineering', 'Computer Engineering', 'Electrical Engineering', 'Electronics Engineering', 'Environmental Engineering', 'Geodetic Engineering', 'Industrial Engineering', 'Mechanical Engineering', 'Sanitary Engineering'],
                                        'College of Medicine': ['Medicine'],
                                        'College of Public Administration & Development Studies': ['Public Administration'],
                                        'College of Sports Science & Physical Education': ['Physical Education', 'Exercise and Sports Sciences'],
                                        'College of Science and Mathematics': ['Biology', 'Chemistry', 'Mathematics', 'Physics', 'Statistics'],
                                        'College of Social Work & Community Development': ['Social Work', 'Community Development'],
                                        'College of Teacher Education': [
                                            'Culture and Arts Education',
                                            'Early Childhood Education',
                                            'Elementary Education',
                                            'Secondary Education',
                                            'Secondary Education Major in English',
                                            'Secondary Education Major in Filipino',
                                            'Secondary Education Major in Mathematics',
                                            'Secondary Education Major in Sciences',
                                            'Secondary Education Major in Social Studies',
                                            'Secondary Education Major in Values Education',
                                            'Special Needs Education'
                                        ]
                                    };

                                    // Function to populate department options based on college
                                    function populateDepartments(college) {
                                        const departments = departmentsByCollegeEditable[college] || [];
                                        $('#edit_department').empty().append('<option value="">Select Department</option>');
                                        departments.forEach(dep => {
                                            $('#edit_department').append(
                                                `<option value="${dep}">${dep}</option>`
                                            );
                                        });
                                    }

                                    // Update department options when college changes
                                    $('#edit_college').on('change', function() {
                                        const selectedCollege = $(this).val();
                                        populateDepartments(selectedCollege);
                                    });

                                    // Function to update selected emails display
                                    function updateSelectedEmailsDisplay() {
                                        const selectedEmails = $('#edit_email_ids option:selected')
                                            .map(function() {
                                                return $(this).text().trim();
                                            })
                                            .get()
                                            .join(', ');
                                        $('#selectedEmailsDisplay').text(selectedEmails || 'None selected');
                                    }

                                    // Update email display when selection changes
                                    $('#edit_email_ids').on('change', updateSelectedEmailsDisplay);

                                    // Button event handlers
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

                                    $('#adviserTable tbody').on('click', '.editBtn', function() {
                                        const row = $(this).closest('tr');
                                        const data = table.row(row).data();
                                        openEditModal(data);
                                    });

                                    function confirmAction(title, text, confirmBtn, callback) {
                                        Swal.fire({
                                            title,
                                            text,
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#3085d6',
                                            cancelButtonColor: '#d33',
                                            confirmButtonText: confirmBtn
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                callback();
                                            }
                                        });
                                    }

                                    function handleResponse(response) {
                                        try {
                                            const data = typeof response === 'string' ? JSON.parse(response) : response;
                                            if (data.success) {
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Success',
                                                    text: data.message || 'Operation completed successfully.',
                                                    timer: 1500,
                                                    showConfirmButton: false
                                                }).then(() => {
                                                    table.ajax.reload(null, false); // Reload without resetting paging
                                                });
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

                                    function openEditModal(data) {
                                        // Reset form first
                                        $('#editAdviserForm')[0].reset();

                                        // Set basic fields
                                        $('#edit_adviser_id').val(data.id);
                                        $('#edit_firstName').val(data.first_name || '');
                                        $('#edit_middleName').val(data.middle_name || '');
                                        $('#edit_lastName').val(data.last_name || '');
                                        $('#edit_email').val(data.email || '');
                                        $('#edit_password').val(''); // Clear password field for security
                                        $('#edit_smtp_email').val(data.smtp_email || '');
                                        $('#edit_major').val(data.major || '');
                                        $('#edit_semester').val(data.semester || '')

                                        // Set college and department
                                        $('#edit_college').val(data.college || '').trigger('change');

                                        // Delay department selection to ensure options are populated
                                        setTimeout(() => {
                                            $('#edit_department').val(data.department || '').trigger('change');
                                        }, 300);

                                        // Parse email IDs
                                        let emailIds = [];
                                        if (data.selected_email_ids) {
                                            if (typeof data.selected_email_ids === 'string') {
                                                emailIds = data.selected_email_ids.split(',').map(id => id.trim());
                                            } else if (Array.isArray(data.selected_email_ids)) {
                                                emailIds = data.selected_email_ids;
                                            }
                                        }

                                        // Set other fields
                                        $('#edit_email_ids').val(emailIds).trigger('change');
                                        $('#edit_school_year').val(data.school_year || '');
                                        $('#edit_wmsu_campus').val(data.wmsu_campus || 'Main Campus');
                                        $('#edit_external_campus').val(data.external_campus || 'None');
                                        $('#edit_year_level').val(data.year_level || data.year || '');

                                        // Show modal
                                        $('#editAdviserModal').modal('show');
                                    }
                                });
                            </script>
                            <?php if (isset($_SESSION['STATUS'])): ?>
                                <script>
                                    <?php if ($_SESSION['STATUS'] === "ADVISER_ADDED_SUCCESSFULLY"): ?>
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success!',
                                            text: 'Adviser added successfully.',
                                            confirmButtonColor: '#3085d6',
                                            confirmButtonText: 'OK'
                                        });
                                    <?php elseif ($_SESSION['STATUS'] === "ADVISER_ADD_FAILED"): ?>
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error!',
                                            text: 'Failed to add adviser. Please try again.',
                                            confirmButtonColor: '#d33',
                                            confirmButtonText: 'OK'
                                        });
                                    <?php elseif ($_SESSION['STATUS'] === "USER_EMAIL_ALREADY_EXISTS"): ?>
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error!',
                                            text: 'Failed to add adviser. The email already exists.',
                                            confirmButtonColor: '#d33',
                                            confirmButtonText: 'OK'
                                        });
                                    <?php elseif ($_SESSION['STATUS'] === "ADVISER_ALREADY_EXISTS_FOR_COMBINATION"): ?>
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error!',
                                            text: 'Failed to add adviser. The adviser already exists.',
                                            confirmButtonColor: '#d33',
                                            confirmButtonText: 'OK'
                                        });
                                    <?php elseif ($_SESSION['STATUS'] === "ADVISER_UPDATED_SUCCESSFULLY"): ?>
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Updated!',
                                            text: 'Adviser information updated successfully.',
                                            confirmButtonColor: '#3085d6',
                                            confirmButtonText: 'OK'
                                        });
                                    <?php elseif ($_SESSION['STATUS'] === "ADVISER_UPDATE_FAILED"): ?>
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Update Failed',
                                            text: 'There was an error updating the adviser.',
                                            confirmButtonColor: '#d33',
                                            confirmButtonText: 'OK'
                                        });
                                    <?php elseif ($_SESSION['STATUS'] === "ADVISER_DELETED_SUCCESSFULLY"): ?>
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Deleted!',
                                            text: 'Adviser deleted successfully.',
                                            confirmButtonColor: '#3085d6',
                                            confirmButtonText: 'OK'
                                        });
                                    <?php elseif ($_SESSION['STATUS'] === "ADVISER_DELETE_FAILED"): ?>
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Delete Failed',
                                            text: 'There was an error deleting the adviser.',
                                            confirmButtonColor: '#d33',
                                            confirmButtonText: 'OK'
                                        });
                                    <?php endif; ?>
                                </script>
                                <?php unset($_SESSION['STATUS']); ?>
                            <?php endif; ?>

                            <div class="modal fade" id="ongoingElectionsModal" tabindex="-1" aria-labelledby="ongoingElectionsModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="ongoingElectionsModalLabel">Ongoing Elections</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php foreach ($ongoingElections as $election): ?>
                                                <p class="mb-2">
                                                    <b>Election:</b> <?= htmlspecialchars($election['election_name']) ?><br>
                                                    <b>Semester:</b> <?= htmlspecialchars($election['semester']) ?><br>
                                                    <b>School Year:</b> <?= htmlspecialchars($election['school_year_start']) ?> - <?= htmlspecialchars($election['school_year_end']) ?>
                                                </p>
                                                <hr>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button class="back-to-top" id="backToTop" title="Go to top">
                                <i class="mdi mdi-arrow-up"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>