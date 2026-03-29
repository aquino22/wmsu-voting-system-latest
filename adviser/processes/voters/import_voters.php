<?php
session_start();
require '../../includes/conn.php';
require_once '../../vendors/autoload.php';

use chillerlan\QRCode\QRCode as QRCode;
use chillerlan\QRCode\QROptions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set headers for streaming
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Flush output buffer
ob_implicit_flush(true);
ob_end_flush();

// Function to send stream data
function sendStreamData($data) {
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

// Function to generate random 4-character string (uppercase A-Z)
function generateRandomChars() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random = '';
    for ($i = 0; $i < 4; $i++) {
        $random .= $chars[rand(0, 25)];
    }
    return $random;
}

// Function to parse value after dash from combined string
function parseValueAfterDash($combined) {
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
    return $combined;
}

// Function to get adviser's email credentials
function getAdviserEmailCredentials($pdo, $adviserId) {
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
function updateEmailCapacity($pdo, $email) {
    $stmt = $pdo->prepare("
        UPDATE email
        SET capacity = capacity + 1
        WHERE email = ?
    ");
    $stmt->execute([$email]);
}

// Function to generate QR code
function generateQrCode(string $studentId, string $uploadDir): string {
    $options = new QROptions([
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'   => QRCode::ECC_L,
        'scale'      => 5,
        'imageBase64' => false,
    ]);

    $fileName = $uploadDir . 'qr_' . $studentId . '_' . time() . '.png';
    (new QRCode($options))->render($studentId, $fileName);
    return $fileName;
}

// Function to send email
function sendEmail($pdo, $adviserId, $toEmail, $studentData, $password, $uploadDir) {
    $mail = new PHPMailer(true);
    try {
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

        $qrFilePath = generateQrCode($studentData['Student ID'], $uploadDir);
        $mail->addAttachment($qrFilePath, 'voter_qr_code.png');

        $mail->isHTML(true);
        $mail->Subject = 'WMSU Voter Registration Confirmation';
        $mail->Body = "Hello {$studentData['First Name']},<br><br>Your voter registration has been successfully processed.<br><br>Student ID: {$studentData['Student ID']}<br>College: {$studentData['College']}<br>Course: {$studentData['Course']}<br>Email: $toEmail<br>Password: $password<br><br>Please find your QR code attached. This QR code contains your Student ID and can be used for voting purposes. Keep it secure.<br><br>Please keep your password secure and use it to log in to the WMSU Voter System.<br><br>Best regards,<br>WMSU Voter System Team";
        $mail->AltBody = "Hello {$studentData['First Name']},\n\nYour voter registration has been successfully processed.\n\nStudent ID: {$studentData['Student ID']}\nCollege: {$studentData['College']}\nCourse: {$studentData['Course']}\nEmail: $toEmail\nPassword: $password\n\nA QR code containing your Student ID is attached for voting purposes. Keep it secure.\n\nPlease keep your password secure and use it to log in to the WMSU Voter System.\n\nBest regards,\nWMSU Voter System Team";

        $mail->send();
        updateEmailCapacity($pdo, $selectedEmail['email']);
        if (file_exists($qrFilePath)) {
            unlink($qrFilePath);
        }
        return true;
    } catch (Exception $e) {
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

// Function to extract text from image using Tesseract OCR
function findTextInImage($imagePath, $searchTexts, $tesseractPath = 'tesseract') {
    // Check if file exists
    if (!file_exists($imagePath)) {
        return ['error' => 'Image file not found'];
    }

    // Validate search texts
    if (!is_array($searchTexts)) {
        $searchTexts = [$searchTexts];
    }

    // Create a temporary file for OCR output
    $tempFile = tempnam(sys_get_temp_dir(), 'ocr');
    $outputFile = $tempFile . '.txt';

    // Execute Tesseract OCR
    $command = escapeshellcmd("$tesseractPath " . escapeshellarg($imagePath) . " " . escapeshellarg($tempFile));
    exec($command, $output, $returnCode);

    if ($returnCode !== 0) {
        return ['error' => 'OCR processing failed'];
    }

    // Read the OCR results
    $ocrText = file_get_contents($outputFile);
    // unlink($outputFile); // Clean up temporary file
    // unlink($tempFile);   // Clean up temporary file

    // Analyze results
    $foundTexts = [];
    $missingTexts = [];
    $allTextsFound = true;

    foreach ($searchTexts as $text) {
        if (stripos($ocrText, $text) !== false) {
            $foundTexts[] = $text;
        } else {
            $missingTexts[] = $text;
            $allTextsFound = false;
        }
    }

    return [
        'found' => $foundTexts,
        'missing' => $missingTexts,
        'all_found' => $allTextsFound,
        'ocr_text' => $ocrText,
    ];
}

// Function to clean name for comparison
function cleanName($name) {
    // Remove extra spaces, special characters, and convert to lowercase
    if (empty($name)) return '';
    $name = preg_replace('/[^A-Za-z0-9\s]/', '', $name);
    $name = preg_replace('/\s+/', ' ', trim($name));
    return strtolower($name);
}

// Function to process PDF with image comparison
function processPdfWithImageComparison($pdfPath, $excelFilePath, $college, $department, $uploadDir) {
    // Convert PDF to images for OCR
    $tempDir = sys_get_temp_dir() . '/pdf_images_' . uniqid();
    mkdir($tempDir, 0755, true);
    $imagePrefix = $tempDir . '/page';
    
    // Convert PDF to high-resolution images (300 DPI)
    exec("pdftoppm -png -r 300 " . escapeshellarg($pdfPath) . " " . escapeshellarg($imagePrefix), $output, $returnCode);
    
    if ($returnCode !== 0) {
        return [
            'status' => 'error',
            'message' => 'PDF to image conversion failed',
            'students' => [],
            'mismatches' => []
        ];
    }

    $imageFiles = glob($tempDir . '/page-*.png');
    
    // Load Excel data
    $excelSpreadsheet = IOFactory::load($excelFilePath);
    $excelSheet = $excelSpreadsheet->getActiveSheet();
    $excelRows = $excelSheet->toArray(null, true, true, true);
    array_shift($excelRows); // Remove header
    
    $excelStudents = [];
    foreach ($excelRows as $row) {
        if (!empty($row['B']) && !empty($row['D']) && !empty($row['F'])) { // Require Student ID, First Name, Last Name
            $excelStudents[] = [
                'Student ID' => $row['B'],
                'First Name' => $row['D'],
                'Last Name' => $row['F'],
                'Middle Name' => $row['E'] ?? '',
                'College' => $row['I'] ?? '',
                'Department' => $row['K'] ?? '',
                'Course' => $row['J'] ?? '',
                'Year Level' => $row['G'] ?? '',
                'Search Texts' => [ // Texts we'll search for in the image
                    $row['B'], // Student ID
                    $row['D'] . ' ' . $row['F'], // Full name
                    $row['I'] // College
                ]
            ];
        }
    }

    $matchedStudents = [];
    $mismatches = [];

    foreach ($excelStudents as $excelStudent) {
        $foundInImage = false;
        $ocrResults = [];
        
        // Search for this student's data in each image page
        foreach ($imageFiles as $imageFile) {
            $result = findTextInImage($imageFile, $excelStudent['Search Texts']);
            $ocrResults[] = $result['ocr_text'];
            
            if ($result['all_found']) {
                $foundInImage = true;
                break;
            }
        }

        if ($foundInImage) {
            // Create student record using Excel data (since we found it in the image)
            $matchedStudents[] = [
                'Student ID' => $excelStudent['Student ID'],
                'First Name' => $excelStudent['First Name'],
                'Last Name' => $excelStudent['Last Name'],
                'Middle Name' => $excelStudent['Middle Name'],
                'Year Level' => $excelStudent['Year Level'],
                'College' => $excelStudent['College'],
                'Course' => parseValueAfterDash($excelStudent['Course']),
                'Department' => parseValueAfterDash($excelStudent['Department']),
                'WMSU Email' => '', // Will be generated later
                'Excel Data' => $excelStudent,
                'OCR Results' => $ocrResults,
                'Found In Image' => true
            ];
        } else {
            $mismatches[] = [
                'student_id' => $excelStudent['Student ID'],
                'excel_name' => $excelStudent['Last Name'] . ', ' . $excelStudent['First Name'],
                'reason' => 'Data not found in image',
                'search_texts' => $excelStudent['Search Texts'],
                'ocr_results' => $ocrResults
            ];
        }
    }

    // Clean up image files
    // // array_map('unlink', $imageFiles);
    // rmdir($tempDir);

    return [
        'status' => !empty($matchedStudents) ? 'success' : 'error',
        'message' => !empty($matchedStudents) ? 'Students found in images' : 'No students found in images',
        'students' => $matchedStudents,
        'mismatches' => $mismatches
    ];
}

// Function to log match/mismatch results to a text file
function logMatchResults($matchedStudents, $mismatches, $uploadDir) {
    $logFileName = $uploadDir . 'match_results_' . time() . '.txt';
    $logContent = "WMSU Voter System Match Results\n";
    $logContent .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";

    $logContent .= "=== Matched Records ===\n";
    if (empty($matchedStudents)) {
        $logContent .= "No matched records found.\n";
    } else {
        foreach ($matchedStudents as $student) {
            $logContent .= "Student ID: {$student['Student ID']}\n";
            $logContent .= "Name: {$student['Last Name']}, {$student['First Name']}\n";
            $logContent .= "Found in Image: " . ($student['Found In Image'] ? 'Yes' : 'No') . "\n";
            $logContent .= "College: {$student['College']}\n";
            $logContent .= "Course: {$student['Course']}\n\n";
        }
    }

    $logContent .= "=== Mismatched Records ===\n";
    if (empty($mismatches)) {
        $logContent .= "No mismatched records found.\n";
    } else {
        foreach ($mismatches as $mismatch) {
            $logContent .= "Student ID: {$mismatch['student_id']}\n";
            $logContent .= "Name: " . ($mismatch['excel_name'] ?? 'N/A') . "\n";
            $logContent .= "Reason: {$mismatch['reason']}\n";
            $logContent .= "Searched For: " . implode(', ', $mismatch['search_texts']) . "\n\n";
        }
    }

    if (!is_writable($uploadDir)) {
        error_log("Upload directory ($uploadDir) is not writable");
        return false;
    }

    if (file_put_contents($logFileName, $logContent) === false) {
        error_log("Failed to write match results to $logFileName");
        return false;
    }

    return $logFileName;
}

try {
    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
        sendStreamData(['status' => 'error', 'message' => 'Invalid request or no file uploaded']);
        exit;
    }

    // Validate file type
    $allowedTypes = [
        'text/csv', 
        'application/vnd.ms-excel', 
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/pdf'
    ];
    
    $file = $_FILES['file'];
    if (!in_array($file['type'], $allowedTypes)) {
        sendStreamData(['status' => 'error', 'message' => 'Invalid file type. Please upload a .csv, .xls, .xlsx or .pdf file']);
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
        if (!mkdir($uploadDir, 0777, true)) {
            sendStreamData(['status' => 'error', 'message' => 'Failed to create upload directory']);
            exit;
        }
    }
    $fileName = time() . '_' . basename($file['name']);
    $filePath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        sendStreamData(['status' => 'error', 'message' => 'Failed to save uploaded file']);
        exit;
    }

    $rows = [];
    $totalRows = 0;
    $mismatches = [];
    $matchedStudents = [];
    $logFileName = null;

    // PDF Processing with Image Comparison
    if ($file['type'] === 'application/pdf') {
        // Require Excel file
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            // unlink($filePath);
            sendStreamData(['status' => 'error', 'message' => 'Excel file is required when uploading a PDF']);
            exit;
        }

        // Save Excel file
        $excelFile = $_FILES['excel_file'];
        $excelFilePath = $uploadDir . time() . '_' . basename($excelFile['name']);
        if (!move_uploaded_file($excelFile['tmp_name'], $excelFilePath)) {
            // unlink($filePath);
            sendStreamData(['status' => 'error', 'message' => 'Failed to save Excel file']);
            exit;
        }

        // Process with image comparison
        $result = processPdfWithImageComparison($filePath, $excelFilePath, $college, $department, $uploadDir);
        
        if ($result['status'] !== 'success' || empty($result['students'])) {
            // unlink($filePath);
            // unlink($excelFilePath);
            $logFileName = logMatchResults([], $result['mismatches'], $uploadDir);
            sendStreamData([
                'status' => 'error',
                'message' => $result['message'] ?? 'No matching students found between PDF and Excel',
                'details' => $result['mismatches'],
                'log_file' => $logFileName
            ]);
            exit;
        }

        $rows = $result['students'];
        $totalRows = count($rows);
        $mismatches = $result['mismatches'];
        
        // Log results
        $logFileName = logMatchResults($rows, $mismatches, $uploadDir);
        // unlink($excelFilePath);
    } else {
        // Process spreadsheet files (CSV, Excel) normally
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        array_shift($rows);
        $totalRows = count($rows);
    }

    $votersAdded = 0;
    $emailsSent = 0;
    $pdo->beginTransaction();

    // Get adviser ID and year level
    $adviserId = $_SESSION['user_id'] ?? null;
    if (!$adviserId) {
        sendStreamData(['status' => 'error', 'message' => 'Adviser not logged in', 'log_file' => $logFileName]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT year FROM advisers WHERE id = ?");
    $stmt->execute([$adviserId]);
    $adviserYearLevel = $stmt->fetchColumn();

    if (!$adviserYearLevel) {
        sendStreamData(['status' => 'error', 'message' => 'Adviser year level not found', 'log_file' => $logFileName]);
        exit;
    }

    // Check email capacity
    $emailCredentials = getAdviserEmailCredentials($pdo, $adviserId);
    $canSendEmails = false;
    foreach ($emailCredentials as $cred) {
        if ($cred['capacity'] < 500) {
            $canSendEmails = true;
            break;
        }
    }

    foreach ($rows as $index => $row) {
        // For PDFs with image comparison, data is already structured
        if ($file['type'] === 'application/pdf') {
            $studentData = $row;
        } else {
            // For spreadsheets
            $studentData = [
                'Student ID' => $row['B'] ?? '',
                'WMSU Email' => $row['C'] ?? '',
                'First Name' => $row['D'] ?? '',
                'Middle Name' => $row['E'] ?? '',
                'Last Name' => $row['F'] ?? '',
                'Year Level' => $row['G'] ?? '',
                'College' => $row['I'] ?? '',
                'Course' => $row['J'] ?? '',
                'Department' => $row['K'] ?? '',
                'WMSU Campus Studying' => $row['L'] ?? 'Main Campus',
                'WMSU ESU Campus' => $row['M'] ?? 'None'
            ];
            
            // Validate names
            $cleanFirst = cleanName($studentData['First Name']);
            $cleanLast = cleanName($studentData['Last Name']);
            if (empty($cleanFirst) || empty($cleanLast)) {
                error_log("Skipping spreadsheet row " . ($index + 2) . ": Empty first or last name after cleaning");
                continue;
            }

            // Validate year level
            if (strtolower(trim($studentData['Year Level'])) !== strtolower(trim($adviserYearLevel))) {
                error_log("Row " . ($index + 2) . " skipped due to year level mismatch. Adviser: $adviserYearLevel, Student: {$studentData['Year Level']}");
                continue;
            }

            // Validate email
            if (!filter_var($studentData['WMSU Email'], FILTER_VALIDATE_EMAIL)) {
                error_log("Row " . ($index + 2) . " skipped due to invalid email: {$studentData['WMSU Email']}");
                continue;
            }

            // Parse and standardize course/department
            $studentData['Course'] = parseValueAfterDash($studentData['Course']);
            $studentData['Department'] = parseValueAfterDash($studentData['Department']);

            // Validate college/department
            if (strtolower(trim($studentData['College'])) !== strtolower(trim($college)) ||
                strtolower(trim($studentData['Department'])) !== strtolower(trim($department))) {
                error_log("Row " . ($index + 2) . " skipped due to college/department mismatch");
                continue;
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
            $studentData['Course'],
            $studentData['Year Level'],
            $studentData['College'],
            $studentData['Department'],
            $studentData['WMSU Campus Studying'],
            $studentData['WMSU ESU Campus'],
            $studentData['First Name'],
            $studentData['Middle Name'],
            $studentData['Last Name'],
            $studentData['Course'],
            $studentData['Year Level'],
            $studentData['College'],
            $studentData['Department'],
            $password,
            $studentData['WMSU Campus Studying'],
            $studentData['WMSU ESU Campus']
        ]);

        if ($canSendEmails && sendEmail($pdo, $adviserId, $studentData['WMSU Email'], $studentData, $passwordPlain, $uploadDir)) {
            $emailsSent++;
        }

        $votersAdded++;
        sendStreamData([
            'status' => 'progress',
            'current' => $votersAdded,
            'total' => $totalRows
        ]);
    }

    // Log import details
    $adviserEmail = $_SESSION['email'] ?? 'unknown@example.com';
    $stmt = $pdo->prepare("
        INSERT INTO adviser_import_details (file, date, status, voters_added, emails_sent, adviser_email, mismatches)
        VALUES (?, NOW(), 'completed', ?, ?, ?, ?)
    ");
    $stmt->execute([$fileName, $votersAdded, $emailsSent, $adviserEmail, json_encode($mismatches)]);

    $pdo->commit();
    sendStreamData([
        'status' => 'complete',
        'message' => "Imported $votersAdded out of $totalRows voters successfully. Sent $emailsSent emails.",
        'mismatches' => $mismatches,
        'log_file' => $logFileName
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // if (isset($filePath) && file_exists($filePath)) {
    //     unlink($filePath);
    // }
    // if (isset($excelFilePath) && file_exists($excelFilePath)) {
    //     unlink($excelFilePath);
    // }
    sendStreamData([
        'status' => 'error',
        'message' => 'Import failed: ' . $e->getMessage(),
        'log_file' => $logFileName
    ]);
    error_log("Import failed: " . $e->getMessage());
}

// // Clean up
// if (isset($filePath) && file_exists($filePath)) {
//     unlink($filePath);
// }
// if (isset($excelFilePath) && file_exists($excelFilePath)) {
//     unlink($excelFilePath);
// }
?>