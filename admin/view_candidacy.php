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
  <title>WMSU i-Elect Admin | Candidates </title>
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

      <?php
      // Get the current PHP file name
      $current_page = basename($_SERVER['PHP_SELF']);
      ?>

      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <?php
        // Get the current PHP file name
        $current_page = basename($_SERVER['PHP_SELF']);
        ?>
        <ul class="nav">
          <li class="nav-item <?php echo $current_page == 'index.php' ? 'active-link' : ''; ?>">
            <a class="nav-link <?php echo $current_page == 'index.php' ? 'active-link' : ''; ?>" href="index.php" <?php echo $current_page == 'index.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
              <i class="mdi mdi-grid-large menu-icon" <?php echo $current_page == 'index.php' ? 'style="color: white !important;"' : ''; ?>></i>
              <span class="menu-title" <?php echo $current_page == 'index.php' ? 'style="color: white !important;"' : ''; ?>>Index</span>
            </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'academic_info.php' ? 'active-link' : ''; ?>">
            <a class="nav-link <?php echo $current_page == 'academic_info.php' ? 'active-link' : ''; ?>" href="academic_info.php" <?php echo $current_page == 'academic_info.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
              <i class="menu-icon mdi mdi-school"
                <?= $current_page == 'academic_info.php' ? 'style="color: white !important;"' : ''; ?>>
              </i>
              <span class="menu-title"
                <?= $current_page == 'academic_info.php' ? 'style="color: white !important;"' : ''; ?>>
                Academic Year
              </span>

            </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'election.php' ? 'active-link' : ''; ?>">
            <a class="nav-link <?php echo $current_page == 'election.php' ? 'active-link' : ''; ?>" href="election.php" <?php echo $current_page == 'election.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
              <i class="menu-icon mdi mdi-vote" <?php echo $current_page == 'election.php' ? 'style="color: white !important;"' : ''; ?>></i>
              <span class="menu-title" <?php echo $current_page == 'election.php' ? 'style="color: white !important;"' : ''; ?>>Election</span>
            </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'candidacy.php' ? 'active-link' : ''; ?>">
            <a class="nav-link <?php echo $current_page == 'candidacy.php' ? 'active-link' : ''; ?>" href="candidacy.php" <?php echo $current_page == 'candidacy.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
              <i class="menu-icon mdi mdi-account-tie" <?php echo $current_page == 'candidacy.php' ? 'style="color: white !important;"' : ''; ?>></i>
              <span class="menu-title" <?php echo $current_page == 'candidacy.php' ? 'style="color: white !important;"' : ''; ?>>Candidacy</span>
            </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'events.php' ? 'active-link' : ''; ?>">
            <a class="nav-link <?php echo $current_page == 'events.php' ? 'active-link' : ''; ?>" href="events.php" <?php echo $current_page == 'events.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
              <i class="menu-icon mdi mdi-calendar" <?php echo $current_page == 'events.php' ? 'style="color: white !important;"' : ''; ?>></i>
              <span class="menu-title" <?php echo $current_page == 'events.php' ? 'style="color: white !important;"' : ''; ?>>Events</span>
            </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'emails.php' ? 'active-link' : ''; ?>">
            <a class="nav-link <?php echo $current_page == 'emails.php' ? 'active-link' : ''; ?>" href="emails.php" <?php echo $current_page == 'emails.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
              <i class="mdi mdi-email menu-icon" <?php echo $current_page == 'emails.php' ? 'style="color: white !important;"' : ''; ?>></i>
              <span class="menu-title" <?php echo $current_page == 'emails.php' ? 'style="color: white !important;"' : ''; ?>>Emails</span>
            </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'advisers.php' ? 'active-link' : ''; ?>">
            <a class="nav-link <?php echo $current_page == 'advisers.php' ? 'active-link' : ''; ?>" href="advisers.php" <?php echo $current_page == 'advisers.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
              <i class="mdi mdi-account-tie menu-icon" <?php echo $current_page == 'advisers.php' ? 'style="color: white !important;"' : ''; ?>></i>
              <span class="menu-title" <?php echo $current_page == 'advisers.php' ? 'style="color: white !important;"' : ''; ?>>Advisers</span>
            </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'voter-list.php' ? 'active-link' : ''; ?>">
            <a class="nav-link <?php echo $current_page == 'voter-list.php' ? 'active-link' : ''; ?>" href="voter-list.php" <?php echo $current_page == 'voter-list.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
              <i class="menu-icon mdi mdi-account-group" <?php echo $current_page == 'voter-list.php' ? 'style="color: white !important;"' : ''; ?>></i>
              <span class="menu-title" <?php echo $current_page == 'voter-list.php' ? 'style="color: white !important;"' : ''; ?>>Voter List</span>
            </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'moderators.php' ? 'active-link' : ''; ?>">
            <a class="nav-link <?php echo $current_page == 'moderators.php' ? 'active-link' : ''; ?>" href="moderators.php" <?php echo $current_page == 'moderators.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
              <i class="mdi mdi-pac-man menu-icon" <?php echo $current_page == 'moderators.php' ? 'style="color: white !important;"' : ''; ?>></i>
              <span class="menu-title" <?php echo $current_page == 'moderators.php' ? 'style="color: white !important;"' : ''; ?>>Moderators</span>
            </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'precincts.php' ? 'active-link' : ''; ?>">
            <a class="nav-link <?php echo $current_page == 'precincts.php' ? 'active-link' : ''; ?>" href="precincts.php" <?php echo $current_page == 'precincts.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
              <i class="mdi mdi-room-service menu-icon" <?php echo $current_page == 'precincts.php' ? 'style="color: white !important;"' : ''; ?>></i>
              <span class="menu-title" <?php echo $current_page == 'precincts.php' ? 'style="color: white !important;"' : ''; ?>>Precincts</span>
            </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'voting.php' ? 'active-link' : ''; ?>">
            <a class="nav-link <?php echo $current_page == 'voting.php' ? 'active-link' : ''; ?>" href="voting.php" <?php echo $current_page == 'voting.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
              <i class="menu-icon mdi mdi-ballot" <?php echo $current_page == 'voting.php' ? 'style="color: white !important;"' : ''; ?>></i>
              <span class="menu-title" <?php echo $current_page == 'voting.php' ? 'style="color: white !important;"' : ''; ?>>Voting</span>
            </a>
          </li>

          <li class="nav-item <?php echo $current_page == 'reports.php' ? 'active-link' : ''; ?>">
            <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active-link' : ''; ?>" href="reports.php" <?php echo $current_page == 'reports.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
              <i class="menu-icon mdi mdi-file-chart" <?php echo $current_page == 'reports.php' ? 'style="color: white !important;"' : ''; ?>></i>
              <span class="menu-title" <?php echo $current_page == 'reports.php' ? 'style="color: white !important;"' : ''; ?>>Reports</span>
            </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'history.php' ? 'active-link' : ''; ?>">
            <a class="nav-link <?php echo $current_page == 'history.php' ? 'active-link' : ''; ?>" href="history.php" <?php echo $current_page == 'history.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
              <i class="menu-icon mdi mdi-history" <?php echo $current_page == 'history.php' ? 'style="color: white !important;"' : ''; ?>></i>
              <span class="menu-title" <?php echo $current_page == 'history.php' ? 'style="color: white !important;"' : ''; ?>>History</span>
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
                              <h3><b>Candidacy Period
                                  <button class="button-view-primary" data-bs-toggle="modal"
                                    data-bs-target="#candidacyModal">
                                    <span style="font-size: 16px">
                                      <small><i class="mdi mdi-plus-circle"></i>
                                        Set Candidacy Period
                                      </small>
                                    </span>
                                  </button>
                                </b></h3>

                            </div>
                          </div>

                          <?php
                          $query = "
                            SELECT c.*, e.election_name, e.level, ay.year_label, ay.semester 
                            FROM candidacy c
                            JOIN elections e ON c.election_id = e.id
                            JOIN academic_years ay ON e.academic_year_id = ay.id
                            ORDER BY c.created_at DESC
                          ";
                          $stmt = $pdo->prepare($query);
                          $stmt->execute();
                          $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                          ?>

                          <table id="electionTable" class="table table-striped table-bordered nowrap"
                            style="width:100%">
                            <thead>
                              <tr>
                                <th>Election Name</th>
                                <th>Semester and School Year</th>
                                <th>Start Period</th>
                                <th>End Period</th>
                                <th>Status</th>
                                <th>Level</th>
                                <th>Action</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($elections as $election): ?>
                                <tr>
                                  <td><?= htmlspecialchars($election['election_name']) ?></td>
                                  <td>
                                    <?= htmlspecialchars($election['semester']) . ' ' . htmlspecialchars($election['year_label']) ?>
                                  </td>
                                  <td><?= date('F d, Y', strtotime($election['start_period'])) ?> at <br>
                                    <?= date('h:i A', strtotime($election['start_period'])) ?></td>
                                  <td><?= date('F d, Y', strtotime($election['end_period'])) ?> at <br>
                                    <?= date('h:i A', strtotime($election['end_period'])) ?></td>

                                  <td>
                                    <span
                                      class="badge <?= ($election['status'] == 'Ongoing') ? 'bg-success' : 'bg-warning' ?>">
                                      <?= htmlspecialchars($election['status']) ?>
                                    </span>
                                  </td>
                                  <td>
                                    <span>
                                      <?= htmlspecialchars($election['level'] ?? '') ?>
                                    </span>
                                  </td>
                                  <td>
                                    <button class="button-view-warning editBtn" data-id="<?= $election['id'] ?>"
                                      data-name="<?= $election['election_name'] ?>"
                                      data-start="<?= explode('-', $election['year_label'])[0] ?? '' ?>"
                                      data-end="<?= explode('-', $election['year_label'])[1] ?? '' ?>"
                                      data-semester="<?= $election['semester'] ?>"
                                      data-startperiod="<?= $election['start_period'] ?>"
                                      data-endperiod="<?= $election['end_period'] ?>"
                                      data-status="<?= $election['status'] ?>" data-bs-toggle="modal"
                                      data-bs-target="#editCandidacyModal">
                                      <i class="mdi mdi-pencil"></i> Edit
                                    </button>
                                    <button class="button-view-danger deleteBtnCandidacy" id="deleteBtnCandidacy"
                                      data-id="<?= $election['id'] ?>">
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

                    <div class="card card-rounded">
                      <div class="card-body">
                        <div class="row mt-4">
                          <div class="container  mb-5">
                            <div class="d-flex align-items-center">
                              <h3><b>Candidate List

                                </b></h3>

                            </div>
                          </div>

                          <table id="candidateTable" class="table table-striped table-bordered nowrap"
                            style="width:100%">
                            <thead>
                              <tr>
                                <th>ID</th>
                                <th>Candidate Name</th>
                                <th>College</th>
                                <th>Course</th>
                                <th>Division</th>

                                <th>Action</th>
                              </tr>
                            </thead>
                            <tbody>
                              <!-- Sample Data -->
                              <tr>
                                <td>CAND_0001</td>
                                <td>John Doe</td>
                                <td>
                                  College of Computing Studies
                                </td>
                                <td>
                                  Bachelor of Science in Inforamtion Technology
                                </td>
                                <td>Universal</td>

                                <td>
                                  <button class="button-view-primary"><i class="mdi mdi-eye"></i> View</button>


                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>





                    <!-- page-body-wrapper ends -->
                  </div>
                  <!-- container-scroller -->

                  <div class="modal fade" id="candidacyReqsModal" tabindex="-1" aria-labelledby="modalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="modalLabel">Create Candidacy</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <form id="candidacyReqsForm">
                            <!-- Candidacy Name -->
                            <?php
                            $stmt = $pdo->query("SELECT election_name FROM candidacy");
                            $candidacies = $stmt->fetchAll();


                            ?>

                            <div class="mb-3">
                              <label for="candidacyName" class="form-label">Candidacy Name</label>
                              <select class="form-control" id="candidacyName" name="candidacyName" required>
                                <option value="">Select a Candidacy</option>
                                <?php foreach ($candidacies as $candidacy): ?>
                                  <option value="<?= htmlspecialchars($candidacy['election_name']) ?>">
                                    <?= htmlspecialchars($candidacy['election_name']) ?>
                                  </option>
                                <?php endforeach; ?>
                              </select>
                            </div>


                            <!-- Dynamic Requirements Section -->
                            <div id="requirementsContainer">
                              <!-- Dynamic fields will be added here -->
                            </div>

                            <!-- Button to Add New Requirement -->
                            <button type="button" class="btn btn-success mt-2" onclick="addRequirement()">+ Add
                              Requirement</button>

                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                              <button type="submit" class="btn btn-primary">Save Candidacy</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Election Form Modal -->
                  <div class="modal fade" id="candidacyModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="exampleModalLabel">Election Form</h1>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <form id="candidacyForm">
                            <div class="mb-3">
                              <label for="electionName" class="form-label">Election Name:</label>
                              <select class="form-control" id="electionName" name="election_name" required>
                                <option value="">Select an Election</option>
                              </select>
                            </div>

                            <script>
                              $(document).ready(function() {
                                $.ajax({
                                  url: 'processes/elect/fetch_election_dropdown.php',
                                  method: 'GET',
                                  dataType: 'json',
                                  success: function(response) {
                                    if (response.error) {
                                      console.error(response.error);
                                      alert("Failed to fetch elections.");
                                      return;
                                    }

                                    if (response.message) {
                                      console.log(response.message); // "No elections found"
                                      return;
                                    }

                                    response.forEach(function(election) {
                                      $('#electionName').append(`<option value="${election.election_name}">${election.election_name}</option>`);
                                    });
                                  },
                                  error: function() {
                                    alert("Failed to fetch elections due to a server error.");
                                  }
                                });
                              });
                            </script>


                            <div class="mb-3">
                              <label for="schoolYear" class="form-label">School Year:</label>
                              <div class="d-flex gap-2">
                                <input type="number" class="form-control" id="schoolYearStart" name="school_year_start"
                                  placeholder="Start Year" required>
                                <input type="number" class="form-control" id="schoolYearEnd" name="school_year_end"
                                  placeholder="End Year" required>
                              </div>
                            </div>

                            <div class="mb-3">
                              <label for="semester" class="form-label">Semester:</label>
                              <select class="form-select" id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                              </select>
                            </div>

                            <div class="mb-3">
                              <label for="startPeriod" class="form-label">Start Period:</label>
                              <input type="datetime-local" class="form-control" id="startPeriod" name="start_period"
                                required>
                            </div>

                            <div class="mb-3">
                              <label for="endPeriod" class="form-label">End Period:</label>
                              <input type="datetime-local" class="form-control" id="endPeriod" name="end_period"
                                required>
                            </div>


                            <div class="mb-3">
                              <label for="application" class="form-label">Level</label>
                              <select class="form-select" id="level" name="level" required>
                                <option value="" disabled selected>Select Application Status and Department</option>
                                <option value="Universal">Universal</option>
                                <option value="College">College</option>
                                <option value="Department">Department</option>
                              </select>
                            </div>

                            <div class="mb-3">
                              <label for="status" class="form-label">Status:</label>
                              <select class="form-select" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Upcoming">Upcoming</option>
                              </select>
                            </div>
                          </form>
                        </div>

                        <div class="modal-footer">
                          <button type="button" class="button-view-secondary" data-bs-dismiss="modal"><i
                              class="mdi mdi-close"></i> Close</button>
                          <button type="submit" class="button-view-primary" id="submitCandidacy"><i
                              class="mdi mdi-upload"></i> Save changes</button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="modal fade" id="editCandidacyModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="exampleModalLabel">Election Form</h1>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <form id="editCandidacyForm" method="POST">
                            <input type="hidden" id="electionId" name="id"> <!-- Hidden Input for ID -->

                            <div class="mb-3">
                              <label for="electionName" class="form-label">Election Name:</label>
                              <input type="text" class="form-control" id="electionName" name="election_name" required>
                            </div>

                            <div class="mb-3">
                              <label for="schoolYear" class="form-label">School Year:</label>
                              <div class="d-flex gap-2">
                                <input type="number" class="form-control" id="schoolYearStart" name="school_year_start"
                                  placeholder="Start Year" required>
                                <input type="number" class="form-control" id="schoolYearEnd" name="school_year_end"
                                  placeholder="End Year" required>
                              </div>
                            </div>

                            <div class="mb-3">
                              <label for="semester" class="form-label">Semester:</label>
                              <select class="form-select" id="semester" name="semester" required>
                                <option value>Select Semester</option>
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                              </select>
                            </div>

                            <div class="mb-3">
                              <label for="startPeriod" class="form-label">Start Period:</label>
                              <input type="datetime-local" class="form-control" id="startPeriod" name="start_period"
                                required>
                            </div>

                            <div class="mb-3">
                              <label for="endPeriod" class="form-label">End Period:</label>
                              <input type="datetime-local" class="form-control" id="endPeriod" name="end_period"
                                required>
                            </div>

                            <div class="mb-3">
                              <label for="status" class="form-label">Status:</label>
                              <select class="form-select" id="status" name="status" required>
                                <option value>Select Status</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Upcoming">Upcoming</option>
                                <option value="Finished">Finished</option>
                                <option value="Rescheduled">Rescheduled</option>
                                <option value="Cancelled">Cancelled</option>
                              </select>
                            </div>

                            <!-- Move the buttons inside the form -->
                            <div class="modal-footer">
                              <button type="button" class="button-view-secondary" data-bs-dismiss="modal">
                                <i class="mdi mdi-close"></i> Close
                              </button>
                              <button type="submit" class="button-view-primary" id="submitEditCandidacy">
                                <i class="mdi mdi-upload"></i> Save changes
                              </button>
                            </div>

                          </form> <!-- Closing form tag moved here -->
                        </div>
                      </div>
                    </div>
                  </div>


                  <div class="modal fade " id="requirementsModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="exampleModalLabel">Viewing Requirements</h1>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <div class="row text-center">
                            <div class="col">
                              <h5><b>Certificate of Candidacy</b></h5>
                              <img src="../external/img/371327515_775043391085332_3242171381045286946_n (1).jpg"
                                class="img-fluid">
                            </div>
                            <div class="col">
                              <h5><b>Certificate of Registration</b></h5>
                              <img src="../external/img/371327515_775043391085332_3242171381045286946_n (1).jpg"
                                class="img-fluid">
                            </div>
                            <div class="col">
                              <h5><b>Candidate's Platforms</b></h5>
                              <img src="../external/img/371327515_775043391085332_3242171381045286946_n (1).jpg"
                                class="img-fluid">
                            </div>
                            <div class="col">
                              <h5><b>Party's Platforms</b></h5>
                              <img src="../external/img/371327515_775043391085332_3242171381045286946_n (1).jpg"
                                class="img-fluid">
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="modal fade" id="portfolioModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="exampleModalLabel">Candidate Profile</h1>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <div class="row">
                            <!-- Candidate's Picture -->
                            <div class="col ">
                              <img src="../external/img/371327515_775043391085332_3242171381045286946_n (1).jpg"
                                class="img-fluid">
                              <br><br>
                              <p><b>Candidate's Name Picture</b></p>
                            </div>
                            <!-- Candidate Details -->
                            <div class="col">
                              <p><b>First Name:</b> Test </p>
                              <p><b>Middle Name:</b> Test </p>
                              <p><b>Last Name:</b> Test </p>
                            </div>
                            <div class="col">
                              <p><b>Course:</b> BS Information Technology </p>
                              <p><b>College:</b> College of Computing Studies </p>
                            </div>
                            <div class="col">
                              <p><b>Party:</b> USP</p>
                              <p><b>Candidate Division:</b> Local</p>
                              <p><b>Candidate Position:</b> Senator</p>
                            </div>
                          </div>

                          <div class="row mt-5">
                            <div class="col">
                              <!-- Skills -->
                              <h5><b>Skills</b></h5>
                              <ul>
                                <li>Problem-Solving</li>
                                <li>Event Planning</li>
                                <li>Adaptability</li>
                                <li>Patience</li>
                              </ul>

                              <!-- Clubs & Organization -->
                              <h5 class="mt-5"><b>Clubs & Organization</b></h5>
                              <ul>
                                <li>Venom Publication (2021 - 2024) - Writer</li>
                                <li>PSITS (Present) - Member</li>
                              </ul>
                            </div>

                            <!-- Platform -->
                            <div class="col">
                              <h5><b>Platform</b></h5>
                              <p>Lorem ipsum dolor sit amet, consectetur
                                adipisicing elit. Blanditiis, omnis molestiae?
                                Excepturi a recusandae
                                libero dolorem expedita at error accusantium
                                repellendus distinctio fuga cupiditate, quisquam
                                doloribus
                                officia impedit illo eius?</p>
                            </div>
                          </div>
                        </div>

                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- jQuery (Required) -->
                  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

                  <!-- DataTables CSS -->
                  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

                  <!-- DataTables JS -->
                  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

                  <script>
                    $(document).ready(function() {
                      var electionTable = $('#electionTable').DataTable({
                        responsive: true,
                        paging: true,
                        searching: true,
                        ordering: true,
                        info: true
                      });
                      $('#semesterFilter').on('change', function() {
                        var selectedYear = this.value;
                        electionTable.columns(2).search(selectedYear).draw(); // 2 is the index for the "Semester" column
                      });

                    });
                    $(document).ready(function() {
                      var candidateTable = $('#candidateTable').DataTable({
                        responsive: true,
                        paging: true,
                        searching: true,
                        ordering: true,
                        info: true
                      });
                    });
                  </script>

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

