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

    // Calculate SMTP capacity (ADD THIS SECTION)
    if ($adviserId) {
        $stmt = $pdo->prepare("SELECT capacity FROM email WHERE adviser_id = ?");
        $stmt->execute([$adviserId]);
        $smtp_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $smtp_capacity = 0;
        foreach ($smtp_details as $detail) {
            $smtp_capacity += $detail['capacity'] ?? 0;
        }
        $total_limit = count($smtp_details) * 500;
    } else {
        $smtp_capacity = 0;
        $total_limit = 500;
    }
}

// Get ongoing election info
$stmt = $pdo->prepare("SELECT * FROM voting_periods WHERE status = 'Ongoing' LIMIT 1");
$stmt->execute();
$ongoingElection = $stmt->fetch(PDO::FETCH_ASSOC);

if ($ongoingElection) {
    $electionId = $ongoingElection['id'];
    $electionName = $ongoingElection['election_id'];
} else {
    $electionId = null;
    $electionName = null;
}

$sentLogs = [];

if ($adviserEmail && $adviser) {
    $params = [];

    // adviser filters
    $params[] = $adviser['college'];
    $params[] = $adviser['department'];
    $params[] = $adviser['wmsu_campus'];
    $params[] = $adviser['year'];

    $externalCampusCondition = '';
    if ($adviser['external_campus'] !== 'None' && !is_null($adviser['external_campus'])) {
        $externalCampusCondition = "AND v.external_campus = ?";
        $params[] = $adviser['external_campus'];
    } else {
        $externalCampusCondition = "AND (v.external_campus IS NULL OR v.external_campus = 'None')";
    }

    // FIXED: Better election condition
    $electionCondition = '';
    if ($electionId) {
        $electionCondition = "AND q.election_id = ?";
        $params[] = $electionId;
    }

    // FIXED: Simplified query with better joins
    $query = "
    SELECT 
    v.first_name,
    v.middle_name,
    v.last_name,
        v.student_id,
    v.email AS voter_email,
    v.college,
    v.department,
    v.wmsu_campus,
    v.external_campus,
    v.year_level,
    v.course,
    COUNT(q.id) AS total_emails_sent,
    q.election_id,
    GROUP_CONCAT(
        CONCAT(
            q.status,
            ' (',
            DATE_FORMAT(q.sent_at, '%M %e, %Y %l:%i %p'),
            ' - ',
            COALESCE(vp.name, 'N/A'),
            ')'
        )
        ORDER BY q.sent_at DESC
        SEPARATOR '<br>'
    ) AS email_details,
    GROUP_CONCAT(DISTINCT vp.name ORDER BY vp.name SEPARATOR ', ') AS voting_period_names
FROM voters v
LEFT JOIN qr_sending_log q
    ON v.student_id = q.student_id
LEFT JOIN voting_periods vp
    ON q.election_id = vp.id
WHERE v.college = ?
  AND v.department = ?
  AND v.wmsu_campus = ?
  AND v.year_level = ?
      $externalCampusCondition
      $electionCondition
    GROUP BY 
        v.student_id
    ORDER BY v.student_id ASC
    ";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $sentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $sentLogs = [];
    }
}
?>
<!--  -->

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
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <h3 class="mb-0"><b>Verified Voters with QR Details</b></h3>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="electionFilter" class="form-label"><b>Filter by Election:</b></label>
                                                            <select class="form-select" id="electionFilter">
                                                                <option value="">All Elections</option>

                                                                <?php
                                                                // FIXED: Query the correct table 'elections' instead of 'voting_periods'
                                                                $stmt = $pdo->prepare("SELECT id, election_id FROM voting_periods ORDER BY created_at ASC");
                                                                $stmt->execute();
                                                                $allElections = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                                foreach ($allElections as $election) {
                                                                    $selected = ($electionId == $election['id']) ? 'selected' : '';
                                                                    echo "<option value='{$election['id']}' $selected>{$election['election_id']}</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">


                                                        <br>

                                                        <div style="padding: 10px; border-radius: 8px; background-color: #f8f9fa; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
                                                            <h5 style="margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                                                                <b>📊 Current Email Statistics</b>

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
                                                            <table class="table table-bordered table-striped text-center" id="votersTable">
                                                                <thead>
                                                                    <tr>
                                                                        <th>#</th>
                                                                        <th>Student ID</th>
                                                                        <th>Full Name</th>
                                                                        <th>College</th>
                                                                        <th>WMSU Campus</th>
                                                                        <th>External Campus</th>
                                                                        <th>Course</th>
                                                                        <th>Department</th>
                                                                        <th>Year Level</th>
                                                                        <th>Email</th>
                                                                        <th>Total Emails Sent</th>
                                                                        <th>Elections</th>
                                                                        <th>Email Details</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    ?>
                                                                    <?php foreach ($sentLogs as $index => $log): ?>
                                                                        <tr>
                                                                            <td><?= $index + 1 ?></td>
                                                                            <td><?= htmlspecialchars($log['student_id']) ?></td>
                                                                            <td><?= htmlspecialchars($log['first_name'] . ' ' . $log['middle_name'] . ' ' . $log['last_name']) ?></td>
                                                                            <td><?= htmlspecialchars($log['college']) ?></td>
                                                                            <td><?= htmlspecialchars($log['wmsu_campus']) ?></td>
                                                                            <td><?= htmlspecialchars($log['external_campus'] ?? '') ?: '-' ?></td>

                                                                            <td><?= htmlspecialchars($log['course']) ?></td>
                                                                            <td><?= htmlspecialchars($log['department']) ?></td>
                                                                            <td><?= htmlspecialchars($log['year_level']) ?></td>
                                                                            <td><?= htmlspecialchars($log['voter_email'] ?? '') ?></td>
                                                                            <td><?= htmlspecialchars($log['total_emails_sent']) ?></td>
                                                                            <td><?= htmlspecialchars($log['voting_period_names'] ?? 'No elections') ?></td>
                                                                            <td>
                                                                                <?php
                                                                                $details = explode('<br>', $log['email_details'] ?? '');
                                                                                foreach ($details as $detail) {
                                                                                    if (!empty(trim($detail))) {
                                                                                        preg_match('/^(\w+)\s*\(([^)]+)\s*-\s*([^)]+)\)$/', $detail, $matches);

                                                                                        if (count($matches) === 4) {
                                                                                            $status = strtolower($matches[1]);
                                                                                            $timestamp = $matches[2];
                                                                                            $votingPeriodName = $matches[3];

                                                                                            $badgeClass = $status === 'sent' ? 'badge-success' : ($status === 'failed' ? 'badge-danger' : 'badge-secondary');
                                                                                            $formattedTime = date('M j, Y g:i A', strtotime($timestamp));
                                                                                            $displayText = strtoupper($status) . ' - ' . $votingPeriodName . ' at ' . $formattedTime;

                                                                                            // Only add clickable data for SENT badges
                                                                                            $dataAttributes = $status === 'sent'
                                                                                                ? "data-student-id='{$log['student_id']}' data-voting-period='{$log['election_id']}'"
                                                                                                : "";

                                                                                            echo "<span class='badge $badgeClass me-1 mb-1 qr-badge' style='cursor: pointer;' $dataAttributes>$displayText</span><br>";
                                                                                        } else {
                                                                                            echo "<span class='badge rounded-pill text-bg-primary' style='color:black; border: 1px solid black'>$detail</span><br>";
                                                                                        }
                                                                                    }
                                                                                }
                                                                                ?>

                                                                            </td>


                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>


                                                            <!-- QR Code Modal -->
                                                            <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="qrModalLabel">Student QR Code</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body text-center">
                                                                            <img id="qrModalImg" src="" alt="QR Code" class="img-fluid">
                                                                            <p id="qrModalStudentId" class="mt-2"></p>
                                                                            <p id="qrModalVotingPeriod" class="mb-0"></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>





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
                                                                                        <p><strong>College:</strong> <span id="modalCollege"></span></p>
                                                                                        <p><strong>Course:</strong> <span id="modalCourse"></span></p>
                                                                                        <p><strong>Department:</strong> <span id="modalDepartment"></span></p>
                                                                                        <p><strong>Year Level:</strong> <span id="modalYearLevel"></span></p>
                                                                                        <p><strong>WMSU Campus Location:</strong> <span id="modalWmsuCampus"></span></p>
                                                                                        <p><strong>WMSU ESU Campus Location:</strong> <span id="modalExternalCampus"></span></p>
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
                                                                    // Initialize DataTables
                                                                    var table = $('#votersTable').DataTable({
                                                                        responsive: true,
                                                                        pageLength: 10,
                                                                        language: {
                                                                            search: "Filter records:"
                                                                        }
                                                                    });

                                                                    // Election filter functionality
                                                                    $('#electionFilter').on('change', function() {
                                                                        var filterValue = $(this).val();

                                                                        if (filterValue === 'current') {
                                                                            // Filter for current election
                                                                            table.column(4).search('<?= $electionName ?>', true, false).draw();
                                                                        } else if (filterValue === '') {
                                                                            // Show all elections
                                                                            table.column(4).search('').draw();
                                                                        } else {
                                                                            // Filter by specific election ID (you might need to adjust this based on your data)
                                                                            table.column(4).search(filterValue).draw();
                                                                        }
                                                                    });

                                                                    // Apply search functionality to footer inputs
                                                                    $('#votersTable tfoot th').each(function(index) {
                                                                        var title = $('#votersTable thead th').eq(index).text();
                                                                        // Only add input for searchable columns (skip Manage column)
                                                                        if (index < 9 && index != 5) { // Columns 0-4, 6-8 are text inputs
                                                                            $(this).html('<input type="text" class="form-control" placeholder="Search ' + title + '" />');
                                                                        }
                                                                    });

                                                                    // Apply text input searches
                                                                    table.columns([0, 1, 2, 3, 4, 6, 7, 8]).every(function() {
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
                                                                        table.column(5).search(val ? '^' + val + '$' : '', true, false).draw();
                                                                    });

                                                                    // Apply college dropdown search
                                                                    $('#academicYearFilter').on('change', function() {
                                                                        var val = $(this).val();
                                                                        table.column(8).search(val, false, false).draw(); // regex = false, smart = false


                                                                    });
                                                                });
                                                            </script>

                                                            <script>
                                                                document.addEventListener('click', function(e) {
                                                                    const badge = e.target.closest('.qr-badge');
                                                                    if (!badge) return;

                                                                    const studentId = badge.dataset.studentId;
                                                                    const votingPeriod = badge.dataset.votingPeriod;

                                                                    console.log(votingPeriod);

                                                                    const qrModalImg = document.getElementById('qrModalImg');
                                                                    const qrModalStudentId = document.getElementById('qrModalStudentId');
                                                                    const qrModalVotingPeriod = document.getElementById('qrModalVotingPeriod');

                                                                    qrModalImg.src = '';
                                                                    qrModalStudentId.textContent = 'Loading...';
                                                                    qrModalVotingPeriod.textContent = '';

                                                                    const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
                                                                    qrModal.show();

                                                                    fetch('get_student_qr.php', {
                                                                            method: 'POST',
                                                                            headers: {
                                                                                'Content-Type': 'application/x-www-form-urlencoded'
                                                                            },
                                                                            body: `student_id=${encodeURIComponent(studentId)}&voting_period_name=${encodeURIComponent(votingPeriod)}`
                                                                        })
                                                                        .then(res => res.json())
                                                                        .then(data => {
                                                                            if (data.status === 'success') {
                                                                                qrModalImg.src = data.qr_url;
                                                                                qrModalStudentId.textContent = 'Student ID: ' + studentId;
                                                                                qrModalVotingPeriod.textContent = 'Voting Period: ' + data.voting_period_name;
                                                                            } else {
                                                                                qrModalStudentId.textContent = data.message || 'Failed to generate QR';
                                                                            }
                                                                        })
                                                                        .catch(() => {
                                                                            qrModalStudentId.textContent = 'Error generating QR code';
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

                                                                if (!studentId) {
                                                                    Swal.fire('Missing Data', 'Student ID is missing.', 'warning');
                                                                    return;
                                                                }

                                                                btn.disabled = true;
                                                                btn.innerHTML = 'Sending...';

                                                                fetch('send_qr.php', {
                                                                        method: 'POST',
                                                                        headers: {
                                                                            'Content-Type': 'application/x-www-form-urlencoded',
                                                                        },
                                                                        body: 'student_id=' + encodeURIComponent(studentId)
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
                                                                                location.reload(); // 🔄 Reload only if confirmed and successful
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
                                                            $('#sendAllQrBtn').click(function() {
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
                                                                                    limit: 10 // Adjust batch size as needed
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