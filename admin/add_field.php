<?php
session_start();
include('includes/conn.php');


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $academic_year_id = $_POST['academic_year_id'] ?? null;
    $fields = $_POST['fields'] ?? [];
    $numColumns = isset($_POST['numColumns']) ? (int)$_POST['numColumns'] : null;
    $fieldFiles = $_FILES['fields'] ?? [];

    if (!$academic_year_id || empty($fields)) {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'Academic year or fields are missing.';
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Get current max order to append new fields
        $stmtOrder = $pdo->prepare("SELECT MAX(field_order) FROM voter_custom_fields WHERE academic_year_id = ?");
        $stmtOrder->execute([$academic_year_id]);
        $currentOrder = (int)$stmtOrder->fetchColumn();

        $insert = $pdo->prepare("
            INSERT INTO voter_custom_fields
            (academic_year_id, field_label, field_type, is_required, options, field_description, field_order, field_sample)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $uploadDir =  'uploads/field_samples/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        echo "checkpoint 1";
        foreach ($fields as $index => $f) {
            $currentOrder++;
            $sampleFileName = null;

            // Handle uploaded sample file(s) for this field
            if (isset($fieldFiles['name'][$index])) {

                // Support multiple files per field, take the first as sample
                $names = $fieldFiles['name'][$index];
                $tmpNames = $fieldFiles['tmp_name'][$index];
                $sizes = $fieldFiles['size'][$index];
                $types = $fieldFiles['type'][$index];

                // If $names is nested array, flatten it by taking the first value
                if (is_array($names)) {
                    $originalName = reset($names);
                    $tmpName = reset($tmpNames);
                    $size = reset($sizes);
                    $type = reset($types);
                } else {
                    $originalName = $names;
                    $tmpName = $tmpNames;
                    $size = $sizes;
                    $type = $types;
                }

                // Sanitize filename
                $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9_\.-]/", "_", basename($originalName));
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $sampleFileName = $fileName;
                } else {
                    throw new Exception("Failed to move uploaded file for field: " . htmlspecialchars($f['label'] ?? ''));
                }
            }

            // Insert field into DB
            $insert->execute([
                $academic_year_id,
                $f['label'] ?? '',
                $f['type'] ?? 'text',
                isset($f['required']) && $f['required'] ? 1 : 0,
                $f['options'] ?? '',
                $f['description'] ?? '',
                $currentOrder,
                $sampleFileName
            ]);
        }

        // Save number of columns if provided
        if ($numColumns !== null) {
            $pdo->prepare("
                INSERT INTO voter_columns (academic_year_id, number)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE number = VALUES(number)
            ")->execute([$academic_year_id, $numColumns]);
        }

        $pdo->commit();

        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'Form fields saved successfully.';
    } catch (Exception $e) {
        echo $e->getMessage();
        $pdo->rollBack();
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'Database or upload error: ' . $e->getMessage();
    }

    // Redirect back to fields page
    header("Location: academic_details.php?ay_id={$academic_year_id}&tab=customfields");
    exit();
}
