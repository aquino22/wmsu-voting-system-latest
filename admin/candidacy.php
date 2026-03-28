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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>WMSU i-Elect Admin | Candidacy </title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="vendors/typicons/typicons.css">
  <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->

  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <link rel="stylesheet" href="css/vertical-layout-light/style.css">
  <!-- endinject -->
  <link rel="shortcut icon" href="images/favicon.png" />

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- jQuery (Required) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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

    <nav class="navbar default-layout col-lg-12 col-12 p-0  d-flex align-items-top flex-row">
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
                        aria-controls="overview" aria-selected="true">Candidacy</a>
                    </li>

                  </ul>

                </div>
                <div class="tab-content tab-content-basic">
                  <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                    <div class="card card-rounded mb-5">
                      <div class="card-body">
                        <div class="row mt-4">
                          <div class="container  mb-5">

                            <div class="d-flex align-items-center">
                              <h3>
                                <b>Candidacy Period</b>

                              </h3>


                              <div class="ms-auto">
                                <button class="btn btn-primary text-white" data-bs-toggle="modal"
                                  data-bs-target="#candidacyModal">

                                  <small><i class="mdi mdi-plus-circle"></i>
                                    Set Candidacy Period
                                  </small>

                                </button>
                              </div>
                            </div>
                          </div>

                          <?php
                          $query = "
SELECT 
    c.id AS candidacy_id,
    c.start_period,
    c.end_period,
    c.status AS candidacy_status,

    e.id AS election_id,
    e.election_name,

    ay.semester,
    ay.year_label

FROM candidacy c
JOIN elections e 
    ON c.election_id = e.id
JOIN academic_years ay 
    ON e.academic_year_id = ay.id

