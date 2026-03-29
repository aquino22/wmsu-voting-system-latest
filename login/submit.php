<?php
date_default_timezone_set('Asia/Manila');
// Database connection
require_once '../includes/conn.php'; // Ensure this contains the PDO connection setup

session_start();
$user_id = $_SESSION['user_id']; // Get logged-in user ID

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = htmlspecialchars($_POST['event_id']);

    // Initialize an array to store form data
    $form_data = [];

    // Loop through all form fields (excluding event_id)
    foreach ($_POST as $key => $value) {
        if ($key !== 'event_id') {
            $clean_key = str_replace('_', ' ', $key); // Convert underscores to spaces
            $form_data[$clean_key] = htmlspecialchars($value);
        }
    }

    // Generate a unique candidate ID
    $candidate_id = uniqid('candidate_', true);
    $form_data['candidate_id'] = $candidate_id;

    // Add status as "pending"
    $form_data['status'] = 'pending';

    // Handle file uploads
    $uploads = [];
    foreach ($_FILES as $key => $file) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $formatted_key = str_replace('_', ' ', $key);
            $upload_file = '../uploads/' . basename($file['name']);

            if (move_uploaded_file($file['tmp_name'], $upload_file)) {
                $uploads[$formatted_key] = $upload_file;
            } else {
                $_SESSION['STATUS'] = 'error';
                $_SESSION['MESSAGE'] = "Error uploading file: " . $file['name'];
                header("Location: your_redirect_page.php");
                exit;
            }
        }
    }

    // Combine form data and uploads
    $registration_data = [
        'form_data' => $form_data,
        'uploads' => $uploads
    ];

    // Convert to JSON
    $registration_data_json = json_encode($registration_data);

    try {
        // Prepare insert statement
        $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, registration_data, created_at) 
                               VALUES (:event_id, :registration_data, NOW())");

        $stmt->bindParam(':event_id', $event_id, PDO::PARAM_STR);
        $stmt->bindParam(':registration_data', $registration_data_json, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $_SESSION['STATUS'] = 'SUCCESS_CANDIDACY';
        } else {
            $_SESSION['STATUS'] = 'ERROR_CANDIDACY';
        }
    } catch (PDOException $e) {
        $_SESSION['STATUS'] = 'ERROR_CANDIDACY';
    }

    // Redirect back to the previous page
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>
