<?php
session_start();
date_default_timezone_set('Asia/Manila');
require '../../includes/conn.php'; // Database connection
require '../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['STATUS'] = "NON_ADMIN";
    header("Location: ../index.php");
    exit();
}

try {
    // Ensure the request is a POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method. Please use the form to update status.");
    }

    // Get POST data
    $candidate_id = isset($_POST['candidate_id']) ? (int) $_POST['candidate_id'] : 0;
    $status_action = isset($_POST['status']) ? trim($_POST['status']) : '';
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

    // Validate inputs
    if ($candidate_id <= 0) {
        throw new Exception("Invalid candidate ID");
    }
    if (!in_array($status_action, ['accept', 'reject'])) {
        throw new Exception("Invalid status value");
    }
    if ($event_id <= 0) {
        throw new Exception("Invalid event ID");
    }

    // Verify the candidate exists and is tied to the event
    $check_stmt = $pdo->prepare("
        SELECT c.id 
        FROM candidates c
        JOIN registration_forms rf ON c.form_id = rf.id
        JOIN events e ON rf.election_name = e.candidacy
        WHERE c.id = ? AND e.id = ? AND c.status = 'pending'
    ");
    $check_stmt->execute([$candidate_id, $event_id]);
    if (!$check_stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception("Candidate not found, not pending, or not associated with this event");
    }

    $status = ($status_action === 'accept') ? 'accepted' : 'rejected';

    // Update the candidate's status
    $update_stmt = $pdo->prepare("
        UPDATE candidates 
        SET status = ?
        WHERE id = ?
    ");
    $update_stmt->execute([$status, $candidate_id]);

    // --- START EMAIL NOTIFICATION ---
    try {
        // 1. Fetch candidate details for email
        $stmt_cand_details = $pdo->prepare("
            SELECT
                MAX(CASE WHEN ff.field_name = 'student_id' THEN cr.value END) as student_id,
                MAX(CASE WHEN ff.field_name = 'party' THEN cr.value END) as party,
                MAX(CASE WHEN ff.field_name = 'position' THEN cr.value END) as position
            FROM candidate_responses cr
            JOIN form_fields ff ON cr.field_id = ff.id
            WHERE cr.candidate_id = ?
            GROUP BY cr.candidate_id
        ");
        $stmt_cand_details->execute([$candidate_id]);
        $candidate_details = $stmt_cand_details->fetch(PDO::FETCH_ASSOC);

        // 2. Fetch election name
        $stmt_election = $pdo->prepare("
            SELECT e.election_name
            FROM events ev
            JOIN elections e ON ev.candidacy = e.id
            WHERE ev.id = ?
        ");
        $stmt_election->execute([$event_id]);
        $election_details = $stmt_election->fetch(PDO::FETCH_ASSOC);
        $election_name = $election_details ? $election_details['election_name'] : 'Not specified';

        if ($candidate_details && !empty($candidate_details['student_id'])) {
            $student_id = $candidate_details['student_id'];
            $party = $candidate_details['party'];
            $position = $candidate_details['position'];

            // 3. Get voter details
            $voterStmt = $pdo->prepare("SELECT email, first_name, last_name FROM voters WHERE student_id = ?");
            $voterStmt->execute([$student_id]);
            $voter = $voterStmt->fetch(PDO::FETCH_ASSOC);

            // 4. Get available SMTP email
            $emailStmt = $pdo->prepare("SELECT id, email, app_password FROM email ORDER BY capacity ASC LIMIT 1");
            $emailStmt->execute();
            $emailConfig = $emailStmt->fetch(PDO::FETCH_ASSOC);

            if ($voter && !empty($voter['email']) && $emailConfig) {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $emailConfig['email'];
                $mail->Password = $emailConfig['app_password'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->setFrom($emailConfig['email'], 'WMSU - Student Affairs');
                $mail->addAddress($voter['email']);

                // Image handling
                $basePath = dirname(dirname(__DIR__)) . '/images/';
                if (file_exists($basePath . 'logo-left.png')) $mail->addEmbeddedImage($basePath . 'logo-left.png', 'logo_left');
                if (file_exists($basePath . 'logo-right.png')) $mail->addEmbeddedImage($basePath . 'logo-right.png', 'logo_right');
                if (file_exists($basePath . 'banner.png')) $mail->addEmbeddedImage($basePath . 'banner.png', 'banner_bottom');

                $mail->isHTML(true);

                $emailTemplate = file_get_contents('../../includes/email_template.html');
                $emailTemplate = str_replace('{{logo_left}}', 'cid:logo_left', $emailTemplate);
                $emailTemplate = str_replace('{{logo_right}}', 'cid:logo_right', $emailTemplate);
                $emailTemplate = str_replace('{{banner_bottom}}', 'cid:banner_bottom', $emailTemplate);
                $emailTemplate = str_replace('{{voter_name}}', htmlspecialchars($voter['first_name']), $emailTemplate);

                if ($status === 'accepted') {
                    $mail->Subject = 'Candidacy Approved';
                    $title = '🎉 Candidacy Approved';
                    $body_content = '<p>Congratulations! Your application to run for candidacy has been reviewed and approved by the election committee.</p>
                        <table width="100%" cellpadding="8" cellspacing="0" style="background:#f8fafc; border-radius:6px; margin:20px 0; font-size:14px;">
                            <tr><td><strong>Election:</strong></td><td>' . htmlspecialchars($election_name) . '</td></tr>
                            <tr><td><strong>Party:</strong></td><td>' . htmlspecialchars($party) . '</td></tr>
                            <tr><td><strong>Position:</strong></td><td>' . htmlspecialchars($position) . '</td></tr>
                        </table>
                        <p>We wish you the best of luck in your campaign! Stay committed, lead with integrity, and inspire others.</p>';
                } else { // rejected
                    $mail->Subject = 'Candidacy Status Update';
                    $title = 'Candidacy Status Update';
                    $body_content = '<p>We regret to inform you that after careful review, your application to run for candidacy has been rejected.</p>
                        <table width="100%" cellpadding="8" cellspacing="0" style="background:#f8fafc; border-radius:6px; margin:20px 0; font-size:14px;">
                            <tr><td><strong>Election:</strong></td><td>' . htmlspecialchars($election_name) . '</td></tr>
                            <tr><td><strong>Party:</strong></td><td>' . htmlspecialchars($party) . '</td></tr>
                            <tr><td><strong>Position:</strong></td><td>' . htmlspecialchars($position) . '</td></tr>
                        </table>
                        <p>If you have any questions or believe this decision was made in error, please contact the election committee for further clarification.</p>';
                }

                $emailTemplate = str_replace('{{email_title}}', $title, $emailTemplate);
                $emailTemplate = str_replace('{{email_body}}', $body_content, $emailTemplate);
                $mail->Body = $emailTemplate;

                $mail->send();

                // Update SMTP capacity
                $updateStmt = $pdo->prepare("UPDATE email SET capacity = capacity + 1 WHERE id = ?");
                $updateStmt->execute([$emailConfig['id']]);
            }
        }
    } catch (Exception $emailError) {
        // Log email error but do not fail the request
        error_log("Email notification failed for candidate_id $candidate_id: " . $emailError->getMessage());
    }
    // --- END EMAIL NOTIFICATION ---

    // Set success message
    $_SESSION['STATUS_NEW'] = "CANDIDATE_STATUS_UPDATE";
    $_SESSION['MESSAGE'] = "Candidate status updated to $status successfully.";

    $location =  $_SERVER['HTTP_REFERER'];
    header("Location:" . $location);
    exit();
} catch (Exception $e) {
    // Set error message
    $_SESSION['STATUS_NEW'] = "CANDIDATE_ACCEPTED_ERROR";
    $_SESSION['ERROR_MESSAGE'] = $e->getMessage();

    $location =  $_SERVER['HTTP_REFERER'];
    header("Location:" . $location);
    exit();
}
