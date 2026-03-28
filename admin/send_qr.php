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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['student_id'])) {
    $email = $_POST['email'];
    $student_id = $_POST['student_id'];

    // Create QR code data (Voting link)
    $qrData = urlencode($student_id);

    // Define QR Code file path
    $qrFilePath = "qr_codes/" . md5($student_id) . ".png";

    // Generate and save QR Code locally
    $options = new QROptions([
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel' => QRCode::ECC_H, // High error correction
    ]);

    (new QRCode($options))->render($qrData, $qrFilePath);

    // Setup PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // SMTP host
        $mail->SMTPAuth = true;
        $mail->Username = 'madahniqa@gmail.com'; // Your SMTP username
        $mail->Password = 'kchq meua husx atrb'; // Your SMTP password (use env variables instead)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('madahniqa@gmail.com', 'WMSU - Student Affairs');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = "WMSU General Election's QR Code";
        $mail->Body = "Dear Student,<br><br>Please find your election QR code attached.<br>Scan this code to vote.<br><br>Thank you.";
        $mail->addAttachment($qrFilePath); // Attach QR Code image

        if ($mail->send()) {
            echo json_encode(["status" => "success", "message" => "QR Code sent successfully to $email"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to send QR Code"]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Mail Error: " . $mail->ErrorInfo]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
}


?>
