<?php
session_start();
include('includes/conn.php');
include('includes/conn_archived.php');

$sql = "
    SELECT *
    FROM elections
    WHERE status = 'Published'
    ORDER BY created_at DESC
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$latestElection = $stmt->fetch(PDO::FETCH_ASSOC);

$showLatestResultsButton = false;
$latestVotingPeriodId = null;
if ($latestElection && isset($pdo_archived)) {
    try {
        $stmt_archived = $pdo_archived->prepare("SELECT voting_period_id FROM archived_elections WHERE election_name = ? LIMIT 1");
        $stmt_archived->execute([$latestElection['election_name']]);
        $latestVotingPeriodId = $stmt_archived->fetchColumn();
        if ($latestVotingPeriodId) {
            $showLatestResultsButton = true;
        }
    } catch (PDOException $e) {
        // Log error or handle case where archived DB is not available
        error_log("Could not check for archived election: " . $e->getMessage());
        $showLatestResultsButton = false;
    }
}
?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WMSU I-Elect Voting System</title>
    <link rel="icon" type="image/png" href="external/img/favicon-32x32.png" />

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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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





    <div class="container-fluid voting-landing">
        <div class="voting-overlay"></div> <!-- Background overlay -->
        <div class="container c-white voting-landing-content">
            <div class="row">
                <div class="col se">
                    <h1>Vote for the best <b><span class="candidate-txt">candidate</span></b> & discover peace</h1>
                    <h5>|
                        <i>
                            "When we vote, we participate in shaping the future of our community and ensuring peace for
                            all."
                        </i>
                    </h5>
                    <a href="login/index.php">
                        <button class="jtc-btn">Join the campaign
                            <span class="check">
                                <i class="bi bi-check-square-fill"></i>
                            </span>
                        </button>
                    </a>
                    <?php if ($showLatestResultsButton): ?>
                        <a href="view_results.php?election=<?php echo htmlspecialchars($latestElection['election_name']); ?>&id=<?php echo $latestVotingPeriodId; ?>&election_id=<?php echo htmlspecialchars($latestElection['id']); ?>">
                            <button class="vr-btn">View Latest Results
                                <span class="check">
                                    <i class="bi bi-check-square-fill"></i>
                                </span>
                            </button>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col">

                </div>

            </div>
        </div>
    </div>

    <?php
    if ($showLatestResultsButton):
    ?>
        <!-- 2. The big full-width Bootstrap banner -->
        <div class="container-fluid bg-success text-white py-5 text-center shadow-lg">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h1 class="display-3 fw-bold">📊 ELECTION RESULTS</h1>
                    <p class="lead mt-3">
                        View the official results of the recent election. See winners, vote counts, and more.
                    </p>
                    <a href="view_results.php?election=<?php echo htmlspecialchars($latestElection['election_name']); ?>&id=<?php echo $latestVotingPeriodId; ?>&election_id=<?php echo htmlspecialchars($latestElection['id']); ?>" class="btn btn-light btn-lg mt-4 px-5 fw-semibold">
                        VIEW RESULTS NOW
                    </a>
                </div>
            </div>
        </div>
    <?php
    endif;
    ?>

    <?php
    $current_date = date('Y-m-d');

    // Fetch the ongoing election
    $sqlElection = " SELECT 
        vp.id AS voting_period_id,
        vp.election_id,
        vp.start_period AS vp_start,
        vp.end_period AS vp_end,
        vp.re_start_period,
        vp.re_end_period,
        vp.status AS vp_status,
        e.election_name
    FROM voting_periods vp
    JOIN elections e ON vp.election_id = e.id
    WHERE vp.status = 'Ongoing'
    ORDER BY vp.start_period ASC";
    $stmtElection = $pdo->prepare($sqlElection);
    $stmtElection->execute();
    $election = $stmtElection->fetch(PDO::FETCH_ASSOC);
    ?>
    <?php if ($election): ?>
        <!-- Voting is Live Section -->
        <div class="container-fluid voting-live-banner text-center py-5"
            style="background-color: #f8d7da; color: #721c24; border-radius: 5px;">
            <h2 class="fw-bold"><?php echo htmlspecialchars($election['election_name']) ?> is now live! Vote Now!</h2>
            <p class="lead">Your voice matters! Cast your vote before the voting period ends.</p>
            <a href="login/index.php" class="btn btn-danger btn-lg">Vote Now</a>
        </div>

        <?php
        // Match precincts with the ongoing election that require a revote
        $sqlPrecincts = "SELECT DISTINCT p.name, p.college, p.department
                     FROM precincts p
                     JOIN precinct_voters pv ON pv.precinct = p.name
                     WHERE p.election = ? AND pv.status = 'revoted'";
        $stmtPrecincts = $pdo->prepare($sqlPrecincts);
        $stmtPrecincts->execute([$election['election_name']]);
        $revotePrecincts = $stmtPrecincts->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php if (!empty($revotePrecincts)): ?>
            <!-- Revote Banner -->
            <div class="container-fluid text-center py-5"
                style="background-color: #fff3cd; color: #856404; border-radius: 5px;">
                <h4 class="fw-bold">A revote is required in the following precincts due to a tie:</h4>
                <ul class="list-unstyled">
                    <?php foreach ($revotePrecincts as $precinct): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($precinct['name']) ?></strong>
                            (<?php echo htmlspecialchars($precinct['college']) ?> - <?php echo htmlspecialchars($precinct['department']) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="login/index.php" class="btn btn-warning btn-lg">Revote Now!</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>


    <?php
    require 'includes/conn.php'; // Include database connection

    // Fetch all events with status 'published' or 'ended'
    $stmt = $pdo->prepare("
    SELECT id, event_title, cover_image, event_details, registration_enabled, status
    FROM events
    WHERE status IN ('published', 'ended')
    ORDER BY created_at DESC
");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($events)) {
    ?>
        <div class="container-fluid event-part">
            <div class="container p-5">
                <div class="text-center d-flex justify-content-center align-items-center flex-lg-column">
                    <h1 class="bold c-white">Events</h1>
                    <p class="small-liner"></p>
                    <p>Below are the events that are happening around Western Mindanao State University that concern
                        voting, elections, and candidatorial matters.
                    </p>
                </div>

                <div class="splide" id="card-slider">
                    <div class="splide__track">
                        <ul class="splide__list" id="event-list">
                            <?php
                            // Fetch the current date
                            $current_date = date('Y-m-d');
                            // Fetch the ongoing election
                            $sqlElection = "SELECT * FROM voting_periods WHERE status = 'Ongoing'";
                            $stmtElection = $pdo->prepare($sqlElection);
                            $stmtElection->execute();
                            $election = $stmtElection->fetch(PDO::FETCH_ASSOC);
                            ?>
                            <style>
                                .event-img {
                                    width: 100%;
                                    /* Full width of the card */
                                    height: 200px;
                                    /* Fixed height */
                                    object-fit: cover;
                                    /* Crop image to fit without stretching */
                                    object-position: center;
                                    /* Center the crop */
                                }
                            </style>
                            <?php
                            foreach ($events as $event) {
                                echo '<li class="splide__slide">';
                                echo '<div class="card">';

                                // Check if cover_image exists and is non-empty
                                $coverImage = !empty($event['cover_image']) && file_exists('uploads/event_covers/' . $event['cover_image'])
                                    ? 'uploads/event_covers/' . htmlspecialchars($event['cover_image'])
                                    : 'uploads/placeholder/ph.jpg'; // Placeholder image

                                // Use a class for consistent sizing
                                echo '<img src="' . $coverImage . '" class="card-img-top event-img" alt="Event Image">';

                                echo '<div class="card-body">';
                                echo '<h5 class="card-title">' . htmlspecialchars($event['event_title']) . '</h5>';
                                echo '<a href="view_events.php?event_id=' . $event['id'] . '" class="btn btn-primary-self m-1">View Event</a>';

                                // Only show the registration button for 'published' events
                                if ($event['status'] === 'published' && $event['registration_enabled'] === 1) {
                                    echo '<a href="login/index.php?require=login" class="btn btn-success-self">File for Candidacy</a>';
                                }

                                echo '</div>';
                                echo '</div>';
                                echo '</li>';
                            }
                            ?>

                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }
    ?>




    <div class="container-fluid first-part mt-5">
        <div class="container">
            <div class="text-center d-flex justify-content-center align-items-center flex-lg-column">
                <h1 class="bold c-navy">Who We Are</h1>
                <p class="small-liner"></p>
                <p>I-Elect is a dynamic platform dedicated to empowering the electoral process through transparency,
                    accessibility, and innovation. Our mission is to enhance democratic participation by leveraging
                    technology to ensure secure and seamless elections.</p>
            </div>
            <div class="row d-flex justify-content-center align-items-center flex-lg-row pt-5">
                <div class="col">
                    <img src="external/img/371327515_775043391085332_3242171381045286946_n (1).jpg"
                        class="img-fluid info-g" alt="Voting Process">
                    <h3 class="bold pt-5 c-navy">Equality and Solidarity</h3>
                    <p>We believe that every student deserves an equal voice in shaping their school environment. Our
                        voting system promotes fairness and inclusivity, ensuring that each vote counts towards a united
                        and progressive student body.</p>
                </div>
                <div class="col">
                    <img src="external/img/Canva-Philippine-Flag-768x511-1-7aaul0gmvmghwjxynwq2etq0g8ayn7wmjrarfya3fi8.jpeg"
                        class="img-fluid info-g" alt="Philippine Flag">
                    <h3 class="bold pt-5 c-navy">Gender and future</h3>
                    <p>Student elections are more than just choosing leaders—they're about fostering responsibility and
                        active participation. By engaging in the voting process, students contribute to a brighter and
                        more democratic future.</p>
                </div>
            </div>
            <div class="text-center mt-5 d-flex justify-content-center align-items-center flex-lg-column">
                <h2 class="bold c-navy">Our Vision & Mission</h2>
                <p class="small-liner"></p>
                <p>To build a future where every citizen has easy access to fair, transparent, and secure elections,
                    promoting democratic integrity.</p>
                <ul class="text-start d-inline-block">
                    <li>Ensure secure and tamper-proof digital voting processes.</li>
                    <li>Educate and encourage citizens to participate in elections.</li>
                    <li>Provide an efficient and accessible voting platform.</li>
                    <li>Collaborate with institutions to enhance electoral security.</li>
                </ul>
            </div>
        </div>
    </div>

    <?php
    date_default_timezone_set('Asia/Manila');
    require 'includes/conn.php';

    $current_date = date('Y-m-d');

    try {
        // 1. Get all ongoing elections
        $sqlElection = "SELECT * FROM elections WHERE status = 'Ongoing'";
        $stmtElection = $pdo->prepare($sqlElection);
        $stmtElection->execute();
        $ongoingElections = $stmtElection->fetchAll(PDO::FETCH_ASSOC);

        $electionsData = [];

        foreach ($ongoingElections as $election) {
            $election_id = $election['id'];
            $election_name = $election['election_name'];

            // 2. Get approved parties for this election
            $sqlParties = "SELECT name, party_image FROM parties WHERE election_id = ? AND status = 'approved'";
            $stmtParties = $pdo->prepare($sqlParties);
            $stmtParties->execute([$election_id]);
            $parties = $stmtParties->fetchAll(PDO::FETCH_ASSOC);

            $partyCandidates = [];

            if (!empty($parties)) {
                foreach ($parties as $party) {
                    $partyName = $party['name'];

                    // 3. Get candidates filtered by election and party
                    $sqlCandidates = "
                SELECT 
                    c.id AS candidate_id, 
                    cr_name.value AS student_name, 
                    cr_pos.value AS position, 
                    cf.file_path AS picture
                FROM candidates c
                JOIN registration_forms rf 
                    ON c.form_id = rf.id
                    AND rf.election_name = ? 
                JOIN candidate_responses cr_name 
                    ON c.id = cr_name.candidate_id
                JOIN form_fields ff_name 
                    ON cr_name.field_id = ff_name.id 
                    AND ff_name.field_name = 'full_name'
                JOIN candidate_responses cr_pos 
                    ON c.id = cr_pos.candidate_id
                JOIN form_fields ff_pos 
                    ON cr_pos.field_id = ff_pos.id 
                    AND ff_pos.field_name = 'position'
                JOIN candidate_responses cr_party 
                    ON c.id = cr_party.candidate_id
                JOIN form_fields ff_party 
                    ON cr_party.field_id = ff_party.id 
                    AND ff_party.field_name = 'party'
                    AND cr_party.value = ?
                LEFT JOIN candidate_files cf 
                    ON c.id = cf.candidate_id
                JOIN form_fields ff_picture 
                    ON cf.field_id = ff_picture.id 
                    AND ff_picture.field_name = 'picture'
                WHERE c.status = 'accepted'
                GROUP BY c.id, cr_name.value, cr_pos.value, cf.file_path
                ";

                    $stmtCandidates = $pdo->prepare($sqlCandidates);
                    $stmtCandidates->execute([$election_id, $partyName]);
                    $partyCandidates[$partyName] = $stmtCandidates->fetchAll(PDO::FETCH_ASSOC);
                }
            }

            // 4. Save election data
            $electionsData[] = [
                'election_name' => $election_id,
                'actual_election_name' => $election_name,
                'parties' => $parties,
                'partyCandidates' => $partyCandidates
            ];
        }
    } catch (Exception $e) {
        $electionsData = [];
    }
    ?>

    <?php foreach ($electionsData as $election): ?>
        <div class="container-fluid second-part">
            <hr>
            <h2 class="text-center fw-bold mb-4 pt-5">
                Party List/s for Election: <?= htmlspecialchars($election['actual_election_name']) ?>
            </h2>

            <div class="row justify-content-center">
                <?php if (!empty($election['parties'])): ?>
                    <?php foreach ($election['parties'] as $party): ?>
                        <?php
                        $partyName = $party['name'];
                        $sliderId = htmlspecialchars(str_replace(' ', '-', $election['election_name'] . '-' . $partyName));
                        ?>
                        <div class="col-md-5 mb-4">
                            <div class="card shadow-sm border-0 p-3 text-center">
                                <img src="uploads/<?= htmlspecialchars($party['party_image']) ?>"
                                    class="img-fluid rounded-circle mx-auto d-block"
                                    style="width: 120px; height: 120px; object-fit: cover;"
                                    onerror="this.src='uploads/default_party_image.jpg';">
                                <h5 class="mt-3"><?= htmlspecialchars($partyName) ?></h5>
                                <p class="text-muted">Party Name</p>

                                <?php $candidates = $election['partyCandidates'][$partyName] ?? []; ?>
                                <?php if (!empty($candidates)): ?>
                                    <section class="splide" id="splide-<?= $sliderId ?>">
                                        <div class="splide__track">
                                            <ul class="splide__list">
                                                <?php foreach ($candidates as $index => $candidate): ?>
                                                    <li class="splide__slide" data-candidate-id="<?= $index ?>">
                                                        <div class="container">
                                                            <img src="login/uploads/candidates/<?= htmlspecialchars($candidate['picture']) ?>"
                                                                class="img-fluid rounded-circle"
                                                                style="width: 80px; height: 80px; object-fit: cover;"
                                                                onerror="this.src='login/uploads/candidates/default_candidate.jpg'">
                                                            <h5 class="mt-3"><?= htmlspecialchars($candidate['student_name']) ?></h5>
                                                            <p><?= htmlspecialchars($candidate['position']) ?></p>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </section>
                                <?php else: ?>
                                    <p class="text-muted">No candidates registered yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="card shadow-sm border-0 p-3">
                            <p class="text-muted">No parties are available for this election.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>



    <script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($electionsData as $election): ?>
                <?php foreach ($election['parties'] as $party): ?>
                    <?php
                    $partyId = str_replace(' ', '-', $election['election_name'] . '-' . $party['name']);
                    $candidateCount = count($election['partyCandidates'][$party['name']] ?? []);
                    $perPage = min(1, $candidateCount); // adjust if needed
                    ?>
                    console.log('Election: <?= htmlspecialchars($election['election_name']) ?>, Party: <?= $partyId ?>, Candidates: <?= $candidateCount ?>');
                    new Splide('#splide-<?= htmlspecialchars($partyId) ?>', {
                        type: 'loop',
                        perPage: <?= $perPage ?>,
                        perMove: 1,
                        autoplay: true,
                        interval: 3000,
                        pauseOnHover: true,
                        gap: '1rem',
                        arrows: <?= $candidateCount > $perPage ? 'true' : 'false' ?>,
                        pagination: <?= $candidateCount > $perPage ? 'true' : 'false' ?>
                    }).mount();
                <?php endforeach; ?>
            <?php endforeach; ?>
        });
    </script>


    <!-- Include Splide.js (if not already included) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Splide/3.6.11/splide.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Splide/3.6.11/splide.min.js"></script>

    <?php
    date_default_timezone_set('Asia/Manila');
    require 'includes/conn.php';

    $current_date = date('Y-m-d');

    // Fetch all ongoing elections
    $sqlElection = "SELECT id, election_name, end_period, status FROM elections WHERE status = 'Ongoing'";
    $stmtElection = $pdo->prepare($sqlElection);
    $stmtElection->execute();
    $ongoingElections = $stmtElection->fetchAll(PDO::FETCH_ASSOC);

    $electionsData = [];

    foreach ($ongoingElections as $election) {
        $election_id = $election['id'];
        $election_name = $election['election_name'];
        $election_end = $election['end_period'];

        // Remaining days
        $remaining_days = floor((strtotime($election_end) - strtotime($current_date)) / (60 * 60 * 24));
        $days_left = ($remaining_days > 0) ? "$remaining_days days left" : "Ends today";

        // Total parties
        $stmtPartiesCount = $pdo->prepare("SELECT COUNT(*) AS total_parties FROM parties WHERE election_id = ?");
        $stmtPartiesCount->execute([$election_id]);
        $total_parties = $stmtPartiesCount->fetchColumn();

        // Total voters (assuming all voters for now)
        $stmtVotersCount = $pdo->query("SELECT COUNT(*) AS total_voters FROM voters where status = 'confirmed'");
        $total_voters = $stmtVotersCount->fetchColumn();

        // Total candidates
        $stmtCandidatesCount = $pdo->prepare("
        SELECT COUNT(*) AS total_candidates 
        FROM candidates c
        INNER JOIN registration_forms rf ON c.form_id = rf.id
        WHERE rf.status IN ('active','ended', 'disabled')
        AND rf.election_name = ?
        AND c.status = 'accepted'
    ");
        $stmtCandidatesCount->execute([$election_id]);
        $total_candidates = $stmtCandidatesCount->fetchColumn();

        $electionsData[] = [
            'election_name' => $election_name,
            'end_period' => $election_end,
            'days_left' => $days_left,
            'total_parties' => $total_parties,
            'total_candidates' => $total_candidates,
            'total_voters' => $total_voters
        ];
    }
    ?>

    <!-- Election Date & Statistics -->
    <div class="container-fluid third-page c-white text-center" style="height: max-content;">
        <br>
        <?php if (!empty($electionsData)): ?>
            <?php foreach ($electionsData as $election): ?>
                <h1 class="mt-5"><?= htmlspecialchars($election['election_name']) ?> Election</h1>
                <p class="small-liner-1"></p>
                <h5 class="bold"><?= htmlspecialchars(date("F j, Y", strtotime($election['end_period']))) ?></h5>
                <p><small><?= htmlspecialchars($election['days_left']) ?></small></p>

                <br>
                <h1 class="pt-lg-5">Election Statistics</h1>
                <p class="small-liner-1"></p>
                <div class="container row mt-3">
                    <div class="col">
                        <h1><?= htmlspecialchars($election['total_parties']) ?></h1>
                        <p>Parties</p>
                    </div>
                    <div class="col">
                        <h1><?= htmlspecialchars($election['total_candidates']) ?></h1>
                        <p>Candidates</p>
                    </div>
                    <div class="col">
                        <h1><?= htmlspecialchars($election['total_voters']) ?></h1>
                        <p>Voters</p>
                    </div>
                </div>
                <hr class="my-5">
            <?php endforeach; ?>
        <?php else: ?>
            <h1 class="mt-5">No Ongoing Election</h1>
            <p class="small-liner-1"></p>
            <h5 class="bold">Stay tuned for updates!</h5>
        <?php endif; ?>
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
    <div id="status-container" class="container-fluid bg-dark text-white py-3 shadow-sm">
        <div class="row">
            <div class="col text-center">
                <span class="fw-bold">Last updated: 10:29AM 20/04/2026</span>
                <span id="timestamp-text"></span>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>

    <script src="
            https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js
            "></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Splide('#card-slider', {
                type: 'loop',
                perPage: 3,
                pagination: false,
                arrows: false,
                autoscroll: true,
                perMove: 1,
                gap: '20px',
                breakpoints: {
                    1024: {
                        perPage: 2
                    },
                    768: {
                        perPage: 1
                    }
                }
            }).mount();
        });
    </script>

