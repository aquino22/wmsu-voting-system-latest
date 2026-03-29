<?php
session_start();
include('includes/conn.php');
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WMSU i-Elect | How to Vote</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css" rel="stylesheet">
    <link href="external/css/styles.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
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
                            <li><a class="dropdown-item" href="#">About Us</a></li>
                            <li><a class="dropdown-item" href="#">About the System</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php" role="button">
                            Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="parties.php" role="button">
                            Parties
                        </a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a class="nav-link" href="login/index.php">
                        <i class="bi bi-person-circle"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid first-part mt-5">
        <div class="container" style="border: 1px solid black; padding: 25px; border-radius: 10px;">
            <a href="index.php" style="text-decoration: none;"><i class="bi bi-arrow-left"></i> Go back</a>
            <h4 class="mt-3">How to Vote in WMSU i-Elect</h4>
            <div class="small-liner mt-2 mb-4"></div>
            <p class="lead">This guide explains how to cast your vote in a WMSU student election. You’ll need to validate your eligibility using your Certificate of Registration (COR), scan your student ID at a precinct, and cast your vote.</p>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Option 1: Online COR Validation and In-Person Voting</h5>
                    <p>If you validate your COR online, you can proceed to a precinct to vote. No login is required for validation.</p>
                    <ol class="list-group list-group-numbered">
                        <li class="list-group-item">
                            <strong>Visit the WMSU i-Elect Website</strong>
                            <p>Go to the WMSU i-Elect homepage and find the “Validate COR” or “Voter Registration” link, typically on the homepage or Events page.</p>
                          
                        </li>
                        <li class="list-group-item">
                            <strong>Upload Your COR</strong>
                            <p>Upload a clear image of your Certificate of Registration (JPEG or PNG, max 2MB). Enter your student ID or name to link the COR to your identity.</p>
                           
                        </li>
                        <li class="list-group-item">
                            <strong>Receive Validation Confirmation</strong>
                            <p>After submitting, you’ll receive a confirmation (e.g., a voting code or email) indicating your eligibility has been validated. Save this confirmation.</p>
                        </li>
                        <li class="list-group-item">
                            <strong>Visit a Voting Precinct</strong>
                            <p>Go to a designated precinct on campus (e.g., College of Engineering, Library) during voting hours (e.g., 8:00 AM–5:00 PM, as announced).</p>
                        </li>
                        <li class="list-group-item">
                            <strong>Scan Your Student ID</strong>
                            <p>Present your student ID to the election moderator (a device or staff-managed system). The moderator will scan your ID to verify your validated status.</p>
                        
                        </li>
                        <li class="list-group-item">
                            <strong>Cast Your Vote</strong>
                            <p>Use the voting system (e.g., a touchscreen or computer) to select your candidates for each position (e.g., President, Vice President). Review your choices and submit your vote.</p>
                           
                        </li>
                        <li class="list-group-item">
                            <strong>Keep Your Voting Confirmation</strong>
                            <p>Receive a confirmation (e.g., a digital receipt or code) from the voting system. Save this to verify your vote if needed.</p>
                        </li>
                    </ol>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Option 2: In-Person COR Validation and Voting</h5>
                    <p>If you cannot validate your COR online, you can do everything at a voting precinct.</p>
                    <ol class="list-group list-group-numbered">
                        <li class="list-group-item">
                            <strong>Visit a Voting Precinct</strong>
                            <p>Go to a designated precinct on campus (e.g., College of Engineering, Library) during voting hours (e.g., 8:00 AM–5:00 PM, as announced).</p>
                        </li>
                        <li class="list-group-item">
                            <strong>Present Your COR</strong>
                            <p>Show your physical Certificate of Registration to the election officer. They will verify your eligibility to vote.</p>
                        </li>
                        <li class="list-group-item">
                            <strong>Scan Your Student ID</strong>
                            <p>Present your student ID to the moderator (a device or staff-managed system). The moderator will scan your ID to confirm your identity and record your vote eligibility.</p>
                        </li>
                        <li class="list-group-item">
                            <strong>Cast Your Vote</strong>
                            <p>Use the voting system (e.g., a touchscreen, computer, or paper ballot) to select your candidates for each position. If using a paper ballot, mark your choices and place it in the ballot box.</p>
                        </li>
                        <li class="list-group-item">
                            <strong>Receive Confirmation</strong>
                            <p>Receive a confirmation (e.g., a stamp on a voter slip, verbal acknowledgment, or digital receipt). Keep this for your records.</p>
                        </li>
                    </ol>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Tips for a Successful Voting Experience</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Bring your COR and student ID to the precinct to avoid delays.</li>
                        <li class="list-group-item">Check the voting schedule and precinct locations in advance.</li>
                        <li class="list-group-item">Review candidate profiles on the Parties page before voting.</li>
                        <li class="list-group-item">For online COR validation, use a clear, readable image of your COR.</li>
                        <li class="list-group-item">Arrive early at the precinct to avoid long queues.</li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Need Help?</h5>
                    <p>Contact the WMSU Election Commission for assistance:</p>
                    <ul>
                        <li><strong>Email</strong>: election@wmsu.edu.ph</li>
                        <li><strong>Office</strong>: WMSU Election Commission, Main Campus, Zamboanga City</li>
                        <li><strong>Hours</strong>: Monday–Friday, 8:00 AM–5:00 PM</li>
                        <li><strong>Website</strong>: <a href="index.php">WMSU i-Elect</a></li>
                    </ul>
                </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>