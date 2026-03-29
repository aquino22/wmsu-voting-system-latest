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
  $college = isset($adviser['college_id']) ? $adviser['college_id'] : null;
  $department = isset($adviser['department_id']) ? $adviser['department_id'] : null;
  $wmsu_campus = isset($adviser['wmsu_campus_id']) ? $adviser['wmsu_campus_id'] : null;
  $external_campus = isset($adviser['external_campus_id']) ? $adviser['external_campus_id'] : null;
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
            <a class="nav-link active-link" href="index.php" style="background-color: #B22222 !important;">
              <i class="mdi mdi-grid-large menu-icon" style="color: white !important;"></i>
              <span class="menu-title" style="color: white !important;">Dashboard</span>
            </a>
          </li>

          <?php
          if ($has_changed == 1) {
          ?>
            <li class="nav-item">
              <a class="nav-link" href="voter-list-previous.php">
                <i class="menu-icon mdi mdi-account-multiple"></i>
                <span class="menu-title">Advisory</span>
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
                    <div class="card-body">
                      <h3><b>Dashboard</b></h3>
                      <br>
                      <h5 class="text-center">Elections Available</h5>

                      <div class="row g-4">

                        <?php

                        $statusLabel = '';

                        // FETCH ALL ELECTIONS
                        $stmt = $pdo->prepare("SELECT * FROM elections ORDER BY start_period DESC");
                        $stmt->execute();
                        $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);


                        // Adviser voter stats
                        $adviserEmail = $_SESSION['email'] ?? null;

                        $totalVoters = 0;
                        $pendingVerification = 0;
                        $studentsVoted = 0;

                        if ($adviserEmail) {

                          $stmt = $pdo->prepare("
        SELECT 
            college_id,
            department_id,
            school_year,
            wmsu_campus_id,
            external_campus_id
        FROM advisers
        WHERE email = ?
        LIMIT 1
    ");

                          $stmt->execute([$adviserEmail]);
                          $adviser = $stmt->fetch(PDO::FETCH_ASSOC);

                          if ($adviser) {

                            $college = $adviser['college_id'];
                            $department = $adviser['department_id'];
                            $year = $adviser['school_year'];
                            $wmsu_campus = $adviser['wmsu_campus_id'];
                            $external_campus = $adviser['external_campus_id'];

                            $conditions = "
            v.college = ?
            AND v.department = ?
            AND v.year_level = ?
            AND v.wmsu_campus = ?
        ";

                            $params = [
                              $college,
                              $department,
                              $year,
                              $wmsu_campus
                            ];



                            if (!empty($external_campus)) {
                              $conditions .= " AND v.external_campus = ?";
                              $params[] = $external_campus;
                            }

                            // TOTAL VOTERS
                            $stmtTotal = $pdo->prepare("
            SELECT COUNT(DISTINCT v.student_id)
            FROM voters v
            JOIN precinct_voters pv 
                ON v.student_id = pv.student_id
            WHERE $conditions
        ");

                            $stmtTotal->execute($params);
                            $totalVoters = $stmtTotal->fetchColumn();


                            // STUDENTS WHO VOTED
                            $stmtVoted = $pdo->prepare("
            SELECT COUNT(DISTINCT v.student_id)
            FROM voters v
            JOIN precinct_voters pv 
                ON v.student_id = pv.student_id
            WHERE $conditions
            AND pv.status = 'voted'
        ");

                            $stmtVoted->execute($params);
                            $studentsVoted = $stmtVoted->fetchColumn();


                            // NOT YET VOTED
                            $pendingVerification = $totalVoters - $studentsVoted;
                          }
                        }

                        ?>

                        <?php

                        foreach ($elections as $election): ?>
                          <?php
                          // Election status
                          $now = date('Y-m-d');
                          $start = $election['start_period'];
                          $end = $election['end_period'];
                          $dbStatus = strtolower($election['status']);


                          if ($dbStatus === "scheduled" || $dbStatus === "pending") {
                            $statusLabel = "Upcoming";
                            $badgeClass = "info";
                          } elseif ($dbStatus === "ongoing" || $dbStatus === "active" || $dbStatus === "started") {
                            $statusLabel = "Ongoing";
                            $badgeClass = "success";
                          } elseif ($now > $end) {
                            $statusLabel = "Ended";
                            $badgeClass = "danger";
                          } elseif ($dbStatus == 'ended' || $dbStatus == 'archived') {
                            $statusLabel = "Ended";
                            $badgeClass = "danger";
                          } elseif ($dbStatus === "cancelled") {
                            $statusLabel = "Cancelled";
                            $badgeClass = "warning";
                          } elseif ($dbStatus === "published") {
                            $statusLabel = "Published";
                            $badgeClass = "primary";
                          } else {
                            $statusLabel = "Unknown";
                            $badgeClass = "secondary";
                          }

                          // Fetch voting periods for this election
                          // Fetch voting periods for a given election along with election name and academic year info
                          $stmtVP = $pdo->prepare("
   SELECT
    e.id AS election_id,
    e.election_name AS name,
    vp.*,
    ay.semester AS semester,
    ay.start_date AS school_year_start,
    ay.end_date AS school_year_end
FROM elections e
LEFT JOIN voting_periods vp ON vp.election_id = e.id
INNER JOIN academic_years ay 
    ON ay.id = e.academic_year_id
WHERE e.id = ?
ORDER BY vp.start_period ASC;

");
                          $stmtVP->execute([$election['id']]);
                          // Fetch all voting periods
                          $votingPeriods = $stmtVP->fetchAll(PDO::FETCH_ASSOC);

                          // Always get academic year info from the first row
                          $semester        = $votingPeriods[0]['semester'] ?? '';
                          $schoolYearStart = $votingPeriods[0]['school_year_start'] ?? '';
                          $schoolYearEnd   = $votingPeriods[0]['school_year_end'] ?? '';

                          // Then optionally assign voting period info
                          if (!empty($votingPeriods) && !empty($votingPeriods[0]['id'])) {
                            $firstVP = $votingPeriods[0];
                            $votingPeriodId     = $firstVP['id'];
                            $votingPeriodStart  = $firstVP['start_period'];
                            $votingPeriodEnd    = $firstVP['end_period'];
                            $votingPeriodStatus = $firstVP['status'];

                            $electionName       = $firstVP['name'];
                          }
                          ?>

                          <!-- ELECTION ROW -->
                          <div class="col-12">
                            <div class="card border-dark shadow-sm mb-4">
                              <div class="card-body">
                                <div class="row g-4">

                                  <!-- Election Card -->
                                  <div class="col-md">
                                    <div class="card border-success shadow-sm">
                                      <div class="card-body">
                                        <h5 class="card-title">🗳️ <?= htmlspecialchars($election['election_name']) ?></h5>
                                        <p><strong>Semester & AY:</strong> <?= $semester ?> <?= $schoolYearStart  ?> - <?= $schoolYearEnd ?></p>
                                        <p><strong>Schedule:</strong> <?= date("F j, Y", strtotime($start)) ?> – <?= date("F j, Y", strtotime($end)) ?></p>
                                        <p><strong>Status:</strong> <span class="badge bg-<?= $badgeClass ?>"><?= $statusLabel ?></span></p>
                                      </div>
                                    </div>
                                  </div>

                                  <!-- Voting Periods -->
                                  <div class="col-md">
                                    <?php if (!empty($votingPeriods) && !empty($votingPeriods[0]['id'])): ?>
                                      <?php foreach ($votingPeriods as $vp): ?>
                                        <?php
                                        $vpStatus = strtolower($vp['status']);
                                        if (in_array($vpStatus, ["ongoing", "active", "started"])) {
                                          $vpLabel = "Ongoing";
                                          $vpBadge = "success";
                                        } elseif (in_array($vpStatus, ["upcoming"])) {
                                          $vpLabel = "Upcoming";
                                          $vpBadge = "warning";
                                        } elseif (in_array($vpStatus, ["scheduled"])) {
                                          $vpLabel = "Scheduled";
                                          $vpBadge = "warning";
                                        } elseif ($vpStatus === "published") {
                                          $vpLabel = "Published";
                                          $vpBadge = "primary";
                                        } else {
                                          $vpLabel = ucfirst($vpStatus);
                                          $vpBadge = "secondary";
                                        }
                                        ?>
                                        <div class="card border-primary shadow-sm mb-3">
                                          <div class="card-body">
                                            <p><strong>Voting Period:</strong> <?= htmlspecialchars($vp['name']) ?></p>
                                            <p><strong>Schedule:</strong> <?= date("F j, Y", strtotime($vp['start_period'])) ?> – <?= date("F j, Y", strtotime($vp['end_period'])) ?></p>
                                            <p><strong>Status:</strong> <span class="badge bg-<?= $vpBadge ?>"><?= $vpLabel ?></span></p>
                                          </div>
                                        </div>
                                      <?php endforeach; ?>
                                    <?php else: ?>
                                      <div class="card">
                                        <div class="card-body text-center">
                                          <h6 class="text-muted">No voting periods assigned yet.</h6>
                                        </div>
                                      </div>

                                    <?php endif; ?>
                                  </div>



                                </div> <!-- /row inside election card -->

                              </div>
                            </div>
                          </div>

                        <?php endforeach; ?>
                        <!-- Voter Statistics -->
                        <div class="col-md">
                          <div class="alert alert-success" role="alert">
                            This will populate once voting starts.
                          </div>

                          <div class="card border-primary shadow-sm">
                            <div class="card-body">
                              <h5 class="card-title">👥 Voter Statistics </h5>
                              <ul class="list-group list-group-flush">
                                <li class="list-group-item">Total Voters: <strong><?= $totalVoters ?></strong></li>
                                <li class="list-group-item">Pending Voting: <strong><?= $pendingVerification ?></strong></li>
                                <li class="list-group-item">Voted: <strong><?= $studentsVoted ?></strong></li>
                              </ul>
                              <a href="voter-list.php" class="btn btn-outline-primary btn-sm mt-3">Manage Voter List</a>
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
    </div>
  </div>
</body>


<!-- Modal for Preview -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="previewModalLabel">📋 Voter List Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalPreviewContent">
        <!-- Table will be injected here -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>

<script>
  let parsedData = [];

  const fileInput = document.querySelector('input[type="file"]');
  const previewBtn = document.getElementById('previewBtn');
  const importForm = document.getElementById('importForm');

  fileInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const fileName = file.name.toLowerCase();
    const fileExt = fileName.split('.').pop();
    const reader = new FileReader();

    if (fileExt === 'csv') {
      reader.onload = function(e) {
        const csv = e.target.result;
        const result = Papa.parse(csv, {
          header: true,
          skipEmptyLines: true
        });
        parsedData = result.data;
        previewBtn.disabled = false;
      };
      reader.readAsText(file);
    } else if (fileExt === 'xlsx') {
      reader.onload = function(e) {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {
          type: 'array'
        });
        const sheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[sheetName];
        parsedData = XLSX.utils.sheet_to_json(worksheet, {
          defval: ''
        });
        previewBtn.disabled = false;
      };
      reader.readAsArrayBuffer(file);
    } else {
      alert("Only .csv or .xlsx files are supported.");
      previewBtn.disabled = true;
    }
  });

  previewBtn.addEventListener('click', function() {
    if (parsedData.length === 0) {
      alert("No data to preview.");
      return;
    }

    let html = '<table class="table table-bordered table-sm"><thead><tr>';
    Object.keys(parsedData[0]).forEach(key => {
      html += `<th>${key}</th>`;
    });
    html += '</tr></thead><tbody>';

    parsedData.forEach(row => {
      html += '<tr>';
      Object.values(row).forEach(cell => {
        html += `<td>${cell}</td>`;
      });
      html += '</tr>';
    });

    html += '</tbody></table>';
    document.getElementById('modalPreviewContent').innerHTML = html;

    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
  });

  importForm.addEventListener('submit', function(e) {
    e.preventDefault();
    if (parsedData.length === 0) {
      alert("No data to import.");
      return;
    }

    fetch('processes/voters/import_voters.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(parsedData)
      })
      .then(res => res.text())
      .then(response => {
        alert(response);
      })
      .catch(err => {
        console.error(err);
        alert('Import failed.');
      });
  });
</script>

</html>

<?php if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] === "LOGIN_SUCCESSFUL"): ?>
  <script>
    Swal.fire({
      title: 'Welcome!',
      text: 'Login Successful.',
      icon: 'success',
      confirmButtonColor: '#3085d6',
      confirmButtonText: 'OK'
    });
  </script>
<?php
  unset($_SESSION['STATUS']);
endif; ?>

<script>
  $(document).ready(function() {
    $('#importsTable').DataTable({
      "paging": true,
      "searching": true,
      "ordering": true,
      "info": true,
      "lengthChange": true,
      "pageLength": 10
    });
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
<!-- DataTables CSS -->
<link rel="stylesheet"
  href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<!-- DataTables JS -->
<script
  src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script
  src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>

</html>