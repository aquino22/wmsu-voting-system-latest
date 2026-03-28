<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json'); // Ensure JSON output

require_once 'includes/conn.php'; // Database connection
require '../vendor/autoload.php'; // Include Composer's autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['students'])) {
    $students = json_decode($_POST['students'], true);

    if (!is_array($students)) {
        echo json_encode(["success" => false, "message" => "Invalid students data format."]);
        exit;
    }

    // Ensure QR code folder exists
    if (!file_exists('qrcodes')) {
        mkdir('qrcodes', 0777, true);
    }

    $results = [];

    foreach ($students as $student) {
        $email = filter_var($student['email'], FILTER_SANITIZE_EMAIL);
        $student_id = htmlspecialchars($student['student_id']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $results[] = ["email" => $email, "success" => false, "message" => "Invalid email format."];
            continue;
        }

        
        // Generate QR Code
        $qrCodePath = "qrcodes/" . md5($student_id) . ".png";
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_H, // High error correction
        ]);
        (new QRCode($options))->render($student_id, $qrCodePath);

        // Send Email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // SMTP host
            $mail->SMTPAuth = true;
            $mail->Username = 'mistyantelope@gmail.com'; // Your SMTP username
            $mail->Password = 'qgam kybv jwqn ahbh'; // Your SMTP password (use env variables instead)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
      

            $mail->setFrom('mistyantelope@gmail.com', 'WMSU - Student Affairs');
            $mail->addAddress($email);
            $mail->addAttachment($qrCodePath); // Attach QR Code

            $mail->isHTML(true);
            $mail->Subject = 'QR Code for Voting';
            $mail->Body = "Dear Student,<br><br>Your QR Code for the voting system is attached.<br><br>Best Regards,<br>Voting Team";

            $mail->send();
            $results[] = ["email" => $email, "success" => true, "message" => "QR Code sent successfully."];
        } catch (Exception $e) {
            $results[] = ["email" => $email, "success" => false, "message" => "Email failed: " . $mail->ErrorInfo];
        }
    }

    echo json_encode(["batch_results" => $results]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}


?>
