<?php
require '../../includes/conn.php'; // Include database connection
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['image'])) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '-' . basename($_FILES['image']['name']);
        $uploadFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            echo json_encode(['success' => true, 'filepath' => $uploadFile]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to upload.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No image uploaded.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
}
?>
