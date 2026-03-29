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


// Validate Student ID format
if (!preg_match('/^\d{4}-\d{5}$/', $student_id)) {
    $_SESSION['status'] = 'error';
    $_SESSION['message'] = 'Student ID must be in format YYYY-XXXXX (e.g., 2020-12345)';
    header("Location: ../register.php");
    exit();
}

// Validate Email format and match with Student ID
$studentIdNumeric = str_replace('-', '', $student_id);
if (
    !str_ends_with($email, '@wmsu.edu.ph') ||
    !str_ends_with(explode('@', $email)[0], $studentIdNumeric)
) {
    $_SESSION['status'] = 'error';
    $_SESSION['message'] = 'Email must be in format <StudentID>@wmsu.edu.ph (e.g., 202012345@wmsu.edu.ph)';
    header("Location: ../register.php");
    exit();
}

// 🔽 INSERT THIS BLOCK HERE
$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM voters WHERE student_id = ?");
$checkStmt->execute([$student_id]);
$existingCount = $checkStmt->fetchColumn();

if ($existingCount > 0) {
    $_SESSION['STATUS'] = 'error';
    $_SESSION['MESSAGE'] = 'An account with this student ID already exists.';
    header("Location: ../register.php");
    exit();
}

// 🔽 INSERT THIS BLOCK HERE
$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? ");
$checkStmt->execute([$student_id]);
$existingCount = $checkStmt->fetchColumn();

if ($existingCount > 0) {
    $_SESSION['STATUS'] = 'error';
    $_SESSION['MESSAGE'] = 'An account with this email already exists.';
    header("Location: ../index.php");
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

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Insert into voters table with activation fields
    $stmt = $pdo->prepare("
        INSERT INTO voters (
            student_id, email, password, first_name, middle_name, last_name,
            course, major, year_level, college, department, wmsu_campus,
            external_campus, first_cor, second_cor, semester,
            activation_token, activation_expiry, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
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
        $activation_expiry
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
    header("Location: ../register.php");
    exit();
}

// Email sending function for activation


// Replace the uploadFile function with this version
function uploadFile($fieldName, $convertPdfToImage = false)
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    $fileType = $_FILES[$fieldName]['type'];

    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['STATUS'] = 'error';
        $_SESSION['MESSAGE'] = 'Invalid file type for ' . $fieldName;
        header("Location: ../register.php");
        exit();
    }



    $uploadPath = '../uploads/';
    $ext = pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION);
    $newFilename = uniqid() . '.' . $ext;
    $destination = $uploadPath . $newFilename;

    // Handle PDF-to-image conversion for cor_1
    // Handle PDF-to-image conversion for cor_1
    if ($convertPdfToImage && $fileType === 'application/pdf') {
        try {
            $newFilename = uniqid() . '.png';
            $destination = $uploadPath . $newFilename;
            $command = escapeshellcmd("pdftoppm -png -f 1 -l 1 " . escapeshellarg($_FILES[$fieldName]['tmp_name']) . " " . escapeshellarg($uploadPath . uniqid()));
            exec($command, $output, $returnVar);
            if ($returnVar !== 0) {
                throw new Exception('pdftoppm command failed.');
            }
            // pdftoppm appends -1 to the filename for the first page
            $generatedFile = $uploadPath . uniqid() . '.png';

            rename($generatedFile, $destination);
        } catch (Exception $e) {
            // $_SESSION['STATUS'] = 'error';
            // $_SESSION['MESSAGE'] = 'Failed to convert PDF to image for ' . $fieldName . ': ' . $e->getMessage();
            // header("Location: ../register.php");
            // exit();
        }
    }

    return $newFilename;
}
