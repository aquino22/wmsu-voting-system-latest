<?php
require_once 'vendor/autoload.php';

use thiagoalessio\TesseractOCR\TesseractOCR;
use GuzzleHttp\Client;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to download PDF
function downloadPDF($url, $savePath) {
    try {
        $client = new Client(['allow_redirects' => true]);
        $response = $client->get($url);
        $data = $response->getBody()->getContents();
        
        if (strpos($data, '%PDF') !== 0) {
            file_put_contents('raw_response.txt', $data);
            throw new Exception("❌ Not a valid PDF file. Check 'raw_response.txt' for details.");
        }
        
        file_put_contents($savePath, $data);
        echo "✅ PDF downloaded: $savePath\n";
        return true;
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
        return false;
    }
}

// Function to convert PDF to images
function convertPDFToImages($pdfPath, $outputDir, $studentId) {
    if (!file_exists($pdfPath)) {
        die("❌ PDF file does not exist: $pdfPath\n");
    }
    
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    $pdftoppmPath = "C:\poppler-24.08.0\Library\bin\pdftoppm.exe"; // Update this path
    if (!file_exists($pdftoppmPath)) {
        die("❌ pdftoppm executable not found at: $pdftoppmPath\n");
    }

    $outputPrefix = $outputDir . '/' . $studentId . '_COR';
    $command = "\"" . $pdftoppmPath . "\" -png -r 300 " . escapeshellarg($pdfPath) . " " . escapeshellarg($outputPrefix) . " 2>&1";
    echo "🔍 Executing command: $command\n";
    $output = shell_exec($command);
    
    if ($output) {
        echo "🔍 pdftoppm output: $output\n";
    }

    $files = glob("$outputDir/{$studentId}_COR*.png");
    if (empty($files)) {
        die("❌ No images were generated in $outputDir. Check pdftoppm output above or PDF validity.\n");
    }

    echo "✅ PDF converted to images in $outputDir\n";
    return $files;
}

// Function to run OCR on images
function runOCRonImages($imageDir, $studentId) {
    $files = glob("$imageDir/{$studentId}_COR*.png");
    if (empty($files)) {
        die("❌ No images found in $imageDir for student $studentId\n");
    }

    $allText = "";
    foreach ($files as $file) {
        echo "🔍 Running OCR on: $file\n";
        $text = (new TesseractOCR($file))->run();
        $allText .= $text . "\n";
    }

    return $allText;
}

