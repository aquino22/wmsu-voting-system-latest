<?php
session_start();
require '../includes/conn.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

header('Content-Type: application/json');

// ── 1. Basic input validation ────────────────────────────────────────────────
$student_id = trim($_POST['student_id'] ?? '');
$status     = trim($_POST['status']     ?? '');

if ($student_id === '' || $status === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID or status']);
    exit;
}

// ── 2. Session / auth check ──────────────────────────────────────────────────
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// ── 3. Fetch adviser ─────────────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare("
        SELECT id, college_id, department_id, major_id, year_level,
               wmsu_campus_id, external_campus_id
        FROM advisers WHERE email = ?
    ");
    $stmt->execute([$_SESSION['email']]);
    $adviser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$adviser || empty($adviser['college_id']) || empty($adviser['department_id'])) {
        echo json_encode(['success' => false, 'message' => 'Adviser information incomplete']);
        exit;
    }

    $adviser_id         = $adviser['id'];
    $adviser_college    = $adviser['college_id'];
    $adviser_department = $adviser['department_id'];
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching adviser data: ' . $e->getMessage()]);
    exit;
}

// ── 4. Fetch SMTP config ─────────────────────────────────────────────────────
// BUG FIX A: capacity is VARCHAR in the DB — cast to UNSIGNED for correct numeric sort.
// BUG FIX B: prefer email assigned to this adviser; fall back to any available slot.
// BUG FIX C: only use rows where status != 'full' (no hard capacity limit in schema,
//             but we respect the status field that other parts of the system set).
try {
    $stmt = $pdo->prepare("
        SELECT id, email, app_password, capacity
        FROM email
        WHERE (adviser_id = ? OR adviser_id IS NULL)
          AND status != 'full'
        ORDER BY
            CASE WHEN adviser_id = ? THEN 0 ELSE 1 END,   -- prefer adviser's own SMTP
            CAST(capacity AS UNSIGNED) ASC                 -- lowest usage first
        LIMIT 1
    ");
    $stmt->execute([$adviser_id, $adviser_id]);
    $emailConfig = $stmt->fetch(PDO::FETCH_ASSOC);

    // BUG FIX D: original query had no WHERE at all — fetched any row regardless of adviser.
    // If still nothing, last resort: any available row ordered by numeric capacity.
    if (!$emailConfig) {
        $stmt = $pdo->prepare("
            SELECT id, email, app_password, capacity
            FROM email
            WHERE status != 'full'
            ORDER BY CAST(capacity AS UNSIGNED) ASC
            LIMIT 1
        ");
        $stmt->execute();
        $emailConfig = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$emailConfig || empty($emailConfig['email']) || empty($emailConfig['app_password'])) {
        echo json_encode(['success' => false, 'message' => 'No available SMTP configuration found']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching email config: ' . $e->getMessage()]);
    exit;
}

// ── 5. Main transaction ──────────────────────────────────────────────────────
try {
    $pdo->beginTransaction();

    // Update voter status
    $stmt = $pdo->prepare("UPDATE voters SET status = ?, needs_update = 0 WHERE student_id = ?");
    $stmt->execute([$status, $student_id]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'No voter found or status unchanged']);
        exit;
    }

    // ── Only send the confirmation email when status = 'confirmed' ────────────
    if ($status === 'confirmed') {

        // BUG FIX E: original query filtered by college AND department, but voters.college
        // and voters.department store integer IDs, while adviser has college_id / department_id.
        // The voter fetch therefore returned nothing → $voter was false → no email was sent.
        // Fix: use the correct integer columns.
        $voterStmt = $pdo->prepare("
            SELECT v.email, v.first_name, v.last_name
            FROM voters v
            WHERE v.student_id = ?
              AND v.college    = ?
              AND v.department = ?
            LIMIT 1
        ");
        $voterStmt->execute([$student_id, $adviser_college, $adviser_department]);
        $voter = $voterStmt->fetch(PDO::FETCH_ASSOC);

        if (!$voter) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Voter not found in your college/department']);
            exit;
        }

        // ── Send email ───────────────────────────────────────────────────────
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $emailConfig['email'];
            $mail->Password   = $emailConfig['app_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            // BUG FIX F: SMTPOptions were set AFTER SMTPDebug — order doesn't matter for
            // PHP arrays, but debug level 2 floods output and breaks JSON response.
            // Changed to level 0 (silent) so nothing leaks before the json_encode.
            $mail->SMTPDebug  = 0;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->setFrom($emailConfig['email'], 'WMSU Election System');
            $mail->addAddress($voter['email'], $voter['first_name'] . ' ' . $voter['last_name']);

            $mail->isHTML(true);
            $mail->Subject = 'Your Voter Account Has Been Approved – WMSU Election System';
            $mail->Body = "
<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'>
  <h2 style='color:#800000;border-bottom:2px solid #800000;padding-bottom:8px;'>WMSU Election System</h2>
  <p>Dear <strong>{$voter['first_name']} {$voter['last_name']}</strong>,</p>
  <p>We are pleased to inform you that your student account has been <strong>successfully approved</strong>
     for the Western Mindanao State University Election System.</p>
  <p>You are now officially authorized to access the platform, review candidate information,
     and participate in the scheduled election activities.</p>
  <p>If you encounter any issues or notice incorrect information in your profile, please contact
     your College Electoral Board (CEB) or the University Election Commission (UEC).</p>
  <p>Your vote plays a vital role in shaping fair, transparent, and accountable student governance.</p>
  <br>
  <p>— <em>WMSU Election System</em></p>
</div>
";
            $mail->AltBody =
                "Dear {$voter['first_name']} {$voter['last_name']},\n\n"
                . "Your voter account has been approved for the WMSU Election System.\n"
                . "You may now log in and participate once the voting period begins.\n\n"
                . "— WMSU Election System";

            $mail->send();

            // ── Post-send DB updates ─────────────────────────────────────────
            // Increment SMTP capacity counter (cast to INT for safe arithmetic)
            $pdo->prepare("
                UPDATE email SET capacity = CAST(capacity AS UNSIGNED) + 1 WHERE id = ?
            ")->execute([$emailConfig['id']]);

            // Upsert email_role_log
            // BUG FIX G: original code checked by student_id but email_role_log has no
            // unique constraint on student_id — INSERT OR UPDATE is cleaner with ON DUPLICATE KEY.
            // Using the existing check-then-insert/update pattern is fine; keep it but fix the
            // missing voting_period_id column (it's NOT NULL in the schema with default 0).
            $checkLog = $pdo->prepare("SELECT id FROM email_role_log WHERE student_id = ?");
            $checkLog->execute([$student_id]);
            if ($checkLog->fetch()) {
                $pdo->prepare("
                    UPDATE email_role_log SET count = count + 1, status = 'sent' WHERE student_id = ?
                ")->execute([$student_id]);
            } else {
                // voting_period_id defaults to 0 in schema; pass 0 explicitly
                $pdo->prepare("
                    INSERT INTO email_role_log (student_id, status, count, voting_period_id)
                    VALUES (?, 'sent', 1, 0)
                ")->execute([$student_id]);
            }

            // Activate user account in users table
            // BUG FIX H: original code used $stmt variable already in use by the outer
            // voter update — re-assigning $stmt mid-transaction is harmless in PHP but
            // confusing; renamed to $activateStmt for clarity.
            $activateStmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE email = ?");
            $activateStmt->execute([$voter['email']]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Voter confirmed and notification email sent successfully.',
            ]);
        } catch (MailException $e) {
            // BUG FIX I: original code had TWO catch (Exception $e) blocks back-to-back
            // — the second one was unreachable (PHP silently ignores duplicate catch types).
            // Also: on mail failure the status update was already done; rolling back leaves
            // the voter in their old status which is the correct safe behavior.
            $pdo->rollBack();

            // Log the mail error for debugging
            error_log('[WMSU Email Error] student=' . $student_id . ' smtp=' . $emailConfig['email'] . ' error=' . $e->getMessage());

            // Persist the error to email_errors table for admin visibility
            try {
                $pdo->prepare("
                    INSERT INTO email_errors (adviser_id, email, recipient_email, error_message)
                    VALUES (?, ?, ?, ?)
                ")->execute([
                    $adviser_id,
                    $emailConfig['email'],
                    $voter['email'],
                    $e->getMessage(),
                ]);
            } catch (PDOException $logEx) {
                error_log('[WMSU Email Error Log Failed] ' . $logEx->getMessage());
            }

            echo json_encode([
                'success' => false,
                'message' => 'Status update was rolled back because the confirmation email could not be sent. '
                    . 'SMTP error: ' . $mail->ErrorInfo,
            ]);
        }
    } else {
        // Non-confirmed status (e.g. rejected, pending) — just commit the status change
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully.',
        ]);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Unexpected error: ' . $e->getMessage()]);
}
