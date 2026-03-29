<?php
session_start();
include('includes/conn.php');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'adviser') {
  session_destroy();
  session_start();
  $_SESSION['STATUS'] = "NON_ADVISER";
  header("Location: ../index.php");
  exit();
}

$has_changed = 0;

$adviserEmail = $_SESSION['email'] ?? null;


// Check if adviser email exists and is not empty
if (!empty($adviserEmail)) {
  // Get adviser's basic info
  $stmt = $pdo->prepare("SELECT * FROM advisers WHERE email = ?");
  $stmt->execute([$adviserEmail]);
  $adviser = $stmt->fetch(PDO::FETCH_ASSOC);

  // Initialize variables with default values
  $adviserId = isset($adviser['id']) ? $adviser['id'] : null;
  $college = isset($adviser['college']) ? $adviser['college'] : null;
  $department = isset($adviser['department']) ? $adviser['department'] : null;
  $wmsu_campus = isset($adviser['wmsu_campus']) ? $adviser['wmsu_campus'] : null;
  $external_campus = isset($adviser['external_campus']) ? $adviser['external_campus'] : null;
  $year = isset($adviser['year']) ? $adviser['year'] : null;
  $full_name = isset($adviser['full_name']) ? $adviser['full_name'] : null;

  // Check if adviser has changed their info
  $stmt = $pdo->prepare("SELECT has_changed FROM advisers WHERE email = ? AND has_changed = 1");
  $stmt->execute([$adviserEmail]);
  $adviser_has_changed = $stmt->fetch(PDO::FETCH_ASSOC);

  // Set has_changed flag
  $has_changed = (isset($adviser_has_changed['has_changed']) && $adviser_has_changed['has_changed'] == 1) ? 1 : 0;
}
?>


<?php
$adviserEmail = $_SESSION['email'] ?? null;



$totalVoters = 0;
$importedByAdviser = 0;
$pendingVerification = 0;

$has_changed = 0;