// Function to validate OCR results
function validateOCR($ocrText, $studentData) {
    $requiredFields = ['Name', 'Major', 'Student Number', 'College', 'Sem SY/Level'];
    $validationResult = [
        'isValid' => true,
        'missingFields' => [],
        'mismatches' => [],
        'isStudent' => false,
        'programValid' => null // New: Track BS IT or BS CS validation
    ];

    $ocrText = strtolower($ocrText);

    // Check if it's a student COR
    if (strpos($ocrText, 'western mindanao state university') !== false && 
        strpos($ocrText, 'certificate of registration') !== false) {
        $validationResult['isStudent'] = true;
    }

    // Expected values from spreadsheet
    $expected = [
        'Name' => strtolower(trim("{$studentData['Last Name']}, {$studentData['First Name']} {$studentData['Middle Name']}")),
        'Major' => '', // Major not provided in Excel, assume empty
        'Student Number' => strtolower(trim($studentData['Student ID'])),
        'College' => strtolower(trim($studentData['College'])),
        'Sem SY/Level' => strtolower(trim($studentData['Semester'])),
        'Program' => strtolower(trim($studentData['Course'])) // New: For BS IT/BS CS
    ];

    // Extract data from OCR text
    $lines = array_filter(array_map('trim', explode("\n", $ocrText)));
    $extracted = [];

    foreach ($lines as $index => $line) {
        if (strpos($line, 'name program major student number') !== false && isset($lines[$index + 1])) {
            $values = array_filter(array_map('trim', preg_split('/\s+/', $lines[$index + 1], -1, PREG_SPLIT_NO_EMPTY)));
            if (count($values) >= 4) {
                $extracted['Name'] = strtolower(implode(' ', array_slice($values, 0, count($values) - 3)));
                $extracted['Program'] = $values[count($values) - 3]; // Capture program
                $extracted['Major'] = $values[count($values) - 2];
                $extracted['Student Number'] = $values[count($values) - 1];
            }
        }
        if (strpos($line, 'college sem/sy level') !== false && isset($lines[$index + 1])) {
            $values = array_filter(array_map('trim', preg_split('/\s+/', $lines[$index + 1], -1, PREG_SPLIT_NO_EMPTY)));
            if (count($values) >= 4) {
                $extracted['College'] = strtolower(implode(' ', array_slice($values, 0, count($values) - 3)));
                $extracted['Sem SY/Level'] = $values[count($values) - 3];
                $extracted['Year'] = $values[count($values) - 2];
                $extracted['Level'] = $values[count($values) - 1];
            }
        }
    }

    // Validate required fields
    foreach ($requiredFields as $field) {
        if (!isset($extracted[$field]) || empty($extracted[$field])) {
            $validationResult['missingFields'][] = $field;
            $validationResult['isValid'] = false;
        } else {
            if ($field !== 'Major' && strpos($extracted[$field], $expected[$field]) === false) {
                $validationResult['mismatches'][] = [
                    'field' => $field,
                    'expected' => $expected[$field],
                    'found' => $extracted[$field]
                ];
                $validationResult['isValid'] = false;
            }
        }
    }

    // Validate Name format (Last Name, First Name Middle Name)
    if (isset($extracted['Name'])) {
        $namePattern = '/^[a-z\s]+,\s+[a-z\s]+$/'; // Simplified: LastName, FirstName MiddleName
        if (!preg_match($namePattern, $extracted['Name'])) {
            $validationResult['mismatches'][] = [
                'field' => 'Name Format',
                'expected' => 'Last Name, First Name Middle Name',
                'found' => $extracted['Name']
            ];
            $validationResult['isValid'] = false;
        }
    }

    // Validate BS IT or BS CS
    if ($validationResult['isStudent'] && isset($extracted['Program']) && isset($extracted['College'])) {
        $isBSIT = $expected['Program'] === 'bs information technology' && 
                  strpos($extracted['College'], 'computing studies') !== false && 
                  strpos($extracted['Program'], 'information technology') !== false;
        $isBSCS = $expected['Program'] === 'bs computer science' && 
                  strpos($extracted['College'], 'computing studies') !== false && 
                  strpos($extracted['Program'], 'computer science') !== false;

        if ($isBSIT) {
            $validationResult['programValid'] = 'BS IT';
        } elseif ($isBSCS) {
            $validationResult['programValid'] = 'BS CS';
        } else {
            $validationResult['mismatches'][] = [
                'field' => 'Program',
                'expected' => $expected['Program'] . ' from College of Computing Studies',
                'found' => $extracted['Program'] . ' from ' . $extracted['College']
            ];
            $validationResult['isValid'] = false;
        }
    }

    return $validationResult;
}

// Function to send email using PHPMailer
function sendEmail($toEmail, $studentData) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'madahniqa@gmail.com';
        $mail->Password = 'wdbx whto kbiv kubv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('madahniqa@gmail.com', 'WMSU Voter System');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'WMSU Voter Validation Test';
        $mail->Body = "Hello {$studentData['First Name']},<br><br>This is a test email to confirm your WMSU Certificate of Registration has been validated successfully.<br><br>Student ID: {$studentData['Student ID']}<br>College: {$studentData['College']}<br>Semester: {$studentData['Semester']}<br><br>Best regards,<br>WMSU Voter System Team";
        $mail->AltBody = "Hello {$studentData['First Name']},\n\nThis is a test email to confirm your WMSU Certificate of Registration has been validated successfully.\n\nStudent ID: {$studentData['Student ID']}\nCollege: {$studentData['College']}\nSemester: {$studentData['Semester']}\n\nBest regards,\nWMSU Voter System Team";

        $mail->send();
        echo "✅ Email sent to $toEmail\n";
        return true;
    } catch (Exception $e) {
        echo "❌ Email sending failed: {$mail->ErrorInfo}\n";
        return false;
    }
}

