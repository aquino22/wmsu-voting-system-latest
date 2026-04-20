<?php
session_start();
date_default_timezone_set('Asia/Manila');
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

  $stmt = $pdo->prepare("SELECT * FROM email WHERE adviser_id = ?");
  $stmt->execute([$adviserId]);
  $smtp = $stmt->fetch(PDO::FETCH_ASSOC);

  $smtp_capacity = $smtp['capacity'];
}
$stmt = $pdo->prepare("SELECT election_name FROM elections WHERE status = 'Ongoing' LIMIT 1");

// Execute the query
$stmt->execute();

// Fetch result
$ongoingElection = $stmt->fetch(PDO::FETCH_ASSOC);

if ($ongoingElection) {
  $electionName = $ongoingElection['election_name'];
} else {
  $electionName = null; // no ongoing election
}




?>


<?php

$stmt = $pdo->prepare("
    SELECT 
        id,
        year_label,
        semester,
        start_date,
        end_date,
        status,
        custom_voter_option
    FROM academic_years
    WHERE status = 'ongoing'
    LIMIT 1
");

$stmt->execute();
$academic_year = $stmt->fetch(PDO::FETCH_ASSOC);

$custom_voter_option = isset($academic_year['custom_voter_option']) ? $academic_year['custom_voter_option'] : 0;

$adviserEmail = $_SESSION['email'] ?? null;

$totalVoters = 0;
$importedByAdviser = 0;
$pendingVerification = 0;

$has_changed = 0;


if ($adviserEmail) {
  // Get adviser info
  $stmt = $pdo->prepare("SELECT * FROM advisers WHERE email = ?");
  $stmt->execute([$adviserEmail]);
  $adviser = $stmt->fetch(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("SELECT has_changed FROM advisers WHERE email = ? AND has_changed = 1");
  $stmt->execute([$adviserEmail]);
  $adviser_has_changed = $stmt->fetch();

  if ($adviserEmail) {
    // Fetch adviser info along with names (optional)
    $stmt = $pdo->prepare("
        SELECT a.*, 
               c.college_name, 
               d.department_name, 
               wc.campus_name AS wmsu_campus_name,
               ec.campus_name AS external_campus_name
        FROM advisers a
        LEFT JOIN colleges c ON a.college_id = c.college_id
        LEFT JOIN departments d ON a.department_id = d.department_id
        LEFT JOIN campuses wc ON a.wmsu_campus_id = wc.campus_id
        LEFT JOIN campuses ec ON a.external_campus_id = ec.campus_id
        WHERE a.email = ?
    ");
    $stmt->execute([$adviserEmail]);
    $adviser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($adviser) {

      $has_changed = $adviser['has_changed'] ?? 0;
      $adviser_id = $adviser['id'];
      $college = $adviser['college_id'];
      $department = $adviser['department_id'];
      $year = $adviser['year_level'];
      $wmsu_campus = $adviser['wmsu_campus_id'];
      $external_campus = $adviser['external_campus_id'];
      $major = $adviser['major_id'] ?? null;
      $full_name = $adviser['full_name'] ?? null;
      $yearLevel = $adviser['year_level'];



      $stmtYear = $pdo->prepare("
    SELECT year_level
    FROM actual_year_levels
    WHERE id = ?
");

      $stmtYear->execute([$year]);
      $yearLevel = $stmtYear->fetchColumn();


      function formatYearLevel($year)
      {
        $suffix = 'th';
        if ($year % 10 == 1 && $year % 100 != 11) $suffix = 'st';
        elseif ($year % 10 == 2 && $year % 100 != 12) $suffix = 'nd';
        elseif ($year % 10 == 3 && $year % 100 != 13) $suffix = 'rd';

        return $year . $suffix . " Year";
      }

      $yearLevelName = formatYearLevel($yearLevel);

      // 1. College name
      $stmt = $pdo->prepare("SELECT college_name FROM colleges WHERE college_id = ?");
      $stmt->execute([$college]);
      $college_name = $stmt->fetchColumn();

      // 2. Department name
      $stmt = $pdo->prepare("SELECT department_name FROM departments WHERE department_id = ?");
      $stmt->execute([$department]);
      $department_name = $stmt->fetchColumn();

      $stmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_code = ?");
      $stmt->execute([$department_name]);
      $course_name = $stmt->fetchColumn();

      // 3. WMSU Campus name
      $stmt = $pdo->prepare("SELECT campus_name FROM campuses WHERE campus_id = ?");
      $stmt->execute([$wmsu_campus]);
      $wmsu_campus_name = $stmt->fetchColumn();

      // 4. External Campus name
      $stmt = $pdo->prepare("SELECT campus_name FROM campuses WHERE campus_id = ?");
      $stmt->execute([$external_campus]);
      $external_campus_name = $stmt->fetchColumn();

      // 5. Major name
      if ($major) {
        $stmt = $pdo->prepare("SELECT major_name FROM majors WHERE major_id = ?");
        $stmt->execute([$major]);
        $major_name = $stmt->fetchColumn();
      } else {
        $major_name = null;
      }

      // Build query dynamically
      $params = [
        $college,
        $department,
        $wmsu_campus,
        $year
      ];

      $query = "SELECT v.*, ay.year_label AS school_year, ay.semester, COALESCE(erl.count,0) AS total_emails_sent
              FROM voters v
              LEFT JOIN academic_years ay ON v.academic_year_id = ay.id
              LEFT JOIN email_role_log erl ON v.student_id = erl.student_id
              WHERE v.college = ?
                AND v.department = ?
                AND v.wmsu_campus = ?
                AND v.year_level = ?
                AND v.status = 'confirmed'";

      // Include major if set
      if (!empty($major)) {
        $query .= " AND v.major = ?";
        $params[] = $major;
      }

      // Handle external campus
      if (empty($external_campus) || $external_campus === 'None') {
        $query .= " AND (v.external_campus IS NULL OR v.external_campus = 'None')";
      } else {
        $query .= " AND v.external_campus = ?";
        $params[] = $external_campus;
      }

      $stmt = $pdo->prepare($query);
      $stmt->execute($params);
      $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
      $voters = [];
    }
  }
}

try {
  $stmt = $pdo->query("
SELECT 
    vp.*,
    e.election_name as name
FROM voting_periods vp
JOIN elections e 
    ON vp.election_id = e.id
WHERE vp.status IN ('Ongoing', 'Scheduled')
ORDER BY vp.start_period ASC;

");

  $votingPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);



  $canSendQr = [];
  $now = time();

  foreach ($votingPeriods as $vp) {

    $startTime = strtotime($vp['start_period']);
    $twoHoursBefore = $startTime - (2 * 60 * 60);


    // TRUE if 2 hours before start → until start
    $canSendQr[$vp['id']] = ($now >= $twoHoursBefore && $now <= $startTime);
  }
} catch (PDOException $e) {
  error_log("Database error: " . $e->getMessage());
  $votingPeriods = [];
  $canSendQr = [];
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
  /* Style for disabled button */
  .button-send-qr.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
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
            <a class="nav-link" href="voter-list.php" style="background-color: #B22222 !important;">
              <i class="menu-icon mdi mdi-account-group" style="color: white !important;"></i>
              <span class="menu-title" style="color: white !important;">Verified Students</span>
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
                              <h3 class="mb-0"><b>Verified Voters </b></h3>


                              <div class="d-flex justify-content-between align-items-center ">

                                <div id="qrCountdownMessage" class="fw-bold mb-2 text-warning" style="margin-right: 20px"></div>

                                <div class="btn-group">
                                  <button

                                    class="btn btn-primary text-light dropdown-toggle <?= !$canSendQr ? 'disabled' : '' ?>"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    <?= !$canSendQr ? 'disabled' : '' ?>>
                                    <i class="mdi mdi-qrcode-scan"></i> Send QR Codes
                                  </button>

                                  <ul class="dropdown-menu">
                                    <?php foreach ($votingPeriods as $election): ?>
                                      <li>
                                        <a
                                          class="dropdown-item send-qr-option"
                                          href="#"
                                          data-election-id="<?= $election['id'] ?>"
                                          data-election-name="<?= $election['name'] ?>">
                                          Election: <?= htmlspecialchars($election['name']) ?>
                                        </a>
                                      </li>
                                    <?php endforeach; ?>
                                  </ul>

                                </div>


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

                            <br>

                            <div style="padding: 10px; border-radius: 8px; background-color: #f8f9fa; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
                              <h5 style="margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                                <b>📊 Current Email Statistics</b>
                                <a href="sent_qr_list.php"
                                  style="font-size: 14px; text-decoration: underline; color: #B22222;">
                                  View Sent Emails/QRs
                                </a>
                              </h5>

                              <h6 style="margin-bottom: 8px;">📧 Emails Sent: <span id="sentCount">
                                  <?php echo isset($smtp_capacity) ? $smtp_capacity : 0; ?>
                                </span> / <?php echo isset($total_limit) ? $total_limit : 500; ?></h6>
                              <div style="background-color: #e9ecef; border-radius: 5px; height: 10px; overflow: hidden;">
                                <div id="emailProgressBar" style="width: <?php echo isset($smtp_capacity) && isset($total_limit) && $total_limit > 0 ? ($smtp_capacity / $total_limit * 100) : 0; ?>%; background-color: #28a745; height: 100%;"></div>
                              </div>
                            </div>




                            <br><br>



                            <div class="table-responsive">
                              <table id="votersTable" class="table table-striped table-bordered nowrap" style="width:100%">
                                <thead>
                                  <tr>
                                    <th>School Year</th>
                                    <th>Semester</th>
                                    <th>Student ID</th>
                                    <th>Email</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th>Last Name</th>

                                    <th>College</th>
                                    <th>Course</th>
                                    <th>Department</th>
                                    <th>Year Level</th>
                                    <th>Campus</th>
                                    <th>Elections Participated</th>
                                    <th>Precinct</th>
                                    <th>Vote Status</th>
                                    <th>Account Status</th>
                                    <th>Email</th>
                                    <th>Total Emails Sent</th>
                                    <th>Manage</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php foreach ($voters as $voter): ?>
                                    <tr>
                                      <td><?= htmlspecialchars($voter['school_year']) ?></td>
                                      <td><?= htmlspecialchars($voter['semester']) ?></td>
                                      <td><?= htmlspecialchars($voter['student_id']) ?></td>
                                      <td><?= htmlspecialchars($voter['email']) ?></td>
                                      <td><?= htmlspecialchars($voter['first_name']) ?></td>
                                      <td><?= htmlspecialchars($voter['middle_name']) ?></td>
                                      <td><?= htmlspecialchars($voter['last_name']) ?></td>

                                      <td><?= htmlspecialchars($college_name) ?></td>
                                      <td><?= htmlspecialchars($course_name) ?></td>
                                      <td><?= htmlspecialchars($department_name) ?></td>
                                      <td><?= htmlspecialchars($yearLevelName) ?></td>
                                      <td>
                                        <?php
                                        if (!empty($voter['wmsu_campus'])) {

                                          if ($wmsu_campus_name === 'WMSU ESU') {
                                            echo htmlspecialchars($wmsu_campus_name . ' - ' . ($external_campus_name ?? '-'));
                                          } else {
                                            echo htmlspecialchars($wmsu_campus_name);
                                          }
                                        } else {
                                          echo htmlspecialchars($external_campus_name ?? '-');
                                        }
                                        ?>
                                      </td>

                                      <td style="text-align: left;">
                                        <?php if (!empty($voter['voting_period_names'])): ?>
                                          <ul style="margin-bottom: 0; padding-left: 20px;">
                                            <?= $voter['voting_period_names'] ?>
                                          </ul>
                                        <?php else: ?>
                                          No elections
                                        <?php endif; ?>
                                      </td>
                                      <td style="text-align: left;">
                                        <?php if (!empty($voter['precincts'])): ?>
                                          <ul style="margin-bottom: 0; padding-left: 20px;">
                                            <?= $voter['precincts'] ?>
                                          </ul>
                                        <?php else: ?>
                                          No elections
                                        <?php endif; ?>
                                      </td>
                                      <td style="text-align: left;">
                                        <?php if (!empty($voter['vote_status'])): ?>
                                          <ul style="margin-bottom: 0; padding-left: 20px;">
                                            <?= $voter['vote_status'] ?>
                                          </ul>
                                        <?php else: ?>
                                          No elections
                                        <?php endif; ?>
                                      </td>

                                      <td>
                                        <span class="badge bg-<?= strtolower($voter['status']) === 'confirmed' ? 'success' : 'warning' ?>">
                                          <?= strtoupper(htmlspecialchars($voter['status'])) ?>
                                        </span>
                                      </td>

                                      <td><?= htmlspecialchars($voter['email']) ?></td>
                                      <td><?= htmlspecialchars($voter['total_emails_sent']) ?></td>


                                      <td>
                                        <div class="dropdown">
                                          <button class="btn btn-warning text-white dropdown-toggle <?= !$canSendQr ? 'disabled' : '' ?>"
                                            type="button"
                                            id="sendQrDropdown"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                            <?= !$canSendQr ? 'disabled' : '' ?>>
                                            <i class="mdi mdi-qrcode"></i> Send QR
                                          </button>

                                          <ul class="dropdown-menu" aria-labelledby="sendQrDropdown">
                                            <?php foreach ($votingPeriods as $election): ?>
                                              <li>
                                                <a class="dropdown-item button-send-qr" href="#"
                                                  data-student-id="<?= htmlspecialchars($voter['student_id']) ?>"
                                                  data-election-id="<?= $election['id'] ?>"
                                                  data-election-name="<?= htmlspecialchars($election['name']) ?>">
                                                  <?= htmlspecialchars($election['name']) ?>
                                                </a>
                                              </li>
                                            <?php endforeach; ?>
                                          </ul>
                                        </div>



                                        <button class="button-view-primary" data-bs-toggle="modal" data-bs-target="#viewModal" data-student-id="<?= htmlspecialchars($voter['student_id']) ?>">
                                          <i class="mdi mdi-eye"></i> View
                                        </button>
                                        <button class="button-view-danger deleteBtn" data-email="<?php echo $voter['email'] ?>">
                                          <i class="mdi mdi-delete"></i> Delete
                                        </button>
                                        <br><br>
                                        <div class="block" style="width:100%">
                                          <?php
                                          if ($custom_voter_option == 1) {
                                            echo '<a href="view_voter.php?student_id=' . urlencode($voter['student_id']) . '" 
            class="btn btn-primary text-white">
            <i class="mdi mdi-eye"></i> View Custom Voter Options
          </a>';
                                          }
                                          ?>
                                        </div>
                                      </td>
                                    </tr>
                                  <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                  <tr>
                                    <th><input type="text" class="form-control" placeholder="Search School Year"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Semester"></th>
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
                                    <th><input type="text" class="form-control" placeholder="Search WMSU Campus Location"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Elections"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Precinct"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Vote Status"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Account Status"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Email"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Emails Sent"></th>
                                    <th></th> <!-- Empty cell for Manage column -->
                                  </tr>
                                </tfoot>
                              </table>

                              <!-- Include DataTables CSS and JS (already in your original code) -->
                              <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
                              <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                              <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                              <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

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
                                            <p><strong>College:</strong> <span id=""><?php echo $college_name; ?></span></p>
                                            <p><strong>Course:</strong> <span id=""><?php echo $course_name; ?></span></p>
                                            <p><strong>Major:</strong> <span id=""><?php echo $major_name ?? '' ?></span></p>
                                            <p><strong>Department:</strong> <span id=""><?php echo $department_name; ?></span></p>
                                            <p><strong>Year Level:</strong> <span id=""><?php echo $yearLevelName; ?></span></p>
                                            <p><strong>WMSU Campus Location:</strong> <span id=""></span></p>
                                            <p><strong>WMSU ESU Campus Location:</strong> <span id=""><?php echo $external_campus_name ?? 'N/A' ?></span></p>
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
                                    if (index < 18 && index != 7) { // Columns 0-6, 8-17 are text inputs
                                      $(this).html('<input type="text" class="form-control" placeholder="Search ' + title + '" />');
                                    }
                                  });

                                  // Apply text input searches
                                  table.columns([0, 1, 2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17]).every(function() {
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
                                    table.column(7).search(val ? '^' + val + '$' : '', true, false).draw();
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

                          <script>
                            const votingStartTime = <?= strtotime($currentPeriod['start_period']) ?> * 1000; // ms
                            const qrWindowStart = votingStartTime - (2 * 60 * 60 * 1000); // 2 hours before start
                            const now = <?= time() ?> * 1000; // current time in ms
                          </script>
                          <script>
                            const countdownEl = document.getElementById('qrCountdownMessage');
                            const qrBtn = document.getElementById('sendAllQrBtn');

                            function updateCountdown() {
                              const now = new Date().getTime();

                              if (now < qrWindowStart) {
                                // Not yet in QR sending window
                                let diff = qrWindowStart - now;
                                const hours = Math.floor(diff / (1000 * 60 * 60));
                                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                                countdownEl.textContent = `QR sending opens in ${hours}h ${minutes}m ${seconds}s`;

                                qrBtn.classList.remove('btn-success');
                                qrBtn.classList.add('btn-primary', 'disabled');
                                qrBtn.disabled = true;
                              } else if (now >= qrWindowStart && now <= votingStartTime) {
                                // QR sending is live
                                countdownEl.textContent = 'QR sending is live!';
                                qrBtn.classList.remove('btn-primary', 'disabled');
                                qrBtn.classList.add('btn-success');
                                qrBtn.disabled = false;
                              } else {
                                // QR sending window passed
                                countdownEl.textContent = 'QR sending period has ended';
                                qrBtn.classList.remove('btn-success');
                                qrBtn.classList.add('btn-primary', 'disabled');
                                qrBtn.disabled = true;
                              }
                            }

                            // Update every second
                            setInterval(updateCountdown, 1000);
                            updateCountdown(); // initial call
                          </script>



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

                            document.addEventListener('click', function(e) {
                              if (e.target.closest('.button-send-qr')) {
                                const btn = e.target.closest('.button-send-qr');
                                const studentId = btn.dataset.studentId;
                                const electionId = btn.dataset.electionId; // <-- GET ELECTION ID

                                if (!studentId || !electionId) {
                                  Swal.fire('Missing Data', 'Student ID or Election ID is missing.', 'warning');
                                  return;
                                }

                                btn.disabled = true;
                                btn.innerHTML = 'Sending...';

                                Swal.fire({
                                  title: `Sending QR...`,
                                  html: 'Please wait...',
                                  allowOutsideClick: false,
                                  didOpen: () => {
                                    Swal.showLoading();
                                  }
                                });

                                fetch('send_qr.php', {
                                    method: 'POST',
                                    headers: {
                                      'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: 'student_id=' + encodeURIComponent(studentId) +
                                      '&election_id=' + encodeURIComponent(electionId) // <-- SEND ELECTION ID
                                  })
                                  .then(res => res.json())
                                  .then(data => {
                                    Swal.fire({
                                      title: data.status === 'success' ? 'Success!' : 'Error',
                                      text: data.message,
                                      icon: data.status === 'success' ? 'success' : 'error',
                                      confirmButtonText: 'OK'
                                    }).then((result) => {
                                      if (result.isConfirmed && data.status === 'success') {
                                        location.reload();
                                      }
                                    });
                                  })
                                  .catch(() => {
                                    Swal.fire('Oops!', 'Something went wrong while sending the QR code.', 'error');
                                  })
                                  .finally(() => {
                                    btn.disabled = false;
                                    btn.innerHTML = '<i class="mdi mdi-qrcode"></i> Send QR';
                                  });
                              }
                            });


                            let totalStudents = 0;

                            $(document).ready(function() {
                              $(document).on('click', '.send-qr-option', function(e) {
                                e.preventDefault();

                                const electionId = $(this).data('election-id'); // voting_period_id
                                const electionName = $(this).data('election-name');

                                console.log("Selected election:", electionId, electionName);

                                // Disable button during processing
                                $(this).prop('disabled', true);
                                const btn = $(this);

                                // Show processing alert
                                Swal.fire({
                                  title: 'Sending QR Codes to All Students',
                                  html: `
        <p id="qrTotalStudents" class="mb-2"></p>
    
        <p id="qrProgressText" class="mt-2">All QR codes have been sent!</p>
        <div id="qrResults" class="text-left small mt-3" style="max-height: 200px; overflow-y: auto;"></div>
    `,
                                  showConfirmButton: false,
                                  allowOutsideClick: false,
                                  didOpen: () => {
                                    // Get student count first before starting
                                    $.ajax({
                                      url: 'get_student_count.php',
                                      method: 'GET',
                                      data: {
                                        election_id: electionId,
                                        election_name: electionName
                                      },
                                      dataType: 'json',
                                      success: function(countResponse) {
                                        console.log("❌ Unexpected response:", countResponse);
                                        console.log("✅ AJAX success callback triggered");
                                        if (countResponse.status !== 'success') {
                                          Swal.fire('Error', 'Unable to get student count', 'error');
                                          btn.prop('disabled', false);
                                          return;
                                        }

                                        totalStudents = countResponse.total;
                                        $('#qrTotalStudents').text();
                                        $('#qrProgressText').text(`Processing students...`);

                                        if (totalStudents === 0) {
                                          Swal.fire('Error', 'No students found', 'error');
                                          btn.prop('disabled', false);
                                          return;
                                        }

                                        // Now start sending
                                        sendAllQrCodes();
                                      },
                                      error: function() {
                                        Swal.fire('Error', 'Could not get student count', 'error');
                                        btn.prop('disabled', false);
                                      }
                                    });
                                  }
                                });

                                function sendAllQrCodes(offset = 0) {
                                  console.log("🚀 sendAllQrCodes() started");
                                  // Update progress
                                  $.ajax({
                                    url: 'get_student_count.php',
                                    method: 'GET',
                                    data: {
                                      election_id: electionId,
                                    },
                                    dataType: 'json',
                                    success: function(countResponse) {
                                      const totalStudents = countResponse.total;
                                      if (totalStudents === 0) {
                                        Swal.fire('Error', 'No students found', 'error');
                                        btn.prop('disabled', false);
                                        return;
                                      }

                                      // Calculate progress percentage
                                      const percent = Math.round((offset / totalStudents) * 100);



                                      // Send batch request
                                      $.ajax({
                                        url: 'send_qr_batch.php',
                                        method: 'POST',
                                        data: {
                                          offset: offset,

                                          election_id: electionId,
                                        },
                                        dataType: 'json',
                                        success: function(response) {
                                          // Process results
                                          response.results.forEach(result => {
                                            const icon = result.status === 'success' ? '✅' : '❌';
                                            $('#qrResults').append(`
                                    <div>${icon} ${result.student_id}: ${result.message || 'Sent'}</div>
                                `);
                                          });

                                          // Scroll results to bottom
                                          const resultsDiv = document.getElementById('qrResults');
                                          resultsDiv.scrollTop = resultsDiv.scrollHeight;

                                          // Update progress
                                          const newOffset = offset + response.results.length;
                                          const newPercent = Math.round((newOffset / totalStudents) * 100);

                                          $('#qrProgressText').text(`Processed ${newOffset} of ${totalStudents} students`);

                                          // Continue if there are more students
                                          if (newOffset < totalStudents) {
                                            sendAllQrCodes(newOffset);
                                          } else {
                                            // Complete
                                            $('#qrProgressText').html(`
    ✅ <strong>Completed!</strong><br>
    ✅ ${response.success_count} succeeded<br>
    ❌ ${totalStudents - response.success_count} failed
`);


                                            Swal.update({
                                              showConfirmButton: true
                                            });
                                            btn.prop('disabled', false);
                                          }
                                        },
                                        error: function() {
                                          $('#qrResults').append('<div>❌ Error processing batch</div>');
                                          // Continue with next batch anyway
                                          const newOffset = offset + 10;
                                          if (newOffset < totalStudents) {
                                            sendAllQrCodes(newOffset);
                                          } else {
                                            btn.prop('disabled', false);
                                          }
                                        }
                                      });
                                    },
                                    error: function() {
                                      Swal.fire('Error', 'Could not get student count', 'error');
                                      btn.prop('disabled', false);
                                    }
                                  });
                                }
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

                                const electionId = $(this).data('election-id');
                                const electionName = $(this).data('election-name');

                                // Show loading SweetAlert
                                Swal.fire({
                                  title: 'Sending QR Code...',
                                  text: 'Please wait while the QR code is being sent.',
                                  allowOutsideClick: false, // Prevent closing while loading
                                  didOpen: () => {
                                    console.log("Swal opened, fetching student count...");

                                    $.ajax({
                                      url: 'get_student_count.php',
                                      method: 'GET',
                                      dataType: 'json',
                                      success: function(countResponse) {
                                        console.log("Count response:", countResponse);

                                        if (countResponse.status !== 'success') {
                                          Swal.fire('Error', 'Unable to get student count', 'error');
                                          btn.prop('disabled', false);
                                          return;
                                        }

                                        totalStudents = countResponse.total;
                                        console.log("Total students:", totalStudents);
                                        $('#qrTotalStudents').text(`Total students to process: ${totalStudents}`);

                                        if (totalStudents === 0) {
                                          Swal.fire('Error', 'No students found', 'error');
                                          btn.prop('disabled', false);
                                          return;
                                        }

                                        // ✅ THIS MUST FIRE
                                        console.log("Calling sendAllQrCodes()");
                                        sendAllQrCodes();
                                      },
                                      error: function(xhr, status, error) {
                                        console.error("AJAX failed:", xhr.responseText);
                                        Swal.fire('Error', 'Could not get student count', 'error');
                                        btn.prop('disabled', false);
                                      }
                                    });
                                  }


                                });

                                // Send the QR code to the server
                                fetch("send_qr.php", {
                                    method: "POST",
                                    headers: {
                                      "Content-Type": "application/x-www-form-urlencoded"
                                    },
                                    body: `email=${encodeURIComponent(email)}&student_id=${encodeURIComponent(student_id)}&election_id=${encodeURIComponent(electionId)}&election_name=${encodeURIComponent(electionName)}`
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