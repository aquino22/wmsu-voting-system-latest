<?php
include('includes/conn.php');
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Voters</title>
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="vendors/typicons/typicons.css">
  <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
  <link rel="stylesheet" href="js/select.dataTables.min.css">
  <link rel="stylesheet" href="css/vertical-layout-light/style.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>

<body>
  <div class="container-scroller">

    <!-- Navbar -->
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
            <h1 class="welcome-text">Welcome, <span class="text-white fw-bold">WMSU Moderator</span></h1>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item dropdown d-none d-lg-block user-dropdown">
            <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
              <img class="img-xs rounded-circle" src="images/wmsu-logo.png" style="background-color: white;" alt="Profile image">
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
              <a class="dropdown-item" href="processes/accounts/logout.php">
                <i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign Out
              </a>
            </div>
          </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
          data-bs-toggle="offcanvas">
          <span class="mdi mdi-menu"></span>
        </button>
      </div>
    </nav>

    <div class="container-fluid page-body-wrapper">

      <!-- Settings Panel -->
      <div id="right-sidebar" class="settings-panel">
        <i class="settings-close ti-close"></i>
        <ul class="nav nav-tabs border-top" id="setting-panel" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="todo-tab" data-bs-toggle="tab" href="#todo-section"
              role="tab" aria-controls="todo-section" aria-expanded="true">TO DO LIST</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="chats-tab" data-bs-toggle="tab" href="#chats-section"
              role="tab" aria-controls="chats-section">CHATS</a>
          </li>
        </ul>
        <div class="tab-content" id="setting-content">
          <div class="tab-pane fade show active scroll-wrapper" id="todo-section" role="tabpanel">
            <div class="add-items d-flex px-3 mb-0">
              <form class="form w-100">
                <div class="form-group d-flex">
                  <input type="text" class="form-control todo-list-input" placeholder="Add To-do">
                  <button type="submit" class="add btn btn-primary todo-list-add-btn" id="add-task">Add</button>
                </div>
              </form>
            </div>
          </div>
          <div class="tab-pane fade" id="chats-section" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between border-bottom">
              <p class="settings-heading border-top-0 mb-3 pl-3 pt-0 border-bottom-0 pb-0">Friends</p>
              <small class="settings-heading border-top-0 mb-3 pt-0 border-bottom-0 pb-0 pr-3 fw-normal">See All</small>
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
        </div>
      </div>

      <!-- Sidebar -->
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          <li class="nav-item active-link">
            <a class="nav-link active-link" href="index.php">
              <i class="mdi mdi-grid-large menu-icon"></i>
              <span class="menu-title">Dashboard</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="voter-list.php" style="background-color: #B22222 !important;">
              <i class="menu-icon mdi mdi-account-group" style="color: white !important;"></i>
              <span class="menu-title" style="color: white !important;">Voter List</span>
            </a>
          </li>

          <style>
            .nav-link.disabled.qr-disabled {
              pointer-events: auto !important;
              cursor: not-allowed;
              opacity: 0.5;
            }
          </style>

          <?php
          /*
           * Check whether ANY voting period is currently Ongoing.
           * No LIMIT — COUNT is sufficient and correct for a boolean check.
           * The QR link is enabled as long as at least one period is active.
           */
          $stmt = $pdo->query("SELECT COUNT(*) FROM voting_periods WHERE status = 'Ongoing'");
          $hasOngoingVoting = (int)$stmt->fetchColumn() > 0;
          ?>

          <li class="nav-item">
            <?php
            $isDisabled = !$hasOngoingVoting;
            $linkClass  = $isDisabled ? 'nav-link disabled qr-disabled' : 'nav-link';
            $linkHref   = $isDisabled ? '#' : 'vote_qr_code.php';
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
                  e.preventDefault();
                  Swal.fire({
                    icon: 'info',
                    title: 'Feature Unavailable',
                    text: 'QR Code Scanning is only available during an active voting period.',
                    confirmButtonText: 'OK'
                  });
                });
              }
            });
          </script>
        </ul>
      </nav>

      <!-- Main Panel -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                  <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                      <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab"
                        href="#overview" role="tab" aria-selected="true">Voter List</a>
                    </li>
                  </ul>
                </div>

                <div class="tab-content tab-content-basic">
                  <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <br>

                    <?php
                    /*
 * ════════════════════════════════════════════════════════════════════
 *  VOTER LIST — scoped to ALL ongoing voting periods
 *
 *  Data flow (from the actual schema):
 *    voting_periods  (status = 'Ongoing')
 *        └─ election_id  →  elections.id
 *                               └─ id  ←  precincts.election
 *                                              └─ id  ←  moderators.precinct
 *
 *  Steps:
 *    1. Get every ONGOING voting period (no LIMIT).
 *    2. Collect the distinct election IDs from those periods.
 *    3. Find the moderator's assigned precinct(s) that belong to
 *       those elections (one moderator row = one precinct).
 *    4. Display a voter-list card for each qualifying precinct,
 *       grouped by election / voting period.
 * ════════════════════════════════════════════════════════════════════
 */
                    try {
                      $email = $_SESSION['email'] ?? '';

                      if (empty($email)) {
                        throw new Exception("Session expired. Please log in again.");
                      }

                      // ── Step 1: Fetch ALL ongoing voting periods (no LIMIT) ──────────
                      $stmt = $pdo->query("
        SELECT vp.id          AS voting_period_id,
               vp.election_id,
               e.election_name,
               vp.start_period,
               vp.end_period
        FROM   voting_periods vp
        JOIN   elections      e  ON e.id = vp.election_id
        WHERE  vp.status = 'Ongoing'
        ORDER  BY e.election_name
    ");
                      $ongoingPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);

                      if (empty($ongoingPeriods)) {
                        echo "<div class='alert alert-info text-center mt-4'>
                <h5>No ongoing voting periods at this time.</h5>
              </div>";
                      } else {

                        // Collect distinct election IDs that are currently active
                        $activeElectionIds = array_unique(array_column($ongoingPeriods, 'election_id'));

                        // Build a quick lookup: election_id → election_name
                        $electionNames = array_column($ongoingPeriods, 'election_name', 'election_id');

                        // ── Step 2: Find this moderator's precincts that belong to
                        //            one of the currently active elections ─────────────
                        //
                        //  moderators.precinct  is a single int FK → precincts.id
                        //  precincts.election   is a single int FK → elections.id
                        //
                        //  We use fetchAll() so that if the admin created multiple
                        //  moderator rows for the same email (one per election/precinct),
                        //  ALL of them are returned — not just the first one.
                        $electionPlaceholders = implode(',', array_fill(0, count($activeElectionIds), '?'));

                        $stmt = $pdo->prepare("
            SELECT m.precinct       AS precinct_id,
                   p.name           AS precinct_name,
                   p.election       AS election_id
            FROM   moderators m
            JOIN   precincts  p ON p.id       = m.precinct
            WHERE  m.email        = ?
              AND  m.precinct     IS NOT NULL
              AND  p.election     IN ($electionPlaceholders)
            ORDER  BY p.election, p.name
        ");
                        $stmt->execute(array_merge([$email], $activeElectionIds));
                        $moderatorPrecincts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($moderatorPrecincts)) {
                          echo "<div class='alert alert-warning text-center mt-4'>
                    <h5>You have no precincts assigned for the current ongoing voting periods.</h5>
                    <p>Please contact your administrator.</p>
                  </div>";
                        } else {

                          // Deduplicate by precinct_id (safety net)
                          $seen         = [];
                          $uniquePrecincts = [];
                          foreach ($moderatorPrecincts as $row) {
                            $pid = (int)$row['precinct_id'];
                            if (!isset($seen[$pid])) {
                              $seen[$pid]      = true;
                              $uniquePrecincts[] = $row;
                            }
                          }

                          // ── Step 3: Fetch voters for ALL matching precincts ───────
                          $precinctIds  = array_column($uniquePrecincts, 'precinct_id');
                          $pPlaceholders = implode(',', array_fill(0, count($precinctIds), '?'));

                          $stmt = $pdo->prepare("
                SELECT
                    v.student_id,
                    v.first_name,
                    v.middle_name,
                    v.last_name,
                    c.course_name,
                    ayl.year_level,
                    pv.status,
                    pv.precinct
                FROM  precinct_voters      pv
                JOIN  voters               v   ON v.student_id = pv.student_id
                LEFT  JOIN courses         c   ON c.id         = v.course
                LEFT  JOIN actual_year_levels ayl ON ayl.id    = v.year_level
                WHERE pv.precinct IN ($pPlaceholders)
                ORDER BY pv.precinct, v.last_name, v.first_name
            ");
                          $stmt->execute($precinctIds);
                          $allVoters = $stmt->fetchAll(PDO::FETCH_ASSOC);

                          // ── Step 4: Render one card per precinct ──────────────────
                          foreach ($uniquePrecincts as $precinct):
                            $pid         = (int)$precinct['precinct_id'];
                            $pName       = $precinct['precinct_name'];
                            $eid         = (int)$precinct['election_id'];
                            $electionName = $electionNames[$eid] ?? 'Unknown Election';

                            $voters = array_filter($allVoters, fn($v) => (int)$v['precinct'] === $pid);
                    ?>

                            <div class="card card-rounded mb-4">
                              <div class="card-body text-center">
                                <br>
                                <!-- Show both election name and precinct name so the
                             moderator always knows which election they're viewing -->
                                <h4 class="text-muted mb-1"><?= htmlspecialchars($electionName) ?></h4>
                                <h3><b>Precinct: <?= htmlspecialchars($pName) ?></b></h3>
                                <br>

                                <?php if (!empty($voters)): ?>
                                  <div class="table-responsive">
                                    <table class="table table-bordered dataTable"
                                      id="votersTable_<?= $pid ?>">
                                      <thead>
                                        <tr>
                                          <th>#</th>
                                          <th>Full Name</th>
                                          <th>Course</th>
                                          <th>Year Level</th>
                                          <th>Status</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        <?php $n = 1; ?>
                                        <?php foreach ($voters as $voter): ?>
                                          <tr>
                                            <td><?= $n++ ?></td>
                                            <td>
                                              <?= htmlspecialchars(trim(
                                                $voter['first_name'] . ' ' .
                                                  ($voter['middle_name'] ? $voter['middle_name'] . ' ' : '') .
                                                  $voter['last_name']
                                              )) ?>
                                            </td>
                                            <td><?= htmlspecialchars($voter['course_name'] ?? '—') ?></td>
                                            <td>
                                              <?php
                                              $yr = (int)($voter['year_level'] ?? 0);
                                              echo match ($yr) {
                                                1 => '1st Year',
                                                2 => '2nd Year',
                                                3 => '3rd Year',
                                                4 => '4th Year',
                                                5 => '5th Year',
                                                default => '—',
                                              };
                                              ?>
                                            </td>
                                            <td>
                                              <?php
                                              $status      = strtolower($voter['status']);
                                              $statusClass = match ($status) {
                                                'verified'   => 'alert-primary',
                                                'unverified' => 'alert-danger',
                                                'voted'      => 'alert-success',
                                                'active'     => 'alert-info text-dark',
                                                'pending'    => 'alert-warning',
                                                default      => 'alert-secondary',
                                              };
                                              ?>
                                              <div class="dropdown">
                                                <?php if ($status === 'voted'): ?>
                                                  <div class="alert <?= $statusClass ?> text-center fw-bold py-2 m-0"
                                                    style="font-size: 18px;">
                                                    VOTED
                                                  </div>
                                                <?php else: ?>
                                                  <?php
                                                  $status = strtolower($voter['status']);

                                                  // Normalize 'pending' to behave as 'active'
                                                  $displayStatus = ($status === 'pending') ? 'active' : $status;

                                                  $statusClass = match ($displayStatus) {
                                                    'verified'   => 'alert-primary',
                                                    'unverified' => 'alert-danger',
                                                    'voted'      => 'alert-success',
                                                    'active'     => 'alert-info text-dark',
                                                    default      => 'alert-secondary',
                                                  };
                                                  ?>
                                                  <div class="alert <?= $statusClass ?> text-center fw-bold py-2 m-0 dropdown-toggle"
                                                    data-bs-toggle="dropdown"
                                                    style="font-size: 18px; cursor: pointer;">
                                                    <?= strtoupper($displayStatus) ?>
                                                  </div>
                                                  <ul class="dropdown-menu">

                                                    <li>
                                                      <a class="dropdown-item"
                                                        href="update_status.php?action=update_status&voter_id=<?= htmlspecialchars($voter['student_id']) ?>&status=verified">
                                                        VERIFIED
                                                      </a>
                                                    </li>
                                                    <li>

                                                    </li>
                                                    <li>
                                                      <a class="dropdown-item"
                                                        href="update_status.php?action=update_status&voter_id=<?= htmlspecialchars($voter['student_id']) ?>&status=pending">
                                                        ACTIVE
                                                      </a>
                                                    </li>
                                                  </ul>
                                                <?php endif; ?>
                                              </div>
                                            </td>
                                          </tr>
                                        <?php endforeach; ?>
                                      </tbody>
                                    </table>
                                  </div>
                                <?php else: ?>
                                  <p class="text-muted">No voters found for this precinct.</p>
                                <?php endif; ?>

                              </div>
                            </div>

                    <?php
                          endforeach; // end per-precinct loop
                        }
                      }
                    } catch (Exception $e) {
                      error_log("voter-list.php error: " . $e->getMessage());
                      echo "<h3 class='text-center mb-5'>
            An error occurred while loading the voter list.<br><br>
            Please try again later.
          </h3>";
                    }
                    ?>

                  </div><!-- /.tab-pane -->
                </div><!-- /.tab-content -->
              </div><!-- /.home-tab -->
            </div>
          </div>
        </div><!-- /.content-wrapper -->
      </div><!-- /.main-panel -->
    </div><!-- /.page-body-wrapper -->
  </div><!-- /.container-scroller -->

  <!-- Vendor Scripts -->
  <script src="vendors/js/vendor.bundle.base.js"></script>
  <script src="vendors/chart.js/Chart.min.js"></script>
  <script src="vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
  <script src="vendors/progressbar.js/progressbar.min.js"></script>
  <script src="js/off-canvas.js"></script>
  <script src="js/hoverable-collapse.js"></script>
  <script src="js/template.js"></script>
  <script src="js/settings.js"></script>
  <script src="js/todolist.js"></script>
  <script src="js/dashboard.js"></script>
  <script src="js/Chart.roundedBarCharts.js"></script>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- DataTables -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    $(document).ready(function() {
      // Auto-init every voter table on the page regardless of how many render
      $('table.dataTable').each(function() {
        $(this).DataTable({
          paging: true,
          searching: true,
          ordering: true,
          info: true
        });
      });
    });
  </script>

  <?php if (isset($_SESSION['alert'])): ?>
    <script>
      Swal.fire({
        icon: '<?= $_SESSION['alert']['type'] ?>',
        title: <?= $_SESSION['alert']['type'] === 'success' ? "'Success!'" : "'Oops!'" ?>,
        text: <?= json_encode($_SESSION['alert']['message']) ?>,
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'OK'
      });
    </script>
    <?php unset($_SESSION['alert']); ?>
  <?php endif; ?>

</body>

</html>