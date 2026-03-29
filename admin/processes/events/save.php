<?php
session_start(); // Start session for storing messages
require_once '../../includes/conn.php'; // Adjust path to match your directory structure

header('Content-Type: application/json'); // Ensure JSON response for AJAX

try {
    // Start a transaction to ensure data consistency
    $pdo->beginTransaction();

    // Step 1: Process Event Details
    $eventTitle = $_POST['event_title'] ?? '';
    $eventDetails = $_POST['event_details'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    $registrationEnabled = isset($_POST['open_for_registration']) && $_POST['open_for_registration'] == '1';
    $candidacy = $_POST['candidacy'] ?? null;
    $registrationStart = $_POST['start_period'] ?? null;
    $registrationDeadline = $_POST['end_period'] ?? null;

    if ($registrationEnabled) {

        if (empty($candidacy)) {
            throw new Exception("Please select a candidacy election before enabling registration as you have enabled candidacy!");
        }

        if (empty($registrationStart) || empty($registrationDeadline)) {
            throw new Exception("Please choose the candidacy start and end dates.");
        }
    }

    $coverImagePath = null;

    // Validate required fields
    if (empty($eventTitle)) {
        throw new Exception("Event title is required.");
    }

    // Step 2: Handle Cover Image Upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../../uploads/event_covers/'; // Adjust path as needed
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true); // Create directory if it doesn't exist
        }

        $fileName = uniqid('cover_') . '_' . basename($_FILES['cover_image']['name']);
        $filePath = $uploadDir . $fileName;

        // Validate file type and size (e.g., allow only images, max 5MB)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        $fileType = mime_content_type($_FILES['cover_image']['tmp_name']);
        $fileSize = $_FILES['cover_image']['size'];

        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Only JPEG, PNG, and GIF images are allowed.");
        }

        // Move the uploaded file
        if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $filePath)) {
            throw new Exception("Failed to upload cover image.");
        }

        $coverImagePath = $fileName; // Store relative path
    }

    // Step 3: Insert Event into Database
    $stmt = $pdo->prepare("
        INSERT INTO events (
            event_title,
            cover_image,
            event_details,
            registration_enabled,
            registration_start,
            registration_deadline,
            status,
            created_at,
            views,
            author,
            candidacy
        )
        VALUES (
            :event_title,
            :cover_image,
            :event_details,
            :registration_enabled,
            :registration_start,
            :registration_deadline,
            :status,
            NOW(),
            0,
            NULL,
            :candidacy
        )
    ");
    $stmt->execute([
        ':event_title' => $eventTitle,
        ':cover_image' => $coverImagePath,
        ':event_details' => $eventDetails,
        ':registration_enabled' => $registrationEnabled ? 1 : 0,
        ':registration_start' => $registrationEnabled ? $registrationStart : null,
        ':registration_deadline' => $registrationEnabled ? $registrationDeadline : null,
        ':status' => $status,
        ':candidacy' => $registrationEnabled ? $candidacy : null,
    ]);
    $eventId = $pdo->lastInsertId(); // Get the ID of the newly inserted event

    // Step 4: Process Registration Form (if applicable)
    if ($registrationEnabled) {
        if (empty($candidacy)) {
            throw new Exception("Candidacy is required when open for registration.");
        }
        if (empty($registrationStart) || empty($registrationDeadline)) {
            throw new Exception("Registration start and end periods are required.");
        }

        // Step 5: Insert Registration Form Metadata
        $formName = 'Filing of Candidacy Form'; // Default form name based on event title
        $stmtForm = $pdo->prepare("
            INSERT INTO registration_forms (form_name, election_name, created_at, status)
            VALUES (:form_name, :election_name, NOW(), :status)
        ");
        $stmtForm->execute([
            ':form_name' => $formName,
            ':election_name' => $candidacy,
            ':status' => 'draft',
        ]);
        $formId = $pdo->lastInsertId();

        // Step 6: Save Form Fields with Template Uploads
        $fieldStmt = $pdo->prepare("
            INSERT INTO form_fields (
                form_id,
                field_name,
                field_type,
                is_required,
                is_default,
                options,
                template_path,
                created_at
            )
            VALUES (
                :form_id,
                :field_name,
                :field_type,
                :is_required,
                :is_default,
                :options,
                :template_path,
                NOW()
            )
        ");

        $templateUploadDir = '../../../uploads/templates/'; // Directory for template uploads
        if (!is_dir($templateUploadDir)) {
            mkdir($templateUploadDir, 0755, true);
        }

        // Allowed file types for templates
        $allowedTemplateTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        $maxTemplateSize = 10 * 1024 * 1024; // Define max template size: 10MB

        // Process Registration Fields
        if (isset($_POST['registration_fields']) && is_array($_POST['registration_fields'])) {
            foreach ($_POST['registration_fields'] as $fieldId => $fieldData) {
                $fieldName = $fieldData['name'] ?? '';
                $fieldType = $fieldData['type'] ?? 'text';
                $isRequired = isset($fieldData['required']) && $fieldData['required'] == '1';
                $isDefault = isset($fieldData['is_default']) ? 1 : 0; // Check if it's a default field
                $options = null;
                $templatePath = null;

                // Handle options for dropdown/radio fields
                if (in_array($fieldType, ['dropdown', 'radio']) && isset($fieldData['options'])) {
                    $options = implode(',', array_map('trim', explode(',', $fieldData['options'])));
                }

                // Handle template upload
                if (
                    isset($_FILES['registration_fields']) &&
                    isset($_FILES['registration_fields']['name'][$fieldId]['template']) &&
                    $_FILES['registration_fields']['error'][$fieldId]['template'] === UPLOAD_ERR_OK
                ) {
                    $templateFile = $_FILES['registration_fields']['tmp_name'][$fieldId]['template'];
                    $templateFileName = $_FILES['registration_fields']['name'][$fieldId]['template'];
                    $templateFileType = mime_content_type($templateFile);
                    $templateFileSize = $_FILES['registration_fields']['size'][$fieldId]['template'];

                    // Validate template file
                    if (!in_array($templateFileType, $allowedTemplateTypes)) {
                        throw new Exception("Invalid template file type for field '$fieldName'. Allowed types: PDF, DOC, DOCX, XLS, XLSX.");
                    }
                    if ($templateFileSize > $maxTemplateSize) {
                        throw new Exception("Template file for '$fieldName' exceeds 10MB limit.");
                    }

                    $templateFileName = uniqid('template_') . '_' . basename($templateFileName);
                    $templatePath = $templateUploadDir . $templateFileName;

                    if (!move_uploaded_file($templateFile, $templatePath)) {
                        throw new Exception("Failed to upload template for field '$fieldName'.");
                    }

                    $templatePath = $templateFileName; // Store relative path
                }

                if (!empty($fieldName)) {
                    $fieldStmt->execute([
                        ':form_id' => $formId,
                        ':field_name' => $fieldName,
                        ':field_type' => $fieldType,
                        ':is_required' => $isRequired ? 1 : 0,
                        ':is_default' => $isDefault,
                        ':options' => $options,
                        ':template_path' => $templatePath,
                    ]);
                }
            }
        }
    }

    // Commit the transaction
    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Event and registration form saved successfully!'
    ]);
    exit;
} catch (Exception $e) {
    // Rollback the transaction on error
    $pdo->rollBack();

    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit;
}
