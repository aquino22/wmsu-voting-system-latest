<?php
session_start();
include('includes/conn.php');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = "NON_ADMIN";
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU i-Elect: Admin | Index</title>

    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="js/select.dataTables.min.css">
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="shortcut icon" href="images/favicon.png" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<style>
    /* ── Back to Top ── */
    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background-color: #B22222;
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(0, 0, 0, .3);
        transition: all .4s cubic-bezier(.4, 0, .2, 1);
        z-index: 1000;
        opacity: 0;
        transform: translateY(20px) scale(.8);
        visibility: hidden;
    }

    .back-to-top.show {
        opacity: 1;
        transform: translateY(0) scale(1);
        visibility: visible;
    }

    .back-to-top:hover {
        background-color: #8B1A1A;
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 6px 12px rgba(0, 0, 0, .4);
    }

    .back-to-top:active {
        transform: translateY(-1px) scale(1.02);
    }

    /* ── Live badge ── */
    .rt-live-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #28a745;
        background: rgba(40, 167, 69, .1);
        border: 1px solid rgba(40, 167, 69, .35);
        border-radius: 20px;
        padding: 2px 10px;
        margin-left: 10px;
        vertical-align: middle;
        user-select: none;
    }

    .rt-live-badge .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #28a745;
        animation: rtPulse 1.4s ease-in-out infinite;
        flex-shrink: 0;
    }

    @keyframes rtPulse {

        0%,
        100% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: .4;
            transform: scale(1.4);
        }
    }

    /* ── Flash overlay when bar changes ── */
    @keyframes rtBarFlash {
        0% {
            opacity: .55;
        }

        100% {
            opacity: 0;
        }
    }

    .rt-flash-overlay {
        position: absolute;
        inset: 0;
        background: rgba(40, 167, 69, .25);
        border-radius: 8px;
        pointer-events: none;
        animation: rtBarFlash .8s ease-out forwards;
    }

    /* ── Stat pop animation ── */
    @keyframes rtStatPop {
        0% {
            color: #28a745;
            transform: scale(1.12);
        }

        100% {
            color: inherit;
            transform: scale(1);
        }
    }

    .rt-stat-highlight {
        animation: rtStatPop .6s ease-out;
    }

    /* ── Last-updated timestamp ── */
    .rt-updated-at {
        font-size: 11px;
        color: #888;
        margin-left: 6px;
        font-style: italic;
    }
</style>

