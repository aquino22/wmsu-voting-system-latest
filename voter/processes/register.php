<?php
date_default_timezone_set('Asia/Manila');
session_start();
require_once '../includes/conn.php';

try {
    // Validate form_id and event_id from GET
    $form_id = isset($_GET['form_id']) ? (int)$_GET['form_id'] : 0;
    $event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
    $latitude = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
    $longitude = isset($_GET['lon']) ? floatval($_GET['lon']) : null;
    $user_id = $_SESSION['user_id'] ?? null;

    // Validation
    if ($form_id <= 0 || $event_id <= 0) {
        throw new Exception("Invalid form or event ID: form_id=$form_id, event_id=$event_id");
    }
    if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
        throw new Exception("Invalid latitude value: $latitude");
    }
    if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
        throw new Exception("Invalid longitude value: $longitude");
    }

    // Fetch form details
    $form_stmt = $pdo->prepare("SELECT * FROM registration_forms WHERE id = ?");
    $form_stmt->execute([$form_id]);
    $form = $form_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$form) {
        throw new Exception("Form not found for form_id: $form_id");
    }

    // Fetch event details
    $event_stmt = $pdo->prepare("SELECT registration_deadline FROM events WHERE id = ?");
    $event_stmt->execute([$event_id]);
    $event = $event_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$event) {
        throw new Exception("Event not found for event_id: $event_id");
    }

    $current_date = new DateTime();
    $registration_deadline = new DateTime($event['registration_deadline']);
    if ($current_date > $registration_deadline) {
        throw new Exception("Registration period has ended for event_id: $event_id");
    }

    // Fetch form fields
    $field_types = [];
    $field_requirements = [];
    $field_names = [];
    $fields_stmt = $pdo->prepare("SELECT id, field_type, field_name, is_required FROM form_fields WHERE form_id = ?");
    $fields_stmt->execute([$form_id]);
    $all_fields = $fields_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_fields as $field) {
        $field_types[$field['id']] = $field['field_type'];
        $field_requirements[$field['id']] = $field['is_required'];
        $field_names[$field['id']] = $field['field_name'];
    }

    // Process non-file fields
    $fields = $_POST['fields'] ?? [];
    $student_id = null;
    $party = null;
    $position = null;
    foreach ($fields as $field_id => $value) {
        $field_stmt = $pdo->prepare("SELECT * FROM form_fields WHERE id = ? AND form_id = ?");
        $field_stmt->execute([$field_id, $form_id]);
        $field = $field_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$field) {
            throw new Exception("Invalid field ID: $field_id for form_id: $form_id");
        }

        if ($field['field_name'] === 'student_id' && !empty($value)) {
            $student_id = $value;
        } elseif ($field['field_name'] === 'party' && !empty($value)) {
            $party = $value;
        } elseif ($field['field_name'] === 'position' && !empty($value)) {
            $position = $value;
        }
    }

    // Check for duplicate student_id
    if ($student_id !== null) {
        $duplicate_stmt = $pdo->prepare("
            SELECT cr.candidate_id
            FROM candidate_responses cr
            JOIN candidates c ON cr.candidate_id = c.id
            JOIN form_fields ff ON cr.field_id = ff.id
            WHERE c.form_id = ?
            AND ff.field_name = 'student_id'
            AND cr.value = ?
            AND c.created_at <= NOW()
        ");
        $duplicate_stmt->execute([$form_id, $student_id]);
        if ($duplicate_stmt->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['STATUS'] = "ERROR_CANDIDACY_DUPLICATION";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    if ($party !== null && $position !== null) {
        // Only check for duplicates if the party is not 'Independent'
        if (strtolower($party) !== 'independent') {
            $party_pos_stmt = $pdo->prepare("
                SELECT cr.candidate_id
                FROM candidate_responses cr
                JOIN candidates c ON cr.candidate_id = c.id
                JOIN form_fields ff ON cr.field_id = ff.id
                WHERE c.form_id = ?
                AND ff.field_name IN ('party', 'position')
                AND cr.value IN (?, ?)
                GROUP BY cr.candidate_id
                HAVING COUNT(DISTINCT ff.field_name) = 2
            ");
            $party_pos_stmt->execute([$form_id, $party, $position]);
            if ($party_pos_stmt->fetch(PDO::FETCH_ASSOC)) {
                $_SESSION['STATUS'] = "ERROR_PARTY_POSITION_DUPLICATION";
                $_SESSION['ERROR_MESSAGE'] = "Someone has already registered for the party '$party' and position '$position'.";
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            }
        }
    } elseif ($party === null || $position === null) {
        throw new Exception("Both 'party' and 'position' fields are required.");
    }

    // Gather activity info
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $location = ($latitude && $longitude) ? json_encode(['lat' => $latitude, 'lon' => $longitude]) : null;
    $behavior_patterns = json_encode([
        'form_submission_time' => $current_date->format('Y-m-d H:i:s'),
        'field_count' => count($fields),
        'has_files' => !empty($_FILES['fields']['name'])
    ]);

    // Start transaction
    $pdo->beginTransaction();

    // Insert candidate record
    $candidate_stmt = $pdo->prepare("INSERT INTO candidates (form_id, created_at) VALUES (?, NOW())");
    $candidate_stmt->execute([$form_id]);
    $candidate_id = $pdo->lastInsertId();

    // Insert non-file fields
    foreach ($fields as $field_id => $value) {
        if ($field_types[$field_id] === 'file') {
            continue;
        }
        $field_value_stmt = $pdo->prepare("
            INSERT INTO candidate_responses (candidate_id, field_id, value, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $field_value_stmt->execute([$candidate_id, $field_id, is_array($value) ? implode(',', $value) : $value]);
    }

    // Handle file uploads
    $files = $_FILES['fields'] ?? [];
    if (!empty($files['name'])) {
        $file_stmt = $pdo->prepare("
            INSERT INTO candidate_files (candidate_id, field_id, file_path, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $upload_dir = '../../login/uploads/candidates/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception("Failed to create upload directory: $upload_dir");
            }
        }



        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'xlsx', 'rtf'];
        $max_file_size = 10 * 1024 * 1024; // 10MB

        foreach ($files['name'] as $field_id => $file_name) {
            if (!empty($file_name) && isset($field_types[$field_id]) && $field_types[$field_id] === 'file') {
                $file_tmp = $files['tmp_name'][$field_id];
                $file_error = $files['error'][$field_id];
                $file_size = $files['size'][$field_id];

                if ($file_error === UPLOAD_ERR_OK) {
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    if (!in_array($file_ext, $allowed_types)) {
                        throw new Exception("Invalid file type for '$file_name'. Allowed: " . implode(', ', $allowed_types));
                    }
                    if ($file_size > $max_file_size) {
                        throw new Exception("File '$file_name' exceeds size limit of " . ($max_file_size / 1024 / 1024) . "MB");
                    }

                    $unique_name = uniqid('candidate_', true) . '.' . $file_ext;
                    $file_path = $upload_dir . $unique_name;

                    if (move_uploaded_file($file_tmp, $file_path)) {
                        $file_stmt->execute([$candidate_id, $field_id, $unique_name]);
                    } else {
                        error_log("Failed to move file: $file_name to $file_path");
                        throw new Exception("Failed to upload file: $file_name");
                    }
                } elseif ($file_error === UPLOAD_ERR_NO_FILE && $field_requirements[$field_id]) {
                    throw new Exception("Required file not uploaded for field: " . $field_names[$field_id]);
                } elseif ($file_error !== UPLOAD_ERR_NO_FILE) {
                    throw new Exception("File upload error for field " . $field_names[$field_id] . ": $file_error");
                }
            } elseif ($field_requirements[$field_id] && isset($field_types[$field_id]) && $field_types[$field_id] === 'file') {
                throw new Exception("Required file not uploaded for field: " . $field_names[$field_id]);
            }
        }
    }

    // // Insert user activity
    // $activity_stmt = $pdo->prepare("
    //     INSERT INTO user_activities (
    //         user_id, action, timestamp, device_info, ip_address, location, behavior_patterns
    //     ) VALUES (?, ?, NOW(), ?, ?, ?, ?)
    // ");
    // $action = 'Submitted candidacy application';
    // $activity_stmt->execute([$user_id, $action, $device_info, $ip_address, $location, $behavior_patterns]);

    // Commit transaction
    $pdo->commit();

    $_SESSION['STATUS'] = 'SUCCESS_CANDIDACY';
    if ($student_id !== null) {
        $_SESSION['ALERT'] = 'STUDENT_ID_PRESENT';
    }
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} catch (Exception $e) {
    echo $e->getMessage();
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Candidacy submission error: " . $e->getMessage());
    $_SESSION['STATUS'] = 'ERROR_CANDIDACY';
    $_SESSION['ERROR_MESSAGE'] = $e->getMessage();
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} finally {
}
