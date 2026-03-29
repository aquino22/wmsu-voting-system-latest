<?php
session_start();
require '../../includes/conn.php';
require_once '../../vendors/autoload.php';

use chillerlan\QRCode\QRCode as QRCode;
use chillerlan\QRCode\QROptions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Endroid\QrCode\Writer\PngWriter;

// Set headers for streaming
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Flush output buffer
ob_implicit_flush(true);
ob_end_flush();

// Function to send stream data
function sendStreamData($data)
{
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

// Function to generate random 4-character string (uppercase A-Z)
function generateRandomChars()
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random = '';
    for ($i = 0; $i < 4; $i++) {
        $random .= $chars[rand(0, 25)];
    }
    return $random;
}

// Function to parse value after dash from combined string
function parseValueAfterDash($combined)
{
    $combined = trim($combined);
    if (strpos($combined, ' - ') !== false) {
        $parts = explode(' - ', $combined, 2);
        $value = trim($parts[1] ?? '');
        // Standardize value
        if (strtolower($value) === 'computer science' || strtolower($value) === 'bs cs') {
            $value = 'Computer Science';
        } elseif (strtolower($value) === 'information technology' || strtolower($value) === 'bs it') {
            $value = 'Information Technology';
        } elseif (strtolower($value) === 'computer engineering' || strtolower($value) === 'bs ce') {
            $value = 'Computer Engineering';
        }
        return $value;
    }
    return $combined; // Return original if no dash
}

