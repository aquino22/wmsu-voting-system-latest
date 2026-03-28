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

<style>

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
          $stmt = $pdo->prepare("SELECT semester, school_year_start, school_year_end FROM elections WHERE status = :status");
          $stmt->execute(['status' => 'Ongoing']);

          $ongoingElections = $stmt->fetchAll(PDO::FETCH_ASSOC);

          if ($ongoingElections) {
            foreach ($ongoingElections as $election) {
              echo "<b>Semester: </b>" . $election['semester'] . " | ";
              echo "<b>School Year: </b>" . $election['school_year_start'] . " - " . $election['school_year_end'] . "<br><br>";
            }
          } 
          ?>
          </li>
           <li class="nav-item font-weight-semibold d-none d-lg-block ms-0">
           
             
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
                          <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                              <h3 class="mb-5 bold-text">Voters List
                                <small>
                                  <a href="" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                  (View By Adivser)
  </a>
                                  </button>
                                  </small>
                              </h3>

                              </small>

                              <div class="d-flex justify-content-between align-items-center mt-3">


                                <!-- Academic Year Filter -->
                                <select id="academicYearFilter" class="form-select form-select-sm w-auto">
                                  <option value="">All Years</option>
                                  <option value="1">1st Year</option>
                                  <option value="2">2nd Year</option>
                                  <option value="3">3rd Year</option>
                                  <option value="4">4th Year</option>
                                </select>
                              </div>
                            </div>


                            <!-- Academic Year Dropdown to Filter Table -->

                            <?php
                            require_once 'includes/conn.php'; // Ensure database connection

                            // Fetch voters from database
                            $query = "SELECT * FROM voters";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute();
                            $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>

                            <div class="table-responsive">
                              <table id="votersTable" class="table table-striped table-bordered nowrap" style="width:100%">
                                <thead>
                                  <tr>
                                    <th>Student ID</th>
                                    <th>Email</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th>Last Name</th>
                                    <th>College</th>
                                    <th>Course</th>
                                    <th>Department</th>
                                    <th>Year Level</th>
                                    <th>Manage</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php foreach ($voters as $voter): ?>
                                    <tr>
                                      <td><?= htmlspecialchars($voter['student_id']) ?></td>
                                      <td><?= htmlspecialchars($voter['email']) ?></td>
                                      <td><?= htmlspecialchars($voter['first_name']) ?></td>
                                      <td><?= htmlspecialchars($voter['middle_name']) ?></td>
                                      <td><?= htmlspecialchars($voter['last_name']) ?></td>
                                      <td><?= htmlspecialchars($voter['college']) ?></td>
                                      <td><?= htmlspecialchars($voter['course']) ?></td>
                                      <td><?= htmlspecialchars($voter['department']) ?></td>
                                      <td><?= htmlspecialchars($voter['year_level']) ?></td>
                                      <td>
                                        <button class="button-view-primary" data-bs-toggle="modal" data-bs-target="#viewModal">
                                          <i class="mdi mdi-eye"></i> View
                                        </button>
                                        <button class="button-view-danger deleteBtn" data-id="<?php echo $voter['id'] ?>">
                                          <i class="mdi mdi-delete"></i> Delete
                                        </button>
                                        <button class="button-view-primary sendQRBtn" data-email="<?= htmlspecialchars($voter['email']) ?>" data-student="<?= htmlspecialchars($voter['student_id']) ?>">
                                          <i class="mdi mdi-email"></i> Send QR
                                        </button>
                                      </td>
                                    </tr>
                                  <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                  <tr>
                                    <th><input type="text" class="form-control" placeholder="Search Student ID"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Email"></th>
                                    <th><input type="text" class="form-control" placeholder="Search First Name"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Middle Name"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Last Name"></th>
                                    <th>
                                      <select class="form-control" id="collegeFilter">
                                        <option value="">All Colleges</option>
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
                                    </th>
                                    <th><input type="text" class="form-control" placeholder="Search Course"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Department"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Year Level"></th>
                                    <th></th> <!-- Empty cell for Manage column -->
                                  </tr>
                                </tfoot>
                              </table>

                              <!-- Include DataTables CSS and JS (already in your original code) -->
                              <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
                              <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                              <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                              <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

                              <!-- DataTables Initialization and Column Filtering -->
                              <script>
                                $(document).ready(function() {
                                  // Initialize DataTables
                                  var table = $('#votersTable').DataTable({
                                    responsive: true,
                                    pageLength: 10,
                                    language: {
                                      search: "Filter records:"
                                    }


                                  });

                                  // Apply search functionality to footer inputs
                                  $('#votersTable tfoot th').each(function(index) {
                                    var title = $('#votersTable thead th').eq(index).text();
                                    // Only add input for searchable columns (skip Manage column)
                                    if (index < 9 && index != 5) { // Columns 0-4, 6-8 are text inputs
                                      $(this).html('<input type="text" class="form-control" placeholder="Search ' + title + '" />');
                                    }
                                  });

                                  // Apply text input searches
                                  table.columns([0, 1, 2, 3, 4, 6, 7, 8]).every(function() {
                                    var column = this;
                                    $('input', this.footer()).on('keyup change clear', function() {
                                      if (column.search() !== this.value) {
                                        column.search(this.value).draw();
                                      }
                                    });
                                  });

                                  // Apply college dropdown search
                                  $('#collegeFilter').on('change', function() {
                                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                    table.column(5).search(val ? '^' + val + '$' : '', true, false).draw();
                                  });

                                  // Apply college dropdown search
                                  $('#academicYearFilter').on('change', function() {
                                    var val = $(this).val();
                                    table.column(8).search(val, false, false).draw(); // regex = false, smart = false


                                  });
                                });
                              </script>

                              <!-- Optional CSS for Footer Input Styling -->
                              <style>
                                tfoot input.form-control {
                                  width: 100%;
                                  padding: 5px;
                                  font-size: 0.9rem;
                                  border-radius: 4px;
                                }

                                tfoot th {
                                  padding: 8px;
                                }
                              </style>
                            </div>

                          </div>

                          <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                          <script>
                            document.addEventListener("DOMContentLoaded", function() {
                              document.querySelectorAll(".sendQRBtn").forEach(button => {
                                button.addEventListener("click", function() {
                                  const email = this.getAttribute("data-email");
                                  const student_id = this.getAttribute("data-student");

                                  Swal.fire({
                                    title: "Send QR Code?",
                                    text: `Are you sure you want to send the QR Code to ${email}?`,
                                    icon: "question",
                                    showCancelButton: true,
                                    confirmButtonText: "Yes, send it!",
                                    cancelButtonText: "Cancel",
                                  }).then((result) => {
                                    if (result.isConfirmed) {
                                      fetch("send_qr.php", {
                                          method: "POST",
                                          headers: {
                                            "Content-Type": "application/x-www-form-urlencoded"
                                          },
                                          body: `email=${encodeURIComponent(email)}&student_id=${encodeURIComponent(student_id)}`
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                          Swal.fire({
                                            title: "Success!",
                                            text: data.message,
                                            icon: "success",
                                          });
                                        })
                                        .catch(error => {
                                          Swal.fire({
                                            title: "Error!",
                                            text: "Failed to send QR Code.",
                                            icon: "error",
                                          });
                                          console.error(error);
                                        });
                                    }
                                  });
                                });
                              });
                            });
                          </script>

                          <script>
                            document.addEventListener("DOMContentLoaded", function() {
                              // Send QR Code to Individual Email
                              document.querySelectorAll(".sendQRBtn").forEach(button => {
                                button.addEventListener("click", function() {
                                  const email = this.getAttribute("data-email");
                                  const student_id = this.getAttribute("data-student");

                                  Swal.fire({
                                    title: "Send QR Code?",
                                    text: `Are you sure you want to send the QR Code to ${email}?`,
                                    icon: "question",
                                    showCancelButton: true,
                                    confirmButtonText: "Yes, send it!",
                                    cancelButtonText: "Cancel",
                                  }).then((result) => {
                                    if (result.isConfirmed) {
                                      sendQRCode(email, student_id);
                                    }
                                  });
                                });
                              });

                              function sendQRCode(email, student_id) {
                                // Show loading SweetAlert
                                Swal.fire({
                                  title: 'Sending QR Code...',
                                  text: 'Please wait while the QR code is being sent.',
                                  allowOutsideClick: false, // Prevent closing while loading
                                  didOpen: () => {
                                    Swal.showLoading(); // Show loading spinner
                                  }
                                });

                                // Send the QR code to the server
                                fetch("send_qr.php", {
                                    method: "POST",
                                    headers: {
                                      "Content-Type": "application/x-www-form-urlencoded"
                                    },
                                    body: `email=${encodeURIComponent(email)}&student_id=${encodeURIComponent(student_id)}`
                                  })
                                  .then(response => response.json())
                                  .then(data => {
                                    console.log(data); // Log the response
                                    if (data.status === "success") {
                                      // Update to success message
                                      Swal.update({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: data.message,
                                        showConfirmButton: true,
                                        allowOutsideClick: true
                                      });
                                      Swal.hideLoading(); // Hide loading spinner
                                    } else {
                                      // Update to failure message
                                      Swal.update({
                                        icon: 'error',
                                        title: 'Failed!',
                                        text: data.message || "Something went wrong.",
                                        showConfirmButton: true,
                                        allowOutsideClick: true
                                      });
                                      Swal.hideLoading();
                                    }
                                  })
                                  .catch(error => {
                                    console.error("Error:", error);
                                    // Update to error message for network/server issues
                                    Swal.update({
                                      icon: 'error',
                                      title: 'Error!',
                                      text: 'There was an error sending the QR code. Please try again.',
                                      showConfirmButton: true,
                                      allowOutsideClick: true
                                    });
                                    Swal.hideLoading();
                                  });
                              }

                            });
                            document.getElementById("sendAllQRButton").addEventListener("click", function() {
                              const rows = document.querySelectorAll("#votersTable tbody tr");
                              let emailList = [];

                              rows.forEach(row => {
                                const email = row.querySelector("td:nth-child(2)").textContent.trim();
                                const student_id = row.querySelector("td:nth-child(1)").textContent.trim();
                                if (email && student_id) {
                                  emailList.push({
                                    email,
                                    student_id
                                  });
                                }
                              });

                              if (emailList.length === 0) {
                                Swal.fire("No Emails Found", "There are no emails to send QR codes to.", "warning");
                                return;
                              }

                              // Show the loading indicator
                              Swal.fire({
                                title: 'Sending QR Codes...',
                                text: 'Please wait while we send the QR codes.',
                                icon: 'info',
                                allowOutsideClick: false, // Prevent closing the alert while sending
                                willOpen: () => {
                                  Swal.showLoading(); // Display the loading spinner
                                }
                              });

                              let successCount = 0;
                              let failedCount = 0;

                              emailList.forEach(({
                                email,
                                student_id
                              }, index) => {
                                setTimeout(() => {
                                  sendQRCode(email, student_id, () => {
                                    successCount++;
                                    if (successCount + failedCount === emailList.length) {
                                      Swal.fire("Process Complete", `QR Codes sent: ${successCount}, Failed: ${failedCount}`, "info");
                                    }
                                  }, () => {
                                    failedCount++;
                                    if (successCount + failedCount === emailList.length) {
                                      Swal.fire("Process Complete", `QR Codes sent: ${successCount}, Failed: ${failedCount}`, "info");
                                    }
                                  });
                                }, index * 500); // Delay each request to avoid server overload
                              });
                            });

                            function sendQRCode(email, student_id, onSuccess = () => {}, onFailure = () => {}) {
                              const students = [{
                                email,
                                student_id
                              }]; // Create a single-element array of student object

                              fetch("send_qr_batch.php", {
                                  method: "POST",
                                  headers: {
                                    "Content-Type": "application/x-www-form-urlencoded"
                                  },
                                  body: `students=${encodeURIComponent(JSON.stringify(students))}` // Send students array as JSON
                                })
                                .then(response => response.json())
                                .then(data => {
                                  if (data.batch_results && data.batch_results.length > 0) {
                                    data.batch_results.forEach(result => {
                                      if (result.success) {
                                        onSuccess();
                                      } else {
                                        onFailure();
                                      }
                                    });
                                  } else {
                                    onFailure(); // If no batch results are present, consider it a failure
                                  }
                                })
                                .catch(error => {
                                  console.error("Fetch Error:", error);
                                  onFailure();
                                });
                            }

                            // Ensure jQuery and SweetAlert2 are loaded
                            document.getElementById('deleteVoters').addEventListener('click', function() {
                              Swal.fire({
                                title: 'Are you sure?',
                                text: "This will delete all voters from the system. This action cannot be undone!",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#3085d6',
                                confirmButtonText: 'Yes, delete all!'
                              }).then((result) => {
                                if (result.isConfirmed) {
                                  // Show loading alert
                                  Swal.fire({
                                    title: 'Deleting Voters...',
                                    text: 'Please wait while all voters are being deleted.',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                      Swal.showLoading();
                                    }
                                  });

                                  // Send AJAX request to delete voters
                                  $.ajax({
                                    url: 'processes/voters/delete_all_voters.php',
                                    type: 'POST',
                                    dataType: 'json',
                                    success: function(response) {
                                      Swal.close(); // Close loading alert
                                      if (response.status === 'success') {
                                        Swal.fire({
                                          title: 'Deleted!',
                                          text: response.message,
                                          icon: 'success',
                                          confirmButtonText: 'OK'
                                        }).then((result) => {
                                          if (result.isConfirmed) {
                                            location.reload(); // Refresh the page after user clicks OK
                                          }
                                        });
                                      } else {
                                        Swal.fire('Failed!', response.message || 'Something went wrong.', 'error');
                                      }
                                    },
                                    error: function(xhr, status, error) {
                                      Swal.close();
                                      Swal.fire('Error!', 'An error occurred while deleting voters. Please try again.', 'error');
                                      console.error('AJAX Error:', error);
                                    }
                                  });
                                }
                              });
                            });
                          </script>
                        </div>
                      </div>
                    </div>
                    <div class="modal fade" id="viewModal" tabindex="-1"
                      aria-labelledby="viewModalLabel" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="viewModalLabel">Voter
                              Details</h5>
                            <button type="button" class="btn-close"
                              data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">

                            <p><strong>Student ID:</strong> <span
                                id="viewStudentID"></span></p>
                            <p><strong>Email:</strong> <span
                                id="viewEmail"></span></p>
                            <p><strong>First Name:</strong> <span
                                id="viewFirstName"></span></p>
                            <p><strong>Middle Name:</strong> <span
                                id="viewMiddleName"></span></p>
                            <p><strong>Last Name:</strong> <span
                                id="viewLastName"></span></p>
                            <p><strong>Course:</strong> <span
                                id="viewCourse"></span></p>
                            <p><strong>Year Level:</strong> <span
                                id="viewYearLevel"></span></p>

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
                document.addEventListener("DOMContentLoaded", function() {
                  // Add click event listener to the export button
                  document.getElementById('exportVoters').addEventListener('click', function() {
                    // Show a loading alert
                    Swal.fire({
                      title: 'Exporting...',
                      text: 'Please wait while the voter data is being prepared.',
                      allowOutsideClick: false,
                      didOpen: () => {
                        Swal.showLoading();
                      }
                    });

                    // Fetch voter data from your server
                    fetch('fetch_voters.php')
                      .then(response => {
                        if (!response.ok) {
                          throw new Error('Network response was not ok');
                        }
                        return response.json();
                      })
                      .then(data => {
                        if (!Array.isArray(data) || data.length === 0) {
                          Swal.fire({
                            icon: 'warning',
                            title: 'No Data',
                            text: 'No voter data available to export.',
                            timer: 2000,
                            showConfirmButton: false
                          });
                          return;
                        }

                        // Prepare data for Excel based on voters table
                        const worksheetData = data.map(voter => ({

                          'Student ID': voter.student_id,
                          'Email': voter.email,
                          'First Name': voter.first_name,
                          'Middle Name': voter.middle_name,
                          'Last Name': voter.last_name,
                          'Course': voter.course,
                          'Year Level': voter.year_level,
                          'College': voter.college,
                          'Department': voter.department
                        }));

                        // Create a new workbook and worksheet
                        const ws = XLSX.utils.json_to_sheet(worksheetData);
                        const wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, 'Voters');

                        // Generate Excel file and trigger download
                        XLSX.writeFile(wb, 'Voters_List.xlsx');

                        // Close the loading alert and show success
                        Swal.fire({
                          icon: 'success',
                          title: 'Success!',
                          text: 'Voters exported successfully!',
                          timer: 2000,
                          showConfirmButton: false
                        });
                      })
                      .catch(error => {
                        console.error('Error fetching voter data:', error);
                        Swal.fire({
                          icon: 'error',
                          title: 'Error!',
                          text: 'Failed to export voters: ' + error.message,
                          timer: 2000,
                          showConfirmButton: false
                        });
                      });
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