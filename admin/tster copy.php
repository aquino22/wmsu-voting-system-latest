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
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>WMSU i-Elect Admin | Precincts </title>
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="vendors/typicons/typicons.css">
  <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="js/select.dataTables.min.css">
  <link rel="stylesheet" href="css/vertical-layout-light/style.css">
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="shortcut icon" href="images/favicon.png" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <!-- Material Design Icons CSS -->
  <link rel="stylesheet" href="https://cdn.materialdesignicons.com/5.9.55/css/materialdesignicons.min.css" />
</head>

<style>
  .custom-padding {
    padding: 20px !important;
    /* Adjust padding as needed */
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
          </li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item dropdown">
          </li>
          <li class="nav-item dropdown d-none d-lg-block user-dropdown">
            <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
              <img class="img-xs rounded-circle logo" src="images/wmsu-logo.png" style="background-color: white;"
                alt="Profile image"> </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
              <div class="dropdown-header text-center">
                <img class="img-xs rounded-circle logoe" src="images/wmsu-logo.png" alt="Profile image">
                <p class="mb-1 mt-3 font-weight-semibold"><?php echo $admin_full_name ?></p>
                <p class="fw-light text-muted mb-0"><?php echo $admin_email ?></p>
                <p class="fw-light text-muted mb-0"><?php echo $admin_phone_number ?></p>
              </div>
              <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-pen text-primary me-2"></i>Edit Account
                Details</a>
              <a class="dropdown-item" href="processes/accounts/logout.php"><i
                  class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign
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
            <a class="nav-link active-link" href="index.php">
              <i class="mdi mdi-grid-large menu-icon"></i>
              <span class="menu-title">Index</span>
            </a>
          </li>

          <li class="nav-item active-link">
            <a class="nav-link active-link" href="moderators.php">
              <i class="mdi mdi-pac-man menu-icon"></i>
              <span class="menu-title">Moderators</span>
            </a>
          </li>
          <li class="nav-item active-link">
            <a class="nav-link active-link" href="precincts.php" style="background-color: #B22222 !important;">
              <i class="mdi mdi-room-service menu-icon" style="color: white !important;"></i>
              <span class="menu-title" style="color: white !important;">Precincts</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="voter-list.php">
              <i class="menu-icon mdi mdi-account-group"></i>
              <span class="menu-title">Voter List</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="election.php">
              <i class="menu-icon mdi mdi-vote"></i>
              <span class="menu-title">Election</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="candidacy.php">
              <i class="menu-icon mdi mdi-account-tie"></i>
              <span class="menu-title">Candidacy</span>
            </a>
          </li>


          <li class="nav-item">
            <a class="nav-link" href="voting.php">
              <i class="menu-icon mdi mdi-ballot"></i>
              <span class="menu-title">Voting</span>
            </a>
          </li>


          <li class="nav-item">
            <a class="nav-link" href="history.php">
              <i class="menu-icon mdi mdi-history"></i>
              <span class="menu-title">History</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="reports.php">
              <i class="menu-icon mdi mdi-file-chart"></i>
              <span class="menu-title">Reports</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="events.php">
              <i class="menu-icon mdi mdi-calendar"></i>
              <span class="menu-title">Events</span>
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
                        aria-controls="overview" aria-selected="true">Precincts</a>
                    </li>

                  </ul>

                </div>
                <div class="tab-content tab-content-basic">
                  <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">

                    <div class="row mt-3">
                      <div class="card px-5 pt-5">
                        <div class="d-flex align-items-center">
                          <h5>Precincts</h5>
                          <div class="ms-auto" aria-hidden="true">
                            <button class="btn btn-primary text-white" data-bs-toggle="modal"
                              data-bs-target="#addPrecinctModal">
                              <i class="bi bi-house-add"></i> Add Precincts
                            </button>
                          </div>
                        </div>
                        <div class="card-body">
                          <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="precinctsTable">
                              <thead class="thead-dark">
                                <tr>
                                  <th>Name</th>
                                  <th>College</th>
                                  <th>Location</th>
                                  <th>Department</th>
                                  <th>Type</th>
                                  <th>Created At</th>
                                  <th>Updated At</th>
                                  <th>Assignment Status</th>
                                  <th>Occupied Status</th>


                                  <th>Election Covered</th>
                                  <th>Manage</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                // Fetch all precincts
                                $query = "SELECT * FROM precincts ORDER BY created_at DESC";
                                $stmt = $pdo->prepare($query);
                                $stmt->execute();
                                $precincts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (count($precincts) > 0) {
                                  foreach ($precincts as $row) {
                                    // Fetch elections covered by this precinct from precinct_elections table
                                    $precinct_name = $row['name'];
                                    $elections_query = "SELECT e.election_name FROM precinct_elections pe
                          JOIN elections e ON pe.election_name = e.election_name
                          WHERE pe.precinct_name = :precinct_id";
                                    $elections_stmt = $pdo->prepare($elections_query);
                                    $elections_stmt->bindParam(':precinct_id', $precinct_name, PDO::PARAM_STR); // Changed to string type
                                    $elections_stmt->execute();
                                    $elections = $elections_stmt->fetchAll(PDO::FETCH_ASSOC);

                                    // Collect election names into a comma-separated string
                                    $election_names = array_column($elections, 'election_name');
                                    $elections_covered = $election_names ? implode(', ', $election_names) : 'No elections';

                                    // Echo the row with correct columns
                                    echo "<tr>
        <td>{$row['name']}</td>
        <td>{$row['college']}</td>
        <td>{$row['location']}</td>
           <td>{$row['department']}</td>
               <td>{$row['type']}</td>
        <td>" . date('F j, Y g:i A', strtotime($row['created_at'])) . "</td>
        <td>" . date('F j, Y g:i A', strtotime($row['updated_at'])) . "</td>
        <td>
          <button class='text-white btn btn-" . ($row['assignment_status'] == 'assigned' ? 'success' : 'danger') . "'>
            " . ucfirst($row['assignment_status']) . "
          </button>
        </td>
        <td>
          <button class='text-white btn btn-" . ($row['occupied_status'] == 'occupied' ? 'success' : 'danger') . "'>
            " . ucfirst($row['occupied_status']) . "
          </button>
        </td>
      
      
        <td>{$elections_covered}</td>
        <td>
          <button class='text-white btn btn-success viewBtn' 
            data-id='{$row['id']}'
            data-name='{$row['name']}'
            data-location='{$row['location']}'
                      data-type='{$row['type']}'
            data-assignment_status='{$row['assignment_status']}'
            data-occupied_status='{$row['occupied_status']}'
            data-bs-toggle='modal' 
            data-bs-target='#viewModal'>
            View
          </button>

   

          <button class='text-white btn btn-danger deleteBtn' data-id='{$row['id']}'>Delete</button>
        </td>
      </tr>";
                                  }
                                } else {
                                  echo "<tr><td colspan='11'  class='text-center'>No precincts found.</td></tr>";
                                }
                                ?>
                              </tbody>
                            </table>
                          </div>
                        </div>

                        <!-- edit code
                   <button type='button' class='btn btn-warning text-white' 
        data-bs-toggle='modal' 
        data-bs-target='#editModal'
        data-id='" . htmlspecialchars($row['id']) . "'
        data-name='" . htmlspecialchars($row['name']) . "'
        data-location='" . htmlspecialchars($row['location']) . "'
        data-type='" . htmlspecialchars($row['type']) . "'
        data-college='" . htmlspecialchars($row['college']) . "'
        data-department='" . htmlspecialchars($row['department']) . "'
        data-assignment-status='" . htmlspecialchars($row['assignment_status']) . "'
        data-occupied-status='" . htmlspecialchars($row['occupied_status']) . "'
        data-elections='" . htmlspecialchars(json_encode($elections_covered)) . "' 
        onclick='fillEditModal(this)'>
        Edit
      </button>

                              -->

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

              <div class="modal fade" id="addPrecinctModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h1 class="modal-title fs-5" id="exampleModalLabel">Add Precinct</h1>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <form id="addPrecinctForm">
                        <div class="row">
                          <div class="col">
                            <div class="mb-3">
                              <label for="name" class="form-label">Precinct Name</label>
                              <input type="text" class="form-control" id="name" name="name" required>
                            </div>

                            <div class="mb-3">
                              <label for="location" class="form-label">Location</label>
                              <input type="text" class="form-control" id="location" name="location" required>
                            </div>

                            <div class="mb-3">
                              <div id="map-container">
                                <div id="leaflet-map"></div>
                                <div id="google-map">
                                  <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d990.2030970492448!2d122.06323088460613!3d6.913022088239877!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v1746872885316!5m2!1sen!2sph" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                                </div>
                              </div>
                              <div id="coordinates">
                                <label for="xInput">X (Longitude):</label>
                                <input type="text" id="xInput" readonly placeholder="Longitude">
                                <label for="yInput">Y (Latitude):</label>
                                <input type="text" id="yInput" readonly placeholder="Latitude">
                                <button type="button" onclick="copyToClipboard()">Copy Coordinates</button>
                              </div>
                            </div>

                            <div class="mb-3">
                              <label for="type" class="form-label">Type</label>
                              <select class="form-control" id="type" name="type" required>
                                <option value="" disabled selected>Select Type</option>
                                <option value="Central">Central</option>
                                <option value="External">External</option>
                              </select>
                            </div>

                            <div class="mb-3">
                              <label for="college" class="form-label">College</label>
                              <select class="form-control" id="college" name="college" required>
                                <option value="" disabled selected>Select College</option>
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
                            </div>

                            <div class="mb-3">
                              <label for="department" class="form-label">Department</label>
                              <select class="form-control" id="department" name="department" required>
                                <option value="" disabled selected>Select Department</option>
                              </select>
                            </div>

                            <div class="mb-3" style="display:none">
                              <label for="assignment_status" class="form-label">Assignment Status</label>
                              <select class="form-control" id="assignment_status" name="assignment_status" required>
                                <option value disabled selected>Select Status</option>
                                <option value="Assigned">Assigned</option>
                                <option value="Unassigned" selected>Unassigned</option>
                              </select>
                            </div>

                            <div class="mb-3" style="display:none">
                              <label for="occupied_status" class="form-label">Occupied Status</label>
                              <select class="form-control" id="occupied_status" name="occupied_status" required>
                                <option value disabled selected>Select Status</option>
                                <option value="Occupied">Occupied</option>
                                <option value="Unoccupied" selected>Unoccupied</option>
                              </select>
                            </div>

                            <div class="mb-3" style="border: 1px solid lightgrey; border-radius: 10px; padding: 10px;">
                              <label class="form-label">Select One Election Below: </label>
                              <div id="electionCheckboxes">
                                <!-- Static example checkboxes for testing -->
                                <div class="form-check">
                                  <input type="checkbox" class="form-check-input" name="elections[]" id="election_1" value="Election 1">
                                  <label class="form-check-label" for="election_1">Election 1</label>
                                </div>
                                <div class="form-check">
                                  <input type="checkbox" class="form-check-input" name="elections[]" id="election_2" value="Election 2">
                                  <label class="form-check-label" for="election_2">Election 2</label>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </form>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="mdi mdi-alpha-x-circle"></i> Close
                      </button>
                      <button type="button" class="btn btn-primary" id="submitForm">
                        <i class="mdi mdi-plus-circle"></i> Save Changes
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <style>
                #electionCheckboxes {
                  margin-left: 25px !important;
                }
              </style>

              <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>



              <script>
                // Department options for each college
                const departmentData = {
                  "College of Law": ["Law"],
                  "College of Agriculture": [
                    "Agriculture",
                    "Food Technology",
                    "Agribusiness",
                    "Agricultural Technology",
                    "Agronomy"
                  ],
                  "College of Liberal Arts": [
                    "Accountancy",
                    "History",
                    "English",
                    "Political Science",
                    "Mass Communication - Journalism",
                    "Mass Communication - Broadcasting",
                    "Economics",
                    "Psychology"
                  ],
                  "College of Architecture": ["Architecture"],
                  "College of Nursing": ["Nursing"],
                  "College of Asian & Islamic Studies": [
                    "Asian Studies",
                    "Islamic Studies"
                  ],
                  "College of Computing Studies": [
                    "Computer Science",
                    "Information Technology",
                    "Computer Technology"
                  ],
                  "College of Forestry & Environmental Studies": [
                    "Forestry",
                    "Agroforestry",
                    "Environmental Science"
                  ],
                  "College of Criminal Justice Education": ["Criminology"],
                  "College of Home Economics": [
                    "Home Economics",
                    "Nutrition and Dietetics",
                    "Hospitality Management"
                  ],
                  "College of Engineering": [
                    "Agricultural and Biosystems",
                    "Civil Engineering",
                    "Computer Engineering",
                    "Electrical Engineering",
                    "Electronics Engineering",
                    "Environmental Engineering",
                    "Geodetic Engineering",
                    "Industrial Engineering",
                    "Mechanical Engineering",
                    "Sanitary Engineering"
                  ],
                  "College of Medicine": ["Medicine"],
                  "College of Public Administration & Development Studies": [
                    "Public Administration"
                  ],
                  "College of Sports Science & Physical Education": [
                    "Physical Education",
                    "Exercise and Sports Sciences"
                  ],
                  "College of Science and Mathematics": [
                    "Biology",
                    "Chemistry",
                    "Mathematics",
                    "Physics",
                    "Statistics"
                  ],
                  "College of Social Work & Community Development": [
                    "Social Work",
                    "Community Development"
                  ],
                  "College of Teacher Education": [
                    "Culture and Arts Education",
                    "Early Childhood Education",
                    "Elementary Education",
                    "Secondary Education",
                    "Secondary Education major in English",
                    "Secondary Education Major in Filipino",
                    "Secondary Education Major in Mathematics",
                    "Secondary Education Major in Sciences",
                    "Secondary Education Major in Social Studies",
                    "Secondary Education Major in Values Education",
                    "Special Needs Education"
                  ]
                };

                // Initialize Leaflet map
                let map;
                let marker;

                function initializeMap() {
                  if (map) return; // Prevent reinitialization
                  map = L.map('leaflet-map').setView([6.9129722649685865, 122.06321320922099], 17);
                  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                  }).addTo(map);

                  // Add click event listener for map
                  map.on('click', (e) => {
                    const {
                      lat,
                      lng
                    } = e.latlng;
                    document.getElementById('xInput').value = lng.toFixed(6);
                    document.getElementById('yInput').value = lat.toFixed(6);

                    // Update Google Maps iframe
                    const googleMap = document.querySelector('#google-map iframe');
                    if (googleMap) {
                      googleMap.src = `https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d1000!2d${lng}!3d${lat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v${Date.now()}`;
                    }

                    // Update marker
                    if (marker) marker.remove();
                    marker = L.marker([lat, lng]).addTo(map);
                  });
                }

                // Handle modal show event to initialize map
                document.getElementById('addPrecinctModal').addEventListener('shown.bs.modal', function() {
                  setTimeout(() => {
                    initializeMap();
                    if (map) map.invalidateSize(); // Ensure map renders correctly
                  }, 100);
                });

                // Clean up map when modal is hidden
                document.getElementById('addPrecinctModal').addEventListener('hidden.bs.modal', function() {
                  if (map) {
                    map.remove();
                    map = null;
                    marker = null;
                  }
                });

                // Populate department dropdown based on college selection
                document.getElementById('college').addEventListener('change', function() {
                  let college = this.value;
                  let departmentDropdown = document.getElementById('department');

                  // Clear previous department options
                  departmentDropdown.innerHTML = `<option value="" disabled selected>Select Department</option>`;

                  if (college in departmentData) {
                    departmentData[college].forEach(dept => {
                      let option = document.createElement('option');
                      option.value = dept;
                      option.textContent = dept;
                      departmentDropdown.appendChild(option);
                    });
                  }
                });

                // Copy coordinates to clipboard
                function copyToClipboard() {
                  const x = document.getElementById('xInput').value;
                  const y = document.getElementById('yInput').value;
                  if (!x || !y) {
                    alert('No coordinates selected. Please click on the map first.');
                    return;
                  }
                  const coords = `Longitude: ${x}, Latitude: ${y}`;
                  navigator.clipboard.writeText(coords)
                    .then(() => {
                      alert('Coordinates copied to clipboard!');
                    })
                    .catch(() => {
                      alert('Failed to copy coordinates. Please copy manually.');
                    });
                }

                // Handle form submission (basic example)
                document.getElementById('submitForm').addEventListener('click', function(event) {
                  event.preventDefault();
                  const form = document.getElementById('addPrecinctForm');
                  if (form.checkValidity()) {
                    alert('Form submitted! (Add server-side handling here)');
                    // Add your server-side submission logic here (e.g., fetch to processes/precincts/add.php)
                  } else {
                    form.reportValidity();
                  }
                });
              </script>


            </div>

            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <!-- DataTables JS -->
            <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
            <script src="vendors/js/vendor.bundle.base.js"></script>
            <script src="vendors/chart.js/Chart.min.js"></script>
            <script src="vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
            <script src="vendors/progressbar.js/progressbar.min.js"></script>
            <script src="vendors/jquery-3.6.0.min.js"></script>
            <script src="vendors/jquery.dataTables.min.js"></script>
            <script src="vendors/dataTables.bootstrap5.min.js"></script>
            <script src="vendors/jspdf.umd.min.js"></script>
            <script src="vendors/sweetalert2@11.js"></script>



            <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Viewing Precinct Details</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <form>
                      <div class="row">
                        <div class="col">
                          <div class="mb-3">
                            <label for="view_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="view_name" readonly>
                          </div>

                          <div class="mb-3">
                            <label for="view_location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="view_location" readonly>
                          </div>

                          <div class="mb-3">
                            <label for="view_type" class="form-label">Type</label>
                            <input type="text" class="form-control" id="view_type" readonly>
                          </div>
                        </div>

                        <div class="col">
                          <div class="mb-3">
                            <label for="view_assignment_status" class="form-label">Assignment Status</label>
                            <input type="text" class="form-control" id="view_assignment_status" readonly>
                          </div>

                          <div class="mb-3">
                            <label for="view_occupied_status" class="form-label">Occupied Status</label>
                            <input type="text" class="form-control" id="view_occupied_status" readonly>
                          </div>
                        </div>
                      </div>
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>


            <!-- Edit Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg"> <!-- Large modal for better visibility -->
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Precinct</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>

                  <div class="modal-body">
                    <form id="editPrecinctForm" method="POST" action="update_precinct.php">
                      <input type="hidden" id="editId" name="id">

                      <div class="row">
                        <div class="col-md-6">
                          <label for="editName" class="form-label">Precinct Name</label>
                          <input type="text" class="form-control" id="editName" name="name" required>
                        </div>
                        <div class="col-md-6">
                          <label for="editLocation" class="form-label">Location</label>
                          <input type="text" class="form-control" id="editLocation" name="location" required>
                        </div>
                      </div>

                      <div class="row mt-3">
                        <div class="col-md-6">
                          <label for="editType" class="form-label">Type</label>
                          <select class="form-control" id="editType" name="type" required>
                            <option value="Central">Central</option>
                            <option value="Local">Local</option>
                          </select>
                        </div>

                        <div class="col-md-6">
                          <label for="editCollege" class="form-label">College</label>
                          <input type="text" class="form-control" id="editCollege" name="college" required>
                        </div>
                      </div>

                      <div class="row mt-3">
                        <div class="col-md-6">
                          <label for="editDepartment" class="form-label">Department</label>
                          <input type="text" class="form-control" id="editDepartment" name="department" required>
                        </div>

                        <div class="col-md-6">
                          <div class="mb-3">
                            <label class="form-label">Select One Election Below: </label>
                            <div id="editElectionCheckboxes"></div> <!-- Dynamically populated checkboxes -->
                          </div>
                        </div>
                      </div>

                      <div class="row mt-3">
                        <div class="col-md-6">
                          <label for="editAssignmentStatus" class="form-label">Assignment Status</label>
                          <select class="form-control" id="editAssignmentStatus" name="assignment_status" required>
                            <option value="assigned">Assigned</option>
                            <option value="unassigned">Unassigned</option>
                          </select>
                        </div>

                        <div class="col-md-6">
                          <label for="editOccupiedStatus" class="form-label">Occupied Status</label>
                          <select class="form-control" id="editOccupiedStatus" name="occupied_status" required>
                            <option value="occupied">Occupied</option>
                            <option value="unoccupied">Unoccupied</option>
                          </select>
                        </div>
                      </div>

                      <div class="modal-footer mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>


</body>

</html>