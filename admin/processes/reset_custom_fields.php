<?php
require_once '../includes/conn.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['academic_year_id'])) {
        die("Invalid request.");
    }

    $academicYearId = intval($_POST['academic_year_id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM voter_custom_fields WHERE academic_year_id = ?");
        $stmt->execute([$academicYearId]);

        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'Custom form fields deleted successfully.';
    } catch (PDOException $e) {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'Database Error: ' . $e->getMessage();
    }
}

header("Location: ../academic_details.php?tab=customfields");
