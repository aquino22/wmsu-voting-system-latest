<?php
session_start(); // Start the session to use $_SESSION
require_once 'includes/conn.php'; // Include your PDO database connection

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['status'] = ['type' => 'error', 'message' => 'Invalid request.'];
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Sanitize and validate inputs
    $event_id = intval($_POST['event_id']);
    $event_title = trim($_POST['event_title']);
    $event_details = trim($_POST['event_details']);
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'published'; // Default to published

    // Handle cover image upload
    $cover_image = !empty($_FILES['cover_image']['name']) ? $_FILES['cover_image']['name'] : null;
    $existing_cover_image = $_POST['existing_cover_image'] ?? null;

    if ($cover_image) {
        $target_dir = "../Uploads/event_covers/";
        $target_file = $target_dir . basename($cover_image);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        // Validate image type and size (5MB limit)
        if (!in_array($imageFileType, $allowed_types) || $_FILES['cover_image']['size'] > 5000000) {
            $_SESSION['status'] = ['type' => 'error', 'message' => 'Invalid or too large image file.'];
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // Move uploaded file
        if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_file)) {
            $_SESSION['status'] = ['type' => 'error', 'message' => 'Failed to upload image.'];
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    } else {
        $cover_image = $existing_cover_image; // Retain existing image if no new upload
    }

    // Prepare and execute the update query
    $stmt = $pdo->prepare("UPDATE events SET event_title = :event_title, cover_image = :cover_image, event_details = :event_details, status = :status WHERE id = :id");
    $stmt->execute([
        ':event_title' => $event_title,
        ':cover_image' => $cover_image,
        ':event_details' => $event_details,
        ':status' => $status,
        ':id' => $event_id
    ]);

    // Set success message
    $_SESSION['status'] = ['type' => 'success', 'message' => 'Event updated successfully.'];

} catch (PDOException $e) {
    $_SESSION['status'] = ['type' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
} catch (Exception $e) {
    $_SESSION['status'] = ['type' => 'error', 'message' => 'Error: ' . $e->getMessage()];
}

// Redirect to the referring page
$referrer = $_SERVER['HTTP_REFERER'] ?? 'default_page.php'; // Fallback URL if HTTP_REFERER is not set
header('Location: ' . $referrer);
exit;
?>