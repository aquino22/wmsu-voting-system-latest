<?php
session_start();
include('includes/conn.php');

$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : null;
$student_id = null;

if ($user_email) {
    $stmt = $pdo->prepare("SELECT student_id, first_name, middle_name, last_name FROM voters WHERE email = ?");
    $stmt->execute([$user_email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $student_id          = $result['student_id'];
        $_SESSION['user_id'] = $student_id;
        $first_name_student  = $result['first_name'];
        $last_name_student   = $result['last_name'];
    }
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'voter') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = "NON_VOTER";
    header("Location: ../index.php");
    exit();
}

// ── Handle withdraw ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'withdraw') {
    $candidate_id = intval($_POST['candidate_id'] ?? 0);
    if ($candidate_id > 0) {
        $chk = $pdo->prepare("
            SELECT c.id, c.admin_config
            FROM candidates c
            JOIN candidate_responses cr ON cr.candidate_id = c.id
            JOIN form_fields ff         ON cr.field_id = ff.id
            WHERE c.id = ? AND ff.field_name = 'student_id' AND cr.value = ?
            LIMIT 1
        ");
        $chk->execute([$candidate_id, $student_id]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);

        if ($row && (int)$row['admin_config'] === 0) {
            $pdo->prepare("DELETE FROM candidate_responses WHERE candidate_id = ?")->execute([$candidate_id]);
            $pdo->prepare("DELETE FROM candidate_files     WHERE candidate_id = ?")->execute([$candidate_id]);
            $pdo->prepare("DELETE FROM candidates          WHERE id = ?")->execute([$candidate_id]);
            $_SESSION['STATUS'] = 'WITHDRAW_SUCCESS';
        } else {
            $_SESSION['STATUS'] = 'WITHDRAW_DENIED';
        }
    }
    header("Location: candidacy.php?event_id=" . intval($_GET['event_id'] ?? 0));
    exit;
}

// ── Fetch event / form / candidacy data ──────────────────────────────────────
$event_id    = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$canRegister = false;
$hasFiled    = false;
$existingCandidate = null;
$candidateStatus   = '';
$filedResponses    = [];
$fileResponses     = [];
$voters = $fields = $parties = [];
$form_id = $form_name = $candidacy_name = null;
$candidacy = null;
$error_message = null;