if ($adviserEmail) {
  // Get adviser's college and department
  $stmt = $pdo->prepare("SELECT id, college, department, wmsu_campus, external_campus, year FROM advisers WHERE email = ?");
  $stmt->execute([$adviserEmail]);
  $adviser = $stmt->fetch();

  $stmt = $pdo->prepare("SELECT has_changed FROM advisers WHERE email = ? AND has_changed = 1");
  $stmt->execute([$adviserEmail]);
  $adviser_has_changed = $stmt->fetch();


  if ($adviser_has_changed['has_changed'] == 1) {
    $has_changed = 1;
  }

  $college = $adviser['college'] ?? '';
  $department = $adviser['department'] ?? '';
  $adviser_id = $adviser['id'] ?? '';

  // Get all email records for the adviser and sum their capacities
  $stmt = $pdo->prepare("SELECT capacity FROM email WHERE adviser_id = ?");
  $stmt->execute([$adviser_id]);
  $smtp_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Calculate total capacity
  $smtp_capacity = 0;
  foreach ($smtp_details as $detail) {
    $smtp_capacity += $detail['capacity'] ?? 0;
  }

  // Adjust total capacity limit based on number of email records
  $total_limit = count($smtp_details) * 500; // 500 per email record


  $groupStmt = $pdo->prepare("
    SELECT DISTINCT 
        ay.id AS academic_year_id,
        ay.year_label,
        ay.semester,
        v.department,
        v.course,
        v.year_level
    FROM voters_copy_adviser v
    JOIN academic_years ay 
        ON v.academic_year_id = ay.id
    WHERE v.adviser_id = ?
    ORDER BY ay.start_date DESC, ay.semester ASC
");
  $groupStmt->execute([$adviser_id]);
  $groupings = $groupStmt->fetchAll(PDO::FETCH_ASSOC);


  $groupStmt->execute([$adviser_id]);
  $groupings = $groupStmt->fetchAll(PDO::FETCH_ASSOC);
}


?>


<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport"
    content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>WMSU i-Elect Adviser | Voter List </title>
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
  .voter-group {
    margin-bottom: 30px;
  }

  .voter-group.hidden {
    display: none;
  }

  #showMoreBtn {
    display: block;
    margin: 20px auto;
    padding: 10px 20px;
    background-color: #4e73df;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }

  #showMoreBtn:hover {
    background-color: #2e59d9;
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
    <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
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
            <h1 class="welcome-text">Welcome, <span class="text-white fw-bold"><?php echo $full_name ?></span>
            </h1>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item dropdown d-none d-lg-block user-dropdown">
            <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
              <img class="img-xs rounded-circle" src="images/wmsu-logo.png" style="background-color: white;"
                alt="Profile image"> </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
              <a class="dropdown-item" href="processes/accounts/logout.php"><i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign
                Out</a>
            </div>
          </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
          data-bs-toggle="offcanvas">
          <span class="mdi mdi-menu"></span>
        </button>
      </div>
    </nav>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:partials/_settings-panel.html -->
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          <li class="nav-item active-link">
            <a class="nav-link active-link" href="index.php">
              <i class="mdi mdi-grid-large menu-icon"></i>
              <span class="menu-title">Dashboard</span>
            </a>
          </li>

          <?php
          if ($has_changed == 1) {
          ?>
            <li class="nav-item">
              <a class="nav-link" href="voter-list-previous.php" style="background-color: #B22222 !important;">
                <i class="menu-icon mdi mdi-account-multiple" style="color: white !important"></i>
                <span class="menu-title" style="color: white !important">Advisory</span>
              </a>
            </li>

          <?php
          }
          ?>



          <li class="nav-item">
            <a class="nav-link" href="voter-list-pending.php">
              <i class="menu-icon mdi mdi-account-multiple-plus"></i>
              <span class="menu-title">Pending Verification</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="voter-list.php">
              <i class="menu-icon mdi mdi-account-group"></i>
              <span class="menu-title">Verified Students</span>
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
                        aria-selected="true">Advisory</a>
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
                              <h3 class="mb-0"><b>Advisory </b> <small style="font-size: 12px;"><span>(This only contains previous voters handled that are changed.)</span></small></h3>



                              <div class="d-flex justify-content-between align-items-center mt-3">


                                <!-- Academic Year Filter -->
                                <div class="mb-3">
                                  <input type="text" id="globalSearch" class="form-control" placeholder="Search all voter tables...">
                                </div>

                              </div>
                            </div>

                            <div id="noResultsMsg" class="alert alert-warning mt-2 text-center text-black" style="display: none;">
                              No matching records found.
                            </div>

                            <br>



                            <div class="voter-groups-container">
                              <?php foreach ($groupings as $index => $group): ?>
                                <?php
                                // Fetch voters for this specific group
                                $voterStmt = $pdo->prepare("
      SELECT * FROM voters_copy_adviser 
      WHERE adviser_id = ? 
        AND school_year = ? 
        AND department = ? 
        AND semester = ?
    ");
                                $voterStmt->execute([
                                  $adviser_id,
                                  $group['school_year'],
                                  $group['department'],
                                  $group['semester']
                                ]);
                                $voters = $voterStmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <?php if (count($voters) > 0): ?>
                                  <div class="voter-group <?php echo $index >= 2 ? 'hidden' : ''; ?>"
                                    data-index="<?php echo $index; ?>">
                                    <h4 class="mt-4">
                                      <div class="row">
                                        <div class="col">
                                          <h5>
                                            <i class="mdi mdi-calendar-range"></i>
                                            School Year: <?= htmlspecialchars($group['school_year']) ?>
                                          </h5>
                                          <h5>
                                            <i class="mdi mdi-timetable"></i>
                                            Semester: <?= htmlspecialchars($group['semester']) ?>
                                          </h5>
                                        </div>
                                        <div class="col">
                                          <h5>
                                            <i class="mdi mdi-school"></i>
                                            Course: <?= htmlspecialchars($group['course']) ?>
                                          </h5>
                                          <h5>
                                            <i class="mdi mdi-numeric"></i>
                                            Year Level: <?= htmlspecialchars($group['year_level']) ?>
                                          </h5>
                                        </div>
                                      </div>
                                      <hr>
                                    </h4>

                                    <div class="table-responsive mb-5">
                                      <table class="table table-striped table-bordered votersTable" style="width:100%">
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
                                            <th>WMSU Campus</th>
                                            <th>ESU Campus</th>
                                            <th>Status</th>
                                            <th>Manage</th>
                                            <!-- <th>Manage</th> -->
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
                                              <td><?= htmlspecialchars($voter['wmsu_campus']) ?></td>
                                              <td><?= htmlspecialchars($voter['external_campus'] ?: 'None') ?></td>
                                              <td>
                                                <span class="badge bg-<?= strtolower($voter['status']) === 'confirmed' ? 'success' : 'warning' ?>">
                                                  <?= strtoupper(htmlspecialchars($voter['status'])) ?>
                                                </span>
                                              </td>
                                              <td>
                                                <button class="button-view-primary" data-bs-toggle="modal" data-bs-target="#viewModal" data-student-id="<?= htmlspecialchars($voter['student_id']) ?>">
                                                  <i class="mdi mdi-eye"></i> View
                                                </button>

                                              </td>
                                            </tr>
                                          <?php endforeach; ?>
                                        </tbody>
                                      </table>
                                    </div>
                                  </div>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            </div>

                            <button id="showMoreBtn" class="<?php echo count($groupings) <= 2 ? 'hidden' : ''; ?>">
                              Show More
                            </button>

                            <script>
                              $(document).ready(function() {
                                const $voterGroups = $('.voter-group');
                                const $showMoreBtn = $('#showMoreBtn');
                                let visibleCount = 2; // Initially show 2 groups

                                function updateDisplay() {
                                  $voterGroups.each(function(index) {
                                    $(this).toggleClass('hidden', index >= visibleCount);
                                  });

                                  // Update button text and visibility
                                  if ($voterGroups.length <= 2) {
                                    $showMoreBtn.addClass('hidden');
                                  } else if (visibleCount >= $voterGroups.length) {
                                    $showMoreBtn.text('Show Less');
                                  } else {
                                    $showMoreBtn.text('Show More').removeClass('hidden');
                                  }
                                }

                                // Button click handler
                                $showMoreBtn.on('click', function() {
                                  if (visibleCount >= $voterGroups.length) {
                                    // Currently showing all - show less
                                    visibleCount = 2;
                                  } else {
                                    // Show more (2 at a time or remaining)
                                    visibleCount = Math.min(visibleCount + 2, $voterGroups.length);
                                  }

                                  updateDisplay();

                                  // Scroll to the newly shown content
                                  $('html, body').animate({
                                    scrollTop: $voterGroups.eq(visibleCount - 1).offset().top
                                  }, 500);
                                });

                                // Initialize DataTables for each table
                                $('.votersTable').each(function() {
                                  $(this).DataTable({
                                    responsive: true,
                                    pageLength: 10,
                                    dom: 'lrtip', // Removed the 'f' to hide the search bar
                                    language: {
                                      search: "Filter records:"
                                    },
                                    searching: false
                                  });
                                });
                              });

                              $('#globalSearch').on('keyup', function() {
                                const searchTerm = $(this).val().toLowerCase();
                                let anyVisible = false;

                                $('.voter-group').each(function() {
                                  const $group = $(this);
                                  let foundMatch = false;

                                  // Check group text
                                  const groupText = $group.text().toLowerCase();
                                  if (groupText.includes(searchTerm)) {
                                    foundMatch = true;
                                  } else {
                                    // Check table rows
                                    $group.find('tbody tr').each(function() {
                                      const rowText = $(this).text().toLowerCase();
                                      if (rowText.includes(searchTerm)) {
                                        foundMatch = true;
                                        return false;
                                      }
                                    });
                                  }

                                  if (foundMatch) {
                                    $group.show();
                                    anyVisible = true;
                                  } else {
                                    $group.hide();
                                  }
                                });

                                // Show or hide the "No Results" message
                                if (!anyVisible) {
                                  $('#noResultsMsg').show();
                                } else {
                                  $('#noResultsMsg').hide();
                                }
                              });
                            </script>





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
                            $(document).ready(function() {
                              $('#sendAllQRButton').on('click', function() {
                                // Show loading alert
                                Swal.fire({
                                  title: 'Sending QR Codes...',
                                  text: 'Please wait while we send the QR codes to voters.',
                                  icon: 'info',
                                  allowOutsideClick: false,
                                  didOpen: () => {
                                    Swal.showLoading();
                                  }
                                });

                                // Send AJAX request to process QR code sending
                                $.ajax({
                                  url: 'send_all_qr.php',
                                  type: 'POST',
                                  dataType: 'json',
                                  success: function(response) {
                                    Swal.close();
                                    if (response.status === 'success') {
                                      Swal.fire(
                                        'Process Complete',
                                        `QR Codes sent: ${response.successCount}, Failed: ${response.failedCount}`,
                                        'success'
                                      );
                                    } else if (response.status === 'no_voters') {
                                      Swal.fire('No Voters Found', response.message, 'warning');
                                    } else {
                                      Swal.fire('Error', response.message || 'An error occurred while sending QR codes.', 'error');
                                    }
                                  },
                                  error: function(xhr, status, error) {
                                    Swal.close();
                                    Swal.fire('Error', 'An error occurred while sending QR codes. Please try again.', 'error');
                                    console.error('AJAX Error:', error);
                                  }
                                });
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

                            $(document).ready(function() {
                              // Attach click event to the Delete All Voters button
                              $('#deleteVoters').on('click', function() {
                                // Ask for confirmation before proceeding
                                Swal.fire({
                                  title: 'Are you sure?',
                                  text: 'This will delete all voters from your department and college.',
                                  icon: 'warning',
                                  showCancelButton: true,
                                  confirmButtonColor: '#d33',
                                  cancelButtonColor: '#3085d6',
                                  confirmButtonText: 'Yes, delete them!',
                                  cancelButtonText: 'Cancel'
                                }).then((result) => {
                                  if (result.isConfirmed) {
                                    // Show loading alert
                                    Swal.fire({
                                      title: 'Fetching Adviser Info...',
                                      text: 'Please wait while we retrieve your college and department.',
                                      allowOutsideClick: false,
                                      didOpen: () => {
                                        Swal.showLoading();
                                      }
                                    });

                                    // Fetch college and department from server
                                    $.ajax({
                                      url: 'get_adviser_info.php',
                                      type: 'POST',
                                      dataType: 'json',
                                      success: function(response) {
                                        if (response.status === 'success') {
                                          var college = response.college;
                                          var department = response.department;
                                          var year_level = response.year_level;

                                          // Proceed with deletion
                                          Swal.fire({
                                            title: 'Deleting...',
                                            text: 'Please wait while voters are being deleted.',
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
                                            data: {
                                              college: college,
                                              department: department,
                                              year_level: year_level
                                            },
                                            success: function(deleteResponse) {
                                              Swal.close(); // Close loading alert
                                              if (deleteResponse.status === 'success') {
                                                Swal.fire({
                                                  title: 'Deleted!',
                                                  text: deleteResponse.message,
                                                  icon: 'success',
                                                  confirmButtonText: 'OK'
                                                }).then((result) => {
                                                  if (result.isConfirmed) {
                                                    location.reload(); // Refresh the page
                                                  }
                                                });
                                              } else {
                                                Swal.fire('Failed!', deleteResponse.message || 'Something went wrong.', 'error');
                                              }
                                            },
                                            error: function(xhr, status, error) {
                                              Swal.close();
                                              Swal.fire('Error!', 'An error occurred while deleting voters. Please try again.', 'error');
                                              console.error('Delete AJAX Error:', error);
                                            }
                                          });
                                        } else {
                                          Swal.close();
                                          Swal.fire('Error!', response.message || 'Failed to retrieve adviser information.', 'error');
                                        }
                                      },
                                      error: function(xhr, status, error) {
                                        Swal.close();
                                        Swal.fire('Error!', 'An error occurred while fetching adviser information. Please try again.', 'error');
                                        console.error('Adviser Info AJAX Error:', error);
                                      }
                                    });
                                  }
                                });
                              });
                            });
                          </script>
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

              <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="viewModalLabel">Voter Details</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div id="voterDetails">
                        <div class="row">
                          <div class="col-md-6">
                            <p><strong>Student ID:</strong> <span id="modalStudentId"></span></p>
                            <p><strong>Email:</strong> <span id="modalEmail"></span></p>
                            <p><strong>First Name:</strong> <span id="modalFirstName"></span></p>
                            <p><strong>Middle Name:</strong> <span id="modalMiddleName"></span></p>
                            <p><strong>Last Name:</strong> <span id="modalLastName"></span></p>
                            <p><strong>College:</strong> <span id="modalCollege"></span></p>
                            <p><strong>Course:</strong> <span id="modalCourse"></span></p>
                            <p><strong>Department:</strong> <span id="modalDepartment"></span></p>
                            <p><strong>Year Level:</strong> <span id="modalYearLevel"></span></p>
                            <p><strong>WMSU Campus Location:</strong> <span id="modalWmsuCampus"></span></p>
                            <p><strong>WMSU ESU Campus Location:</strong> <span id="modalExternalCampus"></span></p>
                            <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                          </div>
                          <div class="col-md-6">
                            <h6>Certificate of Registration</h6>
                            <div class="image-container">
                              <p><strong>COR 1:</strong></p>
                              <a href="#" id="modalCor1Link" target="_blank" style="display: none;">
                                <img id="modalCor1" src="" alt="COR 1" class="img-fluid" style="max-height: 200px;">
                              </a>
                              <p id="modalCor1Error" class="text-danger" style="display: none;">COR 1 not available</p>
                            </div>

                            <br>

                            <div class="image-container">
                              <p><strong>COR 2:</strong></p>
                              <a href="#" id="modalCor2Link" target="_blank" style="display: none;">
                                <img id="modalCor2" src="" alt="COR 2" class="img-fluid" style="max-height: 200px;">
                              </a>
                              <p id="modalCor2Error" class="text-danger" style="display: none;">COR 2 not available</p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-primary text-white" id="confirmStatusBtn" style="display: none;">Confirm</button>
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                  </div>
                </div>
              </div>


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
                    const file = event.target.files[0];

                    // Validate file selection
                    if (!file) {
                      Swal.fire({
                        title: "Error",
                        text: "Please select a file to upload.",
                        icon: "error",
                        confirmButtonText: "OK"
                      });
                      return;
                    }

                    // Validate file type
                    const allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                    if (!allowedTypes.includes(file.type)) {
                      Swal.fire({
                        title: "Error",
                        text: "Please upload a valid .csv, .xls, or .xlsx file.",
                        icon: "error",
                        confirmButtonText: "OK"
                      });
                      event.target.value = '';
                      return;
                    }

                    const form = document.getElementById('importVotersForm');
                    const formData = new FormData(form);

                    // Log FormData contents for debugging
                    for (let [key, value] of formData.entries()) {
                      console.log(`FormData: ${key} = ${value instanceof File ? value.name : value}`);
                    }

                    // Show loading alert with progress
                    Swal.fire({
                      title: "Importing Voters...",
                      html: `
                <p>Please wait while the file is being processed.</p>
                <p id="progress-text">0/0 emails sent</p>
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

                        fetch('processes/voters/import_voters.php', {
                            method: 'POST',
                            body: formData
                          })
                          .then(response => {
                            if (!response.ok) {
                              throw new Error(`HTTP error! Status: ${response.status}`);
                            }
                            // Use ReadableStream to process progress updates
                            const reader = response.body.getReader();
                            const decoder = new TextDecoder();
                            let buffer = '';
                            let finalMessageReceived = false;

                            function read() {
                              reader.read().then(({
                                done,
                                value
                              }) => {
                                if (done) {
                                  if (!finalMessageReceived) {
                                    Swal.close();
                                    Swal.fire({
                                      title: "Error",
                                      text: "Process interrupted or no final response received.",
                                      icon: "error",
                                      confirmButtonText: "OK"
                                    });
                                  }
                                  return;
                                }

                                buffer += decoder.decode(value, {
                                  stream: true
                                });
                                let lines = buffer.split('\n');
                                buffer = lines.pop(); // Keep the last incomplete line

                                lines.forEach(line => {
                                  if (line.trim()) {
                                    try {
                                      console.log('Stream data:', line); // Debug log
                                      const data = JSON.parse(line.trim().replace(/^data: /, ''));
                                      if (data.status === 'progress') {
                                        document.getElementById('progress-text').textContent = `${data.current}/${data.total} emails sent`;
                                      } else if (data.status === 'complete') {
                                        finalMessageReceived = true;
                                        Swal.close();
                                        Swal.fire({
                                          title: "Success",
                                          text: data.message,
                                          icon: "success",
                                          confirmButtonText: "OK"
                                        }).then((result) => {
                                          if (result.isConfirmed) {
                                            location.reload();
                                          }
                                        });
                                      } else if (data.status === 'error') {
                                        finalMessageReceived = true;
                                        Swal.close();
                                        Swal.fire({
                                          title: "Error",
                                          text: data.message,
                                          icon: "error",
                                          confirmButtonText: "Try Again"
                                        });
                                      }
                                    } catch (e) {
                                      console.error('Failed to parse JSON:', e, line);
                                    }
                                  }
                                });

                                read(); // Continue reading
                              }).catch(error => {
                                Swal.close();
                                Swal.fire({
                                  title: "Stream Error",
                                  text: "Error reading stream: " + error.message,
                                  icon: "error",
                                  confirmButtonText: "OK"
                                });
                                console.error('Stream error:', error);
                              });
                            }

                            read(); // Start reading the stream
                          })
                          .catch(error => {
                            Swal.close();
                            Swal.fire({
                              title: "Network Error",
                              text: "Could not complete the request: " + error.message,
                              icon: "error",
                              confirmButtonText: "OK"
                            });
                            console.error('Fetch error:', error);
                          });
                      }
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
                    let email = $(this).data("email"); // Get data-id from the button, not the row

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
                            email: email
                          }, // Send voter ID to PHP
                          dataType: "json",
                          success: function(response) {
                            if (response.status === 'success') {
                              Swal.fire({
                                title: "Deleted!",
                                text: `${name} has been removed.`,
                                icon: "success",
                                customClass: {
                                  popup: 'custom-swal-padding'
                                }
                              }).then(() => {
                                location.reload(); // Refresh page after confirmation
                              });
                            } else {
                              Swal.fire({
                                title: "Error",
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
              </script>

              <script>
                $(document).ready(function() {
                  // Handle click on View button
                  $('.button-view-primary').on('click', function() {
                    var studentId = $(this).data('student-id'); // Get student ID from data attribute

                    // Perform AJAX request to fetch voter details
                    $.ajax({
                      url: 'fetch_voter.php',
                      type: 'POST',
                      data: {
                        student_id: studentId
                      },
                      dataType: 'json',
                      success: function(response) {
                        if (response.success) {
                          // Populate modal fields with voter data
                          $('#modalStudentId').text(response.data.student_id);
                          $('#modalEmail').text(response.data.email);
                          $('#modalFirstName').text(response.data.first_name);
                          $('#modalMiddleName').text(response.data.middle_name || 'N/A');
                          $('#modalLastName').text(response.data.last_name);
                          $('#modalCollege').text(response.data.college);
                          $('#modalCourse').text(response.data.course);
                          $('#modalDepartment').text(response.data.department);
                          $('#modalYearLevel').text(response.data.year_level);
                          $('#modalWmsuCampus').text(response.data.wmsu_campus);
                          $('#modalExternalCampus').text(response.data.external_campus || 'N/A');
                          $('#modalStatus').text(response.data.status || 'N/A');

                          // Handle COR 1 image
                          if (response.data.cor1) {
                            $('#modalCor1').attr('src', response.data.cor1).show();
                            $('#modalCor1Link')
                              .attr('href', response.data.cor1)
                              .attr('target', '_blank') // Open in new tab
                              .show();
                            $('#modalCor1Error').hide();
                          } else {
                            $('#modalCor1').hide();
                            $('#modalCor1Link').hide();
                            $('#modalCor1Error').show();
                          }

                          // Handle COR 2 image
                          if (response.data.cor2) {
                            $('#modalCor2').attr('src', response.data.cor2).show();
                            $('#modalCor2Link')
                              .attr('href', response.data.cor2)
                              .attr('target', '_blank') // Open in new tab
                              .show();
                            $('#modalCor2Error').hide();
                          } else {
                            $('#modalCor2').hide();
                            $('#modalCor2Link').hide();
                            $('#modalCor2Error').show();
                          }

                          // Show or hide Confirm button based on status
                          if (response.data.status === 'confirmed') {
                            $('#confirmStatusBtn').hide();
                          } else {
                            $('#confirmStatusBtn').show();
                          }
                        } else {
                          alert('Error: ' + response.message);
                        }
                      },
                      error: function(xhr, status, error) {
                        alert('An error occurred while fetching voter details.');
                      }
                    });
                  });

                  $('#confirmStatusBtn').on('click', function() {
                    var studentId = $('#modalStudentId').text(); // Get student ID from modal

                    $.ajax({
                      url: 'update_voter_status.php',
                      type: 'POST',
                      data: {
                        student_id: studentId,
                        status: 'confirmed'
                      },
                      dataType: 'json',
                      success: function(response) {
                        if (response.success) {
                          $('#modalStatus').text('confirmed');
                          $('#confirmStatusBtn').hide();

                          // SweetAlert2 success popup
                          Swal.fire({
                            icon: 'success',
                            title: 'Status Confirmed',
                            text: 'Voter status updated successfully.',
                            timer: 1500,
                            showConfirmButton: false
                          }).then(() => {
                            window.location.reload();
                          });

                        } else {
                          // SweetAlert2 error popup
                          Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: response.message || 'Unable to confirm voter status.'
                          });
                        }
                      },
                      error: function(xhr, status, error) {
                        Swal.fire({
                          icon: 'error',
                          title: 'Server Error',
                          text: 'An error occurred while updating voter status.'
                        });
                      }
                    });
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

              <script>
                $('#globalSearch').on('keyup', function() {
                  const searchTerm = $(this).val().toLowerCase();
                  let anyVisible = false;

                  $('.voter-group').each(function() {
                    const $group = $(this);
                    let foundMatch = false;

                    const groupText = $group.text().toLowerCase();
                    if (groupText.includes(searchTerm)) {
                      foundMatch = true;
                    } else {
                      $group.find('tbody tr').each(function() {
                        const rowText = $(this).text().toLowerCase();
                        if (rowText.includes(searchTerm)) {
                          foundMatch = true;
                          return false;
                        }
                      });
                    }

                    if (foundMatch) {
                      $group.show();
                      anyVisible = true;
                    } else {
                      $group.hide();
                    }
                  });


                  // Show More button click handler
                  $showMoreBtn.on('click', function() {
                    // Show next 2 groups
                    const nextGroups = $voterGroups.filter('.hidden').slice(0, 2);

                    nextGroups.removeClass('hidden');
                    visibleCount += nextGroups.length;

                    // Hide button if all groups are visible
                    if ($voterGroups.filter('.hidden').length === 0) {
                      $showMoreBtn.addClass('hidden');
                    }
                  });

                  // Toggle "No Results" message
                  $('#noResultsMsg').toggle(!anyVisible);

                  // Check if Show More button should be shown
                  const visibleGroups = $('.voter-group:visible').length;
                  const hiddenGroups = $('.voter-group:hidden').length;

                  if (visibleGroups === 0 || visibleGroups === $('.voter-group').length) {
                    $('#showMoreBtn').hide(); // hide if none or all are shown
                  } else {
                    $('#showMoreBtn').show(); // show only if there are hidden ones to reveal
                  }
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