<?php
session_start();
include('includes/conn.php');
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
        <div class="container" style="border: 1px solid black; padding: 25px; border-radius: 10px;">
            <a href="index.php" style="text-decoration: none;"><i class="bi bi-arrow-left"></i> Go back</a>
            <h4 class="mt-3 text-center">Parties by Candidacy</h4>
            <div class="small-liner mt-2 mb-4"></div>

            <?php
            // Fetch distinct elections with approved or published parties
            // After
            $stmt = $pdo->prepare("
    SELECT DISTINCT p.election_id, e.election_name AS election_name 
    FROM parties p
    JOIN elections e ON e.id = p.election_id
    WHERE p.status IN ('approved', 'Published') 
    ORDER BY p.election_id
");
            $stmt->execute();
            $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($elections) {
                foreach ($elections as $election) {

                    $electionId   = $election['election_id'];
                    $electionName = $election['election_name'];

                    echo '<h1 class="mt-4 text-center">' . htmlspecialchars($electionName) . ' Elections </h1>';

                    // Check if the election has any parties with 'Published' status to show results
                    // $stmt = $pdo->prepare("SELECT COUNT(*) FROM parties WHERE election_id = ? AND status = 'Published'");
                    // $stmt->execute([$electionId]);
                    // $has_published = $stmt->fetchColumn();

                    // echo $has_published;

                    // Check for archived ID
                    $archivedId = null;
                    if (isset($pdo_archived)) {
                        $stmtArch = $pdo_archived->prepare("SELECT voting_period_id FROM archived_elections WHERE id = ? LIMIT 1");
                        $stmtArch->execute([$electionId]);
                        $archivedId = $stmtArch->fetchColumn();
                    }

                    if ($archivedId) {
                        echo '<div class="text-center mb-3">';
                        echo '<a href="view_results.php?election=' . urlencode($electionId) . '&id=' . $archivedId . '" class="btn btn-primary btn-sm">View Results</a>';
                        echo '</div>';
                    }

                    echo '<div class="row mt-3">';



                    // Fetch parties for this election
                    $stmt = $pdo->prepare("SELECT id, name, platforms, party_image FROM parties WHERE election_id = ? AND status IN ('approved', 'Published')");
                    $stmt->execute([$electionId]);
                    $parties = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($parties) {
                        foreach ($parties as $party) {
            ?>
                            <div class="col-md-6 mb-4">
                                <div class="card p-3">
                                    <?php
                                    // Check if party_image exists and is valid
                                    $partyImage = !empty($party['party_image']) && file_exists('uploads/' . $party['party_image'])
                                        ? 'uploads/' . htmlspecialchars($party['party_image'])
                                        : 'assets/images/placeholder.jpg'; // Fallback placeholder
                                    ?>
                                    <img src="<?php echo $partyImage; ?>"
                                        class="card-img-top"
                                        style="width: 100px; height: 100px; object-fit: cover; margin: 0 auto;"
                                        alt="<?php echo htmlspecialchars($party['name']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4 text-center"><?php echo htmlspecialchars($party['name']); ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted text-center">Platforms</h6>
                                        <?php if (!empty($party['platforms'])): ?>
                                            <div class="platforms-content card-text"
                                                style="min-height: 100px; margin: 0 auto; padding: 10px; border: 1px solid #ddd; border-radius: 5px; overflow: auto; font-size: 14px;">
                                                <?php echo $party['platforms']; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted text-center" style="margin: 0 auto; font-size: 14px;">No platforms specified.</p>
                                        <?php endif; ?>
                                        <h6 class="card-subtitle mb-2 text-muted mt-3 text-center">Candidates</h6>
                                        <?php
                                        // Fetch candidates for this party
                                        $stmt = $pdo->prepare("
                                    SELECT DISTINCT
                                        c.id AS candidate_id,
                                        MAX(CASE WHEN ff.field_name = 'full_name' THEN cr.value END) AS candidate_name,
                                        MAX(CASE WHEN ff.field_name = 'position' THEN cr.value END) AS position,
                                        MAX(CASE WHEN ff.field_name = 'platform' THEN cr.value END) AS platform,
                                        MIN(cf.file_path) AS profile_image
                                    FROM candidates c
                                    JOIN candidate_responses cr ON c.id = cr.candidate_id
                                    JOIN form_fields ff ON cr.field_id = ff.id
                                    LEFT JOIN candidate_files cf ON c.id = cf.candidate_id
                                    LEFT JOIN form_fields ff_picture ON cf.field_id = ff_picture.id 
                                        AND ff_picture.field_name = 'picture'
                                    WHERE c.status = 'accepted'
                                        AND EXISTS (
                                            SELECT 1
                                            FROM candidate_responses cr_party
                                            JOIN form_fields ff_party ON cr_party.field_id = ff_party.id
                                            WHERE cr_party.candidate_id = c.id
                                                AND ff_party.field_name = 'party'
                                                AND cr_party.value = ?
                                        )
                                    GROUP BY c.id
                                ");
                                        $stmt->execute([$party['name']]);
                                        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        // Log for debugging
                                        if (count($candidates) > 0) {
                                            error_log("Candidates for party '{$party['name']}': " . json_encode(array_column($candidates, 'candidate_id')));
                                        } else {
                                            error_log("No candidates found for party: '{$party['name']}'");
                                        }

                                        if ($candidates) {
                                            echo '<ul class="list-group list-group-flush">';
                                            foreach ($candidates as $candidate) {
                                                // Check if profile_image exists and is valid
                                                $profileImage = !empty($candidate['profile_image']) && file_exists('login/uploads/candidates/' . $candidate['profile_image'])
                                                    ? 'login/uploads/candidates/' . htmlspecialchars($candidate['profile_image'])
                                                    : 'assets/images/candidate_placeholder.jpg'; // Fallback placeholder
                                        ?>
                                                <li class="list-group-item d-flex align-items-center">
                                                    <img src="<?php echo $profileImage; ?>"
                                                        alt="<?php echo htmlspecialchars($candidate['candidate_name']); ?>"
                                                        class="rounded-circle me-2"
                                                        style="width: 40px; height: 40px; object-fit: cover;">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($candidate['candidate_name']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($candidate['position']); ?></small>
                                                    </div>
                                                </li>
                                        <?php
                                            }
                                            echo '</ul>';
                                        } else {
                                            echo '<p class="text-muted">No candidates found.</p>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
            <?php
                        }
                    } else {
                        echo '<p class="text-muted">No parties found for this candidacy.</p>';
                    }
                    echo '</div> <hr>';
                }
            } else {
                echo '<p class="mt-4">No parties found.</p>';
            }
            ?>
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