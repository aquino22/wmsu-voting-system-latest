<?php
require '../../includes/conn.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'])) {
        $eventId = $_POST['id'];

        try {
            // Start a transaction
            $pdo->beginTransaction();

            // Find any related registration form where election_name matches events.candidacy
            $formStmt = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = (SELECT candidacy FROM events WHERE id = :eventId)");
            $formStmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
            $formStmt->execute();
            $formId = $formStmt->fetchColumn();

            if ($formId) {
                // Delete candidate responses
                $respStmt = $pdo->prepare("
                    DELETE cr FROM candidate_responses cr
                    INNER JOIN candidates c ON cr.candidate_id = c.id
                    WHERE c.form_id = :formId
                ");
                $respStmt->bindParam(':formId', $formId, PDO::PARAM_INT);
                $respStmt->execute();

                // Delete candidate files
                $fileStmt = $pdo->prepare("
                    DELETE cf FROM candidate_files cf
                    INNER JOIN candidates c ON cf.candidate_id = c.id
                    WHERE c.form_id = :formId
                ");
                $fileStmt->bindParam(':formId', $formId, PDO::PARAM_INT);
                $fileStmt->execute();

                // Delete candidates
                $candStmt = $pdo->prepare("DELETE FROM candidates WHERE form_id = :formId");
                $candStmt->bindParam(':formId', $formId, PDO::PARAM_INT);
                $candStmt->execute();

                // Delete form fields
                $fieldStmt = $pdo->prepare("DELETE FROM form_fields WHERE form_id = :formId");
                $fieldStmt->bindParam(':formId', $formId, PDO::PARAM_INT);
                $fieldStmt->execute();

                // Delete the registration form
                $regStmt = $pdo->prepare("DELETE FROM registration_forms WHERE id = :formId");
                $regStmt->bindParam(':formId', $formId, PDO::PARAM_INT);
                $regStmt->execute();
            }

            // Delete the event
            $eventStmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
            $eventStmt->bindParam(':id', $eventId, PDO::PARAM_INT);
            $eventStmt->execute();

            // Commit the transaction
            $pdo->commit();

            echo json_encode([
                "status" => "success",
                "message" => "Event and all associated registration data deleted successfully."
            ]);

        } catch (PDOException $e) {
            // Roll back on error
            $pdo->rollBack();
            echo json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid request."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid method."]);
}
?>