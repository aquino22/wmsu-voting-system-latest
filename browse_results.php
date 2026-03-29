<?php
session_start();
include('includes/conn_archived.php');
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WMSU i-Elect | Parties</title>
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
                            <li><a class="dropdown-item" href="about_us.php">About Us</a></li>
                            <li><a class="dropdown-item" href="about_system.php">About the System</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php" role="button">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="parties.php" role="button">Parties</a>
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
        <div class="container-fluid m-5 my-5">
            <h2 class="mb-4 text-center">Published Elections</h2>

            <div class="table-responsive shadow-sm">
                <table class="table table-hover align-middle">
                    <thead class="table-success">
                        <tr>
                            <th scope="col">Election</th>
                            <th scope="col">Election Period</th>
                            <th scope="col">Academic School Year</th>
                            <th scope="col">Semester</th>
                            <th scope="col">Parties Involved</th>
                            <th scope="col">Candidates</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <?php
                    // Get archived elections with parties and candidate count
                    $stmt = $pdo_archived->prepare("
    SELECT
        ae.election_name,
        ae.semester,
        ae.start_period,
        ae.end_period,
        ae.school_year_start,
        ae.school_year_end,
        ae.voting_period_id,
        -- Parties: use ae.parties if not empty, else group from archived_candidates
        CASE
            WHEN ae.parties IS NOT NULL AND ae.parties != ''
                THEN ae.parties
            ELSE (
                SELECT GROUP_CONCAT(DISTINCT TRIM(ac.party) ORDER BY ac.party SEPARATOR ', ')
                FROM archived_candidates ac
                WHERE ac.voting_period_id = ae.voting_period_id
            )
        END AS parties_involved,
        -- Count of candidates
        (
            SELECT COUNT(*) FROM archived_candidates
            WHERE voting_period_id = ae.voting_period_id
        ) AS candidate_total
    FROM archived_elections ae
    ORDER BY ae.start_period DESC
");
                    $stmt->execute();
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <tbody>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">No archived elections found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['election_name']) ?></td>
                                    <td>
                                        <?php
                                        $start = new DateTime($row['start_period']);
                                        $end = new DateTime($row['end_period']);
                                        echo htmlspecialchars($start->format('F j, Y') . ' – ' . $end->format('F j, Y'));
                                        ?>
                                    </td>

                                    <td><?= htmlspecialchars($row['school_year_start'] . '–' . $row['school_year_end']) ?></td>
                                    <td><?= htmlspecialchars($row['semester']) ?></td>
                                    <td>
                                        <?= htmlspecialchars(
                                            // make sure every comma is followed by exactly one space
                                            preg_replace('/\s*,\s*/', ', ', $row['parties_involved'] ?? 'N/A')
                                        ) ?>
                                    </td>

                                    <td><?= (int)$row['candidate_total'] ?></td>
                                    <td>
                                        <a href="view_results.php?election=<?= $row['election_name'] ?>"
                                            class="btn btn-outline-success btn-sm">
                                            View Results
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>


                </table>
            </div>
        </div>

    </div>

    <footer>
        <div class="container-fluid">
            <div class="row mt-5">
                <div class="col-md-6">
                    <h1 class="bold c-red"> <img src="external/img/wmsu-logo.png" class="img-fluid logo"> WMSU i-Elect</h1>
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
<?php
// Close PDO connection
$pdo = null;
?>