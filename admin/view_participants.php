<?php
// Secure session configuration
session_start();
$current_page = basename($_SERVER['PHP_SELF']);
require 'includes/conn.php'; // Adjust path to your PDO connection

// Set security headers
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Disable error display in production
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Ensure PDO throws exceptions for all errors
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Custom sanitization functions
function sanitizeString($input, $maxLength)
{
  if ($input === null) return null;
  $input = trim($input);
  $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $input = substr($input, 0, $maxLength);
  return preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $input);
}

// Check admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  session_destroy();
  session_start();
  $_SESSION['STATUS'] = "NON_ADMIN";
  header("Location: ../index.php");
  exit();
}

// Validate CSRF token with expiration (30 minutes)
$csrfTokenAge = 1800;
if (
  !isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) ||
  (time() - $_SESSION['csrf_token_time']) > $csrfTokenAge
) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  $_SESSION['csrf_token_time'] = time();
}

// Initialize variables
$error_message = '';
$event = null;
$form = null;
$candidates = [];
$field_map = [];
$registration_enabled = false;

// Get and validate event ID
$event_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
if ($event_id === false || $event_id <= 0) {
  $error_message = "Invalid event ID";
} else {
  try {
    // Start a transaction
    $pdo->beginTransaction();

    // Fetch event details (no dependency on publication status)
    $event_stmt = $pdo->prepare("
            SELECT candidacy, registration_deadline, registration_enabled 
            FROM events 
            WHERE id = ?
        ");
    $event_stmt->execute([$event_id]);
    $event = $event_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event || empty($event['candidacy'])) {
      $error_message = "Event not found or no candidacy defined";
    } else {
      $registration_enabled = (bool)$event['registration_enabled'];
      $candidacy = sanitizeString($event['candidacy'], 100);

      // Fetch registration form details
      $form_stmt = $pdo->prepare("
                SELECT id, form_name, status
                FROM registration_forms 
                WHERE election_name = ?
            ");
      $form_stmt->execute([$candidacy]);
      $form = $form_stmt->fetch(PDO::FETCH_ASSOC);

      if ($form) {
        $form_id = $form['id'];

        $form_name = sanitizeString($form['form_name'], 100);

        // Fetch all candidates for this form
        $candidates_stmt = $pdo->prepare("
                    SELECT * FROM candidates 
                    WHERE form_id = ? 
                    ORDER BY created_at DESC
                ");
        $candidates_stmt->execute([$form_id]);
        $candidates = $candidates_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch field definitions
        $fields_stmt = $pdo->prepare("
                    SELECT id, field_name, field_type 
                    FROM form_fields 
                    WHERE form_id = ?
                ");
        $fields_stmt->execute([$form_id]);
        $fields = $fields_stmt->fetchAll(PDO::FETCH_ASSOC);
        $field_map = array_column($fields, null, 'id');
      } else {
        $error_message = "No matching registration form found for the candidacy.";
      }
    }

    // Log admin activity
    $user_id = $_SESSION['user_id'];
    $action = 'VIEW_CANDIDATES';
    $timestamp = date('Y-m-d H:i:s');
    $device_info = sanitizeString($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 255);
    $ip_address = filter_var($_SERVER['REMOTE_ADDR'] ?? 'Unknown', FILTER_VALIDATE_IP) ?: 'Unknown';
    $location = 'N/A';
    $behavior_patterns = sanitizeString("Viewed candidates for event ID: $event_id, Candidacy: " . ($candidacy ?? 'None'), 500);

    $activityStmt = $pdo->prepare("
            INSERT INTO user_activities (
                user_id, action, timestamp, device_info, ip_address, location, behavior_patterns
            ) VALUES (
                :user_id, :action, :timestamp, :device_info, :ip_address, :location, :behavior_patterns
            )
        ");
    $activityStmt->execute([
      ':user_id' => $user_id,
      ':action' => $action,
      ':timestamp' => $timestamp,
      ':device_info' => $device_info,
      ':ip_address' => $ip_address,
      ':location' => $location,
      ':behavior_patterns' => $behavior_patterns
    ]);

    $stmt = $pdo->prepare("
SELECT 
    id,
    student_id,
    TRIM(CONCAT(
        first_name, ' ',
        COALESCE(CONCAT(middle_name, ' '), ''),
        last_name
    )) AS full_name
FROM voters
WHERE status = 'confirmed';


 ");
    $stmt->execute();
    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT DATABASE()");
    $currentDb = $stmt->fetchColumn();

    $party_stmt = $pdo->prepare("
     SELECT name 
     FROM parties 
     WHERE election_name = ? AND status = 'approved' 
     ORDER BY name
 ");
    $party_stmt->execute([$candidacy]);
    $parties = $party_stmt->fetchAll(PDO::FETCH_ASSOC);

    $pdo->commit();
  } catch (PDOException $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    error_log("Database error in view_candidates.php: " . $e->getMessage());
    $error_message = "Database error occurred";
  } catch (Exception $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    error_log("Error in view_candidates.php: " . $e->getMessage());
    $error_message = htmlspecialchars($e->getMessage());
  }
}

// Output error message or proceed to render the page
if ($error_message) {
  // You can redirect or display the error in the UI
  $_SESSION['error_message'] = $error_message;
  header("Location: error_page.php"); // Adjust to your error handling page
  exit();
}

// The variables $event, $form, $candidates, $field_map, and $registration_enabled are now set
// Proceed to render your HTML or include a template
?>


<!DOCTYPE html>
<html lang="en">
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>WMSU I-Elect | View Participants</title>
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="vendors/typicons/typicons.css">
  <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="css/vertical-layout-light/style.css">
  <link rel="shortcut icon" href="images/favicon.png" />
  <style>
    .candidate-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }

    .candidate-table th,
    .candidate-table td {
      padding: 12px;
      border: 1px solid #ddd;
      text-align: left;
    }

    .candidate-table th {
      background-color: #f5f5f5;
      font-weight: bold;
    }

    .candidate-table tr:nth-child(even) {
      background-color: #fafafa;
    }

    .candidate-table tr:hover {
      background-color: #f0f0f0;
    }

    .candidate-header {
      background-color: #B22222;
      color: white;
      padding: 10px;
      margin-bottom: 10px;
      border-radius: 4px;
    }

    .file-link {
      color: #007bff;
      text-decoration: none;
    }

    .file-link:hover {
      text-decoration: underline;
    }

    .not-provided {
      color: #888;
      font-style: italic;
    }

    .status-pending {
      color: #ffa500;
      font-weight: bold;
    }

    .status-accepted {
      color: #28a745;
      font-weight: bold;
    }

    .status-rejected {
      color: #dc3545;
      font-weight: bold;
    }

    .action-btn {
      padding: 5px 10px;
      margin-right: 5px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    .accept-btn {
      background-color: #28a745;
      color: white;
    }

    .reject-btn {
      background-color: #dc3545;
      color: white;
    }

    .action-btn:disabled {
      background-color: #ccc;
      cursor: not-allowed;
    }
  </style>
</head>

<body>
  <div class="container-scroller">
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
                        $stmt = $pdo->prepare("SELECT * FROM elections WHERE status = :status");
                        $stmt->execute(['status' => 'Ongoing']);
                        $ongoingElections = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if ($ongoingElections) {

                            // Show first election
                            $first = array_shift($ongoingElections);
                            echo "<br><b>Election: </b> " . $first['election_name'] . " | ";
                            echo "<b>Semester: </b> " . $first['semester'] . " | ";
                            echo "<b>School Year:</b> " . $first['school_year_start'] . " - " . $first['school_year_end'] . "<br>";

                            if ($ongoingElections) {
                                echo '<div id="moreElections" style="display:none; margin-top: 5px !important">';
                            
                                foreach ($ongoingElections as $election) {
                                  
                                    echo "<b>Election: </b> " . $election['election_name'] . " | ";
                                    echo "<b>Semester:</b> " . $election['semester'] . " | ";
                                    echo "<b>School Year:</b> " . $election['school_year_start'] . " - " . $election['school_year_end'] . "<br>";
                                }
                                echo '</div> <br>';
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



        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-sm-12">
                <div class="home-tab">
                  <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                    <ul class="nav nav-tabs" role="tablist">
                      <li class="nav-item">
                        <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Candidates</a>
                      </li>
                    </ul>
                  </div>
                  <div class="tab-content tab-content-basic">
                    <div class="container-fluid mt-4 card">


                      <div class="card-body">


                        <div class="d-flex align-items-center flex-wrap mb-3 mt-3">
                          <h2><b>
                              <?php
                              if (!empty($form['form_name'])) {
                                echo "Candidates for " . htmlspecialchars($form['form_name']);
                              } else {
                                echo "No active registration form available for candidacy yet.";
                              }
                              ?>
                            </b></h2>


                          <?php if (isset($form) && isset($form['status'])) : ?>

                            <?php if ($form['status'] === 'active') : ?>
                              <div class="ms-auto d-flex align-items-center">

                                <form class="me-2">
                                  <button type="button" class="btn btn-primary btn-sm text-white"
                                    data-bs-toggle="modal" data-bs-target="#addCandidateModal">
                                    Add Candidate
                                  </button>
                                </form>

                                <form method="POST" action="processes/events/update_candidate_status.php?id=<?php echo htmlspecialchars($_GET['id']); ?>" class="me-2">
                                  <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
                                  <input type="hidden" name="status" value="accept">
                                  <button type="submit" class="btn btn-success btn-sm text-white">
                                    Accept All Pending
                                  </button>
                                </form>

                                <form method="POST" action="processes/events/update_candidate_status.php?id=<?php echo htmlspecialchars($_GET['id']); ?>" class="me-2">
                                  <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
                                  <input type="hidden" name="status" value="decline">
                                  <button type="submit" class="btn btn-danger btn-sm text-white">
                                    Decline All Pending
                                  </button>
                                </form>
                                <div class="modal fade" id="addCandidateModal" tabindex="-1" aria-labelledby="addCandidateModalLabel" aria-hidden="true">
                                  <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                      <div class="modal-header">
                                        <h5 class="modal-title" id="addCandidateModalLabel"><?php echo htmlspecialchars($form_name); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                      </div>
                                      <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                                        <form method="POST" id="registrationForm" enctype="multipart/form-data">
                                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                          <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
                                          <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                          <input type="hidden" id="lat" name="lat">
                                          <input type="hidden" id="lon" name="lon">

                                          <p class="text-muted mb-3">Note: File types allowed: PDFs, DOCXs, JPGs, PNGs</p>

                                          <?php foreach ($fields as $field) : ?>
                                            <div class="mb-3">
                                              <label class="form-label <?php echo $field['is_required'] ? 'required' : ''; ?>">
                                                <?php
                                                $label = match ($field['field_name']) {
                                                  'full_name' => 'Full Name',
                                                  'student_id' => 'Student ID',
                                                  default => ucfirst(htmlspecialchars($field['field_name'])),
                                                };
                                                echo $label;
                                                ?>
                                              </label>

                                              <?php switch ($field['field_name']):
                                                case 'full_name': ?>
                                                  <select class="form-select" name="fields[<?php echo $field['id']; ?>]" id="full_name_select" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                    <option value="">Select Full Name</option>
                                                    <?php foreach ($voters as $voter) : ?>
                                                      <option value="<?php echo htmlspecialchars($voter['full_name']); ?>" data-student-id="<?php echo htmlspecialchars($voter['student_id']); ?>">
                                                        <?php echo htmlspecialchars($voter['full_name']); ?>
                                                      </option>
                                                    <?php endforeach; ?>
                                                  </select>
                                                <?php break;

                                                case 'student_id': ?>
                                                  <input class="form-control" type="text" name="fields[<?php echo $field['id']; ?>]" id="student_id_field" readonly <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                <?php break;

                                                case 'party': ?>
                                                  <select class="form-select" name="fields[<?php echo $field['id']; ?>]" id="party_select" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                    <option value="">Select Party</option>
                                                    <?php foreach ($parties as $party) : ?>
                                                      <option value="<?php echo htmlspecialchars($party['name']); ?>">
                                                        <?php echo htmlspecialchars($party['name']); ?>
                                                      </option>
                                                    <?php endforeach; ?>
                                                  </select>
                                                <?php break;

                                                case 'position': ?>
                                                  <select class="form-select" name="fields[<?php echo $field['id']; ?>]" id="position_select" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                    <option value="">Select Party First</option>
                                                  </select>
                                                <?php break;

                                                case 'picture': ?>
                                                  <input class="form-control" type="file" id="field_<?php echo $field['id']; ?>" name="fields[<?php echo $field['id']; ?>]" accept=".jpg,.jpeg,.png" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                  <div class="file-preview mt-2" id="preview_<?php echo $field['id']; ?>"></div>
                                                  <?php break;

                                                default:
                                                  switch ($field['field_type']):
                                                    case 'text': ?>
                                                      <input class="form-control" type="text" name="fields[<?php echo $field['id']; ?>]" <?php echo $field['is_required'] ? 'required' : ''; ?> maxlength="255">
                                                    <?php break;

                                                    case 'textarea': ?>
                                                      <textarea class="form-control" name="fields[<?php echo $field['id']; ?>]" rows="4" <?php echo $field['is_required'] ? 'required' : ''; ?>></textarea>
                                                    <?php break;

                                                    case 'dropdown': ?>
                                                      <select class="form-select" name="fields[<?php echo $field['id']; ?>]" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                        <option value="">Select an option</option>
                                                        <?php
                                                        $options = $field['options'] ? explode(',', $field['options']) : [];
                                                        foreach ($options as $option) :
                                                          $option = trim($option);
                                                          if (!empty($option)) : ?>
                                                            <option value="<?php echo htmlspecialchars($option); ?>">
                                                              <?php echo htmlspecialchars($option); ?>
                                                            </option>
                                                        <?php endif;
                                                        endforeach; ?>
                                                      </select>
                                                    <?php break;

                                                    case 'checkbox': ?>
                                                      <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="fields[<?php echo $field['id']; ?>]" value="1">
                                                        <label class="form-check-label"><?php echo htmlspecialchars($field['field_name']); ?></label>
                                                      </div>
                                                    <?php break;

                                                    case 'radio': ?>
                                                      <?php
                                                      $options = $field['options'] ? explode(',', $field['options']) : [];
                                                      foreach ($options as $option) :
                                                        $option = trim($option);
                                                        if (!empty($option)) : ?>
                                                          <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="fields[<?php echo $field['id']; ?>]" value="<?php echo htmlspecialchars($option); ?>" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                            <label class="form-check-label"><?php echo htmlspecialchars($option); ?></label>
                                                          </div>
                                                      <?php endif;
                                                      endforeach; ?>
                                                    <?php break;

                                                    case 'file': ?>
                                                      <input class="form-control" type="file" id="field_<?php echo $field['id']; ?>" name="fields[<?php echo $field['id']; ?>]" accept=".pdf,.docx,.jpg,.png" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                                      <div class="file-preview mt-2" id="preview_<?php echo $field['id']; ?>"></div>
                                              <?php break;
                                                  endswitch;
                                                  break;
                                              endswitch; ?>

                                              <?php if (!empty($field['template_path'])) : ?>
                                                <div class="mt-2">
                                                  <label class="form-label" style="font-style: italic; font-size: 12px;">Template Provided:</label>
                                                  <a href="../Uploads/templates/<?php echo htmlspecialchars($field['template_path']); ?>" class="btn btn-sm btn-outline-primary" download target="_blank">
                                                    <i class="bi bi-download"></i> Download Template
                                                  </a>
                                                </div>
                                              <?php endif; ?>
                                            </div>
                                          <?php endforeach; ?>
                                        </form>
                                      </div>
                                      <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary text-white" form="registrationForm">Submit Registration</button>
                                      </div>
                                    </div>
                                  </div>
                                </div>


                              </div>



                            <?php endif; ?>
                          <?php else : ?>
                            <p class="text-muted ms-auto">No registration form found.</p>
                          <?php endif; ?>
                        </div>






                        <?php if (empty($candidates)): ?>
                          <h5 class="text-center"><b>No candidates have submitted/registered for this election yet!</b></h5>
                        <?php else: ?>
                          <?php foreach ($candidates as $candidate): ?>
                            <div class="candidate-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 10px;">
                              <span class="candidate-id" style="font-weight: bold;">
                                Candidate ID: <?php echo $candidate['id']; ?>
                              </span>
                              <span class="submitted-date" style=" font-size: 0.9em;">
                                Submitted:
                                <?php
                                $date = new DateTime($candidate['created_at']);
                                echo htmlspecialchars($date->format('F j, Y, g:i A'));
                                ?>
                              </span>
                            </div>

                            <table class="candidate-table">
                              <thead>
                                <tr>
                                  <th>Field Name</th>
                                  <th>Value</th>
                                  <th>Status</th>
                                  <th>Actions</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                $responses_stmt = $pdo->prepare("SELECT field_id, value FROM candidate_responses WHERE candidate_id = ?");
                                $responses_stmt->execute([$candidate['id']]);
                                $responses = $responses_stmt->fetchAll(PDO::FETCH_ASSOC);
                                $response_map = array_column($responses, 'value', 'field_id');

                                $files_stmt = $pdo->prepare("SELECT field_id, file_path FROM candidate_files WHERE candidate_id = ?");
                                $files_stmt->execute([$candidate['id']]);
                                $files = $files_stmt->fetchAll(PDO::FETCH_ASSOC);
                                $file_map = array_column($files, 'file_path', 'field_id');

                                $first_row = true;
                                foreach ($field_map as $field_id => $field): ?>
                                  <tr>
                                    <td>
                                      <?php
                                      $field_name = htmlspecialchars($field['field_name']);
                                      if ($field_name === 'full_name') {
                                        echo 'Full Name';
                                      } else if ($field_name === 'student_id') {
                                        echo 'Student ID';
                                      } else {
                                        echo ucfirst(str_replace('_', ' ', $field_name));
                                      }
                                      ?>
                                    </td>
                                    <td>
                                      <?php if ($field['field_type'] === 'file' && isset($file_map[$field_id])): ?>
                                        <?php
                                        $file_path = $file_map[$field_id];
                                        $file_name = basename($file_path);
                                        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                                        ?>

                                        <!-- File View Modal Trigger -->
                                        <button type="button" class="btn btn-link text-primary p-0" data-bs-toggle="modal" data-bs-target="#fileModal<?= $field_id ?>">
                                          <?= htmlspecialchars($file_name) ?>
                                        </button>

                                        <!-- Modal -->
                                        <div class="modal fade" id="fileModal<?= $field_id ?>" tabindex="-1" aria-labelledby="fileModalLabel<?= $field_id ?>" aria-hidden="true">
                                          <div class="modal-dialog modal-md">
                                            <div class="modal-content">
                                              <div class="modal-header">
                                                <h5 class="modal-title" id="fileModalLabel<?= $field_id ?>">File Preview: <?= htmlspecialchars($file_name) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                              </div>
                                              <div class="modal-body text-center">
                                                <?php if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                                  <img src="../login/uploads/candidates/<?= htmlspecialchars($file_path) ?>" alt="File Preview" class="img-fluid" style="max-height: 500px;">
                                                <?php elseif (in_array(strtolower($file_extension), ['pdf'])): ?>
                                                  <iframe src="../login/uploads/candidates/<?= htmlspecialchars($file_path) ?>" frameborder="0" width="100%" height="500px"></iframe>
                                                <?php else: ?>
                                                  <p class="text-muted">File type not supported for preview. <a href="../login/uploads/candidates/<?= htmlspecialchars($file_path) ?>" target="_blank">Download</a></p>
                                                <?php endif; ?>
                                              </div>
                                              <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <a href="../login/uploads/candidates/<?= htmlspecialchars($file_path) ?>" class="btn btn-primary text-white" download>Download</a>
                                              </div>
                                            </div>
                                          </div>
                                        </div>

                                      <?php elseif (isset($response_map[$field_id])): ?>
                                        <?php
                                        $value = $response_map[$field_id];
                                        echo $field['field_type'] === 'checkbox' ? ($value ? 'Yes' : 'No') : htmlspecialchars($value);
                                        ?>
                                      <?php else: ?>
                                        <span class="not-provided">Not provided</span>
                                      <?php endif; ?>
                                    </td>

                                    <?php if ($first_row): ?>
                                      <td rowspan="<?php echo count($field_map); ?>">
                                        <span class="status-<?php echo strtolower($candidate['status']); ?>">
                                          <?php echo ucfirst(htmlspecialchars($candidate['status'])); ?>
                                        </span>
                                      </td>
                                      <td rowspan="<?php echo count($field_map); ?>">
                                        <?php if ($candidate['status'] === 'pending'): ?>
                                          <form method="POST" action="processes/events/update_status.php" style="display: inline;">
                                            <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                            <input type="hidden" name="status" value="accept">
                                            <button type="submit" class="action-btn accept-btn">Accept</button>
                                          </form>
                                          <form method="POST" action="processes/events/update_status.php" style="display: inline;">
                                            <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                            <input type="hidden" name="status" value="reject">
                                            <button type="submit" class="action-btn reject-btn">Reject</button>
                                          </form>
                                        <?php else: ?>
                                          <button class="action-btn" disabled><?php echo $candidate['status']; ?></button>
                                        <?php endif; ?>
                                      </td>
                                      <?php $first_row = false; ?>
                                    <?php endif; ?>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <script src="vendors/js/vendor.bundle.base.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
          document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($fields as $field): ?>
              <?php if ($field['field_type'] === 'file'): ?>
                const fileInput_<?php echo $field['id']; ?> = document.getElementById('field_<?php echo $field['id']; ?>');
                const preview_<?php echo $field['id']; ?> = document.getElementById('preview_<?php echo $field['id']; ?>');

                fileInput_<?php echo $field['id']; ?>.addEventListener('change', function() {
                  preview_<?php echo $field['id']; ?>.innerHTML = ''; // Clear previous preview
                  const file = this.files[0];
                  if (file) {
                    const fileName = file.name;
                    const fileType = file.type;
                    const fileSize = (file.size / 1024).toFixed(2); // Size in KB

                    // Display file details
                    const details = `
                        <p style="margin: 5px 0;"><strong>File:</strong> ${fileName}</p>
                        <p style="margin: 5px 0;"><strong>Type:</strong> ${fileType}</p>
                        <p style="margin: 5px 0;"><strong>Size:</strong> ${fileSize} KB</p>
                    `;
                    preview_<?php echo $field['id']; ?>.innerHTML = details;

                    // Preview for images
                    if (fileType === 'image/jpeg' || fileType === 'image/png') {
                      const img = document.createElement('img');
                      img.src = URL.createObjectURL(file);
                      img.style.maxWidth = '200px';
                      img.style.marginTop = '10px';
                      img.style.display = 'block';
                      img.style.border = '1px solid #ccc';
                      img.style.borderRadius = '4px';
                      preview_<?php echo $field['id']; ?>.appendChild(img);
                    } else if (fileType === 'application/pdf') {
                      const pdfLink = document.createElement('p');
                      pdfLink.innerHTML = '<em style="color: #666;">PDF preview not available in browser</em>';
                      pdfLink.style.margin = '5px 0';
                      preview_<?php echo $field['id']; ?>.appendChild(pdfLink);
                    } else if (fileType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                      const docxNote = document.createElement('p');
                      docxNote.innerHTML = '<em style="color: #666;">DOCX preview not available in browser</em>';
                      docxNote.style.margin = '5px 0';
                      preview_<?php echo $field['id']; ?>.appendChild(docxNote);
                    }
                  }
                });
              <?php endif; ?>
            <?php endforeach; ?>
          });
        </script>

        <script>
          document.addEventListener('DOMContentLoaded', function() {
            const searchSelect = document.getElementById('search_select');
            const fullNameSelect = document.getElementById('full_name_select');
            const studentIdField = document.getElementById('student_id_field');
            let allOptions = Array.from(fullNameSelect.options);
            const dropdown = document.createElement('div');
            dropdown.className = 'dropdown-options';
            if (fullNameSelect && studentIdField) {
              fullNameSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const studentId = selectedOption ? selectedOption.getAttribute('data-student-id') : '';
                studentIdField.value = studentId || '';
                console.log('Selected Full Name:', this.value, 'Student ID:', studentId); // Debug
              });
            } else {
              console.error('full_name_select or student_id_field not found in DOM');
            }

            // Set placeholder
            searchSelect.textContent = searchSelect.getAttribute('data-placeholder');
            searchSelect.classList.add('placeholder');

            // Create dropdown
            document.body.appendChild(dropdown);
            let isDropdownVisible = false;

            // Position dropdown
            function positionDropdown() {
              const rect = searchSelect.getBoundingClientRect();
              dropdown.style.position = 'absolute';
              dropdown.style.top = `${rect.bottom + window.scrollY}px`;
              dropdown.style.left = `${rect.left + window.scrollX}px`;
              dropdown.style.width = `${rect.width}px`;
            }

            // Show/hide dropdown
            function updateDropdown(options) {
              dropdown.innerHTML = '';
              options.forEach(opt => {
                const div = document.createElement('div');
                div.textContent = opt.text;
                div.dataset.value = opt.value;
                div.dataset.studentId = opt.studentId;
                div.addEventListener('click', () => {
                  searchSelect.textContent = opt.text;
                  searchSelect.classList.remove('placeholder');
                  fullNameSelect.value = opt.value;
                  if (studentIdField) studentIdField.value = opt.studentId || '';
                  hideDropdown();
                });
                dropdown.appendChild(div);
              });
              positionDropdown();
              dropdown.style.display = 'block';
              isDropdownVisible = true;
            }

            function hideDropdown() {
              dropdown.style.display = 'none';
              isDropdownVisible = false;
            }

            // Handle input
            searchSelect.addEventListener('input', function() {
              this.classList.remove('placeholder');
              const searchTerm = this.textContent.toLowerCase();
              const filteredOptions = originalOptions.filter(option =>
                option.text.toLowerCase().includes(searchTerm)
              );
              updateDropdown(filteredOptions);
            });

            // Show all options on focus
            searchSelect.addEventListener('focus', function() {
              if (this.textContent === this.getAttribute('data-placeholder')) {
                this.textContent = '';
              }
              updateDropdown(originalOptions);
            });

            // Handle blur
            searchSelect.addEventListener('blur', function() {
              setTimeout(() => {
                if (!this.textContent.trim() || !fullNameSelect.value) {
                  this.textContent = this.getAttribute('data-placeholder');
                  this.classList.add('placeholder');
                }
                hideDropdown();
              }, 100);
            });

            // Handle select change
            if (fullNameSelect && studentIdField) {
              fullNameSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const studentId = selectedOption.getAttribute('data-student-id');
                studentIdField.value = studentId || '23';
                searchSelect.textContent = selectedOption.text;
                searchSelect.classList.remove('placeholder');
              });
            }

            // Keyboard navigation
            searchSelect.addEventListener('keydown', function(e) {
              if (e.key === 'Enter' && isDropdownVisible) {
                e.preventDefault();
                const firstOption = dropdown.querySelector('div');
                if (firstOption) firstOption.click();
              }
            });
          });
        </script>



        <script>
          $(document).ready(function() {
            $('.action-btn').on('click', function(e) {
              e.preventDefault();
              const form = $(this).closest('form');
              const status = form.find('input[name="status"]').val();
              Swal.fire({
                title: `Are you sure you want to ${status} this candidate?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No'
              }).then((result) => {
                if (result.isConfirmed) {
                  form.submit();
                }
              });
            });
          });
        </script>

        <script>
          $(document).ready(function() {
            // Update position options based on party selection
            $('#party_select').change(function() {
              const partyName = $(this).val();
              const electionName = '<?php echo isset($candidacy) ? addslashes($candidacy) : ''; ?>';
              if (partyName && electionName) {
                $.ajax({
                  url: 'processes/get_positions.php',
                  method: 'POST',
                  data: {
                    election_name: electionName,
                    party_name: partyName
                  },
                  success: function(response) {
                    $('#position_select').html(response);
                  },
                  error: function() {
                    $('#position_select').html('<option value="">Error loading positions</option>');
                  }
                });
              } else {
                $('#position_select').html('<option value="">Select Party First</option>');
              }
            });


          });
        </script>

        <script>
          document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            const formId = <?php echo $form_id; ?>;
            const eventId = <?php echo $event_id; ?>;

            form.addEventListener('submit', function(e) {
              e.preventDefault();

              // Detect if the user is on a mobile device
              const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

              if (isMobile && navigator.geolocation) {
                // Prompt for geolocation on mobile devices
                navigator.geolocation.getCurrentPosition(
                  function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    document.getElementById('lat').value = lat;
                    document.getElementById('lon').value = lon;

                    form.action = `processes/register.php?form_id=${formId}&event_id=${eventId}&lat=${lat}&lon=${lon}`;
                    form.submit();
                  },
                  function(error) {
                    Swal.fire({
                      icon: 'warning',
                      title: 'Geolocation Error',
                      text: 'Could not get your location. Proceeding without it.',
                      showConfirmButton: true
                    }).then(() => {
                      form.action = `processes/register.php?form_id=${formId}&event_id=${eventId}&lat=0&lon=0`;
                      form.submit();
                    });
                  }, {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                  }
                );
              } else {
                // Proceed without geolocation for non-mobile devices or if geolocation is unsupported
                if (!navigator.geolocation) {
                  console.warn('Geolocation is not supported by this browser.');
                }
                form.action = `processes/register.php?form_id=${formId}&event_id=${eventId}&lat=0&lon=0 `;
                form.submit();
              }
            });
          });
        </script>

        <?php
        if (isset($_SESSION['STATUS'])) {
          switch ($_SESSION['STATUS']) {
            case 'SUCCESS_CANDIDACY_FROM_ADMIN':
              echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Candidacy Successful!',
                        text: 'Your candidacy has been successfully registered!',
                        showConfirmButton: true
                    });
                </script>";
              break;

            case 'ACCEPT_CANDIDACY_ALL_ADMIN':
              echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Candidacy Successful!',
                        text: 'All existing pending candidacies have been accepted!',
                        showConfirmButton: true
                    });
                </script>";
              break;

            case 'REJECT_CANDIDACY_ALL_ADMIN':
              echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Candidacy Successful!',
                        text: 'All existing pending candidacies have been rejected!',
                        showConfirmButton: true
                    });
                </script>";
              break;

            case 'ERROR_CANDIDACY':
              echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Candidacy Error',
                        text: 'There was an error while registering your candidacy. Please try again!',
                        showConfirmButton: true
                    });
                </script>";
              break;

            case 'ERROR_PARTY_POSITION_DUPLICATION':
              echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Candidacy Registration Error!',
                            text: 'The party and position has already been taken. Please try again!',
                            showConfirmButton: true
                        });
                    </script>";
              break;



            case 'ERROR_CANDIDACY_DUPLICATION':
              echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Candidate already exists!',
                            text: 'There was an error while registering your candidacy for it already exists!',
                            showConfirmButton: true
                        });
                    </script>";
              break;


            case 'LOGOUT_SUCCESSFUL':
              echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Logged Out',
                        text: 'You have successfully logged out!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                </script>";
              break;
          }
          unset($_SESSION['STATUS']);
        }
        ?>

        <?php
        if (isset($_SESSION['STATUS'])) {
          switch ($_SESSION['STATUS']) {
            case 'SUCCESS_CANDIDACY_FROM_ADMIN':
              echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Candidacy Successful!',
                        text: 'Your candidacy has been successfully registered!',
                        showConfirmButton: true
                    });
                </script>";
              break;

            case 'ACCEPT_CANDIDACY_ALL_ADMIN':
              echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Candidacy Successful!',
                        text: 'All existing pending candidacies have been accepted!',
                        showConfirmButton: true
                    });
                </script>";
              break;

            case 'REJECT_CANDIDACY_ALL_ADMIN':
              echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Candidacy Successful!',
                        text: 'All existing pending candidacies have been rejected!',
                        showConfirmButton: true
                    });
                </script>";
              break;

            case 'ERROR_CANDIDACY':
              echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Candidacy Error',
                        text: 'There was an error while registering your candidacy. Please try again!',
                        showConfirmButton: true
                    });
                </script>";
              break;

            case 'ERROR_PARTY_POSITION_DUPLICATION':
              echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Candidacy Registration Error!',
                            text: 'The party and position has already been taken. Please try again!',
                            showConfirmButton: true
                        });
                    </script>";
              break;



            case 'ERROR_CANDIDACY_DUPLICATION':
              echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Candidate already exists!',
                            text: 'There was an error while registering your candidacy for it already exists!',
                            showConfirmButton: true
                        });
                    </script>";
              break;


            case 'LOGOUT_SUCCESSFUL':
              echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Logged Out',
                        text: 'You have successfully logged out!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                </script>";
              break;
          }
          unset($_SESSION['STATUS']);
        }
        ?>

        <?php
        session_start();

        if (isset($_SESSION['STATUS_NEW'])) {
          $status = $_SESSION['STATUS_NEW'];
          $message = $_SESSION['MESSAGE'] ?? '';
          $error = $_SESSION['ERROR_MESSAGE'] ?? '';

          echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
          echo "<script>";

          if ($status === "CANDIDATE_STATUS_UPDATE" && $message) {
            echo "Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '" . addslashes($message) . "'
        });";
          } elseif ($status === "CANDIDATE_ACCEPTED_ERROR" && $error) {
            echo "Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '" . addslashes($error) . "'
        });";
          }

          echo "</script>";

          // Clear session so it doesn't show again
          unset($_SESSION['STATUS_NEW'], $_SESSION['MESSAGE'], $_SESSION['ERROR_MESSAGE']);
        }
        ?>
        <?php include('includes/alerts.php'); ?>

</body>

<button class="back-to-top" id="backToTop" title="Go to top">
  <i class="mdi mdi-arrow-up"></i>
</button>

</html>