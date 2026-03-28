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

  // Allow letters, numbers, spaces, and common symbols
  return preg_replace('/[^\p{L}\p{N}\s\-_.,!?@#$%&*()+=:;"\'\/\\[\\]{}<>|]/u', '', $input);
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
      $candidacy = sanitizeString($event['candidacy'], 300);
      $candidacy_id = $event['candidacy'];


      $candidacy_stmt = $pdo->prepare("
    SELECT e.*, ay.year_label, ay.semester
    FROM elections e
    LEFT JOIN academic_years ay ON e.academic_year_id = ay.id
    WHERE e.id = ?
");
      $candidacy_stmt->execute([$candidacy_id]);
      $candidacy_details = $candidacy_stmt->fetch(PDO::FETCH_ASSOC);


      $candidacy_election_name = $candidacy_details['election_name'];



      // Fetch registration form details
      $form_stmt = $pdo->prepare("
                SELECT id, form_name, status
                FROM registration_forms 
                WHERE election_name = ?
            ");
      $form_stmt->execute([$candidacy_id]);
      $form = $form_stmt->fetch(PDO::FETCH_ASSOC);

      if ($form) {
        $form_id = $form['id'];



        $form_name = sanitizeString($form['form_name'], 300);



        // Fetch all candidates for this form
        $candidates_stmt = $pdo->prepare("
                    SELECT * FROM candidates 
                    WHERE form_id = ? 
                    ORDER BY created_at DESC
                ");
        $candidates_stmt->execute([$form_id]);
        $candidates = $candidates_stmt->fetchAll(PDO::FETCH_ASSOC);
        // === FETCH FIELDS (your existing code) ===
        $fields_stmt = $pdo->prepare("
    SELECT id, field_name, field_type 
    FROM form_fields 
    WHERE form_id = ?
");
        $fields_stmt->execute([$form_id]);
        $fields = $fields_stmt->fetchAll(PDO::FETCH_ASSOC);
        $field_map = array_column($fields, null, 'id'); // indexed by id

        // === EXCLUDE PHOTO FIELDS FROM DISPLAY IN ROWS ===
        $photo_field_names = ['photo', 'profile_picture', 'picture', 'id_photo', 'candidate_photo', 'profile_pic'];
        $filtered_field_map = [];

        foreach ($field_map as $id => $field) {
          if (!in_array(strtolower($field['field_name']), $photo_field_names)) {
            $filtered_field_map[$id] = $field;
          }
        }

        // Also find the actual photo path for the Picture column
        $photo_path = null;
        foreach ($field_map as $id => $field) {
          if (in_array(strtolower($field['field_name']), $photo_field_names) && isset($file_map[$id])) {
            $photo_path = $file_map[$id];
            break;
          }
        }
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
    v.id, 
    v.student_id,
    TRIM(CONCAT(
        v.first_name, ' ',
        COALESCE(v.middle_name, ''),
        CASE WHEN v.middle_name IS NULL OR v.middle_name = '' THEN '' ELSE ' ' END,
        v.last_name
    )) AS full_name
FROM voters v
LEFT JOIN candidates c 
    ON v.student_id = (
        SELECT cr.value 
        FROM candidate_responses cr
        JOIN form_fields ff ON cr.field_id = ff.id
        WHERE cr.candidate_id = c.id 
          AND ff.field_name = 'student_id'
          AND ff.form_id = c.form_id
          AND c.form_id = ?
        LIMIT 1
    )
WHERE 
    c.id IS NULL
    AND v.status = 'confirmed';

");
    $stmt->execute([$form_id]);
    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $stmt = $pdo->query("SELECT DATABASE()");
    $currentDb = $stmt->fetchColumn();

    // Fetch form_id from registration_forms using election_name (candidacy)
    $candidacy_stmt = $pdo->prepare("
                        SELECT id, election_name 
                        FROM elections 
                        WHERE id = ?
                    ");
    $candidacy_stmt->execute([$candidacy_id]);
    $candidacy_results = $candidacy_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidacy_results) {
      throw new Exception("No active registration form found for this candidacy");
    }

    $election_id_real = $candidacy_results['id'];
    $candidacy_name = $candidacy_results['election_name'];

    $party_stmt = $pdo->prepare("
     SELECT name 
     FROM parties 
     WHERE election_id = ? AND status = 'Approved' 
     ORDER BY name
 ");
    $party_stmt->execute([$election_id_real]);
    $parties = $party_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all events for the dropdown
    $all_events_stmt = $pdo->prepare("
        SELECT ev.id, e.election_name
        FROM events ev
        JOIN elections e ON ev.candidacy = e.id
        WHERE ev.candidacy IS NOT NULL
        ORDER BY ev.created_at DESC
    ");
    $all_events_stmt->execute();
    $all_events = $all_events_stmt->fetchAll(PDO::FETCH_ASSOC);

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
      <nav class="navbar default-layout col-lg-12 col-12 p-0 d-flex align-items-top flex-row">
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
                          <h4><b>
                              <?php
                              if (!empty($form['form_name'])) {
                                echo "<h2 class='fw-bold mbIf -2'>Candidacy Filing</h2>";

                                echo "<small>Election: " . htmlspecialchars($candidacy_details['election_name']);
                                if (!empty($candidacy_details['year_label'])) {
                                  echo " | SY: " . htmlspecialchars($candidacy_details['year_label']) . " | Semester: " . htmlspecialchars($candidacy_details['semester']);
                                }
                                echo "</small>";
                              } else {
                                echo "No active registration form available for candidacy yet.";
                              }
                              ?>
                              <div class="mt-3 d-flex">
                                <small>Select Election:</small>
                                <select class="form-select" onchange="if(this.value) window.location.href=this.value">
                                  <?php foreach ($all_events as $evt): ?>
                                    <option value="?id=<?= $evt['id'] ?>" <?= $evt['id'] == $event_id ? 'selected' : '' ?>>
                                      <?= htmlspecialchars($evt['election_name']) ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                            </b></h4>

                          <?php
                          $event_id = (int)$_GET['id'];

                          $stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM candidates c
    JOIN registration_forms rf ON c.form_id = rf.id
    JOIN events e ON rf.election_name = e.candidacy
    WHERE e.id = ? AND c.status = 'pending'
");
                          $stmt->execute([$event_id]);

                          $pendingCount = $stmt->fetchColumn();
                          $disabled = ($pendingCount == 0) ? 'disabled' : '';
                          ?>

                          <?php if (isset($form) && isset($form['status'])) : ?>

                            <?php if ($form['status'] === 'active') : ?>
                              <div class="ms-auto d-flex align-items-center">

                                <form class="me-2">
                                  <button type="button" class="btn btn-primary btn-sm text-white"
                                    data-bs-toggle="modal" data-bs-target="#addCandidateModal">
                                    Add Candidate
                                  </button>
                                </form>

                                <form method="POST" action="processes/events/update_candidate_status.php?id=<?php echo htmlspecialchars($_GET['id']); ?>" class="me-2 bulk-action-form">
                                  <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
                                  <input type="hidden" name="status" value="accept">
                                  <button type="submit" class="btn btn-success btn-sm text-white" <?php echo $disabled; ?>>
                                    Accept All Pending
                                  </button>
                                </form>

                                <form method="POST" action="processes/events/update_candidate_status.php?id=<?php echo htmlspecialchars($_GET['id']); ?>" class="me-2 bulk-action-form">
                                  <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
                                  <input type="hidden" name="status" value="decline">
                                  <button type="submit" class="btn btn-danger btn-sm text-white" <?php echo $disabled; ?>>
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
                                                  <input class="form-control" type="file" required id="field_<?php echo $field['id']; ?>" name="fields[<?php echo $field['id']; ?>]" accept=".jpg,.jpeg,.png" ?>
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
                                  <th>Picture</th>
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

                                $voter_college_name = null;
                                $voter_dept_name    = null;

                                // Find the student_id stored in this candidate's responses
                                $sid_for_lookup = null;
                                foreach ($field_map as $fid => $field) {
                                  if ($field['field_name'] === 'student_id' && isset($response_map[$fid])) {
                                    $sid_for_lookup = $response_map[$fid];
                                    break;
                                  }
                                }

                                if ($sid_for_lookup) {
                                  $voter_info_stmt = $pdo->prepare("
        SELECT c.college_name, d.department_name
        FROM voters v
        LEFT JOIN colleges    c ON v.college    = c.college_id
        LEFT JOIN departments d ON v.department = d.department_id
        WHERE v.student_id = ?
        LIMIT 1
    ");
                                  $voter_info_stmt->execute([$sid_for_lookup]);
                                  $voter_info = $voter_info_stmt->fetch(PDO::FETCH_ASSOC);
                                  if ($voter_info) {
                                    $voter_college_name = $voter_info['college_name'];
                                    $voter_dept_name    = $voter_info['department_name'];
                                  }
                                }

                                $first_row = true;

                                // Find the photo field (assuming field_name = 'photo' or 'profile_picture')
                                $photo_path = null;
                                $photo_field_id = null;
                                foreach ($field_map as $fid => $field) {
                                  if (in_array($field['field_name'], ['photo', 'profile_picture', 'picture', 'id_photo'])) {
                                    if (isset($file_map[$fid])) {
                                      $photo_path = $file_map[$fid];
                                      $photo_field_id = $fid;
                                      break;
                                    }
                                  }
                                }
                                ?>

                                <?php foreach ($filtered_field_map as $field_id => $field): ?>
                                  <tr>
                                    <?php if ($first_row): ?>
                                      <!-- PICTURE COLUMN - Only shown on first row -->
                                      <td rowspan="<?php echo count($field_map) + 1; ?>" class="text-center align-middle">
                                        <?php if ($photo_path):; ?>
                                          <?php
                                          $full_photo_url = "../login/uploads/candidates/" . htmlspecialchars($photo_path);
                                          $unique_photo_modal = "photoModal_" . $candidate['id'];
                                          ?>
                                          <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#<?= $unique_photo_modal ?>">
                                            <img src="<?= $full_photo_url ?>"
                                              alt="Candidate Photo"
                                              class="rounded-circle border"
                                              style="width: 100px; height: 100px; object-fit: cover; cursor: pointer;">
                                          </button>

                                          <!-- Large Photo Modal -->
                                          <div class="modal fade" id="<?= $unique_photo_modal ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                              <div class="modal-content">
                                                <div class="modal-header">
                                                  <h5 class="modal-title">Candidate Photo</h5>
                                                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-center">
                                                  <img src="<?= $full_photo_url ?>" class="img-fluid rounded" style="max-height: 80vh;">
                                                </div>
                                                <div class="modal-footer">
                                                  <a href="<?= $full_photo_url ?>" class="btn btn-primary" style="color:white !important;">Download</a>
                                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                              </div>
                                            </div>
                                          </div>
                                        <?php else: ?>
                                          <div class="bg-light border rounded-circle d-inline-block"
                                            style="width: 100px; height: 100px; line-height: 100px; text-align: center;">
                                            <span class="text-muted">No Photo</span>
                                          </div>
                                        <?php endif; ?>
                                      </td>
                                    <?php endif; ?>

                                    <!-- Field Name -->
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

                                    <!-- Value / File -->
                                    <td>
                                      <?php if ($field['field_type'] === 'file' && isset($file_map[$field_id])): ?>
                                        <?php
                                        $file_path = $file_map[$field_id];
                                        $file_name = basename($file_path);
                                        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                        $unique_modal_id = "fileModal_" . $candidate['id'] . "_" . $field_id;
                                        ?>

                                        <button type="button" class="btn btn-primary text-white p-1" data-bs-toggle="modal" data-bs-target="#<?= $unique_modal_id ?>">
                                          <span class="mdi mdi-eye"></span> View
                                        </button>

                                        <!-- Your existing modal code here (same as before) -->
                                        <div class="modal fade" id="<?= $unique_modal_id ?>" tabindex="-1" aria-labelledby="<?= $unique_modal_id ?>Label" aria-hidden="true">
                                          <div class="modal-dialog modal-xl modal-dialog-centered">
                                            <div class="modal-content">

                                              <div class="modal-header">
                                                <h5 class="modal-title" id="<?= $unique_modal_id ?>Label"><?= htmlspecialchars($file_name) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                              </div>

                                              <div class="modal-body" style="min-height: 70vh;">

                                                <?php if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>

                                                  <!-- IMAGE PREVIEW -->
                                                  <img src="../login/uploads/candidates/<?= $file_path ?>" class="img-fluid w-100" alt="Image preview">

                                                <?php elseif ($file_extension === 'pdf'): ?>

                                                  <!-- PDF PREVIEW -->
                                                  <iframe src="../login/uploads/candidates/<?= $file_path ?>" class="w-100" style="height:70vh;" frameborder="0"></iframe>

                                                <?php elseif (in_array($file_extension, ['mp4', 'webm', 'ogg'])): ?>

                                                  <!-- VIDEO PREVIEW -->
                                                  <video controls class="w-100" style="max-height:70vh;">
                                                    <source src="<?= $file_path ?>" type="video/<?= $file_extension ?>">
                                                    Your browser does not support the video tag.
                                                  </video>

                                                <?php elseif (in_array($file_extension, ['mp3', 'wav', 'ogg'])): ?>

                                                  <!-- AUDIO PREVIEW -->
                                                  <audio controls class="w-100">
                                                    <source src="<?= $file_path ?>">
                                                    Your browser does not support the audio element.
                                                  </audio>

                                                <?php else: ?>

                                                  <!-- UNKNOWN FILE TYPE -->
                                                  <p class="text-muted">
                                                    Cannot preview this file type.
                                                    <br><strong><?= htmlspecialchars($file_name) ?></strong>
                                                  </p>
                                                  <a href="<?= $file_path ?>" download class="btn btn-primary">
                                                    <i class="mdi mdi-download"></i> Download File
                                                  </a>

                                                <?php endif; ?>

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

                                    <!-- Status & Actions - Only on first row -->
                                    <?php if ($first_row): ?>
                                      <td rowspan="<?php echo count($field_map); ?> + 1">
                                        <span class="status-<?php echo strtolower($candidate['status']); ?>">
                                          <?php echo ucfirst(htmlspecialchars($candidate['status'])); ?>
                                        </span>
                                      </td>
                                      <!-- <td rowspan="<?php echo count($field_map); ?>">
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
                                          <button class="action-btn" disabled><?php echo ucfirst($candidate['status']); ?></button>
                                        <?php endif; ?>
                                      </td> -->

                                      <td rowspan="<?= count($field_map) ?> + 1">

                                        <?php if ($candidate['status'] === 'pending'): ?>
                                          <!-- Accept -->
                                          <form method="POST" action="processes/events/update_status.php" style="display:inline;">
                                            <input type="hidden" name="candidate_id" value="<?= $candidate['id'] ?>">
                                            <input type="hidden" name="event_id" value="<?= $event_id ?>">
                                            <input type="hidden" name="status" value="accept">
                                            <button type="submit" class="action-btn accept-btn">Accept</button>
                                          </form>
                                          <!-- Reject -->
                                          <form method="POST" action="processes/events/update_status.php" style="display:inline;">
                                            <input type="hidden" name="candidate_id" value="<?= $candidate['id'] ?>">
                                            <input type="hidden" name="event_id" value="<?= $event_id ?>">
                                            <input type="hidden" name="status" value="reject">
                                            <button type="submit" class="action-btn reject-btn">Reject</button>
                                          </form>
                                        <?php else: ?>
                                          <button class="action-btn" disabled><?= ucfirst($candidate['status']) ?></button>
                                        <?php endif; ?>

                                        <!-- ── Dispose button — available for ALL statuses, but blocked if admin-added ── -->
                                        <?php if (empty($candidate['admin_config']) || (int)$candidate['admin_config'] === 0): ?>
                                          <!-- <button type="button"
                                            class="action-btn dispose-btn"
                                            style="background:#6c757d; color:white; margin-top:4px;"
                                            onclick="confirmDispose(<?= (int)$candidate['id'] ?>, <?= (int)$event_id ?>)">
                                            Dispose
                                          </button> -->
                                        <?php else: ?>
                                          <!-- <button type="button" class="action-btn" style="background:#dee2e6; color:#6c757d; margin-top:4px;" disabled title="Added by admin — cannot be disposed here">
                                            Dispose
                                          </button> -->
                                        <?php endif; ?>

                                      </td>

                                      <?php $first_row = false; ?>
                                    <?php endif; ?>
                                  </tr>

                                <?php endforeach; ?>
                                <tr>
                                  <td>College & Department</td>
                                  <td>
                                    <?php if ($voter_college_name || $voter_dept_name): ?>

                                      <?php if ($voter_college_name): ?>
                                        <?= htmlspecialchars($voter_college_name) ?>
                                      <?php else: ?>
                                        <span class="not-provided">College not available</span>
                                      <?php endif; ?>

                                      <?php if ($voter_college_name && $voter_dept_name): ?>
                                        <br>
                                      <?php endif; ?>
                                      ||
                                      <?php if ($voter_dept_name): ?>
                                        <?= htmlspecialchars($voter_dept_name) ?>
                                      <?php else: ?>
                                        <span class="not-provided">Department not available</span>
                                      <?php endif; ?>

                                    <?php else: ?>
                                      <span class="not-provided">Not available</span>
                                    <?php endif; ?>
                                  </td>
                                </tr>


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
                  Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we update the status.',
                    allowOutsideClick: false,
                    didOpen: () => {
                      Swal.showLoading();
                    }
                  });
                  form.submit();
                }
              });
            });

            // Bulk Action Handler
            $('.bulk-action-form').on('submit', function(e) {
              e.preventDefault();
              const form = this;
              const status = $(this).find('input[name="status"]').val();
              const actionText = status === 'accept' ? 'Accept' : 'Decline';

              Swal.fire({
                title: `Are you sure?`,
                text: `You are about to ${actionText} ALL pending candidates. This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: `Yes, ${actionText} All`,
                cancelButtonText: 'Cancel'
              }).then((result) => {
                if (result.isConfirmed) {
                  Swal.fire({
                    title: 'Processing Bulk Action...',
                    text: 'Sending emails and updating statuses. Please do not close this window.',
                    allowOutsideClick: false,
                    didOpen: () => {
                      Swal.showLoading();
                    }
                  });
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
              const electionName = '<?php echo isset($election_id_real) ? addslashes($election_id_real) : ''; ?>';
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

        <form id="disposeForm" method="POST" action="processes/events/dispose_candidate.php" style="display:none;">
          <input type="hidden" name="candidate_id" id="disposeId">
          <input type="hidden" name="event_id" id="disposeEventId">
        </form>

        <script>
          function confirmDispose(candidateId, eventId) {
            Swal.fire({
              title: 'Dispose Candidate?',
              html: 'This will <strong>permanently delete</strong> the candidate and all their files/responses.<br>This cannot be undone.',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#dc3545',
              confirmButtonText: 'Yes, Dispose',
              cancelButtonText: 'Cancel'
            }).then(result => {
              if (result.isConfirmed) {
                document.getElementById('disposeId').value = candidateId;
                document.getElementById('disposeEventId').value = eventId;
                document.getElementById('disposeForm').submit();
              }
            });
          }
        </script>


        <script>
          document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            const formId = <?php echo $form_id; ?>;
            const eventId = <?php echo $event_id; ?>;

            form.addEventListener('submit', function(e) {
              e.preventDefault();

              // Show loading Swal
              Swal.fire({
                title: 'Submitting Registration...',
                text: 'Please wait while we process your candidacy.',
                allowOutsideClick: false,
                didOpen: () => {
                  Swal.showLoading();
                }
              });

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
                    // Don't show another Swal, just submit. The loading one is already active.
                    form.action = `processes/register.php?form_id=${formId}&event_id=${eventId}&lat=0&lon=0`;
                    form.submit();
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
            text: " . json_encode($message) . "
        });";
          } elseif ($status === "CANDIDATE_ACCEPTED_ERROR" && $error) {
            echo "Swal.fire({
            icon: 'error',
            title: 'Error',
            text: " . json_encode($error) . "
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