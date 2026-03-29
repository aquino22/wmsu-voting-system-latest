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
  <title>WMSU i-Elect Admin | Candidacy </title>
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
              $stmt = $pdo->prepare("SELECT * FROM elections WHERE status = :status");
              $stmt->execute(['status' => 'Ongoing']);
              $ongoingElections = $stmt->fetchAll(PDO::FETCH_ASSOC);

              if ($ongoingElections) {

                // Show first election
                $first = array_shift($ongoingElections);
                echo "<b>Election: </b> " . $first['election_name'] . " | ";
                echo "<b>Semester: </b> " . $first['semester'] . " | ";
                echo "<b>School Year:</b> " . $first['school_year_start'] . " - " . $first['school_year_end'] . "<br>";

                if ($ongoingElections) {
                  echo '<div id="moreElections" style="display:none;">';
                  foreach ($ongoingElections as $election) {
                    echo "<b>Election: </b> " . $election['election_name'] . " | ";
                    echo "<b>Semester:</b> " . $election['semester'] . " | ";
                    echo "<b>School Year:</b> " . $election['school_year_start'] . " - " . $election['school_year_end'] . "<br><br>";
                  }
                  echo '</div>';
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
              });
            </script>

            <script>
              // Back to Top Button Functionality
              document.addEventListener('DOMContentLoaded', function() {
                const backToTopButton = document.getElementById('backToTop');

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
          <li class="nav-item <?php echo $current_page == 'view_sent_qrs.php' ? 'active-link' : ''; ?>">
            <a class="nav-link <?php echo $current_page == 'view_sent_qrs.php' ? 'active-link' : ''; ?>" href="view_sent_qrs.php" <?php echo $current_page == 'view_sent_qrs.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
              <i class="menu-icon mdi mdi-qrcode" <?php echo $current_page == 'view_sent_qrs.php' ? 'style="color: white !important;"' : ''; ?>></i>
              <span class="menu-title" <?php echo $current_page == 'view_sent_qrs.php' ? 'style="color: white !important;"' : ''; ?>>Sent QR Codes</span>
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

                                </b></h3>

                              <div class="ms-auto">
                                <button class="btn btn-primary text-white" data-bs-toggle="modal"
                                  data-bs-target="#candidacyModal">

                                  <small><i class="mdi mdi-plus-circle"></i>
                                    Set Candidacy Period
                                  </small>

                                </button>
                              </div>
                            </div>
                          </div>

                          <?php
                          $query = "
  SELECT c.*, e.id AS event_id, e.event_title, e.status AS event_status 
    FROM candidacy c
    LEFT JOIN events e ON c.election_name = e.candidacy
    ORDER BY c.created_at DESC
";

                          $stmt = $pdo->prepare($query);
                          $stmt->execute();
                          $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
                          ?>
                          <div class="table-responsive">
                            <table id="electionTable" class="table table-striped table-bordered nowrap" style="width:100%">
                              <thead>
                                <tr>
                                  <th>Election Name</th>
                                  <th>Semester</th>
                                  <th>School Year Start</th>
                                  <th>School Year End</th>
                                  <th>Start Period</th>
                                  <th>End Period</th>
                                  <th>Status</th>
                                  <th>Action</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($elections as $election): ?>
                                  <tr>
                                    <td><?= htmlspecialchars($election['election_name']) ?></td>
                                    <td><?= htmlspecialchars($election['semester']) ?></td>
                                    <td><?= htmlspecialchars($election['school_year_start']) ?></td>
                                    <td><?= htmlspecialchars($election['school_year_end']) ?></td>
                                    <td><?= date('F d, Y', strtotime($election['start_period'])) ?> at <br>
                                      <?= date('h:i A', strtotime($election['start_period'])) ?>
                                    </td>
                                    <td><?= date('F d, Y', strtotime($election['end_period'])) ?> at <br>
                                      <?= date('h:i A', strtotime($election['end_period'])) ?>
                                    </td>
                                    <td>
                                      <span class="badge <?= ($election['status'] == 'Ongoing') ? 'bg-success' : 'bg-warning' ?>">
                                        <?= htmlspecialchars($election['status']) ?>
                                      </span>
                                    </td>
                                    <td>
                                      <?php
                                      $status = strtolower($election['status']); // normalize
                                      $isLocked = in_array($status, ['published']);
                                      ?>

                                      <?php if (!empty($election['event_id'])): ?>
                                        <a href="view_participants_admin.php?id=<?= $election['event_id'] ?>" class="button-view-primary" style="text-decoration: none;">
                                          <i class="mdi mdi-eye"></i> View Candidates
                                        </a>
                                      <?php else: ?>
                                        <span class="button-view-primary text-white" style="margin-right: 5px;">
                                          <i class="mdi mdi-stop"></i> No Registration, Yet
                                        </span>
                                      <?php endif; ?>

                                      <?php if ($isLocked): ?>
                                        <a href='history.php'
                                          class='button-view-warning' style="margin-left: 5px">
                                          <i class='mdi mdi-poll'></i> View Results
                                        </a>
                                      <?php else: ?>
                                        <!-- Normal editable buttons -->
                                        <button class="button-view-warning editBtn"
                                          data-id="<?= $election['id'] ?>"
                                          data-name="<?= $election['election_name'] ?>"
                                          data-start="<?= $election['school_year_start'] ?>"
                                          data-end="<?= $election['school_year_end'] ?>"
                                          data-semester="<?= $election['semester'] ?>"
                                          data-startperiod="<?= $election['start_period'] ?>"
                                          data-endperiod="<?= $election['end_period'] ?>"
                                          data-status="<?= $election['status'] ?>"
                                          data-bs-toggle="modal"
                                          data-bs-target="#editCandidacyModal">
                                          <i class="mdi mdi-pencil"></i> Edit
                                        </button>

                                        <button class="button-view-danger deleteBtnCandidacy"
                                          data-id="<?= $election['id'] ?>">
                                          <i class="mdi mdi-delete"></i> Delete
                                        </button>
                                      <?php endif; ?>
                                    </td>

                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>


                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- page-body-wrapper ends -->
                  </div>
                  <!-- container-scroller -->
                  <!-- Election Form Modal -->
                  <div class="modal fade" id="candidacyModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="exampleModalLabel">Candidacy Form</h1>
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

                            <div class="mb-2">
                              <small id="electionScheduleInfo" class="text-muted fst-italic d-block"></small>
                            </div>
                            <script>
                              let electionsData = [];

                              $(document).ready(function() {

                                // ── 1. Load elections ──────────────────────────────────────
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

                                    electionsData = response; // <-- 🔑 keep the data

                                    response.forEach(function(election) {
                                      $('#electionName').append(
                                        `<option value="${election.election_name}">
               ${election.election_name}
             </option>`
                                      );
                                    });
                                  },
                                  error: function() {
                                    alert("Failed to fetch elections due to a server error.");
                                  }
                                });

                                // ── 2. Show schedule when an election is chosen ────────────
                                $('#electionName').on('change', function() {
                                  const selectedName = $(this).val();
                                  const info = electionsData.find(e => e.election_name === selectedName);

                                  if (info) {
                                    const start = new Date(info.start_period.replace(' ', 'T'));
                                    const end = new Date(info.end_period.replace(' ', 'T'));

                                    const options = {
                                      year: 'numeric',
                                      month: 'long',
                                      day: 'numeric',
                                      hour: '2-digit',
                                      minute: '2-digit',
                                      hour12: true
                                    };

                                    $('#electionScheduleInfo').text(
                                      `Election period: ${start.toLocaleString([], options)} – ${end.toLocaleString([], options)}`
                                    );
                                  } else {
                                    $('#electionScheduleInfo').text('');
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
                              <label for="startPeriod" class="form-label">Start Period: <small>(automatically 1 week from the election's start period)</small></label>
                              <input type="datetime-local" class="form-control" id="startPeriod" name="start_period"
                                required>
                            </div>

                            <div class="mb-3">
                              <label for="endPeriod" class="form-label">End Period:</label>
                              <input type="datetime-local" class="form-control" id="endPeriod" name="end_period"
                                required>
                            </div>




                          </form>
                        </div>

                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                              class="mdi mdi-close"></i> Close</button>
                          <button type="submit" class="btn btn-primary text-white" id="submitCandidacy"><i
                              class="mdi mdi-upload"></i> Save changes</button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <script>
                    $('#electionName').on('change', function() {
                      let selectedElection = $(this).val(); // Get the selected election name

                      if (selectedElection === "") {
                        // If no election is selected, reset the fields
                        $('#schoolYearStart').val('');
                        $('#schoolYearEnd').val('');
                        $('#semester').val('');
                        $('#startPeriod').val('');
                        $('#endPeriod').val('');
                        return;
                      }

                      // Fetch the election details via AJAX
                      $.ajax({
                        url: 'processes/candidacy/fetch_election_details.php',
                        method: 'GET',
                        data: {
                          election_name: selectedElection
                        },
                        dataType: 'json',
                        success: function(response) {
                          if (response.status === 'success') {
                            const data = response.data;

                            // Populate fields
                            $('#schoolYearStart').val(data.school_year_start);
                            $('#schoolYearEnd').val(data.school_year_end);
                            $('#semester').val(data.semester);

                            // Parse start_period and add 1 week
                            if (data.start_period) {
                              const startDate = new Date(data.start_period);
                              startDate.setDate(startDate.getDate() + 7); // Add 7 days

                              const pad = num => String(num).padStart(2, '0');
                              const formatted = startDate.getFullYear() + '-' +
                                pad(startDate.getMonth() + 1) + '-' +
                                pad(startDate.getDate()) + 'T' +
                                pad(startDate.getHours()) + ':' +
                                pad(startDate.getMinutes());

                              $('#startPeriod').val(formatted);
                            }


                            let endDate = new Date(data.start_period);
                            endDate.setMonth(endDate.getMonth() + 1);
                            endDate.setDate(endDate.getDate() + 14);

                            $('#endPeriod').val(formattedEnd);

                          } else {
                            alert("Failed to fetch election details: " + response.message);
                            $('#schoolYearStart').val('');
                            $('#schoolYearEnd').val('');
                            $('#semester').val('');
                            $('#startPeriod').val('');
                            $('#endPeriod').val('');
                          }
                        },
                        error: function(xhr, status, error) {
                          console.log("AJAX Error:", xhr.status, status, error);
                          console.log("Response Text:", xhr.responseText);
                          alert("Failed to fetch election details due to a server error.");
                        }
                      });
                    });
                  </script>



                  <div class="modal fade" id="editCandidacyModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="exampleModalLabel">Editing Candidacy Period</h1>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <form id="editCandidacyForm" method="POST">
                            <input type="hidden" id="electionId" name="id"> <!-- Hidden Input for ID -->

                            <div class="mb-3">
                              <label for="electionNameEdit" class="form-label">Election Name:</label>
                              <select class="form-control" id="electionNameEdit" name="election_name" required disabled>

                              </select>
                              <small id="electionScheduleInfoEdit"
                                class="text-muted fst-italic d-block mb-2"></small>
                              <!-- keep the hidden input for pre‑selection -->
                              <input type="hidden" id="preSelectedElection"
                                value="<?php echo htmlspecialchars($preSelectedElection ?? ''); ?>">
                            </div>

                            <script>
                              $(document).ready(function() {

                                // ===== vars =====
                                let electionsMeta = []; // 🔑 keep full meta
                                const $electionSel = $('#electionNameEdit');
                                const preSelected = $('#preSelectedElection').val() || '';

                                // ===== 1. load elections for the dropdown =====
                                $.ajax({
                                  url: 'processes/elect/fetch_election_dropdown.php',
                                  method: 'GET',
                                  dataType: 'json',
                                  success: function(response) {
                                    if (response.error) {
                                      alert('Failed to fetch elections');
                                      return;
                                    }
                                    if (response.message) {
                                      console.log(response.message);
                                      return;
                                    }

                                    electionsMeta = response; // 🔑 store for later


                                    response.forEach(e =>
                                      $electionSel.append(`<option value="${e.election_name}">${e.election_name}</option>`));

                                    if (preSelected) {
                                      $electionSel.val(preSelected).trigger('change');
                                    }
                                  },
                                  error: () => alert('Server error loading elections')
                                });

                                // ===== 2. helper to show period =====
                                function showSchedule(name) {
                                  const info = electionsMeta.find(e => e.election_name === name);
                                  if (!info) {
                                    $('#electionScheduleInfoEdit').text('');
                                    return;
                                  }

                                  // Ensure proper ISO format then format for display
                                  const start = new Date(info.start_period.replace(' ', 'T'));
                                  const end = new Date(info.end_period.replace(' ', 'T'));
                                  const opts = {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: true
                                  };

                                  $('#electionScheduleInfoEdit').text(
                                    `Election period: ${start.toLocaleString([], opts)} – ${end.toLocaleString([], opts)}`
                                  );
                                }

                                // ===== 3. when an election is chosen =====
                                $electionSel.on('change', function() {
                                  const selected = $(this).val();
                                  showSchedule(selected); // 🔍 display‑only

                                  // --- your existing code to fill school year / semester ---
                                  if (!selected) {
                                    $('#schoolYearStart, #schoolYearEnd').val('');
                                    $('#semester').val('');
                                    return;
                                  }
                                  $.ajax({
                                    url: 'processes/candidacy/fetch_election_details.php',
                                    method: 'GET',
                                    data: {
                                      election_name: selected
                                    },
                                    dataType: 'json',
                                    success: function(r) {
                                      if (r.status === 'success') {
                                        $('#schoolYearStart').val(r.data.school_year_start);
                                        $('#schoolYearEnd').val(r.data.school_year_end);
                                        $('#semester').val(r.data.semester);
                                      } else {
                                        alert('Failed to fetch election details: ' + r.message);
                                      }
                                    },
                                    error: () => alert('Server error fetching election details')
                                  });
                                });

                                // ===== 4. if a pre‑selected election came from PHP, show its schedule =====
                                if (preSelected) {
                                  showSchedule(preSelected);
                                }

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
                                <option value>Select Semester</option>
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                              </select>
                            </div>

                            <div class="mb-3">
                              <label for="startPeriod" class="form-label">Start Period: <small>(automatically 1 week from the election's start period)</small></label>
                              <input type="datetime-local" class="form-control" id="editStartPeriod" name="start_period"
                                required>
                            </div>

                            <div class="mb-3">
                              <label for="endPeriod" class="form-label">End Period:</label>
                              <input type="datetime-local" class="form-control" id="editEndPeriod" name="end_period"
                                required>
                            </div>

                            <div class="mb-3">
                              <label for="status" class="form-label">Status:</label>
                              <select class="form-select" id="status" name="status" required>
                                <option value>Select Status</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Upcoming">Upcoming</option>
                                <option value="Published">Published</option>
                                <option value="Ended">Ended</option>

                              </select>
                            </div>

                            <!-- Move the buttons inside the form -->
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="mdi mdi-close"></i> Close
                              </button>
                              <button type="submit" class="btn btn-primary text-white" id="submitEditCandidacy">
                                <i class="mdi mdi-upload"></i> Save changes
                              </button>
                            </div>

                          </form> <!-- Closing form tag moved here -->
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

<button class="back-to-top" id="backToTop" title="Go to top">
  <i class="mdi mdi-arrow-up"></i>
</button>

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
        dataType: "json", // Expect JSON response
        success: function(response) {
          if (response.success) {
            Swal.fire({
              title: "Success!",
              text: "You have succesfully added the candidacy period!", // Use message from PHP
              icon: "success",
              confirmButtonText: "OK"
            }).then(() => {
              $("#candidacyModal").modal("hide"); // Close modal
              $("#candidacyForm")[0].reset(); // Clear form
              location.reload(); // Reload page
            });
          } else {
            Swal.fire({
              title: "Error!",
              text: response.message, // Use message from PHP
              icon: "error",
              confirmButtonText: "OK"
            });
          }
        },
        error: function(xhr, status, error) {
          Swal.fire({
            title: "Error!",
            text: "Something went wrong. Please try again. (Server error: " + xhr.status + ")",
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
      $("#editCandidacyModal #schoolYearStart").val(startYear);
      $("#editCandidacyModal #schoolYearEnd").val(endYear);
      $("#editCandidacyModal #semester").val(semester);
      $("#editCandidacyModal #editStartPeriod").val(startPeriod);
      $("#editCandidacyModal #editEndPeriod").val(endPeriod);
      $("#editCandidacyModal #status").val(status);

      $("#editCandidacyModal #electionNameEdit").val(name).trigger('change');
    });
  });
  $(document).ready(function() {
    $("#submitEditCandidacy").click(function(e) {
      e.preventDefault(); // Stop default form submission

      let formData = $("#editCandidacyForm").serialize();

      $.ajax({
        type: "POST",
        url: "processes/candidacy/update.php", // Your PHP endpoint
        data: formData,
        dataType: "json", // Expect JSON back
        success: function(response) {
          if (response.status === "success") {
            Swal.fire({
              icon: "success",
              title: "Success!",
              text: response.message || "You have successfully edited the candidacy details!",
              timer: 2000,
              showConfirmButton: false
            }).then(() => {
              $("#editCandidacyModal").modal("hide");
              $("#editCandidacyForm")[0].reset();
              location.reload(); // Refresh to reflect updates
            });
          } else {
            Swal.fire({
              icon: "error",
              title: "Update Failed",
              text: response.message || "Something went wrong. Please try again."
            });
          }
        },
        error: function(xhr, status, error) {
          console.error("AJAX Error:", error);
          Swal.fire({
            icon: "error",
            title: "Server Error",
            text: "Could not reach the server. Please try again later."
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
                text: 'You have succesfully deleted the candidacy period!'
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
  document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    const pad = num => String(num).padStart(2, '0');
    const formattedDateTime = now.getFullYear() + '-' +
      pad(now.getMonth() + 1) + '-' +
      pad(now.getDate()) + 'T' +
      pad(now.getHours()) + ':' +
      pad(now.getMinutes());

    // For add modal
    document.getElementById('startPeriod').min = formattedDateTime;
    document.getElementById('endPeriod').min = formattedDateTime;

    // For edit modal
    document.getElementById('editStartPeriod').min = formattedDateTime;
    document.getElementById('editEndPeriod').min = formattedDateTime;
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

  window.addEventListener('DOMContentLoaded', () => {
    const currentYear = new Date().getFullYear();
    document.getElementById('schoolYearStart').value = currentYear;
    document.getElementById('schoolYearEnd').value = currentYear + 1;
  });
</script>

<script>
  document.addEventListener("DOMContentLoaded", function() {

    function getCurrentDateTime() {
      const now = new Date();
      now.setSeconds(0);
      now.setMilliseconds(0);

      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const day = String(now.getDate()).padStart(2, '0');
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');

      return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    const minDateTime = getCurrentDateTime();

    document.getElementById("startPeriod").min = minDateTime;
    document.getElementById("endPeriod").min = minDateTime;

  });
</script>




</html>