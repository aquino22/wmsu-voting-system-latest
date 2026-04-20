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
    <title>WMSU I-Elect: Admin | Voting Periods</title>
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <link rel="shortcut icon" href="images/favicon.png" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .button-view-primary,
        .button-view-warning,
        .button-view-secondary,
        .button-view-info {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            margin-right: 5px;
        }

        .button-view-primary {
            background-color: #007bff;
        }

        .button-view-warning {
            background-color: #ffc107;
        }

        .button-view-secondary {
            background-color: #6c757d;
        }

        .button-view-info {
            background-color: #17a2b8;
        }

        .button-view-primary:hover,
        .button-view-warning:hover,
        .button-view-secondary:hover,
        .button-view-info:hover {
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <div class="container-scroller">
        <!-- Navbar unchanged -->
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

            <?php
            // Get the current PHP file name
            $current_page = basename($_SERVER['PHP_SELF']);
            ?>

            <?php include('includes/sidebar.php') ?>
            </ul>

            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Voting</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel">
                                        <div class="card card-rounded mb-5">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <h3 class="mb-0">
                                                        <b>Voting Period</b>

                                                    </h3>

                                                    <button class="btn btn-primary"
                                                        style="color: white" data-bs-toggle="modal"
                                                        data-bs-target="#addVotingModal"> <i class="mdi mdi-plus-circle"></i>
                                                        Add Voting Period</button>
                                                </div>

                                                <!-- Add Voting Period Modal -->
                                                <div class="modal fade" id="addVotingModal" tabindex="-1" aria-labelledby="addVotingModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="addVotingModalLabel">Add Voting Period</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form id="addVotingForm" method="POST">
                                                                <div class="modal-body">
                                                                    <div class="mb-3">
                                                                        <label for="electionId" class="form-label">Election Name:</label>
                                                                        <select class="form-select" id="electionId" name="election_id" required>
                                                                            <option value="">Select an Election</option>
                                                                        </select>
                                                                    </div>

                                                                    <!-- Read-only election info -->
                                                                    <div class="mb-2">
                                                                        <small id="electionMetaInfo" class="text-muted fst-italic d-block"></small>
                                                                    </div>

                                                                    <div class="mb-2">
                                                                        <small id="electionScheduleInfo" class="text-muted fst-italic d-block"></small>
                                                                    </div>

                                                                    <!-- Voting Start -->
                                                                    <div class="mb-3">
                                                                        <label class="form-label">
                                                                            Voting Start Period:
                                                                            <small>(auto-set after election ends)</small>
                                                                        </label>
                                                                        <input type="datetime-local"
                                                                            class="form-control"
                                                                            id="startPeriod"
                                                                            name="start_period"
                                                                            required>
                                                                    </div>

                                                                    <!-- Voting End -->
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Voting End Period:</label>
                                                                        <input type="datetime-local"
                                                                            class="form-control"
                                                                            id="endPeriod"
                                                                            name="end_period"
                                                                            required>
                                                                    </div>

                                                                    <!-- Status -->
                                                                    <div class="mb-3">
                                                                        <label for="votingStatus" class="form-label">Status:</label>
                                                                        <select class="form-select" id="votingStatus" name="status" required>
                                                                            <option value="" disabled>Select Status</option>
                                                                            <option value="Scheduled">Scheduled</option>

                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                        <i class="mdi mdi-close"></i> Close
                                                                    </button>
                                                                    <button type="submit" class="btn btn-primary text-white">
                                                                        <i class="mdi mdi-upload"></i> Save
                                                                    </button>
                                                                </div>
                                                            </form>

                                                        </div>
                                                    </div>
                                                </div>

                                                <script>
                                                    let electionsData = [];

                                                    $(document).ready(function() {

                                                        // Load elections for voting
                                                        $.ajax({
                                                            url: 'processes/elect/fetch_election_dropdown.php',
                                                            method: 'GET',
                                                            dataType: 'json',
                                                            success: function(response) {

                                                                if (!Array.isArray(response)) return;

                                                                electionsData = response;

                                                                response.forEach(election => {
                                                                    $('#electionId').append(`
          <option value="${election.election_id}">
            ${election.election_name}
          </option>
        `);
                                                                });
                                                            }
                                                        });

                                                        // When election changes
                                                        $('#electionId').on('change', function() {
                                                            const electionId = $(this).val();
                                                            const election = electionsData.find(e => e.election_id == electionId);

                                                            if (!election) {
                                                                $('#electionMetaInfo').text('');
                                                                $('#electionScheduleInfo').text('');
                                                                return;
                                                            }

                                                            // Academic info
                                                            $('#electionMetaInfo').text(
                                                                `School Year: ${election.year_label} | Semester: ${election.semester}`
                                                            );

                                                            // Election schedule
                                                            const electionStart = new Date(election.start_period.replace(' ', 'T'));
                                                            const electionEnd = new Date(election.end_period.replace(' ', 'T'));

                                                            $('#electionScheduleInfo').text(
                                                                `Election period: ${electionStart.toLocaleString()} – ${electionEnd.toLocaleString()}`
                                                            );

                                                            // Add validation based on academic year dates
                                                            if (election.start_date && election.end_date) {
                                                                const startInput = $('#startPeriod');
                                                                const endInput = $('#endPeriod');
                                                                const ayStartDate = election.start_date.split(' ')[0] + 'T00:00';
                                                                const ayEndDate = election.end_date.split(' ')[0] + 'T23:59';

                                                                startInput.attr('min', ayStartDate);
                                                                startInput.attr('max', ayEndDate);
                                                                endInput.attr('min', ayStartDate);
                                                                endInput.attr('max', ayEndDate);
                                                            }


                                                        });

                                                    });
                                                </script>


                                                <!-- Voting Periods Table -->
                                                <div class="table-responsive">
                                                    <table id="votingTable" class="table table-striped nowrap w-100">
                                                        <thead>
                                                            <tr>
                                                                <th>Semester</th>
                                                                <th>School Year</th>
                                                                <th>Name</th>


                                                                <th>Start Period</th>
                                                                <th>End Period</th>
                                                                <th>Previous Start Period</th>
                                                                <th>Previous End Period</th>
                                                                <th>Status</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            try {
                                                                $stmt = $pdo->query("
        SELECT 
            vp.id AS voting_id,
            vp.start_period,
            vp.end_period,
            vp.re_start_period,
            vp.re_end_period,
            vp.status,
            e.election_name,
            ay.semester,
            ay.year_label,
            ay.start_date,
            ay.end_date
        FROM voting_periods vp
        JOIN elections e ON vp.election_id = e.id
        JOIN academic_years ay ON e.academic_year_id = ay.id
        WHERE vp.status IN ('Scheduled', 'Ongoing', 'Paused')
        ORDER BY vp.id DESC
    ");

                                                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                                                    echo "<tr>";
                                                                    echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
                                                                    echo "<td>" . htmlspecialchars($row['year_label']) . "</td>";
                                                                    echo "<td>" . htmlspecialchars($row['election_name']) . "</td>";

                                                                    echo "<td>";
                                                                    echo $row['start_period'] ? (new DateTime($row['start_period']))->format('F j, Y, g:i A') : "Not yet started";
                                                                    echo "</td><td>";
                                                                    echo $row['end_period'] ? (new DateTime($row['end_period']))->format('F j, Y, g:i A') : "Not yet ended";

                                                                    echo "<td>";
                                                                    echo $row['re_start_period'] ? (new DateTime($row['re_start_period']))->format('F j, Y, g:i A') : "No restarts yet";
                                                                    echo "</td>";

                                                                    echo "<td>";
                                                                    echo $row['re_end_period'] ? (new DateTime($row['re_end_period']))->format('F j, Y, g:i A') : "No restarts yet";
                                                                    echo "</td>";

                                                                    echo "<td>";
                                                                    $badge = match ($row['status']) {
                                                                        'Ongoing' => 'success',
                                                                        'Published' => 'danger',
                                                                        'Paused' => 'warning',
                                                                        default => 'primary'
                                                                    };
                                                                    echo "<span class='badge bg-{$badge}'>" . htmlspecialchars($row['status']) . "</span>";
                                                                    echo "</td>";

                                                                    echo "<td>";
                                                                    // Buttons logic remains the same
                                                                    if ($row['status'] == 'Scheduled') {
                                                                        echo "<button class='btn btn-primary text-white start-voting-btn' data-id='{$row['voting_id']}'><i class='mdi mdi-play'></i> Start</button>";
                                                                        echo "<button class='btn btn-secondary text-black reschedule-voting-btn' data-id='{$row['voting_id']}'><i class='mdi mdi-calendar'></i> Reschedule</button>";
                                                                        echo "<button class='btn btn-danger text-white delete-voting-btn' data-id='{$row['voting_id']}'><i class='mdi mdi-delete'></i> Delete</button>";
                                                                    } elseif ($row['status'] == 'Ongoing') {
                                                                        echo "  <a href='view_reports.php?voting_period_id={$row['voting_id']}'>
                      <button class='btn btn-warning text-white ' data-id='{$row['voting_id']}'><i class='mdi mdi-stop'></i> View Results and Tally</button> </a>";
                                                                        echo "<button class='btn btn-secondary text-black pause-voting-btn' data-id='{$row['voting_id']}'><i class='mdi mdi-pause'></i> Pause</button>";
                                                                    } elseif ($row['status'] == 'Paused') {
                                                                        echo "<button class='btn btn-primary text-white start-voting-btn' data-id='{$row['voting_id']}'><i class='mdi mdi-play'></i> Resume</button>";
                                                                        echo "<button class='btn btn-secondary text-black reschedule-voting-btn' data-id='{$row['voting_id']}'><i class='mdi mdi-calendar'></i> Reschedule</button>";
                                                                        echo "  <a href='view_reports.php?voting_period_id={$row['voting_id']}'>
                      <button class='btn btn-warning text-white ' data-id='{$row['voting_id']}'><i class='mdi mdi-stop'></i> View Results and Tally</button> </a>";
                                                                    } elseif ($row['status'] == 'Published') {
                                                                        echo "<a href='view_published.php?voting_period_id={$row['voting_id']}'>
                    <button class='btn btn-success text-white view-results-btn' data-id='{$row['voting_id']}'>
                        <i class='mdi mdi-eye'></i> View Results
                    </button>
                  </a>";
                                                                    } elseif ($row['status'] == 'Ended') {
                                                                        echo "<a href='view_reports.php?voting_period_id={$row['voting_id']}'>
                    <button class='btn btn-warning text-white view-results-btn' data-id='{$row['voting_id']}'>
                        <i class='mdi mdi-eye'></i> View Tallying
                    </button>
                  </a>";
                                                                    }

                                                                    echo "</td>";
                                                                    echo "</tr>";
                                                                }
                                                            } catch (PDOException $e) {
                                                                $_SESSION['STATUS'] = "DATABASE_ERROR";
                                                                $_SESSION['MESSAGE'] = "Database error: " . $e->getMessage();
                                                            }
                                                            ?>
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
        </div>
        <style>
            /* Custom CSS to move the search input to the right */
            .dataTables_wrapper .dataTables_filter {
                float: right !important;
                text-align: right;
            }

            .dataTables_wrapper .dataTables_filter input {
                margin-left: 1rem;
            }
        </style>
        <!-- Scripts -->
        <script src="vendors/js/vendor.bundle.base.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            $(document).ready(function() {
                $('#votingTable').DataTable({
                    order: [
                        [0, 'asc']
                    ],
                    responsive: true,
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true

                });

                // Populate modal fields based on election selection
                $('#electionName').on('change', function() {
                    let electionName = $(this).val();
                    if (!electionName) {
                        $('#semester').val('');
                        $('#schoolYearStart').val('');
                        $('#schoolYearEnd').val('');
                        return;
                    }
                    $.ajax({
                        url: 'processes/voting/fetch_election_details.php',
                        method: 'GET',
                        data: {
                            election_name: electionName
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                $('#semester').val(response.data.semester);
                                $('#schoolYearStart').val(response.data.school_year_start);
                                $('#schoolYearEnd').val(response.data.school_year_end);
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                                $('#semester').val('');
                                $('#schoolYearStart').val('');
                                $('#schoolYearEnd').val('');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to fetch election details.', 'error');
                        }
                    });
                });

                // Form submission
                $('#addVotingForm').on('submit', function(e) {
                    e.preventDefault();

                    const start = $('#startPeriod').val();
                    const end = $('#endPeriod').val();

                    if (!start || !end) {
                        Swal.fire('Invalid Input', 'Start and end period are required.', 'warning');
                        return;
                    }

                    if (new Date(end) <= new Date(start)) {
                        Swal.fire('Invalid Period', 'End period must be after start period.', 'warning');
                        return;
                    }

                    Swal.fire({
                        title: 'Adding Voting Period...',
                        text: 'Please wait.',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    $.ajax({
                        url: 'processes/voting/add_voting_period.php',
                        type: 'POST',
                        data: $(this).serialize(), // election_id, start_period, end_period, status
                        dataType: 'json',
                        success: function(response) {
                            Swal.close();

                            if (response.status === 'success') {
                                Swal.fire('Success', response.message, 'success').then(() => {
                                    $('#addVotingModal').modal('hide');
                                    $('#addVotingForm')[0].reset();
                                    location.reload(); // <- ensures new voting period shows up
                                    // Clear contextual info
                                    $('#electionMetaInfo').text('');
                                    $('#electionScheduleInfo').text('');
                                });
                            } else {
                                Swal.fire('Error', response.message || 'Unable to add voting period.', 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.close();
                            Swal.fire(
                                'Server Error',
                                xhr.responseText || 'Failed to add voting period.',
                                'error'
                            );
                        }
                    });
                });


                // Voting Actions
                function handleAction(action, id, message) {
                    Swal.fire({
                        title: `Are you sure you want to ${action} this voting period?`,
                        text: message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: `Yes, ${action} it!`
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: `${action}ing...`,
                                text: 'Please wait.',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            $.ajax({
                                url: 'processes/voting/update_voting_status.php',
                                type: 'POST',
                                data: {
                                    id: id,
                                    action: action.toLowerCase()
                                },
                                dataType: 'json',
                                success: function(response) {
                                    Swal.close();
                                    if (response.status === 'success') {
                                        Swal.fire(`${action} Successful!`, response.message, 'success').then(() => location.reload());
                                    } else {
                                        Swal.fire('Error!', response.message, 'error');
                                    }
                                },
                                error: function() {
                                    Swal.close();
                                    Swal.fire('Error!', `Failed to ${action} voting period.`, 'error');
                                }
                            });
                        }
                    });
                }




                $('.start-voting-btn').on('click', function() {

                    handleAction('Start', $(this).data('id'), 'This will start the voting period.');
                });

                $('.delete-voting-btn').on('click', function() {
                    handleAction('Delete', $(this).data('id'), 'This will delete the voting period.');
                });

                $('.end-voting-btn').on('click', function() {
                    handleAction('End', $(this).data('id'), 'This will end the voting period and proceed to tallying.');
                });
                $('.pause-voting-btn').on('click', function() {
                    handleAction('Pause', $(this).data('id'), 'This will pause the voting period.');
                });
                $('.reschedule-voting-btn').on('click', function() {
                    let id = $(this).data('id');
                    Swal.fire({
                        title: 'Reschedule Voting Period',
                        html: `
                        <label>Start Period:</label>
                        <input type="datetime-local" id="newStartPeriod" class="swal2-input">
                        <label>End Period:</label>
                        <input type="datetime-local" id="newEndPeriod" class="swal2-input">
                    `,
                        showCancelButton: true,
                        confirmButtonText: 'Reschedule',
                        didOpen: () => {
                            const now = new Date();
                            const pad = num => String(num).padStart(2, '0');
                            const formattedDateTime = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;


                            const newEndPeriod = document.getElementById('newEndPeriod');
                            if (newEndPeriod) {
                                newEndPeriod.min = formattedDateTime;
                            }
                        },
                        preConfirm: () => {
                            return {
                                start_period: document.getElementById('newStartPeriod').value,
                                end_period: document.getElementById('newEndPeriod').value
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Rescheduling...',
                                text: 'Please wait.',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                            $.ajax({
                                url: 'processes/voting/update_voting_status.php',
                                type: 'POST',
                                data: {
                                    id: id,
                                    action: 'reschedule',
                                    start_period: result.value.start_period,
                                    end_period: result.value.end_period
                                },
                                dataType: 'json',
                                success: function(response) {
                                    Swal.close();
                                    if (response.status === 'success') {
                                        Swal.fire('Rescheduled!', response.message, 'success').then(() => location.reload());
                                    } else {
                                        Swal.fire('Error!', response.message, 'error');
                                    }
                                },
                                error: function() {
                                    Swal.close();
                                    Swal.fire('Error!', 'Failed to reschedule voting period.', 'error');
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
                const formattedDateTime = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;


                const endPeriod = document.getElementById('endPeriod');
                if (endPeriod) {
                    endPeriod.min = formattedDateTime;
                }
            });
        </script>


        <!-- put this <script> after your Bootstrap JS import -->
        <script>
            /**
             * Pads a number with a leading 0 (e.g. 8 → "08")
             */
            const pad = n => n.toString().padStart(2, "0");

            /**
             * Converts a Date object → the yyyy‑MM‑ddTHH:mm format
             * required by <input type="datetime‑local">
             */
            function toDateTimeLocal(d) {
                return (
                    d.getFullYear() + "-" +
                    pad(d.getMonth() + 1) + "-" +
                    pad(d.getDate()) + "T" +
                    pad(d.getHours()) + ":" +
                    pad(d.getMinutes())
                );
            }

            /**
             * Sets default start/end values every time the modal is shown.
             * If you only ever create the modal once you could run this just
             * on DOMContentLoaded, but attaching to the `show.bs.modal`
             * event guarantees the values are always fresh.
             */
            const modal = document.getElementById("addVotingModal");
            modal.addEventListener("show.bs.modal", () => {
                const startInput = document.getElementById("startPeriod");
                const endInput = document.getElementById("endPeriod");

                // Today, 07:00
                const todayStart = new Date();
                todayStart.setHours(7, 0, 0, 0);

                // Tomorrow, 16:00
                const tomorrowEnd = new Date(todayStart);
                tomorrowEnd.setDate(tomorrowEnd.getDate() + 1); // move to tomorrow
                tomorrowEnd.setHours(16, 0, 0, 0);

                // Apply
                startInput.value = toDateTimeLocal(todayStart);
                endInput.value = toDateTimeLocal(tomorrowEnd);

                /* Optional: make the fields read‑only so users can’t tamper
                   startInput.readOnly = true;
                   endInput.readOnly   = true;
                */
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

    </div>
</body>

<button class="back-to-top" id="backToTop" title="Go to top">
    <i class="mdi mdi-arrow-up"></i>
</button>

</html>