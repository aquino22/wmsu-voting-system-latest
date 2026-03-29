<?php
session_start();
require '../../includes/conn.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    session_start();
    $_SESSION['STATUS'] = "NON_ADMIN";
    header("Location: ../../index.php");
    exit();
}

$candidate_id = intval($_POST['candidate_id'] ?? 0);
$event_id     = intval($_POST['event_id']     ?? 0);

if (!$candidate_id || !$event_id) {
    $_SESSION['STATUS'] = 'DISPOSE_FAILED';
    header("Location: ../../view_candidates.php?id=$event_id");
    exit();
}

try {
    $pdo->beginTransaction();

    // Verify candidate exists and is NOT admin-added (double-check server side)
    $chk = $pdo->prepare("SELECT id, admin_config FROM candidates WHERE id = ?");
    $chk->execute([$candidate_id]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$row) throw new Exception("Candidate not found.");
    // Admin-added candidates may still be disposed by admin (admin decides — remove check if you want to block)
    // Uncomment to block admin-added candidates:
    // if ((int)$row['admin_config'] === 1) throw new Exception("Cannot dispose admin-added candidate.");

    // Delete related files (physical + DB)
    $files = $pdo->prepare("SELECT file_path FROM candidate_files WHERE candidate_id = ?");
    $files->execute([$candidate_id]);
    foreach ($files->fetchAll(PDO::FETCH_COLUMN) as $path) {
        $fullPath = __DIR__ . '/../../login/uploads/candidates/' . $path;
        if (file_exists($fullPath)) @unlink($fullPath);
    }

    $pdo->prepare("DELETE FROM candidate_files     WHERE candidate_id = ?")->execute([$candidate_id]);
    $pdo->prepare("DELETE FROM candidate_responses WHERE candidate_id = ?")->execute([$candidate_id]);
    $pdo->prepare("DELETE FROM candidates          WHERE id = ?")->execute([$candidate_id]);

    $pdo->commit();
    $_SESSION['STATUS'] = 'DISPOSE_SUCCESS';
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Dispose error: " . $e->getMessage());
    $_SESSION['STATUS'] = 'DISPOSE_FAILED';
}

header("Location: ../../view_candidates.php?id=$event_id");
exit();
