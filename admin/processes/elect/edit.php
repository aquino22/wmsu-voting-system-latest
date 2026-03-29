<?php
require '../../includes/conn.php';
session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* -------------------------------------------------
   Helper for safe execution (ignore missing tables)
--------------------------------------------------*/
function safeExecute(PDO $pdo, string $sql, array $params = []): bool
{
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02') {
            error_log('[SKIPPED] Missing table: ' . $e->getMessage());
            return false;
        }
        throw $e;
    }
}

/* -------------------------------------------------
   1. Auth & Method
--------------------------------------------------*/
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

/* -------------------------------------------------
   2. CSRF & Required Fields
--------------------------------------------------*/
try {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        throw new Exception('Invalid CSRF token');
    }

    $required = ['election_id', 'academic_year_id', 'election_name', 'start_period', 'end_period', 'status'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    /* -------------------------------------------------
       3. Sanitize Inputs
    --------------------------------------------------*/
    $election_id   = filter_var($_POST['election_id'], FILTER_VALIDATE_INT);
    $academic_year_id = filter_var($_POST['academic_year_id'], FILTER_VALIDATE_INT);
    $election_name = trim($_POST['election_name']);
    $status        = trim($_POST['status']);
    $start   = trim($_POST['start_period']);
    $end     = trim($_POST['end_period']);

    if (!$election_id || !$academic_year_id) {
        throw new Exception('Invalid IDs provided.');
    }

    if (strlen($election_name) > 500) {
        throw new Exception('Election name too long.');
    }

    if (strlen($status) > 50) {
        throw new Exception('Invalid status length.');
    }

    /* -------------------------------------------------
       4. Validate Dates
    --------------------------------------------------*/
    // $start = new DateTime($start_input);
    // $end   = new DateTime($end_input);

    // if ($start >= $end) {
    //     throw new Exception('End period must be after start period.');
    // }

    /* -------------------------------------------------
       5. Resolve Academic Year
    --------------------------------------------------*/
    $stmt = $pdo->prepare("SELECT year_label, semester, start_date, end_date FROM academic_years WHERE id = ?");
    $stmt->execute([$academic_year_id]);
    $ay = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ay) {
        throw new Exception('Academic year not found.');
    }

    $ay_start = new DateTime($ay['start_date']);
    $ay_start->setTime(0, 0, 0);          // ← be explicit, mirrors the $ay_end call

    $ay_end = new DateTime($ay['end_date']);
    $ay_end->setTime(23, 59, 59);

    // if ($start < $ay_start || $end > $ay_end) {
    //     throw new Exception(
    //         "Election period must be within the academic year's dates " .
    //             "({$ay_start->format('M j, Y')} to {$ay_end->format('M j, Y')})."
    //     );
    // }

    /* -------------------------------------------------
       6. Check Existing Election & Uniqueness
    --------------------------------------------------*/
    $stmt = $pdo->prepare("SELECT election_name, status FROM elections WHERE id = ?");
    $stmt->execute([$election_id]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$old) {
        throw new Exception('Election not found.');
    }

    $oldName = $old['election_name'];

    // $stmt = $pdo->prepare("SELECT COUNT(*) FROM elections WHERE election_name = ? AND id != ?");
    // $stmt->execute([$election_name, $election_id]);
    // if ($stmt->fetchColumn() > 0) {
    //     throw new Exception('An election with this name already exists.');
    // }

    /* -------------------------------------------------
       7. Update Election
    --------------------------------------------------*/
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE elections SET
            election_name    = :name,
            academic_year_id = :ay,
            start_period     = :start_p,
            end_period       = :end_p,
            status           = :status,
            updated_at       = NOW()
        WHERE id = :id
    ");

    $stmt->execute([
        ':id'      => $election_id,
        ':ay'      => $academic_year_id,
        ':name'    => $election_name,
        ':start_p' => $start,
        ':end_p'   => $end,
        ':status'  => $status
    ]);

    /* -------------------------------------------------
       8. Propagate Name Changes to tables storing the name (if any)
       – Only update tables that store the election name as text.
    --------------------------------------------------*/
    if ($election_name !== $oldName) {
        safeExecute(
            $pdo,
            "UPDATE events SET candidacy = ? WHERE candidacy = ?",
            [$election_name, $oldName]
        );
    }

    $pdo->commit();
    unset($_SESSION['csrf_token']);

    echo json_encode([
        'status'  => $stmt->rowCount() ? 'success' : 'info',
        'message' => $stmt->rowCount() ? 'Election updated successfully' : 'No changes detected'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('edit_election.php: ' . $e->getMessage());
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}

$pdo = null;