WHERE c.status IN ('Ongoing', 'Upcoming', 'TBD')
ORDER BY c.created_at DESC
";

                          $stmt = $pdo->prepare($query);
                          $stmt->execute();
                          $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                          ?>


                          <div class="table-responsive">
                            <table id="electionTable" class="table table-striped table-bordered nowrap" style="width:100%">
                              <thead>
                                <tr>
                                  <th>School Year</th>
                                  <th>Semester</th>
                                  <th>Election Name</th>
                                  <th>Start Period</th>
                                  <th>End Period</th>
                                  <th>Status</th>
                                  <th>Action</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($elections as $election): ?>
                                  <tr>
                                    <td><?= htmlspecialchars($election['year_label']) ?></td>
                                    <td><?= htmlspecialchars($election['semester']) ?></td>
                                    <td><?= htmlspecialchars($election['election_name']) ?></td>
                                    <td>
                                      <?= date('F d, Y', strtotime($election['start_period'])) ?> at <br>
                                      <?= date('h:i A', strtotime($election['start_period'])) ?>
                                    </td>
                                    <td>
                                      <?= date('F d, Y', strtotime($election['end_period'])) ?> at <br>
                                      <?= date('h:i A', strtotime($election['end_period'])) ?>
                                    </td>
                                    <td>
                                      <span class="badge <?= ($election['candidacy_status'] == 'Ongoing') ? 'bg-success' : 'bg-warning' ?>">
                                        <?= htmlspecialchars($election['candidacy_status']) ?>
                                      </span>
                                    </td>
                                    <td>
                                      <button
                                        class="btn btn-warning btn-sm editBtn text-white"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editCandidacyModal"
                                        data-id="<?= $election['candidacy_id'] ?>"
                                        data-election-id="<?= $election['election_id'] ?>"
                                        data-startperiod="<?= $election['start_period'] ?>"
                                        data-endperiod="<?= $election['end_period'] ?>"
                                        data-status="<?= htmlspecialchars($election['candidacy_status']) ?>">
                                        <i class="mdi mdi-pencil"></i> Edit
                                      </button>

                                      <button class="button-view-danger deleteBtnCandidacy"
                                        data-id="<?= $election['candidacy_id'] ?>">
                                        <i class="mdi mdi-delete"></i> Delete
                                      </button>
                                    </td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>


                    <!-- page-body-wrapper ends -->
                  </div>
                  <!-- container-scroller -->
                  <!-- Election Form Modal -->
                  <div class="modal fade" id="candidacyModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="exampleModalLabel">Set Candidacy Period</h1>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="candidacyForm">
                          <div class="modal-body">


                            <div class="mb-3">
                              <label for="electionName" class="form-label">Election Name:</label>
                              <select class="form-select" id="electionName" name="election_id" required>
                                <option value="">Select an Election</option>
                              </select>
                            </div>

                            <!-- Duration Type -->
                            <div class="mb-3">
                              <label class="form-label">Duration Type:</label>
                              <div class="form-check" style="margin-left: 23px;">
                                <input class="form-check-input" type="radio" name="duration_type" id="candidacyDurationStandard" value="standard" checked>
                                <label class="form-check-label" for="candidacyDurationStandard">Standard (USC-Defined Duration) (Approximately 1 week after the election starts)</label>
                              </div>
                              <div class="form-check" style="margin-left: 23px;">
                                <input class="form-check-input" type="radio" name="duration_type" id="candidacyDurationCustom" value="custom">
                                <label class="form-check-label" for="candidacyDurationCustom">Custom Duration</label>
                              </div>
                            </div>

                            <!-- Read-only election info -->
                            <div class="mb-2">
                              <small id="electionMetaInfo" class="text-muted fst-italic d-block"></small>
                            </div>

                            <div class="mb-2">
                              <small id="electionScheduleInfo" class="text-muted fst-italic d-block"></small>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">
                                Start Period:
                                <small>(automatically 1 week from election start)</small>
                              </label>
                              <input type="datetime-local" class="form-control" id="startPeriod" name="start_period" required>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">End Period:</label>
                              <input type="datetime-local" class="form-control" id="endPeriod" name="end_period" required>
                            </div>

                            <!-- New Status Dropdown -->
                            <div class="mb-3">
                              <label for="candidacyStatus" class="form-label">Status:</label>
                              <select class="form-select" id="candidacyStatus" name="status" required>
                                <option value="" disabled readonly>Select Status</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Upcoming">Upcoming</option>
                              </select>
                            </div>


                          </div>


                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                                class="mdi mdi-close"></i> Close</button>
                            <button type="submit" class="btn btn-primary text-white" id="submitCandidacy"><i
                                class="mdi mdi-upload"></i> Save changes</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>

                  <script>
                    document.addEventListener('DOMContentLoaded', function() {
                      const startInput = document.getElementById('startPeriod');
                      const endInput = document.getElementById('endPeriod');
                      const durationRadios = document.querySelectorAll('input[name="duration_type"]');
                      const pad = n => String(n).padStart(2, '0');

                      // Function to format date for datetime-local
                      const formatDateTime = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;

                      // Set default startPeriod to now
                      const now = new Date();
                      startInput.value = formatDateTime(now);
                      startInput.min = formatDateTime(now);

                      // Set default endPeriod to 1 week later
                      const oneWeekLater = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
                      endInput.value = formatDateTime(oneWeekLater);
                      endInput.min = formatDateTime(now);

                      // Helper: check if Standard is selected
                      const isStandard = () => document.getElementById('candidacyDurationStandard').checked;

                      // Auto-update endPeriod when startPeriod changes (only if Standard)
                      startInput.addEventListener('input', function() {
                        if (isStandard()) {
                          const startDate = new Date(this.value);
                          if (!isNaN(startDate)) {
                            const newEnd = new Date(startDate.getTime() + 7 * 24 * 60 * 60 * 1000);
                            endInput.value = formatDateTime(newEnd);
                            endInput.min = formatDateTime(startDate); // optional
                            endInput.readOnly = true; // standard is readonly
                          }
                        }
                      });

                      // Handle radio button changes
                      durationRadios.forEach(radio => {
                        radio.addEventListener('change', function() {
                          if (isStandard()) {
                            // Standard → auto-fill end period 1 week after start
                            const startDate = new Date(startInput.value);
                            const newEnd = new Date(startDate.getTime() + 7 * 24 * 60 * 60 * 1000);
                            endInput.value = formatDateTime(newEnd);
                            endInput.readOnly = true;
                          } else {
                            // Custom → make inputs editable
                            endInput.readOnly = false;
                          }
                        });
                      });
                    });
                  </script>




                  <script>
                    let electionsData = [];

                    $(document).ready(function() {
                      // Load elections
                      $.ajax({
                        url: 'processes/elect/fetch_election_dropdown.php',
                        method: 'GET',
                        dataType: 'json',
                        success: function(response) {
                          if (!Array.isArray(response)) return;
                          electionsData = response;

                          response.forEach(election => {
                            $('#electionName').append(`
          <option value="${election.election_id}">
            ${election.election_name}
          </option>
        `);
                          });
                        }
                      });

                      // When election changes
                      $('#electionName').on('change', function() {
                        const electionId = $(this).val();
                        const election = electionsData.find(e => e.election_id == electionId);
                        if (!election) {
                          $('#electionMetaInfo').text('');
                          $('#electionScheduleInfo').text('');
                          $('#candidacyForm').removeData('electionStart');
                          return;
                        }

                        $('#electionMetaInfo').text(`School Year: ${election.year_label} | Semester: ${election.semester}`);

                        const start = new Date(election.start_period.replace(' ', 'T'));
                        const end = new Date(election.end_period.replace(' ', 'T'));
                        $('#electionScheduleInfo').text(`Election period: ${start.toLocaleString()} – ${end.toLocaleString()}`);

                        if (election.ay_start_date && election.ay_end_date) {
                          const ayStartDate = election.ay_start_date.split(' ')[0] + 'T00:00';
                          const ayEndDate = election.ay_end_date.split(' ')[0] + 'T23:59';
                          $('#startPeriod').attr('min', ayStartDate).attr('max', ayEndDate);
                          $('#endPeriod').attr('min', ayStartDate).attr('max', ayEndDate);
                        }

                        $('#candidacyForm').data('electionStart', election.start_period);

                        // Only auto-fill if Standard is selected
                        if ($('#candidacyDurationStandard').is(':checked')) {
                          fillStandardDates(election.start_period);
                        }

                        if (election.end_period) {
                          const electionEndForInput = election.end_period.replace(' ', 'T').substring(0, 16);
                          $('#startPeriod').attr('max', electionEndForInput);
                          $('#endPeriod').attr('max', electionEndForInput);
                        }
                      });

                      // Handle duration type change
                      $('input[name="duration_type"]').on('change', function() {
                        const electionStart = $('#candidacyForm').data('electionStart');
                        if ($(this).val() === 'standard' && electionStart) {
                          fillStandardDates(electionStart);
                        } else {
                          // Custom → make inputs editable, but do NOT change current values
                          $('#startPeriod, #endPeriod').prop('readonly', false);
                        }
                      });

                      function fillStandardDates(electionStartStr) {
                        const startInput = $('#startPeriod');
                        const endInput = $('#endPeriod');
                        const electionStart = new Date(electionStartStr.replace(' ', 'T'));

                        const candidacyStart = new Date(electionStart);
                        candidacyStart.setDate(candidacyStart.getDate() + 7);

                        const candidacyEnd = new Date(candidacyStart);
                        candidacyEnd.setDate(candidacyEnd.getDate() + 7);

                        const format = (d) => {
                          const pad = (n) => String(n).padStart(2, '0');
                          return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
                        };

                        startInput.val(format(candidacyStart)).prop('readonly', true);
                        endInput.val(format(candidacyEnd)).prop('readonly', true);
                      }
                    });
                  </script>

                  <script>
                    document.getElementById('candidacyForm').addEventListener('submit', function(e) {
                      e.preventDefault();

                      const form = this;
                      const submitBtn = document.querySelector('#submitCandidacy');

                      if (submitBtn) submitBtn.disabled = true;

                      Swal.fire({
                        title: 'Saving...',
                        text: 'Please wait while the candidacy period is being created.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                          Swal.showLoading();
                        }
                      });

                      fetch('create_candidacy.php', {
                          method: 'POST',
                          body: new FormData(form)
                        })
                        .then(res => res.json())
                        .then(data => {
                          Swal.close();

                          if (data.status === 'success') {
                            Swal.fire({
                              icon: 'success',
                              title: 'Success',
                              text: data.message,
                              confirmButtonColor: '#3085d6'
                            }).then(() => {
                              form.reset();
                              document.getElementById('electionMetaInfo').textContent = '';
                              document.getElementById('electionScheduleInfo').textContent = '';
                              location.reload();
                            });
                          } else {
                            Swal.fire({
                              icon: 'error',
                              title: 'Error',
                              text: data.message,
                              confirmButtonColor: '#d33'
                            });
                          }
                        })
                        .catch(err => {
                          console.error(err);
                          Swal.close();

                          Swal.fire({
                            icon: 'error',
                            title: 'Unexpected Error',
                            text: 'Something went wrong. Please try again.',
                            confirmButtonColor: '#d33'
                          });
                        })
                        .finally(() => {
                          if (submitBtn) submitBtn.disabled = false;
                        });
                    });
                  </script>



                  <!-- Edit Candidacy Modal -->
                  <div class="modal fade" id="editCandidacyModal" tabindex="-1" aria-labelledby="editCandidacyLabel" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="editCandidacyLabel">Editing Candidacy Period</h1>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                          <form id="editCandidacyForm" method="POST">
                            <input type="hidden" id="candidacyId" name="id">
                            <input type="hidden" id="electionId" name="electionId">
                            <!-- Election Name (read-only) -->
                            <div class="mb-3">
                              <label class="form-label">Election Name:</label>
                              <input type="text" class="form-control" id="electionNameEdit" readonly>
                            </div>

                            <!-- Election meta info -->
                            <div class="mb-2">
                              <small id="electionMetaInfoEdit" class="text-muted fst-italic d-block"></small>
                            </div>
                            <div class="mb-2">
                              <small id="electionScheduleInfoEdit" class="text-muted fst-italic d-block"></small>
                            </div>

                            <!-- Duration Type -->
                            <div class="mb-3">
                              <label class="form-label">Duration Type:</label>
                              <div class="form-check" style="margin-left: 23px;">
                                <input class="form-check-input" type="radio" name="duration_type_edit" id="editCandidacyDurationStandard" value="standard">
                                <label class="form-check-label" for="editCandidacyDurationStandard">Standard (USC-Defined Duration) (Approximately 1 week after the election starts)</label>
                              </div>
                              <div class="form-check" style="margin-left: 23px;">
                                <input class="form-check-input" type="radio" name="duration_type_edit" id="editCandidacyDurationCustom" value="custom">
                                <label class="form-check-label" for="editCandidacyDurationCustom">Custom Duration</label>
                              </div>
                            </div>

                            <!-- Start / End periods -->
                            <div class="mb-3">
                              <label class="form-label">Start Period:
                                <small>(automatically 1 week from election start)</small>
                              </label>
                              <input type="datetime-local" class="form-control" id="startPeriodEdit" name="start_period" required>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">End Period:</label>
                              <input type="datetime-local" class="form-control" id="endPeriodEdit" name="end_period" required>
                            </div>

                            <!-- Status -->
                            <div class="mb-3">
                              <label for="statusEdit" class="form-label">Status:</label>
                              <select class="form-select" id="statusEdit" name="status" required>
                                <option value="" disabled readonly>Select Status</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Upcoming">Upcoming</option>
                                <option value="Published">Published</option>
                                <option value="TBD">To Be Deleted</option>
                                <option value="Ended">Ended</option>
                              </select>
                            </div>

                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="mdi mdi-close"></i> Close
                              </button>
                              <button type="submit" class="btn btn-primary text-white" id="submitEditCandidacy">
                                <i class="mdi mdi-upload"></i> Save changes
                              </button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>

                  <script>
                    $(document).ready(function() {

                      // ===== helper: format Date object to YYYY-MM-DDTHH:MM =====
                      function formatDateTimeLocal(date) {
                        const pad = n => n.toString().padStart(2, '0');
                        return `${date.getFullYear()}-${pad(date.getMonth()+1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
                      }

                      // ===== elements =====
                      const $start = $('#startPeriodEdit');
                      const $end = $('#endPeriodEdit');
                      const $standard = $('#editCandidacyDurationStandard');
                      const $custom = $('#editCandidacyDurationCustom');

                      let electionStartDate = null; // will be set from your election data

                      // ===== Example: set electionStartDate dynamically =====
                      // Suppose you have election start date from AJAX fetch (replace below)
                      electionStartDate = new Date($('#electionMetaInfoEdit').data('start') || new Date());

                      // Set min to current date to block past dates
                      const now = new Date();
                      $start.attr('min', formatDateTimeLocal(now));
                      $end.attr('min', formatDateTimeLocal(now));

                      // ===== handle Standard vs Custom =====
                      function applyStandardDuration() {
                        // Standard: 1 week from election start
                        const stdStart = new Date(electionStartDate);
                        stdStart.setDate(stdStart.getDate() + 7);
                        const stdEnd = new Date(stdStart);
                        stdEnd.setDate(stdEnd.getDate() + 7); // standard duration is 1 week

                        $start.val(formatDateTimeLocal(stdStart));
                        $end.val(formatDateTimeLocal(stdEnd));

                        // disable manual edits
                        $start.prop('readonly', true);
                        $end.prop('readonly', true);
                      }

                      function applyCustomDuration() {
                        $start.prop('readonly', false);
                        $end.prop('readonly', false);
                      }

                      // Radio change
                      $standard.on('change', applyStandardDuration);
                      $custom.on('change', applyCustomDuration);

                      // Initialize depending on selected option
                      if ($standard.is(':checked')) applyStandardDuration();
                      if ($custom.is(':checked')) applyCustomDuration();

                    });
                  </script>
                  <script>
                    $(document).ready(function() {
                      $('#editCandidacyModal').on('show.bs.modal', function(event) {
                        const button = $(event.relatedTarget);

                        const candidacyId = button.data('id');
                        const electionId = button.data('election-id');
                        const start = button.data('startperiod');
                        const end = button.data('endperiod');
                        const status = button.data('status');

                        // Format dates for datetime-local (remove seconds if present)
                        const formatForInput = (dateStr) => {
                          if (!dateStr) return '';
                          return dateStr.replace(' ', 'T').substring(0, 16);
                        };

                        // Set values for the modal
                        $('#candidacyId').val(candidacyId);
                        $('#electionNameEdit').val(candidacyId);
                        $('#electionId').val(electionId);
                        $('#startPeriodEdit').val(formatForInput(start));
                        $('#endPeriodEdit').val(formatForInput(end));
                        $('#statusEdit').val(status);


                        // Fetch election info dynamically by ID
                        if (electionId) {
                          $.ajax({
                            url: 'processes/candidacy/fetch_election_details_by_id.php',
                            method: 'POST',
                            data: {
                              election_id: electionId
                            },
                            dataType: 'json',
                            success: function(res) {
                              if (res.status === 'success' && res.data) {
                                $('#electionNameEdit').val(res.data.election_name || '');
                                $('#electionMetaInfoEdit').text(`School Year: ${res.data.school_year_start} - ${res.data.school_year_end} | Semester: ${res.data.semester}`);

                                const s = new Date(res.data.start_period.replace(' ', 'T'));
                                const e = new Date(res.data.end_period.replace(' ', 'T'));
                                const opts = {
                                  year: 'numeric',
                                  month: 'long',
                                  day: 'numeric',
                                  hour: '2-digit',
                                  minute: '2-digit',
                                  hour12: true
                                };
                                $('#electionScheduleInfoEdit').text(`Election period: ${s.toLocaleString([], opts)} – ${e.toLocaleString([], opts)}`);

                                // Store election start for calculation
                                $('#editCandidacyForm').data('electionStart', res.data.start_period);

                                // Determine if current dates match Standard logic
                                const electionStart = new Date(res.data.start_period.replace(' ', 'T'));

                                // Standard: Start = Election + 7 days
                                const stdStart = new Date(electionStart);
                                stdStart.setDate(stdStart.getDate() + 7);

                                // Standard: End = Start + 7 days
                                const stdEnd = new Date(stdStart);
                                stdEnd.setDate(stdEnd.getDate() + 7);

                                // Helper to format date object to YYYY-MM-DDTHH:mm
                                const format = (d) => {
                                  const pad = (n) => String(n).padStart(2, '0');
                                  return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
                                };

                                const stdStartStr = format(stdStart);
                                const stdEndStr = format(stdEnd);
                                const currStartStr = $('#startPeriodEdit').val();
                                const currEndStr = $('#endPeriodEdit').val();

                                if (res.data.end_period) {
                                  const electionEndForInput = res.data.end_period.replace(' ', 'T').substring(0, 16);
                                  $('#startPeriodEdit').attr('max', electionEndForInput);
                                  $('#endPeriodEdit').attr('max', electionEndForInput);
                                }

                                if (currStartStr === stdStartStr && currEndStr === stdEndStr) {
                                  $('#editCandidacyDurationStandard').prop('checked', true);
                                } else {
                                  $('#editCandidacyDurationCustom').prop('checked', true);
                                }
                                updateEditCandidacyDates();
                              } else {
                                $('#electionNameEdit').val('Election not found');
                                $('#electionMetaInfoEdit, #electionScheduleInfoEdit').text('');
                              }
                            },
                            error: function() {
                              $('#electionNameEdit').val('Error fetching election');
                              $('#electionMetaInfoEdit, #electionScheduleInfoEdit').text('');
                            }
                          });
                        }
                      });

                      // Handle radio button change in Edit Modal
                      $('input[name="duration_type_edit"]').on('change', function() {
                        updateEditCandidacyDates();
                      });

                      function updateEditCandidacyDates() {
                        const electionStartStr = $('#editCandidacyForm').data('electionStart');
                        const isStandard = $('#editCandidacyDurationStandard').is(':checked');
                        const startInput = $('#startPeriodEdit');
                        const endInput = $('#endPeriodEdit');

                        if (isStandard && electionStartStr) {
                          const electionStart = new Date(electionStartStr.replace(' ', 'T'));

                          // Start: 1 week after election start
                          const candidacyStart = new Date(electionStart);
                          // candidacyStart.setDate(candidacyStart.getDate() + 7);

                          // End: 1 week duration (Standard)
                          const candidacyEnd = new Date(candidacyStart);
                          // candidacyEnd.setDate(candidacyEnd.getDate() + 7);

                          const format = (d) => {
                            const pad = (n) => String(n).padStart(2, '0');
                            return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
                          };

                          startInput.val(format(candidacyStart)).prop('readonly', true);
                          endInput.val(format(candidacyEnd)).prop('readonly', true);
                        } else if (!isStandard) {
                          startInput.prop('readonly', false);
                          endInput.prop('readonly', false);
                        }
                      }
                    });

                    $(document).ready(function() {
                      $('#editCandidacyForm').on('submit', function(e) {
                        e.preventDefault(); // prevent default form submit

                        const formData = {
                          id: $('#candidacyId').val(),
                          election_id: $('#electionId').val(), // hidden input with election ID
                          start_period: $('#startPeriodEdit').val(),
                          end_period: $('#endPeriodEdit').val(),
                          status: $('#statusEdit').val()
                        };

                        $.ajax({
                          url: 'processes/candidacy/update.php', // your update PHP script
                          type: 'POST',
                          data: formData,
                          dataType: 'json',
                          success: function(res) {
                            if (res.success) {
                              Swal.fire({
                                icon: 'success',
                                title: 'Updated!',
                                text: res.message,
                                timer: 2000,
                                showConfirmButton: false
                              }).then(() => {
                                $('#editCandidacyModal').modal('hide'); // close modal
                                location.reload(); // refresh table or page
                              });
                            } else {
                              Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.message
                              });
                            }
                          },
                          error: function() {
                            Swal.fire({
                              icon: 'error',
                              title: 'Server Error',
                              text: 'Something went wrong while updating.'
                            });
                          }
                        });
                      });
                    });
                  </script>










                  <!-- jQuery (Required) -->
                  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

                  <!-- DataTables CSS -->
                  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

                  <!-- DataTables JS -->
                  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>



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

                  <!-- jQuery (Required) -->
                  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

                  <!-- DataTables CSS -->
                  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

                  <!-- DataTables JS -->


</body>

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

<button class="back-to-top" id="backToTop" title="Go to top">
  <i class="mdi mdi-arrow-up"></i>
</button>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Include jQuery and SweetAlert2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script> -->

<!-- <script>
  buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
</script> -->

<script>
  $(document).ready(function() {
    const tableSelectors = [
      '#electionTable',
      '#pastElectionTable'
    ];

    tableSelectors.forEach(function(selector) {
      if ($(selector).length && !$.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable({
          responsive: true,
          paging: true,
          searching: true,
          ordering: true,
          info: true,
          pageLength: 10,
          order: [
            [0, 'desc']
          ],
          dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",

        });
      }
    });
  });
</script>

<script>
  let requirementCount = 0;

  function addRequirement() {
    requirementCount++;

    let requirementDiv = document.createElement("div");
    requirementDiv.classList.add("mb-3");
    requirementDiv.id = `requirement-${requirementCount}`;

    requirementDiv.innerHTML = `
        <label>Requirement Title:</label>
        <input type="text" class="form-control mb-2" name="requirementTitle[]" placeholder="Enter requirement title" required>

        <label>Input Type:</label>
        <select class="form-control mb-2" name="requirementType[]" required>
            <option value="text">Text</option>
            <option value="file">File Upload</option>
            <option value="date">Date</option>
            <option value="number">Number</option>
        </select>

        <button type="button" class="btn btn-danger mt-2" onclick="removeRequirement(${requirementCount})">Remove</button>
    `;

    document.getElementById("requirementsContainer").appendChild(requirementDiv);
  }

  function removeRequirement(id) {
    document.getElementById(`requirement-${id}`).remove();
  }



  window.addEventListener('DOMContentLoaded', () => {
    const currentYear = new Date().getFullYear();
    document.getElementById('schoolYearStart').value = currentYear;
    document.getElementById('schoolYearEnd').value = currentYear + 1;
  });
</script>
<script>
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.deleteBtnCandidacy');
    if (!btn) return;

    const id = btn.dataset.id;
    if (!id) return;

    Swal.fire({
      title: 'Delete Candidacy?',
      text: 'This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d'
    }).then(result => {

      if (!result.isConfirmed) return;

      Swal.fire({
        title: 'Deleting...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      fetch('delete_candidacy.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: new URLSearchParams({
            id
          })
        })
        .then(res => res.json())
        .then(data => {
          Swal.close();

          if (data.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Deleted',
              text: data.message,
              confirmButtonColor: '#3085d6'
            }).then(() => {
              /* Remove row from UI */
              const row = btn.closest('tr');
              if (row) row.remove();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message
            });
          }
        })
        .catch(err => {
          console.error(err);
          Swal.close();
          Swal.fire({
            icon: 'error',
            title: 'Unexpected Error',
            text: 'Please try again.'
          });
        });
    });
  });
</script>




</html>