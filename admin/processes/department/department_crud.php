<?php
session_start();
include('../../includes/conn.php');

$action = $_GET['action'] ?? '';
$redirectUrl = '../../academic_details.php?tab=department';

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

    if ($action === 'add') {
        $name = trim($_POST['department_name'] ?? '');
        $college_id = $_POST['college_id'] ?? 0;

        if (empty($name) || empty($college_id)) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Department name and college are required.';
            header("Location: $redirectUrl");
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO departments (department_name, college_id) VALUES (?, ?)");
        $stmt->execute([$name, $college_id]);

        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'Department added successfully.';
        header("Location: $redirectUrl");
        exit;
    }

    if ($action === 'update') {
        $id = $_POST['department_id'] ?? 0;
        $name = trim($_POST['department_name'] ?? '');
        $college_id = $_POST['college_id'] ?? 0;

        if (empty($id) || empty($name) || empty($college_id)) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Department ID, name, and college are required.';
            header("Location: $redirectUrl");
            exit;
        }

        $stmt = $pdo->prepare("UPDATE departments SET department_name = ?, college_id = ? WHERE department_id = ?");
        $stmt->execute([$name, $college_id, $id]);

        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'Department updated successfully.';
        header("Location: $redirectUrl");
        exit;
    }

    if ($action === 'delete') {
        $id = $_POST['department_id'] ?? 0;

        if (empty($id)) {
            $_SESSION['status'] = 'error';
            $_SESSION['message'] = 'Department ID is required.';
            header("Location: $redirectUrl");
            exit;
        }

        // $stmt = $pdo->prepare("SELECT COUNT(*) FROM majors WHERE department_id = ?");
        // $stmt->execute([$id]);
        // if ($stmt->fetchColumn() > 0) {
        //     $_SESSION['status'] = 'error';
        //     $_SESSION['message'] = 'Cannot delete department with associated majors.';
        //     header("Location: $redirectUrl");
        //     exit;
        // }

        $stmt = $pdo->prepare("DELETE FROM departments WHERE department_id = ?");
        $stmt->execute([$id]);

        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'Department deleted successfully.';
        header("Location: $redirectUrl");
        exit;
    }

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
