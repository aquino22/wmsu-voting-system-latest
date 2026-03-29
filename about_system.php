<?php
session_start();
date_default_timezone_set('Asia/Manila');
include('includes/conn.php');
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>About the System | WMSU I-Elect Voting System</title>
    <link rel="icon" type="image/png" href="external/img/favicon-32x32.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css" rel="stylesheet">
    <link href="external/css/styles.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- Navbar (Reused from your code) -->
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="external/img/wmsu-logo.png" class="img-fluid logo">
                WMSU i - Elect |</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            About
                        </a>
                        <ul class="dropdown-menu">

                            <li><a class="dropdown-item" href="about_us.php">About
                                    Us</a></li>

                            <li><a class="dropdown-item" href="about_system.php">About
                                    the System</a></li>
                        </ul>
                    </li>


                    <li class="nav-item ">
                        <a class="nav-link" href="events.php" role="button">
                            Events
                        </a>

                    </li>

                    <li class="nav-item ">
                        <a class="nav-link" href="parties.php" role="button">
                            Parties
                        </a>

                    </li>

                </ul>
                <div class="d-flex">
                    <a class="nav-link" href="login/index.php">
                        <i class="bi bi-person-circle"></i>
                    </a>
                    </li>
                </div>
            </div>
        </div>
    </nav>

    <!-- About System Content -->
    <div class="container-fluid first-part mt-5">
        <div class="container">
            <div class="text-center d-flex justify-content-center align-items-center flex-lg-column">
                <h1 class="bold c-navy">About the System</h1>
                <p class="small-liner"></p>
                <p>WMSU I-Elect is a comprehensive voting system designed to streamline the election process at Western Mindanao State University, ensuring efficiency, security, and transparency.</p>
            </div>
            <div class="row d-flex justify-content-center align-items-center flex-lg-row pt-5">
                <div class="col">
                    <img src="external/img/Canva-Philippine-Flag-768x511-1-7aaul0gmvmghwjxynwq2etq0g8ayn7wmjrarfya3fi8.jpeg" class="img-fluid info-g" alt="Philippine Flag">
                    <h3 class="bold pt-5 c-navy">Key Features</h3>
                    <ul class="text-start">
                        <li>Secure candidate registration with QR code integration.</li>
                        <li>Real-time election monitoring and statistics.</li>
                        <li>Dynamic party and candidate management.</li>
                        <li>User-friendly interface for voters and administrators.</li>
                    </ul>
                </div>
                <div class="col">
                    <img src="external/img/wmsu-logo.png" class="img-fluid info-g" alt="WMSU Logo">
                    <h3 class="bold pt-5 c-navy">How It Works</h3>
                    <p>The system uses PHP and PDO for secure database interactions, with Bootstrap and Splide.js for a responsive and interactive experience. It tracks ongoing elections, manages voter data, and ensures tamper-proof voting processes.</p>
                </div>
            </div>
            <div class="text-center mt-5 d-flex justify-content-center align-items-center flex-lg-column">
                <h2 class="bold c-navy">Technical Highlights</h2>
                <p class="small-liner"></p>
                <p>Built with modern web technologies, WMSU I-Elect is scalable and tailored for university elections.</p>
                <ul class="text-start d-inline-block">
                    <li>Time Zone: Asia/Manila for accurate scheduling.</li>
                    <li>Database: MySQL with PDO for secure queries.</li>
                    <li>Frontend: Bootstrap 5 and custom CSS.</li>
                    <li>Interactivity: Splide.js for carousels and SweetAlert2 for notifications.</li>
                </ul>
            </div>
        </div>
    </div>

    <footer>
        <div class="container-fluid">
            <div class="row mt-5">
                <div class="col-md-6">
                    <h1 class="bold c-red"> <img src="external/img/wmsu-logo.png" class="img-fluid logo"> WMSU i-Elect
                    </h1>
                    <p>Your friendly WMSU Voting Web Application</p>
                </div>
                <div class="col">
                    <h5 class="c-red bold">About</h5>
                    <p><a href="about_us.php" class="linker">About Us</a></p>
                    <p><a href="about_system.php" class="linker">About the System</a></p>
                </div>
                <div class="col">
                    <h5 class="c-red bold">Help</h5>
                    <p><a href="login/index.php" class="linker">Login</a></p>
                    <p><a href="file_candidacy.php" class="linker">Filing of Candidacy</a></p>
                    <p><a href="voting.php" class="linker">Voting</a></p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>