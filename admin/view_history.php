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
      <?php
      $academic_year_id = isset($_GET['academic_year_id']) ? (int)$_GET['academic_year_id'] : 0;
      // Fetch archived elections

      function getAcademicYear($pdo)
      {
        $stmt = $pdo->prepare("
        SELECT 
            *
        FROM archived_academic_years
        WHERE status = 'archived'
        ORDER BY id DESC
    ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

      $academic_years = getAcademicYear($pdo);

      function getElectionHistory($pdo, $ayId)
      {
        $stmt = $pdo->prepare("
        SELECT 
            ae.id,
            ae.election_name,
            ae.semester,
            ae.school_year_start,
            ae.school_year_end,
            ae.start_period,
            ae.end_period,
            ae.parties,
            ae.turnout,
            ae.archived_on,
            ae.voting_period_id,
            aay.year_label,
            aay.semester AS academic_semester,
            aay.custom_voter_option as custom_voter_option
        FROM archived_elections ae
        LEFT JOIN archived_academic_years aay ON ae.academic_year_id = aay.id
        WHERE ae.status = 'archived' AND academic_year_id = :ayId
        ORDER BY ae.id DESC
    ");
        $stmt->execute(['ayId' => $ayId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

      // Fetch archived candidacies
      function getCandidacyHistory($pdo, $academicYearId)
      {
        $stmt = $pdo->prepare("
        SELECT 
            ac.id,
            ac.election_id,
            ac.semester,
            ac.school_year_start,
            ac.school_year_end,
            ac.start_period,
            ac.end_period,
            ac.total_filed,
            ac.archived_on,
            ac.voting_period_id,
            ae.election_name,
            aay.year_label,
            aay.semester AS academic_semester
        FROM archived_candidacies ac
        LEFT JOIN archived_elections ae 
            ON ac.election_id = ae.id
        LEFT JOIN archived_academic_years aay 
            ON ae.academic_year_id = aay.id
     WHERE ac.status = 'archived' AND academic_year_id = :ayId
        ORDER BY ac.id DESC
    ");
        $stmt->execute(['ayId' => $academicYearId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

      // Fetch candidates for a specific election
      function getCandidatesForElection($pdo, $voting_period_id)
      {
        $stmt = $pdo->prepare("
      SELECT 
    -- Year level
    ayl.year_level AS actual_year_level,

    -- Course
    c.course_name,
    c.course_code,

    -- Major
    m.major_name,

    -- Department
    d.department_name,

    -- College
    col.college_name,
    col.college_abbreviation,

    -- Campus (via college coordinates)
    cam.campus_name,
    cam.campus_location,
    cam.campus_type,

    el.election_name,

    ac.candidate_name,
    ac.position,
    ac.party,
    ac.filed_on,
    ac.outcome,
    ac.votes_received,
    av.student_id,
    av.course,
    av.college,
    av.department,
    av.wmsu_campus,
    av.external_campus

FROM archived_candidates ac
LEFT JOIN archived_voters av 
    ON ac.voting_period_id = av.voting_period_id 
LEFT JOIN archived_actual_year_levels ayl 
    ON ayl.id = av.year_level
LEFT JOIN archived_courses c 
    ON c.id = av.course
LEFT JOIN archived_majors m 
    ON m.major_id = av.major
LEFT JOIN archived_departments d 
    ON d.department_id = av.department
LEFT JOIN archived_colleges col 
    ON col.college_id = av.college
LEFT JOIN archived_college_coordinates cc 
    ON cc.college_id = col.college_id
LEFT JOIN archived_campuses cam 
    ON cam.campus_id = av.wmsu_campus 
       OR cam.campus_id = av.external_campus
LEFT JOIN archived_elections el 
    ON el.id = ac.election_name

WHERE ac.voting_period_id = ?

/* Grouping by the candidate's unique name or ID prevents duplicates */
GROUP BY ac.candidate_name 
ORDER BY ac.position, ac.votes_received DESC;
    ");
        $stmt->execute([$voting_period_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

      function getElectionSummary($pdo, $voting_period_id)
      {
        $summary = [];

        // Total voters
        $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM archived_voters
        WHERE voting_period_id = ?
    ");
        $stmt->execute([$voting_period_id]);
        $summary['total_voters'] = $stmt->fetchColumn();

        // Voted
        $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM archived_voters
        WHERE voting_period_id = ? AND has_voted = 1
    ");
        $stmt->execute([$voting_period_id]);
        $summary['voted'] = $stmt->fetchColumn();

        // Not voted
        $summary['not_voted'] =
          $summary['total_voters'] - $summary['voted'];

        // Total candidates
        $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM archived_candidates
        WHERE voting_period_id = ?
    ");
        $stmt->execute([$voting_period_id]);
        $summary['total_candidates'] = $stmt->fetchColumn();

        return $summary;
      }
      $electionHistory = getElectionHistory($pdo, $academic_year_id);
      $candidacyHistory = getCandidacyHistory($pdo, $academic_year_id);



      // 2. Fetch all archived voters
      $ayId = $_GET['academic_year_id'] ?? null;

      $stmt = $pdo->prepare("SELECT 
    v.*,

    -- Year level
    ayl.year_level AS actual_year_level,

    -- Course
    c.course_name,
    c.course_code,

    -- Major
    m.major_name,

    -- Department
    d.department_name,

    -- College
    col.college_name,
    col.college_abbreviation,

    -- Campus
    cam.campus_name,
    cam.campus_location,
    cam.campus_type,

    el.election_name

FROM archived_voters v

LEFT JOIN archived_actual_year_levels ayl ON ayl.id = v.year_level
LEFT JOIN archived_courses c ON c.id = v.course
LEFT JOIN archived_majors m ON m.major_id = v.major
LEFT JOIN archived_departments d ON d.department_id = v.department
LEFT JOIN archived_colleges col ON col.college_id = v.college
LEFT JOIN archived_college_coordinates cc ON cc.college_id = col.college_id

LEFT JOIN archived_campuses cam 
    ON cam.campus_id = COALESCE(v.wmsu_campus, v.external_campus)

LEFT JOIN archived_elections el 
    ON el.id = v.election_name

WHERE v.status = 'archived'
AND el.academic_year_id = :ayId

ORDER BY v.election_name, v.voting_period_id, v.student_id
");

      $stmt->execute(['ayId' => $ayId]);
      $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // 3. Group voters by election_name and voting_period_id
      $groupedVoters = [];
      foreach ($voters as $voter) {
        $election_name = $voter['election_name'];
        $voting_period_id = $voter['voting_period_id'];

        $groupedVoters[$election_name][$voting_period_id][] = $voter;
      }

      // 4. Fetch all archived precincts

      $stmt = $archived_pdo->prepare("
    SELECT 
        p.*,
        d.department_name
    FROM archived_precincts p
    LEFT JOIN archived_departments d
        ON d.department_id = p.department
    ORDER BY p.archived_at DESC
");
      $stmt->execute();
      $allArchivedPrecincts = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // 5. Fetch all archived candidates
      $stmt = $archived_pdo->prepare("SELECT * FROM archived_candidates ORDER BY id DESC");
      $stmt->execute();
      $allArchivedCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Group candidates by election for easier access
      $groupedCandidates = [];
      foreach ($allArchivedCandidates as $cand) {
        $groupedCandidates[$cand['election_name']][] = $cand;
      }

      /**
       * Fetch all archived positions
       */
      function getArchivedPositions($pdo, $ayId)
      {
        $stmt = $pdo->prepare("
        SELECT 
            ap.*, 
            ae.election_name, 
            ae.semester, 
            ae.school_year_start, 
            ae.school_year_end, 
            aay.year_label, 
            aay.semester AS academic_semester
        FROM archived_positions ap
        LEFT JOIN archived_elections ae 
            ON ap.election_id = ae.id
        LEFT JOIN archived_academic_years aay 
            ON ae.academic_year_id = aay.id
        WHERE ae.academic_year_id = :ayId
        ORDER BY ae.election_name DESC, ap.name ASC
    ");

        $stmt->execute(['ayId' => $ayId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

      /**
       * Fetch all archived parties
       */
      function getArchivedParties($pdo, $ayId)
      {
        $stmt = $pdo->prepare("
      SELECT 
    ap.*, 
    ae.election_name,   -- ✅ ADD THIS
    ae.semester, 
    ae.school_year_start, 
    ae.school_year_end, 
    aay.year_label, 
    aay.semester AS academic_semester
FROM archived_parties ap
LEFT JOIN archived_elections ae 
    ON ap.voting_period_id = ae.voting_period_id
LEFT JOIN archived_academic_years aay 
    ON ae.academic_year_id = aay.id
WHERE aay.id = :ayId
ORDER BY ap.id DESC, ap.name ASC
    ");

        $stmt->execute(['ayId' => $ayId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

      function getAdditionalElectionDetails($pdo, $voting_period_id)
      {
        $details = [];

        // Candidacy Period
        $stmt = $pdo->prepare("SELECT start_period, end_period FROM archived_candidacies WHERE voting_period_id = ? LIMIT 1");
        $stmt->execute([$voting_period_id]);
        $candidacy = $stmt->fetch(PDO::FETCH_ASSOC);
        $details['candidacy_start'] = $candidacy['start_period'] ?? null;
        $details['candidacy_end'] = $candidacy['end_period'] ?? null;

        // Voting Period
        $stmt = $pdo->prepare("SELECT start_period, end_period FROM archived_voting_periods WHERE id = ? LIMIT 1");
        $stmt->execute([$voting_period_id]);
        $voting = $stmt->fetch(PDO::FETCH_ASSOC);
        $details['voting_start'] = $voting['start_period'] ?? null;
        $details['voting_end'] = $voting['end_period'] ?? null;

        // Voter Stats
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_registered,
                SUM(CASE WHEN has_voted = 1 THEN 1 ELSE 0 END) as voted
            FROM archived_voters
            WHERE voting_period_id = ?
        ");
        $stmt->execute([$voting_period_id]);
        $voterStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $details['total_registered'] = $voterStats['total_registered'] ?? 0;
        $details['voted'] = $voterStats['voted'] ?? 0;
        $details['not_voted'] = $details['total_registered'] - $details['voted'];

        // Total Candidates
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM archived_candidates WHERE voting_period_id = ?");
        $stmt->execute([$voting_period_id]);
        $details['total_candidates'] = $stmt->fetchColumn();

        return $details;
      }



      $archivedPositions = getArchivedPositions($pdo, $academic_year_id);
      $archivedParties = getArchivedParties($pdo, $academic_year_id);

      // Map election details for voters
      $electionDetailsMap = [];
      foreach ($electionHistory as $e) {
        $electionDetailsMap[$e['voting_period_id']] = $e;
      }


      if ($academic_year_id > 0) {
        $stmt = $pdo->prepare("
        SELECT custom_voter_option
        FROM archived_academic_years
        WHERE id = :id
        LIMIT 1
    ");
        $stmt->execute(['id' => $academic_year_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
          $custom_voter_option = $result['custom_voter_option'];
        } else {
        }
      }
      ?>

      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                  <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                      <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">View History</a>
                    </li>
                  </ul>
                </div>
                <div class="tab-content tab-content-basic">

                  <!-- Display Bootstrap Alert based on $custom_voter_option -->
                  <?php if ($custom_voter_option === null): ?>
                    <div class="alert alert-danger" role="alert">
                      Academic year not found or invalid ID.
                    </div>
                  <?php elseif ($custom_voter_option): ?>
                    <div class="alert alert-warning" role="alert">
                      <h6>Custom Voter Option is enabled.</h6>
                    </div>
                  <?php else: ?>
                    <div class="alert alert-primary" role="alert">
                      <h6>Custom Voter Option is disabled.</h6>
                    </div>
                  <?php endif; ?>

                  <?php
                  // Fetch archived academic years
                  $academicYearsArray = getAcademicYear($pdo);
                  ?>




                  <?php
                  // Get selected academic year from GET parameter
                  $selectedYear = isset($_GET['academic_year_id']) ? $_GET['academic_year_id'] : '';
                  ?>

                  <select name="academic_year" id="academic_year" class="form-select" onchange="redirectToYear(this.value)">
                    <option value="">Select Academic Year</option>
                    <?php foreach ($academicYearsArray as $academicYear): ?>
                      <option value="<?= htmlspecialchars($academicYear['id']) ?>"
                        <?= ($academicYear['id'] == $selectedYear) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($academicYear['year_label']) ?> | <?= htmlspecialchars($academicYear['semester']) ?>
                        <?= ($academicYear['id'] == $selectedYear) ? '(Chosen) ' : '' ?>
                      </option>
                    <?php endforeach; ?>
                  </select>

                  <script>
                    function redirectToYear(id) {
                      if (id) {
                        // Redirect to same page with ?academic_year=<id>
                        window.location.href = window.location.pathname + '?academic_year_id=' + encodeURIComponent(id);
                      }
                    }
                  </script>

                  <br> <br>

                  <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                    <!-- Election History Card -->
                    <div class="card card-rounded mb-5">
                      <div class="card-body">
                        <div class="row mt-4">
                          <div class="container mb-5">
                            <div class="d-flex align-items-center">
                              <h3><b>Archived Elections</b></h3>
                            </div>
                          </div>
                          <div class="table-responsive">
                            <table id="electionTable" class="table table-striped table-bordered nowrap" style="width:100%">
                              <thead>
                                <tr>
                                  <th>ID</th>
                                  <th>School Year</th>
                                  <th>Semester</th>
                                  <th>Election Name</th>
                                  <th>Start Date</th>
                                  <th>End Date</th>
                                  <th>Winner(s)</th>
                                  <th>Archived On</th>
                                  <th>Voters</th>
                                  <th>Details</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($electionHistory as $election): ?>
                                  <tr>
                                    <td><?php echo htmlspecialchars($election['id']); ?></td>
                                    <td>
                                      <?= !empty($election['year_label']) ? htmlspecialchars($election['year_label']) : ($election['school_year_start'] . '-' . $election['school_year_end']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(!empty($election['academic_semester']) ? $election['academic_semester'] : $election['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($election['election_name']); ?></td>
                                    <td><?php echo date("F j, Y", strtotime($election['start_period'])); ?></td>
                                    <td><?php echo date("F j, Y", strtotime($election['end_period'])); ?></td>
                                    <td>
                                      <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewWinnersModal<?php echo $election['id']; ?>">
                                        <i class="mdi mdi-trophy"></i> See Winners
                                      </button>
                                    </td>
                                    <td><?php echo htmlspecialchars($election['archived_on']); ?></td>
                                    <td>
                                      <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#viewVotersModal<?php echo $election['id']; ?>">
                                        <i class="mdi mdi-account-group"></i> See Voters
                                      </button>
                                    </td>
                                    <td>
                                      <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#viewElectionModal<?php echo $election['id']; ?>">
                                        <i class="mdi mdi-eye"></i> View Record
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

                    <!-- Archived Voters Card -->
                    <div class="card card-rounded mb-5">
                      <div class="card-body">
                        <div class="row mt-4">
                          <div class="container mb-5">
                            <div class="d-flex align-items-center">
                              <h3><b>Archived Voters</b></h3>
                            </div>
                            <div class="row mt-3">
                              <div class="col-md-6">
                                <label>Select Election Name</label>
                                <select id="voterElectionFilter" class="form-select">
                                  <option value="">All Elections</option>
                                  <?php
                                  $uniqueElections = [];
                                  foreach ($electionHistory as $e) {
                                    $uniqueElections[$e['election_name']] = $e['election_name'];
                                  }
                                  foreach ($uniqueElections as $ename): ?>
                                    <option value="<?= htmlspecialchars($ename) ?>"><?= htmlspecialchars($ename) ?></option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                              <div class="col-md-6">
                                <label>Select School Year and Semester</label>
                                <select id="voterSYFilter" class="form-select">
                                  <option value="">All School Years</option>

                                  <?php
                                  $uniqueSY = [];

                                  foreach ($electionHistory as $e) {
                                    $syStr = $e['year_label'] . ' | ' . $e['academic_semester'];
                                    $uniqueSY[$syStr] = $syStr;
                                  }

                                  foreach ($uniqueSY as $sy): ?>
                                    <option value="<?= htmlspecialchars($sy) ?>">
                                      <?= htmlspecialchars($sy) ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                            </div>
                          </div>
                          <div class="table-responsive">
                            <table id="archivedVotersTable" class="table table-striped table-bordered nowrap" style="width:100%">
                              <thead>
                                <tr>
                                  <th>School Year</th>
                                  <th>Semester</th>
                                  <th>Student ID</th>
                                  <th>Email</th>
                                  <th>First Name</th>
                                  <th>Middle Name</th>
                                  <th>Last Name</th>
                                  <th>Year Level</th>
                                  <th>Course</th>
                                  <th>Department</th>
                                  <th>Major</th>
                                  <th>College</th>
                                  <th>Elections Participated</th>
                                  <th>Precinct</th>
                                  <th>Status</th>
                                  <th>Vote Status</th>
                                  <th>Archived On</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($voters as $voter):
                                  $vpId = $voter['voting_period_id'];
                                  $election = $electionDetailsMap[$vpId] ?? null;
                                  $sy = 'N/A';
                                  if ($election) {
                                    if (!empty($election['year_label'])) {
                                      $sy = $election['year_label'];
                                    } else {
                                      $sy = date('Y', strtotime($election['school_year_start'])) . '-' . date('Y', strtotime($election['school_year_end']));
                                    }
                                  }
                                  $sem = $election ? (!empty($election['academic_semester']) ? $election['academic_semester'] : $election['semester']) : 'N/A';
                                ?>
                                  <tr>
                                    <td><?= htmlspecialchars($sy) ?></td>
                                    <td><?= htmlspecialchars($sem) ?></td>
                                    <td><?= htmlspecialchars($voter['student_id']) ?></td>
                                    <td><?= htmlspecialchars($voter['email']) ?></td>
                                    <td><?= htmlspecialchars($voter['first_name']) ?></td>
                                    <td><?= htmlspecialchars($voter['middle_name']) ?></td>
                                    <td><?= htmlspecialchars($voter['last_name']) ?></td>

                                    <td>
                                      <?php
                                      $yearMapping = [
                                        1 => '1st Year',
                                        2 => '2nd Year',
                                        3 => '3rd Year',
                                        4 => '4th Year',
                                        5 => '5th Year'
                                      ];

                                      $yearLevel = (int)$voter['actual_year_level']; // convert to integer
                                      echo isset($yearMapping[$yearLevel]) ? $yearMapping[$yearLevel] : htmlspecialchars($voter['actual_year_level']);
                                      ?>
                                    </td>
                                    <td><?= htmlspecialchars($voter['course_name']) ?></td>
                                    <td><?= htmlspecialchars($voter['department_name']) ?></td>
                                    <td>
                                      <?= !empty($voter['major_name']) ? htmlspecialchars($voter['major_name']) : 'N/A' ?>
                                    </td>
                                    <td><?= htmlspecialchars($voter['college_name']) ?></td>
                                    <td><?= htmlspecialchars($voter['election_name']) ?></td>
                                    <td><?= htmlspecialchars($voter['precinct_name']) ?></td>
                                    <td><?= htmlspecialchars($voter['status']) ?></td>
                                    <td>
                                      <?php if ($voter['has_voted']): ?>
                                        <span class="badge bg-success">Voted</span>
                                      <?php else: ?>
                                        <span class="badge bg-warning">Not Voted</span>
                                      <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($voter['archived_on']) ?></td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Archived Positions Card -->
                    <div class="card card-rounded mb-5">
                      <div class="card-body">
                        <div class="row mt-4">
                          <div class="container mb-5">
                            <div class="d-flex align-items-center">
                              <h3><b>Archived Positions</b></h3>
                            </div>
                            <div class="row mt-3">
                              <div class="col-md-6">
                                <label>Select Election Name</label>
                                <select id="positionElectionFilter" class="form-select">
                                  <option value="">All Elections</option>
                                  <?php foreach ($uniqueElections as $ename): ?>
                                    <option value="<?= htmlspecialchars($ename) ?>"><?= htmlspecialchars($ename) ?></option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                            </div>
                          </div>
                          <div class="table-responsive">
                            <table id="archivedPositionsTable" class="table table-striped table-bordered nowrap" style="width:100%">
                              <thead>
                                <tr>
                                  <th>ID</th>
                                  <th>School Year</th>
                                  <th>Semester</th>
                                  <th>Name</th>
                                  <th>Party</th>
                                  <th>Level</th>
                                  <th>Election Name</th>
                                  <th>Created At</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($archivedPositions as $pos):
                                  if (!empty($pos['year_label'])) {
                                    $sy = $pos['year_label'];
                                  } else {
                                    $sy = date('Y', strtotime($pos['school_year_start'])) . '-' . date('Y', strtotime($pos['school_year_end']));
                                  }
                                  $sem = !empty($pos['academic_semester']) ? $pos['academic_semester'] : $pos['semester'];
                                ?>
                                  <tr>
                                    <td><?= htmlspecialchars($pos['id']) ?></td>
                                    <td><?= htmlspecialchars($sy) ?></td>
                                    <td><?= htmlspecialchars($sem) ?></td>
                                    <td><?= htmlspecialchars($pos['name']) ?></td>
                                    <td><?= htmlspecialchars($pos['party']) ?></td>
                                    <td><?= htmlspecialchars($pos['level']) ?></td>
                                    <td><?= htmlspecialchars($pos['election_name']) ?></td>
                                    <td><?= htmlspecialchars($pos['created_at']) ?></td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Archived Parties Card -->
                    <div class="card card-rounded mb-5">
                      <div class="card-body">
                        <div class="row mt-4">
                          <div class="container mb-5">
                            <div class="d-flex align-items-center">
                              <h3><b>Archived Parties</b></h3>
                            </div>
                            <div class="row mt-3">
                              <div class="col-md-6">
                                <label>Select Election Name</label>
                                <select id="partyElectionFilter" class="form-select">
                                  <option value="">All Elections</option>
                                  <?php foreach ($uniqueElections as $ename): ?>
                                    <option value="<?= htmlspecialchars($ename) ?>"><?= htmlspecialchars($ename) ?></option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                            </div>
                          </div>
                          <div class="table-responsive">
                            <table id="archivedPartiesTable" class="table table-striped table-bordered nowrap" style="width:100%">
                              <thead>
                                <tr>
                                  <th>ID</th>
                                  <th>School Year</th>
                                  <th>Semester</th>
                                  <th>Name</th>
                                  <th>Election Name</th>

                                  <th>Archived On</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($archivedParties as $party):
                                  if (!empty($party['year_label'])) {
                                    $sy = $party['year_label'];
                                  } else {
                                    $sy = date('Y', strtotime($party['school_year_start'])) . '-' . date('Y', strtotime($party['school_year_end']));
                                  }
                                  $sem = !empty($party['academic_semester']) ? $party['academic_semester'] : $party['semester'];
                                ?>
                                  <tr>
                                    <td><?= htmlspecialchars($party['id']) ?></td>
                                    <td><?= htmlspecialchars($sy) ?></td>
                                    <td><?= htmlspecialchars($sem) ?></td>
                                    <td><?= htmlspecialchars($party['name']) ?></td>
                                    <td><?= htmlspecialchars($party['election_name']) ?></td>

                                    <td><?= htmlspecialchars($party['archived_on']) ?></td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="card card-rounded mb-5">
                      <div class="card-body">
                        <div class="row mt-4">
                          <div class="container mb-5">
                            <div class="d-flex align-items-center">
                              <h3><b>Archived Precincts</b></h3>
                            </div>
                          </div>
                          <div class="table-responsive">
                            <?php
                            $elections_query = "
SELECT DISTINCT 
    ae.id AS election_id, 
    ae.election_name, 
    p.election
FROM archived_precincts p
INNER JOIN archived_elections ae
    ON ae.id = p.election
WHERE ae.academic_year_id = :ayId
ORDER BY ae.archived_on DESC
";

                            $elections_stmt = $archived_pdo->prepare($elections_query);
                            $elections_stmt->execute(['ayId' => $ayId]);
                            $elections = $elections_stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($elections) > 0) {
                              foreach ($elections as $election) {
                                $election_name = $election['election'];
                                $actual_election_name = $election['election_name'];
                                echo "<h4 class='mt-1 mb-3'><b>Archived Election Name:</b> " . htmlspecialchars($actual_election_name) . "</h4>";

                                // Get precincts for this specific election
                                $query = "
SELECT 
    p.*,
    ac.college_name,                              -- ← added
    d.department_name,
    cam_type.campus_name   AS type_campus_name,
    cam_external.campus_name AS external_campus_name
FROM archived_precincts p
LEFT JOIN archived_colleges ac                    -- ← added
    ON ac.college_id = p.college
LEFT JOIN archived_departments d
    ON d.department_id = p.department
LEFT JOIN archived_campuses cam_type
    ON cam_type.campus_id = p.type
LEFT JOIN archived_campuses cam_external
    ON cam_external.campus_id = p.college_external
LEFT JOIN archived_elections ae
    ON ae.id = p.election
WHERE p.election = :election_name
  AND ae.academic_year_id = :ayId
ORDER BY p.id DESC
";

                                $stmt = $archived_pdo->prepare($query);
                                $stmt->execute([
                                  'election_name' => $election_name,
                                  'ayId' => $ayId
                                ]);

                                $archived_precincts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (count($archived_precincts) > 0) {
                                  echo '<div class="table-responsive"> 
                                <table class="table table-striped table-bordered w-100" id="precinctsTable">
                                <thead class="thead-dark text-center">
                                    <tr>
                                        <th>Precinct Name</th>
                                        <th>College</th>
                                        <th>Location</th>
                                        <th>Department</th>
                                        <th>Type</th>
                                        <th>Created At</th>
                                        <th>Archived At</th>
                                      
                                    </tr>
                                </thead>
                                <tbody>';


                                  foreach ($archived_precincts as $row) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['college_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['department_name']) . "</td>";

                                    // Handle precinct type + external campus
                                    $typeDisplay = !empty($row['type_campus_name']) ? $row['type_campus_name'] : 'N/A';
                                    if (!empty($row['external_campus_name'])) {
                                      $typeDisplay .= " (" . $row['external_campus_name'] . ")";
                                    }
                                    echo "<td>" . htmlspecialchars($typeDisplay) . "</td>";

                                    echo "<td>" . date('F j, Y g:i A', strtotime($row['created_at'])) . "</td>";
                                    echo "<td>" . date('F j, Y g:i A', strtotime($row['archived_at'])) . "</td>";
                                    echo "</tr>";
                                  }


                                  echo '</tbody></table></div>';
                                } else {
                                  echo "<p>No precincts found for this election.</p>";
                                }
                              }
                            } else {
                              echo "<h6 class='text-center'>No archived elections found.</h6>";
                            }
                            ?>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="card card-rounded mb-5">
                      <div class="card-body">
                        <div class="row mt-4">
                          <div class="container mb-5">
                            <div class="d-flex align-items-center">
                              <h3><b>Archived Candidacies</b></h3>
                            </div>
                          </div>
                          <div class="table-responsive">
                            <table id="partyTable" class="table table-striped table-bordered nowrap" style="width:100%">
                              <thead>
                                <tr>
                                  <th>ID</th>
                                  <th>Election Name</th>
                                  <th>School Year</th>
                                  <th>Semester</th>
                                  <th>Filing Start</th>
                                  <th>Filing End</th>
                                  <th>Candidates</th>
                                  <th>Total Filed</th>
                                  <th>Archived On</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($candidacyHistory as $candidacy): ?>
                                  <tr>
                                    <td><?php echo htmlspecialchars($candidacy['id']); ?></td>
                                    <td><?php echo htmlspecialchars($candidacy['election_name']); ?></td>
                                    <td><?php
                                        if (!empty($candidacy['year_label'])) {
                                          echo htmlspecialchars($candidacy['year_label']);
                                        } else {
                                          echo htmlspecialchars(date('Y', strtotime($candidacy['school_year_start'])) . '-' . date('Y', strtotime($candidacy['school_year_end'])));
                                        }
                                        ?></td>
                                    <td><?php
                                        $sem = !empty($candidacy['academic_semester']) ? $candidacy['academic_semester'] : $candidacy['semester'];
                                        echo htmlspecialchars($sem);
                                        ?></td>
                                    <td><?php echo htmlspecialchars($candidacy['start_period']); ?></td>
                                    <td><?php echo htmlspecialchars($candidacy['end_period']); ?></td>
                                    <td>
                                      <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewCandidatesModal<?php echo $candidacy['id']; ?>">
                                        <i class="mdi mdi-eye"></i> View Candidates
                                      </button>
                                    </td>
                                    <td><?php echo htmlspecialchars($candidacy['total_filed']); ?></td>
                                    <td><?php echo htmlspecialchars($candidacy['archived_on']); ?></td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
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

        <!-- Modals for Elections -->
        <?php foreach ($electionHistory as $election): ?>
          <?php
          $candidates = getCandidatesForElection($pdo, $election['voting_period_id']);
          $details = getAdditionalElectionDetails($pdo, $election['voting_period_id']);
          ?>

          <!-- View Voters Modal -->
          <div class="modal fade" id="viewVotersModal<?php echo $election['id']; ?>" tabindex="-1" aria-labelledby="viewVotersModalLabel<?php echo $election['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-xl">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="viewVotersModalLabel<?php echo $election['id']; ?>">Past Voters - <?php echo htmlspecialchars($election['election_name']); ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="table-responsive">
                    <table class="table table-striped table-bordered nowrap" style="width:100%">
                      <thead>
                        <tr>
                          <th>Student ID</th>
                          <th>Email</th>
                          <th>First Name</th>
                          <th>Middle Name</th>
                          <th>Last Name</th>
                          <th>Precinct</th>
                          <th>College</th>
                          <th>Course</th>
                          <th>Semester</th>
                          <th>Department</th>
                          <th>Major</th>
                          <th>Year Level</th>
                          <th>WMSU Campus</th>
                          <th>ESU Campus</th>
                          <th>Status</th>
                          <th>Vote Status</th>
                          <th>Archived On</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $votersForElection = $groupedVoters[$election['election_name']][$election['voting_period_id']] ?? [];
                        foreach ($votersForElection as $voter): ?>
                          <tr>
                            <td><?= htmlspecialchars($voter['student_id']) ?></td>
                            <td><?= htmlspecialchars($voter['email']) ?></td>
                            <td><?= htmlspecialchars($voter['first_name']) ?></td>
                            <td><?= htmlspecialchars($voter['middle_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($voter['last_name']) ?></td>
                            <td><?= htmlspecialchars($voter['precinct_name'] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($voter['college_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($voter['course_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($voter['semester'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($voter['department_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($voter['major_name'] ?? '-') ?></td>
                            <td>
                              <?php
                              $yearMapping = [
                                1 => '1st Year',
                                2 => '2nd Year',
                                3 => '3rd Year',
                                4 => '4th Year',
                                5 => '5th Year'
                              ];

                              $yearLevel = (int)$voter['actual_year_level']; // convert to integer
                              echo isset($yearMapping[$yearLevel]) ? $yearMapping[$yearLevel] : htmlspecialchars($voter['actual_year_level']);
                              ?>
                            </td>
                            <td><?= htmlspecialchars($voter['campus_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($voter['campus_name'] ?: 'None') ?></td>
                            <td>
                              <span class="badge bg-<?= strtolower($voter['status']) === 'confirmed' ? 'success' : 'warning' ?>">
                                <?= strtoupper(htmlspecialchars($voter['status'])) ?>
                              </span>
                            </td>
                            <td>
                              <span class="badge bg-<?= $voter['has_voted'] ? 'success' : 'warning' ?>">
                                <?= $voter['has_voted'] ? 'VOTED' : 'NOT VOTED' ?>
                              </span>
                            </td>
                            <td><?= date('F j, Y g:i A', strtotime($voter['archived_on'])) ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>

          <!-- View Winners Modal -->
          <div class="modal fade" id="viewWinnersModal<?php echo $election['id']; ?>" tabindex="-1" aria-labelledby="viewWinnersModalLabel<?php echo $election['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="viewWinnersModalLabel<?php echo $election['id']; ?>">Winners - <?php echo htmlspecialchars($election['election_name']); ?> (<?php echo htmlspecialchars($election['semester'] . ', ' . $election['year_label']) ?>)</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th>Position</th>
                        <th>Winner</th>
                        <th>Party</th>
                        <th>Votes Received</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Calculate winners dynamically based on votes
                      $maxVotesByPosition = [];
                      foreach ($candidates as $candidate) {
                        $pos = $candidate['position'];
                        $votes = $candidate['votes_received'];
                        if (!isset($maxVotesByPosition[$pos]) || $votes > $maxVotesByPosition[$pos]) {
                          $maxVotesByPosition[$pos] = $votes;
                        }
                      }

                      foreach ($candidates as $candidate):
                        $pos = $candidate['position'];
                        if ($candidate['votes_received'] == $maxVotesByPosition[$pos] && $maxVotesByPosition[$pos] > 0):
                      ?>
                          <tr>
                            <td><?php echo htmlspecialchars($candidate['position']); ?></td>
                            <td><?php echo htmlspecialchars($candidate['candidate_name']); ?></td>
                            <td><?php echo htmlspecialchars($candidate['party'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($candidate['votes_received']); ?></td>
                          </tr>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>

          <!-- View Election Record Modal -->
          <div class="modal fade" id="viewElectionModal<?php echo $election['id']; ?>" tabindex="-1" aria-labelledby="viewElectionModalLabel<?php echo $election['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="viewElectionModalLabel<?php echo $election['id']; ?>">Election Record - <?php echo htmlspecialchars($election['election_name']); ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <dl class="row">
                    <dt class="col-sm-4">Election Name</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($election['election_name']); ?></dd>
                    <dt class="col-sm-4">Semester & Year</dt>
                    <dd class="col-sm-8"><?php
                                          $sy = !empty($election['year_label']) ? $election['year_label'] : $election['school_year_start'] . '-' . $election['school_year_end'];
                                          $sem = !empty($election['academic_semester']) ? $election['academic_semester'] : $election['semester'];
                                          echo htmlspecialchars($sem . ', ' . $sy);
                                          ?></dd>

                    <dt class="col-sm-4">Candidacy Period</dt>
                    <dd class="col-sm-8">
                      <?php
                      if ($details['candidacy_start'] && $details['candidacy_end']) {
                        echo date('M j, Y g:i A', strtotime($details['candidacy_start'])) . ' - ' . date('M j, Y g:i A', strtotime($details['candidacy_end']));
                      } else {
                        echo 'N/A';
                      }
                      ?>
                    </dd>

                    <dt class="col-sm-4">Voting Period</dt>
                    <dd class="col-sm-8">
                      <?php
                      if ($details['voting_start'] && $details['voting_end']) {
                        echo date('M j, Y g:i A', strtotime($details['voting_start'])) . ' - ' . date('M j, Y g:i A', strtotime($details['voting_end']));
                      } else {
                        echo 'N/A';
                      }
                      ?>
                    </dd>

                    <dt class="col-sm-4">Total Registered Voters</dt>
                    <dd class="col-sm-8"><?php echo number_format($details['total_registered']); ?></dd>
                    <dt class="col-sm-4">Voted</dt>
                    <dd class="col-sm-8"><?php echo number_format($details['voted']); ?></dd>
                    <dt class="col-sm-4">Not Voted</dt>
                    <dd class="col-sm-8"><?php echo number_format($details['not_voted']); ?></dd>
                    <dt class="col-sm-4">Total Candidates</dt>
                    <dd class="col-sm-8"><?php echo number_format($details['total_candidates']); ?></dd>


                    <dt class="col-sm-4">Turnout</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($election['turnout']); ?></dd>
                    <dt class="col-sm-4">Archived On</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($election['archived_on']); ?></dd>
                  </dl>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- Modals for Candidacies -->
        <?php foreach ($candidacyHistory as $candidacy): ?>
          <?php $candidates = getCandidatesForElection($pdo, $candidacy['voting_period_id']); ?>

          <div class="modal fade" id="viewCandidatesModal<?php echo $candidacy['id']; ?>" tabindex="-1" aria-labelledby="viewCandidatesModalLabel<?php echo $candidacy['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="viewCandidatesModalLabel<?php echo $candidacy['id']; ?>">Candidates - <?php echo htmlspecialchars($candidacy['election_name']); ?> (<?php
                                                                                                                                                                                  $sy = !empty($candidacy['year_label']) ? $candidacy['year_label'] : $candidacy['school_year_start'] . '-' . $candidacy['school_year_end'];
                                                                                                                                                                                  $sem = !empty($candidacy['academic_semester']) ? $candidacy['academic_semester'] : $candidacy['semester'];
                                                                                                                                                                                  echo htmlspecialchars($sem . ', ' . $sy);
                                                                                                                                                                                  ?>)</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-3">
                    <p><strong>School Year:</strong> <?php echo htmlspecialchars(!empty($candidacy['year_label']) ? $candidacy['year_label'] : $candidacy['school_year_start'] . '-' . $candidacy['school_year_end']); ?></p>
                    <p><strong>Semester:</strong> <?php echo htmlspecialchars(!empty($candidacy['academic_semester']) ? $candidacy['academic_semester'] : $candidacy['semester']); ?></p>
                    <p><strong>Election Name:</strong> <?php echo htmlspecialchars($candidacy['election_name']); ?></p>
                    <p><strong>Total Candidates Filed:</strong> <?php echo htmlspecialchars($candidacy['total_filed']); ?></p>
                  </div>
                  <?php
                  // Group candidates by position
                  $groupedCandidates = [];
                  foreach ($candidates as $candidate) {
                    $position = $candidate['position'];
                    if (!isset($groupedCandidates[$position])) {
                      $groupedCandidates[$position] = [];
                    }
                    $groupedCandidates[$position][] = $candidate;
                  }
                  ?>

                  <?php
                  // Calculate max votes per position for outcome determination
                  $maxVotesPerPos = [];
                  foreach ($groupedCandidates as $pos => $cands) {
                    $votes = array_column($cands, 'votes_received');
                    $maxVotesPerPos[$pos] = !empty($votes) ? max($votes) : 0;
                  }
                  ?>

                  <?php foreach ($groupedCandidates as $position => $positionCandidates): ?>
                    <div class="position-group mb-4">
                      <h5 class="position-title bg-light p-2 rounded"><?php echo htmlspecialchars($position); ?></h5>
                      <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                          <thead class="table-light">
                            <tr>
                              <th>Student ID</th>
                              <th>Full Name</th>
                              <th>Course</th>
                              <th>College</th>
                              <th>Dept</th>
                              <th>Year Level</th>
                              <th>Campus</th>
                              <th>Party</th>
                              <th>Outcome</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($positionCandidates as $candidate): ?>
                              <tr>
                                <td><?php echo htmlspecialchars($candidate['student_id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($candidate['candidate_name'] ?? 'N/A'); ?></td>

                                <td><?php echo htmlspecialchars($candidate['course_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($candidate['college_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($candidate['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($candidate['actual_year_level'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($candidate['campus_name'] ?? $candidate['external_campus'] ?? 'N/A') ?></td>
                                <td><?php echo htmlspecialchars($candidate['party'] ?? 'N/A'); ?></td>
                                <td>
                                  <?php
                                  $isWinner = ($candidate['votes_received'] == $maxVotesPerPos[$position] && $maxVotesPerPos[$position] > 0);
                                  echo $isWinner ? '<span class="badge bg-success">Won</span>' : '<span class="badge bg-danger">Lost</span>';
                                  ?>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Scripts -->
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
      <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
      <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
      <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
      <script>
        $(document).ready(function() {
          $('table').each(function() {
            $(this).DataTable({
              responsive: true,
              pageLength: 10,
              language: {
                search: "Filter records:"
              },
              order: [
                [0, 'desc']
              ]
            });
          });

          // Archived Voters Table with Filters
          var votersTable = $('#archivedVotersTable').DataTable();

          $('#voterElectionFilter').on('change', function() {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            votersTable.column(12).search(val ? '^' + val + '$' : '', true, false).draw();
          });

          $('#voterSYFilter').on('change', function() {

            var val = $(this).val();

            if (val === "") {
              votersTable.column(0).search('').column(1).search('').draw();
              return;
            }

            var parts = val.split('|');

            var schoolYear = $.trim(parts[0]);
            var semester = $.trim(parts[1]);

            votersTable
              .column(0).search('^' + $.fn.dataTable.util.escapeRegex(schoolYear) + '$', true, false)
              .column(1).search('^' + $.fn.dataTable.util.escapeRegex(semester) + '$', true, false)
              .draw();
          });


          $('#positionElectionFilter').on('change', function() {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            positionsTable.column(4).search(val ? '^' + val + '$' : '', true, false).draw();
          });



          $('#partyElectionFilter').on('change', function() {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            partiesTable.column(2).search(val ? '^' + val + '$' : '', true, false).draw();
          });
        });
      </script>

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

      <!-- View Winners Modal -->
      <div class="modal fade" id="viewWinnersModal1" tabindex="-1" aria-labelledby="viewWinnersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="viewWinnersModalLabel">Winners - Student Council Elections (1st Sem, 2024-2025)</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>Position</th>
                    <th>Winner</th>
                    <th>Party</th>
                    <th>Votes Received</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>President</td>
                    <td>John Doe</td>
                    <td>USP</td>
                    <td>850</td>
                  </tr>
                  <tr>
                    <td>Vice President</td>
                    <td>Jane Smith</td>
                    <td>BUKLOD</td>
                    <td>790</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>

      <!-- View Election Record Modal -->
      <div class="modal fade" id="viewElectionModal1" tabindex="-1" aria-labelledby="viewElectionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="viewElectionModalLabel">Election Record - Student Council Elections</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <dl class="row">
                <dt class="col-sm-4">Election Name</dt>
                <dd class="col-sm-8">Student Council Elections</dd>
                <dt class="col-sm-4">Semester & Year</dt>
                <dd class="col-sm-8">1st Sem, 2024-2025</dd>
                <dt class="col-sm-4">Start Date</dt>
                <dd class="col-sm-8">Sep 1, 2024 7:00 AM</dd>
                <dt class="col-sm-4">End Date</dt>
                <dd class="col-sm-8">Sep 10, 2024 4:00 PM</dd>
                <dt class="col-sm-4">Total Voters</dt>
                <dd class="col-sm-8">1,580</dd>
                <dt class="col-sm-4">Votes Cast</dt>
                <dd class="col-sm-8">1,234 (78%)</dd>
                <dt class="col-sm-4">Archived On</dt>
                <dd class="col-sm-8">Mar 28, 2025</dd>
              </dl>
              <h6>Additional Notes</h6>
              <p>No irregularities reported. Election certified on Sep 15, 2024.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>

      <!-- View Candidates Modal -->
      <div class="modal fade" id="viewCandidatesModal1" tabindex="-1" aria-labelledby="viewCandidatesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="viewCandidatesModalLabel">Candidates - Student Council Elections (1st Sem, 2024-2025)</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Party</th>
                    <th>Filed On</th>
                    <th>Outcome</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>John Doe</td>
                    <td>President</td>
                    <td>USP</td>
                    <td>Aug 5, 2024</td>
                    <td>Won</td>
                  </tr>
                  <tr>
                    <td>Jane Smith</td>
                    <td>Vice President</td>
                    <td>BUKLOD</td>
                    <td>Aug 6, 2024</td>
                    <td>Won</td>
                  </tr>
                  <tr>
                    <td>Mike Johnson</td>
                    <td>President</td>
                    <td>BUKLOD</td>
                    <td>Aug 7, 2024</td>
                    <td>Lost</td>
                  </tr>
                </tbody>
              </table>
              <p><strong>Total Candidates Filed:</strong> 12</p>
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

</body>

</html>