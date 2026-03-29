<?php
session_start();
include('../../includes/conn.php');

$action = $_GET['action'] ?? '';
$redirectUrl = '../../academic_details.php?tab=courses';

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'Invalid request method.';
        header("Location: $redirectUrl");
        exit;
    }

    // ============================
    // ADD COURSE
    // ============================
    if ($action === 'add') {
        $college_id  = $_POST['college_id'] ?? '';
        $course_name = trim($_POST['course_name'] ?? '');
        $course_code = trim($_POST['course_code'] ?? '');

        if (empty($college_id) || empty($course_name)) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'College and Course Name are required.';
            header("Location: $redirectUrl");
            exit;
        }

        // Check if college exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM colleges WHERE college_id = ?");
        $stmt->execute([$college_id]);
        if ($stmt->fetchColumn() == 0) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Selected college does not exist.';
            header("Location: $redirectUrl");
            exit;
        }

        // Check duplicates
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE college_id = ? AND LOWER(course_name) = LOWER(?)");
        $stmt->execute([$college_id, $course_name]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'This course already exists for the selected college.';
            header("Location: $redirectUrl");
            exit;
        }

        // Insert course
        $stmt = $pdo->prepare("INSERT INTO courses (college_id, course_name, course_code) VALUES (?, ?, ?)");
        $stmt->execute([$college_id, $course_name, $course_code]);

        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'Course added successfully.';
        header("Location: $redirectUrl");
        exit;
    }

    // ============================
    // UPDATE COURSE
    // ============================
    if ($action === 'update') {
        $id          = (int)($_POST['course_id'] ?? 0);
        $college_id  = (int)($_POST['college_id'] ?? 0);
        $course_name = trim($_POST['course_name'] ?? '');
        $course_code = trim($_POST['course_code'] ?? '');

        if (empty($id) || empty($college_id) || empty($course_name)) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Course ID, College, and Course Name are required.';
            header("Location: $redirectUrl");
            exit;
        }

        // Check duplicates (excluding current course)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE college_id = ? AND LOWER(course_name) = LOWER(?) AND id != ?");
        $stmt->execute([$college_id, $course_name, $id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Another course with this name already exists for the selected college.';
            header("Location: $redirectUrl");
            exit;
        }

        // Update course
        $stmt = $pdo->prepare("UPDATE courses SET college_id = ?, course_name = ?, course_code = ? WHERE id = ?");
        $stmt->execute([$college_id, $course_name, $course_code, $id]);

        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'Course updated successfully.';
        header("Location: $redirectUrl");
        exit;
    }

    // ============================
    // DELETE COURSE
    // ============================
    if ($action === 'delete') {
        $id = (int)($_POST['course_id'] ?? 0);

        if (empty($id)) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Course ID is required.';
            header("Location: $redirectUrl");
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'Course deleted successfully.';
        header("Location: $redirectUrl");
        exit;
    }

    // ============================
    // Invalid action
    // ============================
    throw new Exception('Invalid action.');
} catch (Exception $e) {
    $_SESSION['status'] = 'error';
    $_SESSION['message'] = $e->getMessage();
    header("Location: $redirectUrl");
    exit;
}
