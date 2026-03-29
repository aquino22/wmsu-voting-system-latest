<?php    // Database connection
session_start();
require_once '../includes/conn.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU I-Elect - QR Scanner</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="js/select.dataTables.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="images/favicon.png" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Custom styles */
        .card {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .qr-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        #reader {
            width: 100% !important;
            max-width: 1000px;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #e0e0e0;
            margin-bottom: 20px;
        }
        
        .scan-guide {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .countdown-container {
            background-color: #fff;
            padding: 10px 20px;
            border-radius: 50px;
            border: 1px solid #dee2e6;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .timer-input {
            border: none;
            background: transparent;
            font-weight: bold;
            color: #B22222;
            font-size: 1.1rem;
            width: 100px;
            text-align: center;
        }
        
        .election-status {
            background: linear-gradient(45deg, #B22222, #ff5a5f);
            color: white;
            padding: 4px 15px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-left: 15px;
        }
        
        .scanner-header {
            position: relative;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .scanner-header h2 {
            position: relative;
            display: inline-block;
            padding-bottom: 10px;
        }
        
        .scanner-header h2:after {
            content: '';
            position: absolute;
            left: 25%;
            bottom: 0;
            width: 50%;
            height: 3px;
            background: linear-gradient(to right, #B22222, transparent);
            border-radius: 2px;
        }
        
        .result-badge {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-block;
            border-left: 4px solid #B22222;
            font-weight: 500;
        }
        
        .scan-animation {
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, transparent, #B22222, transparent);
            position: absolute;
            top: 0;
            left: 0;
            animation: scanMove 2s infinite;
            opacity: 0.7;
            border-radius: 2px;
        }
        
        @keyframes scanMove {
            0% { transform: translateY(0); }
            50% { transform: translateY(300px); }
            100% { transform: translateY(0); }
        }
        
        /* Enhanced navigation styling */
        .navbar {
            background: linear-gradient(to right, #950000, #B22222);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .navbar .navbar-brand {
            color: white;
        }
        
        .sidebar .nav .nav-item.active-link > .nav-link {
            background-color: #B22222 !important;
            color: white !important;
        }
        
        .sidebar .nav .nav-item > .nav-link:hover {
            background-color: rgba(178, 34, 34, 0.1);
        }
        
        .active-menu-link {
            background-color: #B22222 !important;
            color: white !important;
        }
        
        /* Pulse effect for the active scanner */
        .pulse {
            position: relative;
        }
        
        .pulse:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 15px;
            box-shadow: 0 0 0 0 rgba(178, 34, 34, 0.7);
            animation: pulse 2s infinite;
            z-index: -1;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(178, 34, 34, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(178, 34, 34, 0); }
            100% { box-shadow: 0 0 0 0 rgba(178, 34, 34, 0); }
        }
        
    </style>
</head>

<body>
    <div class="container-scroller">
        <!-- partial:partials/_navbar.html -->
        <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
                <div class="me-3">
                    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
                        <span class="icon-menu"></span>
                    </button>
                </div>
                <div>
                    <a class="navbar-brand brand-logo" href="index.html">
                        <img src="images/wmsu-logo.png" alt="logo" class="logo img-fluid" />
                        <small style="font-size: 16px; color: white;"><b>WMSU I-Elect</b></small>
                    </a>
                    <a class="navbar-brand brand-logo-mini" href="index.html">
                        <img src="images/wmsu-logo.png" class="logo img-fluid" alt="logo" />
                    </a>
                </div>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-top">
                <ul class="navbar-nav">
                    <li class="nav-item font-weight-semibold d-none d-lg-block ms-0">
                        <h1 class="welcome-text">Welcome, <span class="text-white fw-bold">WMSU Moderator</span></h1>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                        <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <img class="img-xs rounded-circle" src="images/wmsu-logo.png" style="background-color: white;" alt="Profile image">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                            <div class="dropdown-header text-center">
                                <img class="img-md rounded-circle" src="images/faces/face8.jpg" alt="Profile image">
                                <p class="mb-1 mt-3 font-weight-semibold">Moderator</p>
                                <p class="fw-light text-muted mb-0">Moderator@gmail.com</p>
                            </div>
                            <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-account-outline text-primary me-2"></i>My Profile</a>
                            <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-help-circle-outline text-primary me-2"></i>FAQ</a>
                            <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign Out</a>
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
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="mdi mdi-grid-large menu-icon"></i>
                            <span class="menu-title">Dashboard</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="voter-list.php">
                            <i class="menu-icon mdi mdi-account-group"></i>
                            <span class="menu-title">Voter List</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active-menu-link" href="vote_qr_code.php">
                            <i class="menu-icon mdi mdi-qrcode-scan" style="color: white !important;"></i>
                            <span class="menu-title" style="color: white !important;">QR Code Scanning</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <?php
            $user_id = $_SESSION['user_id']; // Get logged-in user ID
            $current_datetime = date('Y-m-d H:i:s'); // Get current datetime
            $email = $_SESSION['email'];

            // Step 1: Get staff_id and precinct from 'moderators' where staff_id matches user_id
            $stmt = $pdo->prepare("SELECT precinct, email FROM moderators WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $moderator = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($moderator) {
                $precinct = $moderator['precinct'];

                // Step 2: Get precinct data from 'precincts' table
                $stmt = $pdo->prepare("SELECT id, name FROM precincts WHERE name = :precinct");
                $stmt->execute(['precinct' => $precinct]);
                $precinctData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($precinctData) {
                    $precinct_name = $precinctData['name'];

                    // Step 3: Get all election names for the precinct from 'precinct_elections'
                    $stmt = $pdo->prepare("SELECT election_name FROM precinct_elections WHERE precinct_name = :precinct_name");
                    $stmt->execute(['precinct_name' => $precinct_name]);
                    $electionNames = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($electionNames) {
                        $electionNamesArray = array_column($electionNames, 'election_name'); // Convert result to an array of election names

                        // Step 4: Check each election name in the 'elections' table for its status, start, and end period
                        $stmt = $pdo->prepare("
                            SELECT election_name, start_period, end_period, status
                            FROM elections
                            WHERE election_name IN (" . implode(",", array_fill(0, count($electionNamesArray), "?")) . ")
                            ORDER BY start_period ASC
                        ");
                        $stmt->execute($electionNamesArray);
                        $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Step 5: Check if any ongoing election exists
                        $ongoingElectionFound = false;

                        foreach ($elections as $election) {
                            $election_name = $election['election_name'];
                            $start_period = $election['start_period'];
                            $end_period = $election['end_period'];
                            $status = $election['status'];

                            // Check if the election is ongoing
                            if ($status === "Ongoing" && $current_datetime >= $start_period && $current_datetime <= $end_period) {
                                try {
                                    // Fetch the latest or ongoing election
                                    $stmt = $pdo->prepare("
                                        SELECT election_name, semester, start_period, end_period, status 
                                        FROM elections 
                                        WHERE election_name = :election_name 
                                        ORDER BY start_period DESC 
                                        LIMIT 1
                                    ");
                                    $stmt->execute(['election_name' => $election_name]);
                                    $election = $stmt->fetch(PDO::FETCH_ASSOC);

                                    if ($election) {
                                        $semester = $election['semester'];
                                        $start_period = date('m/d/Y h:i A', strtotime($election['start_period']));
                                        $end_period = date('m/d/Y h:i A', strtotime($election['end_period']));
                                        $status = $election['status'];

                                        // Fetch event details using the election_name (candidacy field in events)
                                        $stmt = $pdo->prepare("
                                            SELECT id, event_title 
                                            FROM events 
                                            WHERE candidacy = :election_name 
                                            LIMIT 1
                                        ");
                                        $stmt->execute(['election_name' => $election_name]);
                                        $event = $stmt->fetch(PDO::FETCH_ASSOC);

                                        if ($event) {
                                            $event_id = $event['id'];
                                            $event_title = $event['event_title'];

                                            $stmt = $pdo->prepare("
                                                SELECT COUNT(*) AS candidate_count 
                                                FROM candidates c
                                                JOIN registration_forms rf ON c.form_id = rf.id
                                                JOIN events e ON rf.election_name = e.candidacy
                                                WHERE e.id = :event_id 
                                                AND rf.status = 'active' 
                                                AND c.status = 'accept'
                                            ");

                                            $stmt->execute(['event_id' => $event_id]);
                                            $candidateCount = $stmt->fetch(PDO::FETCH_ASSOC)['candidate_count'] ?? 0;
                                        } else {
                                            $event_id = "-";
                                            $event_title = "No Event Found";
                                            $candidateCount = 0;
                                        }

                                        // Get the number of parties under this election
                                        $stmt = $pdo->prepare("SELECT COUNT(*) AS party_count FROM parties WHERE election_name = :election_name");
                                        $stmt->execute(['election_name' => $election_name]);
                                        $partyCount = $stmt->fetch(PDO::FETCH_ASSOC)['party_count'] ?? 0;
                                    } else {
                                        // Default values if no election found
                                        $election_name = "No Active Election";
                                        $semester = "-";
                                        $start_period = "-";
                                        $end_period = "-";
                                        $status = "Inactive";
                                        $partyCount = 0;
                                        $candidateCount = 0;
                                        $event_id = "-";
                                        $event_title = "No Event Found";
                                    }
                                } catch (PDOException $e) {
                                    die("Database error: " . $e->getMessage());
                                }
                            }
                        }
                    }
                }
            }
            ?>

            <!-- partial -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">QR Scanner</a>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                                        <div class="row">
                                            <div class="col-lg mx-auto">
                                                <div class="card card-rounded pulse">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-center justify-content-between mb-4">
                                                            <div>
                                                                <h2 class="card-title card-title-dash">
                                                                    <?php echo $election_name; ?>
                                                                    <span class="election-status">Ongoing</span>
                                                                </h2>
                                                                <p class="text-muted">Precinct: <?php echo $precinct_name; ?></p>
                                                            </div>
                                                            <div class="d-flex align-items-center">
                                                                <div class="countdown-container me-3">
                                                                    <i class="mdi mdi-clock-outline me-2" style="color: #B22222;"></i>
                                                                    <input type="text" class="timer-input" id="secondsTimer" value="0:13:02" readonly>
                                                                </div>
                                                                <div class="countdown-container">
                                                                    <i class="mdi mdi-calendar me-2" style="color: #666;"></i>
                                                                    <input type="text" class="timer-input" style="color: #666;" id="DateEnding" value="<?php echo date('F d, Y'); ?>" readonly>
                                                                </div>
                                                            </div>
                                                        </div>
                                    <style>
                                        .qr-container {
                                            display: flex;
                                            flex-direction: column;
                                            align-items: center;
                                            padding: 20px;
                                            max-width: 600px;
                                            margin: 0 auto;
                                        }
                                        
                                        #reader {
                                            width: 100% !important;
                                            max-width: 500px;
                                            border-radius: 12px;
                                            overflow: hidden;
                                            border: 2px solid #e0e0e0;
                                            margin-bottom: 10px;
                                            position: relative;
                                        }
                                        
                                        #reader video {
                                            width: 100% !important;
                                            height: 300px !important;
                                            object-fit: cover;
                                            border-radius: 10px;
                                        }
                                        
                                        @media (max-width: 768px) {
                                            #reader {
                                                max-width: 300px;
                                            }
                                            #reader video {
                                                height: 250px !important;
                                            }
                                        }
                                        
                                        @media (max-width: 480px) {
                                            #reader {
                                                max-width: 250px;
                                            }
                                            #reader video {
                                                height: 200px !important;
                                            }
                                        }
                                        
                                        .scan-guide {
                                            background: linear-gradient(to right, #f8f9fa, #e9ecef);
                                            padding: 15px;
                                            border-radius: 10px;
                                            margin: 15px 0;
                                            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                                        }
                                        .pad{
                                            width: auto;
                                            margin: auto;
                                            padding: 20px;
                                        }
                                     
                                    </style>
                                                        <div class="qr-container">
                                                            <div class="scanner-header">
                                                                <h2><i class="mdi mdi-qrcode-scan me-2"></i>Scan Student QR Code</h2>
                                                            </div>
                                                            
                                                            <div class="position-relative mb-4">
                                                                <div class="pad">
                                                                <div id="reader"></div>
                                                                <div class="scan-animation"></div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="scan-guide">
                                                                <h5 class="mb-2"><i class="mdi text-center mdi-information-outline me-2 "></i>Scanning Instructions</h5>
                                                                <ul class="mb-0">
                                                                    <li>Position the student ID QR code within the scanning frame</li>
                                                                    <li>Hold steady until verification is complete</li>
                                                                    <li>Make sure lighting is adequate for better scanning</li>
                                                                </ul>
                                                            </div>
                                                            
                                                            <div class="text-center mt-3">
                                                            <?php
                                                  
                                                  require_once '../includes/conn.php';

                                                  // Fetch the active voting period
                                                  $stmt = $pdo->prepare("SELECT id FROM voting_periods WHERE status = 'Ongoing' LIMIT 1");
                                                  $stmt->execute();
                                                  $voting_period = $stmt->fetch(PDO::FETCH_ASSOC);
                                                  $voting_period_id = $voting_period ? $voting_period['id'] : 0;

                                                  if (!$voting_period_id) {
                                                      die("No active voting period found.");
                                                  }
                                                  ?>

                                                  <div class="container-fluid text-center">
                                                      <h2>Scan QR Code</h2>
                                                      <div id="reader"></div>
                                                      <p>Scanned Result: <span id="qrResult"></span></p>
                                        <!-- Hidden form to submit POST data -->
                                                      <form id="redirectForm" action="vote.php" method="POST" style="display: none;">
                                                          <input type="hidden" name="voting_period_id" id="formVotingPeriodId">
                                                          <input type="hidden" name="student_id" id="formStudentId">
                                                      </form>

                                                      <script src="https://unpkg.com/html5-qrcode/html5-qrcode.min.js"></script>
                                                      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                                      <script>
                                                          // Store the voting_period_id from PHP into a JavaScript variable
                                                          const votingPeriodId = <?php echo $voting_period_id; ?>;
                                                          let isProcessing = false; // Flag to prevent multiple scans of the same QR code

                                                          function onScanSuccess(decodedText, decodedResult) {
                                                              if (isProcessing) return; // Exit if already processing a scan
                                                              isProcessing = true; // Set flag to true to prevent duplicate processing

                                                              console.log("Scanned QR Code:", decodedText); // Debugging: Log the scanned data

                                                              document.getElementById("qrResult").innerText = decodedText;

                                                              // Show a loading message while processing
                                                              Swal.fire({
                                                                  title: 'Verifying QR Code...',
                                                                  text: 'Please wait.',
                                                                  allowOutsideClick: false,
                                                                  didOpen: () => {
                                                                      Swal.showLoading();
                                                                  }
                                                              });

                                                              // Send QR Code data to PHP via AJAX
                                                              fetch("process_qr.php", {
                                                                  method: "POST",
                                                                  headers: {
                                                                      "Content-Type": "application/x-www-form-urlencoded"
                                                                  },
                                                                  body: "qrData=" + encodeURIComponent(decodedText) + "&voting_period_id=" + encodeURIComponent(votingPeriodId)
                                                              })
                                                              .then(response => response.json())
                                                              .then(data => {
                                                                  Swal.close(); // Close the loading dialog

                                                                  if (data.status === "success") {
                                                                      // Success message with redirect
                                                                      Swal.fire({
                                                                          icon: 'success',
                                                                          title: 'QR Code Verified',
                                                                          text: data.message,
                                                                          timer: 1500, // Auto-close after 1.5 seconds
                                                                          showConfirmButton: false
                                                                      }).then(() => {
                                                                          // Populate the hidden form and submit it
                                                                          document.getElementById("formVotingPeriodId").value = votingPeriodId;
                                                                          document.getElementById("formStudentId").value = decodedText;
                                                                          document.getElementById("redirectForm").submit();
                                                                      });
                                                                  } else {
                                                                      // Error message
                                                                      Swal.fire({
                                                                          icon: 'error',
                                                                          title: 'Verification Failed',
                                                                          text: data.message || 'Invalid QR code or server error.',
                                                                          confirmButtonText: 'OK'
                                                                      });
                                                                  }
                                                                  isProcessing = false; // Reset the flag after processing is complete
                                                              })
                                                              .catch(error => {
                                                                  Swal.close(); // Close loading dialog on error
                                                                  console.error("Error:", error);
                                                                  Swal.fire({
                                                                      icon: 'error',
                                                                      title: 'Request Error',
                                                                      text: 'An error occurred while processing your request.',
                                                                      confirmButtonText: 'OK'
                                                                  });
                                                                  isProcessing = false; // Reset the flag after processing is complete
                                                              });
                                                          }

                                                          function onScanFailure(error) {
                                                              console.warn(`Scan failed: ${error}`);
                                                              // Only show the error message if the error is not related to camera access
                                                              if (!error.message.includes("Camera access denied")) {
                                                                  Swal.fire({
                                                                      icon: 'warning',
                                                                      title: 'Scan Failed',
                                                                      text: 'Unable to scan QR code. Please ensure the QR code is clear and try again.',
                                                                      confirmButtonText: 'OK'
                                                                  });
                                                              }
                                                          }

                                                          let html5QrcodeScanner = new Html5QrcodeScanner("reader", {
                                                              fps: 10,
                                                              qrbox: 250
                                                          });

                                                          html5QrcodeScanner.render(onScanSuccess, onScanFailure);
                                                      </script>
                                                            </div>
                                                        </div>

                                                        <!-- Hidden form to submit POST data -->
                                                        <form id="redirectForm" action="vote.php" method="POST" style="display: none;">
                                                            <input type="hidden" name="voting_period_id" id="formVotingPeriodId">
                                                            <input type="hidden" name="student_id" id="formStudentId">
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
                </div>
                <!-- content-wrapper ends -->
            </div>
            <!-- main-panel ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->

    <?php
    require_once '../includes/conn.php';

    // Fetch the active voting period
    $stmt = $pdo->prepare("SELECT id FROM voting_periods WHERE status = 'Ongoing' LIMIT 1");
    $stmt->execute();
    $voting_period = $stmt->fetch(PDO::FETCH_ASSOC);
    $voting_period_id = $voting_period ? $voting_period['id'] : 0;

    if (!$voting_period_id) {
        die("No active voting period found.");
    }
    ?>

    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
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
    <!-- Custom js for this page-->
    <script src="js/dashboard.js"></script>

    <script>
        // Store the voting_period_id from PHP into a JavaScript variable
        const votingPeriodId = <?php echo $voting_period_id; ?>;

        function onScanSuccess(decodedText, decodedResult) {
            document.getElementById("qrResult").innerText = decodedText;

            // Show a loading message while processing
            Swal.fire({
                title: 'Verifying QR Code...',
                text: 'Please wait.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send QR Code data to PHP via AJAX
            fetch("process_qr.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "qrData=" + encodeURIComponent(decodedText) + "&voting_period_id=" + encodeURIComponent(votingPeriodId)
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close(); // Close the loading dialog

                    if (data.status === "success") {
                        // Success message with redirect
                        Swal.fire({
                            icon: 'success',
                            title: 'QR Code Verified!',
                            text: data.message,
                            background: '#f8f9fa',
                            iconColor: '#28a745',
                            confirmButtonColor: '#B22222',
                            timer: 1500, // Auto-close after 1.5 seconds
                            showConfirmButton: false
                        }).then(() => {
                            // Populate the hidden form and submit it
                            document.getElementById("formVotingPeriodId").value = votingPeriodId;
                            document.getElementById("formStudentId").value = decodedText;
                            document.getElementById("redirectForm").submit();
                        });
                    } else {
                        // Error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Verification Failed',
                            text: data.message,
                            background: '#f8f9fa',
                            iconColor: '#dc3545',
                            confirmButtonColor: '#B22222'
                        });
                    }
                })
                .catch(error => {
                    Swal.close(); // Close loading dialog on error
                    console.error("Error:", error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Error',
                        text: 'An error occurred while processing your request.',
                        confirmButtonColor: '#B22222'
                    });
                });
        }

        function onScanFailure(error) {
            console.warn(`Scan failed: ${error}`);
        }


        // Countdown timer functionality 
        function startTimer() {
            const timerDisplay = document.getElementById('secondsTimer');
            const parts = timerDisplay.value.split(':');
            let hours = 0;
            let minutes = parseInt(parts[0]);
            let seconds = parseInt(parts[1]);
            
            const totalSeconds = minutes * 60 + seconds;
            let remainingSeconds = totalSeconds;
            
            const timer = setInterval(() => {
                remainingSeconds--;
                
                if (remainingSeconds <= 0) {
                    clearInterval(timer);
                    timerDisplay.value = "Time's Up!";
                    timerDisplay.style.color = "#dc3545";
                    
                    Swal.fire({
                        icon: 'warning',
                        title: 'Voting Period Ended',
                        text: 'The voting session has concluded.',
                        confirmButtonColor: '#B22222'
                    });
                    
                    return;
                }
                
                const minutesLeft = Math.floor(remainingSeconds / 60);
                const secondsLeft = remainingSeconds % 60;
                
                timerDisplay.value = `${minutesLeft}:${secondsLeft < 10 ? '0' : ''}${secondsLeft}`;
            }, 1000);
        }
        
        // Start the countdown when the page loads
        document.addEventListener('DOMContentLoaded', startTimer);
    </script>
</body>
</html>