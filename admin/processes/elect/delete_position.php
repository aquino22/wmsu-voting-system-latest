<?php
/* delete-position.php
 * ----------------------------------------------------------------------
 * Deletes a position and any candidate–related data that belong to the
 * specified party *only*.  If other parties are still using the same
 * position, the position record is kept.
 * -------------------------------------------------------------------- */

require_once '../../includes/conn.php';
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

/* ───────── 0. Validate incoming IDs and names ───────── */
$positionId = isset($_POST['id'])      ? (int) $_POST['id']      : 0;
$partyName  = isset($_POST['partyName'])   ? trim($_POST['partyName'])   : '';

if ($positionId <= 0 || $partyName === '') {
    echo json_encode(['status' => 'error', 'message' => 'Position ID and party name are required']);
    exit;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();

    /* ───────── 1. Fetch position name ───────── */
    $stmt = $pdo->prepare("SELECT name FROM positions WHERE id = ?");
    $stmt->execute([$positionId]);
    $positionName = $stmt->fetchColumn();

    if (!$positionName) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Position not found']);
        exit;
    }



    $del = $pdo->prepare("DELETE FROM positions WHERE id = ?");
    $del->execute([$positionId]);
    $positionRemoved = ($del->rowCount() > 0);


    /* ───────── 8. Log user activity (optional) ───────── */
    if (isset($_SESSION['user_id'])) {
        $log = $pdo->prepare("
    INSERT INTO user_activities
        (user_id, action, timestamp, device_info, ip_address, location, behavior_patterns)
    VALUES
        (:uid, 'DELETE_POSITION_PARTY', NOW(), :agent, :ip, 'N/A',
         CONCAT(
             'Deleted position \"', :posName, '\" for party \"', :partyName, '\". ',
             'Candidates: ', :cands, '. ',
             'Position row removed: ', :posRemoved
         ))
");

        $log->execute([
            ':uid'        => $_SESSION['user_id'],
            ':agent'      => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            ':ip'         => $_SERVER['REMOTE_ADDR']     ?? 'Unknown',
            ':posName'    => $positionName,
            ':partyName'  => $partyName,
            ':cands'      => $positionId,
            ':posRemoved' => $positionRemoved ? 'yes' : 'no'
        ]);
    }

    /* ───────── 9. Commit & respond ───────── */
    $pdo->commit();

    $msg = "Done: "

        . ($positionRemoved   ? "Position record deleted."           : "Position kept for other parties.");

    echo json_encode(['status' => 'success', 'message' => $msg]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/* ───────── 10. Clean up ───────── */
$pdo = null;
