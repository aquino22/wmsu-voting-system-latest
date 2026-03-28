<?php
ini_set('max_execution_time', 3600);
session_start();
include('includes/conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  session_destroy();
  session_start();
  $_SESSION['STATUS'] = "NON_ADMIN";
  header("Location: ../index.php");
  exit();
}

$moderator_id = intval($_GET['id'] ?? 0);
if (!$moderator_id) {
  $_SESSION['STATUS'] = "MODERATOR_NOT_FOUND";
  header("Location: moderators.php");
  exit();
}

// ── 1. Moderator details ────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT
        m.id,
        m.name,
        m.email,
        m.gender,
        m.status,
        m.precinct    AS precinct_id,
        c.college_name,
        d.department_name,
        j.major_name,
        p.name        AS precinct_name,
        p.location    AS precinct_location,
        p.current_capacity,
        p.max_capacity,
        p.assignment_status,
        p.college_external,
        cm.campus_name,
        cm.campus_id,
        ec.campus_name AS external_campus_name
    FROM moderators m
    LEFT JOIN colleges    c  ON m.college    = c.college_id
    LEFT JOIN departments d  ON m.department = d.department_id
    LEFT JOIN majors      j  ON m.major      = j.major_id
    LEFT JOIN precincts   p  ON m.precinct   = p.id
    LEFT JOIN campuses    cm ON p.type       = cm.campus_id
    LEFT JOIN campuses    ec ON p.college_external = ec.campus_id
    WHERE m.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $moderator_id]);
$moderator = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$moderator) {
  $_SESSION['STATUS'] = "MODERATOR_NOT_FOUND";
  header("Location: moderators.php");
  exit();
}

