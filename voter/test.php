<?php
ob_start();
session_start();
require_once 'vendor/autoload.php';

use thiagoalessio\TesseractOCR\TesseractOCR;

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

$response = ['success' => false, 'message' => 'Initial status'];

try {
    // Handle GET request with file path
    $inputFile = isset($_GET['file']) ? urldecode($_GET['file']) : null;

    if (!$inputFile || !file_exists($inputFile)) {
        $_SESSION['STATUS'] = [
            'type' => 'error',
            'message' => 'File not found or missing: ' . ($inputFile ?? 'null')
        ];
        ob_clean();
        header('Location: test.php');
        exit;
    }

    // Secure file path (prevent directory traversal)
    $inputFile = realpath($inputFile);
    $uploadsDir = realpath('Uploads');
    if ($inputFile === false || $uploadsDir === false || strpos($inputFile, $uploadsDir) !== 0) {
        error_log("Path validation failed: inputFile=$inputFile, uploadsDir=$uploadsDir");
        throw new Exception('Invalid file path.');
    }

    // Preprocess image
    function preprocessImage($filePath, $outputPath)
    {
        // Validate image type using exif_imagetype
        if (!function_exists('exif_imagetype')) {
            throw new Exception('exif_imagetype function not available.');
        }
        $imageType = exif_imagetype($filePath);
        if ($imageType === IMAGETYPE_PNG) {
            $image = imagecreatefrompng($filePath);
        } elseif ($imageType === IMAGETYPE_JPEG) {
            $image = imagecreatefromjpeg($filePath);
        } else {
            throw new Exception('Unsupported or invalid image format: ' . ($imageType ? $imageType : 'unknown'));
        }

        // Check if image creation was successful
        if ($image === false) {
            throw new Exception('Failed to create image from file: ' . $filePath);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width > 1000 && $height > 1000) {
            $resized = $image;
        } else {
            $newWidth = $width * 1.5;
            $newHeight = $height * 1.5;
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
        }
        imagefilter($resized, IMG_FILTER_GRAYSCALE);
        imagefilter($resized, IMG_FILTER_CONTRAST, -10);
        imagefilter($resized, IMG_FILTER_GAUSSIAN_BLUR);
        $width = imagesx($resized);
        $height = imagesy($resized);
        $totalBrightness = 0;
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($resized, $x, $y);
                $gray = ($rgb >> 16) & 0xFF;
                $totalBrightness += $gray;
            }
        }
        $averageBrightness = $totalBrightness / ($width * $height);
        $threshold = $averageBrightness * 0.8;
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($resized, $x, $y);
                $gray = ($rgb >> 16) & 0xFF;
                $color = $gray < $threshold ? imagecolorallocate($resized, 0, 0, 0) : imagecolorallocate($resized, 255, 255, 255);
                imagesetpixel($resized, $x, $y, $color);
            }
        }
        imagepng($resized, $outputPath);
        imagedestroy($resized);
    }

    // Convert PDF if needed
    $ext = strtolower(pathinfo($inputFile, PATHINFO_EXTENSION));
    if ($ext === 'pdf') {
        $tempJpg = 'Uploads/' . uniqid() . '.jpg';
        $command = "convert -density 300 {$inputFile}[0] -quality 100 {$tempJpg}";
        exec($command, $output, $returnVar);
        if ($returnVar !== 0) {
            throw new Exception('PDF conversion failed.');
        }
        preprocessImage($tempJpg, $inputFile);
        unlink($tempJpg); // Clean up
    } else {
        preprocessImage($inputFile, $inputFile);
    }

    // Run OCR
    $ocr = new TesseractOCR($inputFile);
    $ocr->executable('C:\Program Files\Tesseract-OCR\tesseract.exe');
    $ocr->psm(6);
    $ocr->lang('eng');
    $ocr->allowlist('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-/: .');
    $text = $ocr->run();

    $lines = explode("\n", $text);
    $studentNumber = '';
    $semSY = '';
    $university = '';
    $certificate = '';
    $program = '';

    foreach ($lines as $index => $line) {
        if (stripos($line, 'WESTERN MINDANAO STATE UNIVERSITY') !== false) {
            $university = 'WESTERN MINDANAO STATE UNIVERSITY';
        }
        if (stripos($line, 'CERTIFICATE OF REGISTRATION') !== false) {
            $certificate = 'CERTIFICATE OF REGISTRATION';
        }
        if (preg_match('/S\s*N/i', $line)) {
            preg_match('/S\s*N\s*[:\s]*.*(\d{4}-\d{5})/i', $line, $matches);
            if (!empty($matches[1])) {
                $studentNumber = trim($matches[1]);
            } else {
                for ($i = 1; $i <= 3; $i++) {
                    $nextLine = $lines[$index + $i] ?? '';
                    preg_match('/.*(\d{4}-\d{5})/', $nextLine, $matches);
                    if (!empty($matches[1])) {
                        $studentNumber = trim($matches[1]);
                        preg_match('/BS\s*[A-Z]+/', $nextLine, $progMatches);
                        if (!empty($progMatches[0])) {
                            $program = trim($progMatches[0]);
                        }
                        break;
                    }
                }
            }
            preg_match('/BS\s*[A-Z]+/', $line, $progMatches);
            if (!empty($progMatches[0])) {
                $program = trim($progMatches[0]);
            }
        }
        if (preg_match('/S\s*\/\s*SY/i', $line) || preg_match('/S\s+\d+(ST|ND|RD|TH)/i', $line)) {
            preg_match('/(?:S\s*\/\s*SY\s*[:\s]*)?(.*?)(1ST|2ND|3RD|SUMMER)\s*(\d{4}-\d{4})/i', $line, $matches);
            if (empty($matches)) {
                preg_match('/S\s+(1ST|2ND|3RD|SUMMER)\s+(\d{4}-\d{4})/i', $line, $matches);
            }
            if (!empty($matches)) {
                $sem = $matches[2] ?? '';
                $year = $matches[3] ?? ($matches[2] ?? '');
                if ($sem && $year) {
                    $semSY = "$sem $year";
                }
            }
            if (!$semSY) {
                for ($i = 1; $i <= 3; $i++) {
                    $nextLine = $lines[$index + $i] ?? '';
                    preg_match('/(1ST|2ND|3RD|SUMMER)\s*(\d{4}-\d{4})/i', $nextLine, $matches);
                    if (!empty($matches)) {
                        $semSY = trim($matches[1]) . " " . trim($matches[2]);
                        break;
                    }
                }
            }
        }
    }

    if ($university && $certificate && $studentNumber && $semSY) {
        include('../../includes/conn.php');
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not logged in.');
        }
        $stmt = $pdo->prepare("UPDATE precinct_voters SET status = 'verified' WHERE student_id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        // Clean up uploaded file
        if (file_exists($inputFile)) {
            unlink($inputFile);
        }

        // Set success status
        $_SESSION['STATUS'] = [
            'type' => 'success',
            'message' => 'COR verified successfully. Student Number: ' . $studentNumber
        ];

        // Check if request is AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if ($isAjax) {
            // Return JSON for AJAX requests
            header('Content-Type: application/json; charset=UTF-8');
            $response = [
                'success' => true,
                'message' => 'COR verified successfully',
                'data' => compact('studentNumber', 'program', 'semSY'),
                'redirect' => 'index.php'
            ];
            ob_clean();
            echo json_encode($response);
        } else {
            // Perform server-side redirect for non-AJAX requests
            ob_clean();
            header('Location: index.php');
        }
        exit;
    } else {
        $response['message'] = 'OCR data missing: ' . (!$university ? 'university, ' : '') .
            (!$certificate ? 'certificate, ' : '') .
            (!$studentNumber ? 'student number, ' : '') .
            (!$semSY ? 'semester/SY' : '');
        throw new Exception($response['message']);
    }
} catch (Exception $e) {
    // Clean up uploaded file in case of error
    if (isset($inputFile) && file_exists($inputFile)) {
        unlink($inputFile);
    }

    // Log error details
    error_log("Error: " . $e->getMessage() . " | Input file: " . ($inputFile ?? 'none'));

    // Set error status
    $_SESSION['STATUS'] = [
        'type' => 'error',
        'message' => $e->getMessage()
    ];

    // Check if request is AJAX
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    if ($isAjax) {
        // Return JSON for AJAX errors
        header('Content-Type: application/json; charset=UTF-8');
        $response['success'] = false;
        $response['message'] = $e->getMessage();
        ob_clean();
        echo json_encode($response);
    } else {
        // Redirect to error page for non-AJAX errors
        ob_clean();
        header('Location: error.php?message=' . urlencode($e->getMessage()));
    }
    exit;
}
