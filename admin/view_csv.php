<?php
session_start();
include('includes/conn.php');
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = "NON_ADMIN";
    header("Location: ../index.php");
    exit();
}

// Get and sanitize parameters
$filename = isset($_GET['file']) ? basename($_GET['file']) : '';
$adviser_email = isset($_GET['email']) ? filter_var($_GET['email'], FILTER_SANITIZE_EMAIL) : '';
$file_path = '../adviser/processes/voters/uploads/' . $filename;



// Query adviser and import details
try {
    $stmt = $pdo->prepare("
        SELECT 
            a.full_name,
            aid.date,
            aid.status,
            aid.voters_added,
            aid.emails_sent
        FROM 
            advisers a
        JOIN 
            adviser_import_details aid
        ON 
            a.email = aid.adviser_email
        WHERE 
            aid.file = ? AND aid.adviser_email = ?
    ");
    $stmt->execute([$filename, $adviser_email]);
    $import_details = $stmt->fetch();
    if (!$import_details) {
        http_response_code(404);
        die("Import details not found");
    }
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

// Read CSV file
$rows = [];
if (($handle = fopen($file_path, 'r')) !== false) {
    while (($data = fgetcsv($handle)) !== false) {
        $rows[] = array_map('htmlspecialchars', $data);
    }
    fclose($handle);
}

$header = !empty($rows) ? array_shift($rows) : [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU i-Elect Admin | Voter List </title>
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
        href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="js/select.dataTables.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="images/favicon.png" />

    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

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
            class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
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
                    <li class="nav-item dropdown">
                    </li>
                    <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                        <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <img class="img-xs rounded-circle logo" src="images/wmsu-logo.png" style="background-color: white;"
                                alt="Profile image"> </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                            <div class="dropdown-header text-center">
                                <img class="img-xs rounded-circle logoe" src="images/wmsu-logo.png" alt="Profile image">
                                <p class="mb-1 mt-3 font-weight-semibold"><?php echo $admin_full_name ?></p>
                                <p class="fw-light text-muted mb-0"><?php echo $admin_email ?></p>
                                <p class="fw-light text-muted mb-0"><?php echo $admin_phone_number ?></p>
                            </div>
                            <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-pen text-primary me-2"></i>Edit Account
                                Details</a>
                            <a class="dropdown-item" href="processes/accounts/logout.php"><i
                                    class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign
                                Out</a>
                        </div>
                    </li>
                </ul>
                <button
                    class="navbar-toggler navbar-toggler-right d-lg-none align-self-center"
                    type="button" data-bs-toggle="offcanvas">
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
                                                aria-selected="true">Voters</a>
                                        </li>

                                    </ul>

                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview"
                                        role="tabpanel" aria-labelledby="overview">
                                        <div class="card">
                                            <div class="card-body">
                                              
                                                    <div class="container-fluid mt-4">
                                                          <a href="voter-list.php" class="btn btn-primary mt-3 mb-3 text-white">
                                                            <span class="mdi mdi-arrow-left-circle"></span>
                                                          Back to Advisers</a>
                                                        <h2 class="mb-3">Imported Students from <?php echo htmlspecialchars($filename); ?></h2>
                                                        <div class="card mb-5">
                                                            <div class="card-body">
                                                                <h4>Import Details</h4>
                                                                <p><strong>Adviser:</strong> <?php echo htmlspecialchars($import_details['full_name']); ?></p>
                                                                <p><strong>Date:</strong> <?php echo htmlspecialchars($import_details['date'] ?? 'N/A'); ?></p>
                                                                <p><strong>Status:</strong> <?php echo htmlspecialchars($import_details['status'] ?? 'N/A'); ?></p>
                                                                <p><strong>Voters Added:</strong> <?php echo htmlspecialchars($import_details['voters_added'] ?? '0'); ?></p>
                                                                <p><strong>Emails Sent:</strong> <?php echo htmlspecialchars($import_details['emails_sent'] ?? '0'); ?></p>
                                                            </div>
                                                        </div>
                                                        <?php if (!empty($header)): ?>
                                                            <div class="table-container table-responsive">
                                                                <table id="studentsTable" class="table table-bordered">
                                                                    <thead>
                                                                        <tr>
                                                                            <?php foreach ($header as $col): ?>
                                                                                <th><?php echo htmlspecialchars($col); ?></th>
                                                                            <?php endforeach; ?>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($rows as $row): ?>
                                                                            <tr>
                                                                                <?php foreach ($row as $cell): ?>
                                                                                    <td><?php echo htmlspecialchars($cell); ?></td>
                                                                                <?php endforeach; ?>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="alert alert-warning" role="alert">
                                                                No student data found in the CSV file.
                                                            </div>
                                                        <?php endif; ?>
                                                      
                                                    </div>
                                            
                                            </div>
                                        </div>

                                    </div>
                                    <!-- main-panel ends -->
                                </div>
                                <!-- page-body-wrapper ends -->
                            </div>
                            <!-- container-scroller -->

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
                                document.addEventListener("DOMContentLoaded", function() {
                                    // Trigger file input click when button is clicked
                                    document.getElementById('importVotersButton').addEventListener('click', function() {
                                        document.getElementById('fileInput').click();
                                    });

                                    // Handle file selection and form submission
                                    document.getElementById('fileInput').addEventListener('change', function(event) {
                                        const form = document.getElementById('importVotersForm');
                                        const formData = new FormData(form);

                                        Swal.fire({
                                            title: "Importing Voters...",
                                            html: `
    <p>Please wait while the file is being processed.</p>
    <p style="margin-top: 10px;">
      Whilst data is importing, you can still proceed to work on other important matters by 
      <a href="index.php" style="color: #3085d6; text-decoration: underline;" target="_blank">clicking this link</a>!
    </p>
  `,
                                            icon: "info",
                                            allowOutsideClick: false,
                                            showConfirmButton: false,
                                            didOpen: () => {
                                                Swal.showLoading();
                                            }
                                        });

                                        // Use Fetch API instead of XMLHttpRequest for modern approach
                                        fetch('processes/voters/import_voters.php', {
                                                method: 'POST',
                                                body: formData
                                            })
                                            .then(response => {
                                                if (!response.ok) {
                                                    throw new Error('Network response was not ok');
                                                }
                                                return response.text(); // Get raw text response from PHP
                                            })
                                            .then(data => {
                                                Swal.close(); // Close loading alert

                                                // Check if response indicates success (customize based on your PHP output)
                                                if (data.includes('successfully')) {
                                                    Swal.fire({
                                                        title: "Success",
                                                        text: data, // Use the PHP response text
                                                        icon: "success",
                                                        confirmButtonText: "OK"
                                                    }).then((result) => {
                                                        if (result.isConfirmed) {
                                                            location.reload(); // Refresh the page
                                                        }
                                                    });
                                                } else {
                                                    Swal.fire({
                                                        title: "Error",
                                                        text: data || "Error importing voters.",
                                                        icon: "error",
                                                        confirmButtonText: "Try Again"
                                                    });
                                                }
                                            })
                                            .catch(error => {
                                                Swal.close(); // Close loading alert on error
                                                console.error('Fetch error:', error);
                                                Swal.fire({
                                                    title: "Network Error",
                                                    text: "Could not complete the request. Please try again.",
                                                    icon: "error",
                                                    confirmButtonText: "OK"
                                                });
                                            });
                                    });
                                });
                            </script>
                            <script>
                                $(document).ready(function() {
                                    // Delete Button Click
                                    $(document).on("click", ".deleteBtn", function() {
                                        let row = $(this).closest("tr");
                                        let name = row.find("td:eq(1)").text(); // Assuming 2nd column is the name
                                        let voterId = $(this).data("id"); // Get data-id from the button, not the row

                                        Swal.fire({
                                            title: "Are you sure?",
                                            text: `You are about to delete ${name} from the voter's list. This action cannot be undone!`,
                                            icon: "warning",
                                            showCancelButton: true,
                                            confirmButtonColor: "#d33",
                                            cancelButtonColor: "#3085d6",
                                            confirmButtonText: "Yes, delete it!",
                                            customClass: {
                                                popup: 'custom-swal-padding' // Custom class for modal padding
                                            }
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                // Make AJAX request to delete from database
                                                $.ajax({
                                                    url: "delete_voter.php", // PHP script to handle deletion
                                                    type: "POST",
                                                    data: {
                                                        id: voterId
                                                    }, // Send voter ID to PHP
                                                    dataType: "json",
                                                    success: function(response) {
                                                        if (response.success) {
                                                            // On success, remove the row from the UI
                                                            row.remove();
                                                            Swal.fire({
                                                                title: "Deleted!",
                                                                text: `${name} has been removed.`,
                                                                icon: "success",
                                                                customClass: {
                                                                    popup: 'custom-swal-padding'
                                                                }
                                                            });
                                                        } else {
                                                            Swal.fire({
                                                                title: "Error!",
                                                                text: response.message || "Failed to delete the voter.",
                                                                icon: "error",
                                                                customClass: {
                                                                    popup: 'custom-swal-padding'
                                                                }
                                                            });
                                                        }
                                                    },
                                                    error: function(xhr, status, error) {
                                                        Swal.fire({
                                                            title: "Error!",
                                                            text: "An error occurred while deleting the voter.",
                                                            icon: "error",
                                                            customClass: {
                                                                popup: 'custom-swal-padding'
                                                            }
                                                        });
                                                    }
                                                });
                                            }
                                        });
                                    });
                                });

                                $(document).ready(function() {
                                    // View Modal - Populate data
                                    $('.button-view-primary[data-bs-target="#viewModal"]').on('click', function() {
                                        const row = $(this).closest('tr');
                                        $('#viewStudentID').text(row.find('td:eq(0)').text());
                                        $('#viewEmail').text(row.find('td:eq(1)').text());
                                        $('#viewFirstName').text(row.find('td:eq(2)').text());
                                        $('#viewMiddleName').text(row.find('td:eq(3)').text());
                                        $('#viewLastName').text(row.find('td:eq(4)').text());
                                        $('#viewCollege').text(row.find('td:eq(5)').text());
                                        $('#viewCourse').text(row.find('td:eq(6)').text());
                                        $('#viewYearLevel').text(row.find('td:eq(7)').text());
                                    });




                                });
                            </script>

                            <!-- Include SheetJS library -->
                            <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
                            <!-- Include SweetAlert2 -->
                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

                        <script>
        $(document).ready(function() {
            $('#studentsTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                pageLength: 10,
                responsive: true
            });
        });
    </script>

                            <!-- DataTables CSS -->
                            <link rel="stylesheet"
                                href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

                            <!-- DataTables JS -->
                            <script
                                src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                            <script
                                src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


</body>


</html>