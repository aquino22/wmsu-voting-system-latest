<?php
session_start();
require_once '../includes/conn.php'; // Database connection
require '../vendor/autoload.php'; // PHPMailer
require 'vendors/phpqrcode/qrlib.php'; // QR Code library

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Verify session email
if (!isset($_SESSION['email'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No user email found in session. Please log in.'
    ]);
    exit;
}

$stmt = $pdo->prepare("SELECT election_name FROM elections WHERE status = 'Ongoing' LIMIT 1");

// Execute the query
$stmt->execute();

// Fetch result
$ongoingElection = $stmt->fetch(PDO::FETCH_ASSOC);

if ($ongoingElection) {
    $electionName = $ongoingElection['election_name'];
} else {
    $electionName = null; // no ongoing election
}



// Verify advisers table structure
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM advisers");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredColumns = ['email', 'smtp_email_id', 'college', 'department'];
    if (!empty(array_diff($requiredColumns, $columns))) {
        throw new Exception('Missing required columns in advisers table.');
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error checking advisers table structure: ' . $e->getMessage()
    ]);
    exit;
}

// Verify email table structure
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM email");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredColumns = ['id', 'email', 'app_password', 'capacity'];
    if (!empty(array_diff($requiredColumns, $columns))) {
        throw new Exception('Missing required columns in email table.');
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error checking email table structure: ' . $e->getMessage()
    ]);
    exit;
}

// Fetch adviser's college, department, and smtp_email_id
try {
    $stmt = $pdo->prepare("SELECT smtp_email_id, college, department FROM advisers WHERE email = ?");
    $stmt->execute([$_SESSION['email']]);
    $adviser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$adviser || empty($adviser['smtp_email_id']) || empty($adviser['college']) || empty($adviser['department'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No valid SMTP email ID, college, or department found for adviser.'
        ]);
        exit;
    }
    $smtp_email_id = $adviser['smtp_email_id'];
    $college = $adviser['college'];
    $department = $adviser['department'];
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching adviser data: ' . $e->getMessage()
    ]);
    exit;
}

// Fetch SMTP credentials from email table
try {
    $stmt = $pdo->prepare("SELECT id, email, app_password FROM email WHERE id = ?");
    $stmt->execute([$smtp_email_id]);
    $emailConfig = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emailConfig || empty($emailConfig['email']) || empty($emailConfig['app_password'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No valid email configuration found for SMTP email ID ' . $smtp_email_id
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching email configuration: ' . $e->getMessage()
    ]);
    exit;
}

// Fetch voters matching adviser's college and department
try {
    $stmt = $pdo->prepare("SELECT student_id, email FROM voters WHERE LOWER(college) = LOWER(?) AND LOWER(department) = LOWER(?) AND email AND lower(election_name) = LOWER(?) IS NOT NULL");
    $stmt->execute([$college, $department, $electionName]);
    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($voters)) {
        echo json_encode([
            'status' => 'no_voters',
            'message' => 'No voters found for your college and department.'
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching voters: ' . $e->getMessage()
    ]);
    exit;
}

// Process each voter
$successCount = 0;
$failedCount = 0;
$tempDir = '../../qr_codes/';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

foreach ($voters as $voter) {
    $email = $voter['email'];
    $student_id = $voter['student_id'];

    $qrFile = $tempDir . 'qr_' . $student_id . '.png';
    $status = 'failed';
    $notes = '';

    // Generate QR
    try {
        QRcode::png($student_id, $qrFile, QR_ECLEVEL_L, 10);
        $status = 'pending';
        $notes = 'QR generated successfully';
    } catch (Exception $e) {
        $failedCount++;
        $status = 'failed';
        $notes = 'QR Generation Error: ' . $e->getMessage();
        error_log($notes);

        // Log
        try {
            $stmtLog = $pdo->prepare("INSERT INTO qr_sending_log (sender, student_id, election, status, notes) VALUES (?, ?, ?, ?, ?)");
            $stmtLog->execute([$_SESSION['email'], $student_id, $electionName, $status, $notes]);
        } catch (PDOException $e) {
            error_log("Logging Error for $student_id: " . $e->getMessage());
        }
        continue;
    }

    // Send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $emailConfig['email'];
        $mail->Password = $emailConfig['app_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($emailConfig['email'], 'WMSU - Student Affairs');
        $mail->addAddress($email);
        $mail->addAttachment($qrFile, 'qr_code.png');

        $mail->isHTML(true);
        $mail->Subject = 'Your Voting QR Code';
        $mail->Body = "Dear Student,<br><br>"
            . "Attached is your QR code for the voting system (Student ID: " . htmlspecialchars($student_id) . ").<br>"
            . "Please present this QR code at the voting booth.<br><br>"
            . "Best Regards,<br>Voting Team";

        $mail->send();
        $successCount++;
        $status = 'sent';
        $notes = 'Email sent successfully';

        // Increment capacity
        $stmt = $pdo->prepare("UPDATE email SET capacity = capacity + 1 WHERE id = ?");
        $stmt->execute([$smtp_email_id]);

    } catch (Exception $e) {
        $failedCount++;
        $status = 'failed';
        $notes = "Email Error: " . $mail->ErrorInfo;
        error_log("Email Error for $email: " . $mail->ErrorInfo);
    }

    // Log
    try {
        $stmtLog = $pdo->prepare("INSERT INTO qr_sending_log (sender, student_id, election, status, notes) VALUES (?, ?, ?, ?, ?)");
        $stmtLog->execute([$_SESSION['email'], $student_id, $electionName, $status, $notes]);
    } catch (PDOException $e) {
        error_log("Logging Error for $student_id: " . $e->getMessage());
    }

    // Clean up QR
    if (file_exists($qrFile)) {
        unlink($qrFile);
    }
}


// Return results
echo json_encode([
    'status' => 'success',
    'successCount' => $successCount,
    'failedCount' => $failedCount,
    'message' => 'QR code sending completed.'
]);
exit;
