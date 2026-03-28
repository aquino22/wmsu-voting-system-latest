<?php
session_start(); // Start session for storing messages
require_once '../../includes/conn.php'; // Adjust path to match your directory structure

try {
    // Start a transaction to ensure data consistency
    $pdo->beginTransaction();

    // Step 1: Process Event Details
    $title = $_POST['event_title'] ?? '';
    $details = $_POST['event_details'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    $coverImagePath = null;

    // Validate required fields
    if (empty($title)) {
        throw new Exception("Event title is required.");
    }

    // Step 2: Handle Cover Image Upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/event_covers/'; // Adjust path as needed
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true); // Create directory if it doesn't exist
        }

        $fileName = uniqid('cover_') . '_' . basename($_FILES['cover_image']['name']);
        $filePath = $uploadDir . $fileName;

        // Validate file type and size (e.g., allow only images, max 5MB)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $fileType = mime_content_type($_FILES['cover_image']['tmp_name']);
        $fileSize = $_FILES['cover_image']['size'];

        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Only JPEG, PNG, and GIF images are allowed.");
        }
        if ($fileSize > $maxFileSize) {
            throw new Exception("Image size exceeds 5MB limit.");
        }

        // Move the uploaded file
        if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $filePath)) {
            throw new Exception("Failed to upload cover image.");
        }

        $coverImagePath = 'uploads/event_covers/' . $fileName; // Store relative path
    }

    // Step 3: Insert Event into Database
    $stmt = $pdo->prepare("
        INSERT INTO events (title, cover_image, details, status, created_at)
        VALUES (:title, :cover_image, :details, :status, NOW())
    ");
    $stmt->execute([
        ':title' => $title,
        ':cover_image' => $coverImagePath,
        ':details' => $details,
        ':status' => $status,
    ]);
    $eventId = $pdo->lastInsertId(); // Get the ID of the newly inserted event

    // Step 4: Process Registration Form (if applicable)
    $openForRegistration = isset($_POST['open_for_registration']) && $_POST['open_for_registration'] == '1';
    if ($openForRegistration) {
        $candidacy = $_POST['candidacy'] ?? '';
        $startPeriod = $_POST['start_period'] ?? '';
        $endPeriod = $_POST['end_period'] ?? '';

        // Validate candidacy and periods
        if (empty($candidacy)) {
            throw new Exception("Candidacy is required when open for registration.");
        }
        if (empty($startPeriod) || empty($endPeriod)) {
            throw new Exception("Registration start and end periods are required.");
        }

        // Step 5: Save Form Fields
        $fieldStmt = $pdo->prepare("
            INSERT INTO form_fields (event_id, field_name, field_type, is_required, is_default, options, created_at)
            VALUES (:event_id, :field_name, :field_type, :is_required, :is_default, :options, NOW())
        ");

        // Default Fields
        $defaultFields = [
            'full_name' => ['type' => 'text', 'required' => 1],
            'party' => ['type' => 'select', 'required' => isset($_POST['default_fields']['party_required']) ? 1 : 0],
            'position' => ['type' => 'select', 'required' => isset($_POST['default_fields']['position_required']) ? 1 : 0],
        ];

        foreach ($defaultFields as $fieldName => $fieldData) {
            $fieldStmt->execute([
                ':event_id' => $eventId,
                ':field_name' => $fieldName,
                ':field_type' => $fieldData['type'],
                ':is_required' => $fieldData['required'],
                ':is_default' => 1,
                ':options' => ($fieldName === 'party' || $fieldName === 'position') ? 'dynamic' : null,
            ]);
        }

        // Custom Fields
        if (isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
            foreach ($_POST['custom_fields'] as $index => $customField) {
                $label = $customField['label'] ?? '';
                $type = $customField['type'] ?? 'text';
                $isRequired = isset($customField['required']) && $customField['required'] == '1';
                $options = isset($customField['options']) ? implode(',', array_map('trim', explode(',', $customField['options']))) : null;

                if (!empty($label)) {
                    $fieldStmt->execute([
                        ':event_id' => $eventId,
                        ':field_name' => $label,
                        ':field_type' => $type,
                        ':is_required' => $isRequired,
                        ':is_default' => 0,
                        ':options' => in_array($type, ['select', 'radio']) ? $options : null,
                    ]);
                }
            }
        }
    }

    // Commit the transaction
    $pdo->commit();

    // Set success message and redirect
    $_SESSION['message'] = "Event and registration form saved successfully!";
    $_SESSION['message_type'] = "success";
    header("Location: ../../events.php"); // Adjust redirect URL as needed
    exit;

} catch (Exception $e) {
    // Rollback the transaction on error
    $pdo->rollBack();

    // Set error message and redirect
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    header("Location: ../../events.php"); // Adjust redirect URL as needed
    exit;
}
?>