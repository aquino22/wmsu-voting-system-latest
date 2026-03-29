<?php
session_start();
include('includes/conn.php');
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WMSU i-Elect | How to File for Candidacy</title>
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
            <h4 class="mt-3">How to File for Candidacy in WMSU i-Elect</h4>
            <div class="small-liner mt-2 mb-4"></div>
            <p class="lead">This guide explains how to file your candidacy for a student election using the WMSU i-Elect system, either online or by submitting requirements in person at the Election Commission office.</p>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Option 1: File Candidacy Online</h5>
                    <p>Follow these steps to submit your candidacy through the WMSU i-Elect website. No login is required.</p>
                    <ol class="list-group list-group-numbered">
                        <li class="list-group-item">
                            <strong>Visit the WMSU i-Elect Website</strong>
                            <p>Go to the WMSU i-Elect homepage and locate the “File for Candidacy” link or button, typically found in the navigation menu or on the Events page.</p>
                         
                        </li>
                        <li class="list-group-item">
                            <strong>Select Your Election</strong>
                            <p>Choose the election you’re running for (e.g., USC Election, LSC Election) from a dropdown menu. This determines the available positions and parties.</p>
                        </li>
                        <li class="list-group-item">
                            <strong>Enter Your Details</strong>
                            <p>Fill out the candidacy form with the following information:</p>
                            <ul>
                                <li><strong>Full Name</strong>: Enter your complete name as shown on your student ID.</li>
                                <li><strong>Position</strong>: Select your desired position (e.g., President, Vice President) from a dropdown list.</li>
                                <li><strong>Party</strong>: Choose a party (e.g., Unity Party, Progress Party) or select “Independent” if applicable.</li>
                            </ul>
                        </li>
                        <li class="list-group-item">
                            <strong>Upload a Profile Image</strong>
                            <p>Upload a recent, professional photo (JPEG or PNG, max 2MB). This image will appear on the election ballot and party page.</p>
                           
                        </li>
                        <li class="list-group-item">
                            <strong>Review and Submit</strong>
                            <p>Check your details for accuracy, then click “Submit.” You’ll see a confirmation message indicating your candidacy has been received.</p>
                        </li>
                        <li class="list-group-item">
                            <strong>Wait for Approval</strong>
                            <p>Your candidacy will be reviewed by the Election Commission. You’ll be notified via email or by checking the website for updates on your application status.</p>
                        </li>
                    </ol>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Option 2: Submit Candidacy In Person</h5>
                    <p>If you cannot use the website, you can file your candidacy by submitting physical documents to the WMSU Election Commission office.</p>
                    <ol class="list-group list-group-numbered">
                        <li class="list-group-item">
                            <strong>Obtain the Candidacy Form</strong>
                            <p>Download the candidacy form from the WMSU i-Elect website (look for a “Download Form” link) or pick up a printed copy at the Election Commission office.</p>
                        </li>
                        <li class="list-group-item">
                            <strong>Complete the Form</strong>
                            <p>Fill out the form with the following details:</p>
                            <ul>
                                <li><strong>Full Name</strong>: Write your complete name as shown on your student ID.</li>
                                <li><strong>Election</strong>: Specify the election (e.g., USC Election, LSC Election).</li>
                                <li><strong>Position</strong>: Indicate your desired position (e.g., President, Vice President).</li>
                                <li><strong>Party</strong>: Write the party name (e.g., Unity Party) or “Independent” if applicable.</li>
                            </ul>
                        </li>
                        <li class="list-group-item">
                            <strong>Prepare Required Documents</strong>
                            <p>Gather the following:</p>
                            <ul>
                                <li>Completed candidacy form.</li>
                                <li>One 2x2 or passport-size photo (recent, professional).</li>
                                <li>Photocopy of your student ID (optional, check with the office).</li>
                            </ul>
                        </li>
                        <li class="list-group-item">
                            <strong>Submit to the Election Commission</strong>
                            <p>Bring your documents to the WMSU Election Commission office during office hours (Monday–Friday, 8:00 AM–5:00 PM). Submit them to the designated election officer.</p>
                        </li>
                        <li class="list-group-item">
                            <strong>Receive Confirmation</strong>
                            <p>The office will provide a receipt or acknowledgment of your submission. Keep this for your records.</p>
                        </li>
                        <li class="list-group-item">
                            <strong>Wait for Approval</strong>
                            <p>Your candidacy will be reviewed, and you’ll be notified via email or through office announcements about your application status.</p>
                        </li>
                    </ol>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Tips for a Successful Application</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Verify election deadlines to ensure timely submission.</li>
                        <li class="list-group-item">Use a clear, professional photo for your profile (online or offline).</li>
                        <li class="list-group-item">Double-check your form details for accuracy before submitting.</li>
                        <li class="list-group-item">For offline submissions, visit the office early to avoid long queues.</li>
                        <li class="list-group-item">Keep a copy of all submitted documents for your records.</li>
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