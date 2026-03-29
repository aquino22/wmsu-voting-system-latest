<?php
session_start();
require '../../includes/conn.php';
header('Content-Type: application/json');

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// 1. Sanitize input
$id          = intval($_POST['id'] ?? 0);
$election_id = intval($_POST['election_id'] ?? 0);
$start_period = $_POST['start_period'] ?? '';
$end_period   = $_POST['end_period'] ?? '';
$status       = $_POST['status'] ?? '';

if (!$id || !$election_id || !$start_period || !$end_period || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// 2. Validate dates
// try {
$startDT = new DateTime($start_period);
$endDT   = new DateTime($end_period);

//     if ($startDT >= $endDT) {
//         throw new Exception("End period must be after start period");
//     }
// } catch (Exception $e) {
//     echo json_encode(['success' => false, 'message' => $e->getMessage()]);
//     exit;
// }

// 3. Check election exists
$stmt = $pdo->prepare("SELECT start_period, end_period FROM elections WHERE id = :id");
$stmt->execute([':id' => $election_id]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$election) {
    echo json_encode(['success' => false, 'message' => 'Election not found']);
    exit;
}

// 4. Check candidacy period within election boundaries
if ($startDT < new DateTime($election['start_period']) || $endDT > new DateTime($election['end_period'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Candidacy period must be within the election period'
    ]);
    exit;
}

// 5. Prevent duplicates (other than current record)
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM candidacy 
    WHERE election_id = :eid AND id != :id
");
$stmt->execute([':eid' => $election_id, ':id' => $id]);
if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'Candidacy already exists for this election']);
    exit;
}

// 6. Update candidacy
$stmt = $pdo->prepare("
    UPDATE candidacy SET
        election_id  = :eid,
        start_period = :start,
        end_period   = :end,
        status       = :status,
        updated_at   = NOW()
    WHERE id = :id
");

$stmt->execute([
    ':eid'    => $election_id,
    ':start'  => $startDT->format('Y-m-d H:i:s'),
    ':end'    => $endDT->format('Y-m-d H:i:s'),
    ':status' => $status,
    ':id'     => $id
]);

// 7. If status = Finished, update linked tables
if ($status === 'Finished') {
    $stmt = $pdo->prepare("UPDATE voting_periods SET status = 'Finished' WHERE election_id = :eid");
    $stmt->execute([':eid' => $election_id]);

    $stmt = $pdo->prepare("UPDATE events SET registration_enabled = 0 WHERE candidacy_id = :cid");
    $stmt->execute([':cid' => $id]);
}

// 8. Response
echo json_encode([
    'success' => true,
    'message' => $stmt->rowCount() ? 'Candidacy updated successfully' : 'No changes made'
]);

$pdo = null;