// ── 2. Voters in this moderator's precinct ──────────────────────────────────
// precinct_voters.precinct stores the precinct id as a string
// Join to voters via student_id; resolve course, year_level, college, dept
$voters = [];
if ($moderator['precinct_id']) {
  $voterStmt = $pdo->prepare("
        SELECT
            pv.student_id,
            pv.status                       AS precinct_status,
            v.first_name,
            v.middle_name,
            v.last_name,
            v.email,
            v.status                        AS voter_status,
            cr.course_name,
            ayl.year_level                  AS year_level_num,
            yl.description                  AS year_level_label,
            col.college_name,
            dep.department_name
        FROM precinct_voters pv
        LEFT JOIN voters      v   ON v.student_id       = pv.student_id
        LEFT JOIN courses     cr  ON cr.id              = v.course
        LEFT JOIN actual_year_levels ayl ON ayl.id      = v.year_level
        LEFT JOIN year_levels yl  ON yl.level           = ayl.year_level
        LEFT JOIN colleges    col ON col.college_id     = v.college
        LEFT JOIN departments dep ON dep.department_id  = v.department
        WHERE pv.precinct = :precinct_id
        ORDER BY v.last_name ASC, v.first_name ASC
    ");
  $voterStmt->execute([':precinct_id' => (string)$moderator['precinct_id']]);
  $voters = $voterStmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── 3. Precinct vote stats ───────────────────────────────────────────────────
$totalInPrecinct  = count($voters);
$votedInPrecinct  = count(array_filter($voters, fn($v) => $v['precinct_status'] === 'voted'));
$pendingInPrecinct = $totalInPrecinct - $votedInPrecinct;
$turnoutPct = $totalInPrecinct > 0 ? round(($votedInPrecinct / $totalInPrecinct) * 100, 1) : 0;

// ── 4. Navbar admin info ─────────────────────────────────────────────────────
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT a.full_name, u.email FROM admin a JOIN users u ON a.user_id = u.id WHERE u.id = :uid");
$stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>WMSU i-Elect Admin | View Moderator</title>
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="vendors/typicons/typicons.css">
  <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="css/vertical-layout-light/style.css">
  <link rel="shortcut icon" href="images/favicon.png" />
</head>

<body>
  <div class="container-scroller">

    <!-- ── Navbar ── -->
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
            <small style="font-size:16px;"><b>WMSU i-Elect</b></small>
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
              $eStmt = $pdo->prepare("
                            SELECT e.election_name, a.year_label, a.semester
                            FROM elections e
                            JOIN academic_years a ON e.academic_year_id = a.id
                            WHERE e.status = 'Ongoing'
                            ORDER BY a.year_label DESC, a.semester DESC
                        ");
              $eStmt->execute();
              $ongoingElections = $eStmt->fetchAll(PDO::FETCH_ASSOC);
              if ($ongoingElections) {
                $first = array_shift($ongoingElections);
                echo "<br><b>School Year:</b> " . htmlspecialchars($first['year_label']) . " | ";
                echo "<b>Semester:</b> " . htmlspecialchars($first['semester']) . " | ";
                echo "<b>Election:</b> " . htmlspecialchars($first['election_name']) . "<br>";
                if ($ongoingElections) {
                  echo '<div id="moreElections" style="display:none;margin-top:5px;">';
                  foreach ($ongoingElections as $el) {
                    echo "<b>School Year:</b> " . htmlspecialchars($el['year_label']) . " | ";
                    echo "<b>Semester:</b> " . htmlspecialchars($el['semester']) . " | ";
                    echo "<b>Election:</b> " . htmlspecialchars($el['election_name']) . "<br>";
                  }
                  echo '</div><br>';
                  echo '<a href="javascript:void(0)" id="toggleElections" class="text-decoration-underline text-white">Show More</a>';
                }
              }
              ?>
            </h6>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item dropdown d-none d-lg-block user-dropdown">
            <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
              <img class="img-xs rounded-circle logo" src="images/wmsu-logo.png" style="background-color:white;" alt="Profile image">
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
              <div class="dropdown-header text-center">
                <img class="img-xs rounded-circle" src="images/wmsu-logo.png" alt="Profile image">
              </div>
              <p class="mb-1 mt-3 font-weight-semibold dropdown-item"><b>WMSU ADMIN</b></p>
              <a class="dropdown-item" href="processes/accounts/logout.php">
                <i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign Out
              </a>
            </div>
          </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
          <span class="mdi mdi-menu"></span>
        </button>
      </div>
    </nav>

    <div class="container-fluid page-body-wrapper">
      <?php include('includes/sidebar.php') ?>

      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">

                <!-- Page header -->
                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                  <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                      <a class="nav-link active ps-0" data-bs-toggle="tab" href="#overview"
                        role="tab" aria-selected="true">View Moderator</a>
                    </li>
                  </ul>
                  <div>
                    <a href="moderators.php" class="btn btn-light bg-white btn-sm">
                      <i class="bi bi-arrow-left me-1"></i>Back to Moderators
                    </a>
                  </div>
                </div>

                <div class="tab-content tab-content-basic">
                  <div class="tab-pane fade show active" id="overview" role="tabpanel">

                    <!-- ── Moderator Info Card ── -->
                    <div class="card mt-3">
                      <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                          <h4 class="card-title mb-0">Moderator Information</h4>
                          <span class="ms-3 badge <?= $moderator['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= ucfirst(htmlspecialchars($moderator['status'])) ?>
                          </span>
                        </div>

                        <div class="row">
                          <div class="col-md-6">
                            <p><strong>Name:</strong> <?= htmlspecialchars($moderator['name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($moderator['email']) ?></p>
                            <p><strong>Gender:</strong> <?= htmlspecialchars($moderator['gender']) ?></p>
                            <p><strong>College:</strong> <?= htmlspecialchars($moderator['college_name'] ?? 'N/A') ?></p>
                            <p><strong>Department:</strong> <?= htmlspecialchars($moderator['department_name'] ?? 'N/A') ?></p>
                            <p><strong>Major:</strong> <?= htmlspecialchars($moderator['major_name'] ?? 'N/A') ?></p>
                          </div>
                          <div class="col-md-6">
                            <?php if ($moderator['precinct_id']): ?>
                              <p><strong>Precinct:</strong> <?= htmlspecialchars($moderator['precinct_name']) ?></p>
                              <p><strong>Location:</strong> <?= htmlspecialchars($moderator['precinct_location'] ?? 'N/A') ?></p>
                              <p><strong>Campus:</strong>
                                <?= htmlspecialchars($moderator['campus_name'] ?? 'N/A') ?>
                                <?php if (!empty($moderator['external_campus_name'])): ?>
                                  &mdash; <?= htmlspecialchars($moderator['external_campus_name']) ?>
                                <?php endif; ?>
                              </p>
                              <p><strong>Precinct Assignment:</strong>
                                <span class="badge <?= $moderator['assignment_status'] === 'assigned' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                  <?= ucfirst(htmlspecialchars($moderator['assignment_status'] ?? 'N/A')) ?>
                                </span>
                              </p>
                              <p><strong>Capacity:</strong>
                                <?= number_format($moderator['current_capacity']) ?> / <?= number_format($moderator['max_capacity']) ?>
                                voters assigned
                              </p>
                            <?php else: ?>
                              <p><strong>Precinct:</strong> <span class="text-muted">No precinct assigned</span></p>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- ── Precinct Turnout Summary ── -->
                    <?php if ($moderator['precinct_id'] && $totalInPrecinct > 0): ?>
                      <div class="row mt-3 g-3">
                        <div class="col-md-4">
                          <div class="card border-primary h-100">
                            <div class="card-body text-center">
                              <h6 class="text-muted mb-1">Total Assigned</h6>
                              <h2 class="fw-bold text-primary mb-0"><?= number_format($totalInPrecinct) ?></h2>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="card border-success h-100">
                            <div class="card-body text-center">
                              <h6 class="text-muted mb-1">Voted</h6>
                              <h2 class="fw-bold text-success mb-0"><?= number_format($votedInPrecinct) ?></h2>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="card border-warning h-100">
                            <div class="card-body text-center">
                              <h6 class="text-muted mb-1">Turnout</h6>
                              <h2 class="fw-bold text-warning mb-0"><?= $turnoutPct ?>%</h2>
                              <small class="text-muted"><?= number_format($pendingInPrecinct) ?> yet to vote</small>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php endif; ?>

                    <!-- ── Voters Table ── -->
                    <div class="card mt-3">
                      <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                          <h4 class="card-title mb-0">
                            Voters in Precinct
                            <?php if ($moderator['precinct_name']): ?>
                              <small class="text-muted fw-normal fs-6 ms-1">
                                — <?= htmlspecialchars($moderator['precinct_name']) ?>
                              </small>
                            <?php endif; ?>
                          </h4>
                          <span class="badge bg-primary ms-2"><?= $totalInPrecinct ?></span>
                        </div>

                        <?php if (empty($voters)): ?>
                          <div class="alert alert-info mb-0">
                            <?= $moderator['precinct_id']
                              ? 'No voters have been assigned to this precinct yet.'
                              : 'This moderator has no precinct assigned.' ?>
                          </div>
                        <?php else: ?>
                          <div class="table-responsive">
                            <table id="votersTable"
                              class="table table-striped table-bordered nowrap"
                              style="width:100%">
                              <thead>
                                <tr>
                                  <th>Student ID</th>
                                  <th>Full Name</th>
                                  <th>Email</th>
                                  <th>College</th>
                                  <th>Department</th>
                                  <th>Course</th>
                                  <th>Year Level</th>
                                  <th>Voter Status</th>
                                  <th>Precinct Status</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($voters as $v):
                                  $fullName = trim(
                                    ($v['first_name'] ?? '') . ' ' .
                                      (!empty($v['middle_name']) ? $v['middle_name'] . ' ' : '') .
                                      ($v['last_name'] ?? '')
                                  );

                                  // Voter status badge
                                  $vs = strtolower($v['voter_status'] ?? '');
                                  $vBadge = match ($vs) {
                                    'confirmed', 'active' => 'bg-success',
                                    'pending'             => 'bg-warning text-dark',
                                    'rejected'            => 'bg-danger',
                                    'archived'            => 'bg-secondary',
                                    default               => 'bg-secondary',
                                  };

                                  // Precinct status badge
                                  $ps = strtolower($v['precinct_status'] ?? '');
                                  $pBadge = match ($ps) {
                                    'voted'      => 'bg-success',
                                    'pending'    => 'bg-warning text-dark',
                                    'verified'   => 'bg-info text-dark',
                                    'unverified' => 'bg-secondary',
                                    default      => 'bg-secondary',
                                  };
                                ?>
                                  <tr>
                                    <td><?= htmlspecialchars($v['student_id']) ?></td>
                                    <td><?= htmlspecialchars($fullName ?: '—') ?></td>
                                    <td><?= htmlspecialchars($v['email'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($v['college_name'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($v['department_name'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($v['course_name'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($v['year_level_label'] ?? ($v['year_level_num'] ? 'Year ' . $v['year_level_num'] : '—')) ?></td>
                                    <td>
                                      <span class="badge <?= $vBadge ?>">
                                        <?= htmlspecialchars(ucfirst($v['voter_status'] ?? 'Unknown')) ?>
                                      </span>
                                    </td>
                                    <td>
                                      <span class="badge <?= $pBadge ?>">
                                        <?= htmlspecialchars(ucfirst($v['precinct_status'] ?? 'Unknown')) ?>
                                      </span>
                                    </td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>

                  </div><!-- tab-pane -->
                </div><!-- tab-content -->
              </div><!-- home-tab -->
            </div><!-- col -->
          </div><!-- row -->
        </div><!-- content-wrapper -->
      </div><!-- main-panel -->
    </div><!-- page-body-wrapper -->
  </div><!-- container-scroller -->

  <!-- ── Scripts ── -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    $(document).ready(function() {

      $('#votersTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [
          [1, 'asc']
        ],
        columnDefs: [{
          orderable: false,
          targets: [7, 8]
        }]
      });

      // Navbar show-more toggle
      const toggleBtn = document.getElementById('toggleElections');
      const moreDiv = document.getElementById('moreElections');
      if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
          const visible = moreDiv.style.display !== 'none';
          moreDiv.style.display = visible ? 'none' : 'block';
          toggleBtn.textContent = visible ? 'Show More' : 'Show Less';
        });
      }

      // Back to Top
      const backToTopButton = document.getElementById('backToTop');
      if (backToTopButton) {
        window.addEventListener('scroll', function() {
          backToTopButton.classList.toggle('show', window.pageYOffset > 200);
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

  <button class="back-to-top" id="backToTop" title="Go to top">
    <i class="mdi mdi-arrow-up"></i>
  </button>

</body>

</html>