<?php
session_start();
ob_start(); // Start output buffering to catch any unexpected output

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 0); // Suppress errors in production
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');
error_reporting(E_ALL);

// Set content type to JSON
header('Content-Type: application/json; charset=UTF-8');

// Ensure user_id is set
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if (!$user_id) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in.'
    ]);
    ob_end_flush();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cor_image'])) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']; // Only images, since PDFs are converted to JPG client-side
    $fileType = mime_content_type($_FILES['cor_image']['tmp_name']);
    $fileSizeLimit = 5 * 1024 * 1024; // 5 MB

    // Validate file type
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode([
            'success' => false,
            'message' => 'Only JPEG, PNG, and GIF images are allowed.'
        ]);
        ob_end_flush();
        exit;
    }

    // Validate file size
    if ($_FILES['cor_image']['size'] > $fileSizeLimit) {
        echo json_encode([
            'success' => false,
            'message' => 'File size exceeds 5MB limit.'
        ]);
        ob_end_flush();
        exit;
    }

    // Define upload directory
    $uploadDir = '../cor_reader/test/uploads/cor/';
 

    // Validate directory writability
    if (!is_writable($uploadDir)) {
        echo json_encode([
            'success' => false,
            'message' => 'Upload directory is not writable. Check server permissions.'
        ]);
        ob_end_flush();
        exit;
    }

    // Generate unique file name
    $fileExt = strtolower(pathinfo($_FILES['cor_image']['name'], PATHINFO_EXTENSION));
    $tempFileName = 'cor_' . $user_id . '_' . time() . '.' . $fileExt;
    $tempFilePath = $uploadDir . $tempFileName;

    // Move the uploaded file
    if (move_uploaded_file($_FILES['cor_image']['tmp_name'], $tempFilePath)) {
        $relativePath = '../cor_reader_test/uploads/cor/' . $tempFileName;
        $newPath = 'uploads/cor/' . $tempFileName;
        $redirectUrl = "../cor_reader/test/test.php?file=" . urlencode($newPath);

        echo json_encode([
            'success' => true,
            'message' => 'File uploaded successfully. Processing...',
            'redirect' => $redirectUrl
        ]);
        ob_end_flush();
        exit;
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to upload file. Check server permissions.'
        ]);
        ob_end_flush();
        exit;
    }
} else {
    // Handle invalid requests
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
    ob_end_flush();
    exit;
}
?>