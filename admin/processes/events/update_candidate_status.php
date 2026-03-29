<?php
session_start();
date_default_timezone_set('Asia/Manila');
require '../../includes/conn.php';
require '../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['STATUS'] = "NON_ADMIN";
    header("Location: ../index.php");
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    $event_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0);
    $status_action = isset($_POST['status']) ? trim($_POST['status']) : '';

    if ($event_id <= 0) {
        throw new Exception("Invalid event ID");
    }
    if (!in_array($status_action, ['accept', 'decline'])) {
        throw new Exception("Invalid status value");
    }

    $status = ($status_action === 'accept') ? 'accepted' : 'declined';


    // ── 1. Fetch all pending candidates for this event BEFORE updating ──
    // so we have their IDs to send emails to
    $fetch_stmt = $pdo->prepare("
        SELECT c.id AS candidate_id
        FROM candidates c
        JOIN registration_forms rf ON c.form_id = rf.id
        JOIN events e ON rf.election_name = e.candidacy
        WHERE e.id = ? AND c.status = 'pending'
    ");
    $fetch_stmt->execute([$event_id]);
    $candidate_ids = $fetch_stmt->fetchAll(PDO::FETCH_COLUMN); // array of candidate IDs

    // ── 2. Bulk update all pending candidates for this event ──
    $update_stmt = $pdo->prepare("
        UPDATE candidates c
        JOIN registration_forms rf ON c.form_id = rf.id
        JOIN events e ON rf.election_name = e.candidacy
        SET c.status = ?, c.admin_config = 1
        WHERE e.id = ? AND c.status = 'pending'
    ");
    $update_stmt->execute([$status, $event_id]);
    $affected_rows = $update_stmt->rowCount();

    // ── 3. Fetch election name for the email ──
    $stmt_election = $pdo->prepare("
        SELECT e.election_name
        FROM events ev
        JOIN elections e ON ev.candidacy = e.id
        WHERE ev.id = ?
    ");
    $stmt_election->execute([$event_id]);
    $election_details = $stmt_election->fetch(PDO::FETCH_ASSOC);
    $election_name = $election_details ? $election_details['election_name'] : 'Not specified';

    // ── 4. Fetch SMTP config once (reuse across all emails) ──
    $emailStmt = $pdo->prepare("SELECT id, email, app_password FROM email ORDER BY capacity ASC LIMIT 1");
    $emailStmt->execute();
    $emailConfig = $emailStmt->fetch(PDO::FETCH_ASSOC);

    // ── 5. Send email to each affected candidate ──
    if (!empty($candidate_ids) && $emailConfig) {

        // Pre-load embedded images once
        $basePath   = dirname(dirname(__DIR__)) . '/images/';
        $logoLeft   = $basePath . 'logo-left.png';
        $logoRight  = $basePath . 'logo-right.png';
        $banner     = $basePath . 'banner.png';
        $emailTemplatePath = '../../includes/email_template.html';

        $emailsSent = 0;

        foreach ($candidate_ids as $candidate_id) {
            try {
                // Fetch candidate's student_id, party, position
                $stmt_cand = $pdo->prepare("
                    SELECT
                        MAX(CASE WHEN ff.field_name = 'student_id' THEN cr.value END) AS student_id,
                        MAX(CASE WHEN ff.field_name = 'party'      THEN cr.value END) AS party,
                        MAX(CASE WHEN ff.field_name = 'position'   THEN cr.value END) AS position
                    FROM candidate_responses cr
                    JOIN form_fields ff ON cr.field_id = ff.id
                    WHERE cr.candidate_id = ?
                    GROUP BY cr.candidate_id
                ");
                $stmt_cand->execute([$candidate_id]);
                $cand = $stmt_cand->fetch(PDO::FETCH_ASSOC);

                if (!$cand || empty($cand['student_id'])) continue;

                // Fetch voter email and name
                $voterStmt = $pdo->prepare("SELECT email, first_name, last_name FROM voters WHERE student_id = ?");
                $voterStmt->execute([$cand['student_id']]);
                $voter = $voterStmt->fetch(PDO::FETCH_ASSOC);

                if (!$voter || empty($voter['email'])) continue;

                // Build email
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = $emailConfig['email'];
                $mail->Password   = $emailConfig['app_password'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->setFrom($emailConfig['email'], 'WMSU - Student Affairs');
                $mail->addAddress($voter['email']);

                if (file_exists($logoLeft))  $mail->addEmbeddedImage($logoLeft,  'logo_left');
                if (file_exists($logoRight)) $mail->addEmbeddedImage($logoRight, 'logo_right');
                if (file_exists($banner))    $mail->addEmbeddedImage($banner,    'banner_bottom');

                $mail->isHTML(true);

                $emailTemplate = file_get_contents($emailTemplatePath);
                $emailTemplate = str_replace('{{logo_left}}',     'cid:logo_left',     $emailTemplate);
                $emailTemplate = str_replace('{{logo_right}}',    'cid:logo_right',    $emailTemplate);
                $emailTemplate = str_replace('{{banner_bottom}}', 'cid:banner_bottom', $emailTemplate);
                $emailTemplate = str_replace('{{voter_name}}',    htmlspecialchars($voter['first_name']), $emailTemplate);

                if ($status === 'accepted') {
                    $mail->Subject = 'Candidacy Approved';
                    $title         = '🎉 Candidacy Approved';
                    $body_content  = '
                        <p>Congratulations! Your application to run for candidacy has been reviewed and approved by the election committee.</p>
                        <table width="100%" cellpadding="8" cellspacing="0" style="background:#f8fafc; border-radius:6px; margin:20px 0; font-size:14px;">
                            <tr><td><strong>Election:</strong></td><td>' . htmlspecialchars($election_name)    . '</td></tr>
                            <tr><td><strong>Party:</strong></td><td>'    . htmlspecialchars($cand['party'])    . '</td></tr>
                            <tr><td><strong>Position:</strong></td><td>' . htmlspecialchars($cand['position']) . '</td></tr>
                        </table>
                        <p>We wish you the best of luck in your campaign! Stay committed, lead with integrity, and inspire others.</p>';
                } else {
                    $mail->Subject = 'Candidacy Status Update';
                    $title         = 'Candidacy Status Update';
                    $body_content  = '
                        <p>We regret to inform you that after careful review, your application to run for candidacy has been rejected.</p>
                        <table width="100%" cellpadding="8" cellspacing="0" style="background:#f8fafc; border-radius:6px; margin:20px 0; font-size:14px;">
                            <tr><td><strong>Election:</strong></td><td>' . htmlspecialchars($election_name)    . '</td></tr>
                            <tr><td><strong>Party:</strong></td><td>'    . htmlspecialchars($cand['party'])    . '</td></tr>
                            <tr><td><strong>Position:</strong></td><td>' . htmlspecialchars($cand['position']) . '</td></tr>
                        </table>
                        <p>If you have any questions or believe this decision was made in error, please contact the election committee for further clarification.</p>';
                }

                $emailTemplate = str_replace('{{email_title}}', $title,        $emailTemplate);
                $emailTemplate = str_replace('{{email_body}}',  $body_content, $emailTemplate);
                $mail->Body    = $emailTemplate;

                $mail->send();
                $emailsSent++;
            } catch (Exception $emailError) {
                // Log per-candidate failure but keep looping
                error_log("Bulk email failed for candidate_id $candidate_id: " . $emailError->getMessage());
            }
        }

        // Update SMTP capacity once with total emails sent
        if ($emailsSent > 0) {
            $updateCapacity = $pdo->prepare("UPDATE email SET capacity = capacity + ? WHERE id = ?");
            $updateCapacity->execute([$emailsSent, $emailConfig['id']]);
        }
    }

    // ── 6. Set session status and redirect ──
    if ($affected_rows > 0) {
        $_SESSION['STATUS'] = "ACCEPT_CANDIDACY_ALL_ADMIN";
    } else {
        $_SESSION['STATUS'] = "REJECT_CANDIDACY_ALL_ADMIN";
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
} catch (Exception $e) {
    $_SESSION['STATUS']        = "CANDIDATE_UPDATE_ERROR";
    $_SESSION['ERROR_MESSAGE'] = $e->getMessage();

    echo $e->getMessage();
    exit();
}
