<?php
session_start();
require '../../includes/conn.php';

// Check if user is admin (adding based on your previous patterns)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['STATUS'] = "NON_ADMIN";
    header("Location: ../../index.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $event_id = (int)$_GET['id'];

    try {
        // Fetch the candidacy field from the events table
        $sql = "SELECT candidacy FROM events WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $event_id, PDO::PARAM_INT);
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            $_SESSION['STATUS'] = "EVENT_NOT_FOUND";
            $_SESSION['ERROR_MESSAGE'] = "Event with ID $event_id not found.";
            header("Location: ../../events.php");
            exit();
        }

        $candidacy = $event['candidacy'];

        // Start transaction
        $pdo->beginTransaction();

        // Unpublish the event (combining both updates into one)
        $sqlUnpublish = "
            UPDATE events 
            SET status = 'draft', 
                registration_enabled = 1
            WHERE id = :id
        ";
        $stmtUnpublish = $pdo->prepare($sqlUnpublish);
        $stmtUnpublish->bindParam(':id', $event_id, PDO::PARAM_INT);

        // Update related registration forms
        $sqlUpdateForms = "
            UPDATE registration_forms 
            SET status = 'draft' 
            WHERE election_name = :candidacy
        ";
        $stmtForms = $pdo->prepare($sqlUpdateForms);
        $stmtForms->bindParam(':candidacy', $candidacy, PDO::PARAM_STR);

        // Execute both updates
        $unpublishSuccess = $stmtUnpublish->execute();
        $formsSuccess = $stmtForms->execute();

        if ($unpublishSuccess && $formsSuccess) {
            $affectedForms = $stmtForms->rowCount();
            $pdo->commit();
            $_SESSION['STATUS'] = "UNPUBLISH_SUCCESSFUL";
            $_SESSION['MESSAGE'] = "Event unpublished successfully. $affectedForms related form(s) updated.";
        } else {
            $pdo->rollBack();
            throw new Exception(
                !$unpublishSuccess ? "Failed to unpublish event." : 
                "Failed to update related registration forms."
            );
        }

        header("Location: ../../events.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['STATUS'] = "DATABASE_ERROR";
        $_SESSION['ERROR_MESSAGE'] = "Database error: " . $e->getMessage();
        header("Location: ../../events.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['STATUS'] = "UNPUBLISH_UNSUCCESSFUL";
        $_SESSION['ERROR_MESSAGE'] = $e->getMessage();
        header("Location: ../../events.php");
        exit();
    }
} else {
    $_SESSION['STATUS'] = "INVALID_ID";
    $_SESSION['ERROR_MESSAGE'] = "Invalid or missing event ID.";
    header("Location: ../../events.php");
    exit();
}
?>