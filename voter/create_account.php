<?php
session_start();
require_once '../includes/conn.php'; // Your database connection file
require '../../vendor/autoload.php'; // Path to PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

// Get form data
$student_id = trim($_POST['student_id']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$first_name = trim($_POST['first_name']);
$middle_name = trim($_POST['middle_name']);
$last_name = trim($_POST['last_name']);
$course = trim($_POST['course']);
$major = isset($_POST['major']) ? trim($_POST['major']) : null;
$year_level = trim($_POST['year_level']);
$college = trim($_POST['college']);
$department = trim($_POST['department']);
$wmsu_campus = trim($_POST['wmsu_campus']);
$external_campus = isset($_POST['external_campus']) ? trim($_POST['external_campus']) : null;
$semester = trim($_POST['semester']);

$stmt = $pdo->prepare("SELECT election_name, academic_year_id FROM elections WHERE status = 'Ongoing' LIMIT 1");

// Execute the query
$stmt->execute();

// Fetch result
$ongoingElection = $stmt->fetch(PDO::FETCH_ASSOC);

if ($ongoingElection) {
    $electionName = $ongoingElection['election_name'];
    $academicYearId = $ongoingElection['academic_year_id'];
} else {
    $electionName = null; // no ongoing election
    $academicYearId = null;
}


// Validate Student ID format
if (!preg_match('/^\d{4}-\d{5}$/', $student_id)) {
    $_SESSION['STATUS'] = 'error';
    $_SESSION['MESSAGE'] = 'Student ID must be in format YYYY-XXXXX (e.g., 2020-12345)';
    header("Location: ../register.php");
    exit();
}

// Validate Email format and match with Student ID
$studentIdNumeric = str_replace('-', '', $student_id);
if (

    !str_ends_with($email, '@wmsu.edu.ph') ||
    !str_ends_with(explode('@', $email)[0], $studentIdNumeric)
) {

    $_SESSION['STATUS'] = 'error';
    $_SESSION['MESSAGE'] = 'Email must be in format <StudentID>@wmsu.edu.ph (e.g., 202012345@wmsu.edu.ph)';
    header("Location: ../register.php");
    exit();
}


// Generate activation token
$activation_token = bin2hex(random_bytes(50));
$activation_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));


// File upload handling
$first_cor = uploadFile('cor_1', true);
$second_cor = uploadFile('cor_2');


// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo $student_id;

// Check if student exists and get their status
$checkStmt = $pdo->prepare("SELECT status FROM voters WHERE student_id = ?");
$checkStmt->execute([$student_id]);
$existingRecord = $checkStmt->fetch();

