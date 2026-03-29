<?php
require_once '../../includes/conn.php'; // Database connection
require '../../../vendor/autoload.php'; // Include Composer's autoloader

// Increase limits for large imports
ini_set('max_execution_time', 7200); // 2 hours
ini_set('memory_limit', '1024M'); // 1GB
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\Common\EccLevel;

// First, verify the database structure
try {
    $pdo->query("SELECT 1 FROM voters LIMIT 1");
} catch (PDOException $e) {
    echo htmlspecialchars("Database error: Voters table not found or inaccessible");
    exit;
}

// Check if student_id column exists
$columnExists = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM voters LIKE 'student_id'");
    $columnExists = ($stmt->rowCount() > 0);
} catch (PDOException $e) {
    echo htmlspecialchars("Error checking database structure: " . $e->getMessage());
    exit;
}

if (!$columnExists) {
    echo htmlspecialchars("Error: The student_id column does not exist in the voters table. Please check your database structure.");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo htmlspecialchars("File upload error. Code: " . $_FILES['file']['error'] . ". Please try again.");
        exit;
    }

    $file = $_FILES['file'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_extension, ['csv', 'xls', 'xlsx'])) {
        echo htmlspecialchars("Invalid file type. Please upload a CSV or Excel file.");
        exit;
    }

    // Validate file size (e.g., max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        echo htmlspecialchars("File too large. Maximum size is 10MB.");
        exit;
    }

    // Validate MIME type
    $allowed_mimes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed_mimes)) {
        echo htmlspecialchars("Invalid file format. Please upload a valid CSV or Excel file.");
        exit;
    }

    try {
        $tempDir = '../../excel_file_uploads/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        // Sanitize file name
        $safeFileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($file['name']));
        $tempFilePath = $tempDir . $safeFileName;

        if (!move_uploaded_file($file['tmp_name'], $tempFilePath)) {
            throw new Exception('Failed to move uploaded file.');
        }

        $successfulInserts = 0;
        $skippedRows = 0;
        $batchSize = 200;
        $errorDetails = [];
        $rowCount = 0;
        $successfulVoters = []; // Store successful imports for email sending

        // Get all existing student_ids and emails
        $existingStudents = [];
        $existingEmails = [];
        try {
            $stmt = $pdo->query("SELECT student_id, email FROM voters");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['student_id'])) $existingStudents[$row['student_id']] = true;
                if (!empty($row['email'])) $existingEmails[$row['email']] = true;
            }
        } catch (PDOException $e) {
            throw new Exception("Failed to check existing records: " . htmlspecialchars($e->getMessage()));
        }

        // Field length limits
        $maxLengths = [
            'student_id' => 50,
            'email' => 255,
            'first_name' => 100,
            'middle_name' => 100,
            'last_name' => 100,
            'course' => 100,
            'year_level' => 10,
            'college' => 100,
            'department' => 100
        ];

        if ($file_extension === 'csv') {
            $handle = fopen($tempFilePath, 'r');
            if ($handle === false) {
                throw new Exception('Failed to open CSV file.');
            }

            $totalRows = 0;
            while (($row = fgetcsv($handle)) !== false) $totalRows++;
            $totalRows = max(0, $totalRows - 1); // Subtract header
            rewind($handle);

            $pdo->beginTransaction();
            $batchCount = 0;

            $header = fgetcsv($handle); // Read header for debugging
            if (!$header || !in_array('student_id', array_map('strtolower', $header))) {
                throw new Exception("CSV file must contain a 'student_id' column.");
            }

            while (($row = fgetcsv($handle)) !== false) {
                $rowCount++;
                $batchCount++;

                if (empty(array_filter($row))) {
                    $skippedRows++;
                    $errorDetails[] = "Row $rowCount: Empty row";
                    error_log("Row $rowCount: Empty row, raw data: " . json_encode($row));
                    continue;
                }

                $row = array_pad($row, 10, '');
                list($student_id, $email, $first_name, $middle_name, $last_name, $course, $year_level, $college, $department) = array_slice($row, 0, 9);



                // Enforce length limits
                foreach (['student_id', 'email', 'first_name', 'middle_name', 'last_name', 'course', 'year_level', 'college', 'department'] as $field) {
                    if (strlen($$field) > $maxLengths[$field]) {
                        $skippedRows++;
                        $errorDetails[] = "Row $rowCount: $field exceeds maximum length of {$maxLengths[$field]} characters";
                        error_log("Row $rowCount: $field too long, value: " . $$field);
                        continue 2; // Skip to next row
                    }
                }

                if (empty($student_id)) {
                    $skippedRows++;
                    $errorDetails[] = "Row $rowCount: Missing student ID";
                    error_log("Row $rowCount: Missing student ID, raw data: " . json_encode($row));
                    continue;
                }

                if (isset($existingStudents[$student_id])) {
                    $skippedRows++;
                    $errorDetails[] = "Row $rowCount: Duplicate student ID '$student_id'";
                    continue;
                }

                if (!empty($email)) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errorDetails[] = "Row $rowCount: Invalid email format - record imported without email";
                        $email = '';
                    } elseif (isset($existingEmails[$email])) {
                        $skippedRows++;
                        $errorDetails[] = "Row $rowCount: Duplicate email '$email'";
                        continue;
                    }
                }

                $temp_password = $student_id . "wmsuvoter";
        $hashed_password = hash('sha256', $temp_password);

                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO voters 
                        (student_id, email, password, first_name, middle_name, last_name, course, year_level, college, department)
                        VALUES 
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $result = $stmt->execute([
                        $student_id,
                        $email,
                        $hashed_password,
                        $first_name,
                        $middle_name,
                        $last_name,
                        $course,
                        $year_level,
                        $college,
                        $department
                    ]);

                    if ($result) {
                        $successfulInserts++;
                        $existingStudents[$student_id] = true;
                        if (!empty($email)) {
                            $existingEmails[$email] = true;
                            $successfulVoters[] = [
                                'email' => $email,
                                'student_id' => $student_id,
                                'temp_password' => $temp_password
                            ];
                        }

                        if (!empty($email)) {
                            $stmt_user = $pdo->prepare("
                                INSERT INTO users (email, password, role) 
                                VALUES (?, ?, 'voter')
                                ON DUPLICATE KEY UPDATE password = VALUES(password)
                            ");
                            $stmt_user->execute([$email, $hashed_password]);
                        }
                    }
                } catch (PDOException $e) {
                    $skippedRows++;
                    $errorDetails[] = "Row $rowCount: Database error - " . $e->getMessage();
                    error_log("Row $rowCount: Database error - " . $e->getMessage());
                }

                if ($batchCount % $batchSize === 0) {
                    $pdo->commit();
                    $pdo->beginTransaction();
                }
            }

            $pdo->commit();
            fclose($handle);
        } else {
            $spreadsheet = IOFactory::load($tempFilePath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();
            $totalRows = count($data) - 1;

            // Validate header
            $header = array_map('strtolower', $data[0]);
            if (!in_array('student_id', $header)) {
                throw new Exception("Excel file must contain a 'student_id' column.");
            }

            $pdo->beginTransaction();
            $batchCount = 0;

            foreach ($data as $index => $row) {
                if ($index == 0) continue;

                $rowCount = $index;
                $batchCount++;

                if (empty(array_filter($row))) {
                    $skippedRows++;
                    $errorDetails[] = "Row $rowCount: Empty row";
                    error_log("Row $rowCount: Empty row, raw data: " . json_encode($row));
                    continue;
                }

                $row = array_pad($row, 10, '');
                list($student_id, $email, $first_name, $middle_name, $last_name, $course, $year_level, $college, $department) = array_slice($row, 0, 9);

                // Sanitize and validate inputs
                $student_id = trim(filter_var($student_id, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
                $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
                $first_name = trim(filter_var($first_name, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
                $middle_name = trim(filter_var($middle_name, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
                $last_name = trim(filter_var($last_name, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
                $course = trim(filter_var($course, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
                $year_level = trim(filter_var($year_level, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
                $college = $college;
                $department = trim(filter_var($department, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));

                // Enforce length limits
                foreach (['student_id', 'email', 'first_name', 'middle_name', 'last_name', 'course', 'year_level', 'college', 'department'] as $field) {
                    if (strlen($$field) > $maxLengths[$field]) {
                        $skippedRows++;
                        $errorDetails[] = "Row $rowCount: $field exceeds maximum length of {$maxLengths[$field]} characters";
                        error_log("Row $rowCount: $field too long, value: " . $$field);
                        continue 2; // Skip to next row
                    }
                }

                if (empty($student_id)) {
                    $skippedRows++;
                    $errorDetails[] = "Row $rowCount: Missing student ID";
                    error_log("Row $rowCount: Missing student ID, raw data: " . json_encode($row));
                    continue;
                }

                if (isset($existingStudents[$student_id])) {
                    $skippedRows++;
                    $errorDetails[] = "Row $rowCount: Duplicate student ID '$student_id'";
                    continue;
                }

                if (!empty($email)) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errorDetails[] = "Row $rowCount: Invalid email format - record imported without email";
                        $email = '';
                    } elseif (isset($existingEmails[$email])) {
                        $skippedRows++;
                        $errorDetails[] = "Row $rowCount: Duplicate email '$email'";
                        continue;
                    }
                }

                $temp_password = $student_id . "wmsuvoter";
                  $hashed_password = hash('sha256', $temp_password);

                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO voters 
                        (student_id, email, password, first_name, middle_name, last_name, course, year_level, college, department)
                        VALUES 
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $result = $stmt->execute([
                        $student_id,
                        $email,
                        $hashed_password,
                        $first_name,
                        $middle_name,
                        $last_name,
                        $course,
                        $year_level,
                        $college,
                        $department
                    ]);

                    if ($result) {
                        $successfulInserts++;
                        $existingStudents[$student_id] = true;
                        if (!empty($email)) {
                            $existingEmails[$email] = true;
                            $successfulVoters[] = [
                                'email' => $email,
                                'student_id' => $student_id,
                                'temp_password' => $temp_password
                            ];
                        }

                        if (!empty($email)) {
                            $stmt_user = $pdo->prepare("
                                INSERT INTO users (email, password, role) 
                                VALUES (?, ?, 'voter')
                                ON DUPLICATE KEY UPDATE password = VALUES(password)
                            ");
                            $stmt_user->execute([$email, $hashed_password]);
                        }
                    }
                } catch (PDOException $e) {
                    $skippedRows++;
                    $errorDetails[] = "Row $rowCount: Database error - " . $e->getMessage();
                    error_log("Row $rowCount: Database error - " . $e->getMessage());
                }

                if ($batchCount % $batchSize === 0) {
                    $pdo->commit();
                    $pdo->beginTransaction();
                }
            }

            $pdo->commit();
        }

        // Send emails with QR codes and temporary passwords
        $emailResults = [];
        $qrCodeDir = 'qrcodes/';
        if (!file_exists($qrCodeDir)) {
            mkdir($qrCodeDir, 0777, true);
        }

        foreach ($successfulVoters as $voter) {
            $email = filter_var($voter['email'], FILTER_SANITIZE_EMAIL);
            $student_id = htmlspecialchars($voter['student_id']);
            $temp_password = htmlspecialchars($voter['temp_password']);

            // Generate QR Code
            $qrCodePath = $qrCodeDir . md5($student_id) . ".png";
            $options = new QROptions([
                'outputType' => QROutputInterface::GDIMAGE_PNG,
                'eccLevel' => EccLevel::H,
            ]);
            try {
                (new QRCode($options))->render($student_id, $qrCodePath);
            } catch (Exception $e) {
                $emailResults[] = [
                    "email" => htmlspecialchars($email),
                    "success" => false,
                    "message" => "Failed to generate QR code: " . $e->getMessage()
                ];
                continue;
            }

            // Send Email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'madahniqa@gmail.com';
                $mail->Password = 'kchq meua husx atrb';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('madahniqa@gmail.com', 'WMSU - Student Affairs');
                $mail->addAddress($email);
                $mail->addAttachment($qrCodePath);

                $mail->isHTML(true);
                $mail->Subject = 'Your Voting Credentials and QR Code';
                $mail->Body = "Dear Student,<br><br>"
                    . "Your temporary password for the voting system is: <strong>" . htmlspecialchars($temp_password) . "</strong><br>"
                    . "Your QR Code for voting is attached below.<br><br>"
                    . "Best Regards,<br>Voting Team";

                $mail->send();
                $emailResults[] = [
                    "email" => htmlspecialchars($email),
                    "success" => true,
                    "message" => "Credentials and QR Code sent successfully."
                ];
            } catch (Exception $e) {
                $emailResults[] = [
                    "email" => htmlspecialchars($email),
                    "success" => false,
                    "message" => "Email failed: " . $mail->ErrorInfo
                ];
            }

            // Clean up QR code file
            if (file_exists($qrCodePath)) {
                unlink($qrCodePath);
            }
        }

        // Prepare report
        $report = "Voters imported successfully\n\n";
        $report .= "\n\nTotal rows processed: " . ($successfulInserts + $skippedRows) . "\n";
        $report .= "Successfully imported: $successfulInserts\n";
        $report .= "Skipped: $skippedRows\n";

        if (!empty($errorDetails)) {
            $report .= "\nFirst 5 import errors:\n";
            $report .= implode("\n", array_slice($errorDetails, 0, 5));
            if (count($errorDetails) > 5) {
                $report .= "\n...and " . (count($errorDetails) - 5) . " more import errors";
            }
        }

        // Append email results to report
        $report .= "\n\nEmail Sending Results:\n";
        $successfulEmails = 0;
        $failedEmails = 0;
        foreach ($emailResults as $result) {
            if ($result['success']) {
                $successfulEmails++;
            } else {
                $failedEmails++;
                $report .= "Failed to send to {$result['email']}: {$result['message']}\n";
            }
        }
        $report .= "Emails sent successfully: $successfulEmails\n";
        $report .= "Emails failed: $failedEmails\n";

        // Clean up uploaded file
        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }
        echo $report;
        exit;
    } catch (Exception $e) {
        if (isset($tempFilePath) && file_exists($tempFilePath)) {
            @unlink($tempFilePath);
        }
        echo htmlspecialchars("Error: " . $e->getMessage());
        exit;
    }
} else {
    echo htmlspecialchars("No file uploaded");
    exit;
}