<body>
    <div class="container-scroller">

        <!-- ═══════════ NAVBAR ═══════════ -->
        <?php
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT a.full_name, a.phone_number, u.email
                           FROM admin a
                           JOIN users u ON a.user_id = u.id
                           WHERE u.id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        $admin_full_name    = $admin['full_name']    ?? '';
        $admin_phone_number = $admin['phone_number'] ?? '';
        $admin_email        = $admin['email']        ?? '';
        ?>

        <nav class="navbar default-layout col-lg-12 col-12 p-0 d-flex align-items-center justify-content-center flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
                <div class="me-3">
                    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
                        <span class="icon-menu"></span>
                    </button>
                </div>
                <div>
                    <a class="navbar-brand brand-logo" href="index.php">
                        <img src="images/wmsu-logo.png" alt="logo" class="logo img-fluid" />
                        <small style="font-size:16px;"><b>WMSU i-Elect</b></small>
                    </a>
                    <a class="navbar-brand brand-logo-mini" href="index.html">
                        <img src="images/wmsu-logo.png" class="logo img-fluid" alt="logo" />
                    </a>
                </div>
            </div>

            <div class="navbar-menu-wrapper d-flex align-items-top">
                <ul class="navbar-nav">
                    <li class="nav-item font-weight-semibold d-none d-lg-block ms-0">
                        <h1 class="welcome-text">Welcome, <span class="text-white fw-bold">WMSU Admin</span></h1>
                        <h6>
                            <?php
                            $stmt = $pdo->prepare("
                            SELECT e.id, e.election_name, e.academic_year_id, a.year_label, a.semester
                            FROM elections e
                            JOIN academic_years a ON e.academic_year_id = a.id
                            WHERE e.status = :status
                            ORDER BY a.year_label DESC, a.semester DESC
                        ");
                            $stmt->execute(['status' => 'Ongoing']);
                            $ongoingElections = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if ($ongoingElections) {
                                $first = array_shift($ongoingElections);
                                echo "<br><b>School Year:</b> " . htmlspecialchars($first['year_label']) . " | ";
                                echo "<b>Semester:</b> "       . htmlspecialchars($first['semester'])    . " | ";
                                echo "<b>Election:</b> "       . htmlspecialchars($first['election_name']) . "<br>";

                                if ($ongoingElections) {
                                    echo '<div id="moreElections" style="display:none; margin-top:5px;">';
                                    foreach ($ongoingElections as $election) {
                                        echo "<b>School Year:</b> " . htmlspecialchars($election['year_label']) . " | ";
                                        echo "<b>Semester:</b> "   . htmlspecialchars($election['semester'])    . " | ";
                                        echo "<b>Election:</b> "   . htmlspecialchars($election['election_name']) . "<br>";
                                    }
                                    echo '</div><br>';
                                    echo '<a href="javascript:void(0)" id="toggleElections" class="text-decoration-underline text-white">Show More</a>';
                                }
                            }
                            ?>
                        </h6>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                        <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <img class="img-xs rounded-circle logo" src="images/wmsu-logo.png" style="background-color:white;" alt="Profile image">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                            <div class="dropdown-header text-center">
                                <img class="img-xs rounded-circle logoe" src="images/wmsu-logo.png" alt="Profile image">
                            </div>
                            <p class="mb-1 mt-3 font-weight-semibold dropdown-item"><b>WMSU ADMIN</b></p>
                            <a class="dropdown-item" id="logoutLink" href="processes/accounts/logout.php">
                                <i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign Out
                            </a>
                        </div>
                    </li>
                </ul>

                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
                    <span class="mdi mdi-menu"></span>
                </button>
            </div>
        </nav>
        <!-- ═══════════ END NAVBAR ═══════════ -->

        <div class="container-fluid page-body-wrapper">
            <?php include('includes/sidebar.php'); ?>

            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab"
                                                href="#overview" role="tab" aria-controls="overview" aria-selected="true">
                                                Dashboard
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">

                                        <?php
                                        /* ═══════════════════════════════════════════════════════
                                       FETCH VOTING PERIOD DATA
                                    ═══════════════════════════════════════════════════════ */
                                        $votingPeriodData = [];

                                        // Fallback variables used by SweetAlert below
                                        $daysUntilStart       = null;
                                        $votingPeriodName     = '';
                                        $votingPeriodStartDate = '';
                                        $votingPeriodStatus   = '';

                                        try {
                                            // Priority 1: Ongoing / Paused
                                            $stmt = $pdo->prepare("
                                            SELECT
                                                vp.id              AS voting_period_id,
                                                vp.start_period    AS vp_start,
                                                vp.end_period      AS vp_end,
                                                vp.re_start_period AS vp_re_start,
                                                vp.re_end_period   AS vp_re_end,
                                                vp.status          AS vp_status,
                                                e.id               AS election_id,
                                                e.election_name,
                                                ay.year_label,
                                                ay.semester,
                                                ay.start_date      AS ay_start,
                                                ay.end_date        AS ay_end,
                                                c.start_period     AS candidacy_start,
                                                c.end_period       AS candidacy_end
                                            FROM voting_periods vp
                                            JOIN elections e      ON vp.election_id = e.id
                                            JOIN academic_years ay ON e.academic_year_id = ay.id
                                            LEFT JOIN candidacy c  ON c.election_id = e.id
                                            WHERE vp.status IN ('Ongoing','Paused')
                                            ORDER BY vp.start_period ASC
                                        ");
                                            $stmt->execute();
                                            $votingPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                            // Priority 2: Scheduled
                                            if (empty($votingPeriods)) {
                                                $stmt = $pdo->prepare("
                                                SELECT
                                                    vp.id              AS voting_period_id,
                                                    vp.start_period    AS vp_start,
                                                    vp.end_period      AS vp_end,
                                                    vp.re_start_period AS vp_re_start,
                                                    vp.re_end_period   AS vp_re_end,
                                                    vp.status          AS vp_status,
                                                    e.id               AS election_id,
                                                    e.election_name,
                                                    ay.year_label,
                                                    ay.semester,
                                                    ay.start_date      AS ay_start,
                                                    ay.end_date        AS ay_end,
                                                    c.start_period     AS candidacy_start,
                                                    c.end_period       AS candidacy_end
                                                FROM voting_periods vp
                                                JOIN elections e      ON vp.election_id = e.id
                                                JOIN academic_years ay ON e.academic_year_id = ay.id
                                                LEFT JOIN candidacy c  ON c.election_id = e.id
                                                WHERE vp.status = 'Scheduled' AND vp.start_period >= NOW()
                                                ORDER BY vp.start_period ASC
                                            ");
                                                $stmt->execute();
                                                $votingPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            }

                                            // Priority 3: Most recent Finished
                                            if (empty($votingPeriods)) {
                                                $stmt = $pdo->prepare("
                                                SELECT
                                                    vp.id              AS voting_period_id,
                                                    vp.start_period    AS vp_start,
                                                    vp.end_period      AS vp_end,
                                                    vp.re_start_period AS vp_re_start,
                                                    vp.re_end_period   AS vp_re_end,
                                                    vp.status          AS vp_status,
                                                    e.id               AS election_id,
                                                    e.election_name,
                                                    ay.year_label,
                                                    ay.semester,
                                                    ay.start_date      AS ay_start,
                                                    ay.end_date        AS ay_end,
                                                    c.start_period     AS candidacy_start,
                                                    c.end_period       AS candidacy_end
                                                FROM voting_periods vp
                                                JOIN elections e      ON vp.election_id = e.id
                                                JOIN academic_years ay ON e.academic_year_id = ay.id
                                                LEFT JOIN candidacy c  ON c.election_id = e.id
                                                WHERE vp.status = 'Finished'
                                                ORDER BY vp.end_period DESC
                                                LIMIT 3
                                            ");
                                                $stmt->execute();
                                                $votingPeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            }

                                            foreach ($votingPeriods as $v) {
                                                $votingPeriodId     = $v['voting_period_id'];
                                                $vpElectionId       = $v['election_id'];
                                                $vpName             = $v['election_name'];
                                                $vpStatus           = $v['vp_status'];
                                                $vpStart            = $v['vp_start'];
                                                $vpEnd              = $v['vp_end'] ?: 'TBD';
                                                $vpSemester         = $v['semester'];
                                                $vpYearLabel        = $v['year_label'];
                                                $candidacyStart     = $v['candidacy_start'] ?? null;
                                                $candidacyEnd       = $v['candidacy_end']   ?? null;

                                                // Days until start
                                                $currentDate = new DateTime();
                                                $startDate   = new DateTime($vpStart);
                                                $daysUntilStart = $startDate < $currentDate
                                                    ? -$currentDate->diff($startDate)->days
                                                    :  $currentDate->diff($startDate)->days;

                                                // Set fallback vars for SweetAlert (last period wins, good enough)
                                                $votingPeriodName      = $vpName;
                                                $votingPeriodStartDate = $vpStart;
                                                $votingPeriodStatus    = $vpStatus;

                                                // Party count
                                                $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM parties WHERE election_id = ? AND status = 'approved'");
                                                $stmt2->execute([$vpElectionId]);
                                                $partyCount = (int)$stmt2->fetchColumn();

                                                // Candidate count
                                                $stmt2 = $pdo->prepare("
                                                SELECT COUNT(*)
                                                FROM candidates c
                                                JOIN registration_forms rf ON c.form_id = rf.id
                                                WHERE rf.election_name = ? AND c.status = 'accepted'
                                            ");
                                                $stmt2->execute([$vpElectionId]);
                                                $candidateCount = (int)$stmt2->fetchColumn();

                                                // Voting stats (only for live periods)
                                                $totalVotesCast    = 0;
                                                $registeredVoters  = 0;
                                                $voterTurnout      = 0;
                                                $leadingPartyDisplay = 'N/A';
                                                $availableVotes    = 0;
                                                $positions         = [];
                                                $voteData          = [];

                                                if (in_array($vpStatus, ['Ongoing', 'Paused'])) {
                                                    // Total unique voters
                                                    $stmt2 = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM votes WHERE voting_period_id = ?");
                                                    $stmt2->execute([$votingPeriodId]);
                                                    $totalVotesCast = (int)$stmt2->fetchColumn();

                                                    // Registered voters via precincts
                                                    $stmt2 = $pdo->prepare("SELECT precinct_id FROM precinct_elections WHERE election_name = ?");
                                                    $stmt2->execute([$vpElectionId]);
                                                    $assignedPrecincts = $stmt2->fetchAll(PDO::FETCH_COLUMN);

                                                    if (!empty($assignedPrecincts)) {
                                                        $inQ  = implode(',', array_fill(0, count($assignedPrecincts), '?'));
                                                        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM precinct_voters WHERE precinct IN ($inQ)");
                                                        $stmt2->execute($assignedPrecincts);
                                                        $registeredVoters = (int)$stmt2->fetchColumn();
                                                    }

                                                    $voterTurnout   = round(($totalVotesCast / max($registeredVoters, 1)) * 100, 1);
                                                    $availableVotes = $registeredVoters - $totalVotesCast;

                                                    // Leading party
                                                    $stmt2 = $pdo->prepare("
                                                    SELECT cr_party.value AS party_name, COUNT(v.id) AS vote_count
                                                    FROM candidates c
                                                    LEFT JOIN candidate_responses cr_party ON c.id = cr_party.candidate_id
                                                    JOIN form_fields ff_party ON cr_party.field_id = ff_party.id AND ff_party.field_name = 'party'
                                                    LEFT JOIN votes v ON c.id = v.candidate_id AND v.voting_period_id = ?
                                                    JOIN registration_forms rf ON c.form_id = rf.id
                                                    WHERE c.status = 'accepted' AND rf.election_name = ?
                                                    GROUP BY cr_party.value
                                                    ORDER BY vote_count DESC
                                                    LIMIT 1
                                                ");
                                                    $stmt2->execute([$votingPeriodId, $vpElectionId]);
                                                    $leadingParty        = $stmt2->fetch(PDO::FETCH_ASSOC);
                                                    $leadingPartyDisplay = $leadingParty['party_name'] ?? 'N/A';

                                                    // Positions
                                                    $stmt2 = $pdo->prepare("SELECT DISTINCT name, MIN(level) AS level FROM positions GROUP BY name ORDER BY level, name");
                                                    $stmt2->execute();
                                                    $positions = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                                                    // Vote data per position
                                                    foreach ($positions as $pos) {
                                                        $stmt2 = $pdo->prepare("
                                                         SELECT
        c.id,
        cr_name.value           AS name,
        cr_party.value          AS party_name,
        COALESCE(col.college_name,    vtrs.college)     AS college,
        COALESCE(dept.department_name, vtrs.department) AS department,
        COUNT(v.id)             AS vote_count
    FROM candidates c
    JOIN candidate_responses cr_name  ON c.id = cr_name.candidate_id
    JOIN form_fields ff_name ON cr_name.field_id = ff_name.id AND ff_name.field_name = 'full_name'
    LEFT JOIN candidate_responses cr_party ON c.id = cr_party.candidate_id
    JOIN form_fields ff_party ON cr_party.field_id = ff_party.id AND ff_party.field_name = 'party'
    JOIN candidate_responses cr_pos  ON c.id = cr_pos.candidate_id
    JOIN form_fields ff_pos ON cr_pos.field_id = ff_pos.id AND ff_pos.field_name = 'position' AND cr_pos.value = ?
    LEFT JOIN candidate_responses cr_sid ON c.id = cr_sid.candidate_id
    JOIN form_fields ff_sid ON cr_sid.field_id = ff_sid.id AND ff_sid.field_name = 'student_id'
    LEFT JOIN voters vtrs ON vtrs.student_id = cr_sid.value
    LEFT JOIN colleges    col  ON col.college_id    = vtrs.college
    LEFT JOIN departments dept ON dept.department_id = vtrs.department
    LEFT JOIN votes v ON c.id = v.candidate_id AND v.voting_period_id = ?
    JOIN registration_forms rf ON c.form_id = rf.id
    WHERE c.status = 'accepted' AND rf.election_name = ?
    GROUP BY c.id, cr_name.value, cr_party.value, col.college_name, dept.department_name
                                                    ");
                                                        $stmt2->execute([$pos['name'], $votingPeriodId, $vpElectionId]);
                                                        $voteData[$pos['name']] = [
                                                            'level'      => $pos['level'],
                                                            'candidates' => $stmt2->fetchAll(PDO::FETCH_ASSOC)
                                                        ];
                                                    }
                                                }

                                                $votingPeriodData[] = [
                                                    'id'               => $votingPeriodId,
                                                    'name'             => $vpName,
                                                    'semester'         => $vpSemester,
                                                    'year_label'       => $vpYearLabel,
                                                    'start_date'       => $vpStart,
                                                    'end_date'         => $vpEnd,
                                                    'status'           => $vpStatus,
                                                    'days_until_start' => $daysUntilStart,
                                                    'party_count'      => $partyCount,
                                                    'candidate_count'  => $candidateCount,
                                                    'total_votes_cast' => $totalVotesCast,
                                                    'registered_voters' => $registeredVoters,
                                                    'voter_turnout'    => $voterTurnout,
                                                    'leading_party'    => $leadingPartyDisplay,
                                                    'available_votes'  => $availableVotes,
                                                    'positions'        => $positions,
                                                    'vote_data'        => $voteData,
                                                    'candidacy_start'  => $candidacyStart,
                                                    'candidacy_end'    => $candidacyEnd,
                                                ];
                                            }
                                        } catch (Exception $e) {
                                            $votingPeriodData = [];
                                            error_log("Error fetching voting periods: " . $e->getMessage());
                                        }
                                        ?>

                                        <!-- Pass PHP data to JS -->
                                        <script>
                                            const votingPeriods = <?php echo json_encode($votingPeriodData); ?>;
                                            const votingPeriodsById = {};
                                            votingPeriods.forEach(p => {
                                                votingPeriodsById[p.id] = p;
                                            });
                                        </script>

                                        <!-- ═══════════ VOTING PERIOD CARDS ═══════════ -->
                                        <div class="row mt-3">
                                            <?php if (!empty($votingPeriodData)): ?>
                                                <?php foreach ($votingPeriodData as $period): ?>

                                                    <?php
                                                    $statusColor = match ($period['status']) {
                                                        'Ongoing'   => '#28a745',
                                                        'Paused'    => '#ffc107',
                                                        'Scheduled' => '#17a2b8',
                                                        default     => '#6c757d',
                                                    };
                                                    $isLive = in_array($period['status'], ['Ongoing', 'Paused']);
                                                    ?>

                                                    <div class="col-12 mb-4">
                                                        <div class="card card-rounded">

                                                            <!-- Card header -->
                                                            <div class="card-header" style="background-color:<?php echo $statusColor; ?>; color:white;">
                                                                <h4 class="mb-0">
                                                                    <i class="mdi mdi-vote"></i>
                                                                    <?php echo htmlspecialchars($period['name']); ?>
                                                                    <span class="badge bg-light text-dark float-end">
                                                                        <?php echo ucfirst($period['status']); ?>
                                                                    </span>
                                                                </h4>
                                                            </div>

                                                            <div class="card-body">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="d-flex justify-content-between align-items-start flex-grow-1">
                                                                        <div>
                                                                            <h5 class="card-subtitle card-subtitle-dash">
                                                                                School Year: <?php echo htmlspecialchars($period['year_label']); ?> | <?php echo htmlspecialchars($period['semester']); ?>
                                                                            </h5>
                                                                            <p>
                                                                                Voting Period:
                                                                                <strong><?php echo date('F j, Y', strtotime($period['start_date'])); ?></strong>
                                                                                to
                                                                                <strong><?php echo $period['end_date'] !== 'TBD' ? date('F j, Y', strtotime($period['end_date'])) : 'TBD'; ?></strong>
                                                                            </p>
                                                                            <p>
                                                                                Candidacy Period:
                                                                                <strong><?php echo $period['candidacy_start'] ? date('F j, Y', strtotime($period['candidacy_start'])) : 'TBD'; ?></strong>
                                                                                to
                                                                                <strong><?php echo $period['candidacy_end']   ? date('F j, Y', strtotime($period['candidacy_end']))   : 'TBD'; ?></strong>
                                                                            </p>

                                                                            <!-- Countdown -->
                                                                            <?php if ($period['end_date'] !== 'TBD' && $isLive): ?>
                                                                                <div style="font-weight:bold; color:#B22222; margin-bottom:5px;">
                                                                                    Remaining Voting Time:
                                                                                    <span id="countdown-timer-<?php echo $period['id']; ?>"></span>
                                                                                </div>
                                                                                <script>
                                                                                    (function() {
                                                                                        var end = new Date("<?php echo $period['end_date']; ?>").getTime();
                                                                                        var el = document.getElementById("countdown-timer-<?php echo $period['id']; ?>");
                                                                                        var tick = setInterval(function() {
                                                                                            var diff = end - Date.now();
                                                                                            if (diff <= 0) {
                                                                                                clearInterval(tick);
                                                                                                el.textContent = "Voting period has ended.";
                                                                                                return;
                                                                                            }
                                                                                            var d = Math.floor(diff / 86400000);
                                                                                            var h = Math.floor((diff % 86400000) / 3600000);
                                                                                            var m = Math.floor((diff % 3600000) / 60000);
                                                                                            var s = Math.floor((diff % 60000) / 1000);
                                                                                            el.textContent = d + "d " + h + "h " + m + "m " + s + "s";
                                                                                        }, 1000);
                                                                                    })();
                                                                                </script>
                                                                            <?php endif; ?>

                                                                            <?php if ($period['status'] === 'Paused'): ?>
                                                                                <a href="#" class="btn btn-primary btn-sm mt-2 resume-voting"
                                                                                    data-id="<?php echo $period['id']; ?>">Resume Voting</a>
                                                                            <?php elseif ($period['status'] === 'Scheduled'): ?>
                                                                                <a href="candidacy.php" class="btn btn-info btn-sm mt-2">Manage Candidacy</a>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Party / Candidate counters -->
                                                                    <div class="ms-3">
                                                                        <div class="row">
                                                                            <div class="col">
                                                                                <div class="card bg-danger text-white">
                                                                                    <div class="card-body text-center p-2">
                                                                                        <small>Parties</small>
                                                                                        <h4 class="mb-0"><b><?php echo $period['party_count']; ?></b></h4>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col">
                                                                                <div class="card bg-warning text-dark">
                                                                                    <div class="card-body text-center p-2">
                                                                                        <small>Candidates</small>
                                                                                        <h4 class="mb-0"><b><?php echo $period['candidate_count']; ?></b></h4>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- ── Voting statistics (live periods only) ── -->
                                                                <?php if ($isLive): ?>

                                                                    <div class="row mt-3">
                                                                        <div class="col-12">
                                                                            <h5><b>Voting Statistics for <?php echo htmlspecialchars($period['name']); ?></b></h5>
                                                                            <div class="statistics-details d-flex align-items-center justify-content-between">
                                                                                <div class="text-center">
                                                                                    <p class="statistics-title mb-1">Total Votes</p>
                                                                                    <h4 class="rate-percentage mb-0"
                                                                                        data-rt-stat="<?php echo $period['id']; ?>-total">
                                                                                        <?php echo number_format($period['total_votes_cast']); ?>
                                                                                    </h4>
                                                                                    <p class="text-muted mb-0">Period: <?php echo htmlspecialchars($period['name']); ?></p>
                                                                                </div>
                                                                                <div class="text-center">
                                                                                    <p class="statistics-title mb-1">Registered Voters</p>
                                                                                    <h4 class="rate-percentage mb-0"
                                                                                        data-rt-stat="<?php echo $period['id']; ?>-registered">
                                                                                        <?php echo number_format($period['registered_voters']); ?>
                                                                                    </h4>
                                                                                    <p class="text-muted mb-0">Assigned to this election</p>
                                                                                </div>
                                                                                <div class="text-center">
                                                                                    <p class="statistics-title mb-1">Turnout Rate</p>
                                                                                    <h4 class="rate-percentage mb-0"
                                                                                        data-rt-stat="<?php echo $period['id']; ?>-turnout">
                                                                                        <?php echo $period['voter_turnout']; ?>%
                                                                                    </h4>
                                                                                    <p class="text-muted mb-0">Voted / Registered</p>
                                                                                </div>
                                                                                <div class="text-center">
                                                                                    <p class="statistics-title mb-1">Leading Party</p>
                                                                                    <h4 class="rate-percentage mb-0"
                                                                                        data-rt-stat="<?php echo $period['id']; ?>-leading">
                                                                                        <?php echo htmlspecialchars($period['leading_party']); ?>
                                                                                    </h4>
                                                                                    <p class="text-muted mb-0">Current leader</p>
                                                                                </div>
                                                                                <div class="text-center">
                                                                                    <p class="statistics-title mb-1">Available Votes</p>
                                                                                    <h4 class="rate-percentage mb-0"
                                                                                        data-rt-stat="<?php echo $period['id']; ?>-available">
                                                                                        <?php echo number_format($period['available_votes']); ?>
                                                                                    </h4>
                                                                                    <p class="text-muted mb-0">Remaining to vote</p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Turnout doughnut -->
                                                                    <div class="row mt-4">
                                                                        <div class="col-lg-6 offset-lg-3">
                                                                            <h5 class="text-center"><b>Voter Turnout</b></h5>
                                                                            <div style="height:300px;">
                                                                                <canvas id="turnoutChart-<?php echo $period['id']; ?>"></canvas>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Real-Time Tally -->
                                                                    <?php if (!empty($period['vote_data'])): ?>
                                                                        <div class="row mt-4">
                                                                            <div class="col-12">
                                                                                <div class="d-flex align-items-center mb-3">
                                                                                    <h5 class="mb-0">
                                                                                        <b>Real-Time Tally: <?php echo htmlspecialchars($period['name']); ?></b>
                                                                                        <span class="rt-live-badge">
                                                                                            <span class="dot"></span> LIVE
                                                                                        </span>
                                                                                        <span class="rt-updated-at"
                                                                                            id="rt-updated-<?php echo $period['id']; ?>"></span>
                                                                                    </h5>
                                                                                    <div class="ms-auto">
                                                                                        <button class="btn btn-primary btn-sm download-png text-white"
                                                                                            data-period-id="<?php echo $period['id']; ?>">
                                                                                            <i class="mdi mdi-download"></i> PNG
                                                                                        </button>
                                                                                        <button class="btn btn-primary btn-sm download-pdf text-white"
                                                                                            data-period-id="<?php echo $period['id']; ?>">
                                                                                            <i class="mdi mdi-file-pdf"></i> PDF
                                                                                        </button>
                                                                                    </div>
                                                                                </div>

                                                                                <!-- Position tabs -->
                                                                                <ul class="nav nav-tabs"
                                                                                    id="positionTabs-<?php echo $period['id']; ?>" role="tablist">
                                                                                    <li class="nav-item">
                                                                                        <a class="nav-link active"
                                                                                            id="all-tab-<?php echo $period['id']; ?>"
                                                                                            data-bs-toggle="tab"
                                                                                            href="#all-<?php echo $period['id']; ?>"
                                                                                            role="tab">All Positions</a>
                                                                                    </li>
                                                                                    <?php foreach ($period['positions'] as $posIndex => $position): ?>
                                                                                        <li class="nav-item">
                                                                                            <a class="nav-link"
                                                                                                id="tab-<?php echo $period['id']; ?>-<?php echo $posIndex; ?>"
                                                                                                data-bs-toggle="tab"
                                                                                                href="#position-<?php echo $period['id']; ?>-<?php echo $posIndex; ?>"
                                                                                                role="tab">
                                                                                                <?php echo htmlspecialchars($position['name']); ?>
                                                                                            </a>
                                                                                        </li>
                                                                                    <?php endforeach; ?>
                                                                                </ul>

                                                                                <div class="tab-content mt-3"
                                                                                    id="positionTabContent-<?php echo $period['id']; ?>">

                                                                                    <!-- All positions tab -->
                                                                                    <div class="tab-pane fade show active"
                                                                                        id="all-<?php echo $period['id']; ?>" role="tabpanel">
                                                                                        <div class="row">
                                                                                            <?php foreach ($period['vote_data'] as $positionName => $data): ?>
                                                                                                <div class="col-md-6 mb-4">
                                                                                                    <h6 class="text-center"><?php echo htmlspecialchars($positionName); ?></h6>
                                                                                                    <canvas id="chart_<?php echo $period['id']; ?>_<?php echo str_replace(' ', '_', $positionName); ?>"
                                                                                                        style="max-height:250px;"
                                                                                                        data-period-id="<?php echo $period['id']; ?>"
                                                                                                        data-position="<?php echo htmlspecialchars($positionName); ?>">
                                                                                                    </canvas>
                                                                                                </div>
                                                                                            <?php endforeach; ?>
                                                                                        </div>
                                                                                    </div>

                                                                                    <!-- Individual position tabs -->
                                                                                    <?php foreach ($period['positions'] as $posIndex => $position): ?>
                                                                                        <div class="tab-pane fade"
                                                                                            id="position-<?php echo $period['id']; ?>-<?php echo $posIndex; ?>"
                                                                                            role="tabpanel">
                                                                                            <div class="row">
                                                                                                <div class="col-12">
                                                                                                    <h6 class="text-center"><?php echo htmlspecialchars($position['name']); ?></h6>
                                                                                                    <canvas id="chart_<?php echo $period['id']; ?>_<?php echo str_replace(' ', '_', $position['name']); ?>_tab"
                                                                                                        style="max-height:300px;"
                                                                                                        data-period-id="<?php echo $period['id']; ?>"
                                                                                                        data-position="<?php echo htmlspecialchars($position['name']); ?>">
                                                                                                    </canvas>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    <?php endforeach; ?>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endif; ?>

                                                                <?php endif; /* end $isLive */ ?>
                                                            </div><!-- /card-body -->
                                                        </div><!-- /card -->
                                                    </div><!-- /col-12 -->

                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="col-12">
                                                    <div class="card card-rounded">
                                                        <div class="card-body text-center">
                                                            <h1 class="card-title card-title-dash">No Voting Period Available!</h1>
                                                            <p class="card-subtitle card-subtitle-dash">
                                                                There are currently no active or scheduled voting periods.
                                                                Please create or schedule a new election.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <!-- ═══════════ END VOTING PERIOD CARDS ═══════════ -->

                                        <!-- ═══════════ USER ACTIVITIES ═══════════ -->
                                        <?php
                                        try {
                                            $stmt = $pdo->prepare("
                                            SELECT
                                                u.email            AS email,
                                                u.role             AS role,
                                                a.action           AS action,
                                                a.timestamp        AS date_time,
                                                a.device_info      AS device_information,
                                                a.ip_address       AS ip_address,
                                                a.location         AS location,
                                                a.behavior_patterns AS behavior_patterns
                                            FROM users u
                                            JOIN user_activities a ON u.id = a.user_id
                                            ORDER BY a.timestamp ASC
                                        ");
                                            $stmt->execute();
                                            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        } catch (Exception $e) {
                                            $activities = [];
                                            error_log("Error fetching user activities: " . $e->getMessage());
                                        }
                                        ?>

                                        <div class="row mt-3">
                                            <div class="card px-5 pt-5">
                                                <div class="d-flex align-items-center">
                                                    <h3 class="ml-5"><b>User Activities</b></h3>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-bordered" id="userActivityTable">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>Email</th>
                                                                    <th>Role</th>
                                                                    <th>Action</th>
                                                                    <th>Date &amp; Time</th>
                                                                    <th>Device Information</th>
                                                                    <th>IP Address</th>
                                                                    <th>Location</th>
                                                                    <th>Behavior Patterns</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if (!empty($activities)): ?>
                                                                    <?php foreach ($activities as $activity): ?>
                                                                        <tr>
                                                                            <td><?php echo htmlspecialchars($activity['email']); ?></td>
                                                                            <td>
                                                                                <?php
                                                                                switch (strtolower($activity['role'])) {
                                                                                    case 'admin':
                                                                                        echo '<span class="badge bg-danger">Admin</span>';
                                                                                        break;
                                                                                    case 'moderator':
                                                                                        echo '<span class="badge bg-primary">Moderator</span>';
                                                                                        break;
                                                                                    case 'voter':
                                                                                        echo '<span class="badge bg-success">Voter</span>';
                                                                                        break;
                                                                                    case 'adviser':
                                                                                        echo '<span class="badge bg-warning">Adviser</span>';
                                                                                        break;
                                                                                    default:
                                                                                        echo htmlspecialchars($activity['role']);
                                                                                }
                                                                                ?>
                                                                            </td>
                                                                            <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                                                            <td><?php echo htmlspecialchars(date('Y-m-d h:i A', strtotime($activity['date_time']))); ?></td>
                                                                            <td><?php echo htmlspecialchars($activity['device_information']); ?></td>
                                                                            <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                                                                            <td><?php echo htmlspecialchars($activity['location']); ?></td>
                                                                            <td><?php echo htmlspecialchars($activity['behavior_patterns']); ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <tr>
                                                                        <td colspan="8" class="text-center">No activities found.</td>
                                                                    </tr>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- ═══════════ END USER ACTIVITIES ═══════════ -->

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /container-scroller -->

    <!-- Back-to-top button -->
    <button class="back-to-top" id="backToTop" title="Go to top">
        <i class="mdi mdi-arrow-up"></i>
    </button>

    <!-- ═══════════════════════════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════════════════════════ -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script src="vendors/progressbar.js/progressbar.min.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/hoverable-collapse.js"></script>
    <script src="js/template.js"></script>
    <script src="js/settings.js"></script>
    <script src="js/todolist.js"></script>
    <script src="js/dashboard.js"></script>
    <script src="js/Chart.roundedBarCharts.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- DataTable init -->
    <script>
        $(document).ready(function() {
            $('#userActivityTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                order: [
                    [0, 'desc']
                ]
            });
        });
    </script>

    <!-- Toggle elections / Back-to-top / SweetAlert -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /* ── Show More / Show Less elections ── */
            const toggleBtn = document.getElementById('toggleElections');
            const moreDiv = document.getElementById('moreElections');
            if (toggleBtn && moreDiv) {
                toggleBtn.addEventListener('click', function() {
                    const hidden = moreDiv.style.display === 'none';
                    moreDiv.style.display = hidden ? 'block' : 'none';
                    toggleBtn.textContent = hidden ? 'Show Less' : 'Show More';
                });
            }

            /* ── Back to Top ── */
            const backToTop = document.getElementById('backToTop');
            if (backToTop) {
                window.addEventListener('scroll', function() {
                    backToTop.classList.toggle('show', window.pageYOffset > 200);
                });
                backToTop.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }

            /* ── SweetAlert voting reminders ── */
            const daysUntilStart = <?php echo json_encode($daysUntilStart); ?>;
            const votingPeriodName = <?php echo json_encode($votingPeriodName); ?>;
            const votingStartDateFmt = <?php echo json_encode($votingPeriodStartDate ? date('F j, Y', strtotime($votingPeriodStartDate)) : 'N/A'); ?>;
            const vpStatus = <?php echo json_encode($votingPeriodStatus); ?>;

            if (daysUntilStart !== null && vpStatus === 'Scheduled') {
                const alerts = {
                    30: {
                        icon: 'info',
                        title: 'Voting Period Approaching',
                        text: `The voting period "${votingPeriodName}" is 1 month away! It starts on ${votingStartDateFmt}.`
                    },
                    15: {
                        icon: 'info',
                        title: 'Voting Period Approaching',
                        text: `The voting period "${votingPeriodName}" is 15 days away! It starts on ${votingStartDateFmt}.`
                    },
                    3: {
                        icon: 'warning',
                        title: 'Voting Period Nearing',
                        text: `The voting period "${votingPeriodName}" is 3 days away! It starts on ${votingStartDateFmt}.`
                    },
                    1: {
                        icon: 'warning',
                        title: 'Voting Period Tomorrow',
                        text: `The voting period "${votingPeriodName}" is 1 day away! It starts on ${votingStartDateFmt}.`
                    },
                };
                if (alerts[daysUntilStart]) {
                    Swal.fire({
                        ...alerts[daysUntilStart],
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    </script>

    <!-- Logout with geolocation -->
    <script>
        document.getElementById('logoutLink').addEventListener('click', function(e) {
            e.preventDefault();
            const go = loc => window.location.href = `processes/accounts/logout.php?location=${encodeURIComponent(loc)}`;
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    pos => go(`${pos.coords.latitude},${pos.coords.longitude}`),
                    () => go('N/A'), {
                        timeout: 10000
                    }
                );
            } else {
                go('N/A');
            }
        });
    </script>

    <!-- ═══════════════════════════════════════════════════════════
     REAL-TIME CHART ENGINE
═══════════════════════════════════════════════════════════ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /* ── Config ── */
            const POLL_MS = 5000; // poll every 5 s — change freely
            const API_URL = 'api_realtime_votes.php'; // must be in the same admin/ folder

            /* ── State ── */
            const allCharts = {}; // canvasId  → Chart.js instance
            const lastVoteMap = {}; // canvasId  → previous vote array (for flash detection)

            /* ── Helpers ── */
            const fmt = n => n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            const trunc = (s, max) => s.length > max ? s.slice(0, max - 3) + '…' : s;

            const hsl = (i, n) => `hsl(${Math.round(i * 360 / Math.max(n,1))},70%,60%)`;
            const hslDark = (i, n) => `hsl(${Math.round(i * 360 / Math.max(n,1))},70%,40%)`;
            const hslLight = (i, n) => `hsl(${Math.round(i * 360 / Math.max(n,1))},70%,70%)`;

            /* ────────────────────────────────────────────────────────
               BUILD BAR CHART (create from scratch)
            ──────────────────────────────────────────────────────── */
            function buildBarChart(canvasId, position, data, isTabChart) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;
                if (allCharts[canvasId]) allCharts[canvasId].destroy();

                const cands = data.candidates || [];
                const n = cands.length;
                const labels = n ? cands.map(c => trunc(c.name, isTabChart ? 30 : 20)) : ['No candidates'];
                const votes = n ? cands.map(c => Number(c.vote_count) || 0) : [0];
                const details = n ?
                    cands.map(c => ({
                        name: c.name,
                        party: c.party_name || 'Independent',
                        college: c.college || '',
                        department: c.department || ''
                    })) : [{
                        name: 'N/A',
                        party: 'N/A',
                        college: 'N/A',
                        department: 'N/A'
                    }];

                lastVoteMap[canvasId] = [...votes];

                allCharts[canvasId] = new Chart(canvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Votes',
                            data: votes,
                            backgroundColor: n ? cands.map((_, i) => hsl(i, n)) : ['#ccc'],
                            borderColor: n ? cands.map((_, i) => hslDark(i, n)) : ['#999'],
                            hoverBackgroundColor: n ? cands.map((_, i) => hslLight(i, n)) : ['#bbb'],
                            borderWidth: 2,
                            borderRadius: 8,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 600,
                            easing: 'easeOutQuart'
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Votes',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    },
                                    color: '#333'
                                },
                                ticks: {
                                    stepSize: 1,
                                    callback: v => Number.isInteger(v) ? v : null,
                                    font: {
                                        size: 11
                                    },
                                    color: '#555'
                                },
                                grid: {
                                    color: 'rgba(0,0,0,.1)'
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: isTabChart ? 12 : 10,
                                        weight: '500'
                                    },
                                    color: '#333',
                                    autoSkip: false,
                                    maxRotation: isTabChart ? 45 : 0,
                                    minRotation: isTabChart ? 45 : 0
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            title: {
                                display: isTabChart,
                                text: position,
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                },
                                color: '#2c3e50',
                                padding: {
                                    top: 10,
                                    bottom: 20
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,.8)',
                                titleFont: {
                                    size: 12,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 11
                                },
                                padding: 8,
                                cornerRadius: 6,
                                callbacks: {
                                    title: ctx => details[ctx[0].dataIndex].name,
                                    label: ctx => {
                                        const d = details[ctx.dataIndex];
                                        return [`Party: ${d.party}`, `Votes: ${fmt(votes[ctx.dataIndex])}`, `College: ${d.college||'N/A'}`, `Department: ${d.department||'N/A'}`];
                                    }
                                }
                            },
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }

            /* ────────────────────────────────────────────────────────
               BUILD TURNOUT DOUGHNUT
            ──────────────────────────────────────────────────────── */
            function buildTurnoutChart(canvasId, voted, available) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;
                if (allCharts[canvasId]) allCharts[canvasId].destroy();

                allCharts[canvasId] = new Chart(canvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Voted', 'Not Voted'],
                        datasets: [{
                            data: [voted, Math.max(available, 0)],
                            backgroundColor: ['#28a745', '#dc3545'],
                            hoverBackgroundColor: ['#218838', '#c82333'],
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 600
                        },
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => {
                                        const total = ctx.chart._metasets[ctx.datasetIndex].total;
                                        return `${ctx.label}: ${fmt(ctx.raw)} (${Math.round(ctx.raw/total*100)}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            /* ────────────────────────────────────────────────────────
               FLASH animation when a bar's value changes
            ──────────────────────────────────────────────────────── */
            function flashCanvas(canvasId) {
                const canvas = document.getElementById(canvasId);
                const wrapper = canvas?.closest('.col-md-6, .col-12');
                if (!wrapper) return;
                wrapper.style.position = 'relative';
                const o = document.createElement('div');
                o.className = 'rt-flash-overlay';
                wrapper.appendChild(o);
                o.addEventListener('animationend', () => o.remove());
            }

            /* ────────────────────────────────────────────────────────
               PATCH BAR CHART (smooth update, no full rebuild)
               Returns false if chart doesn't exist yet (caller must build)
            ──────────────────────────────────────────────────────── */
            function patchBarChart(canvasId, data) {
                const chart = allCharts[canvasId];
                if (!chart) return false;

                const newVotes = (data.candidates || []).map(c => Number(c.vote_count) || 0);
                const oldVotes = lastVoteMap[canvasId] || [];
                const changed = newVotes.some((v, i) => v !== (oldVotes[i] ?? -1));
                if (!changed) return true;

                chart.data.datasets[0].data = newVotes;
                chart.update('active');
                flashCanvas(canvasId);
                lastVoteMap[canvasId] = [...newVotes];
                return true;
            }

            /* ────────────────────────────────────────────────────────
               PATCH TURNOUT DOUGHNUT
            ──────────────────────────────────────────────────────── */
            function patchTurnoutChart(canvasId, voted, available) {
                const chart = allCharts[canvasId];
                if (!chart) return false;
                chart.data.datasets[0].data = [voted, Math.max(available, 0)];
                chart.update('active');
                return true;
            }

            /* ────────────────────────────────────────────────────────
               UPDATE STAT CARD VALUE with pop animation
            ──────────────────────────────────────────────────────── */
            function updateStatCard(periodId, key, value) {
                const el = document.querySelector(`[data-rt-stat="${periodId}-${key}"]`);
                if (!el) return;
                const display = (typeof value === 'number') ? fmt(value) : String(value);
                if (el.textContent.trim() === display) return;
                el.textContent = display;
                el.classList.remove('rt-stat-highlight');
                void el.offsetWidth; // force reflow
                el.classList.add('rt-stat-highlight');
            }

            /* ────────────────────────────────────────────────────────
               RENDER ALL CHARTS for a period (initial page load)
            ──────────────────────────────────────────────────────── */
            function renderChartsForPeriod(periodId, voteData) {
                // "All positions" overview tab
                Object.keys(voteData).forEach(pos => {
                    buildBarChart(`chart_${periodId}_${pos.replace(/ /g,'_')}`, pos, voteData[pos], false);
                });
                // Individual position tabs
                Object.keys(voteData).forEach(pos => {
                    buildBarChart(`chart_${periodId}_${pos.replace(/ /g,'_')}_tab`, pos, voteData[pos], true);
                });
                // Turnout doughnut
                const p = votingPeriodsById[periodId];
                if (p) buildTurnoutChart(`turnoutChart-${periodId}`, p.total_votes_cast, p.available_votes);
            }

            /* ────────────────────────────────────────────────────────
               APPLY FRESH DATA from the polling API
            ──────────────────────────────────────────────────────── */
            function applyUpdate(periodId, fresh) {
                // 1. Turnout doughnut
                const tId = `turnoutChart-${periodId}`;
                if (!patchTurnoutChart(tId, fresh.total_votes_cast, fresh.available_votes)) {
                    buildTurnoutChart(tId, fresh.total_votes_cast, fresh.available_votes);
                }

                // 2. Bar charts
                Object.keys(fresh.vote_data).forEach(pos => {
                    const data = fresh.vote_data[pos];
                    const allId = `chart_${periodId}_${pos.replace(/ /g,'_')}`;
                    const tabId = `chart_${periodId}_${pos.replace(/ /g,'_')}_tab`;
                    if (!patchBarChart(allId, data)) buildBarChart(allId, pos, data, false);
                    if (!patchBarChart(tabId, data)) buildBarChart(tabId, pos, data, true);
                });

                // 3. Stat cards
                updateStatCard(periodId, 'total', fresh.total_votes_cast);
                updateStatCard(periodId, 'registered', fresh.registered_voters);
                updateStatCard(periodId, 'turnout', fresh.voter_turnout + '%');
                updateStatCard(periodId, 'leading', fresh.leading_party);
                updateStatCard(periodId, 'available', fresh.available_votes);

                // 4. Refresh in-memory cache
                if (votingPeriodsById[periodId]) {
                    Object.assign(votingPeriodsById[periodId], {
                        total_votes_cast: fresh.total_votes_cast,
                        registered_voters: fresh.registered_voters,
                        voter_turnout: fresh.voter_turnout,
                        leading_party: fresh.leading_party,
                        available_votes: fresh.available_votes,
                    });
                }

                // 5. Timestamp badge
                const ts = document.getElementById(`rt-updated-${periodId}`);
                if (ts) ts.textContent = `Updated ${new Date().toLocaleTimeString()}`;
            }

            /* ────────────────────────────────────────────────────────
               POLLING LOOP
            ──────────────────────────────────────────────────────── */
            function getActivePeriodIds() {
                return votingPeriods
                    .filter(p => p.status === 'Ongoing' || p.status === 'Paused')
                    .map(p => p.id);
            }

            function poll() {
                const ids = getActivePeriodIds();
                if (!ids.length) return;

                fetch(`${API_URL}?period_ids=${ids.join(',')}`)
                    .then(r => r.ok ? r.json() : Promise.reject(r.status))
                    .then(data => {
                        if (data.error) {
                            console.warn('RT API:', data.error);
                            return;
                        }
                        Object.keys(data).forEach(id => applyUpdate(Number(id), data[id]));
                    })
                    .catch(err => console.warn('RT poll failed:', err));
            }

            /* ────────────────────────────────────────────────────────
               TAB-SWITCH → resize charts that just became visible
            ──────────────────────────────────────────────────────── */
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
                tab.addEventListener('shown.bs.tab', () => {
                    Object.keys(allCharts).forEach(id => {
                        const el = document.getElementById(id);
                        if (el && el.closest('.tab-pane.active')) allCharts[id].resize();
                    });
                });
            });

            /* ────────────────────────────────────────────────────────
               DOWNLOAD HELPERS
            ──────────────────────────────────────────────────────── */
            document.querySelectorAll('.download-png').forEach(btn =>
                btn.addEventListener('click', () => downloadAsPNG(btn.dataset.periodId)));
            document.querySelectorAll('.download-pdf').forEach(btn =>
                btn.addEventListener('click', () => downloadAsPDF(btn.dataset.periodId)));

            function headerMeta(periodId) {
                const p = votingPeriodsById[periodId];
                const opts = {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                };
                let vp = 'N/A';
                if (p?.start_date && p?.end_date) {
                    vp = `${new Date(p.start_date).toLocaleString('en-US',opts)} to ${new Date(p.end_date).toLocaleString('en-US',opts)}`;
                }
                return {
                    name: p?.name || 'N/A',
                    semester: p?.semester || 'N/A',
                    vp
                };
            }

            function loadLogos(cb) {
                const l = new Image(),
                    r = new Image();
                let done = 0;
                const ready = () => {
                    if (++done === 2) cb(l, r);
                };
                l.onload = ready;
                r.onload = ready;
                l.src = 'images/wmsu_logo.png';
                r.src = 'images/osa_logo.png';
            }

            function downloadAsPNG(periodId) {
                const {
                    name,
                    semester,
                    vp
                } = headerMeta(periodId);
                loadLogos((lL, lR) => {
                    Object.keys(allCharts).forEach(chartId => {
                        if (!chartId.startsWith(`chart_${periodId}_`) || chartId.includes('_tab') || chartId.includes('turnout')) return;
                        const ci = new Image();
                        ci.src = allCharts[chartId].toBase64Image();
                        ci.onload = () => {
                            const cv = document.createElement('canvas');
                            const ctx = cv.getContext('2d');
                            const HDR = 90;
                            cv.width = ci.width;
                            cv.height = ci.height + HDR;
                            ctx.fillStyle = '#fff';
                            ctx.fillRect(0, 0, cv.width, cv.height);
                            ctx.drawImage(lL, 10, 10, 60, 60);
                            ctx.drawImage(lR, cv.width - 70, 10, 60, 60);
                            ctx.fillStyle = '#000';
                            ctx.font = 'bold 18px Arial';
                            ctx.textAlign = 'center';
                            ctx.fillText('Western Mindanao State University', cv.width / 2, 28);
                            ctx.font = '14px Arial';
                            ctx.fillText(`Election: ${name}`, cv.width / 2, 46);
                            ctx.fillText(`Semester: ${semester}`, cv.width / 2, 62);
                            ctx.fillText(`Voting Period: ${vp}`, cv.width / 2, 78);
                            ctx.drawImage(ci, 0, HDR);
                            const a = document.createElement('a');
                            a.download = `${chartId}.png`;
                            a.href = cv.toDataURL('image/png');
                            a.click();
                        };
                    });
                });
            }

            function downloadAsPDF(periodId) {
                const {
                    jsPDF
                } = window.jspdf;
                const doc = new jsPDF('p', 'mm', 'a4');
                const {
                    name,
                    semester,
                    vp
                } = headerMeta(periodId);
                loadLogos((lL, lR) => {
                    const pw = doc.internal.pageSize.getWidth();
                    let y = 10;
                    doc.addImage(lL, 'PNG', 10, y, 20, 20);
                    doc.addImage(lR, 'PNG', pw - 30, y, 20, 20);
                    y += 5;
                    doc.setFontSize(14);
                    doc.setFont('helvetica', 'bold');
                    doc.text('Western Mindanao State University', pw / 2, y + 7, {
                        align: 'center'
                    });
                    doc.setFontSize(12);
                    doc.setFont('helvetica', 'normal');
                    doc.text(`Election: ${name}`, pw / 2, y + 15, {
                        align: 'center'
                    });
                    doc.text(`Semester: ${semester}`, pw / 2, y + 22, {
                        align: 'center'
                    });
                    doc.text(`Voting Period: ${vp}`, pw / 2, y + 29, {
                        align: 'center'
                    });
                    y += 40;
                    Object.keys(allCharts).forEach(chartId => {
                        if (!chartId.startsWith(`chart_${periodId}_`) || chartId.includes('_tab') || chartId.includes('turnout')) return;
                        if (y > 250) {
                            doc.addPage();
                            y = 10;
                        }
                        const pos = chartId.replace(`chart_${periodId}_`, '').replace(/_/g, ' ');
                        doc.setFontSize(11);
                        doc.text(`Position: ${pos}`, 10, y);
                        y += 5;
                        doc.addImage(allCharts[chartId].toBase64Image(), 'PNG', 10, y, 190, 100);
                        y += 105;
                    });
                    doc.save(`vote_tally_period_${periodId}.pdf`);
                });
            }

            /* ────────────────────────────────────────────────────────
               BOOT — initial render then start polling
            ──────────────────────────────────────────────────────── */
            <?php if (!empty($votingPeriodData)): ?>
                <?php foreach ($votingPeriodData as $period): ?>
                    <?php if (in_array($period['status'], ['Ongoing', 'Paused']) && !empty($period['vote_data'])): ?>
                        renderChartsForPeriod(
                            <?php echo (int)$period['id']; ?>,
                            <?php echo json_encode($period['vote_data']); ?>
                        );
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            setInterval(poll, POLL_MS);
            setTimeout(poll, 1500); // first poll shortly after render
        });
    </script>

</body>

</html>

<?php include('includes/alerts.php'); ?>