</body>

<?php
// Fetch the current date
$current_date = date('Y-m-d');

// Fetch the ongoing election
$sqlElection = "SELECT id, election_name, start_period, end_period FROM elections WHERE status = 'Ongoing' AND start_period <= ? AND end_period >= ?";
$stmtElection = $pdo->prepare($sqlElection);
$stmtElection->execute([$current_date, $current_date]);
$election = $stmtElection->fetch(PDO::FETCH_ASSOC);

// Check if election exists
if ($election) {
    $election_name = $election['election_name'];
    // Create the voting table name based on the election name
    $voting_table_name = 'voting_' . str_replace(' ', '_', strtolower($election_name));

    // Check if the table exists
    $stmt_check = $pdo->prepare("SHOW TABLES LIKE :table_name");
    $stmt_check->execute([':table_name' => $voting_table_name]);

    // Fetch the result to determine if the table exists
    $table_exists = $stmt_check->fetchColumn();
} else {
    $table_exists = false;
}
?>

<?php if ($table_exists): ?>
    <script>
        Swal.fire({
            title: "ATTENTION: WMSU Students",
            text: " In order for you to properly vote, you must have your QR Code ready with you. In case it is lost, please notify the admin.",
            icon: "success"
        });
    </script>
<?php endif; ?>

