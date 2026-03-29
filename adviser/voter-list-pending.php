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

$adviserEmail = $_SESSION['email'] ?? null;
$has_changed = 0;
$voters = [];
$rejected_voters = [];
$totalVoters = 0;
$importedByAdviser = 0;
$pendingVerification = 0;

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

if ($adviserEmail) {
  // Get adviser info
  $stmt = $pdo->prepare("SELECT * FROM advisers WHERE email = ?");
  $stmt->execute([$adviserEmail]);
  $adviser = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($adviser) {
    $adviserId = $adviser['id'];
    $college = $adviser['college_id'];
    $department = $adviser['department_id'];
    $year = $adviser['school_year'];
    $wmsu_campus = $adviser['wmsu_campus_id'];
    $external_campus = $adviser['external_campus_id'];
    $major = $adviser['major_id'] ?? null;
    $year = $adviser['year_level'];
    $full_name = isset($adviser['full_name']) ? $adviser['full_name'] : null;

    $has_changed = (isset($adviser_has_changed['has_changed']) && $adviser_has_changed['has_changed'] == 1) ? 1 : 0;

    $stmt = $pdo->prepare("SELECT * FROM email WHERE adviser_id = ?");
    $stmt->execute([$adviserId]);
    $smtp = $stmt->fetch(PDO::FETCH_ASSOC);

    $smtp_capacity = $smtp['capacity'];

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

    // Assuming $pdo is your PDO connection

    // 1. College name
    $stmt = $pdo->prepare("SELECT college_name FROM colleges WHERE college_id = ?");
    $stmt->execute([$college]);
    $college_name = $stmt->fetchColumn(); // returns the name

    // 2. Department name
    $stmt = $pdo->prepare("SELECT department_name FROM departments WHERE department_id = ?");
    $stmt->execute([$department]);
    $department_name = $stmt->fetchColumn();

    // 3. Course name

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

    if (!$external_campus_name) {
      $external_campus_name = 'N/A';
    }

    // 5. Major name
    $stmt = $pdo->prepare("SELECT major_name FROM majors WHERE major_id = ?");
    $stmt->execute([$major]);
    $major_name = $stmt->fetchColumn();

    if (!$major_name) {
      $major_name = 'N/A';
    }


    // Check if adviser has changed info
    $stmt = $pdo->prepare("SELECT has_changed FROM advisers WHERE email = ? AND has_changed = 1");
    $stmt->execute([$adviserEmail]);
    $adviser_has_changed = $stmt->fetch(PDO::FETCH_ASSOC);
    $has_changed = !empty($adviser_has_changed) ? 1 : 0;

    // Prepare base params
    $baseParams = [$college, $department, $wmsu_campus, $external_campus, $year, $major];


    // Election participation columns
    $extraSelect = "
, ep.participated_elections AS voting_period_names
, ep.assigned_precincts AS precincts
, ep.vote_statuses AS vote_status
";

    // Election participation join
    $epJoin = "
LEFT JOIN (
    SELECT 
        base.student_id,
        GROUP_CONCAT(CONCAT('<li>', base.election_name, '</li>') ORDER BY base.election_name SEPARATOR '') AS participated_elections,
        GROUP_CONCAT(CONCAT('<li>', base.precinct_name, '</li>') ORDER BY base.election_name SEPARATOR '') AS assigned_precincts,
        GROUP_CONCAT(
            CONCAT(
                '<li>',
                IF(base.has_voted > 0,
                    CONCAT('<span class=\"badge badge-success\">', base.precinct_name, ' - Voted</span>'),
                    CONCAT('<span class=\"badge badge-warning\">', base.precinct_name, ' - Not Voted</span>')
                ),
                '</li>'
            )
            ORDER BY base.election_name SEPARATOR ''
        ) AS vote_statuses
    FROM (
        SELECT 
            pv.student_id,
            el.election_name,
            p.name AS precinct_name,
            COUNT(vo.id) AS has_voted
        FROM precinct_voters pv
        JOIN precincts p ON pv.precinct = p.id
        JOIN precinct_elections pe ON p.name = pe.precinct_name
        JOIN elections el ON pe.election_name = el.election_name
        JOIN voting_periods vp2 ON el.id = vp2.election_id
        LEFT JOIN votes vo 
            ON vp2.id = vo.voting_period_id 
            AND pv.student_id = vo.student_id
        GROUP BY pv.student_id, el.id, p.id
    ) base
    GROUP BY base.student_id
) ep ON v.student_id = ep.student_id
";


    // --------------------------------------------------
    // BASE CONDITION
    // --------------------------------------------------

    if ($major) {
      $conditions = "
v.college = ?
AND v.department = ?
AND v.wmsu_campus = ?
AND v.major = ?
";

      $params = [$college, $department, $wmsu_campus, $major];
    } else {
      $conditions = "
v.college = ?
AND v.department = ?
AND v.wmsu_campus = ?

";

      $params = [$college, $department, $wmsu_campus];
    }


    // Handle external campus
    if ($external_campus && $external_campus !== 'None') {

      $conditions .= " AND v.external_campus = ? ";
      $params[] = $external_campus;
    } else {

      $conditions .= " AND (v.external_campus IS NULL OR v.external_campus = 'None') ";
    }

    $conditions .= " AND v.year_level = ? ";
    $params[] = $year;


    // --------------------------------------------------
    // BASE QUERY
    // --------------------------------------------------

    $baseQuery = "
SELECT 
    v.*,
    ay.year_label AS school_year,
    ay.semester,
    COALESCE(erl.count, 0) AS total_emails_sent
    $extraSelect
FROM voters v
LEFT JOIN academic_years ay 
    ON v.academic_year_id = ay.id
LEFT JOIN email_role_log erl 
    ON v.student_id = erl.student_id
$epJoin
WHERE $conditions
AND TRIM(LOWER(v.status)) = ?
";


    // --------------------------------------------------
    // FINAL QUERIES
    // --------------------------------------------------

    $statusPendingQuery  = $baseQuery;
    $statusRejectedQuery = $baseQuery;

    $paramsPending  = array_merge($params, ['pending']);
    $paramsRejected = array_merge($params, ['rejected']);
  }
  // Fetch pending voters
  $stmt = $pdo->prepare($statusPendingQuery);
  $stmt->execute($paramsPending);
  $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Fetch rejected voters
  $stmt = $pdo->prepare($statusRejectedQuery);
  $stmt->execute($paramsRejected);
  $rejected_voters = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

  <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

  <script
    src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            <a class="nav-link" href="voter-list-pending.php" style="background-color: #B22222 !important;">
              <i class="menu-icon mdi mdi-account-multiple-plus" style="color: white !important;"></i>
              <span class="menu-title" style="color: white !important;">Pending Verification</span>
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
                              <h3 class="mb-0"><b>Pending Voters</b></h3>


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

                            <br>
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
                                    <th>Emails Sent</th>
                                    <th>College</th>
                                    <th>Course</th>
                                    <th>Department</th>
                                    <th>Major</th>
                                    <th>Year Level</th>
                                    <th>Campus</th>
                                    <th>Elections Participated</th>
                                    <th>Precinct</th>
                                    <th>Vote Status</th>
                                    <th>Account Status</th>

                                    <th>Email</th>
                                    <th>Total Emails Sent</th>
                                    <th>Email Details</th>
                                    <th>Actions</th>
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
                                      <td><?= htmlspecialchars($voter['total_emails_sent']) ?></td>
                                      <td><?= htmlspecialchars($college_name) ?></td>
                                      <td><?= htmlspecialchars($course_name) ?></td>
                                      <td><?= htmlspecialchars($department_name) ?></td>
                                      <td><?= htmlspecialchars($major_name) ?? 'N/A' ?></td>
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

                                      <td><?= htmlspecialchars($voter['email'] ?? '') ?></td>
                                      <td><?= htmlspecialchars($voter['total_emails_sent']) ?? 0 ?></td>
                                      <td>
                                        <?php
                                        if (empty($log['email_details'])) {
                                          // No logs at all
                                          echo "<span class='badge badge-warning text-black'>NO EMAIL SENT</span>";
                                        } else {
                                          $details = explode('<br>', $log['email_details']);

                                          foreach ($details as $detail) {
                                            $detail = trim($detail);
                                            if ($detail === '') continue;

                                            preg_match('/^(\w+)\s*\(([^)]+)\s*-\s*([^)]+)\)$/', $detail, $matches);

                                            if (count($matches) === 4) {
                                              $status = strtolower($matches[1]);
                                              $timestamp = $matches[2];
                                              $votingPeriodName = $matches[3];

                                              $badgeClass = match ($status) {
                                                'sent'   => 'badge-success',
                                                'failed' => 'badge-danger',
                                                default  => 'badge-secondary',
                                              };

                                              $formattedTime = date('M j, Y g:i A', strtotime($timestamp));
                                              $displayText = strtoupper($status) . ' - ' . $votingPeriodName . ' at ' . $formattedTime;

                                              $dataAttributes = $status === 'sent'
                                                ? "data-student-id='{$log['student_id']}' data-voting-period='{$log['election_id']}'"
                                                : "";

                                              echo "<span class='badge $badgeClass me-1 mb-1 qr-badge' style='cursor:pointer;' $dataAttributes>
                    $displayText
                  </span><br>";
                                            } else {
                                              echo "<span class='badge badge-secondary'>$detail</span><br>";
                                            }
                                          }
                                        }
                                        ?>
                                      </td>



                                      <td>
                                        <button class="button-view-primary" data-bs-toggle="modal" data-bs-target="#viewModal" data-student-id="<?= htmlspecialchars($voter['student_id']) ?>">
                                          <i class="mdi mdi-eye"></i> View
                                        </button>
                                        <button class="button-view-danger deleteBtn" data-email="<?php echo $voter['email'] ?>">
                                          <i class="mdi mdi-delete"></i> Delete
                                        </button>
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
                                    <th><input type="text" class="form-control" placeholder="Search WMSU ESU Campus Location"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Vote Status"></th>
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
                                            <p><strong>WMSU Campus Location:</strong> <span id=""><?php echo $wmsu_campus_name ?></span></p>
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
                                      <button type="button" class="btn btn-warning text-white" id="rejectStatusBtn">Reject</button>
                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                  </div>
                                </div>
                              </div>



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


                    <br> <Br>

                    <div class="card">
                      <div class="card-body">
                        <div class="container-fluid mt-4">
                          <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                              <h3 class="mb-0"><b>Rejected Voters</b></h3>




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

                            <br>




                            <div class="table-responsive">
                              <table id="rejectedVotersTable" class="table table-striped table-bordered nowrap" style="width:100%">
                                <thead>
                                  <tr>
                                    <th>School Year</th>
                                    <th>Semester</th>
                                    <th>Student ID</th>
                                    <th>Email</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th>Last Name</th>
                                    <th>Emails Sent</th>
                                    <th>College</th>
                                    <th>Course</th>
                                    <th>Department</th>
                                    <th>Year Level</th>
                                    <th> Campus </th>

                                    <th>Elections Participated</th>
                                    <th>Precinct</th>
                                    <th>Vote Status</th>
                                    <th>Status</th>
                                    <th>Rejected Reason</th>
                                    <th>Manage</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php foreach ($rejected_voters as $voter): ?>
                                    <tr>
                                      <td><?= htmlspecialchars($voter['school_year']) ?></td>
                                      <td><?= htmlspecialchars($voter['semester']) ?></td>
                                      <td><?= htmlspecialchars($voter['student_id']) ?></td>
                                      <td><?= htmlspecialchars($voter['email']) ?></td>
                                      <td><?= htmlspecialchars($voter['first_name']) ?></td>
                                      <td><?= htmlspecialchars($voter['middle_name']) ?></td>
                                      <td><?= htmlspecialchars($voter['last_name']) ?></td>
                                      <td><?= htmlspecialchars($voter['total_emails_sent']) ?></td>
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

                                      <td><?= htmlspecialchars($voter['rejection_reason'] ?: 'None') ?></td>



                                      <td>
                                        <button class="button-view-primary" data-bs-toggle="modal" data-bs-target="#viewModal" data-student-id="<?= htmlspecialchars($voter['student_id']) ?>">
                                          <i class="mdi mdi-eye"></i> View
                                        </button>
                                        <button class="button-view-danger deleteBtn" data-email="<?php echo $voter['email'] ?>">
                                          <i class="mdi mdi-delete"></i> Delete
                                        </button>
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
                                    <th><input type="text" class="form-control" placeholder="Search WMSU ESU Campus Location"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Elections"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Precinct"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Vote Status"></th>
                                    <th><input type="text" class="form-control" placeholder="Search Status"></th>
                                    <th>
                                      <select class="form-control" id="rejectionFilter">
                                        <option value="">All Reasons</option>
                                        <option value="Incomplete Information">Incomplete Information</option>
                                        <option value="Invalid ID or Document">Invalid ID or Document</option>
                                        <option value="Not Eligible">Not Eligible</option>

                                      </select>
                                    </th>
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
                                            <p><strong>WMSU Campus Location:</strong> <span id=""><?php echo $wmsu_campus_name ?></span></p>
                                            <p><strong>WMSU ESU Campus Location:</strong> <span id=""><?php echo $external_campus_name ?? 'N/A' ?></span></p>
                                            <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                                          </div>
                                          <div class="col-md-6">
                                            <h6>Certificate of Registration</h6>
                                            <div class="image-container">
                                              <p><strong>COR 1:</strong></p>
                                              <img id="modalCor1" src="" alt="COR 1" class="img-fluid mb-3" style="max-width: 100%; max-height: 200px; display: none;">
                                              <p id="modalCor1Error" class="text-danger" style="display: none;">COR 1 not available</p>
                                            </div>
                                            <div class="image-container">
                                              <p><strong>COR 2:</strong></p>
                                              <img id="modalCor2" src="" alt="COR 2" class="img-fluid" style="max-width: 100%; max-height: 200px; display: none;">
                                              <p id="modalCor2Error" class="text-danger" style="display: none;">COR 2 not available</p>
                                            </div>
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                    <div class="modal-footer">
                                      <button type="button" class="btn btn-primary text-white" id="confirmStatusBtn" style="display: none;">Confirm</button>
                                      <button type="button" class="btn btn-warning text-white" id="rejectStatusBtn">Reject</button>
                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                  </div>
                                </div>
                              </div>

                              <!-- DataTables Initialization and Column Filtering -->
                              <script>
                                $(document).ready(function() {
                                  // Initialize main voters table
                                  var table = $('#votersTable').DataTable({
                                    responsive: true,
                                    pageLength: 10,
                                    language: {
                                      search: "Filter records:"
                                    }
                                  });

                                  // Initialize rejected voters table if it exists
                                  if ($('#rejectedVotersTable').length) {
                                    $('#rejectedVotersTable').DataTable({
                                      responsive: true,
                                      pageLength: 10,
                                      language: {
                                        search: "Filter records:"
                                      }
                                    });
                                  }

                                  // Add footer inputs for searchable columns (skip column 5 = College, handled by dropdown)
                                  $('#votersTable tfoot th').each(function(index) {
                                    var title = $('#votersTable thead th').eq(index).text();
                                    if (index < 17 && index !== 5) {
                                      $(this).html('<input type="text" class="form-control" placeholder="Search ' + title + '" />');
                                    }
                                  });

                                  // Apply search to footer inputs (columns: 0-4, 6-8)
                                  table.columns([0, 1, 2, 3, 4, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16]).every(function() {
                                    var column = this;
                                    $('input', this.footer()).on('keyup change clear', function() {
                                      if (column.search() !== this.value) {
                                        column.search(this.value).draw();
                                      }
                                    });
                                  });

                                  // College filter dropdown (column index 5)
                                  $('#collegeFilter').on('change', function() {
                                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                    table.column(5).search(val ? '^' + val + '$' : '', true, false).draw();
                                  });

                                  // Academic year filter dropdown (column index 8)
                                  $('#academicYearFilter').on('change', function() {
                                    var val = $(this).val();
                                    table.column(8).search(val, false, false).draw();
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
                          $('#modalStatus').text(
                            (response.data.status || 'N/A')
                            .toLowerCase()
                            .replace(/^./, c => c.toUpperCase())
                          );

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
                      beforeSend: function() {
                        // Show SweetAlert2 loading popup
                        Swal.fire({
                          title: 'Processing',
                          text: 'Updating voter status and sending email...',
                          allowOutsideClick: false,
                          allowEscapeKey: false,
                          didOpen: () => {
                            Swal.showLoading();
                          }
                        });
                      },
                      success: function(response) {
                        // Close the loading popup
                        Swal.close();

                        if (response.success) {
                          // Update modal status
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
                        // Close the loading popup
                        Swal.close();

                        // SweetAlert2 error popup
                        Swal.fire({
                          icon: 'error',
                          title: 'Server Error',
                          text: 'An error occurred while updating voter status: ' + error
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

                $('#rejectStatusBtn').on('click', async function() {
                  const studentId = $('#modalStudentId').text(); // Get student ID from modal

                  const {
                    value: reason
                  } = await Swal.fire({
                    title: 'Reject Voter',
                    input: 'select',
                    inputOptions: {
                      'Incomplete Information': 'Incomplete Information',
                      'Invalid ID or Document': 'Invalid ID or Document',
                      'Not Eligible': 'Not Eligible',

                    },
                    inputPlaceholder: 'Select a reason',
                    showCancelButton: true,
                    confirmButtonText: 'Reject',
                    cancelButtonText: 'Cancel',
                    inputValidator: (value) => {
                      if (!value) return 'Please select a reason for rejection.';
                    }
                  });

                  if (reason) {
                    // Send AJAX request to reject voter
                    $.ajax({
                      url: 'reject_voter.php',
                      type: 'POST',
                      data: {
                        student_id: studentId,
                        reason: reason
                      },
                      dataType: 'json',
                      beforeSend: function() {
                        Swal.fire({
                          title: 'Rejecting...',
                          text: 'Please wait while we reject the voter.',
                          allowOutsideClick: false,
                          allowEscapeKey: false,
                          didOpen: () => Swal.showLoading()
                        });
                      },
                      success: function(response) {
                        Swal.close();
                        if (response.status === 'success') {
                          $('#modalStatus').text('rejected');
                          $('#confirmStatusBtn').hide();

                          Swal.fire({
                            icon: 'success',
                            title: 'Voter Rejected',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                          }).then(() => window.location.reload());
                        } else {
                          Swal.fire('Error', response.message || 'Failed to reject voter.', 'error');
                        }
                      },
                      error: function(xhr, status, error) {
                        Swal.close();
                        Swal.fire('Server Error', 'Something went wrong: ' + error, 'error');
                      }
                    });
                  }
                });

                const lightbox = GLightbox({
                  selector: '.glightbox'
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