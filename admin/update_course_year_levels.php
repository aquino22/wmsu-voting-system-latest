<?php
session_start();
include('../../includes/conn.php');
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'] ?? null;
    $year_levels = $_POST['year_levels'] ?? [];

    if (!$course_id) {
        echo json_encode(['status' => 'error', 'message' => 'Course ID is missing.']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Delete existing year levels for the course
        $delete_stmt = $pdo->prepare("DELETE FROM course_year_levels WHERE course_id = ?");
        $delete_stmt->execute([$course_id]);

        // Insert new year levels
        if (!empty($year_levels)) {
            $insert_stmt = $pdo->prepare("INSERT INTO course_year_levels (course_id, year_level) VALUES (?, ?)");
            foreach ($year_levels as $level) {
                $insert_stmt->execute([$course_id, $level]);
            }
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Year levels updated successfully.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
