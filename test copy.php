<?php
require_once 'vendor/autoload.php';

use thiagoalessio\TesseractOCR\TesseractOCR;
use GuzzleHttp\Client;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

function validateOCR($ocrText, $studentData) {
    $requiredFields = ['Name', 'Program', 'Major', 'Student Number', 'College', 'Sem SY/Level'];
    $validationResult = ['isValid' => true, 'missingFields' => [], 'mismatches' => [], 'isStudent' => false];

    // Normalize OCR text to lowercase for case-insensitive matching
    $ocrText = strtolower($ocrText);

    // Check if it's a student COR
    if (strpos($ocrText, 'western mindanao state university') !== false && 
        strpos($ocrText, 'certificate of registration') !== false) {
        $validationResult['isStudent'] = true;
    }

    // Normalize expected values from studentData
    $expected = [
        'Name' => strtolower(trim("{$studentData['Last Name']}, {$studentData['First Name']} {$studentData['Middle Name']}")),
        'Program' => strtolower(trim($studentData['Course'])),
        'Major' => '', // Major not provided in Excel
        'Student Number' => strtolower(trim($studentData['Student ID'])),
        'College' => strtolower(trim($studentData['College'])),
        'Sem SY/Level' => strtolower(trim("{$studentData['Semester']} {$studentData['Year Level']}"))
    ];

    // Normalize Program (map Excel Course to COR format)
    $programMap = [
        'college of computing studies - information technology' => 'bs it',
        'college of computing studies - computer science' => 'bs cs'
        // Add more mappings as needed
    ];
    $expectedProgram = $expected['Program'];
    $expected['Program'] = isset($programMap[$expectedProgram]) ? $programMap[$expectedProgram] : $expectedProgram;

    // Split OCR text into lines
    $lines = array_filter(array_map('trim', explode("\n", $ocrText)));
    $extracted = [];
    $currentSection = '';

    foreach ($lines as $line) {
        $line = trim($line);

        // Identify sections
        if (stripos($line, 'name program major student number') !== false) {
            $currentSection = 'header1';
            continue;
        } elseif (stripos($line, 'college sem/sy level') !== false) {
            $currentSection = 'header2';
            continue;
        }

        // Parse based on section
        if ($currentSection === 'header1') {
            // Expected format: AQUINO, AHMAD PANDAOG BS CS 2020-01524
            $parts = array_filter(preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY));
            if (count($parts) >= 4) {
                // Extract Name (e.g., AQUINO, AHMAD PANDAOG)
                $nameParts = [];
                $i = 0;
                while ($i < count($parts) && !in_array(strtoupper($parts[$i]), ['BS', 'CS', 'IT', 'CR'])) {
                    $nameParts[] = $parts[$i];
                    $i++;
                }
                $extracted['Name'] = strtolower(implode(' ', $nameParts));

                // Extract Program (e.g., BS CS)
                $programParts = [];
                while ($i < count($parts) && !preg_match('/^\d{4}-\d{5}$/', $parts[$i]) && !preg_match('/^ps\d{2}\s[a-z]+$/i', $parts[$i])) {
                    $programParts[] = $parts[$i];
                    $i++;
                }
                $extracted['Program'] = strtolower(implode(' ', $programParts));

                // Extract Major (second part of Program, e.g., CS)
                $extracted['Major'] = isset($programParts[1]) ? strtolower($programParts[1]) : '';

                // Extract Student Number (e.g., 2020-01524 or PS00 CTEIA)
                if ($i < count($parts)) {
                    $studentNumberParts = array_slice($parts, $i);
                    $extracted['Student Number'] = strtolower(implode(' ', $studentNumberParts));
                }
            }
            $currentSection = '';
        } elseif ($currentSection === 'header2') {
            // Expected format: COLLEGE OF COMPUTING STUDIES 2ND 2023-2024 4
            $parts = array_filter(preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY));
            if (count($parts) >= 4) {
                // Extract College (e.g., COLLEGE OF COMPUTING STUDIES)
                $collegeParts = [];
                $i = 0;
                while ($i < count($parts) && !in_array(strtoupper($parts[$i]), ['1ST', '2ND', '3RD', '4TH'])) {
                    $collegeParts[] = $parts[$i];
                    $i++;
                }
                $extracted['College'] = strtolower(implode(' ', $collegeParts));

                // Extract Sem SY/Level (e.g., 2ND 2023-2024 4)
                $semParts = array_slice($parts, $i);
                $extracted['Sem SY/Level'] = strtolower(implode(' ', $semParts));

                // Normalize Sem SY/Level for comparison (e.g., 2ND 4)
                $semPartsFiltered = [$semParts[0], end($semParts)];
                $extracted['Sem SY/Level Normalized'] = strtolower(implode(' ', $semPartsFiltered));
            }
            $currentSection = '';
        }
    }

    // Validate required fields
    foreach ($requiredFields as $field) {
        if (!isset($extracted[$field])) {
            $validationResult['missingFields'][] = $field;
            $validationResult['isValid'] = false;
        } else {
            $compareField = $field === 'Sem SY/Level' ? 'Sem SY/Level Normalized' : $field;
            if ($field !== 'Major' && $extracted[$compareField] !== $expected[$field]) {
                $validationResult['mismatches'][] = [
                    'field' => $field,
                    'expected' => $expected[$field],
                    'found' => $extracted[$compareField]
                ];
                $validationResult['isValid'] = false;
            }
        }
    }

    return $validationResult;
}

// ---- Main Execution ----

$excelFile = 'students.csv'; // Update with your Excel file path
$imageOutputDir = 'pdf_images';
$outputTextFile = 'validation_results.txt';

try {
    $spreadsheet = IOFactory::load($excelFile);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);
    $header = array_shift($rows); // Remove header row

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

                // Clean up
                foreach ($imageFiles as $file) {
                    unlink($file);
                }
            } else {
                $results[] = [
                    'Student ID' => $studentId,
                    'Validation' => ['isValid' => false, 'missingFields' => [], 'mismatches' => [], 'isStudent' => false, 'error' => 'PDF not found'],
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