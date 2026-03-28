<?php
require_once 'includes/conn.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $form_id = (int)$_POST['form_id'];
    $pdo->beginTransaction();

    // Insert candidate
    $stmt = $pdo->prepare("INSERT INTO candidates (form_id, created_at) VALUES (?, NOW())");
    $stmt->execute([$form_id]);
    $candidate_id = $pdo->lastInsertId();

    // Handle all submitted fields
    if (isset($_POST['fields'])) {
        $field_stmt = $pdo->prepare("INSERT INTO candidate_responses (
            candidate_id, field_id, value, created_at
        ) VALUES (?, ?, ?, NOW())");

        // Fetch field definitions to check types
        $fields_stmt = $pdo->prepare("SELECT id, field_name, field_type FROM form_fields WHERE form_id = ?");
        $fields_stmt->execute([$form_id]);
        $fields = $fields_stmt->fetchAll(PDO::FETCH_ASSOC);
        $field_types = array_column($fields, 'field_type', 'id');

        foreach ($_POST['fields'] as $field_id => $value) {
            // Skip empty values except for files (handled separately)
            if (empty($value) && (!isset($field_types[$field_id]) || $field_types[$field_id] !== 'file')) {
                continue;
            }

            // Handle non-file fields
            if (!isset($field_types[$field_id]) || $field_types[$field_id] !== 'file') {
                $field_stmt->execute([$candidate_id, $field_id, $value]);
            }
        }

        // Handle file uploads separately
        if (!empty($_FILES['fields']['name'])) {
            $file_stmt = $pdo->prepare("INSERT INTO candidate_files (
                candidate_id, field_id, file_path, created_at
            ) VALUES (?, ?, ?, NOW())");

            foreach ($_FILES['fields']['name'] as $field_id => $file_name) {
                if (!empty($file_name) && isset($field_types[$field_id]) && $field_types[$field_id] === 'file') {
                    $file_tmp = $_FILES['fields']['tmp_name'][$field_id];
                    $file_error = $_FILES['fields']['error'][$field_id];

                    if ($file_error === UPLOAD_ERR_OK) {
                        $upload_dir = '../login/uploads/candidates/' . $candidate_id . '/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        $file_path = $upload_dir . basename($file_name);
                        if (move_uploaded_file($file_tmp, $file_path)) {
                            $file_stmt->execute([$candidate_id, $field_id, $file_path]);
                        } else {
                            throw new Exception("Failed to upload file: " . $file_name);
                        }
                    } elseif ($file_error !== UPLOAD_ERR_NO_FILE) {
                        throw new Exception("File upload error for field $field_id: " . $file_error);
                    }
                }
            }
        }
    }

    $pdo->commit();

    // --- Send Email Notification ---
    try {
        $student_id_value = null;
        // Find the student_id value from the submitted fields
        foreach ($fields as $field) {
            if ($field['field_name'] === 'student_id' && isset($_POST['fields'][$field['id']])) {
                $student_id_value = $_POST['fields'][$field['id']];
                break;
            }
        }

        if ($student_id_value) {
            // Fetch voter details (email and name)
            $stmt = $pdo->prepare("SELECT email, first_name, last_name FROM voters WHERE student_id = ?");
            $stmt->execute([$student_id_value]);
            $voter = $stmt->fetch(PDO::FETCH_ASSOC);

            $mail->addEmbeddedImage(__DIR__ . '/images/logo-left.png', 'logo_left');
            $mail->addEmbeddedImage(__DIR__ . '/images/logo-right.png', 'logo_right');
            $mail->addEmbeddedImage(__DIR__ . '/images/banner.png', 'banner_bottom');

            if ($voter && !empty($voter['email'])) {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'antonetqt3.14@gmail.com';
                $mail->Password = 'rbwe uhtl bwlt trey';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->setFrom('antonetqt3.14@gmail.com', 'WMSU - Student Affairs');
                $mail->addAddress($voter['email']);
                $mail->isHTML(true);
                $mail->Subject = 'Candidate Nomination Notification';
                $mail->Body = "Dear " . htmlspecialchars($voter['first_name'] . ' ' . $voter['last_name']) . ",<br><br>" .
                    "You have been nominated as a candidate in the upcoming election.<br>" .
                    "Please log in to the system to view more details.<br><br>" .
                    "Best Regards,<br>WMSU Voting System";
                $mail->send();
            }
        }
    } catch (Exception $e) {
        // Log error but continue execution so the nomination isn't rolled back
        error_log("Email notification failed: " . $e->getMessage());
    }
    // -------------------------------

    header("Location: display_form.php?form_id=" . $form_id . "&success=1");
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error: " . $e->getMessage());
}

// Note: The table creation SQL remains the same:
// CREATE TABLE candidates (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     form_id INT,
//     created_at DATETIME,
//     FOREIGN KEY (form_id) REFERENCES registration_forms(id)
// );
//
// CREATE TABLE candidate_responses (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     candidate_id INT,
//     field_id INT,
//     value TEXT,
//     created_at DATETIME,
//     FOREIGN KEY (candidate_id) REFERENCES candidates(id),
//     FOREIGN KEY (field_id) REFERENCES form_fields(id)
// );
