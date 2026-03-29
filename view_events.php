<?php
session_start();
include('includes/conn.php');
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WMSU i-Elect | Viewing Event</title>
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



<div class="container-fluid mt-5">
    <div class="container" style="border: 1px solid black; padding: 25px; border-radius: 10px;">


        <?php
        // Handle referrer
        $referrer = $_SERVER['HTTP_REFERER'] ?? 'default_page.php';
        ?>
        <a href="<?php echo htmlspecialchars($referrer); ?>" style="text-decoration: none;"><i class="bi bi-arrow-left"></i> Go back</a>
        <br>

        <?php
        try {
            if (!isset($_GET['event_id']) || !filter_var($_GET['event_id'], FILTER_VALIDATE_INT)) {
                throw new Exception('Invalid event ID');
            }

            $event_id = (int)$_GET['event_id'];

            // Increment views
            $stmt = $pdo->prepare("UPDATE events SET views = views + 1 WHERE id = ?");
            $stmt->execute([$event_id]);

            // Fetch event details
            $stmt = $pdo->prepare("SELECT event_title, cover_image, event_details, created_at, views, author, candidacy FROM events WHERE id = ?");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                throw new Exception('Event not found');
            }

            // Check election status
            $election_name = $event['candidacy'];
            $stmt = $pdo->prepare("SELECT status FROM elections WHERE id = ?");
            $stmt->execute([$election_name]);
            $election_status = $stmt->fetchColumn();
            if ($election_status && $election_status !== 'Ongoing') {
                echo '<p class="text-warning mt-2">Note: This election is currently ' . htmlspecialchars($election_status) . '.</p>';
            }

            // Render event details
        ?>
            <p class="mt-3">Published At: <?php echo htmlspecialchars((new DateTime($event['created_at']))->format('F j, Y, g:i A')); ?> | <i class="bi bi-eye-fill"></i> <?php echo htmlspecialchars($event['views']); ?></p>
            <h4 class="mt-2"><?php echo htmlspecialchars($event['event_title']); ?> | <span style="font-size: 12px">Author: <?php echo htmlspecialchars($event['author']); ?></span></h4>
            <?php
            $coverImage = !empty($event['cover_image']) && file_exists("uploads/event_covers/" . $event['cover_image'])
                ? "uploads/event_covers/" . htmlspecialchars($event['cover_image'])
                : "assets/images/placeholder.jpg";
            ?>
            <img src="<?php echo $coverImage; ?>" class="img-fluid" alt="<?php echo htmlspecialchars($event['event_title']); ?>">
            <p class="mt-2 mb-2">Scroll down below to read details.</p>
            <div class="small-liner mt-2 mb-2" style="border-top: 1px solid #ddd;"></div>
            <h5 class="mt-4">Event Details</h5>
            <?php echo $event['event_details'] ?: '<p class="text-muted">No details provided.</p>'; ?>
            <hr>

            <?php
            // Fetch approved parties
            $stmt = $pdo->prepare("SELECT id, name, platforms, party_image FROM parties WHERE election_id = ? AND  status IN ('approved', 'Published') ORDER BY ID ASC");
            $stmt->execute([$election_name]);
            $parties = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($parties) {
            ?>
                <h5 class="mt-4">Parties and Candidates</h5>
                <div class="row mt-3">
                    <?php foreach ($parties as $party) { ?>
                        <div class="col-12 col-md-6 mb-4">
                            <div class="card p-3">
                                <?php
                                $partyImage = !empty($party['party_image']) && file_exists("uploads/" . $party['party_image'])
                                    ? "uploads/" . htmlspecialchars($party['party_image'])
                                    : "assets/images/placeholder.jpg";
                                ?>
                                <img src="<?php echo $partyImage; ?>"
                                    class="card-img-top"
                                    style="width: 100px; height: 100px; object-fit: cover; margin: 0 auto;"
                                    alt="<?php echo htmlspecialchars($party['name']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title mb-4 text-center"><?php echo htmlspecialchars($party['name']); ?></h5>
                                    <h6 class="card-subtitle mb-2 text-muted text-center">Platforms</h6>
                                    <?php if (!empty($party['platforms'])) { ?>
                                        <div class="platforms-content card-text"
                                            style="min-height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; overflow: auto; font-size: 14px;">
                                            <?php echo $party['platforms']; ?>
                                        </div>
                                    <?php } else { ?>
                                        <p class="text-muted text-center" style="font-size: 14px;">No platforms specified.</p>
                                    <?php } ?>
                                    <h6 class="card-subtitle mb-2 text-muted mt-3 text-center">Candidates</h6>
                                    <?php
                                    // Fetch candidates
                                    $stmt = $pdo->prepare("
                                        SELECT c.id AS candidate_id,
                                               cr_name.value AS candidate_name,
                                               cr_position.value AS position,
                                               cf.file_path AS profile_image
                                        FROM candidates c
                                        LEFT JOIN candidate_responses cr_name ON c.id = cr_name.candidate_id
                                        LEFT JOIN form_fields ff_name ON cr_name.field_id = ff_name.id AND ff_name.field_name = 'full_name'
                                        LEFT JOIN candidate_responses cr_position ON c.id = cr_position.candidate_id
                                        LEFT JOIN form_fields ff_position ON cr_position.field_id = ff_position.id AND ff_position.field_name = 'position'
                                        LEFT JOIN candidate_files cf ON c.id = cf.candidate_id
                                        LEFT JOIN form_fields ff_picture ON cf.field_id = ff_picture.id AND ff_picture.field_name = 'picture'
                                        LEFT JOIN candidate_responses cr_party ON c.id = cr_party.candidate_id
                                        LEFT JOIN form_fields ff_party ON cr_party.field_id = ff_party.id AND ff_party.field_name = 'party'
                                        WHERE c.status = 'accepted' AND cr_party.value = ?
                                        GROUP BY c.id
                                    ");
                                    $stmt->execute([$party['name']]);
                                    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    if ($candidates) {
                                        echo '<ul class="list-group list-group-flush">';
                                        foreach ($candidates as $candidate) {
                                            $profileImage = !empty($candidate['profile_image']) && file_exists("login/uploads/candidates/" . $candidate['profile_image'])
                                                ? "login/uploads/candidates/" . htmlspecialchars($candidate['profile_image'])
                                                : "admin/uploads/candidates/" . htmlspecialchars($candidate['profile_image']);
                                    ?>
                                            <li class="list-group-item d-flex align-items-center">
                                                <img src="<?php echo $profileImage; ?>"
                                                    alt="<?php echo htmlspecialchars($candidate['candidate_name'] ?? 'Candidate'); ?>"
                                                    class="rounded-circle me-2"
                                                    style="width: 40px; height: 40px; object-fit: cover;">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($candidate['candidate_name'] ?? 'Unknown'); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($candidate['position'] ?? 'N/A'); ?></small>
                                                </div>
                                            </li>
                                    <?php
                                        }
                                        echo '</ul>';
                                    } else {
                                        echo '<p class="text-muted">No candidates found.</p>';
                                    }
                                    ?>
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
                                        <button class="btn btn-danger deleteBtnParty mt-3" data-id="<?php echo $party['id']; ?>">
                                            <i class="mdi mdi-delete"></i> Delete Party
                                        </button>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
        <?php
            } else {
                echo '<p class="text-muted mt-4">No approved parties found for this candidacy.</p>';
            }
        } catch (Exception $e) {
            error_log("Error in event_details.php: " . $e->getMessage());
            echo '<p class="text-danger mt-4">' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
</div>

<!-- JavaScript for Party Deletion -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
    <script>
        document.querySelectorAll('.deleteBtnParty').forEach(button => {
            button.addEventListener('click', function() {
                if (!confirm('Are you sure you want to delete this party and all related data?')) {
                    return;
                }

                const partyId = this.getAttribute('data-id');
                const csrfToken = document.querySelector('input[name="csrf_token"]').value;

                fetch('../../processes/elect/delete_party.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            party_id: partyId,
                            csrf_token: csrfToken
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert(data.message);
                            this.closest('.col-12').remove();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to delete party');
                    });
            });
        });
    </script>
<?php } ?>

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