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

// Fetch all elections
try {
    $stmt = $pdo->prepare("SELECT id, election_name, start_period, end_period, status FROM elections ORDER BY created_at DESC");
    $stmt->execute();
    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $elections_with_voting = [];

    foreach ($elections as $election) {
        $stmt = $pdo->prepare("SELECT id, status, start_period, end_period FROM voting_periods WHERE election_id = ? LIMIT 1");
        $stmt->execute([$election['id']]);
        $voting_period = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT start_period, end_period FROM candidacy WHERE election_id = ? LIMIT 1");
        $stmt->execute([$election['id']]);
        $candidacy = $stmt->fetch(PDO::FETCH_ASSOC);

        // Add id and status to the election array
        $election['voting_period_id'] = $voting_period ? $voting_period['id'] : null;
        $election['voting_period_status'] = $voting_period ? $voting_period['status'] : null;
        $election['voting_start'] = $voting_period ? $voting_period['start_period'] : null;
        $election['voting_end'] = $voting_period ? $voting_period['end_period'] : null;
        $election['candidacy_start'] = $candidacy ? $candidacy['start_period'] : null;
        $election['candidacy_end'] = $candidacy ? $candidacy['end_period'] : null;

        $elections_with_voting[] = $election;
    }
} catch (Exception $e) {
    $elections_with_voting = [];
    echo "<p>Error fetching elections: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU i-Elect Admin | Reports </title>
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

    <style>
        .card-election {
            transition: transform 0.2s;
            cursor: pointer;
        }

        .card-election:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .card-election-body {
            text-align: center;
        }

        .status-ongoing {
            color: #28a745 !important;
            /* green */
            font-weight: bold;
        }

        .status-published {
            color: #007bff;
            /* blue */
            font-weight: bold;
        }

        .status-ended {
            color: #fd7e14;
            /* orange */
            font-weight: bold;
        }

        .status-completed {
            color: #6c757d;
            /* gray */
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container-scroller">
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

        <div class="container-fluid page-body-wrapper">


            <?php include('includes/sidebar.php') ?>

            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">

                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab"
                                                aria-controls="overview" aria-selected="true">Reports</a>
                                        </li>

                                    </ul>

                                </div>
                                <div class="card mt-5">
                                    <div class="card-body">
                                        <h3 class="mb-4 mb-2"><b>Election Reports Archive</b></h3>
                                        <div class="row">
                                            <?php foreach ($elections_with_voting as $election): ?>
                                                <div class="col-md-4 mb-4 mt-3">
                                                    <div class="card card-election">

                                                        <div class="card-body card-election-body">
                                                            <h5 class="card-title"><b><?php echo htmlspecialchars($election['election_name']); ?></b></h5>
                                                            <p class="card-text">
                                                                <small>
                                                                    <b>Election:</b> <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($election['start_period']))); ?> - <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($election['end_period']))); ?><br>
                                                                    <?php if ($election['candidacy_start']): ?>
                                                                        <b>Candidacy:</b> <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($election['candidacy_start']))); ?> - <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($election['candidacy_end']))); ?><br>
                                                                    <?php endif; ?>
                                                                    <?php if ($election['voting_start']): ?>
                                                                        <b>Voting:</b> <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($election['voting_start']))); ?> - <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($election['voting_end']))); ?><br>
                                                                    <?php endif; ?>
                                                                </small>

                                                                <b>Status:</b>
                                                                <?php
                                                                // Determine class
                                                                $statusClass = match ($election['voting_period_status'] ?? '') {
                                                                    'Ongoing' => 'status-ongoing',
                                                                    'Published' => 'status-published',
                                                                    'Ended' => 'status-ended',
                                                                    default => 'status-completed',
                                                                };

                                                                // Determine text
                                                                $statusText = in_array($election['voting_period_status'], ['Ongoing', 'Published', 'Ended'])
                                                                    ? $election['voting_period_status']
                                                                    : $election['status'];
                                                                ?>
                                                                <span class="<?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span>


                                                            </p>
                                                            <div class="btn-group">
                                                                <?php if ($election['status'] === 'Published'): ?>
                                                                    <a href="view_published.php?voting_period_id=<?php echo urlencode($election['voting_period_id']); ?>" class="btn btn-primary text-white">View Report</a>
                                                                <?php else: ?>
                                                                    <button type="button" class="btn btn-primary text-white" onclick="viewReport('<?php echo $election['voting_period_id']; ?>'); event.stopPropagation();">View Report</button>
                                                                <?php endif; ?>
                                                                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split text-white" data-bs-toggle="dropdown" aria-expanded="false" onclick="event.stopPropagation();">
                                                                    <span class="visually-hidden"></span>
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                    <?php if ($election['status'] === 'Published'): ?>
                                                                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); updateStatus('<?php echo $election['id']; ?>', 'Ended'); event.stopPropagation();">Unpublish</a></li>

                                                                    <?php endif; ?>
                                                                    <li><a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); deleteElection('<?php echo $election['id']; ?>'); event.stopPropagation();">Delete</a></li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (empty($elections_with_voting)): ?>
                                                <div class="col-12 text-center">
                                                    <h3>No elections found.</h3>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="vendors/js/vendor.bundle.base.js"></script>
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
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

        <script>
            function viewReport(votingPeriodId) {
                if (!votingPeriodId) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'No voting period associated with this election.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
                window.location.href = 'view_reports.php?voting_period_id=' + encodeURIComponent(votingPeriodId);
            }

            function publishElection(votingPeriodId) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You are about to publish this election results. This will archive the current data.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, publish it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'publish_results.php?voting_period_id=' + encodeURIComponent(votingPeriodId);
                    }
                })
            }

            function updateStatus(electionId, status) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You are about to change the election status to " + status + ".",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, change it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performAction('update_status', {
                            election_id: electionId,
                            status: status
                        });
                    }
                })
            }

            function deleteElection(electionId) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this! This will delete the election and associated data.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performAction('delete', {
                            election_id: electionId
                        });
                    }
                })
            }

            function performAction(action, data) {
                $.post('manage_election_action.php', {
                    action: action,
                    ...data
                }, function(response) {
                    try {
                        if (response.success) {
                            Swal.fire('Success!', response.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error!', 'Unexpected response from server.', 'error');
                    }
                }, 'json');
            }
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

        <button class="back-to-top" id="backToTop" title="Go to top">
            <i class="mdi mdi-arrow-up"></i>
        </button>
</body>

</html>