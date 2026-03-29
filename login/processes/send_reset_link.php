<?php
require '../includes/conn.php';
require '../../vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json');

$email = $_POST['email'] ?? '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

// Check if user exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'No account found with that email.']);
    exit;
}

// Generate token
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Save to password_resets table
$stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token=?, expires_at=?");
$stmt->execute([$email, $token, $expires, $token, $expires]);

// Get sender email credentials
$stmt = $pdo->prepare("SELECT email, app_Password FROM email LIMIT 1");
$stmt->execute();
$sender = $stmt->fetch();

if (!$sender) {
    echo json_encode(['success' => false, 'message' => 'System email configuration missing.']);
    exit;
}

// Send Email via PHPMailer
$mail = new PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $sender['email'];
    $mail->Password   = $sender['app_Password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom($sender['email'], 'WMSU Voting System');
    $mail->addAddress($email, 'WMSU Voting System');

    // Add embedded image
    $mail->addEmbeddedImage('../../external/img/wmsu-logo.png', 'wmsu_logo');

    // Dynamic link generation
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $path = str_replace('\\', '/', dirname(dirname($_SERVER['PHP_SELF']))); // Go up one level to /login/
    $resetLink = "$protocol://$host$path/reset_password.php?email=" . urlencode($email) . "&token=$token";

    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request';

    $imagePath1 = 'logo-left.png';
    $imagePath2 = 'logo-right.png';
    $imagePath3 = 'banner.png';

    $mail->addEmbeddedImage($imagePath1, 'logo-left');
    $mail->addEmbeddedImage($imagePath2, 'logo-right');
    $mail->addEmbeddedImage($imagePath3, 'banner');


    $mail->Body = '
<div style="background-color: #f4f4f4; padding: 20px; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
        
        <div style="background-color: #800000; padding: 20px; text-align: center;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td align="left" width="60">
                        <img src="cid:logo-left" alt="WMSU" style="height: 60px; width: auto; display: block;">
                    </td>
                    <td align="center" style="color: #ffffff;">
                        <h1 style="margin: 0; font-size: 22px; letter-spacing: 1px; text-transform: uppercase;">WMSU Voting System</h1>
                        <span style="font-size: 12px; opacity: 0.8;">Western Mindanao State University</span>
                    </td>
                    <td align="right" width="60">
                        <img src="cid:logo-right" alt="COMELEC" style="height: 60px; width: auto; display: block;">
                    </td>
                </tr>
            </table>
        </div>

        <div style="padding: 40px 30px; color: #333333;">
            <h2 style="margin-top: 0; color: #800000; font-size: 20px;">Password Reset Request</h2>
            <p>Hello,</p>
            <p>We received a request to reset the password for your WMSU Voting account. To ensure the security of your account, please click the button below to set a new password:</p>
            
            <div style="text-align: center; margin: 35px 0;">
                <a href="' . $resetLink . '" style="background-color: #800000; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">Reset My Password</a>
            </div>

            <div style="background-color: #fff8f8; border-left: 4px solid #800000; padding: 15px; margin-bottom: 20px;">
                <p style="margin: 0; font-size: 13px; color: #666;">
                    <strong>Security Note:</strong> This link will expire in <strong>1 hour</strong>. If you did not make this request, you can safely ignore this email; your account remains secure.
                </p>
            </div>
        </div>

        <div style="background-color: #f9f9f9; border-top: 1px solid #eeeeee; text-align: center;">
             <img src="cid:banner" alt="WMSU Banner" style="width: 100%; max-width: 600px; height: auto; display: block;">
             <div style="padding: 20px; font-size: 11px; color: #999999; line-height: 1.4;">
                &copy; ' . date("Y") . ' WMSU University Student Election Policy.<br>
                This is an automated message, please do not reply.
             </div>
        </div>
    </div>
</div>';

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Reset link sent to your email.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Email could not be sent.']);
}