if ($existingRecord) {


    // If account status is 'confirmed', prevent updates
    if ($existingRecord['status'] === 'confirmed') {

        $_SESSION['STATUS'] = 'error';
        $_SESSION['MESSAGE'] = 'Your account is already confirmed and cannot be updated. Please contact support for changes.';
        header("Location: ../register.php");
        exit();
    }

    // Proceed with update since account is not confirmed
    try {
        $pdo->beginTransaction();

        // Update voters table - only if status is not 'confirmed'
        $updateVoter = $pdo->prepare("
    UPDATE voters SET 
        email = ?, 
        password = ?, 
        first_name = ?, 
        middle_name = ?, 
        last_name = ?,
        course = ?, 
        major = ?, 
        year_level = ?, 
        college = ?, 
        department = ?, 
        wmsu_campus = ?,
        external_campus = ?, 
        first_cor = COALESCE(?, first_cor), 
        second_cor = COALESCE(?, second_cor), 
        semester = ?,
        activation_token = ?, 
        activation_expiry = ?,
        status = 'pending',  
        academic_year_id = ?
    WHERE student_id = ? AND status != 'confirmed'
");

        $updateVoter->execute([
            $email,
            $hashed_password,
            $first_name,
            $middle_name,
            $last_name,
            $course,
            $major,
            $year_level,
            $college,
            $department,
            $wmsu_campus,
            $external_campus,
            $first_cor,
            $second_cor,
            $semester,
            $activation_token,
            $activation_expiry,
            $academicYearId,   // ✅ This comes before student_id
            $student_id     // ✅ This goes last
        ]);

        // Check if any rows were actually updated
        if ($updateVoter->rowCount() === 0) {
            throw new PDOException('No records updated - account may have been confirmed');
        }

        // Update users table
        $updateUser = $pdo->prepare("
            UPDATE users SET 
                email = ?, 
                password = ?, 
                is_active = 0
            WHERE (email = ? OR email = ?)
        ");

        $updateUser->execute([$email, $hashed_password, $email, $student_id]);

        $pdo->commit();

        $_SESSION['STATUS'] = 'success';
        $_SESSION['MESSAGE'] = 'Your account information has been updated. Please wait for verification.';
        header("Location: ../index.php");

        exit();
    } catch (PDOException $e) {
        echo $e->getMessage();
        $pdo->rollBack();
        $_SESSION['STATUS'] = 'error';
        $_SESSION['MESSAGE'] = 'Update failed: ' . $e->getMessage();
        echo $e->getMessage();
        header("Location: ../register.php");
        exit();
    }
}

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Insert into voters table with activation fields
    $stmt = $pdo->prepare("
        INSERT INTO voters (
            student_id, email, password, first_name, middle_name, last_name,
            course, major, year_level, college, department, wmsu_campus,
            external_campus, first_cor, second_cor, semester,
            activation_token, activation_expiry, academic_year_id, is_active, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'pending')
    ");

    $stmt->execute([
        $student_id,
        $email,
        $hashed_password,
        $first_name,
        $middle_name,
        $last_name,
        $course,
        $major,
        $year_level,
        $college,
        $department,
        $wmsu_campus,
        $external_campus,
        $first_cor,
        $second_cor,
        $semester,
        $activation_token,
        $activation_expiry,
        $academicYearId
    ]);

    // Insert into users table (set as inactive)
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password, role, is_active, created_at)
        VALUES (?, ?, 'voter', 0, NOW())
    ");

    $stmt->execute([$email, $hashed_password]);

    // Commit transaction
    $pdo->commit();

    $_SESSION['STATUS'] = 'success';
    $_SESSION['MESSAGE'] = 'Registration successful! Please wait for your adviser to verify your account.';
    header("Location: ../index.php");
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['STATUS'] = 'error';
    $_SESSION['MESSAGE'] = 'Registration failed: ' . $e->getMessage();
    echo $e->getMessage();
    header("Location: ../register.php");
    exit();
}

// Email sending function for activation


// Replace the uploadFile function with this version
/**
 * File upload function with optional PDF-to-image conversion
 * @param string $fieldName The name of the file input field
 * @param bool $convertPdfToImage Whether to convert PDF to image (for cor_1)
 * @return string|null The filename of the uploaded or converted file, or null if no file
 */
function uploadFile($fieldName, $convertPdfToImage = false)
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    $fileType = $_FILES[$fieldName]['type'];

    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['STATUS'] = 'error';
        $_SESSION['MESSAGE'] = 'Invalid file type for ' . $fieldName . '. Allowed types: JPEG, PNG, PDF.';
        header("Location: ../register.php");
        exit();
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($_FILES[$fieldName]['size'] > $maxSize) {
        $_SESSION['STATUS'] = 'error';
        $_SESSION['MESSAGE'] = 'File too large for ' . $fieldName . ' (max 5MB).';
        header("Location: ../register.php");
        echo "new error3";
        exit();
    }

    $uploadPath = rtrim('../uploads/', '/') . '/';
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0775, true);
    }
    if (!is_writable($uploadPath)) {
        $_SESSION['STATUS'] = 'error';
        $_SESSION['MESSAGE'] = 'Upload directory is not writable: ' . $uploadPath;
        header("Location: ../register.php");
        echo "new error2";
        exit();
    }

    // Handle PDF-to-image conversion for cor_1
    if ($convertPdfToImage && $fileType === 'application/pdf') {
        try {
            // Use a consistent prefix for pdftoppm output
            $prefix = uniqid();
            $newFilename = $prefix . '.png';
            $destination = $uploadPath . $newFilename;
            $outputPrefix = $uploadPath . $prefix;
            $command = escapeshellcmd("pdftoppm -png -f 1 -l 1 " . escapeshellarg($_FILES[$fieldName]['tmp_name']) . " " . escapeshellarg($outputPrefix));
            error_log("Executing pdftoppm command: $command");
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                error_log("pdftoppm command failed: " . implode(', ', $output));
                throw new Exception('pdftoppm command failed: ' . implode(', ', $output));
            }

            // pdftoppm appends -1 to the filename for the first page
            $generatedFile = $outputPrefix . '-1' . '.png';
            if (!file_exists($generatedFile)) {
                error_log("Converted image not found at: $generatedFile");
                throw new Exception('Converted image not found at: ' . $generatedFile);
            }

            // Rename to desired filename
            if (!rename($generatedFile, $destination)) {
                error_log("Failed to rename $generatedFile to $destination");
                throw new Exception('Failed to rename converted image to: ' . $destination);
            }
        } catch (Exception $e) {
            $_SESSION['STATUS'] = 'error';
            $_SESSION['MESSAGE'] = 'Failed to convert PDF to image for ' . $fieldName . ': ' . $e->getMessage();
            header("Location: ../register.php");
            echo "new error1";
            exit();
        }
    } else {
        // Handle non-PDF files (JPEG, PNG, or cor_2)
        $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
        $newFilename = uniqid() . '.' . $ext;
        $destination = $uploadPath . $newFilename;

        if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destination)) {
            error_log("Failed to move uploaded file to: $destination");
            $_SESSION['STATUS'] = 'error';
            $_SESSION['MESSAGE'] = 'Failed to upload ' . $fieldName;
            echo "new error";
            header("Location: ../register.php");
            exit();
        }
    }

    return $newFilename;
}
