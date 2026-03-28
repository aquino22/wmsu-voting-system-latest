<?php
session_start();
include('includes/conn.php');
$current_page = basename($_SERVER['PHP_SELF']);
// Check admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  session_destroy();
  session_start();
  $_SESSION['STATUS'] = "NON_ADMIN";
  header("Location: ../index.php");
  exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
} else {
  $event_id = $_GET['id']; // Assign the ID properly
}
?>

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



<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>WMSU i-Elect Admin | Viewing Event </title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="vendors/typicons/typicons.css">
  <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

  <!-- jQuery & DataTables JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <link rel="stylesheet" href="css/vertical-layout-light/style.css">
  <!-- endinject -->
  <link rel="shortcut icon" href="images/favicon.png" />

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

  <!-- Include Quill CSS & JS -->
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<style>

</style>

<body>
  <div class="container-scroller">
    <!-- partial:partials/_navbar.html -->
    <nav class="navbar default-layout col-lg-12 col-12 p-0  d-flex align-items-top flex-row">
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
      <!-- [Existing navbar code unchanged] -->
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

      <?php
      // Fetch event details
      $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
      $stmt->execute([$event_id]);
      $event = $stmt->fetch(PDO::FETCH_ASSOC);
      $event_title = $event['event_title'];
      ?>


      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                  <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                      <a class="nav-link ps-0" href="events.php">Events</a>
                    </li>
                    <li class="nav-item " style="margin-left: 15px;">
                      <a class="nav-link ps-0 active" href="#"><?php echo $event_title ?></a>
                    </li>


                  </ul>
                  <button type="button" class="btn btn-primary text-white" data-bs-toggle="modal" data-bs-target="#editModal">
                    <i class="mdi mdi-pencil me-1"></i> Edit the Article
                  </button>

                </div>
                <?php
                $event_id = $_GET['id'];
                $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
                $stmt->execute([$event_id]);
                $event = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$event) {
                  die("Event not found.");
                }
                ?>

                <div class="tab-content tab-content-basic">
                  <div class="row">
                    <div class="card">
                      <div class="card-body">
                        <?php if ($event['cover_image']): ?>
                          <img src="../uploads/event_covers/<?= htmlspecialchars($event['cover_image']) ?>" class="img-fluid">
                          <br><br>
                        <?php endif; ?>

                        <h1><?= htmlspecialchars($event['event_title']) ?></h1>
                        <p><i class="mdi mdi-eye"></i> Views: <?= $event['views'] ?></p>
                        <p>Written by: <?= htmlspecialchars($event['author']) ?> | Published At:
                          <?= date("F j, Y, g:i A", strtotime($event['created_at'])) ?>
                        </p>
                        <p><?= $event['event_details'] ?></p> <!-- This contains Quill-formatted HTML -->
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel"><b>Editing Event Article:</b> <?php echo htmlspecialchars($event['event_title']); ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <form id="editEventForm" enctype="multipart/form-data" method="POST" action="update_event.php">
                        <div class="modal-body">
                          <!-- Hidden Input for Event ID -->
                          <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">

                          <!-- Title Input -->
                          <div class="mb-3">
                            <label for="event_title" class="form-label">Event Title</label>
                            <input type="text" class="form-control" name="event_title" id="event_title" value="<?php echo htmlspecialchars($event['event_title']); ?>" required>
                          </div>

                          <!-- Cover Image Upload -->
                          <div class="mb-3">
                            <label for="cover_image" class="form-label">Cover Image</label>
                            <?php if (!empty($event['cover_image'])): ?>
                              <a href="../Uploads/event_covers/<?php echo htmlspecialchars($event['cover_image']); ?>" target="_blank"><small>(View Image)</small></a>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="cover_image" id="cover_image" accept="image/*">
                          </div>
                          <!-- Quill Editor for Event Details -->
                          <div class="mb-3">
                            <label for="event_details" class="form-label">Event Details</label>
                            <div id="editor-container-content" style="height: 500px;"></div>
                            <input type="hidden" name="event_details" id="event_details">
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="mdi mdi-close me-1"></i> Close
                          </button>
                          <button type="submit" class="btn btn-primary text-white">
                            <i class="mdi mdi-upload me-1"></i> Publish
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="modal fade" id="ongoingElectionsModal" tabindex="-1" aria-labelledby="ongoingElectionsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered ">
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


    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Initialize Quill editor
        const quill = new Quill('#editor-container-content', {
          theme: 'snow',
          modules: {
            toolbar: [
              [{
                'header': [1, 2, 3, false]
              }],
              ['bold', 'italic', 'underline'],
              ['link', 'image'],
              [{
                'list': 'ordered'
              }, {
                'list': 'bullet'
              }],
              ['clean']
            ]
          }
        });

        // Populate editor with existing event details
        const eventDetails = <?php echo json_encode($event['event_details']); ?>;
        if (eventDetails) {
          quill.root.innerHTML = eventDetails; // Load existing content
        }

        // Update hidden input with editor content on form submission
        const form = document.getElementById('editEventForm');
        form.addEventListener('submit', function() {
          const hiddenInput = document.getElementById('event_details');
          hiddenInput.value = quill.root.innerHTML; // Store editor content in hidden input
        });

        // Handle Unpublish button (optional, requires server-side logic)
        document.getElementById('unpublishBtn').addEventListener('click', function() {
          if (confirm('Are you sure you want to unpublish this event?')) {
            // Send AJAX request to unpublish
            fetch('update_event.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `event_id=<?php echo $event['id']; ?>&status=unpublished`
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  alert('Event unpublished successfully.');
                  location.reload();
                } else {
                  alert('Error unpublishing event.');
                }
              });
          }
        });
      });
    </script>

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
</body>

<!-- jQuery (Required) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>



<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
  const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
</script>

<?php if (isset($_SESSION['status'])): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      Swal.fire({
        icon: '<?php echo $_SESSION['status']['type'] === 'success' ? 'success' : 'error'; ?>',
        title: '<?php echo $_SESSION['status']['type'] === 'success' ? 'Success' : 'Error'; ?>',
        text: '<?php echo htmlspecialchars($_SESSION['status']['message'], ENT_QUOTES, 'UTF-8'); ?>',
        confirmButtonText: 'OK',
        timer: 3000, // Auto-close after 3 seconds
        timerProgressBar: true
      });
    });
  </script>
  <?php unset($_SESSION['status']); // Clear the message after displaying 
  ?>
<?php endif; ?>

<button class="back-to-top" id="backToTop" title="Go to top">
  <i class="mdi mdi-arrow-up"></i>
</button>

</html>