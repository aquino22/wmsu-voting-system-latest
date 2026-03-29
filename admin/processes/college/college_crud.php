<?php
session_start();
require '../../includes/conn.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['status'] = 'error';
    $_SESSION['message'] = 'Unauthorized access.';
    header("Location: /admin/academic_details.php?tab=college");
    exit;
}

$action = $_GET['action'] ?? '';

function redirectBack($status, $message)
{
    $_SESSION['status'] = $status;
    $_SESSION['message'] = $message;

    header("Location: ../../academic_details.php?tab=college");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectBack('error', 'Invalid request method.');
}

try {

    /*
    ============================
    ADD COLLEGE
    ============================
    */
    if ($action === 'add') {

        $name = trim($_POST['college_name'] ?? '');
        $abbreviation = trim($_POST['college_abbreviation'] ?? '');
        $campus_id = $_POST['campus_id'] ?? null;
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;

        if (!$name) {
            redirectBack('error', 'College name is required.');
        }

        $pdo->beginTransaction();

        // Check existing college
        $stmt = $pdo->prepare("SELECT college_id FROM colleges WHERE LOWER(college_name) = LOWER(?)");
        $stmt->execute([$name]);
        $existingCollege = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingCollege) {
            $college_id = $existingCollege['college_id'];
        } else {

            $stmt = $pdo->prepare("
                INSERT INTO colleges (college_name, college_abbreviation)
                VALUES (?, ?)
            ");
            $stmt->execute([$name, $abbreviation]);

            $college_id = $pdo->lastInsertId();
        }

        if ($campus_id) {

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM college_coordinates
                WHERE college_id = ? AND campus_id = ?
            ");
            $stmt->execute([$college_id, $campus_id]);

            if ($stmt->fetchColumn() > 0) {
                throw new Exception('This college already has coordinates for the selected campus.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO college_coordinates
                (college_id, campus_id, latitude, longitude)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $college_id,
                $campus_id,
                $latitude ?: null,
                $longitude ?: null
            ]);
        }

        $pdo->commit();

        redirectBack('success', 'College added successfully.');
    }

    /*
    ============================
    UPDATE COLLEGE
    ============================
    */ elseif ($action === 'update') {

        $id = (int) ($_POST['college_id'] ?? 0);
        $name = trim($_POST['college_name'] ?? '');
        $abbreviation = trim($_POST['college_abbreviation'] ?? '');
        $campus_id = $_POST['campus_id'] ?? null;
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;

        if (!$id || !$name) {
            redirectBack('error', 'College ID and name are required.');
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM colleges
            WHERE LOWER(college_name) = LOWER(?)
            AND college_id != ?
        ");
        $stmt->execute([$name, $id]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Another college with this name already exists.');
        }

        $stmt = $pdo->prepare("
            UPDATE colleges
            SET college_name = ?, college_abbreviation = ?
            WHERE college_id = ?
        ");
        $stmt->execute([$name, $abbreviation, $id]);

        if ($campus_id) {

            $stmt = $pdo->prepare("
                SELECT coordinate_id
                FROM college_coordinates
                WHERE college_id = ? AND campus_id = ?
            ");
            $stmt->execute([$id, $campus_id]);
            $coord = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($coord) {

                $stmt = $pdo->prepare("
                    UPDATE college_coordinates
                    SET latitude = ?, longitude = ?
                    WHERE coordinate_id = ?
                ");

                $stmt->execute([
                    $latitude ?: null,
                    $longitude ?: null,
                    $coord['coordinate_id']
                ]);
            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO college_coordinates
                    (college_id, campus_id, latitude, longitude)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->execute([
                    $id,
                    $campus_id,
                    $latitude ?: null,
                    $longitude ?: null
                ]);
            }
        }

        $pdo->commit();

        redirectBack('success', 'College updated successfully.');
    }

    /*
    ============================
    DELETE COLLEGE
    ============================
    */ elseif ($action === 'delete') {

        $id = (int) ($_POST['college_id'] ?? 0);

        if (!$id) {
        }

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM departments
            WHERE college_id = ?
        ");
        $stmt->execute([$id]);

        if ($stmt->fetchColumn() > 0) {
            redirectBack('error', 'Cannot delete college with associated departments.');
        }

        $stmt = $pdo->prepare("DELETE FROM colleges WHERE college_id = ?");
        $stmt->execute([$id]);

        redirectBack('success', 'College deleted successfully.');
    } else {
        redirectBack('error', 'Invalid action.');
    }
} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirectBack('error', $e->getMessage());
}