try {
    if ($event_id <= 0) throw new Exception("Invalid event ID");

    $event_stmt = $pdo->prepare("SELECT candidacy, registration_deadline FROM events WHERE id = ? AND status = 'published' AND registration_enabled = 1");
    $event_stmt->execute([$event_id]);
    $event = $event_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$event || empty($event['candidacy'])) throw new Exception("Event not found or registration not enabled");

    $canRegister = (new DateTime()) <= (new DateTime($event['registration_deadline']));

    if ($canRegister) {
        $candidacy = $event['candidacy']; // election id (int stored as candidacy)

        // Election name
        $en = $pdo->prepare("SELECT election_name FROM elections WHERE id = ?");
        $en->execute([$candidacy]);
        $enRow = $en->fetch(PDO::FETCH_ASSOC);
        if (!$enRow) throw new Exception("No election found");
        $candidacy_name = $enRow['election_name'];

        // Registration form
        $fs = $pdo->prepare("SELECT id, form_name FROM registration_forms WHERE election_name = ? AND status = 'active'");
        $fs->execute([$candidacy]);
        $form = $fs->fetch(PDO::FETCH_ASSOC);
        if (!$form) throw new Exception("No active registration form found");
        $form_id   = $form['id'];
        $form_name = $form['form_name'];

        // Already filed?
        if ($student_id) {
            $chkStmt = $pdo->prepare("
                SELECT c.id, c.status, c.created_at, c.admin_config
                FROM candidates c
                JOIN candidate_responses cr ON c.id = cr.candidate_id
                JOIN form_fields ff ON cr.field_id = ff.id
                WHERE c.form_id = ? AND ff.field_name = 'student_id' AND cr.value = ?
                LIMIT 1
            ");
            $chkStmt->execute([$form_id, $student_id]);
            $existingCandidate = $chkStmt->fetch(PDO::FETCH_ASSOC);
            if ($existingCandidate) {
                $hasFiled        = true;
                $candidateStatus = $existingCandidate['status'];
                $respStmt = $pdo->prepare("SELECT ff.field_name, cr.value FROM candidate_responses cr JOIN form_fields ff ON cr.field_id = ff.id WHERE cr.candidate_id = ?");
                $respStmt->execute([$existingCandidate['id']]);
                $filedResponses = $respStmt->fetchAll(PDO::FETCH_KEY_PAIR);
                $fileStmt = $pdo->prepare("SELECT ff.field_name, cf.file_path FROM candidate_files cf JOIN form_fields ff ON cf.field_id = ff.id WHERE cf.candidate_id = ?");
                $fileStmt->execute([$existingCandidate['id']]);
                $fileResponses = $fileStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            }
        }

        // Form fields
        $flds = $pdo->prepare("SELECT * FROM form_fields WHERE form_id = ? ORDER BY id ASC");
        $flds->execute([$form_id]);
        $fields = $flds->fetchAll(PDO::FETCH_ASSOC);

        // Parties
        $ps = $pdo->prepare("SELECT name FROM parties WHERE election_id = ? AND status = 'Approved' ORDER BY name");
        $ps->execute([$candidacy]);
        $parties = $ps->fetchAll(PDO::FETCH_ASSOC);

        // Eligible voters (confirmed, not already a candidate for this election)
        $vs = $pdo->prepare("
            SELECT v.id, v.student_id,
                   TRIM(CONCAT(v.first_name,' ',COALESCE(v.middle_name,''),' ',v.last_name)) AS full_name
            FROM voters v
            LEFT JOIN candidates c ON c.id = (
                SELECT c2.id FROM candidates c2
                JOIN registration_forms rf2 ON c2.form_id = rf2.id
                JOIN candidate_responses cr2 ON cr2.candidate_id = c2.id
                JOIN form_fields ff2 ON cr2.field_id = ff2.id
                WHERE ff2.field_name = 'student_id' AND cr2.value = v.student_id AND rf2.election_name = ?
                LIMIT 1
            )
            WHERE v.status = 'confirmed' AND v.student_id = ? AND c.id IS NULL
        ");
        $vs->execute([$candidacy, $student_id]);
        $voters = $vs->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>WMSU I-Elect – File Candidacy</title>
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/typicons/typicons.css">
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" href="js/select.dataTables.min.css">
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <link rel="stylesheet" type="text/css" href="../login/css/util.css">
    <link rel="stylesheet" type="text/css" href="../login/css/main.css">
    <link rel="shortcut icon" href="images/favicon.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .input100 {
            border: 1px solid lightgray !important;
            border-radius: 4px;
        }

        .input100:focus,
        .input100:hover,
        .input100:active {
            border: 1px solid lightgray !important;
            outline: none;
            box-shadow: none;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .election-card {
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .election-card:hover {
            border-color: #B22222;
            transform: translateY(-5px);
        }

        .election-card.selected {
            border-color: #B22222;
            background-color: #f8f9fa;
        }

        .election-status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.8rem;
        }

        .countdown-container {
            background-color: #fff;
            padding: 10px 20px;
            border-radius: 50px;
            border: 1px solid #dee2e6;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .timer-input {
            border: none;
            background: transparent;
            font-weight: bold;
            color: #B22222;
            font-size: 0.8rem;
            width: 100px;
            text-align: center;
        }

        .election-status {
            background: linear-gradient(45deg, #B22222, #ff5a5f);
            color: white;
            padding: 4px 15px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-left: 15px;
        }

        .profiler {
            max-width: 150px;
            height: auto;
            border-radius: 50%;
        }

        .bordered {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }

        .spacer {
            margin-right: 10px;
        }

        .navbar {
            background: linear-gradient(to right, #950000, #B22222);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav .nav-item.active-link>.nav-link {
            background-color: #B22222 !important;
            color: white !important;
        }

        .voter-status {
            padding: 10px;
            border-radius: 8px;
            font-weight: 500;
        }

        .voter-status.verified {
            background: #d4edda;
            color: #155724;
        }

        .voter-status.voted {
            background: #e2e3e5;
            color: #41464b;
        }

        .action-btn {
            font-size: 1.1rem;
            padding: 10px 20px;
        }

        .candidate-container {
            position: relative;
            min-height: 200px;
        }

        .blur-overlay {
            filter: blur(8px);
            pointer-events: none;
        }

        .overlay-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 20px 40px;
            border-radius: 10px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            z-index: 10;
            max-width: 80%;
        }

        .election-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .vote-btn {
            background: linear-gradient(45deg, #B22222, #ff5a5f);
            border: none;
            padding: 10px 25px;
            font-weight: bold;
        }

        .vote-btn:hover {
            background: linear-gradient(45deg, #950000, #B22222);
            transform: translateY(-2px);
        }

        .election-info {
            background: linear-gradient(135deg, #950000 0%, #FFFFFFFF 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .field-container {
            margin: 15px 0;
        }

        .required::after {
            content: '*';
            color: red;
            margin-left: 5px;
        }

        .error {
            color: red;
        }

        .custom-file {
            margin-top: 10px;
        }

        input[type="file"] {
            background-color: transparent;
            width: 100%;
            cursor: pointer;
        }

        .custom-file-input {
            display: none;
        }

        .file-label {
            background-color: #4CAF50;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .file-label:hover {
            background-color: #45a049;
        }

        .custom-file {
            display: block;


        }

        .card {
            background-color: white !important;
            color: black !important;
        }

        label {
            color: black !important;
        }

        input {
            color: black !important;
        }

        select {
            color: black !important;
        }

        .file-preview {
            display: block;
        }

        /* .input100:active {
        border-bottom: 1px solid black !important;
    } */

        .custom-searchable-select {
            position: relative;
            width: 100%;
        }

        .search-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 5px;
            box-sizing: border-box;
        }

        .custom-searchable {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            min-height: 38px;
            cursor: text;
        }

        .custom-searchable.placeholder {
            color: #999;
        }

        .custom-searchable:focus {
            outline: none;
            border-color: #666;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .hidden-select {
            display: none;
        }

        .dropdown-options {
            display: none;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
        }

        .dropdown-options div {
            padding: 8px 10px;
            cursor: pointer;
        }

        .dropdown-options div:hover {
            background: #f0f0f0;
        }
    </style>
</head>

<body>
    <div class="container-scroller">
        <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
                <div class="me-3"><button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize"><span class="icon-menu"></span></button></div>
                <div>
                    <a class="navbar-brand brand-logo" href="index.php">
                        <img src="images/wmsu-logo.png" alt="logo" class="logo img-fluid" />
                        <small style="font-size:16px;"><b>WMSU I-Elect</b></small>
                    </a>
                </div>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-top">
                <ul class="navbar-nav">
                    <li class="nav-item font-weight-semibold d-none d-lg-block ms-0">
                        <h1 class="welcome-text">Welcome, <span class="text-white fw-bold"><?= htmlspecialchars(($first_name_student ?? '') . ' ' . ($last_name_student ?? '')) ?></span></h1>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                        <a class="nav-link" href="#" data-bs-toggle="dropdown">
                            <img class="img-xs rounded-circle logo" src="images/wmsu-logo.png" style="background-color:white;" alt="Profile">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown">
                            <a class="dropdown-item" href="processes/accounts/logout.php"><i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign Out</a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="container-fluid page-body-wrapper">
            <nav class="sidebar sidebar-offcanvas" id="sidebar">
                <ul class="nav">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="mdi mdi-grid-large menu-icon"></i><span class="menu-title">Home</span></a></li>
                    <li class="nav-item active-link">
                        <a class="nav-link" href="candidacy.php" style="background-color:#B22222 !important;">
                            <i class="mdi mdi-account menu-icon" style="color:white !important;"></i>
                            <span class="menu-title" style="color:white !important;">File Candidacy</span>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="choose_voting.php"><i class="menu-icon mdi mdi-account-group"></i><span class="menu-title">Vote</span></a></li>
                </ul>
            </nav>

            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item"><a class="nav-link active ps-0" data-bs-toggle="tab" href="#overview" role="tab">Filing for Candidacy</a></li>
                                    </ul>
                                </div>
                                <div class="tab-content tab-content-basic">
                                    <div class="tab-pane fade show active" id="overview" role="tabpanel">
                                        <div class="container-fluid mt-5" style="max-width:1000px">

                                            <?php if ($canRegister): ?>
                                                <h2 class="text-center text-light m-2 mb-4">Filing of Candidacy for <?= htmlspecialchars($candidacy_name ?? '') ?></h2>
                                                <div class="card mx-auto">
                                                    <div class="card-body p-4">
                                                        <h4 class="text-center mb-4"><?= htmlspecialchars($form_name ?? '') ?></h4>

                                                        <?php if ($hasFiled): ?>
                                                            <!-- ── Already filed ── -->
                                                            <?php if (!empty($existingCandidate['admin_config']) && $existingCandidate['admin_config'] == 1): ?>
                                                                <div class="alert alert-warning text-center">
                                                                    <strong>You were added as a candidate by the admin.</strong>
                                                                </div>
                                                            <?php endif; ?>

                                                            <div class="alert alert-info text-center">
                                                                <h4 class="mb-2">You have already filed your candidacy.</h4>
                                                                <p class="mb-1"><strong>Status:</strong>
                                                                    <span class="badge <?= $candidateStatus === 'accepted' ? 'bg-success' : ($candidateStatus === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                                                        <?= ucfirst(htmlspecialchars($candidateStatus)) ?>
                                                                    </span>
                                                                </p>
                                                                <p class="mb-0"><strong>Date Filed:</strong> <?= date('F j, Y g:i A', strtotime($existingCandidate['created_at'])) ?></p>
                                                            </div>

                                                            <!-- ── Withdraw — only if self-filed ── -->
                                                            <!-- <?php if (empty($existingCandidate['admin_config']) || (int)$existingCandidate['admin_config'] === 0): ?>
                                                                <div class="text-center my-3">
                                                                    <button type="button" class="btn btn-outline-danger withdraw-btn px-4"
                                                                        onclick="confirmWithdraw(<?= (int)$existingCandidate['id'] ?>)">
                                                                        <i class="mdi mdi-account-remove me-1"></i>Withdraw My Candidacy
                                                                    </button>
                                                                    <p class="text-muted small mt-1">Withdrawing will permanently remove your filing.</p>
                                                                </div>
                                                                <!-- Hidden form submitted by JS -->
                                                            <form id="withdrawForm" method="POST" action="candidacy.php?event_id=<?= $event_id ?>">
                                                                <input type="hidden" name="action" value="withdraw">
                                                                <input type="hidden" name="candidate_id" value="<?= (int)$existingCandidate['id'] ?>">
                                                            </form>
                                                        <?php endif; ?> -->

                                                        <!-- Submitted info summary -->
                                                        <div class="mt-4">
                                                            <h5>Submitted Information</h5>
                                                            <ul class="list-group">
                                                                <?php foreach ($fields as $field):
                                                                    $fn    = $field['field_name'];
                                                                    $val   = $filedResponses[$fn] ?? null;
                                                                    $fval  = $fileResponses[$fn]  ?? null;
                                                                    $label = $fn === 'full_name' ? 'Full Name' : ($fn === 'student_id' ? 'Student ID' : ucfirst(str_replace('_', ' ', $fn)));
                                                                ?>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                        <strong><?= htmlspecialchars($label) ?></strong>
                                                                        <span>
                                                                            <?php if ($field['field_type'] === 'file' || $fn === 'picture'): ?>
                                                                                <?php if (!empty($fval)): ?>
                                                                                    <button class="btn btn-sm btn-success view-file-btn"
                                                                                        data-bs-toggle="modal" data-bs-target="#filePreviewModal"
                                                                                        data-file="<?= htmlspecialchars($fval) ?>">View File</button>
                                                                                <?php else: ?><span class="text-muted">No file</span><?php endif; ?>
                                                                            <?php else: ?>
                                                                                <?= htmlspecialchars($val ?? 'N/A') ?>
                                                                            <?php endif; ?>
                                                                        </span>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>

                                                    <?php else: ?>
                                                        <!-- ── Registration form ── -->
                                                        <form method="POST" action="processes/register.php?form_id=<?= $form_id ?>&event_id=<?= $event_id ?>" id="registrationForm" enctype="multipart/form-data">
                                                            <input type="hidden" name="form_id" value="<?= $form_id ?>">
                                                            <input type="hidden" name="event_id" value="<?= $event_id ?>">
                                                            <input type="hidden" id="lat" name="lat">
                                                            <input type="hidden" id="lon" name="lon">
                                                            <p class="text-muted small">Accepted file types: PDF, DOCX, JPG, PNG</p>

                                                            <?php foreach ($fields as $field): ?>
                                                                <div class="field-container mt-3">
                                                                    <label class="<?= $field['is_required'] ? 'required' : '' ?>">
                                                                        <?= $field['field_name'] === 'full_name' ? 'Full Name' : ($field['field_name'] === 'student_id' ? 'Student ID' : ucfirst(htmlspecialchars($field['field_name']))) ?>
                                                                    </label>
                                                                    <?php switch ($field['field_name']):
                                                                        case 'full_name': ?>
                                                                            <div class="position-relative">
                                                                                <div class="custom-searchable" contenteditable="true" id="search_select" data-placeholder="Select Full Name"></div>
                                                                                <select class="hidden-select" name="fields[<?= $field['id'] ?>]" id="full_name_select" <?= $field['is_required'] ? 'required' : '' ?>>
                                                                                    <option value="">Select Full Name</option>
                                                                                    <?php foreach ($voters as $v): $isSel = ($v['student_id'] == ($student_id ?? '')) ? 'selected' : ''; ?>
                                                                                        <option value="<?= htmlspecialchars($v['full_name']) ?>" data-student-id="<?= htmlspecialchars($v['student_id']) ?>" <?= $isSel ?>><?= htmlspecialchars($v['full_name']) ?></option>
                                                                                    <?php endforeach; ?>
                                                                                </select>
                                                                            </div>
                                                                        <?php break;
                                                                        case 'student_id': ?>
                                                                            <input class="input100 form-control" type="text" name="fields[<?= $field['id'] ?>]" id="student_id_field" readonly>
                                                                        <?php break;
                                                                        case 'party': ?>
                                                                            <select class="input100 form-select" name="fields[<?= $field['id'] ?>]" id="party_select" <?= $field['is_required'] ? 'required' : '' ?>>
                                                                                <option value="">Select Party</option>
                                                                                <?php foreach ($parties as $p): ?>
                                                                                    <option value="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        <?php break;
                                                                        case 'position': ?>
                                                                            <select class="input100 form-select" name="fields[<?= $field['id'] ?>]" id="position_select" <?= $field['is_required'] ? 'required' : '' ?>>
                                                                                <option value="">Select Party First</option>
                                                                            </select>
                                                                        <?php break;
                                                                        case 'picture': ?>
                                                                            <div class="mt-1">
                                                                                <input required class="custom-file-input" type="file" id="field_<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]" accept=".jpg,.jpeg,.png">
                                                                                <label class="file-label" for="field_<?= $field['id'] ?>">Choose Profile Picture</label>
                                                                            </div>
                                                                            <div id="preview_<?= $field['id'] ?>" class="mt-2"></div>
                                                                            <?php break;
                                                                        default:
                                                                            switch ($field['field_type']):
                                                                                case 'text': ?>
                                                                                    <input class="input100 form-control" type="text" name="fields[<?= $field['id'] ?>]" <?= $field['is_required'] ? 'required' : '' ?> maxlength="255">
                                                                                <?php break;
                                                                                case 'textarea': ?>
                                                                                    <textarea class="input100 form-control" name="fields[<?= $field['id'] ?>]" <?= $field['is_required'] ? 'required' : '' ?>></textarea>
                                                                                <?php break;
                                                                                case 'file': ?>
                                                                                    <div class="mt-1">
                                                                                        <input class="custom-file-input" type="file" id="field_<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]" accept=".pdf,.docx,.jpg,.png" <?= $field['is_required'] ? 'required' : '' ?>>
                                                                                        <label class="file-label" for="field_<?= $field['id'] ?>">Choose File</label>
                                                                                    </div>
                                                                                    <div id="preview_<?= $field['id'] ?>" class="mt-2"></div>
                                                                    <?php break;
                                                                            endswitch;
                                                                            break;
                                                                    endswitch; ?>
                                                                    <?php if (!empty($field['template_path'])): ?>
                                                                        <div class="mt-2">
                                                                            <a href="../uploads/templates/<?= htmlspecialchars($field['template_path']) ?>" class="btn btn-sm btn-outline-primary" download target="_blank">Download Template</a>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                            <button type="submit" class="btn btn-primary w-100 mt-4">Submit Registration</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <p class="alert alert-danger text-center"><?= htmlspecialchars($error_message ?? 'Registration is closed for this event.') ?></p>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- File Preview Modal -->
    <div class="modal fade" id="filePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">File Preview</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="filePreviewContainer"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmWithdraw(candidateId) {
            Swal.fire({
                title: 'Withdraw Candidacy?',
                text: 'This will permanently remove your candidacy filing. This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Yes, Withdraw',
                cancelButtonText: 'Cancel'
            }).then(r => {
                if (r.isConfirmed) document.getElementById('withdrawForm').submit();
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // File preview modal
            const basePath = '../login/uploads/candidates/';
            document.querySelectorAll('.view-file-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const file = this.dataset.file;
                    const ext = file.split('.').pop().toLowerCase();
                    document.getElementById('filePreviewContainer').innerHTML = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext) ?
                        '<img src="' + basePath + file + '" class="img-fluid rounded shadow">' :
                        '<iframe src="' + basePath + file + '" style="width:100%;height:600px;border:none;"></iframe>';
                });
            });
            document.getElementById('filePreviewModal')?.addEventListener('hidden.bs.modal', () => {
                document.getElementById('filePreviewContainer').innerHTML = '';
            });

            // Searchable full-name select
            const hiddenSel = document.getElementById('full_name_select');
            const customDiv = document.getElementById('search_select');
            const sidField = document.getElementById('student_id_field');
            if (hiddenSel && customDiv) {
                const placeholder = customDiv.getAttribute('data-placeholder');
                const allOptions = Array.from(hiddenSel.options).filter(o => o.value).map(o => ({
                    text: o.text,
                    value: o.value,
                    studentId: o.getAttribute('data-student-id')
                }));

                const dropdown = document.createElement('div');
                dropdown.className = 'dropdown-options';
                customDiv.parentElement.appendChild(dropdown);

                // Pre-select if option has selected attribute
                const preSelected = hiddenSel.querySelector('option[selected]');
                if (preSelected) {
                    customDiv.textContent = preSelected.textContent;
                    if (sidField) sidField.value = preSelected.getAttribute('data-student-id') || '';
                } else {
                    customDiv.textContent = placeholder;
                    customDiv.classList.add('placeholder');
                }

                function showDropdown(opts) {
                    dropdown.innerHTML = '';
                    opts.forEach(o => {
                        const d = document.createElement('div');
                        d.textContent = o.text;
                        d.addEventListener('mousedown', e => {
                            e.preventDefault();
                            customDiv.textContent = o.text;
                            customDiv.classList.remove('placeholder');
                            hiddenSel.value = o.value;
                            if (sidField) sidField.value = o.studentId || '';
                            dropdown.style.display = 'none';
                        });
                        dropdown.appendChild(d);
                    });
                    const rect = customDiv.getBoundingClientRect();
                    dropdown.style.top = (rect.bottom + window.scrollY) + 'px';
                    dropdown.style.left = (rect.left + window.scrollX) + 'px';
                    dropdown.style.width = rect.width + 'px';
                    dropdown.style.display = 'block';
                }

                customDiv.addEventListener('focus', () => {
                    if (customDiv.textContent === placeholder) customDiv.textContent = '';
                    showDropdown(allOptions);
                });
                customDiv.addEventListener('input', () => {
                    const q = customDiv.textContent.toLowerCase();
                    showDropdown(allOptions.filter(o => o.text.toLowerCase().includes(q)));
                });
                customDiv.addEventListener('blur', () => {
                    setTimeout(() => {
                        dropdown.style.display = 'none';
                        if (!customDiv.textContent.trim() || !hiddenSel.value) {
                            customDiv.textContent = placeholder;
                            customDiv.classList.add('placeholder');
                        }
                    }, 150);
                });
            }

            // Form submission with optional geolocation
            const form = document.getElementById('registrationForm');
            const formId = <?= isset($form_id)  ? (int)$form_id  : 0 ?>;
            const eventId = <?= isset($event_id) ? (int)$event_id : 0 ?>;
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const doSubmit = () => {
                        form.action = 'processes/register.php?form_id=' + formId + '&event_id=' + eventId + '&lat=0&lon=0';
                        form.submit();
                    };
                    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                    if (isMobile && navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            p => {
                                form.action = 'processes/register.php?form_id=' + formId + '&event_id=' + eventId + '&lat=' + p.coords.latitude + '&lon=' + p.coords.longitude;
                                form.submit();
                            },
                            () => doSubmit(), {
                                enableHighAccuracy: true,
                                timeout: 5000
                            }
                        );
                    } else {
                        doSubmit();
                    }
                });
            }
        });

        $(document).ready(function() {
            $('#party_select').change(function() {
                const party = $(this).val();
                const election = '<?= isset($candidacy) ? addslashes((string)$candidacy) : '' ?>';
                if (party && election) {
                    $.ajax({
                        url: 'processes/get_positions.php',
                        method: 'POST',
                        data: {
                            election_name: election,
                            party_name: party
                        },
                        success: r => $('#position_select').html(r),
                        error: () => $('#position_select').html('<option value="">Error loading positions</option>')
                    });
                } else {
                    $('#position_select').html('<option value="">Select Party First</option>');
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            <?php if (isset($_SESSION['STATUS'])): ?>
                <?php if ($_SESSION['STATUS'] === 'WITHDRAW_SUCCESS'): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Withdrawn',
                        text: 'Your candidacy has been withdrawn.',
                        confirmButtonText: 'OK'
                    });
                <?php elseif ($_SESSION['STATUS'] === 'WITHDRAW_DENIED'): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Not Allowed',
                        text: 'Admin-added candidacies cannot be withdrawn here.',
                        confirmButtonText: 'OK'
                    });
                <?php elseif ($_SESSION['STATUS'] === 'SUCCESS_CANDIDACY'): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Filed!',
                        text: 'Your candidacy has been registered.',
                        showConfirmButton: true
                    });
                <?php elseif ($_SESSION['STATUS'] === 'ERROR_CANDIDACY'): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'There was an error registering your candidacy.',
                        showConfirmButton: true
                    });
                <?php elseif ($_SESSION['STATUS'] === 'ERROR_PARTY_POSITION_DUPLICATION'): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Duplicate',
                        text: 'That party and position combination is already taken.',
                        showConfirmButton: true
                    });
                <?php elseif ($_SESSION['STATUS'] === 'ERROR_CANDIDACY_DUPLICATION'): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Already Exists',
                        text: 'A candidacy for this student already exists.',
                        showConfirmButton: true
                    });
                <?php endif; ?>
                <?php unset($_SESSION['STATUS']); ?>
            <?php endif; ?>
        });
    </script>

    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="js/off-canvas.js"></script>
    <script src="js/hoverable-collapse.js"></script>
    <script src="js/template.js"></script>
    <script src="js/settings.js"></script>
    <script src="js/dashboard.js"></script>
</body>

</html>