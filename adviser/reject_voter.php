<?php
session_start();
require_once '../includes/conn.php'; // Adjust the path as needed
require '../vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? null;
    $reason = $_POST['reason'] ?? null;

    if (!$student_id || !$reason) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing student ID or reason.']);
        exit;
    }

    try {
        // Start transaction
        $pdo->beginTransaction();

        // First get the student's email and name before updating
        $stmt = $pdo->prepare("SELECT email, first_name, last_name FROM voters WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Student not found.']);
            exit;
        }

        // Update voter status
        $stmt = $pdo->prepare("UPDATE voters SET status = 'rejected', rejection_reason = ? WHERE student_id = ?");
        $stmt->execute([$reason, $student_id]);

        // Get available SMTP email with lowest capacity
        $emailStmt = $pdo->prepare("
            SELECT id, email, app_password 
            FROM email 
            ORDER BY capacity ASC 
            LIMIT 1
        ");
        $emailStmt->execute();
        $emailConfig = $emailStmt->fetch(PDO::FETCH_ASSOC);

        if (!$emailConfig || empty($emailConfig['email']) || empty($emailConfig['app_password'])) {
            $pdo->commit(); // Still update status even if email fails
            echo json_encode([
                'status' => 'success',
                'message' => 'Voter rejected but email could not be sent (no email service configured).'
            ]);
            exit;
        }

        // Send rejection email
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $emailConfig['email'];
            $mail->Password = $emailConfig['app_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Recipients
            $mail->setFrom($emailConfig['email'], 'Election System');
            $mail->addAddress($student['email'], $student['first_name'] . ' ' . $student['last_name']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Voting Registration Rejected';
            $mail->Body = "
                <h2>Voting Registration Rejected</h2>
                <p>Dear {$student['first_name']},</p>
                <p>We regret to inform you that your voting registration has been rejected for the following reason:</p>
                <p><strong>{$reason}</strong></p>
                <p>Please review your registration details and correct any issues before reapplying.</p>
                <p>If you believe this was a mistake, please contact your election administrator.</p>
                <p>Thank you!</p>
            ";
            $mail->AltBody = "Your voting registration has been rejected. Reason: {$reason}. Please review your registration details and correct any issues before reapplying.";

            $mail->send();

            // Update email capacity
            $updateStmt = $pdo->prepare("UPDATE email SET capacity = capacity + 1 WHERE id = ?");
            $updateStmt->execute([$emailConfig['id']]);

            // Update email_role_log
            $checkLog = $pdo->prepare("SELECT id FROM email_role_log WHERE student_id = ?");
            $checkLog->execute([$student_id]);
            if ($checkLog->fetch()) {
                $updateLog = $pdo->prepare("UPDATE email_role_log SET count = count + 1, status = 'sent' WHERE student_id = ?");
                $updateLog->execute([$student_id]);
            } else {
                $insertLog = $pdo->prepare("INSERT INTO email_role_log (student_id, status, count) VALUES (?, 'sent', 1)");
                $insertLog->execute([$student_id]);
            }

            // Commit transaction if everything succeeded
            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'message' => 'Voter rejected and notification email sent.'
            ]);
        } catch (Exception $e) {
            $pdo->commit(); // Still update status even if email fails
            echo json_encode([
                'status' => 'success',
                'message' => "Voter rejected but email could not be sent. Error: {$mail->ErrorInfo}"
            ]);
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
}
