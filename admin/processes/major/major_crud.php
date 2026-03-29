<?php
session_start();
include('../../includes/conn.php');

$action = $_GET['action'] ?? '';
$redirectUrl = '../../academic_details.php?tab=majors';

try {

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'Unauthorized';
        header("Location: $redirectUrl");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'Invalid request method.';
        header("Location: $redirectUrl");
        exit;
    }

    // ============================
    // ADD MAJOR
    // ============================
    if ($action === 'add') {
        $name = trim($_POST['major_name'] ?? '');
        $course_id = $_POST['course_id'] ?? 0;

        if (empty($name) || empty($course_id)) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Major name and course are required.';
            header("Location: $redirectUrl");
            exit;
        }

        // Prevent duplicate for same course
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM majors WHERE major_name = ? AND course_id = ?");
        $stmt->execute([$name, $course_id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'This major already exists for the selected course.';
            header("Location: $redirectUrl");
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO majors (major_name, course_id) VALUES (?, ?)");
        $stmt->execute([$name, $course_id]);

        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'Major added successfully.';
        header("Location: $redirectUrl");
        exit;
    }

    // ============================
    // UPDATE MAJOR
    // ============================
    if ($action === 'update') {
        $id = $_POST['major_id'] ?? 0;
        $name = trim($_POST['major_name'] ?? '');
        $course_id = $_POST['course_id'] ?? 0;

        if (empty($id) || empty($name) || empty($course_id)) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Major ID, name, and course are required.';
            header("Location: $redirectUrl");
            exit;
        }

        // Prevent updating to duplicate name for same course
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM majors WHERE major_name = ? AND course_id = ? AND major_id != ?");
        $stmt->execute([$name, $course_id, $id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Another major with this name already exists for the selected course.';
            header("Location: $redirectUrl");
            exit;
        }

        $stmt = $pdo->prepare("UPDATE majors SET major_name = ?, course_id = ? WHERE major_id = ?");
        $stmt->execute([$name, $course_id, $id]);

        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'Major updated successfully.';
        header("Location: $redirectUrl");
        exit;
    }

    // ============================
    // DELETE MAJOR
    // ============================
    if ($action === 'delete') {
        $id = $_POST['major_id'] ?? 0;

        if (empty($id)) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Major ID is required.';
            header("Location: $redirectUrl");
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM majors WHERE major_id = ?");
        $stmt->execute([$id]);

        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'Major deleted successfully.';
        header("Location: $redirectUrl");
        exit;
    }

    // ============================
    // Invalid action
    // ============================
    $_SESSION['status'] = 'error';
    $_SESSION['message'] = 'Invalid action.';
    header("Location: $redirectUrl");
    exit;
} catch (Exception $e) {
    $_SESSION['status'] = 'error';
    $_SESSION['message'] = $e->getMessage();
    header("Location: $redirectUrl");
    exit;
}
