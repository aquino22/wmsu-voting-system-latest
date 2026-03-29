<?php
session_start();
require '../../includes/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

$precinct_id      = (int)($_POST['precinct_id']      ?? 0);
$name             = trim($_POST['name']              ?? '');
$location         = trim($_POST['location']          ?? '');
$max_capacity     = (int)($_POST['max_capacity']     ?? 0);
$college_id       = $_POST['college']                ?? null;
$department_id    = $_POST['department']             ?? null;
$major_id         = !empty($_POST['major'])          ? $_POST['major'] : null;
$campus_type      = $_POST['type']                   ?? null;  // campus_id (8=Main, 10=ESU)
$college_external = !empty($_POST['college_external']) ? $_POST['college_external'] : null;
$latitude         = trim($_POST['latitude']          ?? '');
$longitude        = trim($_POST['longitude']         ?? '');
$election_id      = !empty($_POST['election'])       ? (int)$_POST['election'] : null;

// ── Validation ────────────────────────────────────────────────────────────────
if (!$precinct_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid precinct ID.']);
    exit();
}
if (empty($name) || empty($location) || !$max_capacity || !$college_id || !$department_id || !$campus_type) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}
if (!$election_id) {
    echo json_encode(['success' => false, 'message' => 'Please select an election.']);
    exit();
}

// ESU requires a campus location
if ($campus_type == 10 && empty($college_external)) {
    echo json_encode(['success' => false, 'message' => 'Please select an ESU campus location.']);
    exit();
}

// Main campus must not have a college_external
if ($campus_type != 10) {
    $college_external = null;
}

try {
    $pdo->beginTransaction();

    // Check precinct exists
    $check = $pdo->prepare("SELECT id FROM precincts WHERE id = ?");
    $check->execute([$precinct_id]);
    if (!$check->fetchColumn()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Precinct not found.']);
        exit();
    }

    // Check name uniqueness (exclude self)
    $checkName = $pdo->prepare("SELECT id FROM precincts WHERE name = ? AND id != ?");
    $checkName->execute([$name, $precinct_id]);
    if ($checkName->fetchColumn()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'A precinct with this name already exists.']);
        exit();
    }

    // Check college + department + campus combination uniqueness (exclude self)
    $checkCombo = $pdo->prepare("
        SELECT id FROM precincts
        WHERE college          = ?
        AND   department       = ?
        AND   type             = ?
        AND   (college_external = ? OR (college_external IS NULL AND ? IS NULL))
        AND   id != ?
    ");
    $checkCombo->execute([
        $college_id,
        $department_id,
        $campus_type,
        $college_external,
        $college_external,
        $precinct_id
    ]);
    if ($checkCombo->fetchColumn()) {
        $pdo->rollBack();
        $comboLocation = $campus_type == 10
            ? 'WMSU ESU — ' . ($college_external ?? 'unknown location')
            : 'Main Campus';
        echo json_encode([
            'success' => false,
            'message' => "A precinct for this college + department combination at $comboLocation already exists."
        ]);
        exit();
    }

    // Update precinct
    $stmt = $pdo->prepare("
        UPDATE precincts SET
            name             = ?,
            location         = ?,
            max_capacity     = ?,
            college          = ?,
            department       = ?,
            major_id         = ?,
            type             = ?,
            college_external = ?,
            latitude         = ?,
            longitude        = ?,
            updated_at       = NOW(),
            status           = ?,
            election         = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $name,
        $location,
        $max_capacity,
        $college_id,
        $department_id,
        $major_id,
        $campus_type,
        $college_external,
        $latitude  ?: null,
        $longitude ?: null,
        'active',
        $election_id,
        $precinct_id,
    ]);

    // ── Election assignment — only archive + insert if election actually changed ──
    // Fetch the current active election for this precinct
    $currentElection = $pdo->prepare("
        SELECT election_name
        FROM precinct_elections
        WHERE precinct_id = ? AND archived = 0
        LIMIT 1
    ");
    $currentElection->execute([$precinct_id]);
    $currentElectionId = (int) $currentElection->fetchColumn();

    if ($currentElectionId !== $election_id) {
        // Election changed — archive the old active row and insert the new one
        $pdo->prepare("
            UPDATE precinct_elections
            SET archived    = 1,
                archived_at = NOW()
            WHERE precinct_id = ? AND archived = 0
        ")->execute([$precinct_id]);

        $pdo->prepare("
            INSERT INTO precinct_elections (precinct_id, precinct_name, election_name, archived)
            VALUES (?, ?, ?, 0)
        ")->execute([$precinct_id, $name, $election_id]);
    } else {
        // Same election — just sync the precinct_name in case it was renamed
        $pdo->prepare("
            UPDATE precinct_elections
            SET precinct_name = ?
            WHERE precinct_id = ? AND archived = 0
        ")->execute([$name, $precinct_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Precinct updated successfully.']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Precinct update failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
