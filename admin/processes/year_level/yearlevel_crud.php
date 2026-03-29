<?php
session_start();
include('../../includes/conn.php');

$action = $_GET['action'] ?? '';

try {
    // 1. ADMIN AUTH & REQUEST CHECK
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // 2. INPUTS
    $course_id = $_POST['course_id'] ?? null;
    $major_id  = !empty($_POST['major_id']) ? $_POST['major_id'] : null;

    // We keep both arrays separate as requested
    $course_year_levels = $_POST['course_year_level_ids'] ?? [];
    $major_year_levels  = $_POST['major_year_level_ids'] ?? [];

    if (empty($course_id)) {
        throw new Exception('Course is required.');
    }

    // -----------------------------
    // UPDATE / SYNC ASSIGNMENTS
    // -----------------------------
    if ($action === 'update') {
        $pdo->beginTransaction();

        // --- PART A: Handle General Course Year Levels ---
        if (!empty($course_year_levels)) {
            // Wipe existing general levels (where major_id is NULL)
            $stmt = $pdo->prepare("DELETE FROM actual_year_levels WHERE course_id = ? AND major_id IS NULL");
            $stmt->execute([$course_id]);

            // Insert new general levels
            $stmt = $pdo->prepare("INSERT INTO actual_year_levels (course_id, major_id, year_level) VALUES (?, NULL, ?)");
            foreach ($course_year_levels as $level) {
                $stmt->execute([$course_id, $level]);
            }
        }

        // --- PART B: Handle Major-Specific Year Levels ---
        if (!empty($major_id) && !empty($major_year_levels)) {
            // Validate major belongs to course
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM majors WHERE major_id = ? AND course_id = ?");
            $stmt->execute([$major_id, $course_id]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Selected major does not belong to this course.');
            }

            // Wipe existing levels for this specific major
            $stmt = $pdo->prepare("DELETE FROM actual_year_levels WHERE course_id = ? AND major_id = ?");
            $stmt->execute([$course_id, $major_id]);

            // Insert new major-specific levels
            $stmt = $pdo->prepare("INSERT INTO actual_year_levels (course_id, major_id, year_level) VALUES (?, ?, ?)");
            foreach ($major_year_levels as $level) {
                $stmt->execute([$course_id, $major_id, $level]);
            }
        }

        $pdo->commit();
        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'Academic details updated successfully.';
    }

    // -----------------------------
    // CLEAR ASSIGNMENTS
    // -----------------------------
    elseif ($action === 'clear') {
        $pdo->beginTransaction();

        // Clear general levels
        $stmt = $pdo->prepare("DELETE FROM actual_year_levels WHERE course_id = ? AND major_id IS NULL");
        $stmt->execute([$course_id]);

        // Clear major levels if a major is selected
        if (!empty($major_id)) {
            $stmt = $pdo->prepare("DELETE FROM actual_year_levels WHERE course_id = ? AND major_id = ?");
            $stmt->execute([$course_id, $major_id]);
        }

        $pdo->commit();
        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'Year levels cleared successfully.';
    }

    header('Location: ../../academic_details.php?tab=yearlevel');
    exit;
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['status'] = 'error';
    $_SESSION['message'] = $e->getMessage();
    header('Location: ../../academic_details.php?tab=yearlevel');
    exit;
}
