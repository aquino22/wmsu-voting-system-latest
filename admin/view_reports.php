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

$voting_period_id = $_GET['voting_period_id'] ?? null;
if (!$voting_period_id) {
    header("Location: reports.php");
    exit();
}

// Step 1: Fetch voting period using the passed `voting_period_id`
$voting_period_id = $_GET['voting_period_id'] ?? null;

if (!$voting_period_id) {
    throw new Exception("No voting period ID provided.");
}

// Get the voting period based on the passed ID
$stmt = $pdo->prepare("  
 SELECT vp.*, e.election_name 
    FROM voting_periods vp
    JOIN elections e ON vp.election_id = e.id
    WHERE  vp.id = ?
    LIMIT 1");
$stmt->execute([$voting_period_id]);
$votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$votingPeriod) {
    throw new Exception("No voting period found with the given ID.");
}

// Extract voting period details
$votingPeriodName = $votingPeriod['election_name'];
$votingPeriodCreatedAt = $votingPeriod['created_at'];
$votingPeriodCandidacyId = $votingPeriod['election_id'];





// Step 2: Match the voting period name with elections where status is 'Ongoing' or 'Published'
$stmt = $pdo->prepare("
       SELECT * 
       FROM elections
       WHERE id = ?
     
   ");
$stmt->execute([$votingPeriodCandidacyId]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);



if (!$election) {
    throw new Exception("No matching election found for the current voting period.");
}



// Fetch all unique colleges from voters, join to get college_name
$stmt = $pdo->query("
    SELECT DISTINCT v.college AS college_id, c.college_name
    FROM voters v
    JOIN colleges c ON v.college = c.college_id
    WHERE v.college IS NOT NULL
    ORDER BY c.college_name
");
$colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);

$all_departments = $pdo->query("
    SELECT v.college, d.department_id, d.department_name
    FROM voters v
    LEFT JOIN departments d ON v.department = d.department_id
")->fetchAll(PDO::FETCH_ASSOC);

$departments_by_college = [];

foreach ($all_departments as $row) {
    $departments_by_college[$row['college']][$row['department_id']] = $row['department_name'];
}
foreach ($departments_by_college as &$depts) {
    $depts = array_unique($depts);
}
unset($depts);

?>

<?php

include('includes/conn.php');

// Check admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Get voting period ID
$voting_period_id = $_GET['voting_period_id'] ?? null;


// Validate voting period exists
try {
    if (!$voting_period_id) {
        throw new Exception("No voting period specified");
    }

    // Fetch voting period
    $stmt = $pdo->prepare("SELECT * FROM voting_periods WHERE id = ?");
    $stmt->execute([$voting_period_id]);
    $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$votingPeriod) {
        throw new Exception("Voting period not found");
    }

    $votingPeriodElectionId = $votingPeriod['election_id'];

    $stmt = $pdo->prepare("
    SELECT * FROM elections 
    WHERE id = ?
      AND status IN ('Ongoing', 'Scheduled', 'Published', 'Ended', 'published')
");
    $stmt->execute([$votingPeriodElectionId]); // <-- wrap in array
    $election = $stmt->fetch(PDO::FETCH_ASSOC);


    $stmt = $pdo->prepare("
        SELECT * FROM candidacy 
        WHERE (election_id = ?)
        AND status IN ('Ongoing', 'Scheduled', 'Published', 'Ended', 'published')
  
    ");
    $stmt->execute([$votingPeriodCandidacyId]);
    $candidacy_details = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
    SELECT * FROM voting_periods 
    WHERE election_id = ?
    AND status IN ('Ongoing', 'Scheduled', 'Published', 'Ended', 'published')
");

    $stmt->execute([$votingPeriodCandidacyId]);
    $voting_period_details = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['ERROR'] = $e->getMessage();
    echo $e->getMessage();
    // header("Location: reports.php");
    exit();
}

// STEP 1: Get voting period with its election name
$stmt = $pdo->prepare("
    SELECT vp.*, e.election_name 
    FROM voting_periods vp
    JOIN elections e ON vp.election_id = e.id
    WHERE  vp.id = ?
    LIMIT 1
");


$stmt->execute([$voting_period_id]); // Ensure parameter is wrapped in an array
$votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$votingPeriod) {
    die("Invalid voting period ID.");
}
$electionName = $votingPeriod['election_id'];

// STEP 2: Get candidacy from events
$stmt = $pdo->prepare("SELECT candidacy FROM events WHERE candidacy = :name");
$stmt->execute(['name' => $electionName]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) {
    die("No matching event for voting period name.");
}
$candidacy = $event['candidacy'];



// STEP 3: Get registration_form ID from candidacy
$stmt = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = :candidacy");
$stmt->execute(['candidacy' => $candidacy]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$form) {
    die("No registration form found for candidacy.");
}
$formId = $form['id'];



// STEP 4: Get required field IDs
$stmt = $pdo->prepare("
    SELECT id, field_name 
    FROM form_fields 
    WHERE form_id = :form_id 
      AND field_name IN ('full_name', 'student_id', 'picture', 'party', 'position')
");
$stmt->execute(['form_id' => $formId]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fields = [];
foreach ($results as $row) {
    $fields[$row['field_name']] = $row['id'];
}

$fullNameFieldId   = $fields['full_name'] ?? null;
$studentIdFieldId  = $fields['student_id'] ?? null;
$pictureFieldId    = $fields['picture'] ?? null;
$partyFieldId      = $fields['party'] ?? null;
$positionFieldId   = $fields['position'] ?? null;

if (!$fullNameFieldId || !$studentIdFieldId || !$pictureFieldId || !$partyFieldId || !$positionFieldId) {
    die("Missing one or more required field IDs.");
}

// LEVELS TO PROCESS
$levels = ['Central', 'Local'];

function hasAdminVotes($voting_period_id)
{
    global $conn;

    $sql = "SELECT COUNT(id) AS total
            FROM votes
            WHERE voting_period_id = ?
              AND precinct = 'admin-precinct'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $voting_period_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result['total'] > 0;
}

function checkForTiedVotes($voting_period_id)
{
    global $conn;

    $useAdminVotes = hasAdminVotes($voting_period_id);

    $sql = "SELECT 
                v.position,
                v.candidate_id,
                c.name AS candidate_name,
                COUNT(v.id) AS vote_count
            FROM votes v
            JOIN candidates c ON v.candidate_id = c.id
            WHERE v.voting_period_id = ?";

    // If admin votes exist, count ONLY admin-precinct votes
    if ($useAdminVotes) {
        $sql .= " AND v.precinct = 'admin-precinct'";
    }

    $sql .= "
            GROUP BY v.position, v.candidate_id, c.name
            ORDER BY v.position, vote_count DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $voting_period_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $positionVotes = [];
    $tiedPositions = [];

    while ($row = $result->fetch_assoc()) {
        $positionVotes[$row['position']][] = $row;
    }

    foreach ($positionVotes as $position => $candidates) {
        if (count($candidates) < 2) {
            continue;
        }

        $topVotes = $candidates[0]['vote_count'];

        $tiedCandidates = array_filter(
            $candidates,
            fn($c) => $c['vote_count'] == $topVotes
        );

        if (count($tiedCandidates) > 1) {
            $tiedPositions[$position] = array_column(
                $tiedCandidates,
                'candidate_name'
            );
        }
    }

    return $tiedPositions;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Head section unchanged -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU I-Elect</title>
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="js/select.dataTables.min.css">
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <link rel="shortcut icon" href="images/favicon.png" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include jsPDF and html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <style>
        table,
        th,
        td {
            border: 1px solid black !important;
            text-align: center;
        }

        .profiler {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }

        .bordered {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <script>
        const departmentsByCollege = <?php echo json_encode($departments_by_college); ?>;
        const votingPeriodId = <?php echo json_encode($voting_period_id); ?>;

        $(document).ready(function() {
            console.log("Departments by College:", departmentsByCollege); // Debugging

            $('#collegeFilter').change(function() {

                const college = $(this).val();
                const $deptFilter = $('#departmentFilter');

                $deptFilter.empty().append('<option value="">All Departments</option>');

                console.log("Selected College:", college);

                if (college && departmentsByCollege[college]) {

                    const departments = departmentsByCollege[college];

                    Object.entries(departments).forEach(([id, name]) => {
                        $deptFilter.append(`<option value="${id}">${name}</option>`);
                    });

                } else {
                    console.log("No departments found for college:", college);
                }

                fetchData();
            });

            $('#departmentFilter').change(fetchData);
            fetchData();

            $('#publishBtn').click(function(e) {
                    e.preventDefault();

                    // Show loading state
                    Swal.fire({
                        title: 'Checking Results',
                        html: 'Please wait while we check for any tied votes...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: 'check_tied_votes.php',
                        method: 'GET',
                        data: {
                            voting_period_id: votingPeriodId
                        },
                        success: function(response) {
                            Swal.close();

                            // Parse response if it's a string
                            if (typeof response === 'string') {
                                try {
                                    response = JSON.parse(response);
                                } catch (e) {
                                    Swal.fire({
                                        title: "Error",
                                        text: "Invalid response from server",
                                        icon: "error"
                                    });
                                    return;
                                }
                            }

                            if (response.hasTies) {
                                // Format tied positions with better organization
                                let tiedList = '<div style="text-align: left; max-height: 300px; overflow-y: auto;">';

                                for (const [position, departments] of Object.entries(response.tiedPositions)) {
                                    tiedList += `<h4 style="margin-bottom: 5px;">${position}:</h4><ul style="margin-top: 5px;">`;

                                    for (const [department, candidates] of Object.entries(departments)) {
                                        const deptLabel = department === 'Central' ? 'Central Position' : `Department: ${department}`;
                                        tiedList += `<li><b>${deptLabel}</b><ul>`;

                                        candidates.forEach(candidate => {
                                            tiedList += `<li>${candidate.name} (${candidate.party}) - ${candidate.vote_count} votes</li>`;
                                            console.log(`- ${candidate.name} (${candidate.party}) — ${candidate.vote_count} votes`);
                                        });

                                        tiedList += `</ul></li>`;
                                    }

                                    tiedList += `</ul>`;
                                }

                                tiedList += '</div>';

                                Swal.fire({
                                    title: "Tied Votes Detected!",
                                    html: `The following positions have tied votes:<br><br>${tiedList}<br>How would you like to proceed?`,
                                    icon: "warning",
                                    showCancelButton: true,
                                    showDenyButton: true,
                                    // confirmButtonText: 'Revote with Electoral Board',
                                    denyButtonText: 'Public Revote',
                                    cancelButtonText: 'Cancel',
                                    width: '800px',
                                    customClass: {
                                        popup: 'tied-votes-popup'
                                    }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Option 1: Electoral Board
                                        window.open(`revote_with_electoral_board.php?voting_period_id=${votingPeriodId}`, '_blank');
                                    } else if (result.isDenied) {

                                        $('#publicRevoteModal').modal('show'); // Show the reschedule modal

                                        // // Option 2: Public Revote
                                        // const positionsParam = encodeURIComponent(JSON.stringify(response.tiedPositions));
                                        // window.location.href = `revote_public.php?voting_period_id=${votingPeriodId}&positions=${positionsParam}`;
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: "No Ties Detected",
                                    text: "Would you like to publish the election results now?",
                                    icon: "question",
                                    showCancelButton: true,
                                    confirmButtonColor: "#3085d6",
                                    cancelButtonColor: "#d33",
                                    confirmButtonText: "Yes, publish results",
                                    cancelButtonText: "Not yet"
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = `publish_results.php?voting_period_id=${votingPeriodId}`;
                                    }
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                title: "Error",
                                text: "Could not check for tied votes. Please try again. " +
                                    (xhr.responseText ? "Server response: " + xhr.responseText : ""),
                                icon: "error"
                            });
                            console.error("AJAX Error:", status, error);
                        }
                    });

                }

            );
        });

        function fetchData() {
            const college = $('#collegeFilter').val();
            const department = $('#departmentFilter').val();

            $.ajax({
                url: 'fetch_votes.php',
                method: 'POST',
                data: {
                    college: college,
                    department: department,
                    voting_period_id: votingPeriodId
                },
                success: function(response) {
                    $('#reportContainer').html(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching data:', error);
                    $('#reportContainer').html('<p>Error loading data.</p>');
                }
            });
        }
    </script>




    <style>
        .custom-padding {
            padding: 20px !important;
            /* Adjust padding as needed */
        }
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
                SELECT e.*, a.year_label, a.semester
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

                </div>
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
                                                <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="reports.php" role="tab"
                                                    aria-controls="overview" aria-selected="true">Reports</a>
                                            </li>

                                        </ul>

                                    </div>
                                    <div class="tab-content tab-content-basic">
                                        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                                            <div class="card card-rounded mb-5">
                                                <div class="card-body">
                                                    <div class="row mt-4">
                                                        <div class="container mb-5">
                                                            <div class="d-flex align-items-center">
                                                                <h5><b>Election Name: </b><?php echo htmlspecialchars($election['election_name']); ?></h5>
                                                                <div class="ms-auto" aria-hidden="true">
                                                                    <?php


                                                                    if (($election['status'] ?? '') != 'Published') { ?>
                                                                        <a href="#"><button class="btn btn-primary text-white" id="publishBtn"><i class="mdi mdi-publish"></i> Publish</button></a>
                                                                    <?php } else {
                                                                    ?>
                                                                        <a href="view_published.php?voting_period_id=<?php echo $voting_period_id ?>"><button class="btn btn-primary text-white"><i class="mdi mdi-eye"></i> View Published</button></a>
                                                                    <?php } ?>
                                                                    <a href="print.php?voting_period_id=<?php echo $_GET['voting_period_id'] ?>" target="_blank">
                                                                        <button class="btn btn-danger text-white" id="printPdfBtn"><i class="mdi mdi-pdf-box">PDF</i></button>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            <?php

                                                            $startPeriod = $election['start_period'] ?? null;
                                                            $endPeriod = $election['end_period'] ?? null;


                                                            ?>
                                                            <h6><b>Election Period:</b>
                                                                <b><?php echo $startPeriod ? htmlspecialchars(date('l', strtotime($startPeriod))) : 'N/A'; ?></b>
                                                                (<?php echo $startPeriod ? htmlspecialchars(date('m/d/Y, h:i A', strtotime($startPeriod))) : 'N/A'; ?>)
                                                                -
                                                                <b><?php echo $endPeriod ? htmlspecialchars(date('l', strtotime($endPeriod))) : 'N/A'; ?></b>
                                                                (<?php echo $endPeriod ? htmlspecialchars(date('m/d/Y, h:i A', strtotime($endPeriod))) : 'N/A'; ?>)
                                                            </h6>

                                                            <?php
                                                            $candidacyStart = $candidacy_details['start_period'] ?? null;
                                                            $candidacyEnd = $candidacy_details['end_period'] ?? null;
                                                            ?>
                                                            <h6><b>Candidacy Period:</b>
                                                                <b><?php echo $candidacyStart ? htmlspecialchars(date('l', strtotime($candidacyStart))) : 'N/A'; ?></b>
                                                                (<?php echo $candidacyStart ? htmlspecialchars(date('m/d/Y, h:i A', strtotime($candidacyStart))) : 'N/A'; ?>)
                                                                -
                                                                <b><?php echo $candidacyEnd ? htmlspecialchars(date('l', strtotime($candidacyEnd))) : 'N/A'; ?></b>
                                                                (<?php echo $candidacyEnd ? htmlspecialchars(date('m/d/Y, h:i A', strtotime($candidacyEnd))) : 'N/A'; ?>)
                                                            </h6>

                                                            <?php
                                                            $votingStart = $voting_period_details['start_period'] ?? null;
                                                            $votingEnd = $voting_period_details['end_period'] ?? null;
                                                            ?>
                                                            <h6><b>Voting Day:</b>
                                                                <b><?php echo $votingStart ? htmlspecialchars(date('l', strtotime($votingStart))) : 'N/A'; ?></b>
                                                                (<?php echo $votingStart ? htmlspecialchars(date('m/d/Y, h:i A', strtotime($votingStart))) : 'N/A'; ?>)
                                                                -
                                                                <b><?php echo $votingEnd ? htmlspecialchars(date('l', strtotime($votingEnd))) : 'N/A'; ?></b>
                                                                (<?php echo $votingEnd ? htmlspecialchars(date('m/d/Y, h:i A', strtotime($votingEnd))) : 'N/A'; ?>)
                                                            </h6>


                                                            <!-- Filter Dropdowns -->

                                                        </div>


                                                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                                                            <li class="nav-item" role="presentation">
                                                                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">Overview of Election Results</button>
                                                            </li>
                                                            <li class="nav-item" role="presentation">
                                                                <button class="nav-link " id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab">Voting Statistics</button>
                                                            </li>
                                                            <li class="nav-item" role="presentation">
                                                                <button class="nav-link " id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button" role="tab">Voting Summary</button>
                                                            </li>
                                                        </ul>

                                                        <!-- Tab Content -->
                                                        <div class="tab-content mt-4" id="myTabContent">


                                                            <div class="tab-pane fade show active" id="details" role="tabpanel">
                                                                <div class="mb-3">
                                                                    <label for="collegeFilter"><b>Filter by College:</b></label>
                                                                    <select id="collegeFilter" class="form-control d-inline-block w-auto">
                                                                        <option value="">All Colleges</option>
                                                                        <?php foreach ($colleges as $college): ?>
                                                                            <option value="<?php echo htmlspecialchars($college['college_id']); ?>">
                                                                                <?php echo htmlspecialchars($college['college_name']); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>

                                                                    <label for="departmentFilter" class="ms-3"><b>Filter by Department:</b></label>
                                                                    <select id="departmentFilter" class="form-control d-inline-block w-auto">
                                                                        <option value="">All Departments</option>
                                                                    </select>
                                                                </div>
                                                                <h1 class="mb-5 text-center"><b>Election Results</b></h1>
                                                                <div class="container-fluid" id="reportContainer">
                                                                    <!-- AJAX-loaded election results will go here -->
                                                                </div>

                                                            </div>

                                                            <div class="tab-pane fade " id="stats" role="tabpanel">

                                                                <?php
                                                                // --- 1. Total Verified Voters ---
                                                                $stmt = $pdo->prepare("
                                                                    SELECT COUNT(DISTINCT pv.student_id) AS total_verified 
                                                                    FROM precinct_voters pv
                                                                    JOIN precincts p ON pv.precinct = p.id
                                                                    JOIN precinct_elections pe ON p.id = pe.precinct_id
                                                                    JOIN elections e ON pe.election_name = e.id
                                                                    JOIN voting_periods vp ON e.id = vp.election_id
                                                                    WHERE vp.id = ?
                                                                ");
                                                                $stmt->execute([$voting_period_id]);
                                                                $total_verified_voters = $stmt->fetch(PDO::FETCH_ASSOC)['total_verified'];

                                                                // --- 2. Total Voted ---
                                                                $stmt = $pdo->prepare("SELECT COUNT(DISTINCT student_id) AS total_voted FROM votes WHERE voting_period_id = ?");
                                                                $stmt->execute([$voting_period_id]);
                                                                $total_voted = $stmt->fetch(PDO::FETCH_ASSOC)['total_voted'];

                                                                // --- 3. Total Registered Voters (Eligible for this election) ---
                                                                $total_students = $total_verified_voters;

                                                                // --- 4. Voting Summary ---
                                                                $total_did_not_vote = $total_voted - $total_verified_voters;

                                                                // --- 5. Voters per College (Verified) ---
                                                                $stmt = $pdo->prepare("
    SELECT 
        s.college,
        c.college_name,
        COUNT(DISTINCT pv.student_id) AS verified_voters,
        COUNT(DISTINCT v.student_id) AS voted_voters
    FROM precinct_voters pv
    JOIN voters s ON s.student_id = pv.student_id
    JOIN precincts p ON pv.precinct = p.id
    JOIN precinct_elections pe ON p.name = pe.precinct_name
    JOIN elections e ON pe.election_name = e.id
    JOIN voting_periods vp ON e.id = vp.election_id
    LEFT JOIN votes v 
        ON s.student_id = v.student_id 
        AND v.voting_period_id = vp.id
    LEFT JOIN colleges c 
        ON s.college = c.college_id
    WHERE vp.id = ?
    GROUP BY s.college, c.college_name
");
                                                                $stmt->execute([$voting_period_id]);
                                                                $verified_voters_per_college = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                                ?>

                                                                <div class="container-fluid">
                                                                    <h1 class="mb-5 text-center"><b>Voting Statistics</b></h1>

                                                                    <!-- Total Verified -->
                                                                    <div class="mb-4">
                                                                        <h5><b>Total Verified Voters:</b> <?= number_format($total_verified_voters) ?></h5>
                                                                    </div>

                                                                    <!-- Voted vs Did Not Vote -->
                                                                    <div class="mb-4">
                                                                        <h5><b>Voting Participation:</b></h5>
                                                                        <ul>
                                                                            <li><b>Voted:</b> <?= number_format($total_voted) ?></li>
                                                                            <li><b>Did Not Vote:</b> <?= number_format(abs($total_did_not_vote)) ?></li>
                                                                            <li><b>Total Registered Voters:</b> <?= number_format($total_students) ?></li>
                                                                        </ul>
                                                                    </div>

                                                                    <!-- Chart -->
                                                                    <div class="row mb-5">
                                                                        <div class="col-lg-6 offset-lg-3" style="height: 400px;">
                                                                            <canvas id="votingStatsChart"></canvas>
                                                                        </div>
                                                                    </div>
                                                                    <script>
                                                                        document.addEventListener("DOMContentLoaded", function() {
                                                                            const ctx = document.getElementById('votingStatsChart');
                                                                            if (ctx) {
                                                                                new Chart(ctx, {
                                                                                    type: 'doughnut',
                                                                                    data: {
                                                                                        labels: ['Voted', 'Did Not Vote'],
                                                                                        datasets: [{
                                                                                            data: [<?php echo $total_voted; ?>, <?php echo $total_did_not_vote; ?>],
                                                                                            backgroundColor: ['#28a745', '#dc3545'],
                                                                                            hoverBackgroundColor: ['#218838', '#c82333']
                                                                                        }]
                                                                                    },
                                                                                    options: {
                                                                                        responsive: true,
                                                                                        maintainAspectRatio: false,
                                                                                        plugins: {
                                                                                            legend: {
                                                                                                position: 'bottom'
                                                                                            },
                                                                                            title: {
                                                                                                display: true,
                                                                                                text: 'Voter Turnout'
                                                                                            },
                                                                                            tooltip: {
                                                                                                callbacks: {
                                                                                                    label: function(context) {
                                                                                                        let label = context.label || '';
                                                                                                        if (label) {
                                                                                                            label += ': ';
                                                                                                        }
                                                                                                        let value = context.raw;
                                                                                                        let total = context.chart._metasets[context.datasetIndex].total;
                                                                                                        let percentage = Math.round((value / total) * 100) + '%';
                                                                                                        return label + value.toLocaleString() + ' (' + percentage + ')';
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                });
                                                                            }
                                                                        });
                                                                    </script>

                                                                    <!-- Table of Verified Voters Per College -->
                                                                    <div class="mb-4">
                                                                        <h5><b>Verified Voters per College</b></h5>
                                                                        <table class="table table-bordered table-striped">
                                                                            <thead class="table-dark">
                                                                                <tr>
                                                                                    <th>College</th>
                                                                                    <th>Verified Voters</th>
                                                                                    <th>Voted</th>
                                                                                    <th>Did Not Vote</th>
                                                                                    <th>Turnout (%)</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                <?php foreach ($verified_voters_per_college as $row):
                                                                                    $voted = $row['voted_voters'];
                                                                                    $verified = $row['verified_voters'];
                                                                                    $not_voted = $verified - $voted;
                                                                                    $turnout = $verified > 0 ? round(($voted / $verified) * 100, 2) : 0;
                                                                                ?>
                                                                                    <tr>
                                                                                        <td><?= htmlspecialchars($row['college_name']) ?></td>
                                                                                        <td><?= number_format($verified) ?></td>
                                                                                        <td><?= number_format($voted) ?></td>
                                                                                        <td><?= number_format($not_voted) ?></td>
                                                                                        <td><?= $turnout ?>%</td>
                                                                                    </tr>
                                                                                <?php endforeach; ?>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="tab-pane fade " id="summary" role="tabpanel">

                                                                <?php
                                                                require 'includes/conn.php';

                                                                $votingPeriodId = $_GET['voting_period_id'] ?? null;
                                                                if (!$votingPeriodId) {
                                                                    die("No voting period ID provided.");
                                                                }

                                                                // STEP 1: Get voting period info with its election_name
                                                                $stmt = $pdo->prepare("
    SELECT vp.id, e.id as election_id, e.election_name as voting_period_name
    FROM voting_periods vp
    JOIN elections e ON vp.election_id = e.id
    WHERE vp.id = :id
    LIMIT 1
");
                                                                $stmt->execute(['id' => $votingPeriodId]);
                                                                $votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);

                                                                if (!$votingPeriod) {
                                                                    die("Invalid voting period ID.");
                                                                }

                                                                $votingPeriodName = $votingPeriod['voting_period_name'];
                                                                $electionName = $votingPeriod['voting_period_name'];
                                                                $electionId = $votingPeriod['election_id'];

                                                                // STEP 2: Get candidacy/event for this voting period
                                                                $stmt = $pdo->prepare("
    SELECT candidacy 
    FROM events 
    WHERE candidacy = :candidacy
    LIMIT 1
");
                                                                $stmt->execute(['candidacy' => $electionId]);
                                                                $event = $stmt->fetch(PDO::FETCH_ASSOC);

                                                                if (!$event) {
                                                                    die("No matching event found for voting period.");
                                                                }
                                                                $candidacy = $event['candidacy'];

                                                                // STEP 3: Get registration form ID
                                                                $stmt = $pdo->prepare("
    SELECT id 
    FROM registration_forms 
    WHERE election_name = :candidacy
    LIMIT 1
");
                                                                $stmt->execute(['candidacy' => $electionId]);
                                                                $form = $stmt->fetch(PDO::FETCH_ASSOC);
                                                                if (!$form) {
                                                                    die("No registration form found for this candidacy.");
                                                                }
                                                                $formId = $form['id'];

                                                                // STEP 4: Get required field IDs
                                                                $stmt = $pdo->prepare("
    SELECT id, field_name 
    FROM form_fields 
    WHERE form_id = :form_id 
      AND field_name IN ('full_name', 'student_id', 'picture', 'party', 'position')
");
                                                                $stmt->execute(['form_id' => $formId]);
                                                                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                                $fields = [];
                                                                foreach ($results as $row) {
                                                                    $fields[$row['field_name']] = $row['id'];
                                                                }

                                                                $fullNameFieldId  = $fields['full_name'] ?? null;
                                                                $studentIdFieldId = $fields['student_id'] ?? null;
                                                                $pictureFieldId   = $fields['picture'] ?? null;
                                                                $partyFieldId     = $fields['party'] ?? null;
                                                                $positionFieldId  = $fields['position'] ?? null;

                                                                if (!$fullNameFieldId || !$studentIdFieldId || !$pictureFieldId || !$partyFieldId || !$positionFieldId) {
                                                                    die("Missing one or more required field IDs.");
                                                                }

                                                                // STEP 5: Fetch colleges and ESU campuses
                                                                $colleges = $pdo->query("
    SELECT DISTINCT c.college_id, c.college_name
    FROM voters v
    LEFT JOIN colleges c ON v.college = c.college_id
    WHERE v.college IS NOT NULL
    ORDER BY c.college_id
")->fetchAll(PDO::FETCH_ASSOC);
                                                                $esu_campuses = $pdo->query("
    SELECT DISTINCT c.campus_id, c.campus_name
    FROM precincts p
    LEFT JOIN campuses c ON p.college_external = c.campus_id
    WHERE p.college_external IS NOT NULL
    ORDER BY c.campus_name
")->fetchAll(PDO::FETCH_ASSOC);

                                                                // Function to get voter statistics
                                                                function getVoterStats($pdo, $votingPeriodId, $college = null, $campus = null)
                                                                {
                                                                    $conditions = [];
                                                                    $params = [':voting_period_id' => $votingPeriodId];

                                                                    // 1. Handling the Campus (The 'Where' they are)
                                                                    if ($campus) {
                                                                        // If a campus is specified, we match it against college_external
                                                                        $conditions[] = "p.college_external = :campus";
                                                                        $params[':campus'] = $campus;
                                                                    }

                                                                    // 2. Handling the College (The 'What' they study)
                                                                    if ($college) {
                                                                        // If it's a Main Campus student, the college is in the voters table.
                                                                        // If it's an ESU student, the campus location is the differentiator.
                                                                        $conditions[] = "v.college = :college";
                                                                        $params[':college'] = $college;
                                                                    }

                                                                    $params[':campus'] = $campus ?? null;

                                                                    $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

                                                                    $query = "
    SELECT 
        COUNT(DISTINCT pv.student_id) AS total,
        COUNT(DISTINCT vts.student_id) AS voted,
        (COUNT(DISTINCT pv.student_id) - COUNT(DISTINCT vts.student_id)) AS not_voted
    FROM precinct_voters pv
    INNER JOIN precincts p ON pv.precinct = p.id
    LEFT JOIN voters v ON pv.student_id = v.student_id
    LEFT JOIN votes vts ON pv.student_id = vts.student_id 
        AND vts.voting_period_id = :voting_period_id
        AND (
            :campus IS NULL 
            OR EXISTS (
                SELECT 1 FROM precincts vp 
                WHERE vp.id = vts.precinct 
                AND vp.college_external = :campus
            )
        )
    $whereClause
";

                                                                    $stmt = $pdo->prepare($query);
                                                                    $stmt->execute($params);
                                                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);


                                                                    return [
                                                                        'total'     => (int)($result['total'] ?? 0),
                                                                        'voted'     => (int)($result['voted'] ?? 0),
                                                                        'not_voted' => (int)($result['not_voted'] ?? 0)
                                                                    ];
                                                                }
                                                                // Function to get candidates for a position
                                                                function getCandidatesForPosition(
                                                                    $pdo,
                                                                    $votingPeriodId,
                                                                    $position,
                                                                    $fullNameFieldId,
                                                                    $studentIdFieldId,
                                                                    $pictureFieldId,
                                                                    $partyFieldId,
                                                                    $positionFieldId,
                                                                    $college = null,
                                                                    $campus = null
                                                                ) {
                                                                    // Step 1: Get the registration form ID for this voting period
                                                                    $stmtForm = $pdo->prepare("
        SELECT rf.id 
        FROM registration_forms rf
        JOIN elections e ON rf.election_name = e.id
        JOIN voting_periods vp ON e.id = vp.election_id
        WHERE vp.id = :voting_period_id
        LIMIT 1
    ");
                                                                    $stmtForm->execute([':voting_period_id' => $votingPeriodId]);
                                                                    $formId = $stmtForm->fetchColumn();
                                                                    if (!$formId) {
                                                                        error_log("No registration form found for voting_period_id $votingPeriodId");
                                                                        return [];
                                                                    }

                                                                    // Step 2: Build query
                                                                    $where = "pos.value = :position AND c.form_id = :form_id";
                                                                    $params = [
                                                                        ':position' => $position,
                                                                        ':form_id' => $formId,
                                                                        ':full_name_field_id' => $fullNameFieldId,
                                                                        ':student_id_field_id' => $studentIdFieldId,
                                                                        ':picture_field_id' => $pictureFieldId,
                                                                        ':party_field_id' => $partyFieldId,
                                                                        ':position_field_id' => $positionFieldId,
                                                                        ':voting_period_id' => $votingPeriodId
                                                                    ];

                                                                    if ($college) {
                                                                        $where .= " AND vtr.college = :college";
                                                                        $params[':college'] = $college;
                                                                    }
                                                                    if ($campus) {
                                                                        $params[':campus'] = $campus;
                                                                        $params[':campus_filter'] = $campus; // For vote counting
                                                                    }

                                                                    // Step 3: Fetch candidates
                                                                    $query = "
    SELECT 
    vtr.student_id,
    c.id AS candidate_id,
    fn.value AS full_name,
    st.value AS student_id_text,
    pic.file_path AS picture,
    party.value AS partylist,
    vtr.college,
    col.college_name,
    vtr.course,
    crs.course_name,
    c.status,
    pos.value AS position,

    COALESCE(COUNT(DISTINCT CASE 
        WHEN :campus IS NOT NULL THEN 
            CASE WHEN p.type = 10 AND p.college_external = :campus_filter THEN v.student_id END
        ELSE v.student_id 
    END), 0) AS vote_count

FROM candidates c

LEFT JOIN candidate_responses fn 
    ON fn.candidate_id = c.id 
    AND fn.field_id = :full_name_field_id

LEFT JOIN candidate_responses st 
    ON st.candidate_id = c.id 
    AND st.field_id = :student_id_field_id

LEFT JOIN candidate_files pic 
    ON pic.candidate_id = c.id 
    AND pic.field_id = :picture_field_id

LEFT JOIN candidate_responses party 
    ON party.candidate_id = c.id 
    AND party.field_id = :party_field_id

LEFT JOIN candidate_responses pos 
    ON pos.candidate_id = c.id 
    AND pos.field_id = :position_field_id

LEFT JOIN voters vtr 
    ON vtr.student_id = st.value

LEFT JOIN colleges col 
    ON vtr.college = col.college_id

LEFT JOIN courses crs 
    ON vtr.course = crs.id

LEFT JOIN precinct_voters pv 
    ON pv.student_id = st.value 
    AND pv.status = 'verified'

LEFT JOIN votes v 
    ON v.candidate_id = c.id 
    AND v.voting_period_id = :voting_period_id

LEFT JOIN precincts p 
    ON v.precinct = p.name

WHERE $where

GROUP BY 
    c.id,
    fn.value,
    st.value,
    pic.file_path,
    party.value,
    vtr.college,
    col.college_name,
    vtr.course,
    crs.course_name,
    c.status,
    pos.value

ORDER BY vote_count DESC, fn.value ASC
    ";

                                                                    // Add null params if not set to avoid binding errors
                                                                    if (!isset($params[':campus'])) $params[':campus'] = null;
                                                                    if (!isset($params[':campus_filter'])) $params[':campus_filter'] = null;


                                                                    $stmt = $pdo->prepare($query);
                                                                    $stmt->execute($params);
                                                                    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);


                                                                    if (empty($candidates) && $college) {
                                                                        error_log("No candidates found for position '$position' in college '$college' for voting_period_id $votingPeriodId");
                                                                    }

                                                                    return $candidates;
                                                                }


                                                                $levels = ['Central', 'Local'];
                                                                ?>



                                                                <div class="container-fluid py-4">
                                                                    <!-- Existing Levels (Central, Local, External) -->
                                                                    <?php foreach ($levels as $level): ?>
                                                                        <hr class="my-4">
                                                                        <h2 class="display-5 fw-bold mb-4"><?php echo strtoupper($level); ?> POSITIONS</h2>

                                                                        <?php
                                                                        // Fetch all parties for this election to ensure they all appear in breakdown
                                                                        $stmt = $pdo->prepare("SELECT name FROM parties WHERE election_id = ?");
                                                                        $stmt->execute([$election['id']]);
                                                                        $allParties = $stmt->fetchAll(PDO::FETCH_COLUMN);

                                                                        $stmt = $pdo->prepare("SELECT DISTINCT name FROM positions WHERE level = :level");
                                                                        $stmt->execute(['level' => $level]);
                                                                        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                                        $hasCandidates = false;

                                                                        foreach ($positions as $pos):
                                                                            $position = $pos['name'];
                                                                            $candidates = getCandidatesForPosition($pdo, $votingPeriodId, $position, $fullNameFieldId, $studentIdFieldId, $pictureFieldId, $partyFieldId, $positionFieldId);

                                                                            if (empty($candidates)) continue;
                                                                            $hasCandidates = true;

                                                                            $partyVotes = array_fill_keys($allParties, 0);
                                                                            foreach ($candidates as $cand) {
                                                                                $party = $cand['partylist'] ?? 'Independent';
                                                                                $partyVotes[$party] = ($partyVotes[$party] ?? 0) + $cand['vote_count'];
                                                                            }

                                                                            $maxVotes = max(array_column($candidates, 'vote_count'));
                                                                        ?>
                                                                            <div class="card mb-4 shadow-sm">
                                                                                <div class="card-body">
                                                                                    <h3 class="card-title h4 fw-bold"><?php echo htmlspecialchars($position); ?></h3>
                                                                                    <div class="table-responsive">
                                                                                        <table class="table table-striped table-hover">
                                                                                            <thead class="table-dark">
                                                                                                <tr>
                                                                                                    <th>Rank</th>
                                                                                                    <th>Photo</th>
                                                                                                    <th>Name</th>
                                                                                                    <th>Course</th>
                                                                                                    <th>College</th>
                                                                                                    <th>Partylist</th>
                                                                                                    <th>Votes</th>
                                                                                                </tr>
                                                                                            </thead>
                                                                                            <tbody>
                                                                                                <?php
                                                                                                $currentRank = 1;
                                                                                                $currentVotes = $maxVotes;
                                                                                                $index = 0;
                                                                                                foreach ($candidates as $cand):
                                                                                                    if ($cand['vote_count'] < $currentVotes) {
                                                                                                        $currentRank = $index + 1;
                                                                                                        $currentVotes = $cand['vote_count'];
                                                                                                    }
                                                                                                    $isWinner = $cand['vote_count'] === $maxVotes;
                                                                                                ?>
                                                                                                    <tr class="<?php echo $isWinner ? 'table-success fw-bold' : ''; ?>">
                                                                                                        <td><?php echo $currentRank; ?><?php echo $currentRank === 1 ? 'st' : ($currentRank === 2 ? 'nd' : ($currentRank === 3 ? 'rd' : 'th')); ?><?php echo $isWinner && count(array_filter($candidates, fn($c) => $c['vote_count'] === $maxVotes)) > 1 ? ' (Tie)' : ''; ?></td>
                                                                                                        <td>
                                                                                                            <?php if (!empty($cand['picture'])): ?>
                                                                                                                <img src="../login/uploads/candidates/<?php echo htmlspecialchars($cand['picture']); ?>" width="60" height="60" class="rounded-circle" alt="Candidate Photo">
                                                                                                            <?php else: ?>
                                                                                                                <span class="text-muted">No Image</span>
                                                                                                            <?php endif; ?>
                                                                                                        </td>
                                                                                                        <td><?php echo htmlspecialchars($cand['full_name'] ?? 'Unknown'); ?></td>
                                                                                                        <td><?php echo htmlspecialchars($cand['course_name'] ?? 'N/A'); ?></td>
                                                                                                        <td><?php echo htmlspecialchars($cand['college_name'] ?? 'N/A'); ?></td>
                                                                                                        <td><?php echo htmlspecialchars($cand['partylist'] ?? 'Independent'); ?></td>
                                                                                                        <td><strong><?php echo number_format($cand['vote_count']); ?></strong></td>
                                                                                                    </tr>
                                                                                                <?php
                                                                                                    $index++;
                                                                                                endforeach;
                                                                                                ?>
                                                                                            </tbody>
                                                                                        </table>
                                                                                    </div>
                                                                                    <h6 class="mt-3">Partylist Vote Breakdown:</h6>
                                                                                    <ul class="list-unstyled">
                                                                                        <?php foreach ($partyVotes as $party => $total): ?>
                                                                                            <li><strong><?php echo htmlspecialchars($party); ?>:</strong> <?php echo number_format($total); ?> votes</li>
                                                                                        <?php endforeach; ?>
                                                                                    </ul>
                                                                                    <div class="container-fluid row">
                                                                                        <div class="d-flex justify-content-center align-items-center text-center mx-auto">
                                                                                            <div class="col-md-3">
                                                                                                <canvas id="chart-<?php echo md5($level . $position); ?>" height="50" class="mt-3"></canvas>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    <script>
                                                                                        new Chart(document.getElementById('chart-<?php echo md5($level . $position); ?>'), {
                                                                                            type: 'pie',
                                                                                            data: {
                                                                                                labels: <?php echo json_encode(array_column($candidates, 'full_name')); ?>,
                                                                                                datasets: [{
                                                                                                    label: 'Votes',
                                                                                                    data: <?php echo json_encode(array_column($candidates, 'vote_count')); ?>,
                                                                                                    backgroundColor: ['#dc3545', '#0d6efd', '#ffc107', '#6f42c1', '#20c997', '#fd7e14', '#28a745', '#ffeb3b'],
                                                                                                    borderWidth: 2
                                                                                                }]
                                                                                            },
                                                                                            options: {
                                                                                                responsive: true,
                                                                                                plugins: {
                                                                                                    legend: {
                                                                                                        position: 'bottom'
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        });
                                                                                    </script>
                                                                                </div>
                                                                            </div>
                                                                        <?php endforeach; ?>

                                                                        <?php if (!$hasCandidates): ?>
                                                                            <div class="alert alert-info" role="alert">
                                                                                <i>No candidates available for this level.</i>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    <?php endforeach; ?>

                                                                    <!-- Local Election (Per College) -->
                                                                    <hr class="my-4">
                                                                    <h2 class="display-5 fw-bold mb-4">LOCAL ELECTION (PER COLLEGE)</h2>
                                                                    <div class="mb-3 d-none">
                                                                        <select id="college-select" class="form-select">
                                                                            <option value="">Select College</option>

                                                                            <?php foreach ($colleges as $college): ?>
                                                                                <option value="<?= htmlspecialchars($college['college_id']) ?>">
                                                                                    <?= htmlspecialchars($college['college_name']) ?>
                                                                                </option>
                                                                            <?php endforeach; ?>

                                                                        </select>
                                                                    </div>

                                                                    <div id="college-results">
                                                                        <?php foreach ($colleges as $college): ?>

                                                                            <?php
                                                                            $collegeId = $college['college_id'];
                                                                            $collegeName = $college['college_name'];

                                                                            $stats = getVoterStats($pdo, $votingPeriodId, $collegeId);
                                                                            ?>
                                                                            <div class="college-section" data-college="<?php echo htmlspecialchars($collegeId); ?>">


                                                                                <div class="card mb-4 shadow-sm">
                                                                                    <div class="card-body">
                                                                                        <h3 class="card-title h4"><?php echo htmlspecialchars($collegeName); ?> Voter Stats</h3>
                                                                                        <p>Total Verified Voters: <strong><?php echo number_format($stats['total']); ?></strong></p>
                                                                                        <p>Voted: <strong><?php echo number_format($stats['voted']); ?></strong></p>
                                                                                        <p>Did Not Vote: <strong><?php echo number_format($stats['total'] - $stats['voted']); ?></strong></p>
                                                                                    </div>
                                                                                </div>

                                                                                <?php
                                                                                $positionsToShow = ['Mayor', 'Vice-Mayor', 'Senator', 'Councilor'];
                                                                                $hasCollegeCandidates = false;
                                                                                foreach ($positionsToShow as $position):
                                                                                    $candidates = getCandidatesForPosition($pdo, $votingPeriodId, $position, $fullNameFieldId, $studentIdFieldId, $pictureFieldId, $partyFieldId, $positionFieldId, $collegeId);
                                                                                    if (!empty($candidates)) {
                                                                                        $hasCollegeCandidates = true;
                                                                                        break;
                                                                                    }
                                                                                endforeach;
                                                                                ?>

                                                                                <?php if ($hasCollegeCandidates): ?>
                                                                                    <div class="card mb-4 shadow-sm">
                                                                                        <div class="card-body">
                                                                                            <h3 class="card-title h4">Winning Candidates</h3>
                                                                                            <div class="row row-cols-1 row-cols-md-2 g-3">
                                                                                                <?php foreach ($positionsToShow as $position):
                                                                                                    $candidates = getCandidatesForPosition($pdo, $votingPeriodId, $position, $fullNameFieldId, $studentIdFieldId, $pictureFieldId, $partyFieldId, $positionFieldId, $collegeId);
                                                                                                    if (empty($candidates)) continue;
                                                                                                    $maxVotes = max(array_column($candidates, 'vote_count'));
                                                                                                    $topCandidates = array_filter($candidates, fn($c) => $c['vote_count'] === $maxVotes);
                                                                                                    foreach ($topCandidates as $winner):
                                                                                                ?>
                                                                                                        <div class="col">
                                                                                                            <div class="p-3 bg-light border rounded">
                                                                                                                <h4 class="fw-medium"><?php echo htmlspecialchars($position); ?></h4>
                                                                                                                <p class="mb-0"><?php echo htmlspecialchars($winner['full_name'] ?? 'Unknown'); ?> (<?php echo number_format($winner['vote_count']); ?> votes)</p>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    <?php endforeach; ?>
                                                                                                <?php endforeach; ?>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>

                                                                                    <?php foreach ($positionsToShow as $position):
                                                                                        $candidates = getCandidatesForPosition($pdo, $votingPeriodId, $position, $fullNameFieldId, $studentIdFieldId, $pictureFieldId, $partyFieldId, $positionFieldId, $collegeId);
                                                                                        if (empty($candidates)) continue;

                                                                                        $partyVotes = [];
                                                                                        foreach ($candidates as $cand) {
                                                                                            $party = $cand['partylist'] ?? 'Independent';
                                                                                            $partyVotes[$party] = ($partyVotes[$party] ?? 0) + $cand['vote_count'];
                                                                                        }

                                                                                        $maxVotes = max(array_column($candidates, 'vote_count'));
                                                                                    ?>
                                                                                        <div class="card mb-4 shadow-sm">
                                                                                            <div class="card-body">
                                                                                                <h3 class="card-title h4 fw-bold"><?php echo htmlspecialchars($position); ?></h3>
                                                                                                <div class="table-responsive">
                                                                                                    <table class="table table-striped table-hover">
                                                                                                        <thead class="table-dark">
                                                                                                            <tr>
                                                                                                                <th>Rank</th>
                                                                                                                <th>Photo</th>
                                                                                                                <th>Name</th>
                                                                                                                <th>Course</th>
                                                                                                                <th>College</th>
                                                                                                                <th>Partylist</th>
                                                                                                                <th>Votes</th>
                                                                                                            </tr>
                                                                                                        </thead>
                                                                                                        <tbody>
                                                                                                            <?php
                                                                                                            $currentRank = 1;
                                                                                                            $currentVotes = $maxVotes;
                                                                                                            $index = 0;
                                                                                                            foreach ($candidates as $cand):
                                                                                                                if ($cand['vote_count'] < $currentVotes) {
                                                                                                                    $currentRank = $index + 1;
                                                                                                                    $currentVotes = $cand['vote_count'];
                                                                                                                }
                                                                                                                $isWinner = $cand['vote_count'] === $maxVotes;
                                                                                                            ?>
                                                                                                                <tr class="<?php echo $isWinner ? 'table-success fw-bold' : ''; ?>">
                                                                                                                    <td><?php echo $currentRank; ?><?php echo $currentRank === 1 ? 'st' : ($currentRank === 2 ? 'nd' : ($currentRank === 3 ? 'rd' : 'th')); ?><?php echo $isWinner && count(array_filter($candidates, fn($c) => $c['vote_count'] === $maxVotes)) > 1 ? ' (Tie)' : ''; ?></td>
                                                                                                                    <td>
                                                                                                                        <?php if (!empty($cand['picture'])): ?>
                                                                                                                            <img src="../login/uploads/candidates/<?php echo htmlspecialchars($cand['picture']); ?>" width="60" height="60" class="rounded-circle" alt="Candidate Photo">
                                                                                                                        <?php else: ?>
                                                                                                                            <span class="text-muted">No Image</span>
                                                                                                                        <?php endif; ?>
                                                                                                                    </td>
                                                                                                                    <td><?php echo htmlspecialchars($cand['full_name'] ?? 'Unknown'); ?></td>
                                                                                                                    <td><?php echo htmlspecialchars($cand['course_name'] ?? 'N/A'); ?></td>
                                                                                                                    <td><?php echo htmlspecialchars($cand['college_name'] ?? 'N/A'); ?></td>
                                                                                                                    <td><?php echo htmlspecialchars($cand['partylist'] ?? 'Independent'); ?></td>
                                                                                                                    <td><strong><?php echo number_format($cand['vote_count']); ?></strong></td>
                                                                                                                </tr>
                                                                                                            <?php
                                                                                                                $index++;
                                                                                                            endforeach;
                                                                                                            ?>
                                                                                                        </tbody>
                                                                                                    </table>
                                                                                                </div>
                                                                                                <h6 class="mt-3">Partylist Vote Breakdown:</h6>
                                                                                                <ul class="list-unstyled">
                                                                                                    <?php foreach ($partyVotes as $party => $total): ?>
                                                                                                        <li><strong><?php echo htmlspecialchars($party); ?>:</strong> <?php echo number_format($total); ?> votes</li>
                                                                                                    <?php endforeach; ?>
                                                                                                </ul>
                                                                                                <div class="container-fluid row">
                                                                                                    <div class="d-flex justify-content-center align-items-center text-center mx-auto">
                                                                                                        <div class="col-md-3">
                                                                                                            <canvas id="chart-college-<?php echo md5($collegeId . $position); ?>" height="10" class="mt-3"></canvas>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <script>
                                                                                                    new Chart(document.getElementById('chart-college-<?php echo md5($collegeId . $position); ?>'), {
                                                                                                        type: 'pie',
                                                                                                        data: {
                                                                                                            labels: <?php echo json_encode(array_column($candidates, 'full_name')); ?>,
                                                                                                            datasets: [{
                                                                                                                label: 'Votes',
                                                                                                                data: <?php echo json_encode(array_column($candidates, 'vote_count')); ?>,
                                                                                                                backgroundColor: ['#dc3545', '#0d6efd', '#ffc107', '#6f42c1', '#20c997', '#fd7e14', '#28a745', '#ffeb3b'],
                                                                                                                borderWidth: 1
                                                                                                            }]
                                                                                                        },
                                                                                                        options: {
                                                                                                            responsive: true,
                                                                                                            plugins: {
                                                                                                                legend: {
                                                                                                                    position: 'bottom'
                                                                                                                }
                                                                                                            }
                                                                                                        }
                                                                                                    });
                                                                                                </script>
                                                                                            </div>
                                                                                        </div>
                                                                                    <?php endforeach; ?>
                                                                                <?php else: ?>
                                                                                    <div class="alert alert-warning" role="alert">
                                                                                        <i>No candidates found for <?php echo htmlspecialchars($collegeName); ?> in this election.</i>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>

                                                                    <!-- ESU Campuses -->
                                                                    <hr class="my-4">
                                                                    <h2 class="display-5 fw-bold mb-4">ESU CAMPUSES</h2>
                                                                    <div class="mb-3">
                                                                        <select id="campus-select" class="form-select">
                                                                            <option value="">Select Campus</option>
                                                                            <?php foreach ($esu_campuses as $c): ?>

                                                                                <?php
                                                                                $campusId = $c['campus_id'];
                                                                                $campusName = $c['campus_name'];
                                                                                ?>

                                                                                <option value="<?= $c['campus_id'] ?>">
                                                                                    <?= htmlspecialchars($c['campus_name']) ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>

                                                                    <div id="campus-results" class="d-none">
                                                                        <?php foreach ($esu_campuses as $c): ?>

                                                                            <div class="campus-section" data-campus="<?php echo htmlspecialchars($c['campus_id']); ?>">
                                                                                <?php $stats = getVoterStats($pdo, $votingPeriodId, null, $c['campus_id']);

                                                                                ?>
                                                                                <div class="card mb-4 shadow-sm">
                                                                                    <div class="card-body">
                                                                                        <h3 class="card-title h4"><?php echo htmlspecialchars($c['campus_name']); ?> Voter Stats</h3>
                                                                                        <p>Total Verified Voters: <strong><?php echo number_format($stats['total']); ?></strong></p>
                                                                                        <p>Voted: <strong><?php echo number_format($stats['voted']); ?></strong></p>
                                                                                        <p>Did Not Vote: <strong><?php echo number_format($stats['total'] - $stats['voted']); ?></strong></p>
                                                                                    </div>
                                                                                </div>

                                                                                <?php
                                                                                $hasCampusCandidates = false;
                                                                                foreach ($positionsToShow as $position):
                                                                                    $candidates = getCandidatesForPosition($pdo, $votingPeriodId, $position, $fullNameFieldId, $studentIdFieldId, $pictureFieldId, $partyFieldId, $positionFieldId, null,  $campusId);
                                                                                    if (!empty($candidates)) {
                                                                                        $hasCampusCandidates = true;
                                                                                        break;
                                                                                    }
                                                                                endforeach;
                                                                                ?>

                                                                                <?php if ($hasCampusCandidates): ?>
                                                                                    <div class="card mb-4 shadow-sm">
                                                                                        <div class="card-body">
                                                                                            <h3 class="card-title h4">Winning Candidates</h3>
                                                                                            <div class="row row-cols-1 row-cols-md-2 g-3">
                                                                                                <?php foreach ($positionsToShow as $position):
                                                                                                    $candidates = getCandidatesForPosition($pdo, $votingPeriodId, $position, $fullNameFieldId, $studentIdFieldId, $pictureFieldId, $partyFieldId, $positionFieldId, null,  $campusId);
                                                                                                    if (empty($candidates)) continue;
                                                                                                    $maxVotes = max(array_column($candidates, 'vote_count'));
                                                                                                    $topCandidates = array_filter($candidates, fn($c) => $c['vote_count'] === $maxVotes);
                                                                                                    foreach ($topCandidates as $winner):
                                                                                                ?>
                                                                                                        <div class="col">
                                                                                                            <div class="p-3 bg-light border rounded">
                                                                                                                <h4 class="fw-medium"><?php echo htmlspecialchars($position); ?></h4>
                                                                                                                <p class="mb-0"><?php echo htmlspecialchars($winner['full_name'] ?? 'Unknown'); ?> (<?php echo number_format($winner['vote_count']); ?> votes)</p>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    <?php endforeach; ?>
                                                                                                <?php endforeach; ?>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>

                                                                                    <?php foreach ($positionsToShow as $position):
                                                                                        $candidates = getCandidatesForPosition($pdo, $votingPeriodId, $position, $fullNameFieldId, $studentIdFieldId, $pictureFieldId, $partyFieldId, $positionFieldId, null,  $campusId);
                                                                                        if (empty($candidates)) continue;

                                                                                        $partyVotes = [];
                                                                                        foreach ($candidates as $cand) {
                                                                                            $party = $cand['partylist'] ?? 'Independent';
                                                                                            $partyVotes[$party] = ($partyVotes[$party] ?? 0) + $cand['vote_count'];
                                                                                        }

                                                                                        $maxVotes = max(array_column($candidates, 'vote_count'));
                                                                                    ?>
                                                                                        <div class="card mb-4 shadow-sm">
                                                                                            <div class="card-body">
                                                                                                <h3 class="card-title h4 fw-bold"><?php echo htmlspecialchars($position); ?></h3>
                                                                                                <div class="table-responsive">
                                                                                                    <table class="table table-striped table-hover">
                                                                                                        <thead class="table-dark">
                                                                                                            <tr>
                                                                                                                <th>Rank</th>
                                                                                                                <th>Photo</th>
                                                                                                                <th>Name</th>
                                                                                                                <th>Course</th>
                                                                                                                <th>College</th>
                                                                                                                <th>Partylist</th>
                                                                                                                <th>Votes</th>
                                                                                                            </tr>
                                                                                                        </thead>
                                                                                                        <tbody>
                                                                                                            <?php
                                                                                                            $currentRank = 1;
                                                                                                            $currentVotes = $maxVotes;
                                                                                                            $index = 0;
                                                                                                            foreach ($candidates as $cand):
                                                                                                                if ($cand['vote_count'] < $currentVotes) {
                                                                                                                    $currentRank = $index + 1;
                                                                                                                    $currentVotes = $cand['vote_count'];
                                                                                                                }
                                                                                                                $isWinner = $cand['vote_count'] === $maxVotes;
                                                                                                            ?>
                                                                                                                <tr class="<?php echo $isWinner ? 'table-success fw-bold' : ''; ?>">
                                                                                                                    <td><?php echo $currentRank; ?><?php echo $currentRank === 1 ? 'st' : ($currentRank === 2 ? 'nd' : ($currentRank === 3 ? 'rd' : 'th')); ?><?php echo $isWinner && count(array_filter($candidates, fn($c) => $c['vote_count'] === $maxVotes)) > 1 ? ' (Tie)' : ''; ?></td>
                                                                                                                    <td>
                                                                                                                        <?php if (!empty($cand['picture'])): ?>
                                                                                                                            <img src="../login/uploads/candidates/<?php echo htmlspecialchars($cand['picture']); ?>" width="60" height="60" class="rounded-circle" alt="Candidate Photo">
                                                                                                                        <?php else: ?>
                                                                                                                            <span class="text-muted">No Image</span>
                                                                                                                        <?php endif; ?>
                                                                                                                    </td>
                                                                                                                    <td><?php echo htmlspecialchars($cand['full_name'] ?? 'Unknown'); ?></td>
                                                                                                                    <td><?php echo htmlspecialchars($cand['course_name'] ?? 'N/A'); ?></td>
                                                                                                                    <td><?php echo htmlspecialchars($cand['college_name'] ?? 'N/A'); ?></td>
                                                                                                                    <td><?php echo htmlspecialchars($cand['partylist'] ?? 'Independent'); ?></td>
                                                                                                                    <td><strong><?php echo number_format($cand['vote_count']); ?></strong></td>
                                                                                                                </tr>
                                                                                                            <?php
                                                                                                                $index++;
                                                                                                            endforeach;
                                                                                                            ?>
                                                                                                        </tbody>
                                                                                                    </table>
                                                                                                </div>
                                                                                                <h6 class="mt-3">Partylist Vote Breakdown:</h6>
                                                                                                <ul class="list-unstyled">
                                                                                                    <?php foreach ($partyVotes as $party => $total): ?>
                                                                                                        <li><strong><?php echo htmlspecialchars($party); ?>:</strong> <?php echo number_format($total); ?> votes</li>
                                                                                                    <?php endforeach; ?>
                                                                                                </ul>
                                                                                                <div class="container-fluid row">
                                                                                                    <div class="d-flex justify-content-center align-items-center text-center mx-auto">
                                                                                                        <div class="col-md-3">
                                                                                                            <canvas id="chart-campus-<?php echo md5($campusId . $position); ?>" height="50" class="mt-3"></canvas>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <script>
                                                                                                    new Chart(document.getElementById('chart-campus-<?php echo md5($campusId . $position); ?>'), {
                                                                                                        type: 'pie',
                                                                                                        data: {
                                                                                                            labels: <?php echo json_encode(array_column($candidates, 'full_name')); ?>,
                                                                                                            datasets: [{
                                                                                                                label: 'Votes',
                                                                                                                data: <?php echo json_encode(array_column($candidates, 'vote_count')); ?>,
                                                                                                                backgroundColor: ['#dc3545', '#0d6efd', '#ffc107', '#6f42c1', '#20c997', '#fd7e14', '#28a745', '#ffeb3b'],
                                                                                                                borderWidth: 1
                                                                                                            }]
                                                                                                        },
                                                                                                        options: {
                                                                                                            responsive: true,
                                                                                                            plugins: {
                                                                                                                legend: {
                                                                                                                    position: 'bottom'
                                                                                                                }
                                                                                                            }
                                                                                                        }
                                                                                                    });
                                                                                                </script>
                                                                                            </div>
                                                                                        </div>
                                                                                    <?php endforeach; ?>
                                                                                <?php else: ?>
                                                                                    <div class="alert alert-warning" role="alert">
                                                                                        <i>No candidates found for <?php echo htmlspecialchars($campusName); ?> in this election.</i>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                </div>

                                                                <script>
                                                                    document.getElementById('campus-select').addEventListener('change', function() {
                                                                        const selected = this.value.trim();
                                                                        const results = document.getElementById('campus-results');
                                                                        const sections = document.querySelectorAll('.campus-section');
                                                                        sections.forEach(section => {
                                                                            const sectionCampus = section.dataset.campus.trim();
                                                                            section.style.display = sectionCampus === selected ? 'block' : 'none';
                                                                            if (sectionCampus === selected && section.querySelector('.alert-warning')) {
                                                                                console.log(`No candidates found for campus: ${selected}`);
                                                                            }
                                                                        });
                                                                        results.classList.toggle('d-none', !selected);
                                                                        console.log(`Selected campus: ${selected}`);
                                                                    });
                                                                </script>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- page-body-wrapper ends -->
                                </div>
                                <!-- container-scroller -->

                                <script>
                                    $(document).ready(function() {
                                        // Global variable for voting period ID
                                        let votingPeriodId = '<?php echo $voting_period_id ?>'; // PHP-injected voting_period_id
                                        if (!votingPeriodId) {
                                            alert('Voting period ID is missing');
                                            return;
                                        }

                                    });
                                </script>
    </body>

    <?php if (isset($_SESSION['STATUS'])): ?>
        <script>
            <?php if ($_SESSION['STATUS'] === "REVOTE_UPDATED_SUCCESSFULLY"): ?>
                Swal.fire("Success", "Voting period updated successfully towards revoting.", "success");
            <?php elseif ($_SESSION['STATUS'] === "REVOTE_UPDATE_NO_CHANGES"): ?>
                Swal.fire("Notice", "No changes made to the voting period.", "info");
            <?php elseif (str_starts_with($_SESSION['STATUS'], "REVOTE_UPDATE_ERROR")): ?>
                Swal.fire("Error", "<?php echo addslashes($_SESSION['STATUS']); ?>", "error");
            <?php endif; ?>
        </script>
        <?php unset($_SESSION['STATUS']); ?>
    <?php endif; ?>



    <!-- Public Revote Reschedule Modal -->
    <div class="modal fade" id="publicRevoteModal" tabindex="-1" aria-labelledby="publicRevoteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="publicRevoteForm" action="editVotingPeriod.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold" id="publicRevoteModalLabel">Reschedule Public Revote</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date & Time</label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" required
                                value="<?php echo date('Y-m-d\TH:i'); ?>">

                        </div>

                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                        </div>

                        <input type="hidden" id="rescheduleVotingPeriodId" name="voting_period_id" value="<?php echo $_GET['voting_period_id'] ?>">
                        <input type="hidden" id="rescheduleTiedPositions" name="positions">

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Reschedule</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

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

            const startDateInput = document.getElementById('start_date');
            if (startDateInput) {
                startDateInput.min = formattedDateTime;
            }
            const endDateInput = document.getElementById('end_date');
            if (endDateInput) {
                endDateInput.min = formattedDateTime;
            }
        });
    </script>

</html>