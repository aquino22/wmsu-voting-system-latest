<?php
session_start();
include('includes/conn.php');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  session_destroy();
  session_start();
  $_SESSION['STATUS'] = "NON_ADMIN";
  header("Location: ../index.php");
  exit();
}

if (
  !isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) ||
  (time() - $_SESSION['csrf_token_time']) > 1800
) { // 30-minute expiration
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  $_SESSION['csrf_token_time'] = time();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>WMSU i-Elect Admin | Events </title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="vendors/typicons/typicons.css">
  <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

  <!-- jQuery & DataTables JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <!-- Dependencies -->
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <link rel="stylesheet" href="css/vertical-layout-light/style.css">
  <!-- endinject -->
  <link rel="shortcut icon" href="images/favicon.png" />

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

  <!-- Include Quill CSS & JS -->
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

      <?php include('includes/sidebar.php') ?>

      </ul>
      </nav>
      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                  <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                      <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab"
                        aria-controls="overview" aria-selected="true">Events</a>
                    </li>
                  </ul>
                </div>
                <div class="tab-content tab-content-basic">
                  <div class="card">
                    <div class="card-body">
                      <div class="d-flex align-items-center">
                        <h3 class=""><b>Events </b></h3>
                        <div class="ms-auto" aria-hidden="true">
                          <button type="button" class="btn btn-primary text-white" data-bs-toggle="modal"
                            data-bs-target="#eventModal">
                            <small>
                              <i class="mdi mdi-plus-circle"></i> Create an Event
                            </small>
                          </button>
                        </div>

                      </div>
                      <?php
                      try {
                        // Fetch all events with related election name and academic year info
                        $stmt = $pdo->prepare("
        SELECT 
            ev.id, 
            ev.event_title, 
            ev.status, 
            ev.registration_enabled, 
            DATE_FORMAT(ev.created_at, '%b %d, %Y') AS formatted_date,
            e.election_name,      
            ay.year_label,
            ay.semester
        FROM events ev
        LEFT JOIN elections e ON ev.candidacy = e.id  
        LEFT JOIN academic_years ay ON e.academic_year_id = ay.id
        ORDER BY ev.created_at DESC
    ");
                        $stmt->execute();
                        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                      } catch (PDOException $e) {
                        echo "Error: " . $e->getMessage();
                      }
                      ?>
                      <table id="eventsTable" class="table table-striped">
                        <thead>
                          <tr>
                            <th>Title</th>
                            <th>School Year</th>
                            <th>Semester</th>
                            <th>Election Under</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($events as $event): ?>
                            <tr>
                              <td><?= htmlspecialchars($event['event_title']) ?></td>
                              <td><?= htmlspecialchars($event['year_label'] ?? 'N/A') ?></td>
                              <td><?= htmlspecialchars($event['semester'] ?? 'N/A') ?></td>
                              <td><?= htmlspecialchars($event['election_name'] ?? 'N/A') ?></td>
                              <td>
                                <span class="badge bg-<?=
                                                      $event['status'] == 'published' ? 'success' : ($event['status'] == 'draft' ? 'primary' : 'warning')
                                                      ?>">
                                  <?= ucfirst(htmlspecialchars($event['status'])) ?>
                                </span>
                              </td>


                              <td><?= htmlspecialchars($event['formatted_date']) ?></td>
                              <td class="d-flex">
                                <a href="view_events.php?id=<?= $event['id'] ?>" style="text-decoration: none;">
                                  <button class="btn btn-primary btn-md text-white"><i class="mdi mdi-eye"></i> View Event</button>
                                </a>
                                <?php if ($event['registration_enabled'] == 1): ?>
                                  <a href="view_participants_admin.php?id=<?= $event['id'] ?>" style="text-decoration: none;" target="_blank">
                                    <button class="btn btn-primary btn-md text-white"><i class="mdi mdi-account-group"></i> View Candidates</button>
                                  </a>
                                <?php endif; ?>
                                <div class="dropdown">
                                  <button class="btn btn-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-calendar"></i>
                                    Manage Event
                                  </button>
                                  <ul class="dropdown-menu">
                                    <?php if ($event['registration_enabled'] == 1): ?>
                                    <?php endif ?>
                                    <li><a class="dropdown-item"
                                        href="processes/events/publish.php?id=<?php echo $event['id'] ?>"><i class="mdi mdi-calendar-multiple"></i> Publish
                                        Event</a>
                                    </li>
                                    <li><a class="dropdown-item"
                                        href="processes/events/unpublish.php?id=<?php echo $event['id'] ?>"><i class="mdi mdi-calendar-remove"></i> Unpublish
                                        Event</a></li>
                                    <li><a class="dropdown-item"
                                        href="processes/events/end.php?id=<?php echo $event['id'] ?>"><i class="mdi mdi-calendar-check"></i> End Event</a></li>
                                  </ul>
                                </div>
                                <button class="btn btn-danger btn-md text-white delete-btn" data-id="<?= $event['id'] ?>">
                                  <i class="mdi mdi-delete"></i>
                                  Delete
                                </button>
                                </tdc>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Edit Registration Date Modal -->
                <div class="modal fade" id="dateModal" tabindex="-1" aria-labelledby="dateModalLabel"
                  aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="dateModalLabel">Edit Registration Date</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <form id="editRegistrationForm">
                          <input type="hidden" id="event_id" name="event_id">
                          <!-- CSRF Token -->
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                          <div class="mb-3">
                            <label for="registration_start_date" class="form-label">Start Date</label>
                            <input type="datetime-local" class="form-control" name="registration_start_date"
                              id="registration_start_date">
                          </div>

                          <div class="mb-3">
                            <label for="registration_deadline" class="form-label">Deadline</label>
                            <input type="datetime-local" class="form-control" name="registration_deadline"
                              id="registration_deadline">
                          </div>

                          <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>

                <script>
                  document.addEventListener("DOMContentLoaded", function() {
                    // Populate Modal on Click
                    document.querySelectorAll("[data-bs-target='#dateModal']").forEach(button => {
                      button.addEventListener("click", function() {
                        const eventId = this.getAttribute("data-event-id");
                        document.getElementById("event_id").value = eventId;

                        // Show loading indicator
                        Swal.fire({
                          title: "Loading...",
                          text: "Fetching event details...",
                          allowOutsideClick: false,
                          didOpen: () => {
                            Swal.showLoading();
                          }
                        });

                        fetch(`processes/events/get_registration.php?event_id=${eventId}`)
                          .then(response => response.json())
                          .then(data => {
                            Swal.close(); // Close loading alert
                            if (data.success) {
                              document.getElementById("registration_start_date").value = data.data.registration_start;
                              document.getElementById("registration_deadline").value = data.data.registration_deadline;
                            } else {
                              Swal.fire("Error!", "Please select a proper candidacy with working registration dates.", "error");
                            }
                          })
                          .catch(error => {
                            Swal.close();
                            Swal.fire("Error!", "Failed to fetch data.", "error");
                            console.error("Error fetching data:", error);
                          });
                      });
                    });

                    // Submit Form via AJAX with Confirmation
                    document.getElementById("editRegistrationForm").addEventListener("submit", function(event) {
                      event.preventDefault();

                      Swal.fire({
                        title: "Are you sure?",
                        text: "Do you want to update the registration dates?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Yes, update it!",
                        cancelButtonText: "Cancel"
                      }).then((result) => {
                        if (result.isConfirmed) {
                          const formData = new FormData(document.getElementById("editRegistrationForm"));

                          Swal.fire({
                            title: "Updating...",
                            text: "Please wait while we update the dates.",
                            allowOutsideClick: false,
                            didOpen: () => {
                              Swal.showLoading();
                            }
                          });

                          fetch("processes/events/edit_registration.php", {
                              method: "POST",
                              body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                              Swal.close();
                              if (data.success) {
                                Swal.fire("Success!", "Registration dates updated successfully!", "success")
                                  .then(() => location.reload()); // Reload page after confirmation
                              } else {
                                Swal.fire("Error!", data.error, "error");
                              }
                            })
                            .catch(error => {
                              Swal.close();
                              Swal.fire("Error!", "Failed to update registration dates.", "error");
                              console.error("Error updating registration:", error);
                            });
                        }
                      });
                    });
                  });
                </script>



                <style>
                  .modal-body {
                    max-height: 60vh;
                    /* Adjust height as needed */
                    overflow-y: auto;
                  }

                  .fields-container {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 1rem;
                    /* Space between field cards */
                  }

                  .dynamic-field {
                    background: #f8f9fa;
                    border: 1px solid #e9ecef;
                    border-radius: 0.5rem;
                    padding: 1rem;
                    width: 100%;
                    max-width: 400px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                  }

                  .dynamic-field .removeField {
                    font-size: 1rem;
                    /* Small size */
                    color: #dc3545;
                    /* Red color */
                    cursor: pointer;
                    background: none;
                    border: none;
                    padding: 0;
                    line-height: 1;
                  }

                  .dynamic-field .removeField:hover {
                    color: #b02a37;
                    /* Darker red on hover */
                  }

                  .dynamic-field .d-flex {
                    margin-bottom: 0.5rem;
                    /* Space below the label/icon row */
                  }

                  .dynamic-field label.form-label {
                    font-size: 0.85rem;
                    font-weight: 500;
                    color: #495057;
                  }

                  .form-check-input {
                    margin-left: 3px !important;
                  }

                  /* Ensure other styles (choices, colleges, etc.) remain intact */
                </style>



                <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Add an Event</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <form method="POST" id="addForm" enctype="multipart/form-data">
                        <div class="modal-body">
                          <input type="hidden" name="start_period" id="start_period">
                          <input type="hidden" name="end_period" id="end_period">
                          <!-- CSRF Token -->
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                          <!-- Title Input -->
                          <div class="mb-3">
                            <label for="event_title" class="form-label">Event Title</label>
                            <input type="text" class="form-control" name="event_title" id="event_title" required>
                          </div>

                          <!-- Cover Image Upload -->
                          <div class="mb-3">
                            <label for="cover_image" class="form-label">Cover Image</label>
                            <input type="file" class="form-control" name="cover_image" id="cover_image" accept="image/*">
                          </div>

                          <!-- Quill Editor -->
                          <div class="mb-3">
                            <label for="editor-container" class="form-label">Event Details</label>
                            <div id="editor-container" style="height: 300px;"></div>
                            <input type="hidden" name="event_details" id="event_details">
                          </div>

                          <!-- Open for Registration Checkbox -->
                          <div class="mb-3">
                            <input type="checkbox" id="open_for_registration" name="open_for_registration" value="1">
                            <label for="open_for_registration" class="form-label ms-2">Open for Registration</label>
                            <br>
                            <small>Select a candidacy to define the registration period for interested applicants.</small>
                          </div>

                          <!-- Registration Dates -->
                          <div id="registrationDatesSection" class="border p-3 rounded" style="display: none;">
                            <h5>Registration Period</h5>
                            <div id="candidacySection">
                              <label for="candidacyDropdown" class="form-label">Select Candidacy</label>
                              <select id="candidacyDropdown" class="form-control" name="candidacy">
                                <option value="">Select Candidacy</option>
                              </select>
                              <p class="mt-2">Start Period: <span id="startPeriodDisplay">N/A</span></p>
                              <p>End Period: <span id="endPeriodDisplay">N/A</span></p>
                            </div>
                          </div>

                          <!-- Dynamic Registration Form -->
                          <div id="registrationFormSection" class="border p-4 rounded-3 bg-white shadow-sm" style="display: none;">
                            <h5 class="fw-bold text-primary mb-3">Candidate Form Fields</h5>
                            <small class="d-block text-muted mb-3">Tip: Use 'Student Name' as <b>Field Name</b> with <b>Text</b> type for student names.</small>
                            <h6 class="fw-bold text-secondary mb-2">Default Fields</h6>
                            <div id="registrationFields" class="fields-container"></div>
                            <h6 class="fw-bold text-secondary mt-3 mb-2">Custom Fields</h6>
                            <div id="customFields" class="fields-container"></div>
                            <button type="button" class="btn btn-success text-white btn-sm rounded-pill px-3 mt-3 w-100" id="addField">
                              <i class="bi bi-plus-circle me-1"></i> Add Field
                            </button>
                          </div>

                          <input type="hidden" value="draft" name="status">
                          <input type="hidden" name="event_id" id="event_id">
                        </div>

                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> <i class="mdi mdi-close"></i>Close</button>
                          <button type="submit" class="btn btn-primary text-white" id="submitEvent"> <i class="mdi mdi-upload"></i> Save Event</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <style>
                  .border {
                    border: 1px solid #dee2e6 !important;
                  }

                  .rounded {
                    border-radius: 0.375rem !important;
                  }

                  .bg-white {
                    background-color: #fff !important;
                  }

                  .shadow-sm {
                    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
                  }

                  .p-4 {
                    padding: 1.5rem !important;
                  }

                  .rounded-3 {
                    border-radius: 0.3rem !important;
                  }

                  .fw-bold {
                    font-weight: 700 !important;
                  }

                  .text-primary {
                    color: #0d6efd !important;
                  }

                  .text-secondary {
                    color: #6c757d !important;
                  }

                  .mb-3 {
                    margin-bottom: 1rem !important;
                  }

                  .mt-3 {
                    margin-top: 1rem !important;
                  }

                  .mb-2 {
                    margin-bottom: 0.5rem !important;
                  }

                  .d-block {
                    display: block !important;
                  }

                  .text-muted {
                    color: #6c757d !important;
                  }

                  .btn-success {
                    color: #fff;
                    background-color: #198754;
                    border-color: #198754;
                  }

                  .btn-success:hover {
                    background-color: #157347;
                    border-color: #146c43;
                  }

                  .btn-sm {
                    padding: 0.25rem 0.5rem;
                    font-size: 0.875rem;
                  }

                  .rounded-pill {
                    border-radius: 50rem !important;
                  }

                  .px-3 {
                    padding-left: 1rem !important;
                    padding-right: 1rem !important;
                  }

                  .field-container {
                    margin-bottom: 1rem;
                    padding: 1rem;
                    border: 1px solid #dee2e6;
                    border-radius: 0.375rem;
                    background-color: #f8f9fa;
                  }

                  .field-container.default {
                    background-color: #e9ecef;
                  }
                </style>

                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"></script>
                <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
                <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script>
                  document.addEventListener("DOMContentLoaded", function() {
                    // Initialize Quill editor
                    const quill = new Quill('#editor-container', {
                      theme: 'snow',
                      modules: {
                        toolbar: [
                          [{
                            'header': [1, 2, 3, false]
                          }],
                          ['bold', 'italic', 'underline'],
                          ['link', 'image'],
                          [{
                            'list': 'ordered'
                          }, {
                            'list': 'bullet'
                          }]
                        ]
                      }
                    });

                    const registrationCheckbox = document.getElementById("open_for_registration");
                    const registrationDatesSection = document.getElementById("registrationDatesSection");
                    const registrationFormSection = document.getElementById("registrationFormSection");
                    const candidacySection = document.getElementById("candidacySection");
                    const addFieldButton = document.getElementById("addField");
                    const registrationFields = document.getElementById("registrationFields");
                    const customFields = document.getElementById("customFields");
                    const candidacyDropdown = document.getElementById("candidacyDropdown");

                    // Toggle registration sections
                    function toggleRegistrationFields() {
                      const display = registrationCheckbox.checked ? "block" : "none";
                      registrationDatesSection.style.display = display;
                      registrationFormSection.style.display = display;
                      candidacySection.style.display = display;
                      if (!registrationCheckbox.checked) {
                        registrationFields.innerHTML = "";
                        customFields.innerHTML = "";
                        candidacyDropdown.innerHTML = "<option value=''>Select Candidacy</option>";
                      } else {
                        fetchCandidacyEvents();
                        addDefaultFields();
                      }
                    }

                    registrationCheckbox.addEventListener("change", toggleRegistrationFields);

                    // Fetch candidacy events
                    function fetchCandidacyEvents() {
                      fetch("processes/events/fetch_candidacy.php")
                        .then(response => response.json())
                        .then(data => {
                          if (data.error) throw new Error(data.error);
                          candidacyDropdown.innerHTML = "<option value=''>Select Candidacy</option>";
                          data.forEach(candidacy => {
                            const option = document.createElement("option");
                            option.value = candidacy.election_id;
                            option.textContent = candidacy.election_name;
                            option.dataset.startPeriod = candidacy.start_period;
                            option.dataset.endPeriod = candidacy.end_period;
                            candidacyDropdown.appendChild(option);
                          });
                        })
                        .catch(error => console.error("Error fetching candidacy:", error));
                    }

                    // Update period display and fetch party/position options
                    candidacyDropdown.addEventListener("change", function() {
                      const selectedOption = this.options[this.selectedIndex];
                      const startPeriod = selectedOption.dataset.startPeriod || "N/A";
                      const endPeriod = selectedOption.dataset.endPeriod || "N/A";
                      document.getElementById("startPeriodDisplay").textContent = startPeriod;
                      document.getElementById("endPeriodDisplay").textContent = endPeriod;
                      document.getElementById("start_period").value = startPeriod !== "N/A" ? startPeriod : "";
                      document.getElementById("end_period").value = endPeriod !== "N/A" ? endPeriod : "";

                      const electionName = this.value;
                      if (electionName) {
                        fetch("processes/events/get_parties.php", {
                            method: "POST",
                            headers: {
                              "Content-Type": "application/x-www-form-urlencoded"
                            },
                            body: `election_name=${encodeURIComponent(electionName)}`
                          })
                          .then(response => response.text())
                          .then(data => {
                            const partySelect = document.querySelector("[name='registration_fields[party][options]']");
                            if (partySelect) partySelect.innerHTML = data;
                          })
                          .catch(error => console.error("Error fetching parties:", error));
                      }
                    });

                    // Add default fields
                    function addDefaultFields() {
                      registrationFields.innerHTML = "";
                      const defaultFields = [{
                          name: "full_name",
                          type: "text",
                          required: true,
                          isDefault: true
                        },
                        {
                          name: "student_id",
                          type: "text",
                          required: true,
                          isDefault: true
                        },
                        {
                          name: "party",
                          type: "dropdown",
                          required: true,
                          isDefault: true
                        },
                        {
                          name: "position",
                          type: "dropdown",
                          required: true,
                          isDefault: true
                        },
                        {
                          name: "picture",
                          type: "file",
                          required: true,
                          isDefault: true
                        }
                      ];

                      defaultFields.forEach(field => {
                        const fieldId = field.name;
                        const fieldHtml = `
                <div class="field-container default" data-field-id="${fieldId}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label">
                            ${field.name.replace('_', ' ').charAt(0).toUpperCase() + field.name.replace('_', ' ').slice(1).toLowerCase()}
                        </label>
                    </div>
                    <input type="hidden" name="registration_fields[${fieldId}][name]" value="${field.name}">
                    <input type="hidden" name="registration_fields[${fieldId}][type]" value="${field.type}">
                    <input type="hidden" name="registration_fields[${fieldId}][is_default]" value="1">
                    ${field.type === "text" ? `
                        <input type="text" class="form-control mb-2" disabled placeholder="Enter ${field.name.replace('_', ' ')}">
                    ` : field.type === "dropdown" ? `
                        <select class="form-control mb-2" name="registration_fields[${fieldId}][options]" disabled>
                            <option value="">Select ${field.name}</option>
                        </select>
                    ` : `
                        <input type="file" class="form-control mb-2" disabled>
                    `}
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" name="registration_fields[${fieldId}][required]" value="1" ${field.required ? 'checked disabled' : ''}>
                        <label class="form-check-label">Required</label>
                    </div>
                </div>
            `;
                        registrationFields.insertAdjacentHTML('beforeend', fieldHtml);
                      });
                    }

                    // Add custom field
                    addFieldButton.addEventListener("click", function() {
                      const fieldId = Date.now();
                      const fieldHtml = `
            <div class="field-container" data-field-id="${fieldId}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label">Field Name</label>
                    <button type="button" class="btn p-0 border-0 remove-field" data-field-id="${fieldId}" style="background: none; box-shadow: none;">
                        <i class="bi bi-trash text-danger"></i>
                    </button>
                </div>
                <input type="text" class="form-control mb-2" name="registration_fields[${fieldId}][name]" placeholder="Enter Field Name" required>
                <select class="form-control mb-2 field-type" name="registration_fields[${fieldId}][type]">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="dropdown">Dropdown</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="radio">Radio</option>
                    <option value="file">File</option>
                </select>
                <div class="mb-2 options-container" id="options_${fieldId}" style="display: none;">
                    <input type="text" class="form-control" name="registration_fields[${fieldId}][options]" placeholder="Options (comma-separated)">
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" name="registration_fields[${fieldId}][required]" value="1">
                    <label class="form-check-label">Required</label>
                </div>
                <div class="mb-2 template-container">
                    <label class="form-label">Downloadable Template (optional)</label>
                    <input type="file" class="form-control template-upload" name="registration_fields[${fieldId}][template]" accept=".doc,.docx,.pdf,.xlsx">
                    <a href="#" class="template-preview d-none" target="_blank">Preview Template</a>
                </div>
                <button type="button" class="btn btn-secondary btn-sm toggle-options" data-field-id="${fieldId}">Add Options</button>
            </div>
        `;
                      customFields.insertAdjacentHTML('beforeend', fieldHtml);

                      const fieldTypeSelect = document.querySelector(`[name="registration_fields[${fieldId}][type]"]`);
                      const optionsDiv = document.getElementById(`options_${fieldId}`);
                      const toggleButton = document.querySelector(`[data-field-id="${fieldId}"].toggle-options`);
                      const templateUpload = document.querySelector(`[name="registration_fields[${fieldId}][template]"]`);
                      const templatePreview = document.querySelector(`[data-field-id="${fieldId}"] .template-preview`);
                      const removeButton = document.querySelector(`[data-field-id="${fieldId}"].remove-field`);

                      fieldTypeSelect.addEventListener("change", function() {
                        const type = this.value;
                        optionsDiv.style.display = ['dropdown', 'radio'].includes(type) ? 'block' : 'none';
                      });

                      toggleButton.addEventListener("click", function() {
                        optionsDiv.style.display = optionsDiv.style.display === 'block' ? 'none' : 'block';
                      });

                      templateUpload.addEventListener("change", function() {
                        if (this.files && this.files[0]) {
                          templatePreview.classList.remove("d-none");
                          templatePreview.href = URL.createObjectURL(this.files[0]);
                          templatePreview.textContent = `Preview: ${this.files[0].name}`;
                        } else {
                          templatePreview.classList.add("d-none");
                          templatePreview.href = "#";
                          templatePreview.textContent = "Preview Template";
                        }
                      });

                      removeButton.addEventListener("click", function() {
                        this.closest('.field-container').remove();
                      });
                    });

                    // Form submission handling
                    document.getElementById('addForm').addEventListener('submit', function(e) {
                      e.preventDefault();

                      // Populate Quill editor content
                      document.getElementById('event_details').value = quill.root.innerHTML;

                      // Prepare form data
                      const formData = new FormData(this);

                      // Show loading state
                      Swal.fire({
                        title: 'Saving Event...',
                        text: 'Please wait while the event is being saved.',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                          Swal.showLoading();
                        }
                      });

                      fetch('processes/events/save.php', {
                          method: 'POST',
                          body: formData
                        })
                        .then(response => {
                          if (!response.ok) {
                            throw new Error(`Network response was not ok: ${response.statusText}`);
                          }
                          return response.json();
                        })
                        .then(data => {
                          Swal.fire({
                            title: data.success ? 'Success!' : 'Error!',
                            text: data.message,
                            icon: data.success ? 'success' : 'error',
                            confirmButtonText: 'OK'
                          }).then((result) => {
                            if (data.success && result.isConfirmed) {
                              const modal = document.getElementById('eventModal');
                              modal.classList.remove('show');
                              modal.style.display = 'none';
                              document.body.classList.remove('modal-open');
                              const backdrop = document.querySelector('.modal-backdrop');
                              if (backdrop) backdrop.remove();

                              this.reset();
                              quill.root.innerHTML = '';
                              registrationFields.innerHTML = '';
                              customFields.innerHTML = '';
                              candidacyDropdown.innerHTML = '<option value="">Select Candidacy</option>';
                              registrationDatesSection.style.display = 'none';
                              registrationFormSection.style.display = 'none';
                              registrationCheckbox.checked = false;
                              location.reload();
                            }
                          });
                        })
                        .catch(error => {
                          console.error('Fetch error:', error);
                          Swal.fire({
                            title: 'Error!',
                            text: `Failed to save event: ${error.message}`,
                            icon: 'error',
                            confirmButtonText: 'OK'
                          });
                        });
                    });
                  });
                </script>


                <div class="modal fade" id="sampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                  aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Editing Event Article: Upcoming School
                          Elections</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="tab-content tab-content-basic">
                          <div id="editor-container-content" style="height: 300px;"></div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success">Publish</button>
                        <button type="button" class="btn btn-primary">Unpublish</button>
                      </div>
                    </div>
                  </div>
                </div>


              </div>
            </div>
          </div>
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
          <footer class="footer">

            <!-- partial -->
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->

    <script>
      $(document).ready(function() {
        if (!$.fn.DataTable.isDataTable('#eventsTable')) {
          $('#eventsTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            order: []
          });
        }
      });



      // Image upload handler
      function imageHandler() {
        const input = document.createElement("input");
        input.setAttribute("type", "file");
        input.setAttribute("accept", "image/*");
        input.click();

        input.onchange = async () => {
          const file = input.files[0];

          if (file) {
            let formData = new FormData();
            formData.append("image", file);

            Swal.fire({
              title: "Uploading...",
              text: "Please wait while your image is being uploaded.",
              icon: "info",
              allowOutsideClick: false,
              showConfirmButton: false,
              didOpen: () => {
                Swal.showLoading();
              }
            });

            try {
              const response = await fetch("processes/events/upload.php", {
                method: "POST",
                body: formData
              });

              const result = await response.json();
              Swal.close(); // Close the loading animation

              if (result.success) {
                const range = quill.getSelection() || {
                  index: 0
                }; // Prevent null selection error
                quill.insertEmbed(range.index, "image", result.filepath);

                Swal.fire({
                  title: "Success!",
                  text: "Image uploaded successfully.",
                  icon: "success",
                  confirmButtonText: "OK"
                });
              } else {
                Swal.fire({
                  title: "Upload Failed!",
                  text: "Error: " + result.error,
                  icon: "error",
                  confirmButtonText: "OK"
                });
              }
            } catch (error) {
              Swal.fire({
                title: "Error!",
                text: "An error occurred while uploading the image.",
                icon: "error",
                confirmButtonText: "OK"
              });
            }
          }
        };
      }



      var editable_quill = new Quill('#editor-container-content', {
        theme: 'snow', // Snow theme for a clean UI
        modules: {
          toolbar: [
            [{
              header: [1, 2, false]
            }],
            ['bold', 'italic', 'underline'],
            ['blockquote', 'code-block'],
            [{
              list: 'ordered'
            }, {
              list: 'bullet'
            }],
            [{
              indent: '-1'
            }, {
              indent: '+1'
            }],
            ['link', 'image'],
            ['clean']
          ]
        }
      });

      editable_quill.root.innerHTML = "<h2>Sample Event Title</h2><p>This is a <b>sample event description</b> with some formatting.</p>";

      document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".delete-btn").forEach(button => {
          button.addEventListener("click", function() {
            const eventId = this.getAttribute("data-id"); // Get event ID (optional)

            Swal.fire({
              title: "Are you sure?",
              text: "This action cannot be undone!",
              icon: "warning",
              showCancelButton: true,
              confirmButtonColor: "#d33",
              cancelButtonColor: "#3085d6",
              confirmButtonText: "Yes, delete it!"
            }).then((result) => {
              if (result.isConfirmed) {
                // Perform delete action (example: send request to server)
                Swal.fire("Deleted!", "The event has been deleted.", "success");

                // Example: Remove the event row from DataTable
                // $(this).closest("tr").remove();
              }
            });
          });
        });
      });
    </script>

    </script>

    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="vendors/chart.js/Chart.min.js"></script>
    <script src="vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.delete-btn').forEach(button => {
          button.addEventListener('click', function() {
            const eventId = this.getAttribute('data-id');

            Swal.fire({
              title: "Are you sure?",
              text: "You won't be able to revert this! Deleting it will delete any associated data as well. (Events, candidates, voting tallying and results publishing)",
              icon: "warning",
              showCancelButton: true,
              confirmButtonColor: "#d33",
              cancelButtonColor: "#3085d6",
              confirmButtonText: "Yes, delete it!"
            }).then((result) => {
              if (result.isConfirmed) {
                fetch('processes/events/delete.php', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + eventId
                  })
                  .then(response => response.json())
                  .then(data => {
                    if (data.status === 'success') {
                      Swal.fire("Deleted!", data.message, "success")
                        .then(() => location.reload()); // Refresh the page
                    } else {
                      Swal.fire("Error!", data.message, "error");
                    }
                  })
                  .catch(error => {
                    Swal.fire("Error!", "An error occurred.", "error");
                  });
              }
            });
          });
        });
      });
    </script>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const status = <?php echo json_encode($_SESSION['STATUS'] ?? ''); ?>;

        if (status) {
          let title = "";
          let text = "";
          let icon = "";

          switch (status) {
            case "PUBLISH_SUCCESSFUL":
              title = "Published!";
              text = "The event has been published successfully.";
              icon = "success";
              break;

            case "PUBLISH_UNSUCCESSFUL":
              title = "Publish Failed!";
              text = "Failed to publish the event.";
              icon = "error";
              break;

            case "UNPUBLISH_SUCCESSFUL":
              title = "Unpublished!";
              text = "The event has been unpublished successfully.";
              icon = "success";
              break;

            case "UNPUBLISH_UNSUCCESSFUL":
              title = "Unpublish Failed!";
              text = "Failed to unpublish the event.";
              icon = "error";
              break;

            case "END_SUCCESSFUL":
              title = "Ended!";
              text = "The event has been ended successfully.";
              icon = "success";
              break;

            case "END_UNSUCCESSFUL":
              title = "End Failed!";
              text = "Failed to end the event.";
              icon = "error";
              break;

            default:
              title = "";
              text = "";
              icon = "";
          }

          // Show SweetAlert if there's a valid status
          if (title && text && icon) {
            Swal.fire({
              title: title,
              text: text,
              icon: icon,
              confirmButtonText: "OK"
            });
          }

          unset($_SESSION['STATUS']);
        }
      });
    </script>

    <div class="modal fade" id="ongoingElectionsModal" tabindex="-1" aria-labelledby="ongoingElectionsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered ">
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

<!-- jQuery (Required) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<?php
include('includes/alerts.php');
?>

</html>