<?php


if (isset($_SESSION['STATUS'])) {
    switch ($_SESSION['STATUS']) {
        case 'ACCESS_DENIED':
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: 'You are not authorized to access this page!',
                    showConfirmButton: true
                });
            </script>";
            break;
        case 'NON_ADMIN':
            echo "<script>
                Swal.fire({
                    icon: 'warning',
                    title: 'Restricted Area',
                    text: 'Only administrators can access this section.',
                    showConfirmButton: true
                });
            </script>";
            break;
        case 'NON_MODERATOR':
            echo "<script>
                    Swal.fire({
                        icon: 'warning',
                        title: 'Restricted Area',
                        text: 'Only moderators can access this section.',
                        showConfirmButton: true
                    });
                </script>";
            break;
        case 'NON_VOTER':
            echo "<script>
                        Swal.fire({
                            icon: 'warning',
                            title: 'Restricted Area',
                            text: 'Only registered voters can access this section.',
                            showConfirmButton: true
                        });
                    </script>";
            break;
        case 'LOGOUT_SUCCESFUL':
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Logged Out',
                    text: 'You have successfully logged out!',
                    timer: 2000,
                    showConfirmButton: false
                });
            </script>";
            break;
        case 'NO_VOTING_PERIOD':
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'There are no voting periods yet!',
                    text: 'Please wait for a formal announcement regarding being able to vote!',
                    timer: 2000,
                    showConfirmButton: false
                });
            </script>";
            break;
    }
    unset($_SESSION['STATUS']); // Clear the session variable after use
}
session_unset();
session_destroy();
?>

<script>
    // 2. Fade out and remove the container after 3 seconds
    setTimeout(() => {
        const container = document.getElementById('status-container');
        if (container) {
            container.style.opacity = '0';

            // Completely remove from DOM after the fade transition
            setTimeout(() => {
                container.remove();
            }, 10);
        }
    }, 3000);
</script>

</html>