// ---- Main Execution ----
$excelFile = 'new_students.csv';
$imageOutputDir = 'pdf_images';
$outputTextFile = 'validation_results.txt';

try {
    $spreadsheet = IOFactory::load($excelFile);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);
    $header = array_shift($rows);

    $results = [];
    foreach ($rows as $row) {
        $studentData = [
            'Timestamp' => $row['A'],
            'Student ID' => $row['B'],
            'WMSU Email' => $row['C'],
            'First Name' => $row['D'],
            'Middle Name' => $row['E'],
            'Last Name' => $row['F'],
            'Year Level' => $row['G'],
            'Semester' => $row['H'],
            'College' => $row['I'],
            'Course' => $row['J'],
            'Department' => $row['K'],
            'COR' => $row['L']
        ];

        $studentId = $studentData['Student ID'];
        $pdfUrl = $studentData['COR'];
        $pdfPath = "downloaded_{$studentId}.pdf";

        echo "\n🔄 Processing student: $studentId\n";
        if (downloadPDF($pdfUrl, $pdfPath)) {
            $imageFiles = convertPDFToImages($pdfPath, $imageOutputDir, $studentId);
            $ocrText = runOCRonImages($imageOutputDir, $studentId);
            $validation = validateOCR($ocrText, $studentData);

            $results[] = [
                'Student ID' => $studentId,
                'Validation' => $validation,
                'OCR Text' => $ocrText
            ];

            // Send test email if validated as a student
            if ($validation['isStudent']) {
                sendEmail($studentData['WMSU Email'], $studentData);
            }

            // Clean up
            unlink($pdfPath);
            foreach ($imageFiles as $file) {
                unlink($file);
            }
        } else {
            echo "⚠️ Download failed for $studentId. Checking for local PDF...\n";
            if (file_exists($pdfPath)) {
                $imageFiles = convertPDFToImages($pdfPath, $imageOutputDir, $studentId);
                $ocrText = runOCRonImages($imageOutputDir, $studentId);
                $validation = validateOCR($ocrText, $studentData);

                $results[] = [
                    'Student ID' => $studentId,
                    'Validation' => $validation,
                    'OCR Text' => $ocrText
                ];

                // Send test email if validated as a student
                if ($validation['isStudent']) {
                    sendEmail($studentData['WMSU Email'], $studentData);
                }

                // Clean up
                foreach ($imageFiles as $file) {
                    unlink($file);
                }
            } else {
                $results[] = [
                    'Student ID' => $studentId,
                    'Validation' => ['isValid' => false, 'missingFields' => [], 'mismatches' => [], 'isStudent' => false, 'programValid' => null, 'error' => 'PDF not found'],
                    'OCR Text' => ''
                ];
            }
        }
    }

    // Save validation results
    $output = '';
    foreach ($results as $result) {
        $output .= "Student ID: {$result['Student ID']}\n";
        if (isset($result['Validation']['error'])) {
            $output .= "Error: {$result['Validation']['error']}\n";
        } else {
            $output .= "Is Student: " . ($result['Validation']['isStudent'] ? 'Yes' : 'No') . "\n";
            $output .= "Valid: " . ($result['Validation']['isValid'] ? 'Yes' : 'No') . "\n";
            $output .= "Program: " . ($result['Validation']['programValid'] ?? 'Not validated') . "\n";
            if (!empty($result['Validation']['missingFields'])) {
                $output .= "Missing Fields: " . implode(', ', $result['Validation']['missingFields']) . "\n";
            }
            if (!empty($result['Validation']['mismatches'])) {
                $output .= "Mismatches:\n";
                foreach ($result['Validation']['mismatches'] as $mismatch) {
                    $output .= "- {$mismatch['field']}: Expected '{$mismatch['expected']}', Found '{$mismatch['found']}'\n";
                }
            }
            $output .= "OCR Text:\n{$result['OCR Text']}\n";
        }
        $output .= str_repeat('-', 50) . "\n";
    }
    file_put_contents($outputTextFile, $output);
    echo "\n✅ Validation complete. Results saved to '$outputTextFile'\n";

} catch (Exception $e) {
    die("❌ Error reading Excel file: " . $e->getMessage() . "\n");
}
?>