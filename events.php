<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WMSU i - Elect | Results</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">


    <link href="
https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css
" rel="stylesheet">

    <link href="external/css/styles.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
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

    <div class="container-fluid first-part mt-5">
        <div class="container">
            <div class=" d-flex justify-content-center align-items-center flex-lg-column">
                <h1 class="bold c-navy">Events</h1>

                <p class="small-liner"></p>
                <p>Below are the events that are happening around Western Mindanao State University that concerns
                    voting, elections and canditorial matters.
                </p>
                <div class="container mt-5">
                    <div class="row" id="events-list">
                        <!-- Events will be dynamically loaded here -->
                    </div>
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

         <style>
                                .event-img {
    width: 100%;          /* Full width of the card */
    height: 200px;        /* Fixed height */
    object-fit: cover;    /* Crop image to fit without stretching */
    object-position: center; /* Center the crop */
}
                            </style>
                            
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        fetch("processes/events/fetch_events.php")
            .then(response => response.json())
            .then(data => {
                console.log(data); // Check the response structure
                let eventsContainer = document.getElementById("events-list");
                if (Array.isArray(data.events)) {
                    data.events.forEach(event => {
                        let registrationStart = new Date(event.registration_start);
                        let registrationDeadline = new Date(event.registration_deadline);
                        let currentDate = new Date();

                        let statusText = "";
                        let statusClass = "text-secondary";


                        const coverImage = event.cover_image && event.cover_image !== ''
        ? `uploads/event_covers/${encodeURIComponent(event.cover_image)}`
        : 'uploads/placeholder/ph.jpg';
        
                        let eventCard = `
                            <div class="col-md-4 mb-4">
                                <div class="card article-card">
                                    <img src="${coverImage}" class="card-img-top event-img" alt="Event Cover">
                                    <div class="card-body">
                                        <h5 class="card-title">${event.event_title}</h5>
                                        <p class="${statusClass} fw-bold">${statusText}</p>
                                        <a href="view_events.php?event_id=${event.id}" class="btn btn-primary-self">View Details</a>
                                        ${
                                            (event.registration_enabled == 1 || event.registration_enabled === true || event.registration_enabled === "1")
                                          
                                            ? `<a href="login/candidacy.php?event_id=${event.id}" class="btn btn-success-self">File for Candidacy</a>`
                                            : ''
                                        }
                                    </div>
                                </div>
                            </div>`;

                        eventsContainer.innerHTML += eventCard;
                    });
                } else {
                    console.error('Events are not an array:', data.events);
                }
            });
    });
</script>


</body>

</html>