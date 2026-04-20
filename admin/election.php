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
  <title>WMSU i-Elect Admin | Elections </title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="vendors/typicons/typicons.css">
  <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">

  <!-- inject:css -->
  <link rel="stylesheet" href="css/vertical-layout-light/style.css">
  <!-- endinject -->
  <link rel="shortcut icon" href="images/favicon.png" />

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
    <nav class="navbar default-layout col-lg-12 col-12 p-0 d-flex align-items-top flex-row">
      <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
        <div class="me-3">
          <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
            <span class="icon-menu"></span>
          </button>
        </div>
        <div>
          <a class="navbar-brand brand-logo" href="index.html">

            <img src="images/wmsu-logo.png" alt="logo" class="logo img-fluid" />
            <small style="font-size: 16px;"><b>WMSU i-Elect</b></small>
          </a>

          <a class="navbar-brand brand-logo-mini" href="index.html">
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
                        aria-controls="overview" aria-selected="true">Election</a>
                    </li>
                  </ul>
                </div>
                <div class="tab-content tab-content-basic">
                  <div class="tab-content tab-content-basic">
                    <div class="tab-pane fade show active" id="overview" role="tabpanel">
                      <div class="card card-rounded mb-5">
                        <div class="card-body">
                          <div class="d-flex align-items-center mb-3">
                            <h3 class="mb-0"><b>Election Periods</b> <small style="font-size: 1rem"></h3></small>
                            <div class="ms-auto d-flex">
                              <button class="btn btn-primary text-white" data-bs-toggle="modal"
                                data-bs-target="#electionModal">
                                <small><i class="mdi mdi-plus-circle"></i> Add Election Period</small>
                              </button>
                              <?php
                              /* ============================
   1. Fetch ALL academic years that have elections
   ============================ */
                              $ayQuery = $pdo->query("
   SELECT
    a.id,
    a.year_label,
    a.semester
FROM academic_years a
WHERE a.status = 'Ongoing'
ORDER BY a.year_label DESC, a.semester DESC;
");
                              $academicYears = $ayQuery->fetchAll(PDO::FETCH_ASSOC);

                              /* ============================
   2. Fetch ONGOING academic year(s)
   ============================ */
                              $ongoingQuery = $pdo->prepare("
    SELECT DISTINCT
        a.id,
        a.year_label,
        a.semester
    FROM academic_years a
    JOIN elections e
        ON e.academic_year_id = a.id
    WHERE e.status = 'Ongoing'
    ORDER BY a.year_label DESC, a.semester DESC
");
                              $ongoingQuery->execute();
                              $ongoingAYs = $ongoingQuery->fetchAll(PDO::FETCH_ASSOC);

                              /* ============================
   3. Build Ongoing display text
   ============================ */
                              $ongoingContext = '';
                              $ongoingIds = [];

                              if (!empty($ongoingAYs)) {
                                foreach ($ongoingAYs as $row) {
                                  $ongoingIds[] = $row['id'];
                                  $labels[] = $row['year_label'] . ' – ' . $row['semester'];
                                }

                                $ongoingContext = ' (' .
                                  implode(', ', array_slice($labels, 0, 2)) .
                                  (count($labels) > 2 ? ', ...' : '') .
                                  ')';
                              }
                              ?>



                              <small>
                                <select id="semesterFilter" class="form-select w-auto ms-auto">
                                  <option value="all">All Semesters</option>

                                  <?php if (!empty($ongoingAYs)): ?>
                                    <option value="ongoing">
                                      Ongoing<?= htmlspecialchars($ongoingContext) ?>
                                    </option>
                                  <?php endif; ?>

                                  <?php foreach ($academicYears as $ay): ?>
                                    <?php if (in_array($ay['id'], $ongoingIds)) continue; ?>
                                    <option value="<?= (int)$ay['id'] ?>">
                                      <?= htmlspecialchars($ay['year_label']) ?>
                                      – <?= htmlspecialchars($ay['semester']) ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>

                              </small>
                              <small>
                                <select id="statusFilter" class="form-select w-auto ms-2 d-inline-block">
                                  <option value="">All Statuses</option>
                                  <option value="Ongoing">Ongoing</option>
                                  <option value="Upcoming">Upcoming</option>
                                  <option value="Scheduled">Scheduled</option>
                                  <option value="Ended">Ended</option>
                                  <option value="Published">Published</option>
                                </select>
                              </small>
                            </div>



                          </div>
                          <div class="table-responsive">
                            <table id="electionTable" class="table table-striped table-bordered nowrap" style="width:100%">
                              <thead>
                                <tr>
                                  <th>School Year</th>
                                  <th>Semester</th>
                                  <th>Name</th>
                                  <th>Start Period</th>
                                  <th>End Period</th>
                                  <th>Status</th>
                                  <th>Action</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                try {

                                  $currentDateTime = new DateTime();

                                  $query = "
        SELECT 
            e.*, 
            a.year_label, 
            a.semester AS ay_semester
        FROM elections e
        JOIN academic_years a 
            ON e.academic_year_id = a.id
        WHERE e.status IN ('Ongoing','Upcoming','Scheduled','TBD')
        ORDER BY e.start_period DESC
    ";

                                  $stmt = $pdo->prepare($query);
                                  $stmt->execute();

                                  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                    $status = $row['status'];

                                    /* ---------------------------
           Auto update election status
        --------------------------- */

                                    //                     if ($status !== 'TBD' && !empty($row['end_period'])) {

                                    //                       $endPeriodObj = new DateTime($row['end_period']);

                                    //                       if ($currentDateTime > $endPeriodObj && $status !== 'Ended') {

                                    //                         $update = $pdo->prepare("
                                    //     UPDATE elections 
                                    //     SET status = 'Ended' 
                                    //     WHERE id = :id
                                    // ");

                                    //                         $update->execute([
                                    //                           ':id' => $row['id']
                                    //                         ]);

                                    //                         $status = 'Ended';
                                    //                       }
                                    //                     }

                                    /* ---------------------------
           Format Dates
        --------------------------- */

                                    $startPeriod = 'N/A';
                                    $endPeriod = 'N/A';
                                    $startPeriodRaw = '';
                                    $endPeriodRaw = '';

                                    if (!empty($row['start_period'])) {
                                      $startObj = new DateTime($row['start_period']);
                                      $startPeriod = $startObj->format('M j, Y \a\t g:i A');
                                      $startPeriodRaw = $startObj->format('Y-m-d\TH:i');
                                    }

                                    if (!empty($row['end_period'])) {
                                      $endObj = new DateTime($row['end_period']);
                                      $endPeriod = $endObj->format('M j, Y \a\t g:i A');
                                      $endPeriodRaw = $endObj->format('Y-m-d\TH:i');
                                    }

                                    /* ---------------------------
           Badge Colors
        --------------------------- */

                                    $badgeClass = match ($status) {
                                      'Ongoing'   => 'bg-success',
                                      'Upcoming'  => 'bg-warning',
                                      'Ended'     => 'bg-danger',
                                      'Published' => 'bg-info',
                                      'TBD'       => 'bg-dark',
                                      default     => 'bg-secondary'
                                    };

                                    echo "<tr>";

                                    echo "<td>" . htmlspecialchars($row['year_label']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['ay_semester']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['election_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($startPeriod) . "</td>";
                                    echo "<td>" . htmlspecialchars($endPeriod) . "</td>";
                                    echo "<td><span class='badge $badgeClass'>" . htmlspecialchars($status) . "</span></td>";

                                    echo "<td>";

                                    /* ---------------------------
           Action Buttons
        --------------------------- */

                                    if (in_array($status, ['Upcoming', 'Ongoing'])) {

                                      echo "
            <button 
                class='btn btn-warning btn-sm edit-election text-white'
                data-bs-toggle='modal'
                data-bs-target='#editElectionModal'
                data-id='{$row['id']}'
                data-election-name='" . htmlspecialchars($row['election_name']) . "'
                data-academic-year-id='{$row['academic_year_id']}'
                data-start-period='{$startPeriodRaw}'
                data-end-period='{$endPeriodRaw}'
                data-status='{$status}'
            >
                <i class='mdi mdi-pencil'></i> Edit
            </button>

            <button 
                class='btn btn-danger btn-sm delete-election text-white'
                data-id='{$row['id']}'
                data-election='" . htmlspecialchars($row['election_name']) . "'
            >
                <i class='mdi mdi-delete'></i> Delete
            </button>
            ";
                                    } elseif ($status === 'TBD') {

                                      echo "
 

    <button 
        class='btn btn-danger btn-sm delete-election text-white'
        data-id='{$row['id']}'
        data-election='" . htmlspecialchars($row['election_name']) . "'
    >
        <i class='mdi mdi-delete'></i> Delete
    </button>
    ";
                                    } else {

                                      echo "
    <a href='history.php?id={$row['id']}' 
       class='btn btn-info btn-sm'>
       <i class='mdi mdi-poll'></i> View Results
    </a>

    <button 
        class='btn btn-danger btn-sm delete-election'
        data-id='{$row['id']}'
        data-election='" . htmlspecialchars($row['election_name']) . "'
    >
        <i class='mdi mdi-delete'></i> Delete
    </button>
    ";
                                    }

                                    echo "</td>";
                                    echo "</tr>";
                                  }
                                } catch (PDOException $e) {

                                  echo "
    <tr>
        <td colspan='7' class='text-center text-danger'>
            Error: " . htmlspecialchars($e->getMessage()) . "
        </td>
    </tr>
    ";
                                }
                                ?>
                              </tbody>
                            </table>

                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="card card-rounded mb-5">
                    <div class="card-body">
                      <div class="row mt-4">
                        <?php
                        // Fetch distinct election names for filters
                        try {
                          $stmtFilter = $pdo->prepare("SELECT DISTINCT election_name FROM elections WHERE status IN ('Scheduled', 'Ongoing', 'Upcoming', 'TBD') ORDER BY election_name");
                          $stmtFilter->execute();
                          $filterElections = $stmtFilter->fetchAll(PDO::FETCH_COLUMN);
                        } catch (PDOException $e) {
                          $filterElections = [];
                        }
                        ?>
                        <div class="container  mb-5">
                          <div class="d-flex align-items-center flex-wrap gap-2">
                            <h3><b>Registered Parties</b><small style="font-size: 1rem"> </h3></small>
                            <div class="ms-auto d-flex align-items-center flex-wrap gap-2">
                              <select id="partyElectionFilter" class="form-select w-auto">
                                <option value="">All Elections</option>
                                <?php foreach ($filterElections as $electionName): ?>
                                  <option value="<?= htmlspecialchars($electionName) ?>"><?= htmlspecialchars($electionName) ?></option>
                                <?php endforeach; ?>
                              </select>
                              <select id="partyStatusFilter" class="form-select w-auto">
                                <option value="">All Statuses</option>
                                <option value="Approved">Approved</option>
                                <option value="Published">Published</option>
                                <option value="Disapproved">Disapproved</option>
                                <option value="Unverified">Unverified</option>
                              </select>
                              <button class="btn btn-primary text-white" data-bs-toggle="modal" data-bs-target="#partyModal">
                                <small><i class="mdi mdi-plus-circle"></i> Add a New Party</small>
                              </button>
                            </div>
                          </div>
                        </div>
                        <?php
                        try {
                          $stmt = $pdo->prepare("
        SELECT DISTINCT
            p.*,
            e.election_name,
            a.year_label,
            a.semester
        FROM parties p
        INNER JOIN elections e
            ON p.election_id = e.id
        INNER JOIN academic_years a
            ON e.academic_year_id = a.id
        WHERE e.status IN ('Scheduled', 'Ongoing', 'Upcoming')
        ORDER BY a.year_label DESC, a.semester DESC, p.name ASC
    ");

                          $stmt->execute();
                          $parties = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                          // optional logging
                        }
                        ?>

                        <table id="partyTable" class="table table-striped table-bordered nowrap" style="width:100%">
                          <thead>
                            <tr>
                              <th>School Year</th>
                              <th>Semester</th>
                              <th>Name</th>
                              <th>Election Under</th>
                              <th>Platforms</th>
                              <th>Status</th>
                              <th>Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (!empty($parties)): ?>
                              <?php foreach ($parties as $party): ?>
                                <tr>
                                  <!-- School Year -->
                                  <td><?= htmlspecialchars($party['year_label']) ?></td>

                                  <!-- Semester -->
                                  <td><?= htmlspecialchars($party['semester']) ?></td>

                                  <!-- Party Name -->
                                  <td><?= htmlspecialchars($party['name']) ?></td>

                                  <!-- Election Under -->
                                  <td><?= htmlspecialchars($party['election_name']) ?></td>

                                  <!-- Platforms -->
                                  <td>
                                    <?php if (!empty($party['platforms'])): ?>
                                      <button class="btn btn-sm viewPlatformsBtn"
                                        data-id="<?= $party['id'] ?>"
                                        data-platforms="<?= htmlspecialchars($party['platforms'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewPlatformsModal">
                                        <i class="mdi mdi-eye me-1"></i> View Platforms
                                      </button>
                                    <?php else: ?>
                                      <span class="text-muted">No platforms specified</span>
                                    <?php endif; ?>
                                  </td>

                                  <!-- Status -->
                                  <td>
                                    <?php
                                    $status = strtolower($party['status']);
                                    switch ($status) {
                                      case 'approved':
                                        $badge = 'bg-success';
                                        break;
                                      case 'disapproved':
                                        $badge = 'bg-danger';
                                        break;
                                      case 'unverified':
                                        $badge = 'bg-secondary text-black';
                                        break;
                                      case 'published':
                                        $badge = 'bg-primary';
                                        break;
                                      default:
                                        $badge = 'bg-warning text-black';
                                    }
                                    ?>
                                    <span class="badge <?= $badge ?>">
                                      <?= ucfirst($status) ?>
                                    </span>
                                  </td>

                                  <!-- Action -->
                                  <td>
                                    <?php if ($status === 'published'): ?>
                                      <a href="history.php" class="button-view-primary">
                                        <i class="mdi mdi-poll"></i> View Results
                                      </a>
                                    <?php else: ?>
                                      <button class="button-view-warning editBtnParty"
                                        data-id="<?= $party['id'] ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editPartyModal">
                                        <i class="mdi mdi-pencil"></i> Edit
                                      </button>

                                      <button class="button-view-danger deleteBtnParty"
                                        data-id="<?= $party['id'] ?>">
                                        <i class="mdi mdi-delete"></i> Delete
                                      </button>
                                    <?php endif; ?>
                                  </td>
                                </tr>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          </tbody>

                        </table>

                        <!-- Platforms View Modal -->
                        <div class="modal fade" id="viewPlatformsModal" tabindex="-1" aria-labelledby="viewPlatformsModalLabel" aria-hidden="true">
                          <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h1 class="modal-title fs-5" id="viewPlatformsModalLabel">Party Platforms</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                <div id="platformsContent"></div>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                  <i class="mdi mdi-close me-1"></i> Close
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>

                        <script>
                          document.addEventListener('DOMContentLoaded', function() {
                            // Handle View Platforms button click
                            document.querySelectorAll('.viewPlatformsBtn').forEach(button => {
                              button.addEventListener('click', function() {
                                const platforms = this.getAttribute('data-platforms');
                                const partyId = this.getAttribute('data-id');

                                // Set modal content
                                const platformsContent = document.getElementById('platformsContent');
                                platformsContent.innerHTML = platforms || '<p class="text-muted">No platforms specified.</p>';

                                // Update modal title with party name (optional, requires fetching party name)
                                const modalTitle = document.getElementById('viewPlatformsModalLabel');
                                modalTitle.textContent = `Party Platforms`;


                              });
                            });
                          });

                          document.querySelectorAll('.deleteBtnParty').forEach(button => {
                            button.addEventListener('click', function(event) {
                              event.preventDefault(); // Prevent the default click behavior (like form submission or link navigation)

                              const partyId = this.getAttribute('data-id');
                              const csrfToken = document.querySelector('input[name="csrf_token"]').value;
                              const buttonRef = this; // To access in inner scope

                              Swal.fire({
                                title: 'Are you sure?',
                                text: 'This will delete the party and all related data.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Yes, delete it!',
                                cancelButtonText: 'Cancel',
                                confirmButtonColor: '#e74c3c',
                                cancelButtonColor: '#6c757d'
                              }).then((result) => {
                                if (result.isConfirmed) {
                                  fetch('processes/elect/delete_party.php', {
                                      method: 'POST',
                                      headers: {
                                        'Content-Type': 'application/json'
                                      },
                                      body: JSON.stringify({
                                        party_id: partyId,
                                        csrf_token: csrfToken
                                      })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                      if (data.status === 'success') {
                                        Swal.fire({
                                          icon: 'success',
                                          title: 'Deleted!',
                                          text: data.message,
                                          timer: 2000,
                                          showConfirmButton: false
                                        });
                                        location.reload();
                                      } else {
                                        Swal.fire({
                                          icon: 'error',
                                          title: 'Error',
                                          text: data.message
                                        });
                                      }
                                    })
                                    .catch(error => {
                                      console.error('Error:', error);
                                      Swal.fire({
                                        icon: 'error',
                                        title: 'Request Failed',
                                        text: 'Failed to delete party. Please try again later.'
                                      });
                                    });
                                }
                              });
                            });
                          });
                        </script>

                      </div>
                    </div>
                  </div>

                  <div class="card card-rounded mb-5">
                    <div class="card-body">
                      <div class="row mt-4">
                        <div class="container  mb-5">
                          <div class="d-flex align-items-center flex-wrap gap-2">
                            <h3><b>Election Positions </b><small style="font-size: 1rem"></h3></small>
                            <div class="ms-auto d-flex align-items-center flex-wrap gap-2">
                              <select id="positionElectionFilter" class="form-select w-auto">
                                <option value="">All Elections</option>
                                <?php foreach ($filterElections as $electionName): ?>
                                  <option value="<?= htmlspecialchars($electionName) ?>"><?= htmlspecialchars($electionName) ?></option>
                                <?php endforeach; ?>
                              </select>
                              <select id="positionPartyFilter" class="form-select w-auto">
                                <option value="">All Parties</option>
                              </select>
                              <select id="positionLevelFilter" class="form-select w-auto">
                                <option value="">All Levels</option>
                                <option value="Central">Central</option>
                                <option value="Local">Local</option>
                              </select>
                              <select id="positionRestrictionFilter" class="form-select w-auto">
                                <option value="">All Restrictions</option>
                                <option value="any">Any College</option>
                                <option value="restricted">Restricted</option>
                              </select>
                              <button class="btn btn-primary text-white" data-bs-toggle="modal" data-bs-target="#positionModal">
                                <small><i class="mdi mdi-plus-circle"></i> Add a New Position</small>
                              </button>
                            </div>
                          </div>
                        </div>
                        <table id="positionTable" class="table table-striped table-bordered nowrap" style="width:100%">
                          <thead>
                            <tr>
                              <th>School Year</th>
                              <th>Semester</th>
                              <th>Name</th>
                              <th>Party</th>
                              <th>Level</th>
                              <th>Restrictions</th>
                              <th>Election Under</th>
                              <th>Action</th>

                            </tr>
                          </thead>
                          <tbody>
                            <!-- Data will be inserted here by JavaScript -->
                          </tbody>
                        </table>

                      </div>
                    </div>
                  </div>

                  <div>

                  </div>




                  <?php
                  require 'includes/conn.php'; // Include database connection

                  if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["updateElection"])) {
                    try {
                      $stmt = $pdo->prepare("UPDATE elections SET 
            election_name = :name, 
            school_year_start = :school_year_start,
            school_year_end = :school_year_end,
            semester = :semester, 
            start_period = :start_period, 
            end_period = :end_period, 
            status = :status 
            WHERE id = :id");

                      $stmt->execute([
                        ':id' => $_POST['election_id'],
                        ':name' => $_POST['election_name'],
                        ':school_year_start' => $_POST['school_year_start'],
                        ':school_year_end' => $_POST['school_year_end'],
                        ':semester' => $_POST['semester'],
                        ':start_period' => $_POST['start_period'],
                        ':end_period' => $_POST['end_period'],
                        ':status' => $_POST['status']
                      ]);

                      // Set success flag
                      echo "<script>localStorage.setItem('updateSuccess', 'true'); window.location.href='election.php';</script>";
                    } catch (PDOException $e) {
                      die("Error updating election: " . $e->getMessage());
                    }
                  }
                  ?>



                  <div class="modal fade" id="partyModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="exampleModalLabel">Add Party</h1>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <form id="partyForm" enctype="multipart/form-data" method="POST">
                            <?php

                            // Generate CSRF token if not already set
                            if (empty($_SESSION['csrf_token'])) {
                              $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                            }
                            ?>

                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <div class="mb-3">
                              <label for="partyName" class="form-label">Party Name:</label>
                              <input type="text" class="form-control" id="partyName" name="name" list="partyOptions" required>
                              <datalist id="partyOptions">
                                <option value="Independent"></option>
                              </datalist>
                            </div>

                            <?php
                            // Fetch election names from the database
                            $query = "SELECT id, election_name FROM elections WHERE status IN ('Ongoing')";
                            $stmt = $pdo->query($query);
                            $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>

                            <div class="mb-3">
                              <label for="election" class="form-label">Election:</label>
                              <select class="form-select" id="election" name="election" required>
                                <option value="">Select Election</option>
                                <?php foreach ($elections as $election): ?>
                                  <option value="<?= htmlspecialchars($election['id']); ?>">
                                    <?= htmlspecialchars($election['election_name']); ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                            </div>

                            <div class="mb-3">
                              <label for="party_image" class="form-label">Party Image:</label>
                              <input type="file" class="form-control" name="party_image" id="party_image" required accept="image/*">
                            </div>

                            <!-- Quill Editor for Platforms -->
                            <div class="mb-3">
                              <label for="platforms" class="form-label">Party Platforms:</label>
                              <div id="platforms-editor" style="height: 200px;"></div>
                              <input type="hidden" name="platforms" id="platforms">
                            </div>

                            <input type="hidden" id="status" name="status" value="Unverified">
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="mdi mdi-close"></i> Close
                              </button>
                              <button type="submit" class="btn btn-primary text-white">
                                <i class="mdi mdi-upload"></i> Save Changes
                              </button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Include Quill CSS -->
                  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
                  <!-- Include Quill JS -->
                  <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>



                  <div class="modal fade" id="positionModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="exampleModalLabel">New Position</h1>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <form id="positionFormAdd">



                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <!-- Position Name -->

                            <!-- Position Field (Initially dropdown for Central/Local) -->
                            <div class="mb-3">
                              <label for="position" class="form-label">New Position:</label>
                              <div id="positionFieldContainer">
                                <input type="text" class="form-control" id="position" name="position" required>
                              </div>
                              <br>
                              <button type="button" class="btn btn-link p-0 mt-1" id="customPositionBtn" style="display: none;">Enter custom position</button>
                            </div>

                            <div class="mb-3">
                              <label for="position" class="form-label">Level:</label>
                              <select class="form-control" name="level" id="level">
                                <option value="Central">Central</option>
                                <option value="Local">Local</option>
                              </select>
                            </div>
                            <!-- Parties (Checkboxes) -->
                            <div class="mb-3">
                              <label class="form-label">Parties:</label>
                              <div id="partyCheckboxes" class="checkbox-container">
                              </div>
                            </div>

                            <!-- ── College/Department Restrictions — LOCAL positions only ── -->
                            <div class="mb-3" id="collegeRestrictionWrapper" style="display:none;">
                              <label class="form-label fw-bold">
                                <i class="mdi mdi-school me-1"></i>College &amp; Department Restrictions
                                <small class="text-muted fw-normal ms-1">(Only students from selected colleges/departments may run for this position)</small>
                              </label>
                              <div class="alert alert-info py-2 mb-2" style="font-size:0.85rem;">
                                <i class="mdi mdi-information-outline me-1"></i>
                                Leave <b>all unchecked</b> to allow any college.
                                Check a college to restrict to it, then optionally expand and check specific departments within it.
                              </div>
                              <div id="collegeCheckboxesAdd" class="college-checkbox-container"></div>
                            </div>

                            <div class="modal-footer">

                              <button type="button" class="btn btn-secondary " data-bs-dismiss="modal">
                                <i class="mdi mdi-close"></i> Close
                              </button>

                              <button type="submit" class="btn btn-primary text-white">
                                <i class="mdi mdi-upload"></i> Save changes
                              </button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>

                  <style>
                    .checkbox-container {
                      max-height: 200px;
                      overflow-y: auto;
                      padding-left: 45px;
                      border: 1px solid #ced4da;
                      border-radius: 4px;
                    }

                    .form-check {
                      margin-bottom: 8px;
                    }
                  </style>




                  <div class="modal fade" id="electionModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="exampleModalLabel">Add Election Period</h1>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <?php
                          // Fetch academic years and semesters from the database
                          $academicYears = $pdo->query("SELECT id, year_label, semester, start_date, end_date
    FROM academic_years WHERE status = 'Ongoing'
    ORDER BY year_label DESC, semester ASC
")->fetchAll(PDO::FETCH_ASSOC);

                          // Prepare arrays for dropdowns
                          $schoolYears = [];
                          $semesters = [];


                          // Remove duplicates
                          $schoolYears = array_unique($schoolYears);
                          $semesters = array_unique($semesters);
                          ?>

                          <form id="electionForm">
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <!-- School Year -->
                            <div class="mb-3">
                              <label for="academicYear" class="form-label">Academic Year & Semester:</label>
                              <select class="form-select" id="academicYear" name="academic_year_id" required>
                                <option value="">Select Academic Year</option>
                                <?php foreach ($academicYears as $ay): ?>
                                  <option value="<?= $ay['id'] ?>" data-start-date="<?= $ay['start_date'] ?>" data-end-date="<?= $ay['end_date'] ?>">
                                    <?= htmlspecialchars($ay['year_label']) ?> - <?= htmlspecialchars($ay['semester']) ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                            </div>

                            <!-- Election Name -->
                            <div class="mb-3">
                              <label for="electionName" class="form-label">Election Name:</label>
                              <input type="text" class="form-control" id="electionName" name="election_name" required>
                            </div>

                            <!-- Duration Type -->
                            <div class="mb-3">
                              <label class="form-label">Duration Type:</label>
                              <div class="form-check">
                                <input class="form-check-input" type="radio" name="duration_type" id="durationStandard" value="standard" checked>
                                <label class="form-check-label" for="durationStandard">Standard (USC-Defined Duration) </label>
                              </div>
                              <div class="form-check">
                                <input class="form-check-input" type="radio" name="duration_type" id="durationCustom" value="custom">
                                <label class="form-check-label" for="durationCustom">Custom Duration</label>
                              </div>
                            </div>

                            <!-- Start / End Period -->
                            <div class="mb-3">
                              <label for="startPeriod" class="form-label">Start Period:</label>
                              <input type="datetime-local" class="form-control" id="startPeriod" name="start_period" required>
                            </div>

                            <div class="mb-3">
                              <label for="endPeriod" class="form-label">End Period: <small>(Usually 1 month and two weeks from now)</small></label>
                              <input type="datetime-local" class="form-control" id="endPeriod" name="end_period" required>
                            </div>

                            <script>
                              const durationStandard = document.getElementById("durationStandard");
                              const durationCustom = document.getElementById("durationCustom");
                              const startPeriod = document.getElementById("startPeriod");
                              const endPeriod = document.getElementById("endPeriod");

                              function updateEndDate() {
                                if (durationStandard.checked && startPeriod.value) {
                                  let startDate = new Date(startPeriod.value);

                                  // Add 1 month
                                  startDate.setMonth(startDate.getMonth() + 1);

                                  // Add 14 days
                                  startDate.setDate(startDate.getDate() + 14);

                                  // Format for datetime-local
                                  let formatted = startDate.toISOString().slice(0, 16);
                                  endPeriod.value = formatted;

                                  endPeriod.readOnly = false;
                                }
                              }

                              durationStandard.addEventListener("change", updateEndDate);
                              durationCustom.addEventListener("change", updateEndDate);
                              startPeriod.addEventListener("change", updateEndDate);
                            </script>

                            <div class="mb-3">
                              <label class="form-label">Status:</label>
                              <select class="form-select" name="status" id="editStatus" required>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Upcoming">Upcoming</option>

                              </select>
                            </div>

                            <!-- Modal Footer -->
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="mdi mdi-close"></i> Close
                              </button>
                              <button type="submit" class="btn btn-primary text-white">
                                <i class="mdi mdi-upload"></i> Save changes
                              </button>
                            </div>
                          </form>

                        </div>
                      </div>
                    </div>
                  </div>





                  <?php
                  require 'includes/conn.php'; // Include database connection

                  if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["updateElection"])) {
                    try {
                      $stmt = $pdo->prepare("UPDATE elections SET 
            election_name = :name, 
            school_year_start = :school_year_start,
            school_year_end = :school_year_end,
            semester = :semester, 
            start_period = :start_period, 
            end_period = :end_period, 
            status = :status 
            WHERE id = :id");

                      $stmt->execute([
                        ':id' => $_POST['election_id'],
                        ':name' => $_POST['election_name'],
                        ':school_year_start' => $_POST['school_year_start'],
                        ':school_year_end' => $_POST['school_year_end'],
                        ':semester' => $_POST['semester'],
                        ':start_period' => $_POST['start_period'],
                        ':end_period' => $_POST['end_period'],
                        ':status' => $_POST['status']
                      ]);

                      // Set success flag
                      echo "<script>localStorage.setItem('updateSuccess', 'true'); window.location.href='election.php';</script>";
                    } catch (PDOException $e) {
                      die("Error updating election: " . $e->getMessage());
                    }
                  }
                  ?>

                  <!-- Edit Party Modal -->
                  <div class="modal fade" id="editPartyModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="exampleModalLabel">Edit Party</h1>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <?php
                          // Fetch election names from the database
                          $query = "SELECT id, election_name FROM elections WHERE status IN ('Ongoing')";
                          $stmt = $pdo->query($query);
                          $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                          ?>
                          <form id="partyFormEditable" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                            <input type="hidden" id="editPartyId" name="id">
                            <div class="mb-3">
                              <label for="editPartyName" class="form-label">Party Name:</label>
                              <input type="text" class="form-control" id="editPartyName" name="name" required>
                            </div>

                            <div class="mb-3">
                              <label for="editElectionParty" class="form-label">Election:</label>
                              <select class="form-select" id="editElectionParty" name="election" required>
                                <option value="">Select Election</option>
                                <?php foreach ($elections as $election): ?>
                                  <option value="<?= htmlspecialchars($election['id']); ?>">
                                    <?= htmlspecialchars($election['election_name']); ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                            </div>

                            <div class="mb-3">
                              <label for="editPartyImage" class="form-label">Party Image</label>
                              <input type="file" class="form-control" name="editPartyImage" id="editPartyImage" onchange="previewImage(event)" accept="image/*">
                              <div id="imagePreviewContainer" class="text-center" style="margin-top: 10px; display: none;">
                                <img id="imagePreview" src="" alt="Party Image" style="max-width: 100%; max-height: 300px;" />
                              </div>
                            </div>

                            <div class="mb-3">
                              <label for="editStatus" class="form-label">Party Status:</label>
                              <select class="form-select" id="editStatus" name="status_party" required>
                                <option value="" disabled>Select Option</option>
                                <option value="Approved">Approved</option>
                                <option value="Published">Published</option>
                                <option value="Disapproved">Disapproved</option>
                                <option value="Unverified">Unverified</option>
                              </select>
                            </div>

                            <div class="mb-3">
                              <label for="editPlatforms" class="form-label">Party Platforms:</label>
                              <div id="edit-platforms-editor" style="height: 200px;"></div>
                              <input type="hidden" name="platforms" id="editPlatforms">
                            </div>

                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="mdi mdi-close"></i> Close
                              </button>
                              <button type="submit" class="btn btn-primary text-white">
                                <i class="mdi mdi-upload"></i> Save changes
                              </button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Include Quill CSS and JS -->
                  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
                  <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

                  <script>
                    document.addEventListener('DOMContentLoaded', function() {
                      // Initialize Quill editor for Platforms
                      const quill = new Quill('#edit-platforms-editor', {
                        theme: 'snow',
                        modules: {
                          toolbar: [
                            [{
                              'header': [1, 2, 3, false]
                            }],
                            ['bold', 'italic', 'underline'],
                            ['link'],
                            [{
                              'list': 'ordered'
                            }, {
                              'list': 'bullet'
                            }],
                            ['clean']
                          ]
                        }
                      });

                      // Handle Edit button click (from partyTable)
                      document.querySelectorAll('.editBtnParty').forEach(button => {
                        button.addEventListener('click', function() {
                          const partyId = this.getAttribute('data-id');

                          if (!partyId) {
                            Swal.fire({
                              title: 'Error!',
                              text: 'Party ID is missing. Please try again.',
                              icon: 'error',
                              timer: 2000,
                              showConfirmButton: false
                            });
                            return;
                          }

                          // Fetch party details using AJAX
                          fetch(`processes/elect/get_party.php?id=${partyId}`)
                            .then(response => response.json())
                            .then(data => {
                              if (data.fetch_status === 'success') {
                                // Populate form fields
                                document.getElementById('editPartyId').value = data.party.id;
                                document.getElementById('editPartyName').value = data.party.name;
                                document.getElementById('editStatus').value = data.party.status;

                                // Set election
                                const electionDropdown = document.getElementById('editElectionParty');
                                for (let option of electionDropdown.options) {
                                  if (option.value === data.party.election_name) {
                                    option.selected = true;
                                    break;
                                  }
                                }

                                // Populate Quill editor with platforms
                                quill.root.innerHTML = data.party.platforms || '<p><br></p>';

                                // Show image preview
                                const partyImage = data.party.party_image;
                                const imagePreviewContainer = document.getElementById('imagePreviewContainer');
                                const imagePreview = document.getElementById('imagePreview');
                                if (partyImage) {
                                  imagePreview.src = `../Uploads/${partyImage}`;
                                  imagePreviewContainer.style.display = 'block';
                                } else {
                                  imagePreviewContainer.style.display = 'none';
                                }

                                // Initialize and show modal
                                const editModal = new bootstrap.Modal(document.getElementById('editPartyModal'), {
                                  backdrop: true,
                                  keyboard: true
                                });

                              } else {
                                Swal.fire({
                                  title: 'Error!',
                                  text: 'Party data could not be loaded.',
                                  icon: 'error',
                                  timer: 2000,
                                  showConfirmButton: false
                                });
                              }
                            })
                            .catch(error => {
                              console.error('Fetch error:', error);
                              Swal.fire({
                                title: 'Error!',
                                text: 'Failed to load party details.',
                                icon: 'error',
                                timer: 2000,
                                showConfirmButton: false
                              });
                            });
                        });
                      });

                      // Handle form submission
                      document.getElementById('partyFormEditable').addEventListener('submit', function(event) {
                        event.preventDefault();

                        // Validate Platforms
                        const platformsContent = quill.getText().trim();
                        if (!platformsContent) {
                          Swal.fire({
                            title: 'Error!',
                            text: 'Platforms cannot be empty.',
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                          });
                          return;
                        }

                        // Update hidden input with Quill content
                        document.getElementById('editPlatforms').value = quill.root.innerHTML;

                        // Prepare form data
                        const formData = new FormData(this);

                      });

                      // Image preview function
                      function previewImage(event) {
                        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
                        const imagePreview = document.getElementById('imagePreview');
                        const file = event.target.files[0];

                        if (file) {
                          const reader = new FileReader();
                          reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            imagePreviewContainer.style.display = 'block';
                          };
                          reader.readAsDataURL(file);
                        } else {
                          imagePreviewContainer.style.display = 'none';
                        }
                      }

                      // Attach previewImage to file input
                      const fileInput = document.querySelector('input[type="file"]');
                      if (fileInput) {
                        fileInput.addEventListener('change', previewImage);
                      }
                    });
                  </script>







                  <div class="modal fade" id="positionModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="exampleModalLabel">New Position</h1>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <form id="positionFormAdd">
                            <!-- Position Name -->
                            <div class="mb-3">
                              <label for="position" class="form-label">New Position:</label>
                              <input type="text" class="form-control" id="position" name="position" required>
                            </div>
                            <div class="mb-3">
                              <label for="position" class="form-label">Level:</label>
                              <select class="form-control" name="level" id="level">
                                <option value="Central">Central</option>
                                <option value="Local">Local</option>
                              </select>
                            </div>
                            <!-- Parties (Checkboxes) -->
                            <div class="mb-3">
                              <label class="form-label">Parties:</label>
                              <div id="partyCheckboxesStarter" class="checkbox-container">
                              </div>
                            </div>

                            <!-- ── College/Department Restrictions — LOCAL positions only ── -->
                            <div class="mb-3" id="collegeRestrictionWrapper" style="display:none;">
                              <label class="form-label fw-bold">
                                <i class="mdi mdi-school me-1"></i>College &amp; Department Restrictions
                                <small class="text-muted fw-normal ms-1">(Only students from selected colleges/departments may run for this position)</small>
                              </label>
                              <div class="alert alert-info py-2 mb-2" style="font-size:0.85rem;">
                                <i class="mdi mdi-information-outline me-1"></i>
                                Leave <b>all unchecked</b> to allow any college.
                                Check a college to restrict to it, then optionally expand and check specific departments within it.
                              </div>
                              <div id="collegeCheckboxesAdd" class="college-checkbox-container"></div>
                            </div>

                            <div class="modal-footer">

                              <button type="button" class="btn btn-secondary " data-bs-dismiss="modal">
                                <i class="mdi mdi-close"></i> Close
                              </button>

                              <button type="submit" class="btn btn-primary text-white">
                                <i class="mdi mdi-upload"></i> Save changes
                              </button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>

                  <style>
                    .checkbox-container {
                      max-height: 200px;
                      overflow-y: auto;
                      padding-left: 45px;
                      border: 1px solid #ced4da;
                      border-radius: 4px;
                    }

                    .form-check {
                      margin-bottom: 8px;
                    }
                  </style>




                  <!-- page-body-wrapper ends -->
                </div>
                <!-- container-scroller -->
                <div class="modal fade" id="editPositionModalNew" tabindex="-1" aria-labelledby="editPositionModalLabel" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h1 class="modal-title fs-5" id="editPositionModalLabel">Edit Position</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <form id="positionFormEdit">
                          <!-- Hidden field for position ID -->
                          <input type="hidden" id="editPositionId" name="position_id">

                          <!-- CSRF Token -->
                          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                          <!-- Position Name -->
                          <div class="mb-3">
                            <label class="form-label">Position Name:</label>

                            <!-- Container for dynamic field (will be populated via JavaScript) -->
                            <div id="editPositionFieldContainer">
                              <!-- This will be filled with either a dropdown or text input -->
                            </div>

                            <!-- Switch to text input button -->
                            <button type="button" id="editCustomPositionBtn" class="btn btn-link p-0 mt-1" style="display:none;">
                              Enter custom position
                            </button>
                          </div>

                          <!-- Level -->
                          <div class="mb-3">
                            <label for="editLevel" class="form-label">Level:</label>
                            <select class="form-control" name="level" id="editLevel">
                              <option value="Central">Central</option>
                              <option value="Local">Local</option>
                            </select>
                          </div>

                          <!-- Parties (Checkboxes) -->
                          <div class="mb-3">
                            <label class="form-label">Parties:</label>
                            <div id="editPartyCheckboxes" class="checkbox-container">
                              <!-- Checkboxes will be dynamically added here via JavaScript -->
                            </div>
                          </div>

                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                              <i class="mdi mdi-close"></i> Close
                            </button>
                            <button type="submit" class="btn btn-primary text-white">
                              <i class="mdi mdi-upload"></i> Save changes
                            </button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>

                <style>
                  .checkbox-container {
                    height: auto;
                    max-height: 200px;
                    overflow-y: auto;
                    padding: 10px;
                    border: 1px solid #ced4da;
                    border-radius: 4px;
                  }

                  .form-check {
                    margin-bottom: 8px;
                  }

                  .form-check-input {
                    margin-left: 5px !important;
                  }
                </style>

                <script>
                  $(document).ready(function() {
                    const tables = [
                      '#electionTable',
                      '#partyTable',
                      '#pastPartyTable',
                      '#pastElectionTable'
                    ];

                    tables.forEach(function(selector) {
                      if ($(selector).length && !$.fn.DataTable.isDataTable(selector)) {
                        $(selector).DataTable({
                          responsive: true,
                          paging: true,
                          searching: true,
                          ordering: true,
                          info: true,
                          dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                          // buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
                        });
                      }
                    });

                    var electionTable = $('#electionTable').DataTable();
                    $('#semesterFilter').on('change', function() {
                      var selectedValue = $(this).val();

                      // Clear all previous filters on the table
                      electionTable.columns().search('').draw();

                      if (selectedValue === "Ongoing") {
                        // Filter for rows where status is 'Ongoing'
                        electionTable.column(5).search('^Ongoing$', true, false).draw();
                      } else if (selectedValue !== "") {
                        // The value is academic_year_id. The table doesn't have it.
                        // I will filter by the text of the option.
                        var selectedText = $(this).find('option:selected').text().trim();
                        var parts = selectedText.split(' - ');
                        var yearLabel = parts[0];
                        var semester = parts[1];

                        electionTable.column(0).search(yearLabel, true, false);
                        electionTable.column(1).search(semester, true, false).draw();
                      }
                      // If selectedValue is "", all filters are cleared, showing all semesters.
                    });
                  });
                </script>


                <script>
                  // ===== Shared data =====
                  const CENTRAL_POSITIONS = ['President', 'Vice-President'];
                  const LOCAL_POSITIONS = ['Mayor', 'Senator', 'Councilor'];

                  // ===== Element handles =====
                  const editLevelSelect = document.getElementById('editLevel');
                  const editPosContainer = document.getElementById('editPositionFieldContainer');
                  const editCustomPosBtn = document.getElementById('editCustomPositionBtn');

                  // ===== Render helpers =====
                  function renderEditDropdown(options, selected = '') {
                    editPosContainer.innerHTML =
                      `<select class="form-control" id="editPositionName" name="position" required>
         ${options.map(o =>
            `<option value="${o}" ${o === selected ? 'selected' : ''}>${o}</option>`).join('')}
       </select>`;
                    editCustomPosBtn.style.display = 'inline-block';
                  }

                  function renderEditTextInput(value = '') {
                    editPosContainer.innerHTML =
                      `<input type="text" class="form-control" id="editPositionName" name="position"
              value="${value}" required>`;
                    editCustomPosBtn.style.display = 'none';
                  }

                  // ===== Level change handler =====
                  function applyEditLevel(level, currentValue = '') {
                    const list = level === 'Central' ? CENTRAL_POSITIONS : LOCAL_POSITIONS;
                    if (list.includes(currentValue)) {
                      renderEditDropdown(list, currentValue);
                    } else {
                      renderEditDropdown(list); // show list …
                      renderEditTextInput(currentValue); // … then immediately switch if value is custom
                    }
                  }

                  // ===== UI events =====
                  editLevelSelect.addEventListener('change', () => applyEditLevel(editLevelSelect.value));
                  editCustomPosBtn.addEventListener('click', () => renderEditTextInput());

                  // Function to populate the edit modal based on position ID


                  // Handle form submission
                  $(document).ready(function() {
                    $('#positionFormEdit').on('submit', function(e) {
                      e.preventDefault();

                      const positionName = $('#editPositionName').val().trim();
                      const selectedParties = $('input[name="parties[]"]:checked').length;

                      if (!positionName) {
                        Swal.fire({
                          icon: 'warning',
                          title: 'Validation Error',
                          text: 'Position name is required'
                        });
                        return;
                      }

                      if (selectedParties === 0) {
                        Swal.fire({
                          icon: 'warning',
                          title: 'Validation Error',
                          text: 'Please select at least one party'
                        });
                        return;
                      }

                      let formData = new FormData(this);

                      $.ajax({
                        url: 'processes/elect/update_position.php',
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        dataType: 'json',
                        success: function(response) {
                          Swal.fire({
                            icon: response.status === 'success' ? 'success' : 'error',
                            title: response.status === 'success' ? 'Success' : 'Error',
                            text: response.message || 'Operation completed'

                          }).then(() => {
                            if (response.status === 'success') {
                              $('#editPositionModal').modal('hide');
                              location.reload();
                            }
                          });
                        },
                        error: function(xhr, status, error) {
                          Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: error
                          });
                        }
                      });
                    });

                    // Clear modal when closed
                    $('#editPositionModal').on('hidden.bs.modal', function() {
                      $('#positionFormEdit')[0].reset();
                      $('#editPartyCheckboxes').empty();
                    });
                  });
                </script>




                <script>
                  // Function to populate the modal with the selected position data
                  function editPosition(positionId) {

                    // Fetch position data based on positionId
                    fetch(`processes/elect/get_position.php?id=${positionId}`)
                      .then(response => response.json())
                      .then(data => {
                        if (data.status === 'success') {
                          // Populate the form with fetched position data
                          const position = data.position;
                          document.getElementById('positionEdit').value = position.position;
                          document.getElementById('applicationEdit').value = position.application;

                          // Set a hidden field with the position ID for the update process
                          const positionIdInput = document.createElement('input');
                          positionIdInput.type = 'hidden';
                          positionIdInput.name = 'id';
                          positionIdInput.value = position.id;
                          document.getElementById('positionForm').appendChild(positionIdInput);

                        } else {
                          Swal.fire({
                            title: 'Error!',
                            text: 'Failed to fetch position data.',
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                          });
                        }
                      })
                      .catch(error => {
                        console.error('Error fetching position data:', error);
                        Swal.fire({
                          title: 'Error!',
                          text: 'An error occurred while fetching the position data.',
                          icon: 'error',
                          timer: 2000,
                          showConfirmButton: false
                        });
                      });
                  }

                  // Handle form submission to update position
                  document.getElementById('positionForm').addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevent default form submission

                    // Get form data
                    const formData = new FormData(this);

                    // Submit the updated data
                    fetch('processes/elect/update_position.php', {
                        method: 'POST',
                        body: formData
                      })
                      .then(response => response.json())
                      .then(data => {
                        if (data.status === 'success') {
                          Swal.fire({
                            title: 'Updated!',
                            text: 'Position details have been updated.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                          }).then(() => {
                            location.reload(); // Reload the page after success
                          });
                        } else {
                          Swal.fire({
                            title: 'Error!',
                            text: 'Failed to update position.',
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                          });
                        }
                      })

                  });

                  // Handle form submission
                  $(document).ready(function() {
                    $('#positionFormEdit').on('submit', function(e) {
                      e.preventDefault();

                      const positionName = $('#editPositionName').val().trim();
                      const level = $('#editLevel').val(); // Added level validation
                      const selectedParties = $('input[name="parties[]"]:checked').length;

                      if (!positionName) {
                        Swal.fire({
                          icon: 'warning',
                          title: 'Validation Error',
                          text: 'Position name is required'
                        });
                        return;
                      }

                      if (!level) {
                        Swal.fire({
                          icon: 'warning',
                          title: 'Validation Error',
                          text: 'Please select a level'
                        });
                        return;
                      }

                      if (selectedParties === 0) {
                        Swal.fire({
                          icon: 'warning',
                          title: 'Validation Error',
                          text: 'Please select at least one party'
                        });
                        return;
                      }

                      let formData = new FormData(this);

                      $.ajax({
                        url: 'processes/elect/update_position.php',
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        dataType: 'json',
                        success: function(response) {
                          Swal.fire({
                            icon: response.status === 'success' ? 'success' : 'error',
                            title: response.status === 'success' ? 'Success' : 'Error',
                            text: response.message || 'Operation completed',
                            showCancelButton: response.status === 'success',
                            confirmButtonText: 'Reload Page',

                          }).then((result) => {
                            if (response.status === 'success' && result.isConfirmed) {
                              $('#editPositionModal').modal('hide');
                              location.reload();
                            } else if (response.status === 'success') {
                              $('#editPositionModal').modal('hide');
                            }
                          });
                        },
                        error: function(xhr, status, error) {
                          Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: 'Failed to update position: ' + error
                          });
                        }
                      });

                    });

                    // Clear modal when closed
                    $('#editPositionModal').on('hidden.bs.modal', function() {
                      $('#positionFormEdit')[0].reset();
                      $('#editPartyCheckboxes').empty();
                    });
                  });


                  document.addEventListener("DOMContentLoaded", function() {
                    const verifyLink = document.getElementById("verifyLink");
                    const editPartyId = document.getElementById("editPartyId");

                    // Function to update the verify button link
                    function updateVerifyLink() {
                      const partyId = editPartyId.value;
                      if (partyId) {
                        verifyLink.href = `processes/elect/verify.php?id=${partyId}`;
                      }
                    }

                    // Ensure link updates when modal is triggered
                    document.getElementById("editPartyModal").addEventListener("show.bs.modal", function() {
                      setTimeout(updateVerifyLink, 300); // Slight delay to ensure input is populated
                    });
                  });



                  // delte party id

                  $(document).ready(function() {
                    // Delete Button Click
                    $(document).on("click", ".deleteBtn", function() {
                      let row = $(this).closest("tr");
                      let name = row.find("td:eq(1)").text(); // Get Name Column

                      Swal.fire({
                        title: "Are you sure?",
                        text: `You are about to delete ${name}. This action cannot be undone!`,
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
                          row.remove();
                          Swal.fire({
                            title: "Deleted!",
                            text: `${name} has been removed.`,
                            icon: "success",
                            customClass: {
                              popup: 'custom-swal-padding' // Custom class for modal padding
                            }
                          });
                        }

                      });
                    });
                  });
                </script>

                <script>
                  document.addEventListener("DOMContentLoaded", function() {
                    const positionFormAdd = document.getElementById("positionFormAdd");
                    console.log("JavaScript loaded!"); // ✅ Check if script runs

                    // Handle form submission
                    positionFormAdd.addEventListener("submit", function(e) {
                      e.preventDefault(); // Prevent default form submission

                      // Create FormData object
                      const formData = new FormData(positionFormAdd);

                      // Send form data to the server
                      fetch("processes/elect/add_position.php", {
                          method: "POST",
                          body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                          if (data.status === "success") {
                            // Close the modal after successful submission
                            let modal = new bootstrap.Modal(document.getElementById("positionModal"));
                            modal.hide(); // Ensure modal closes properly

                            Swal.fire({
                              title: "Success!",
                              text: data.message,
                              icon: "success",
                              confirmButtonText: "OK"
                            }).then((result) => {
                              if (result.isConfirmed) {
                                location.reload(); // ✅ Reload only after confirming
                              }
                            });
                          } else {
                            Swal.fire({
                              title: "Error!",
                              text: data.message,
                              icon: "error",
                              confirmButtonText: "OK"
                            });
                          }
                        })
                        .catch(error => {
                          console.error("Error:", error);
                          Swal.fire({
                            title: "Error!",
                            text: "There was an error processing the form.",
                            icon: "error",
                            confirmButtonText: "OK"
                          });
                        });
                    });
                  });
                </script>


                <script>
                  document.addEventListener("DOMContentLoaded", function() {
                    $(document).ready(function() {
                      // Initialize DataTable — window scope so filter functions can access it
                      window.positionTable = $('#positionTable').DataTable({
                        responsive: true,
                        paging: true,
                        searching: true,
                        ordering: true,
                        info: true,
                        dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                        // buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                        columns: [{
                            data: "school_year"
                          },
                          {
                            data: "semester"
                          },
                          {
                            data: "name"
                          },
                          {
                            data: "party"
                          },
                          {
                            data: "level",
                            render: function(data) {
                              if (data === "Central") return `<span class="badge bg-success">Central</span>`;
                              if (data === "Local") return `<span class="badge bg-warning">Local</span>`;
                              return `<span class="badge bg-secondary">${data}</span>`;
                            }
                          },
                          {
                            // col 5 — Restrictions
                            data: "allowed_colleges",
                            render: function(data, type, row) {
                              if (row.level !== 'Local') {
                                return '<span class="text-muted" style="font-size:0.8rem;">N/A</span>';
                              }
                              try {
                                const colleges = typeof data === 'string' ? JSON.parse(data) : (data || []);
                                if (!colleges || !colleges.length) {
                                  return '<span class="badge bg-primary">Any College</span>';
                                }
                                return colleges.map(c => {
                                  let deptHtml = '';
                                  try {
                                    const depts = typeof row.allowed_departments === 'string' ? JSON.parse(row.allowed_departments) : (row.allowed_departments || []);
                                    const cd = depts.filter(d => d.college_id == c.id);
                                    if (cd.length) deptHtml = `<br><small class="text-muted ms-1">→ ${cd.map(d => d.name).join(', ')}</small>`;
                                  } catch (e) {}
                                  return `<div style="font-size:0.82rem;"><strong>${c.name}</strong>${deptHtml}</div>`;
                                }).join('');
                              } catch (e) {
                                return '<span class="badge bg-primary">Any College</span>';
                              }
                            }
                          },
                          {
                            data: "election_name"
                          },
                          {
                            data: "id",
                            render: function(data, type, row) {
                              const isPublished = String(row.status).toLowerCase() === "published";
                              if (isPublished) {
                                return `<a href='history.php' class='button-view-primary'><i class='mdi mdi-poll'></i> View Results</a>`;
                              }
                              return `<button class="button-view-danger deleteBtnPosition" data-id="${data}" data-party="${row.party}"><i class="mdi mdi-delete"></i> Delete</button>`;
                            }
                          }
                        ]
                      });



                      // Function to fetch and display position data
                      function loadPositionData() {
                        fetch('processes/elect/fetch_positions.php')
                          .then(response => response.json())
                          .then(data => {
                            if (Array.isArray(data)) {
                              positionTable.clear().rows.add(data).draw(); // ✅ Proper DataTable update

                              // Populate party filter dynamically from loaded data
                              var parties = [...new Set(data.map(r => r.party).filter(Boolean))].sort();
                              var partySelect = document.getElementById('positionPartyFilter');
                              if (partySelect) {
                                var currentVal = partySelect.value;
                                partySelect.innerHTML = '<option value="">All Parties</option>';
                                parties.forEach(function(p) {
                                  var opt = document.createElement('option');
                                  opt.value = p;
                                  opt.textContent = p;
                                  if (p === currentVal) opt.selected = true;
                                  partySelect.appendChild(opt);
                                });
                              }
                            } else {
                              console.error('Data is not an array', data);
                            }
                          })
                          .catch(error => {
                            console.error('Error fetching positions:', error);
                          });
                      }

                      // Load positions initially
                      loadPositionData();
                      // Delete Button Click Event (Using Event Delegation)
                      document.addEventListener('click', function(event) {
                        const deleteBtn = event.target.closest('.deleteBtnPosition');
                        if (deleteBtn) {
                          const positionId = deleteBtn.getAttribute('data-id');
                          const partyName = deleteBtn.getAttribute('data-party'); // ✅ get the party name
                          console.log(positionId, partyName);

                          Swal.fire({
                            title: 'Are you sure?',
                            text: `You won't be able to revert this!\nParty: ${partyName}`, // optional
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, delete it!',
                            cancelButtonText: 'No, cancel!',
                          }).then((result) => {
                            if (result.isConfirmed) {
                              deletePosition(positionId, partyName); // ✅ pass both values
                            }
                          });
                        }
                      });

                      // Function to Delete Position (Fixed to Use POST)
                      function deletePosition(positionId, partyName) {
                        fetch('processes/elect/delete_position.php', {
                            method: 'POST',
                            headers: {
                              'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `id=${positionId}&partyName=${partyName}`
                          })
                          .then(response => response.json())
                          .then(data => {
                            if (data.status === 'success') {
                              Swal.fire({
                                title: 'Deleted!',
                                text: data.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                              }).then(() => {
                                window.location.href = 'election.php'
                              });
                            } else {
                              Swal.fire({
                                title: 'Error!',
                                text: data.message,
                                icon: 'error',
                                timer: 2000,
                                showConfirmButton: false
                              });
                            }
                          })
                          .catch(error => {
                            console.error("Error:", error);
                            Swal.fire({
                              title: 'Error!',
                              text: 'There was an error deleting the position.',
                              icon: 'error',
                              timer: 2000,
                              showConfirmButton: false
                            });
                          });
                      }
                    });
                  });

                  // Initialize DataTable
                  var recentPositionTable = $('#recentPositionTable').DataTable({
                    responsive: true,
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    // buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                    columns: [{
                        data: "name"
                      },
                      {
                        data: "party"
                      },

                      {
                        data: "level",
                        render: function(data) {
                          // Add badge based on level value
                          if (data === "Central") {
                            return `<span class="badge bg-success">Central</span>`;
                          } else if (data === "Local") {
                            return `<span class="badge bg-warning">Local</span>`;
                          } else {
                            return `<span class="badge bg-secondary">${data}</span>`; // Fallback for unexpected values
                          }
                        }
                      },
                      {
                        data: "election_name"
                      },
                      {
                        data: "id",
                        render: function(data, type, row) {

                          const isPublished = String(row.status).toLowerCase() === "published";

                          if (isPublished) {
                            return `
        <a href='history.php' 
           class='button-view-primary'>
            <i class='mdi mdi-poll'></i> View Results
        </a>
      `;
                          }

                          return `


      <button class="button-view-danger deleteBtnPosition" 
          data-id="${data}" 
          data-party="${row.party}">
          <i class="mdi mdi-delete"></i> Delete
      </button>
    `;
                        }
                      }


                    ]
                  });

                  //                     <?php
                                          //                       <button class="button-view-warning editBtnPositionNew" 
                                          //     data-id="${data}" 
                                          //     data-party="${row.party}">
                                          //     <i class="mdi mdi-pencil"></i> Edit
                                          // </button>
                                          // 
                                          ?>

                  // Function to fetch and display position data
                  function loadRecentPositionData() {
                    fetch('processes/elect/fetch_recent_positions.php')
                      .then(response => response.json())
                      .then(data => {
                        if (Array.isArray(data)) {
                          recentPositionTable.clear().rows.add(data).draw(); // ✅ Proper DataTable update
                        } else {
                          console.error('Data is not an array', data);
                        }
                      })
                      .catch(error => {
                        console.error('Error fetching positions:', error);
                      });
                  }

                  // Load positions initially
                  loadRecentPositionData();
                  // Delete Button Click Event (Using Event Delegation)
                  document.addEventListener('click', function(event) {
                    const deleteBtn = event.target.closest('.deleteBtnPosition');
                    if (deleteBtn) {
                      const positionId = deleteBtn.getAttribute('data-id');
                      const partyName = deleteBtn.getAttribute('data-party'); // ✅ get the party name
                      console.log(positionId, partyName);

                      Swal.fire({
                        title: 'Are you sure?',
                        text: `You won't be able to revert this!\nParty: ${partyName}`, // optional
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'No, cancel!',
                      }).then((result) => {
                        if (result.isConfirmed) {
                          deletePosition(positionId, partyName); // ✅ pass both values
                        }
                      });
                    }
                  });

                  // Function to Delete Position (Fixed to Use POST)
                  function deletePosition(positionId, partyName) {
                    fetch('processes/elect/delete_position.php', {
                        method: 'POST',
                        headers: {
                          'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `id=${positionId}&partyName=${partyName}`
                      })
                      .then(response => response.json())
                      .then(data => {
                        if (data.status === 'success') {
                          Swal.fire({
                            title: 'Deleted!',
                            text: data.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                          }).then(() => {
                            window.location.href = 'election.php'
                          });
                        } else {
                          Swal.fire({
                            title: 'Error!',
                            text: data.message,
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                          });
                        }
                      })
                      .catch(error => {
                        console.error("Error:", error);
                        Swal.fire({
                          title: 'Error!',
                          text: 'There was an error deleting the position.',
                          icon: 'error',
                          timer: 2000,
                          showConfirmButton: false
                        });
                      });
                  }

                  loadPositionData();


                  //asd




                  //asd



                  // Handle form submission to update position
                  document.getElementById('positionForm').addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevent default form submission

                    // Get form data
                    const formData = new FormData(this);

                    // Submit the updated data
                    fetch('processes/elect/update_position.php', {
                        method: 'POST',
                        body: formData
                      })
                      .then(response => response.json())
                      .then(data => {
                        if (data.status === 'success') {
                          Swal.fire({
                            title: 'Updated!',
                            text: 'Position details have been updated.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                          }).then(() => {
                            location.reload(); // Reload the page after success
                          });
                        } else {
                          Swal.fire({
                            title: 'Error!',
                            text: 'Failed to update position.',
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                          });
                        }
                      })
                      .catch(error => {
                        Swal.fire({
                          title: 'Error!',
                          text: 'An error occurred while updating the position.',
                          icon: 'error',
                          timer: 2000,
                          showConfirmButton: false
                        });
                      });
                  });

                  // Handle form submission to update position
                  document.getElementById('positionForm').addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevent default form submission

                    // Get form data
                    const formData = new FormData(this);

                    // Submit the updated data
                    fetch('processes/elect/update_position.php', {
                        method: 'POST',
                        body: formData
                      })
                      .then(response => response.json())
                      .then(data => {
                        if (data.status === 'success') {
                          Swal.fire({
                            title: 'Updated!',
                            text: 'Position details have been updated.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                          }).then(() => {
                            location.reload(); // Reload the page after success
                          });
                        } else {
                          Swal.fire({
                            title: 'Error!',
                            text: 'Failed to update position.',
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                          });
                        }
                      })
                      .catch(error => {
                        Swal.fire({
                          title: 'Error!',
                          text: 'An error occurred while updating the position.',
                          icon: 'error',
                          timer: 2000,
                          showConfirmButton: false
                        });
                      });
                  });

                  document.body.addEventListener('click', function(event) {
                    if (event.target && event.target.classList.contains('editBtnPosition')) {
                      const positionId = event.target.getAttribute('data-id');
                      console.log('Position ID:', positionId); // Debugging log

                      // Fetch the position data from the server
                      fetch(`processes/elect/get_position.php?id=${positionId}`)
                        .then(response => response.json())
                        .then(data => {
                          console.log('Data fetched from server:', data); // Debugging log

                          if (data.status === 'success') {
                            const position = data.position;
                            console.log(position);
                            document.getElementById('positionEdit').value = position.name; // Populate Position Name
                            document.getElementById('applicationEdits').value = position.application; // Populate Application Type


                            // Optionally, set a hidden field with the position ID for updating
                            const positionIdInput = document.createElement('input');
                            positionIdInput.type = 'hidden';
                            positionIdInput.name = 'id';
                            positionIdInput.value = position.id;
                            document.getElementById('positionForm').appendChild(positionIdInput);
                          } else {
                            Swal.fire({
                              title: 'Error!',
                              text: 'Failed to fetch position data.',
                              icon: 'error',
                              timer: 2000,
                              showConfirmButton: false
                            });
                          }
                        })
                        .catch(error => {
                          console.error('Error fetching position data:', error);
                          Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while fetching the position data.',
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                          });
                        });
                    }
                  });
                </script>



                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    function populateModal(button) {
                      try {
                        const id = button.getAttribute('data-id');
                        const electionName = button.getAttribute('data-election-name');
                        const academicYearId = button.getAttribute('data-academic-year-id');
                        const startPeriod = button.getAttribute('data-start-period');
                        const endPeriod = button.getAttribute('data-end-period');
                        const status = button.getAttribute('data-status');

                        document.getElementById('editElectionId').value = id || '';
                        document.getElementById('editElectionName').value = electionName || '';
                        document.getElementById('editAcademicYear').value = academicYearId || '';
                        document.getElementById('editStartPeriod').value = startPeriod || '';
                        document.getElementById('editEndPeriod').value = endPeriod || '';
                        document.getElementById('editStatus').value = status || '';
                      } catch (error) {
                        console.error('Error populating modal:', error);
                        Swal.fire({
                          icon: 'error',
                          title: 'Error',
                          text: 'Failed to load election data: ' + error.message
                        });
                      }
                    }


                    // jQuery event listener for edit
                    $(document).on('click', '.edit-election', function() {
                      populateModal(this);
                    });

                    // Form submission handler
                    document.getElementById('editElectionForm').addEventListener('submit', function(e) {
                      e.preventDefault();
                      Swal.fire({
                        title: 'Confirm Update',
                        text: 'Are you sure you want to update this election?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, update it!',
                        cancelButtonText: 'Cancel',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                          const formData = new FormData(this);
                          return fetch('update_election.php', {
                              method: 'POST',
                              body: formData
                            })
                            .then(response => {
                              if (!response.ok) throw new Error('Network response was not ok');
                              return response.text().then(text => {
                                try {
                                  return JSON.parse(text);
                                } catch (e) {
                                  throw new Error('Invalid JSON response: ' + text);
                                }
                              });
                            })
                            .catch(error => {
                              Swal.showValidationMessage(`Request failed: ${error}`);
                            });
                        },
                        allowOutsideClick: () => !Swal.isLoading()
                      }).then(result => {
                        if (result.isConfirmed) {
                          if (result.value.status === 'success') {
                            Swal.fire({
                              icon: 'success',
                              title: 'Success!',
                              text: result.value.message,
                              timer: 2000,
                              timerProgressBar: true
                            }).then(() => {
                              const modal = bootstrap.Modal.getInstance(document.getElementById('editElectionModal'));
                              if (modal) modal.hide();
                              location.reload();
                            });
                          } else {
                            Swal.fire({
                              icon: 'error',
                              title: 'Error!',
                              text: result.value.message || 'Failed to update election'
                            });
                          }
                        }
                      });
                    });

                    // Delete election handler
                    document.querySelectorAll('.delete-election').forEach(button => {
                      button.addEventListener('click', function() {
                        const electionId = this.getAttribute('data-id');
                        const electionName = this.getAttribute('data-election');
                        Swal.fire({
                          title: 'Confirm Deletion',
                          text: `Are you sure you want to delete "${electionName}"?`,
                          icon: 'warning',
                          showCancelButton: true,
                          confirmButtonColor: '#d33',
                          cancelButtonColor: '#3085d6',
                          confirmButtonText: 'Yes, delete it!'
                        }).then(result => {
                          if (result.isConfirmed) {
                            fetch('processes/elect/delete_election.php', {
                                method: 'POST',
                                headers: {
                                  'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `delete_election=true&election_id=${electionId}`
                              })
                              .then(response => response.json())
                              .then(data => {
                                if (data.status === 'success') {
                                  Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: data.message || 'Election deleted successfully',
                                    timer: 1500
                                  }).then(() => location.reload());
                                } else {
                                  throw new Error(data.message || 'Failed to delete election');
                                }
                              })
                              .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                  icon: 'error',
                                  title: 'Error!',
                                  text: error.message || 'Failed to delete election'
                                });
                              });
                          }
                        });
                      });
                    });

                    var electionTable = $('#electionTable').DataTable();

                    // ── Helper: parse semester dropdown into year/semester strings ──
                    function getSemesterParts() {
                      var val = $('#semesterFilter').val();
                      if (!val || val === 'all' || val === 'ongoing') return {
                        yearLabel: '',
                        semester: '',
                        val: val
                      };
                      var text = $('#semesterFilter option:selected').text().trim();
                      var parts = text.split(' – ');
                      if (parts.length < 2) parts = text.split(' - ');
                      return parts.length >= 2 ? {
                        yearLabel: parts[0].trim(),
                        semester: parts[1].trim(),
                        val: val
                      } : {
                        yearLabel: '',
                        semester: '',
                        val: val
                      };
                    }

                    // ── Election table filter ──
                    function filterElectionTable() {
                      var s = getSemesterParts();
                      var statusVal = $('#statusFilter').val();
                      electionTable.columns().search('');
                      if (s.val === 'ongoing') {
                        electionTable.column(5).search('^Ongoing$', true, false);
                      } else if (s.yearLabel) {
                        electionTable.column(0).search(s.yearLabel, true, false);
                        electionTable.column(1).search(s.semester, true, false);
                      }
                      if (statusVal) electionTable.column(5).search('^' + statusVal + '$', true, false);
                      electionTable.draw();
                    }

                    // ── Party table filter ──
                    // Status uses a custom search fn because rendered cell contains <span class="badge">
                    var partyStatusCustomFilter = null;

                    function filterPartyTable() {
                      var partyTable = $('#partyTable').DataTable();
                      var election = $('#partyElectionFilter').val();
                      var status = $('#partyStatusFilter').val();
                      var s = getSemesterParts();

                      // Remove previous custom filter if any
                      if (partyStatusCustomFilter) {
                        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(f) {
                          return f !== partyStatusCustomFilter;
                        });
                        partyStatusCustomFilter = null;
                      }

                      partyTable.column(3).search(election ? election : '', true, false);

                      if (s.yearLabel) {
                        partyTable.column(0).search(s.yearLabel, true, false);
                        partyTable.column(1).search(s.semester, true, false);
                      } else {
                        partyTable.column(0).search('');
                        partyTable.column(1).search('');
                      }

                      if (status) {
                        partyStatusCustomFilter = function(settings, data, dataIndex) {
                          if (settings.nTable.id !== 'partyTable') return true;

                          var rowData = partyTable.row(dataIndex).data();
                          if (!rowData) return true;

                          var statusColumnIndex = 5;

                          var cellHtml = rowData[statusColumnIndex] || '';

                          // Strip HTML safely
                          var rowStatus = $('<div>').html(cellHtml).text().trim().toLowerCase();

                          return rowStatus === status.toLowerCase();
                        };
                        $.fn.dataTable.ext.search.push(partyStatusCustomFilter);
                      }

                      partyTable.draw();
                    }

                    // ── Position table filter ──
                    var positionRestrictionCustomFilter = null;

                    function filterPositionTable() {
                      var pt = window.positionTable;
                      if (!pt) return;
                      var election = $('#positionElectionFilter').val();
                      var party = $('#positionPartyFilter').val();
                      var level = $('#positionLevelFilter').val();
                      var restriction = $('#positionRestrictionFilter').val();
                      var s = getSemesterParts();

                      // Remove previous custom filter if any
                      if (positionRestrictionCustomFilter) {
                        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(f) {
                          return f !== positionRestrictionCustomFilter;
                        });
                        positionRestrictionCustomFilter = null;
                      }

                      // col 6 = election_name, col 3 = party, col 4 = level (regex exact)
                      pt.column(6).search(election ? election : '', true, false);
                      pt.column(3).search(party ? party : '', true, false);
                      pt.column(4).search(level ? '^' + level + '$' : '', true, false);

                      if (s.yearLabel) {
                        pt.column(0).search(s.yearLabel, true, false);
                        pt.column(1).search(s.semester, true, false);
                      } else {
                        pt.column(0).search('');
                        pt.column(1).search('');
                      }

                      if (restriction) {
                        positionRestrictionCustomFilter = function(settings, data, dataIndex) {
                          if (settings.nTable.id !== 'positionTable') return true;
                          var row = pt.row(dataIndex).data();
                          if (!row || row.level !== 'Local') return false;
                          try {
                            var colleges = row.allowed_colleges;
                            if (typeof colleges === 'string') colleges = JSON.parse(colleges);
                            var hasRestriction = Array.isArray(colleges) && colleges.length > 0;
                            if (restriction === 'any') return !hasRestriction;
                            if (restriction === 'restricted') return hasRestriction;
                          } catch (e) {}
                          return true;
                        };
                        $.fn.dataTable.ext.search.push(positionRestrictionCustomFilter);
                      }

                      pt.draw();
                    }

                    // ── Wire up all filter controls ──
                    $('#semesterFilter, #statusFilter').on('change', function() {
                      filterElectionTable();
                      if ($(this).attr('id') === 'semesterFilter') {
                        filterPartyTable();
                        filterPositionTable();
                      }
                    });

                    $('#partyElectionFilter, #partyStatusFilter').off('change').on('change', filterPartyTable);
                    $('#positionElectionFilter, #positionPartyFilter, #positionLevelFilter, #positionRestrictionFilter').off('change').on('change', filterPositionTable);

                    var pastElectionTable = $('#pastElectionTable').DataTable();
                    $('#pastSemesterFilter').on('change', function() {
                      pastElectionTable.column(1).search(this.value).draw();
                    });

                  });
                </script>



                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    document.querySelectorAll('.delete-election').forEach(button => {
                      button.addEventListener('click', function() {
                        const electionId = this.getAttribute('data-id');

                        // Confirm deletion
                        Swal.fire({
                          title: 'Are you sure?',
                          text: 'This will delete anything related to the election, including parties, positions, candidacy, voting, registration forms, events, precincts, and moderators!',
                          icon: 'warning',
                          showCancelButton: true,
                          confirmButtonColor: '#d33',
                          cancelButtonColor: '#3085d6',
                          confirmButtonText: 'Yes, delete it!'
                        }).then((result) => {
                          if (result.isConfirmed) {
                            // Make POST request to delete election
                            fetch('processes/elect/delete_election.php', {
                                method: 'POST',
                                headers: {
                                  'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: new URLSearchParams({
                                  'election_id': electionId
                                })
                              })
                              .then(response => response.json()) // Parse JSON response
                              .then(data => {
                                if (data.status === 'success') {
                                  Swal.fire({
                                    title: 'Deleted!',
                                    text: data.message, // Use server message
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 1500 // Auto-close after 1.5 seconds
                                  }).then(() => location.reload()); // Reload page on success
                                } else {
                                  // Handle error cases, including 'Ongoing' status
                                  Swal.fire({
                                    title: 'Error',
                                    text: data.message, // Show specific error (e.g., "Cannot delete an ongoing election.")
                                    icon: 'error',
                                    confirmButtonColor: '#d33'
                                  });
                                }
                              })
                              .catch(error => {
                                console.error('Fetch error:', error);
                                Swal.fire({
                                  title: 'Error',
                                  text: 'Failed to communicate with the server.',
                                  icon: 'error',
                                  confirmButtonColor: '#d33'
                                });
                              });
                          }
                        });
                      });
                    });
                  });
                </script>



                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    // Initialize Quill editor for Platforms
                    const quill = new Quill('#platforms-editor', {
                      theme: 'snow',
                      modules: {
                        toolbar: [
                          [{
                            'header': [1, 2, 3, false]
                          }],
                          ['bold', 'italic', 'underline'],
                          ['link'],
                          [{
                            'list': 'ordered'
                          }, {
                            'list': 'bullet'
                          }],
                          ['clean']
                        ]
                      }
                    });

                    // Handle form submission
                    document.getElementById("partyForm").addEventListener("submit", function(event) {
                      event.preventDefault(); // Prevent default form submission

                      // Validate Platforms field
                      const platformsContent = quill.getText().trim();
                      if (!platformsContent) {
                        Swal.fire({
                          title: 'Error!',
                          text: 'Platforms cannot be empty.',
                          icon: 'error',
                          timer: 2000,
                          showConfirmButton: false
                        });
                        return;
                      }

                      // Update hidden input with editor content
                      const hiddenInput = document.getElementById('platforms');
                      hiddenInput.value = quill.root.innerHTML; // Store editor content

                      // Prepare form data
                      let formData = new FormData(this);

                      // Submit form via AJAX
                      fetch("processes/elect/add_party.php", {
                          method: "POST",
                          body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                          if (data.status === "success") {
                            Swal.fire({
                              title: "Success!",
                              text: data.message,
                              icon: "success",
                              timer: 2000,
                              showConfirmButton: false
                            }).then(() => {
                              location.reload(); // Reload the page
                            });
                          } else {
                            Swal.fire({
                              title: "Error!",
                              text: data.message,
                              icon: "error",
                              timer: 2000,
                              showConfirmButton: false
                            });
                          }
                        })
                        .catch(error => {
                          console.error("Error:", error);
                          Swal.fire({
                            title: "Error!",
                            text: "Something went wrong. Please try again.",
                            icon: "error",
                            timer: 2000,
                            showConfirmButton: false
                          });
                        });
                    });
                  });
                </script>

                <script>
                  // Image preview function
                  function previewImage(event) {
                    const input = event.target;
                    const previewContainer = document.getElementById('imagePreviewContainer');
                    const preview = document.getElementById('imagePreview');

                    if (!previewContainer || !preview) {
                      console.error('Preview elements not found');
                      return;
                    }

                    if (input.files && input.files[0]) {
                      const reader = new FileReader();
                      reader.onload = function(e) {
                        preview.src = e.target.result;
                        previewContainer.style.display = 'block';
                      };
                      reader.onerror = function() {
                        console.error('Error reading file');
                        preview.src = '';
                        previewContainer.style.display = 'none';
                      };
                      reader.readAsDataURL(input.files[0]);
                    } else {
                      preview.src = '';
                      previewContainer.style.display = 'none';
                    }
                  }

                  // Form submission handler
                  document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('partyFormEditable');
                    if (!form) {
                      console.error('Form #partyFormEditable not found');
                      return;
                    }

                    form.addEventListener('submit', function(e) {
                      e.preventDefault();

                      const submitBtn = this.querySelector('button[type="submit"]');
                      if (!submitBtn) {
                        console.error('Submit button not found');
                        return;
                      }

                      const originalBtnText = submitBtn.innerHTML;
                      submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Processing...';
                      submitBtn.disabled = true;

                      const formData = new FormData(this);

                      fetch('processes/elect/update_party_new.php', {
                          method: 'POST',
                          body: formData
                        })
                        .then(response => {
                          if (!response.ok) {
                            return response.json().then(err => {
                              throw new Error(err.message || `HTTP error! Status: ${response.status}`);
                            });
                          }
                          return response.json();
                        })
                        .then(data => {
                          if (data.status === 'success') {
                            Swal.fire({
                              title: 'Success!',
                              text: data.message || 'Party updated successfully!',
                              icon: 'success',
                              confirmButtonText: 'OK',
                              allowOutsideClick: false,
                              allowEscapeKey: false
                            }).then((result) => {
                              if (result.isConfirmed) {
                                const modal = bootstrap.Modal.getInstance(document.getElementById('editPartyModal'));
                                if (modal) {
                                  modal.hide();
                                } else {
                                  console.warn('Modal not found or not initialized');
                                }
                                window.location.href = 'election.php'; // Use absolute path; adjust as needed
                              }
                            });
                          } else {
                            throw new Error(data.message || 'Unknown error occurred');
                          }
                        })
                        .catch(error => {
                          const errorMsg = error.message.includes('voting period has started') ?
                            error.message // Specific message for voting period error
                            :
                            'An error occurred while updating the party. Please try again.';

                          Swal.fire({
                            title: 'Error!',
                            text: errorMsg,
                            icon: 'error',
                            confirmButtonText: 'OK'
                          });
                        })
                        .finally(() => {
                          submitBtn.innerHTML = originalBtnText;
                          submitBtn.disabled = false;
                        });
                    });
                  });
                </script>



                <script>
                  document.addEventListener("DOMContentLoaded", function() {
                    const partyContainer = document.getElementById("partyCheckboxes");

                    // Fetch parties from the database
                    fetch("processes/elect/fetch_party.php")
                      .then(response => response.json())
                      .then(data => {
                        // Clear existing content
                        partyContainer.innerHTML = "";

                        if (!data || data.length === 0 || data.status === "error") {
                          // If no parties, show an empty message
                          const emptyMessage = document.createElement("p");
                          emptyMessage.textContent = "No parties available.";
                          emptyMessage.classList.add("text-muted");
                          emptyMessage.classList.add("text-center");
                          partyContainer.appendChild(emptyMessage);
                          return;
                        }

                        // Loop through and create checkboxes
                        data.forEach(party => {
                          const checkboxDiv = document.createElement("div");
                          checkboxDiv.classList.add("form-check");

                          const checkbox = document.createElement("input");
                          checkbox.type = "checkbox";
                          checkbox.classList.add("form-check-input");
                          checkbox.name = "parties[]";
                          checkbox.value = party.name;
                          checkbox.id = `party-${party.name.replace(/\s+/g, '-')}`;

                          const label = document.createElement("label");
                          label.classList.add("form-check-label");
                          label.htmlFor = checkbox.id;
                          label.textContent = party.name;

                          checkboxDiv.appendChild(checkbox);
                          checkboxDiv.appendChild(label);
                          partyContainer.appendChild(checkboxDiv);
                        });
                      })
                      .catch(error => {
                        console.error("Error fetching parties:", error);
                        partyContainer.innerHTML = "<p class='text-danger'>Failed to load parties.</p>";
                      });
                  });
                </script>

                <script>
                  document.getElementById("electionForm").addEventListener("submit", function(event) {
                    event.preventDefault(); // Prevent default form submission

                    let formData = new FormData(this);

                    fetch("processes/elect/submit.php", {
                        method: "POST",
                        body: formData
                      })
                      .then(response => response.json())
                      .then(data => {
                        if (data.status === "success") {
                          Swal.fire({
                            title: "Success!",
                            text: data.message,
                            icon: "success",
                            showCancelButton: true, // Add a cancel button
                            confirmButtonText: "OK",

                          }).then((result) => {
                            if (result.isConfirmed) {
                              // Only redirect if the user clicks "OK"
                              window.location.href = "election.php";
                            }
                            // If "Stay" is clicked, do nothing (page stays as is)
                          });
                        } else {
                          Swal.fire({
                            title: "Error!",
                            text: data.message,
                            icon: "error"
                          });
                        }
                      })
                      .catch(error => {
                        console.error("Error:", error);
                        Swal.fire({
                          title: "Error!",
                          text: "Something went wrong. Please try again.",
                          icon: "error"
                        });
                      });
                  });
                </script>

                <script>
                  // Image preview function
                  function previewImage(event) {
                    const input = event.target;
                    const previewContainer = document.getElementById('imagePreviewContainer');
                    const preview = document.getElementById('imagePreview');

                    if (input.files && input.files[0]) {
                      const reader = new FileReader();

                      reader.onload = function(e) {
                        preview.src = e.target.result;
                        previewContainer.style.display = 'block';
                      }

                      reader.readAsDataURL(input.files[0]);
                    } else {
                      preview.src = '';
                      previewContainer.style.display = 'none';
                    }
                  }
                </script>



                <script>
                  document.addEventListener("DOMContentLoaded", function() {
                    const partyContainer = document.getElementById("partyCheckboxesStarter");

                    // Fetch parties from the database
                    fetch("processes/elect/fetch_party.php")
                      .then(response => response.json())
                      .then(data => {
                        if (data.status === "error") {
                          console.error("Error:", data.message);
                          return;
                        }
                        data.forEach(party => {
                          const checkboxDiv = document.createElement("div");
                          checkboxDiv.classList.add("form-check");

                          const checkbox = document.createElement("input");
                          checkbox.type = "checkbox";
                          checkbox.classList.add("form-check-input");
                          checkbox.name = "parties[]";
                          checkbox.value = party.name;
                          checkbox.id = `party-${party.name.replace(/\s+/g, '-')}`;

                          const label = document.createElement("label");
                          label.classList.add("form-check-label");
                          label.htmlFor = checkbox.id;
                          label.textContent = party.name;

                          checkboxDiv.appendChild(checkbox);
                          checkboxDiv.appendChild(label);
                          partyContainer.appendChild(checkboxDiv);
                        });
                      })
                      .catch(error => console.error("Error fetching parties:", error));
                  });
                </script>


                <script>
                  document.addEventListener("DOMContentLoaded", function() {
                    // Select all edit buttons
                    document.querySelectorAll(".editBtnParty").forEach(button => {
                      button.addEventListener("click", function() {
                        const partyId = this.getAttribute("data-id"); // Get party ID from button

                        // Debugging: Check if ID is retrieved
                        console.log("Editing Party ID:", partyId);

                        if (!partyId) {
                          Swal.fire({
                            title: 'Error!',
                            text: 'Party ID is missing. Please try again.',
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                          });
                          return;
                        }

                        // Fetch party details using AJAX (Optional, if you need more details)
                        fetch(`processes/elect/get_party.php?id=${partyId}`)
                          .then(response => response.json())
                          .then(data => {
                            console.log("Party Data:", data); // Debugging

                            if (data.fetch_status === 'success') {
                              // Populate the modal form fields
                              document.getElementById('editPartyId').value = data.party.id;
                              document.getElementById('editPartyName').value = data.party.name;
                              document.getElementById('editStatus').value = data.party.status
                              console.log(data.party.party_image);
                              console.log(data.status);


                              // Set the selected election
                              const electionDropdown = document.getElementById('editElectionParty');
                              for (let option of electionDropdown.options) {
                                if (option.value === data.party.election_name) {
                                  option.selected = true;
                                  break;
                                }
                              }

                              // Dynamically set the "View Image" link and image preview
                              const partyImage = data.party.party_image;
                              const imagePath = `../uploads/${partyImage}`;



                              // Show the image in the preview container
                              const imagePreviewContainer = document.getElementById('imagePreviewContainer');
                              const imagePreview = document.getElementById('imagePreview');
                              imagePreview.src = imagePath; // Set the image preview source
                              imagePreviewContainer.style.display = 'block'; // Show the image preview container


                              // Show modal
                              const editModal = new bootstrap.Modal(document.getElementById('editPartyModal'));

                            } else {
                              Swal.fire({
                                title: 'Error!',
                                text: 'Party data could not be loaded.',
                                icon: 'error',
                                timer: 2000,
                                showConfirmButton: false
                              });
                            }
                          })
                          .catch(error => {
                            console.error("Fetch error:", error);
                            Swal.fire({
                              title: 'Error!',
                              text: 'Failed to load party details.',
                              icon: 'error',
                              timer: 2000,
                              showConfirmButton: false
                            });
                          });
                      });
                    });
                  });
                </script>

                <script>
                  $(document).ready(function() {
                    // Load election data into the modal when Edit is clicked
                    $('.edit-election').on('click', function() {
                      let electionId = $(this).data('id');

                      $.ajax({
                        url: 'processes/elect/fetch_election_by_id.php',
                        method: 'GET',
                        data: {
                          id: electionId
                        },
                        dataType: 'json',
                        success: function(response) {
                          if (response.status === 'success') {
                            let election = response.data;
                            $('#editElectionId').val(election.id);
                            $('#editElectionName').val(election.election_name);
                            $('#editSchoolYearStart').val(election.school_year_start);
                            $('#editSchoolYearEnd').val(election.school_year_end);
                            $('#editSemester').val(election.semester);
                            $('#editStartPeriod').val(election.start_period ? new Date(election.start_period).toISOString().slice(0, 16) : '');
                            $('#editEndPeriod').val(election.end_period ? new Date(election.end_period).toISOString().slice(0, 16) : '');
                            $('#editLevel').val(election.level);
                            $('#editStatus').val(election.status);
                          } else {
                            Swal.fire({
                              icon: 'error',
                              title: 'Error!',
                              text: response.message
                            });
                          }
                        },
                        error: function(xhr, status, error) {
                          console.log("AJAX Error:", xhr.status, status, error);
                          console.log("Response Text:", xhr.responseText);
                          Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to load election data due to a server error.'
                          });
                        }
                      });
                    });

                    $('#submitEditElection').on('click', function(e) {
                      e.preventDefault();

                      let formData = $('#editElectionForm').serialize();

                      $.ajax({
                        type: 'POST',
                        url: 'processes/elect/edit.php',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                          if (response.status === 'success') {
                            Swal.fire({
                              icon: 'success',
                              title: 'Success!',
                              text: response.message,
                              showCancelButton: true, // Add cancel button
                              confirmButtonText: 'OK',

                            }).then((result) => {
                              if (result.isConfirmed) {
                                // Only proceed if "OK" is clicked
                                $('#editElectionModal').modal('hide');
                                $('#editElectionForm')[0].reset();
                                location.reload();
                              }
                              // If "Stay" is clicked, do nothing (stay on page)
                            });
                          } else {
                            Swal.fire({
                              icon: 'error',
                              title: 'Error!',
                              text: response.message || 'Failed to update election'
                            });
                          }
                        },
                        error: function(xhr, status, error) {
                          console.log("AJAX Error:", xhr.status, status, error);
                          console.log("Response Text:", xhr.responseText);
                          Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Server error occurred while updating election'
                          });
                        }
                      });
                    });
                  });
                  const now = new Date();
                  const pad = num => String(num).padStart(2, '0');
                  const formatted = now.getFullYear() + '-' +
                    pad(now.getMonth() + 1) + '-' +
                    pad(now.getDate()) + 'T' +
                    pad(now.getHours()) + ':' +
                    pad(now.getMinutes());

                  document.getElementById('startPeriod').value = formatted;

                  // Add 1 month and 2 weeks
                  let future = new Date(now);
                  // future.setMonth(future.getMonth() + 1);
                  // future.setDate(future.getDate() + 14); // Add 2 weeks

                  const formattedFuture = future.getFullYear() + '-' +
                    pad(future.getMonth() + 1) + '-' +
                    pad(future.getDate()) + 'T' +
                    pad(future.getHours()) + ':' +
                    pad(future.getMinutes());

                  document.getElementById('endPeriod').value = formattedFuture;


                  window.addEventListener('DOMContentLoaded', () => {
                    const currentYear = new Date().getFullYear();
                    document.getElementById('schoolYearStart').value = currentYear;
                    document.getElementById('schoolYearEnd').value = currentYear + 1;

                    // Duration Logic
                    const startPeriodInput = document.getElementById('startPeriod');
                    const endPeriodInput = document.getElementById('endPeriod');
                    const radioStandard = document.getElementById('durationStandard');
                    const radioCustom = document.getElementById('durationCustom');

                    function updateEndPeriod(e) {
                      if (radioStandard.checked && startPeriodInput.value) {
                        const startDate = new Date(startPeriodInput.value);
                        const endDate = new Date(startDate.getTime() + 7 * 24 * 60 * 60 * 1000); // Add 7 days

                        // Format to YYYY-MM-DDTHH:mm for datetime-local input
                        const year = endDate.getFullYear();
                        const month = String(endDate.getMonth() + 1).padStart(2, '0');
                        const day = String(endDate.getDate()).padStart(2, '0');
                        const hours = String(endDate.getHours()).padStart(2, '0');
                        const minutes = String(endDate.getMinutes()).padStart(2, '0');

                        endPeriodInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                        endPeriodInput.readOnly = true;
                      } else if (radioCustom.checked) {
                        endPeriodInput.readOnly = false;
                        // If triggered by clicking the Custom radio button, set date to tomorrow
                        if (e && e.target === radioCustom) {
                          const today = new Date();
                          const tomorrow = new Date(today);
                          tomorrow.setDate(tomorrow.getDate() + 1);

                          const year = tomorrow.getFullYear();
                          const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
                          const day = String(tomorrow.getDate()).padStart(2, '0');
                          const hours = String(tomorrow.getHours()).padStart(2, '0');
                          const minutes = String(tomorrow.getMinutes()).padStart(2, '0');

                          endPeriodInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                        }
                      }
                    }

                    radioStandard.addEventListener('change', updateEndPeriod);
                    radioCustom.addEventListener('change', updateEndPeriod);
                    startPeriodInput.addEventListener('change', updateEndPeriod);

                    // Initial call to set state based on default checked radio
                    updateEndPeriod();
                  });

                  document.addEventListener('DOMContentLoaded', function() {
                    const academicYearSelect = document.getElementById('academicYear');
                    const startPeriodInput = document.getElementById('startPeriod');
                    const endPeriodInput = document.getElementById('endPeriod');

                    function validateElectionDates() {
                      const selectedOption = academicYearSelect.options[academicYearSelect.selectedIndex];
                      if (!selectedOption || !selectedOption.dataset.startDate) {
                        return;
                      }

                      const ayStartDate = selectedOption.dataset.startDate;
                      const ayEndDate = selectedOption.dataset.endDate;

                      startPeriodInput.min = ayStartDate.split(' ')[0] + 'T00:00';
                      startPeriodInput.max = ayEndDate.split(' ')[0] + 'T23:59';
                      endPeriodInput.min = ayStartDate.split(' ')[0] + 'T00:00';
                      endPeriodInput.max = ayEndDate.split(' ')[0] + 'T23:59';
                    }

                    academicYearSelect.addEventListener('change', validateElectionDates);

                    startPeriodInput.addEventListener('change', function() {
                      endPeriodInput.min = startPeriodInput.value;
                    });

                    // Edit Modal Validation
                    const editAcademicYearSelect = document.getElementById('editAcademicYear');
                    const editStartPeriodInput = document.getElementById('editStartPeriod');
                    const editEndPeriodInput = document.getElementById('editEndPeriod');
                    const editModal = document.getElementById('editElectionModal');

                    function validateEditElectionDates() {
                      const selectedOption = editAcademicYearSelect.options[editAcademicYearSelect.selectedIndex];
                      if (!selectedOption || !selectedOption.dataset.startDate) {
                        return;
                      }

                      const ayStartDate = selectedOption.dataset.startDate;
                      const ayEndDate = selectedOption.dataset.endDate;

                      editStartPeriodInput.min = ayStartDate.split(' ')[0] + 'T00:00';
                      editStartPeriodInput.max = ayEndDate.split(' ')[0] + 'T23:59';
                      editEndPeriodInput.min = ayStartDate.split(' ')[0] + 'T00:00';
                      editEndPeriodInput.max = ayEndDate.split(' ')[0] + 'T23:59';
                    }

                    editAcademicYearSelect.addEventListener('change', validateEditElectionDates);
                    editStartPeriodInput.addEventListener('change', function() {
                      editEndPeriodInput.min = editStartPeriodInput.value;
                    });
                    editModal.addEventListener('shown.bs.modal', validateEditElectionDates);
                  });
                </script>

                <!-- Single Edit Election Modal -->
                <div class='modal fade' id='editElectionModal' tabindex='-1' aria-labelledby='editElectionModalLabel' aria-hidden='true'>
                  <div class='modal-dialog'>
                    <div class='modal-content'>
                      <div class='modal-header'>
                        <h1 class='modal-title fs-5' id='editElectionModalLabel'>Edit Election Details</h1>
                        <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                      </div>
                      <div class='modal-body'>
                        <form id="editElectionForm">
                          <?php
                          // Generate CSRF token if not already set
                          if (empty($_SESSION['csrf_token'])) {
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                          }

                          // Fetch academic years for dropdown
                          $stmt = $pdo->query("SELECT id, year_label, semester, start_date, end_date FROM academic_years ORDER BY year_label DESC, semester DESC");
                          $academicYears = $stmt->fetchAll(PDO::FETCH_ASSOC);
                          ?>

                          <!-- CSRF Token -->
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                          <!-- Election ID -->
                          <input type="hidden" name="election_id" id="editElectionId">

                          <!-- Academic Year & Semester -->
                          <div class="mb-3">
                            <label class="form-label">Academic Year & Semester:</label>
                            <select class="form-select" name="academic_year_id" id="editAcademicYear" required>
                              <option value="">Select Academic Year</option>
                              <?php foreach ($academicYears as $ay): ?>
                                <option value="<?= $ay['id'] ?>" data-start-date="<?= $ay['start_date'] ?>" data-end-date="<?= $ay['end_date'] ?>">
                                  <?= htmlspecialchars($ay['year_label']) ?> - <?= htmlspecialchars($ay['semester']) ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>

                          <!-- Election Name -->
                          <div class="mb-3">
                            <label class="form-label">Election Name:</label>
                            <input type="text" class="form-control" name="election_name" id="editElectionName" required>
                          </div>

                          <div class="mb-3">
                            <label class="form-label">Duration Type:</label>
                            <div class="form-check">
                              <input class="form-check-input" type="radio" name="duration_type" id="durationStandard" value="standard" checked>
                              <label class="form-check-label" for="durationStandard">Standard (USC-Defined Duration) (Approximately 1 week after the election starts)</label>
                            </div>
                            <div class="form-check">
                              <input class="form-check-input" type="radio" name="duration_type" id="durationCustom" value="custom">
                              <label class="form-check-label" for="durationCustom">Custom Duration</label>
                            </div>
                          </div>


                          <!-- Start / End Period -->
                          <div class="mb-3">
                            <label class="form-label">Start Period:</label>
                            <input type="datetime-local" class="form-control" name="start_period" id="editStartPeriod" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">End Period: <small>(Usually 1 month and two weeks from now)</small></label>
                            <input type="datetime-local" class="form-control" name="end_period" id="editEndPeriod" required>
                          </div>

                          <!-- Status -->
                          <div class="mb-3">
                            <label class="form-label">Status:</label>
                            <select class="form-select" name="status" id="editStatus" required>
                              <option value="Ongoing">Ongoing</option>
                              <option value="Upcoming">Upcoming</option>
                              <option value="Published">Published</option>
                              <option value="TBD">To Be Deleted</option>
                              <option value="Ended">Ended</option>
                            </select>
                          </div>

                          <!-- Modal Footer -->
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="mdi mdi-close"></i> Close</button>
                            <button type="submit" class="btn btn-primary text-white" id="submitEditElection"><i class="mdi mdi-upload"></i> Save Changes</button>
                          </div>
                        </form>

                      </div>
                    </div>
                  </div>
                </div>

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

                <!-- Vendor Plugins -->
                <script src="vendors/js/vendor.bundle.base.js"></script>
                <script src="vendors/chart.js/Chart.min.js"></script>
                <script src="vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
                <script src="vendors/progressbar.js/progressbar.min.js"></script>
                <script src="vendors/jquery-3.6.0.min.js"></script>
                <script src="vendors/jquery.dataTables.min.js"></script>
                <script src="vendors/dataTables.bootstrap5.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
                <!-- <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
                <script src="vendors/jspdf.umd.min.js"></script> -->
                <script src="vendors/sweetalert2@11.js"></script>

                <!-- Custom Scripts -->
                <script src="js/off-canvas.js"></script>
                <script src="js/hoverable-collapse.js"></script>
                <script src="js/template.js"></script>
                <script src="js/settings.js"></script>
                <script src="js/todolist.js"></script>
                <script src="js/dashboard.js"></script>
                <script src="js/Chart.roundedBarCharts.js"></script>

                <script>
                  document.addEventListener('DOMContentLoaded', function() {
                    const levelSelect = document.getElementById('level');
                    const positionContainer = document.getElementById('positionFieldContainer');
                    const customPositionBtn = document.getElementById('customPositionBtn');
                    const collegeWrapper = document.getElementById('collegeRestrictionWrapper');

                    const centralPositions = ['President', 'Vice-President'];
                    const localPositions = ['Mayor', 'Vice-Mayor', 'Senator'];

                    let isCustom = false;

                    levelSelect.addEventListener('change', function() {
                      const level = levelSelect.value;
                      isCustom = false;

                      if (level === 'Central') {
                        renderDropdown(centralPositions);
                        collegeWrapper.style.display = 'none';
                        const box = document.getElementById('collegeCheckboxesAdd');
                        if (box) box.innerHTML = '';
                      } else {
                        renderDropdown(localPositions);
                        collegeWrapper.style.display = 'block';
                        renderCollegeDeptCheckboxes('collegeCheckboxesAdd', [], []);
                      }
                      customPositionBtn.style.display = 'inline-block';
                    });

                    customPositionBtn.addEventListener('click', function() {
                      renderTextInput();
                      isCustom = true;
                      customPositionBtn.style.display = 'none';
                    });

                    function renderDropdown(options) {
                      positionContainer.innerHTML = `
        <select class="form-control" id="position" name="position" required>
          ${options.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
        </select>
      `;
                    }

                    function renderTextInput() {
                      positionContainer.innerHTML = `
        <input type="text" class="form-control" id="position" name="position" required>
      `;
                    }

                    levelSelect.dispatchEvent(new Event('change'));
                  });
                </script>

                <style>
                  .college-checkbox-container {
                    max-height: 320px;
                    overflow-y: auto;
                    padding: 10px;
                    border: 1px solid #ced4da;
                    border-radius: 4px;
                  }

                  .college-checkbox-container .fw-semibold {
                    color: #333;
                  }

                  .college-checkbox-container .text-muted {
                    font-size: 0.875rem;
                  }
                </style>

                <script>
                  // College + Department data baked in from PHP at render time
                  const wmsuColleges = <?php
                                        try {
                                          $colStmt = $pdo->query(
                                            "SELECT c.college_id, c.college_name, c.college_abbreviation,
                                d.department_id, d.department_name
                         FROM   colleges c
                         LEFT JOIN departments d ON d.college_id = c.college_id
                         ORDER  BY c.college_name ASC, d.department_name ASC"
                                          );
                                          $colRows = $colStmt->fetchAll(PDO::FETCH_ASSOC);
                                          $colMap  = [];
                                          foreach ($colRows as $r) {
                                            $cid = $r['college_id'];
                                            if (!isset($colMap[$cid])) {
                                              $colMap[$cid] = [
                                                'id'          => (int)$cid,
                                                'name'        => $r['college_name'],
                                                'abbr'        => $r['college_abbreviation'],
                                                'departments' => []
                                              ];
                                            }
                                            if ($r['department_id']) {
                                              $colMap[$cid]['departments'][] = [
                                                'id'   => (int)$r['department_id'],
                                                'name' => $r['department_name']
                                              ];
                                            }
                                          }
                                          echo json_encode(array_values($colMap));
                                        } catch (Exception $e) {
                                          echo '[]';
                                        }
                                        ?>;

                  function renderCollegeDeptCheckboxes(containerId, selCollegeIds, selDeptIds) {
                    const container = document.getElementById(containerId);
                    if (!container) return;
                    container.innerHTML = '';

                    if (!wmsuColleges.length) {
                      container.innerHTML = '<p class="text-muted text-center mb-0">No colleges found.</p>';
                      return;
                    }

                    wmsuColleges.forEach(function(college) {
                      const isChecked = selCollegeIds.includes(college.id);
                      const wrap = document.createElement('div');
                      wrap.className = 'mb-2';

                      const cRow = document.createElement('div');
                      cRow.className = 'form-check';

                      const cBox = document.createElement('input');
                      cBox.type = 'checkbox';
                      cBox.className = 'form-check-input';
                      cBox.name = 'allowed_colleges[]';
                      cBox.value = college.id;
                      cBox.id = containerId + '-col-' + college.id;
                      cBox.checked = isChecked;

                      const cLbl = document.createElement('label');
                      cLbl.className = 'form-check-label fw-semibold';
                      cLbl.htmlFor = cBox.id;
                      cLbl.textContent = college.name + ' (' + college.abbr + ')';

                      cRow.appendChild(cBox);
                      cRow.appendChild(cLbl);
                      wrap.appendChild(cRow);

                      const deptWrap = document.createElement('div');
                      deptWrap.className = 'ps-4 mt-1';
                      deptWrap.id = containerId + '-depts-' + college.id;
                      deptWrap.style.display = isChecked ? 'block' : 'none';

                      (college.departments || []).forEach(function(dept) {
                        const dRow = document.createElement('div');
                        dRow.className = 'form-check';

                        const dBox = document.createElement('input');
                        dBox.type = 'checkbox';
                        dBox.className = 'form-check-input';
                        dBox.name = 'allowed_departments[]';
                        dBox.value = dept.id;
                        dBox.id = containerId + '-dept-' + dept.id;
                        dBox.checked = selDeptIds.includes(dept.id);

                        const dLbl = document.createElement('label');
                        dLbl.className = 'form-check-label text-muted';
                        dLbl.htmlFor = dBox.id;
                        dLbl.textContent = dept.name;

                        dRow.appendChild(dBox);
                        dRow.appendChild(dLbl);
                        deptWrap.appendChild(dRow);
                      });

                      wrap.appendChild(deptWrap);
                      container.appendChild(wrap);

                      cBox.addEventListener('change', function() {
                        deptWrap.style.display = this.checked ? 'block' : 'none';
                        if (!this.checked) {
                          deptWrap.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
                            cb.checked = false;
                          });
                        }
                      });
                    });
                  }
                </script>


                <?php if (isset($_SESSION['status'])): ?>
                  <script>
                    document.addEventListener('DOMContentLoaded', function() {
                      Swal.fire({
                        icon: <?php echo json_encode($_SESSION['status']['type'] === 'success' ? 'success' : 'error'); ?>,
                        title: <?php echo json_encode($_SESSION['status']['type'] === 'success' ? 'Success' : 'Error'); ?>,
                        text: <?php echo json_encode($_SESSION['status']['message']); ?>,
                        confirmButtonText: 'OK',
                        timer: 3000,
                        timerProgressBar: true
                      });
                    });

                    function openEditPositionModal(positionData) {
                      // Set the position ID
                      $('#editPositionId').val(positionData.id);

                      // Set the level first
                      $('#editLevel').val(positionData.level || 'Central');

                      // Use your existing applyEditLevel function to populate the position name
                      applyEditLevel(positionData.level || 'Central', positionData.name || '');

                      // Populate party checkboxes - adjust for your data structure
                      const checkboxesContainer = $('#editPartyCheckboxes');
                      checkboxesContainer.empty();

                      // Fetch parties and populate checkboxes
                      $.ajax({
                        url: 'processes/elect/fetch_party.php',
                        type: 'GET',
                        dataType: 'json',
                        success: function(partiesData) {
                          console.log('Fetched parties:', partiesData);

                          const parties = Array.isArray(partiesData) ? partiesData : (partiesData.parties || []);
                          if (!parties.length) {
                            checkboxesContainer.html('<p>No approved parties available</p>');
                            return;
                          }

                          // Convert assigned_parties to array of names if they're IDs
                          const currentParties = positionData.assigned_parties || [];
                          const currentPartyNames = currentParties.map(partyId => {
                            // If assigned_parties are IDs, find the corresponding party name
                            const party = parties.find(p => p.id === partyId || p.name === partyId);
                            return party ? party.name : partyId;
                          });

                          parties.forEach(party => {
                            const checkboxId = `edit-party-${party.name.replace(/\s+/g, '-')}`;
                            const checkboxDiv = $('<div>').addClass('form-check');

                            const checkbox = $('<input>').attr({
                              type: 'checkbox',
                              class: 'form-check-input',
                              name: 'parties[]',
                              value: party.name, // Use party name as value
                              id: checkboxId
                            });

                            const label = $('<label>')
                              .addClass('form-check-label')
                              .attr('for', checkboxId)
                              .text(party.name);

                            // Check if this party is currently assigned
                            if (currentPartyNames.includes(party.name)) {
                              checkbox.prop('checked', true);
                            }

                            checkboxDiv.append(checkbox).append(label);
                            checkboxesContainer.append(checkboxDiv);
                          });
                        },
                        error: function(xhr, status, error) {
                          console.error('Fetch parties error:', status, error);
                          checkboxesContainer.html('<p>Error loading parties</p>');
                        }
                      });

                      // Show the modal
                      $('#editPositionModal').modal('show');
                    }

                    // Handle the "Enter custom position" button
                    $('#editCustomPositionBtn').click(function() {
                      const currentValue = $('#editPositionFieldContainer select').val();
                      $('#editPositionFieldContainer').html(`<input type="text" class="form-control" name="position" value="${currentValue}" required>`);
                      $(this).hide();
                    });
                  </script>

                  <script>
                    // Event listener for the new edit buttons
                    document.addEventListener('click', function(event) {
                      if (event.target.closest('.editBtnPositionNew')) {
                        const button = event.target.closest('.editBtnPositionNew');
                        const positionId = button.getAttribute('data-id');
                        const partyName = button.getAttribute('data-party');

                        console.log('Editing position ID:', positionId, 'Party:', partyName);
                        populateEditPositionModal(positionId);
                      }
                    });

                    // Function to populate the edit modal
                    function populateEditPositionModal(positionId) {
                      console.log('Fetching data for position ID:', positionId);

                      // Set the hidden position ID and show modal
                      $('#editPositionId').val(positionId);
                      $('#editPositionModalNew').modal('show');

                      // Fetch position data from your PHP endpoint
                      fetch(`processes/elect/get_position.php?id=${positionId}`)
                        .then(response => response.json())
                        .then(data => {
                          console.log('Position data response:', data);

                          if (data.status === 'success') {
                            const position = data.position;

                            // Set the level
                            $('#editLevel').val(position.level || 'Central');

                            // Apply the level and position name to populate the dynamic field
                            applyEditLevel(position.level || 'Central', position.name || '');

                            // Fetch and populate parties
                            populatePartiesForPosition(position.id, position.party);

                          } else {
                            Swal.fire({
                              icon: 'error',
                              title: 'Error',
                              text: data.message || 'Failed to fetch position data'
                            });
                          }
                        })
                        .catch(error => {
                          console.error('Error fetching position data:', error);
                          Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load position data'
                          });
                        });
                    }

                    // Function to populate parties for a specific position
                    function populatePartiesForPosition(positionId, currentParty) {
                      const partyContainer = $('#editPartyCheckboxes');
                      partyContainer.empty();

                      // Fetch all approved parties
                      $.ajax({
                        url: 'processes/elect/fetch_party.php',
                        type: 'GET',
                        dataType: 'json',
                        success: function(partiesData) {
                          console.log('Fetched parties:', partiesData);

                          const parties = Array.isArray(partiesData) ? partiesData : (partiesData.parties || []);
                          if (!parties.length) {
                            partyContainer.html('<p>No approved parties available</p>');
                            return;
                          }

                          parties.forEach(party => {
                            const checkboxId = `edit-party-${party.name.replace(/\s+/g, '-')}`;
                            const checkboxDiv = $('<div>').addClass('form-check');

                            const checkbox = $('<input>').attr({
                              type: 'checkbox',
                              class: 'form-check-input',
                              name: 'parties[]',
                              value: party.name,
                              id: checkboxId
                            });

                            const label = $('<label>')
                              .addClass('form-check-label')
                              .attr('for', checkboxId)
                              .text(party.name);

                            // Check if this party matches the current position's party
                            if (party.name === currentParty) {
                              checkbox.prop('checked', true);
                            }

                            checkboxDiv.append(checkbox).append(label);
                            partyContainer.append(checkboxDiv);
                          });
                        },
                        error: function(xhr, status, error) {
                          console.error('Fetch parties error:', status, error);
                          partyContainer.html('<p>Error loading parties</p>');
                        }
                      });
                    }

                    // Form submission handler
                    $(document).ready(function() {
                      $('#positionFormEdit').on('submit', function(e) {
                        e.preventDefault();

                        // Get the position name from the dynamically created field
                        const positionNameField = document.querySelector('#editPositionFieldContainer input, #editPositionFieldContainer select');
                        const positionName = positionNameField ? positionNameField.value.trim() : '';
                        const selectedParties = $('input[name="parties[]"]:checked').length;

                        if (!positionName) {
                          Swal.fire({
                            icon: 'warning',
                            title: 'Validation Error',
                            text: 'Position name is required'
                          });
                          return;
                        }

                        if (selectedParties === 0) {
                          Swal.fire({
                            icon: 'warning',
                            title: 'Validation Error',
                            text: 'Please select at least one party'
                          });
                          return;
                        }

                        let formData = new FormData(this);

                        $.ajax({
                          url: 'processes/elect/update_position.php',
                          type: 'POST',
                          data: formData,
                          contentType: false,
                          processData: false,
                          dataType: 'json',
                          success: function(response) {
                            Swal.fire({
                              icon: response.status === 'success' ? 'success' : 'error',
                              title: response.status === 'success' ? 'Success' : 'Error',
                              text: response.message || 'Operation completed'
                            }).then(() => {
                              if (response.status === 'success') {
                                $('#editPositionModalNew').modal('hide');
                                location.reload();
                              }
                            });
                          },
                          error: function(xhr, status, error) {
                            Swal.fire({
                              icon: 'error',
                              title: 'Server Error',
                              text: 'Failed to update position: ' + error
                            });
                          }
                        });
                      });

                      // Clear modal when closed
                      $('#editPositionModalNew').on('hidden.bs.modal', function() {
                        $('#positionFormEdit')[0].reset();
                        $('#editPartyCheckboxes').empty();
                        $('#editPositionFieldContainer').empty();
                      });
                    });
                  </script>
                  <?php unset($_SESSION['status']); ?>
                <?php endif; ?>
                <?php
                include('includes/alerts.php');
                ?>
</body>
<button class="back-to-top" id="backToTop" title="Go to top">
  <i class="mdi mdi-arrow-up"></i>
</button>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    const pad = num => String(num).padStart(2, '0');
    const formattedDateTime = now.getFullYear() + '-' +
      pad(now.getMonth() + 1) + '-' +
      pad(now.getDate()) + 'T' +
      pad(now.getHours()) + ':' +
      pad(now.getMinutes());

    // For "Add Election Period" modal
    const startPeriod = document.getElementById('startPeriod');
    if (startPeriod) {
      startPeriod.min = formattedDateTime;
    }
    const endPeriod = document.getElementById('endPeriod');
    if (endPeriod) {
      endPeriod.min = formattedDateTime;
    }

    // For "Edit Election Details" modal
    const editStartPeriod = document.getElementById('editStartPeriod');
    if (editStartPeriod) {
      editStartPeriod.min = formattedDateTime;
    }
    const editEndPeriod = document.getElementById('editEndPeriod');
    if (editEndPeriod) {
      editEndPeriod.min = formattedDateTime;
    }
  });
</script>
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

</html>