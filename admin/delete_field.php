<?php
session_start();
include('includes/conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $academic_year_id = $_POST['academic_year_id'];

    if (empty($id)) {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'Invalid ID.';
        header("Location: voter_custom_fields.php?ay_id=" . $academic_year_id);
        exit();
    }

    try {
        // Fetch file path before deleting to remove the file from server
        $stmt = $pdo->prepare("SELECT field_sample FROM voter_custom_fields WHERE id = ?");
        $stmt->execute([$id]);
        $field = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($field && !empty($field['field_sample'])) {
            $filePath = '../uploads/samples/' . $field['field_sample'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM voter_custom_fields WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'Custom field deleted successfully.';
    } catch (PDOException $e) {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'Error deleting field: ' . $e->getMessage();
    }

    header("Location: academic_details.php?ay_id=" . $academic_year_id . "&tab=customfields");
    exit();
}
