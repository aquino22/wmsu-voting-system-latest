<?php
date_default_timezone_set('Asia/Manila');
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
include('includes/conn.php');

// Check for ongoing election
$hasOngoingElection = false;

$query = "SELECT COUNT(*) FROM elections WHERE status = 'ongoing'";
$stmt = $pdo->prepare($query);
$stmt->execute();
$count = $stmt->fetchColumn(); // This fetches the first column of the first row

$hasOngoingElection = ($count > 0);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <title>WMSU (I-Elect) Voting System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--===============================================================================================-->
    <link rel="icon" type="image/png" href="images/favicon-32x32.png" />
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <!--===============================================================================================-->

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="fonts/iconic/css/material-design-iconic-font.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="vendor/animate/animate.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="vendor/animsition/css/animsition.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="vendor/select2/select2.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="vendor/daterangepicker/daterangepicker.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <!--===============================================================================================-->
</head>




<body>

    <div class="limiter">
        <div class="container-login100"
            style="background-image: url('../external/img/Peach\ Ash\ Grey\ Gradient\ Color\ and\ Style\ Video\ Background.png');">

        </div>

      
    </div>
    
    </div>
    </div>



    <div id="dropDownSelect1"></div>

    <!--===============================================================================================-->
    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <!--===============================================================================================-->
    <script src="vendor/animsition/js/animsition.min.js"></script>
    <!--===============================================================================================-->
    <script src="vendor/bootstrap/js/popper.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <!--===============================================================================================-->
    <script src="vendor/select2/select2.min.js"></script>
    <!--===============================================================================================-->
    <script src="vendor/daterangepicker/moment.min.js"></script>
    <script src="vendor/daterangepicker/daterangepicker.js"></script>
    <!--===============================================================================================-->
    <script src="vendor/countdowntime/countdowntime.js"></script>
    <!--===============================================================================================-->
    <script src="js/main.js"></script>

    <script>
     

        <?php
        // Function to reset capacity for a new day
        function resetCapacity($pdo)
        {
            // Get the current date
            $currentDate = date('Y-m-d');

            // Query to check if any records have a date_added from a previous day
            $sql = "SELECT id, DATE(date_added) AS added_date FROM email WHERE DATE(date_added) < :currentDate";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['currentDate' => $currentDate]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // If there are records with an older date, reset their capacity
            if (!empty($records)) {
                // Update capacity to 0 (or your desired default value)
                $updateSql = "UPDATE email SET capacity = 0 WHERE DATE(date_added) < :currentDate";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute(['currentDate' => $currentDate]);

                // Optionally, update date_added to the current date
                $updateDateSql = "UPDATE email SET date_added = NOW() WHERE DATE(date_added) < :currentDate";
                $updateDateStmt = $pdo->prepare($updateDateSql);
                $updateDateStmt->execute(['currentDate' => $currentDate]);
            } else {
            }
        }

        // Run the reset function
        resetCapacity($pdo);
        ?>