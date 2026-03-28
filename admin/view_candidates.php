<?php
require_once 'includes/conn.php';

try {
    // Validate and sanitize form_id
    if (!isset($_GET['form_id']) || !is_numeric($_GET['form_id'])) {
        throw new Exception("Invalid form ID");
    }
    
    $form_id = (int)$_GET['form_id'];
    
    // Fetch form details
    $form_stmt = $pdo->prepare("SELECT * FROM registration_forms WHERE id = ? AND status = 'active'");
    $form_stmt->execute([$form_id]);
    $form = $form_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$form) {
        throw new Exception("Form not found or inactive");
    }

    // Fetch all candidates for this form
    $candidates_stmt = $pdo->prepare("SELECT * FROM candidates WHERE form_id = ? ORDER BY created_at DESC");
    $candidates_stmt->execute([$form_id]);
    $candidates = $candidates_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch field definitions
    $fields_stmt = $pdo->prepare("SELECT id, field_name, field_type FROM form_fields WHERE form_id = ?");
    $fields_stmt->execute([$form_id]);
    $fields = $fields_stmt->fetchAll(PDO::FETCH_ASSOC);
    $field_map = array_column($fields, null, 'id'); // Map field IDs to their details

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Candidates for <?php echo htmlspecialchars($form['form_name']); ?></title>
        <style>
            .candidate-container { 
                border: 1px solid #ccc; 
                padding: 15px; 
                margin: 10px 0; 
                border-radius: 5px; 
            }
            .field-item { margin: 5px 0; }
            .file-link { color: blue; text-decoration: underline; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <h2>Candidates for <?php echo htmlspecialchars($form['form_name']); ?></h2>
        
        <?php if (empty($candidates)): ?>
            <p>No candidates have submitted this form yet.</p>
        <?php else: ?>
            <?php foreach ($candidates as $candidate): ?>
                <div class="candidate-container">
                    <h3>Candidate ID: <?php echo $candidate['id']; ?></h3>
                    <p>Submitted: <?php echo htmlspecialchars($candidate['created_at']); ?></p>
                    
                    <?php
                    // Fetch responses for this candidate
                    $responses_stmt = $pdo->prepare("SELECT field_id, value FROM candidate_responses WHERE candidate_id = ?");
                    $responses_stmt->execute([$candidate['id']]);
                    $responses = $responses_stmt->fetchAll(PDO::FETCH_ASSOC);
                    $response_map = array_column($responses, 'value', 'field_id');

                    // Fetch files for this candidate
                    $files_stmt = $pdo->prepare("SELECT field_id, file_path FROM candidate_files WHERE candidate_id = ?");
                    $files_stmt->execute([$candidate['id']]);
                    $files = $files_stmt->fetchAll(PDO::FETCH_ASSOC);
                    $file_map = array_column($files, 'file_path', 'field_id');

                    // Display all fields
                    foreach ($field_map as $field_id => $field): ?>
                        <div class="field-item">
                            <strong><?php echo htmlspecialchars($field['field_name']); ?>:</strong>
                            <?php
                            if ($field['field_type'] === 'file' && isset($file_map[$field_id])) {
                                $file_path = $file_map[$field_id];
                                $file_name = basename($file_path);
                                echo '<a href="' . htmlspecialchars($file_path) . '" class="file-link" target="_blank">' . 
                                     htmlspecialchars($file_name) . '</a>';
                            } elseif (isset($response_map[$field_id])) {
                                $value = $response_map[$field_id];
                                if ($field['field_type'] === 'checkbox') {
                                    echo $value ? 'Yes' : 'No';
                                } else {
                                    echo htmlspecialchars($value);
                                }
                            } else {
                                echo 'Not provided';
                            }
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <p><a href="display_form.php?form_id=<?php echo $form_id; ?>">Back to Form</a></p>
        <p><a href="create_form.php">Back to Form Builder</a></p>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
    </head>
    <body>
        <div class="error">
            <h2>Error</h2>
            <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
            <a href="create_form.php">Back to Form Builder</a>
        </div>
    </body>
    </html>
    <?php
}
?>