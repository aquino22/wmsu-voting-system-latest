<?php
session_start();
include('includes/conn.php');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'moderator') {
  session_destroy();
  session_start();
  $_SESSION['STATUS'] = "NON_MODERATOR";
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
  <title>Moderator </title>
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

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

</head>



<body>
  <div class="container-scroller">
    <!-- partial:partials/_navbar.html -->
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
            <h1 class="welcome-text">Welcome, <span class="text-white fw-bold">WMSU Moderator</span>
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

      <div id="right-sidebar" class="settings-panel">
        <i class="settings-close ti-close"></i>
        <ul class="nav nav-tabs border-top" id="setting-panel" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="todo-tab" data-bs-toggle="tab" href="#todo-section" role="tab"
              aria-controls="todo-section" aria-expanded="true">TO DO LIST</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="chats-tab" data-bs-toggle="tab" href="#chats-section" role="tab"
              aria-controls="chats-section">CHATS</a>
          </li>
        </ul>
        <div class="tab-content" id="setting-content">
          <div class="tab-pane fade show active scroll-wrapper" id="todo-section" role="tabpanel"
            aria-labelledby="todo-section">
            <div class="add-items d-flex px-3 mb-0">
              <form class="form w-100">
                <div class="form-group d-flex">
                  <input type="text" class="form-control todo-list-input" placeholder="Add To-do">
                  <button type="submit" class="add btn btn-primary todo-list-add-btn" id="add-task">Add</button>
                </div>
              </form>
            </div>
            <div class="list-wrapper px-3">
              <ul class="d-flex flex-column-reverse todo-list">
                <li>
                  <div class="form-check">
                    <label class="form-check-label">
                      <input class="checkbox" type="checkbox">
                      Team review meeting at 3.00 PM
                    </label>
                  </div>
                  <i class="remove ti-close"></i>
                </li>
                <li>
                  <div class="form-check">
                    <label class="form-check-label">
                      <input class="checkbox" type="checkbox">
                      Prepare for presentation
                    </label>
                  </div>
                  <i class="remove ti-close"></i>
                </li>
                <li>
                  <div class="form-check">
                    <label class="form-check-label">
                      <input class="checkbox" type="checkbox">
                      Resolve all the low priority tickets due today
                    </label>
                  </div>
                  <i class="remove ti-close"></i>
                </li>
                <li class="completed">
                  <div class="form-check">
                    <label class="form-check-label">
                      <input class="checkbox" type="checkbox" checked>
                      Schedule meeting for next week
                    </label>
                  </div>
                  <i class="remove ti-close"></i>
                </li>
                <li class="completed">
                  <div class="form-check">
                    <label class="form-check-label">
                      <input class="checkbox" type="checkbox" checked>
                      Project review
                    </label>
                  </div>
                  <i class="remove ti-close"></i>
                </li>
              </ul>
            </div>
            <h4 class="px-3 text-muted mt-5 fw-light mb-0">Events</h4>
            <div class="events pt-4 px-3">
              <div class="wrapper d-flex mb-2">
                <i class="ti-control-record text-primary me-2"></i>
                <span>Feb 11 2018</span>
              </div>
              <p class="mb-0 font-weight-thin text-gray">Creating component
                page build a js</p>
              <p class="text-gray mb-0">The total number of sessions</p>
            </div>
            <div class="events pt-4 px-3">
              <div class="wrapper d-flex mb-2">
                <i class="ti-control-record text-primary me-2"></i>
                <span>Feb 7 2018</span>
              </div>
              <p class="mb-0 font-weight-thin text-gray">Meeting with
                Alisa</p>
              <p class="text-gray mb-0 ">Call Sarah Graves</p>
            </div>
          </div>
          <!-- To do section tab ends -->
          <div class="tab-pane fade" id="chats-section" role="tabpanel" aria-labelledby="chats-section">
            <div class="d-flex align-items-center justify-content-between border-bottom">
              <p class="settings-heading border-top-0 mb-3 pl-3 pt-0 border-bottom-0 pb-0">Friends</p>
              <small class="settings-heading border-top-0 mb-3 pt-0 border-bottom-0 pb-0 pr-3 fw-normal">See
                All</small>
            </div>
            <ul class="chat-list">
              <li class="list active">
                <div class="profile"><img src="images/faces/face1.jpg" alt="image"><span class="online"></span></div>
                <div class="info">
                  <p>Thomas Douglas</p>
                  <p>Available</p>
                </div>
                <small class="text-muted my-auto">19 min</small>
              </li>
              <li class="list">
                <div class="profile"><img src="images/faces/face2.jpg" alt="image"><span class="offline"></span></div>
                <div class="info">
                  <div class="wrapper d-flex">
                    <p>Catherine</p>
                  </div>
                  <p>Away</p>
                </div>
                <div class="badge badge-success badge-pill my-auto mx-2">4</div>
                <small class="text-muted my-auto">23 min</small>
              </li>
              <li class="list">
                <div class="profile"><img src="images/faces/face3.jpg" alt="image"><span class="online"></span></div>
                <div class="info">
                  <p>Daniel Russell</p>
                  <p>Available</p>
                </div>
                <small class="text-muted my-auto">14 min</small>
              </li>
              <li class="list">
                <div class="profile"><img src="images/faces/face4.jpg" alt="image"><span class="offline"></span></div>
                <div class="info">
                  <p>James Richardson</p>
                  <p>Away</p>
                </div>
                <small class="text-muted my-auto">2 min</small>
              </li>
              <li class="list">
                <div class="profile"><img src="images/faces/face5.jpg" alt="image"><span class="online"></span></div>
                <div class="info">
                  <p>Madeline Kennedy</p>
                  <p>Available</p>
                </div>
                <small class="text-muted my-auto">5 min</small>
              </li>
              <li class="list">
                <div class="profile"><img src="images/faces/face6.jpg" alt="image"><span class="online"></span></div>
                <div class="info">
                  <p>Sarah Graves</p>
                  <p>Available</p>
                </div>
                <small class="text-muted my-auto">47 min</small>
              </li>
            </ul>
          </div>
          <!-- chat tab ends -->
        </div>
      </div>

      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          <li class="nav-item active-link">
            <a class="nav-link active-link" href="index.php" style="background-color: #B22222 !important;">
              <i class="mdi mdi-grid-large menu-icon" style="color: white !important;"></i>
              <span class="menu-title" style="color: white !important;">Dashboard</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="voter-list.php">
              <i class="menu-icon mdi mdi-account-group"></i>
              <span class="menu-title">Voter List</span>
            </a>
          </li>


          <style>
            .nav-link.disabled.qr-disabled {
              pointer-events: auto !important;
              /* allow JS to catch click */
              cursor: not-allowed;
              opacity: 0.5;
            }
          </style>

          <?php
          // Get ALL ongoing voting periods
          $stmt = $pdo->prepare("
    SELECT *
    FROM voting_periods
    WHERE status IN ('Ongoing')
");
          $stmt->execute();

          $ongoingVotingPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);

          // Check if there is at least ONE ongoing period
          $hasOngoingVoting = !empty($ongoingVotingPeriods);


          ?>

          <li class="nav-item">
            <?php
            // Enable QR scanning ONLY when there's at least one ongoing period
            $isDisabled = !$hasOngoingVoting;
            $linkClass = $isDisabled ? 'nav-link disabled qr-disabled' : 'nav-link';
            $linkHref = $isDisabled ? '#' : 'vote_qr_code.php';
            ?>
            <a class="<?= $linkClass ?>"
              href="<?= $linkHref ?>"
              <?= $isDisabled ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
              <i class="menu-icon mdi mdi-qrcode-scan"></i>
              <span class="menu-title">QR Code Scanning</span>
            </a>
          </li>

          <script>
            window.addEventListener('load', function() {
              const disabledLink = document.querySelector('.qr-disabled');

              if (disabledLink) {
                disabledLink.addEventListener('click', function(e) {
                  e.preventDefault(); // Prevent navigation

                  Swal.fire({
                    icon: 'info',
                    title: 'Unavailable',
                    text: 'QR Code Scanning is only available during an ongoing voting period.',
                    confirmButtonText: 'OK'
                  });
                });
              }
            });
          </script>
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
                        aria-controls="overview" aria-selected="true">Dashboard</a>
                    </li>

                  </ul>

                </div>

                <div class="row mt-3">
                  <div class="card px-5 pt-5">
                    <div class="d-flex align-items-center">
                      <!-- <h3 class="ml-5"><b>User Activities</b></h3> -->
                      <div class=" ms-auto" aria-hidden="true">
                        <div class="text-center mt-3">

                        </div>
                      </div>
                    </div>
                    <div class="card-body">

                      <?php
                      $total_voters = 0;
                      $total_votes = 0;
                      $total_candidates = 0;

                      $moderator_email = $_SESSION['email'] ?? '';

                      // ----------------------------------------------------
                      // 1. Fetch moderator data
                      // ----------------------------------------------------
                      $stmt = $pdo->prepare("SELECT * FROM moderators WHERE email = ?");
                      $stmt->execute([$moderator_email]);
                      $moderator_data = $stmt->fetch(PDO::FETCH_ASSOC);

                      if (!$moderator_data) {
                        die("Moderator data not found.");
                      }

                      // Decode assigned precincts
                      $assigned_precincts = json_decode($moderator_data['precinct'], true);
                      if (!is_array($assigned_precincts)) {
                        $assigned_precincts = [$moderator_data['precinct']];
                      }

                      // ----------------------------------------------------
                      // 2. Fetch ongoing / paused voting periods WITH election
                      // ----------------------------------------------------
                      $stmt = $pdo->prepare("
    SELECT 
        vp.id AS voting_period_id,
        vp.start_period,
        vp.end_period,
        vp.status,

        e.id AS election_id,
        e.election_name as election_name
    FROM voting_periods vp
    JOIN elections e ON vp.election_id = e.id
    WHERE vp.status IN ('Ongoing', 'Paused')
");
                      $stmt->execute();
                      $ongoing_periods = $stmt->fetchAll(PDO::FETCH_ASSOC);

                      $dashboardData = [];

                      // ----------------------------------------------------
                      // 3. Loop voting periods
                      // ----------------------------------------------------
                      foreach ($ongoing_periods as $period) {

                        $voting_period_id = $period['voting_period_id'];
                        $election_id      = $period['election_id'];
                        $election_name   = $period['election_name'];



                        // ------------------------------------------------
                        // Fetch precincts assigned to THIS election
                        // ------------------------------------------------
                        $placeholders = implode(',', array_fill(0, count($assigned_precincts), '?'));

                        $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name
        FROM precincts p
        JOIN precinct_elections pe 
            ON pe.precinct_id = p.id
        WHERE pe.election_name = ?
          AND p.id IN ($placeholders)
    ");
                        $stmt->execute(array_merge([$election_id], $assigned_precincts));
                        $precincts_for_period = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        // ------------------------------------------------
                        // Candidate count (ONCE per election)
                        // ------------------------------------------------
                        $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT c.id)
        FROM candidates c
        JOIN registration_forms rf 
            ON c.form_id = rf.id
        WHERE rf.election_name = ?
          AND c.status = 'accepted'
    ");
                        $stmt->execute([$election_id]);
                        $candidate_count = (int)$stmt->fetchColumn();

                        $period_summary = [
                          'period'     => $period,
                          'precincts'  => []
                        ];

                        // ------------------------------------------------
                        // Loop precincts
                        // ------------------------------------------------
                        foreach ($precincts_for_period as $precinct) {

                          // Total voters in precinct
                          $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM precinct_voters 
            WHERE precinct = ?
        ");
                          $stmt->execute([$precinct['id']]);
                          $voter_count = (int)$stmt->fetchColumn();


                          // Votes cast in this voting period & precinct
                          $stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT v.student_id)
    FROM votes v
    JOIN precinct_voters pv 
        ON v.student_id = pv.student_id
    WHERE pv.precinct = ?
      AND v.voting_period_id = ?
");
                          $stmt->execute([
                            $precinct['id'],
                            $voting_period_id
                          ]);
                          $votes_cast = (int)$stmt->fetchColumn();

                          $period_summary['precincts'][] = [
                            'id'          => $precinct['id'],
                            'name'        => $precinct['name'],
                            'voters'      => $voter_count,
                            'votes_cast'  => $votes_cast,
                            'candidates'  => $candidate_count
                          ];
                        }

                        $dashboardData[] = $period_summary;
                      }

                      // ----------------------------------------------------
                      // 4. Totals
                      // ----------------------------------------------------
                      foreach ($dashboardData as $period_summary) {
                        // Add candidates count ONCE per election/period
                        if (!empty($period_summary['precincts'])) {
                          $total_candidates += $period_summary['precincts'][0]['candidates'];
                        }

                        foreach ($period_summary['precincts'] as $precinct) {
                          $total_voters     += $precinct['voters'];
                          $total_votes      += $precinct['votes_cast'];
                        }
                      }
                      ?>





                      <!DOCTYPE html>
                      <html lang="en">

                      <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Moderator Dashboard</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                          .card {
                            margin-bottom: 20px;
                          }

                          .timer {
                            font-size: 1.5rem;
                            font-weight: bold;
                          }
                        </style>
                      </head>

                      <body>
                        <div class="container-fluid mt-4">
                          <h1 class="text-center mb-4"><b>Moderator Dashboard</b></h1>

                          <br>
                          <!-- Welcome Message -->
                          <div class="alert alert-info text-center">
                            <h6>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Moderator'); ?>!</h6>
                          </div>

                          <br>

                          <!-- Stats Row -->
                          <div class="row mb-4">
                            <div class="col-md-4">
                              <div class="card text-center">
                                <div class="card-body">
                                  <h5 class="card-title">Total Voters</h5>
                                  <p class="card-text display-4"><?= $total_voters ?></p>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="card text-center">
                                <div class="card-body">
                                  <h5 class="card-title">Total Candidates</h5>
                                  <p class="card-text display-4"><?= $total_candidates ?></p>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="card text-center">
                                <div class="card-body">
                                  <h5 class="card-title">Votes Cast</h5>
                                  <p class="card-text display-4"><?= $total_votes ?></p>
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Ongoing Voting Periods -->
                          <?php if (!empty($dashboardData)): ?>
                            <?php foreach ($dashboardData as $period_summary): ?>
                              <?php
                              $status = $period_summary['period']['status'];

                              $badgeClass = match ($status) {
                                'Ongoing'     => 'bg-success',
                                'Paused'      => 'bg-warning text-dark',
                                'Rescheduled' => 'bg-info text-dark',
                                default       => 'bg-secondary',
                              };
                              ?>

                              <div class="card mb-4">
                                <div class="card-header">
                                  <div class="d-flex align-items-center">
                                    <h5 class="mb-0">
                                      <b>Voting Period: </b><?= htmlspecialchars($election_name) ?>
                                    </h5>

                                    <div class="ms-auto">
                                      <h6 class="mb-0">
                                        <strong>Status:</strong>
                                        <span class="badge <?= $badgeClass ?>">
                                          <?= htmlspecialchars($status) ?>
                                        </span>
                                      </h6>
                                    </div>
                                  </div>



                                </div>
                                <div class="card-body">

                                  <div class="d-flex flex-wrap align-items-start gap-4">

                                    <div>
                                      <h6 class="mb-1">
                                        <strong>Date & Time Start:</strong><br>
                                        <span class="text-muted">
                                          <?= !empty($period_summary['period']['start_period'])
                                            ? date('F j, Y \a\t g:i A', strtotime($period_summary['period']['start_period']))
                                            : 'Not set'
                                          ?>
                                        </span>
                                      </h6>
                                    </div>

                                    <div>
                                      <h6 class="mb-1">
                                        <strong>Date & Time End:</strong><br>
                                        <span class="text-muted">
                                          <?= !empty($period_summary['period']['end_period'])
                                            ? date('F j, Y \a\t g:i A', strtotime($period_summary['period']['end_period']))
                                            : 'Not set'
                                          ?>
                                        </span>
                                      </h6>
                                    </div>


                                    <div class="ms-auto text-end">
                                      <h6 class="mb-1">
                                        <strong>Time Remaining:</strong>
                                      </h6>
                                      <span
                                        class="timer fw-semibold text-danger"
                                        data-end="<?= $period_summary['period']['end_period'] ?>">
                                      </span>
                                    </div>

                                  </div>



                                  <?php if (!empty($period_summary['period']['end_period'])): ?>
                                    <br>


                                  <?php endif; ?>

                                  <!-- Precinct Summary -->
                                  <h3 class="mt-3 text-center bold"><b>Precinct Summary</b></h3>
                                  <br>
                                  <table class="table table-striped">
                                    <thead>
                                      <tr>
                                        <th>Precinct</th>
                                        <th>Voters</th>
                                        <th>Candidates</th>
                                        <th>Votes Cast</th>

                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php foreach ($period_summary['precincts'] as $precinct): ?>
                                        <tr>
                                          <td><?= htmlspecialchars($precinct['name']) ?></td>
                                          <td><?= $precinct['voters'] ?></td>
                                          <td><?= $precinct['candidates'] ?></td>
                                          <td><?= $precinct['votes_cast'] ?></td>

                                        </tr>
                                      <?php endforeach; ?>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            <?php endforeach; ?>
                          <?php else: ?>
                            <h5 class="text-center">No ongoing voting periods or assigned precincts.</h5>
                          <?php endif; ?>

                        </div>

                        <!-- Timer JS for multiple periods -->
                        <script>
                          document.addEventListener("DOMContentLoaded", function() {
                            const timers = document.querySelectorAll(".timer");

                            timers.forEach(timer => {
                              const endTime = new Date(timer.dataset.end).getTime();

                              function update() {
                                let now = new Date().getTime();
                                let diff = endTime - now;

                                if (diff > 0) {
                                  let h = Math.floor(diff / (1000 * 60 * 60));
                                  let m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                  let s = Math.floor((diff % (1000 * 60)) / 1000);

                                  timer.textContent = `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
                                } else {
                                  timer.textContent = "00:00:00";
                                }
                              }

                              update();
                              setInterval(update, 1000);
                            });
                          });
                        </script>


                      </body>

                      </html>
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

        <script>
          $(document).ready(function() {
            $('#userActivityTable').DataTable({
              "paging": true,
              "searching": true,
              "ordering": true,
              "info": true
            });
          });

          $(document).ready(function() {
            $('#eventsTable').DataTable({
              "paging": true, // Enable pagination
              "searching": true, // Enable search
              "ordering": true, // Enable sorting
              "info": true // Show info about entries
            });
          });
        </script>

        <script>
          document.addEventListener("DOMContentLoaded", function() {
            const {
              jsPDF
            } = window.jspdf;

            // President Chart
            const ctx1 = document.getElementById("presidentChart").getContext("2d");
            const presidentChart = new Chart(ctx1, {
              type: "bar",
              data: {
                labels: ["Candidate A", "Candidate B", "Candidate C"],
                datasets: [{
                  label: "Votes",
                  data: [450, 350, 220],
                  backgroundColor: ["#ff6384", "#36a2eb", "#ffce56"],
                  borderColor: ["#ff6384", "#36a2eb", "#ffce56"],
                  borderWidth: 1
                }]
              },
              options: {
                responsive: true,
                scales: {
                  y: {
                    beginAtZero: true,
                    stepSize: 50
                  }
                }
              }
            });

            // Vice-President Chart
            const ctx2 = document.getElementById("vpChart").getContext("2d");
            const vpChart = new Chart(ctx2, {
              type: "bar",
              data: {
                labels: ["Candidate X", "Candidate Y", "Candidate Z"],
                datasets: [{
                  label: "Votes",
                  data: [500, 420, 300],
                  backgroundColor: ["#4bc0c0", "#9966ff", "#ff9f40"],
                  borderColor: ["#4bc0c0", "#9966ff", "#ff9f40"],
                  borderWidth: 1
                }]
              },
              options: {
                responsive: true,
                scales: {
                  y: {
                    beginAtZero: true,
                    stepSize: 50
                  }
                }
              }
            });

            // Real-time updates (example: update every 5 seconds)
            setInterval(() => {
              presidentChart.data.datasets[0].data = presidentChart.data.datasets[0].data.map(votes =>
                votes + Math.floor(Math.random() * 10));
              presidentChart.update();

              vpChart.data.datasets[0].data = vpChart.data.datasets[0].data.map(votes => votes + Math
                .floor(Math.random() * 10));
              vpChart.update();
            }, 5000);

            // Download Both Charts as PNG
            document.getElementById("downloadPNG").addEventListener("click", function() {
              // Create a temporary canvas for combining both charts
              const combinedCanvas = document.createElement('canvas');
              const combinedCtx = combinedCanvas.getContext('2d');

              // Set canvas size to fit both charts side by side
              const width = presidentChart.width + vpChart.width;
              const height = Math.max(presidentChart.height, vpChart.height);
              combinedCanvas.width = width;
              combinedCanvas.height = height;

              // Draw both charts on the combined canvas
              combinedCtx.drawImage(presidentChart.canvas, 0, 0);
              combinedCtx.drawImage(vpChart.canvas, presidentChart.width, 0);

              // Convert to image data
              const combinedImage = combinedCanvas.toDataURL("image/png");

              // Create a download link
              const link = document.createElement("a");
              link.href = combinedImage;
              link.download = "vote_charts_combined.png";
              link.click();
            });

            // Download Both Charts as PDF
            document.getElementById("downloadPDF").addEventListener("click", function() {
              const pdf = new jsPDF();

              // Get images of both charts
              const presidentImage = document.getElementById("presidentChart").toDataURL("image/png");
              const vpImage = document.getElementById("vpChart").toDataURL("image/png");

              // Add both images to the PDF
              pdf.addImage(presidentImage, "PNG", 15, 30, 180, 100); // Add President Chart
              pdf.addPage();
              pdf.addImage(vpImage, "PNG", 15, 30, 180, 100); // Add Vice-President Chart

              // Save the PDF
              pdf.save("vote_charts_combined.pdf");
            });
          });
        </script>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- jQuery (Required) -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

</body>

</html>