// Function to get adviser's email credentials
function getAdviserEmailCredentials($pdo, $adviserId)
{
    $stmt = $pdo->prepare("
        SELECT e.email, e.app_password, e.capacity
        FROM email e
        WHERE e.adviser_id = ?
        ORDER BY e.capacity ASC
    ");
    $stmt->execute([$adviserId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to update email capacity
function updateEmailCapacity($pdo, $email)
{
    $stmt = $pdo->prepare("
        UPDATE email
        SET capacity = capacity + 1
        WHERE email = ?
    ");
    $stmt->execute([$email]);
}

// Function to generate QR code
function generateQrCode(string $studentId, string $uploadDir): string
{
    $options = new QROptions([
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'   => QRCode::ECC_L, // Low error correction
        'scale'      => 5,             // Size multiplier (each QR module is 5x5 pixels)
        'imageBase64' => false,
    ]);

    $fileName = $uploadDir . 'qr_' . $studentId . '_' . time() . '.png';

    // Generate and save QR code
    (new QRCode($options))->render($studentId, $fileName);

    return $fileName;
}

// Function to send email
function sendEmail($pdo, $adviserId, $toEmail, $studentData, $password, $uploadDir)
{
    $mail = new PHPMailer(true);
    try {
        // Get available email credentials
        $emailCredentials = getAdviserEmailCredentials($pdo, $adviserId);
        if (empty($emailCredentials)) {
            error_log("No email credentials found for adviser_id: $adviserId");
            return false;
        }

        $selectedEmail = null;
        foreach ($emailCredentials as $cred) {
            if ($cred['capacity'] < 500) {
                $selectedEmail = $cred;
                break;
            }
        }

        if (!$selectedEmail) {
            error_log("No email with capacity < 500 for adviser_id: $adviserId");
            return false;
        }

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $selectedEmail['email'];
        $mail->Password = $selectedEmail['app_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($selectedEmail['email'], 'WMSU Voter System');
        $mail->addAddress($toEmail);

        // Generate QR code
        $qrFilePath = generateQrCode($studentData['Student ID'], $uploadDir);
        $mail->addAttachment($qrFilePath, 'voter_qr_code.png');

        $mail->isHTML(true);
        $mail->Subject = 'WMSU Voter Registration Confirmation';
        $mail->Body = "Hello {$studentData['First Name']},<br><br>Your voter registration has been successfully processed.<br><br>Student ID: {$studentData['Student ID']}<br>College: {$studentData['College']}<br>Course: {$studentData['Course']}<br>Email: $toEmail<br>Password: $password<br><br>Please find your QR code attached. This QR code contains your Student ID and can be used for voting purposes. Keep it secure.<br><br>Please keep your password secure and use it to log in to the WMSU Voter System.<br><br>Best regards,<br>WMSU Voter System Team";
        $mail->AltBody = "Hello {$studentData['First Name']},\n\nYour voter registration has been successfully processed.\n\nStudent ID: {$studentData['Student ID']}\nCollege: {$studentData['College']}\nCourse: {$studentData['Course']}\nEmail: $toEmail\nPassword: $password\n\nA QR code containing your Student ID is attached for voting purposes. Keep it secure.\n\nPlease keep your password secure and use it to log in to the WMSU Voter System.\n\nBest regards,\nWMSU Voter System Team";

        $mail->send();

        // Increment capacity for the used email
        updateEmailCapacity($pdo, $selectedEmail['email']);

        // Clean up QR code file
        if (file_exists($qrFilePath)) {
            unlink($qrFilePath);
        }

        return true;
    } catch (Exception $e) {
        // Clean up QR code file on failure
        if (isset($qrFilePath) && file_exists($qrFilePath)) {
            unlink($qrFilePath);
        }
        error_log("Email sending failed to $toEmail using {$selectedEmail['email']}: " . $e->getMessage());
        $stmt = $pdo->prepare("
            INSERT INTO email_errors (adviser_id, email, recipient_email, error_message, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$adviserId, $selectedEmail['email'], $toEmail, $e->getMessage()]);
        return false;
    }
}

try {
    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
        sendStreamData(['status' => 'error', 'message' => 'Invalid request or no file uploaded']);
        exit;
    }

    // Validate file type
    $allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    $file = $_FILES['file'];
    if (!in_array($file['type'], $allowedTypes)) {
        sendStreamData(['status' => 'error', 'message' => 'Invalid file type. Please upload a .csv, .xls, or .xlsx file']);
        exit;
    }

    // Validate college and department
    $college = filter_input(INPUT_POST, 'college', FILTER_SANITIZE_STRING);
    $department = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING);
    if (empty($college) || empty($department)) {
        sendStreamData(['status' => 'error', 'message' => 'College and department are required']);
        exit;
    }

    // Save uploaded file
    $uploadDir = 'Uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $fileName = time() . '_' . basename($file['name']);
    $filePath = $uploadDir . $fileName;
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        sendStreamData(['status' => 'error', 'message' => 'Failed to save uploaded file']);
        exit;
    }

    // Load spreadsheet
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);
    $header = array_shift($rows);

    $totalRows = count($rows);
    $votersAdded = 0;
    $emailsSent = 0;
    $pdo->beginTransaction();

    // Get adviser ID and year level from session/database
    $adviserId = $_SESSION['user_id'] ?? null;
    if (!$adviserId) {
        sendStreamData(['status' => 'error', 'message' => 'Adviser not logged in']);
        exit;
    }

    // Get adviser's year level
    $stmt = $pdo->prepare("SELECT year FROM advisers WHERE id = ?");
    $stmt->execute([$adviserId]);
    $adviserYearLevel = $stmt->fetchColumn();

    if (!$adviserYearLevel) {
        sendStreamData(['status' => 'error', 'message' => 'Adviser year level not found']);
        exit;
    }

    // Check email capacity before processing
    $emailCredentials = getAdviserEmailCredentials($pdo, $adviserId);
    $canSendEmails = false;
    foreach ($emailCredentials as $cred) {
        if ($cred['capacity'] < 500) {
            $canSendEmails = true;
            break;
        }
    }

    foreach ($rows as $index => $row) {
        $studentData = [
            'Timestamp' => $row['A'] ?? '',
            'Student ID' => $row['B'] ?? '',
            'WMSU Email' => $row['C'] ?? '',
            'First Name' => $row['D'] ?? '',
            'Middle Name' => $row['E'] ?? '',
            'Last Name' => $row['F'] ?? '',
            'Year Level' => $row['G'] ?? '',
            'Semester' => $row['H'] ?? '',
            'College' => $row['I'] ?? '',
            'Course' => $row['J'] ?? '',
            'Department' => $row['K'] ?? '',
            'WMSU Campus Studying' => $row['L'] ?? '',
            'WMSU ESU Campus' => $row['M'] ?? '',
            'COR' => $row['N'] ?? ''
        ];

        // Skip if year level doesn't match adviser's year level
        if (strtolower(trim($studentData['Year Level'])) !== strtolower(trim($adviserYearLevel))) {
            error_log("Row " . ($index + 2) . " skipped due to year level mismatch. Adviser: $adviserYearLevel, Student: {$studentData['Year Level']}");
            continue;
        }

        // Validate email format
        if (!filter_var($studentData['WMSU Email'], FILTER_VALIDATE_EMAIL)) {
            error_log("Row " . ($index + 2) . " skipped due to invalid email: {$studentData['WMSU Email']}");
            continue;
        }

        // Parse course and department to get values after dash
        $studentData['Course'] = parseValueAfterDash($studentData['Course']);
        $studentData['Department'] = parseValueAfterDash($studentData['Department']);

        // Validate college and department against form input
        if (
            strtolower(trim($studentData['College'])) !== strtolower(trim($college)) ||
            strtolower(trim($studentData['Department'])) !== strtolower(trim($department))
        ) {
            error_log("Row " . ($index + 2) . " skipped due to college/department mismatch. Spreadsheet: College={$studentData['College']}, Department={$studentData['Department']}; Form: College=$college, Department=$department");
            continue;
        }

        // Handle standardization for College of Computing Studies
        $finalCollege = trim($studentData['College']);
        $finalCourse = trim($studentData['Course']);
        $finalDepartment = trim($studentData['Department']);

        if (
            strtolower($finalCollege) === 'computing studies' ||
            strtolower($finalCollege) === 'college of computing studies'
        ) {
            $finalCollege = 'College of Computing Studies';
            if (
                strtolower($finalCourse) === 'computer science' ||
                strtolower($finalCourse) === 'bs cs'
            ) {
                $finalCourse = 'Computer Science';
                $finalDepartment = 'Computer Science';
            } elseif (
                strtolower($finalCourse) === 'information technology' ||
                strtolower($finalCourse) === 'bs it'
            ) {
                $finalCourse = 'Information Technology';
                $finalDepartment = 'Information Technology';
            }
        }

        // Generate password
        $randomChars = generateRandomChars();
        $passwordPlain = $studentData['Student ID'] . 'wmsuvoter_' . $randomChars;
        $password = password_hash($passwordPlain, PASSWORD_DEFAULT);

        // Insert into users table
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, role, created_at)
            VALUES (?, ?, 'voter', NOW())
            ON DUPLICATE KEY UPDATE password = ?, role = 'voter'
        ");
        $stmt->execute([$studentData['WMSU Email'], $password, $password]);

        // Insert into voters table
        $stmt = $pdo->prepare("
            INSERT INTO voters (student_id, email, password, first_name, middle_name, last_name, course, year_level, college, department, wmsu_campus, external_campus)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                first_name = ?, middle_name = ?, last_name = ?, course = ?, year_level = ?, college = ?, department = ?, password = ?, wmsu_campus = ?, external_campus = ?
        ");
        $stmt->execute([
            $studentData['Student ID'],
            $studentData['WMSU Email'],
            $password,
            $studentData['First Name'],
            $studentData['Middle Name'],
            $studentData['Last Name'],
            $finalCourse,
            $studentData['Year Level'],
            $finalCollege,
            $finalDepartment,
            $studentData['WMSU Campus Studying'],
            $studentData['WMSU ESU Campus'],
            // For ON DUPLICATE KEY UPDATE
            $studentData['First Name'],
            $studentData['Middle Name'],
            $studentData['Last Name'],
            $finalCourse,
            $studentData['Year Level'],
            $finalCollege,
            $finalDepartment,
            $password,
            $studentData['WMSU Campus Studying'],
            $studentData['WMSU ESU Campus']
        ]);

        // Send email with password and QR code
        if ($canSendEmails && sendEmail($pdo, $adviserId, $studentData['WMSU Email'], $studentData, $password, $uploadDir)) {
            $emailsSent++;
        }

        $votersAdded++;

        // Send progress update
        sendStreamData([
            'status' => 'progress',
            'current' => $votersAdded,
            'total' => $totalRows
        ]);
    }

    // Log import details
    $adviserEmail = $_SESSION['email'] ?? 'unknown@example.com';
    $stmt = $pdo->prepare("
        INSERT INTO adviser_import_details (file, date, status, voters_added, emails_sent, adviser_email)
        VALUES (?, NOW(), 'completed', ?, ?, ?)
    ");
    $stmt->execute([$fileName, $votersAdded, $emailsSent, $adviserEmail]);

    $pdo->commit();

    // Send completion message
    sendStreamData([
        'status' => 'complete',
        'message' => "Imported $votersAdded out of $totalRows voters successfully. Sent $emailsSent emails."
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendStreamData([
        'status' => 'error',
        'message' => 'Import failed: ' . $e->getMessage()
    ]);
    error_log("Import failed: " . $e->getMessage());
}

// Clean up
if (isset($filePath) && file_exists($filePath)) {
    unlink($filePath);
}
