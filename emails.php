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
    <title>WMSU i-Elect Admin | Emails </title>
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
                                                aria-selected="true">Emails</a>
                                        </li>

                                    </ul>

                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview"
                                        role="tabpanel" aria-labelledby="overview">

                                        <div class="row mt-3">
                                            <div class="card px-5 pt-5">
                                                <div class="container-fluid">
                                                    <!-- Button trigger modal -->


                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <h3 class="mb-0"><b>Emails</b></h3>
                                                    <div class=" ms-auto" aria-hidden="true">
                                                        <button type="button" class="btn btn-primary text-white" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                                            <i class="bi bi-info-circle"></i> Instructions
                                                        </button>

                                                        <!-- Modal -->
                                                        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                            <div class="modal-dialog modal-lg"> <!-- Optional: larger size -->
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h1 class="modal-title fs-5" id="exampleModalLabel">How to Use Google SMTP with App Password</h1>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <ol class="list-group list-group-numbered">
                                                                            <li class="list-group-item">
                                                                                <strong>Enable 2-Step Verification</strong><br>
                                                                                <div class="col-sm-3 pt-2 pb-2">
                                                                                    <img src="images/Sharis Account.png" class="img-fluid">
                                                                                </div>
                                                                                Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">Google App Passwords</a>.<br>
                                                                                Sign in your <strong>Google Account</strong> or <b>create a new one </b> in order to complete the process.
                                                                            </li>

                                                                            <li class="list-group-item">
                                                                                =
                                                                                <strong>Generate an App Password</strong><br>
                                                                                <div class="row">
                                                                                    <div class="col-sm-3 pt-2 pb-2">
                                                                                        <img src="images/App Password - 0.png" class="img-fluid">
                                                                                    </div>
                                                                                    <div class="col-sm-3 pt-2 pb-2">
                                                                                        <img src="images/App Password Pt. 1.png" class="img-fluid">
                                                                                    </div>
                                                                                    <div class="col-sm-3 pt-2 pb-2">
                                                                                        <img src="images/App Password Pt 2.png" class="img-fluid">
                                                                                    </div>
                                                                                </div>
                                                                                After enabling 2FA, return to the <strong>"Security"</strong> page and click <strong>"App passwords"</strong>.<br>
                                                                                Choose <strong>Mail</strong> as the app and <strong>Other (Custom Name)</strong> as the device (e.g., "SMTP for App").<br>
                                                                                Click <strong>Generate</strong> and copy the 16-character password shown.
                                                                            </li>

                                                                            <li class="list-group-item">
                                                                                <strong>Done!</strong><br>
                                                                                Copy the email you've used or made in creating the SMTP and the app password!
                                                                                Your app should now send emails using Gmail securely via SMTP.
                                                                            </li>
                                                                        </ol>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button class="btn btn-primary text-white"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#addEmailModal">
                                                            <i class="bi bi-person-add"></i> Add Email
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table id="adviserTable" class="table table-striped table-bordered nowrap" style="width:100%">
                                                            <thead>
                                                                <tr>
                                                                    <th>Email</th>
                                                                    <th>Application Password</th>
                                                                    <th>Capacity</th>
                                                                    <th>Adviser Account Assigned</th>
                                                                    <th>Manage</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $stmt = $pdo->query("
    SELECT email.*, advisers.full_name 
    FROM email
    LEFT JOIN advisers ON email.adviser_id = advisers.id
    ORDER BY email.id DESC
");

                                                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                                    echo "<tr>
        <td>" . htmlspecialchars($row['email']) . "</td>
        <td>
            <div class='d-flex align-items-center gap-2'>
                <p class='mb-0 app-password' data-password='" . htmlspecialchars($row['app_password']) . "'>••••••••</p>
                <button type='button' class='btn btn-outline-secondary toggle-password'>Show</button>
            </div>
        </td>
        <td>" . htmlspecialchars($row['capacity']) . " / 500</td>
        <td>" . (!empty($row['full_name']) ? htmlspecialchars($row['full_name']) : "<i>Unassigned</i>") . "</td>
        <td>
            <a href='javascript:void(0);' 
               class='btn btn-danger btn-sm text-white delete-btn' 
               data-id='" . $row['id'] . "'>
                <i class='bi bi-trash'></i> Delete
            </a>
        </td>
    </tr>";
                                                                }

                                                                ?>
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

                            <div class="modal fade" id="addEmailModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Add New Email</h1>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" action="processes/emails/add.php" class="row g-3">
                                            <div class="modal-body">
                                                <div class="col-md-12">
                                                    <label for="emailInput" class="form-label">Email</label>
                                                    <input type="email" name="email" class="form-control w-100" id="emailInput" required>
                                                </div>
                                                <br>
                                                <div class="col-md-12">
                                                    <label for="appPasswordInput" class="form-label">App Password</label>
                                                    <input type="text" name="app_password" class="form-control w-100" id="appPasswordInput" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary" name="add">Save changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Ensure Bootstrap and jQuery are included -->
                            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                            <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
                            <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                            <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

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

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="ongoingElectionsModal" tabindex="-1" aria-labelledby="ongoingElectionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
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
</body>
<button class="back-to-top" id="backToTop" title="Go to top">
    <i class="mdi mdi-arrow-up"></i>
</button>

<script>
    $(document).ready(function() {
        $('#adviserTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: true,
            ordering: true
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".toggle-password").forEach(function(btn) {
            btn.addEventListener("click", function() {
                const p = this.parentElement.querySelector(".app-password");
                const realPassword = p.getAttribute("data-password");

                if (p.textContent === "••••••••") {
                    p.textContent = realPassword;
                    this.innerHTML = "Hide";
                } else {
                    p.textContent = "••••••••";
                    this.innerHTML = "Show";
                }
            });
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".delete-btn").forEach(function(button) {
            button.addEventListener("click", function() {
                const id = this.getAttribute("data-id");
                Swal.fire({
                    title: "Are you sure?",
                    text: "This action cannot be undone!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `processes/emails/delete.php?id=${id}`;
                    }
                });
            });
        });
    });
</script>




<?php

if (isset($_SESSION['STATUS'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function () {";

    if ($_SESSION['STATUS'] === 'EMAIL_EXISTS') {
        echo "Swal.fire({
            icon: 'error',
            title: 'Email Already Exists',
            text: 'Please use a different email.',
            confirmButtonColor: '#d33',
            padding: '2em' // Added padding here
        });";
    }

    if ($_SESSION['STATUS'] === 'EMAIL_SUCCESS_CREATED') {
        echo "Swal.fire({
            icon: 'success',
            title: 'Email Added',
            text: 'The email account was successfully created.',
            confirmButtonColor: '#3085d6',
            padding: '2em' // Added padding here
        });";
    }

    if ($_SESSION['STATUS'] === 'EMAIL_SUCCESS_DELETED') {
        echo "Swal.fire({
                icon: 'success',
                title: 'Email Deleted',
                text: 'The email account was successfully deleted.',
                confirmButtonColor: '#3085d6',
                padding: '2em'
            });";
    }

    if ($_SESSION['STATUS'] === 'EMAIL_DELETE_FAILED') {
        echo "Swal.fire({
                icon: 'error',
                title: 'Delete Failed',
                text: 'There was an error deleting the email account.',
                confirmButtonColor: '#d33',
                padding: '2em'
            });";
    }

    echo "});
    </script>";

    unset($_SESSION['STATUS']); // Unset after showing the message
}
?>

</html>