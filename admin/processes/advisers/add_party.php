<?php
session_start();
require_once '../../includes/conn.php'; // Adjust path to your PDO connection

header('Content-Type: application/json');

// Disable error display in production
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in as an admin.']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
        exit;
    }

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
        exit;
    }

    // Sanitize and validate inputs
    $name = trim(filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
    $election = trim(filter_var($_POST['election'] ?? '', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
    $platforms = $_POST['platforms'] ?? '';
    $status = 'Approved'; // Hardcoded to prevent tampering
    $party_image = !empty($_FILES['party_image']['name']) ? $_FILES['party_image']['name'] : null;

    // Field length validation
    $maxLengths = [
        'name' => 100,
        'election' => 100,
        'platforms' => 5000 // Adjust based on database schema
    ];

    if (empty($name) || empty($election) || empty($party_image)) {
        echo json_encode(['status' => 'error', 'message' => 'Party name, election, and image are required.']);
        exit;
    }

    if (strlen($name) > $maxLengths['name']) {
        echo json_encode(['status' => 'error', 'message' => 'Party name is too long (max ' . $maxLengths['name'] . ' characters).']);
        exit;
    }

    if (strlen($election) > $maxLengths['election']) {
        echo json_encode(['status' => 'error', 'message' => 'Election name is too long (max ' . $maxLengths['election'] . ' characters).']);
        exit;
    }

    if (strlen($platforms) > $maxLengths['platforms']) {
        echo json_encode(['status' => 'error', 'message' => 'Platforms content is too long (max ' . $maxLengths['platforms'] . ' characters).']);
        exit;
    }

    // Handle party image upload
    if ($party_image) {
        $target_dir = '../../../uploads/'; // Adjust to secure path, preferably outside web root
        if (!is_dir($target_dir) || !is_writable($target_dir)) {
            echo json_encode(['status' => 'error', 'message' => 'Upload directory is not accessible or writable.']);
            exit;
        }

        // Sanitize and validate file
        $imageFileType = strtolower(pathinfo($party_image, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        // Verify MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['party_image']['tmp_name']);
        finfo_close($finfo);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mime, $allowed_mimes) || !in_array($imageFileType, $allowed_types)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid image format. Only JPG, JPEG, PNG, GIF allowed.']);
            exit;
        }

        // Check file size
        if ($_FILES['party_image']['size'] > $max_file_size) {
            echo json_encode(['status' => 'error', 'message' => 'Image size exceeds 5MB limit.']);
            exit;
        }

        // Check for upload errors
        if ($_FILES['party_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'File upload error: ' . $_FILES['party_image']['error']]);
            exit;
        }

        // Generate a secure unique filename
        $unique_filename = uniqid('party_', true) . '.' . $imageFileType;
        $target_file = $target_dir . $unique_filename;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['party_image']['tmp_name'], $target_file)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload image.']);
            exit;
        }
        $party_image = $unique_filename;
    }

    // Insert into parties table
    $stmt = $pdo->prepare("
        INSERT INTO parties (name, election_name, platforms, party_image, status) 
        VALUES (:name, :election_name, :platforms, :party_image, :status)
    ");
    $stmt->execute([
        ':name' => $name,
        ':election_name' => $election,
        ':platforms' => $platforms,
        ':party_image' => $party_image,
        ':status' => $status
    ]);

    // Invalidate CSRF token after successful submission
    unset($_SESSION['csrf_token']);

    echo json_encode(['status' => 'success', 'message' => 'Party added successfully.']);

} catch (PDOException $e) {
    error_log("Database error in add_party.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'A database error occurred. Please try again later.']);
} catch (Exception $e) {
    error_log("Error in add_party.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . htmlspecialchars($e->getMessage())]);
}
?>