</body>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Include jQuery and SweetAlert2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  $(document).ready(function() {
    $("#submitCandidacy").click(function(e) {
      e.preventDefault(); // Prevent default form submission

      let formData = $("#candidacyForm").serialize(); // Get form data

      $.ajax({
        type: "POST",
        url: "processes/candidacy/save.php", // Backend PHP script
        data: formData,
        success: function(response) {
          Swal.fire({
            title: "Success!",
            text: response,
            icon: "success",
            confirmButtonText: "OK"
          }).then(() => {
            $("#candidacyModal").modal("hide"); // Close modal on success
            $("#candidacyForm")[0].reset(); // Clear form
            location.reload();
          });
        },
        error: function() {
          Swal.fire({
            title: "Error!",
            text: "Something went wrong. Please try again.",
            icon: "error",
            confirmButtonText: "OK"
          });
        }
      });
    });
  });

  $(document).ready(function() {
    $(".editBtn").click(function() {
      // Get data attributes from the clicked button
      let id = $(this).data("id");
      let name = $(this).data("name");
      let startYear = $(this).data("start");
      let endYear = $(this).data("end");
      let semester = $(this).data("semester");
      let startPeriod = $(this).data("startperiod");
      let endPeriod = $(this).data("endperiod");
      let status = $(this).data("status");

      // Populate the modal form fields
      $("#editCandidacyModal #electionId").val(id);
      $("#editCandidacyModal #electionName").val(name);
      $("#editCandidacyModal #schoolYearStart").val(startYear);
      $("#editCandidacyModal #schoolYearEnd").val(endYear);
      $("#editCandidacyModal #semester").val(semester);
      $("#editCandidacyModal #startPeriod").val(startPeriod);
      $("#editCandidacyModal #endPeriod").val(endPeriod);
      $("#editCandidacyModal #status").val(status);
    });
  });

  $(document).ready(function() {
    $("#submitEditCandidacy").click(function(e) {
      e.preventDefault(); // Prevent default form submission

      let formData = $("#editCandidacyForm").serialize(); // Get form data

      $.ajax({
        type: "POST",
        url: "processes/candidacy/update.php", // Backend PHP script
        data: formData,
        success: function(response) {
          Swal.fire({
            icon: "success",
            title: "Success!",
            text: response
          }).then(() => {
            $("#editCandidacyModal").modal("hide"); // Close modal on success
            $("#editCandidacyForm")[0].reset(); // Reset form
            location.reload(); // Refresh the page
          });
        },
        error: function() {
          Swal.fire({
            icon: "error",
            title: "Error!",
            text: "Something went wrong. Please try again."
          });
        }
      });
    });
  });

  $(document).ready(function() {
    $("#semesterFilter").on("change", function() {
      let selectedSemester = $(this).val().toLowerCase(); // Get selected semester value

      $("#electionTable tbody tr").each(function() {
        let rowSemester = $(this).find("td:nth-child(1)").text().toLowerCase().trim(); // Get semester from row

        if (selectedSemester === "" || rowSemester === selectedSemester) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    });
  });

  $(document).ready(function() {
    $(".deleteBtnCandidacy").on("click", function() {
      let electionId = $(this).data("id"); // Get election ID from button

      Swal.fire({
        title: "Are you sure?",
        text: "This action cannot be undone!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!"
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            type: "POST",
            url: "processes/candidacy/delete.php", // Backend PHP script
            data: {
              id: electionId
            },
            success: function(response) {
              Swal.fire({
                icon: "success",
                title: "Deleted!",
                text: response
              }).then(() => {
                location.reload(); // Refresh page after deletion
              });
            },
            error: function() {
              Swal.fire({
                icon: "error",
                title: "Error!",
                text: "Something went wrong. Please try again."
              });
            }
          });
        }
      });
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

  // Handle Form Submission
  document.getElementById("candidacyReqsForm").addEventListener("submit", function(event) {
    event.preventDefault();

    let candidacyName = document.getElementById("candidacyName").value;
    let requirementTitles = document.getElementsByName("requirementTitle[]");
    let requirementTypes = document.getElementsByName("requirementType[]");

    let requirements = [];
    for (let i = 0; i < requirementTitles.length; i++) {
      requirements.push({
        title: requirementTitles[i].value,
        type: requirementTypes[i].value
      });
    }

    let formData = new FormData();
    formData.append("candidacyName", candidacyName);
    formData.append("requirements", JSON.stringify(requirements));

    fetch("processes/candidacy/save_requirements.php", {
        method: "POST",
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        alert("Requirements saved successfully!");
        console.log(data);
        document.getElementById("candidacyReqsForm").reset();
        document.getElementById("requirementsContainer").innerHTML = "";
      })
      .catch(error => console.error("Error:", error));
  });
</script>

<button class="back-to-top" id="backToTop" title="Go to top">
  <i class="mdi mdi-arrow-up"></i>
</button>



</html>