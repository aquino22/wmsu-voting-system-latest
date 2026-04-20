<?php
session_start();
include('includes/conn_new.php');
include('includes/conn_archived.php');
include('includes/conn_archived_conn.php');
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
  <title>WMSU i-Elect Admin | History </title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="vendors/typicons/typicons.css">
  <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
  <link rel="stylesheet" href="js/select.dataTables.min.css">
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <link rel="stylesheet" href="css/vertical-layout-light/style.css">
  <!-- endinject -->
  <link rel="shortcut icon" href="images/favicon.png" />

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<style>

</style>

<body>
  <div class="container-scroller">
    <!-- partial:partials/_navbar.html -->
    <?php
    $admin_full_name = 'WMSU Admin';
    $admin_phone_number = '+631234567890';
    $admin_email = 'wmsu_admin@wmsu.edu.ph';
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
              $stmt = $pdo_new->prepare("
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


      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                  <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                      <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">History</a>
                    </li>
                  </ul>
                </div>
                <?php
                // Get all academic years
                $academicYears = $pdo->query("
SELECT id, year_label, semester
FROM archived_academic_years
ORDER BY start_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <div class="tab-content tab-content-basic">
                  <div class="card">
                    <div class="card-body">
                      <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">

                        <div class="card shadow-sm">
                          <div class="card-header">
                            <h5 class="mb-0">Election History by Academic Years</h5>
                          </div>

                          <div class="card-body">

                            <div class="accordion" id="academicYearAccordion">

                              <?php $yearIndex = 0; ?>
                              <?php foreach ($academicYears as $ay): ?>
                                <?php $yearIndex++; ?>

                                <div class="accordion-item">
                                  <h2 class="accordion-header" id="headingYear<?= $yearIndex ?>">
                                    <button class="accordion-button <?= $yearIndex != 1 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseYear<?= $yearIndex ?>">
                                      <?= $ay['semester'] ?> | Academic Year <?= $ay['year_label'] ?>
                                    </button>
                                  </h2>

                                  <div id="collapseYear<?= $yearIndex ?>" class="accordion-collapse collapse <?= $yearIndex == 1 ? 'show' : '' ?>" data-bs-parent="#academicYearAccordion">
                                    <div class="accordion-body">

                                      <?php
                                      // Fetch elections for this academic year
                                      $stmt = $pdo->prepare("
                        SELECT id AS election_id, election_name, start_period AS election_start, end_period AS election_end, voting_period_id
                        FROM archived_elections
                        WHERE academic_year_id = ?
                        ORDER BY start_period DESC
                      ");
                                      $stmt->execute([$ay['id']]);
                                      ?>

                                      <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                        // Fetch counts
                                        $parties = $pdo->prepare("SELECT COUNT(*) FROM archived_parties WHERE voting_period_id = ?");
                                        $parties->execute([$row['voting_period_id']]);
                                        $partiesCount = $parties->fetchColumn();

                                        $candidates = $pdo->prepare("SELECT COUNT(*) FROM archived_candidates WHERE voting_period_id = ?");
                                        $candidates->execute([$row['voting_period_id']]);
                                        $candidatesCount = $candidates->fetchColumn();

                                        $voters = $pdo->prepare("SELECT COUNT(*) FROM archived_voters WHERE voting_period_id = ?");
                                        $voters->execute([$row['voting_period_id']]);
                                        $votersCount = $voters->fetchColumn();

                                        $candidacy = $pdo->prepare("SELECT start_period,end_period FROM archived_candidacies WHERE election_id = ? LIMIT 1");
                                        $candidacy->execute([$row['election_id']]);
                                        $candidacy = $candidacy->fetch(PDO::FETCH_ASSOC);

                                        $voting = $pdo->prepare("SELECT start_period,end_period FROM archived_voting_periods WHERE election_id = ? LIMIT 1");
                                        $voting->execute([$row['election_id']]);
                                        $voting = $voting->fetch(PDO::FETCH_ASSOC);
                                      ?>

                                        <!-- Election Section -->
                                        <div class="mb-4 p-3 border rounded">
                                          <h6 class="mb-3">
                                            <?= strtoupper($row['election_name'] ?? 'Election') ?>
                                          </h6>

                                          <div class="row">
                                            <div class="col-md-6">
                                              <p><strong>Election Period:</strong> <?= date("F j, Y", strtotime($row['election_start'])) ?> – <?= date("F j, Y", strtotime($row['election_end'])) ?></p>
                                              <p><strong>Candidacy Period:</strong> <?= date("F j, Y", strtotime($candidacy['start_period'])) ?> – <?= date("F j, Y", strtotime($candidacy['end_period'])) ?></p>
                                              <p><strong>Voting Period:</strong> <?= date("F j, Y", strtotime($voting['start_period'])) ?> – <?= date("F j, Y", strtotime($voting['end_period'])) ?></p>
                                            </div>

                                            <div class="col-md-6">
                                              <p><strong>Parties:</strong> <?= $partiesCount ?></p>
                                              <p><strong>Voters:</strong> <?= $votersCount ?></p>
                                              <p><strong>Candidates:</strong> <?= $candidatesCount ?></p>
                                            </div>
                                          </div>

                                          <div class="text-center mt-3">
                                            <a style="color:white !important" href="view_history.php?voting_period_id=<?= $row['voting_period_id'] ?>&academic_year_id=<?= $ay['id'] ?>" class="badge bg-primary text-decoration-none p-2">
                                              <i class="bi bi-eye-fill"></i> View More
                                            </a>
                                          </div>
                                        </div>
                                        <hr>

                                      <?php endwhile; ?>

                                    </div>
                                  </div>
                                </div>

                              <?php endforeach; ?>

                            </div>

                          </div>
                        </div>

                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>


      </div>

      <!-- Scripts -->
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
      <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
      <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
      <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>


      <!-- Include other scripts as needed -->
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
      <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
      <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

      <script>
        async function checkElectionStatus() {
          try {
            const response = await fetch('check_status.php');
            const data = await response.json();

            if (data.expired && data.elections.length > 0) {
              // Loop through each expired election found
              for (const election of data.elections) {
                await Swal.fire({
                  title: 'Voting Period Concluded',
                  text: `The period for ${election.election_name} has officially ended. Click 'Publish Results' to proceed. This can still be dismissed if further information checking on periods are needed to be performed.`,
                  icon: 'info',
                  showCancelButton: true,
                  confirmButtonText: 'Publish Results',
                  cancelButtonText: 'Dismiss',
                  confirmButtonColor: '#3085d6',
                  cancelButtonColor: '#6e7881',
                  allowOutsideClick: true,
                  allowEscapeKey: true
                }).then((result) => {
                  if (result.isConfirmed) {
                    window.location.href = election.redirect_to;
                  }
                });
              }
            }
          } catch (err) {
            console.error('Status Check Error:', err);
          }
        }

        document.addEventListener('DOMContentLoaded', checkElectionStatus);
      </script>

      <button class="back-to-top" id="backToTop" title="Go to top">
        <i class="mdi mdi-arrow-up"></i>
      </button>

</body>

</html>