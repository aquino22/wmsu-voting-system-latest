<?php
session_start();
require '../../includes/conn.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['STATUS'] = 'error';
    $_SESSION['MESSAGE'] = 'Unauthorized access.';
    header("Location: ../../login.php");
    exit;
}

$action = $_GET['action'] ?? '';

function redirectBack($status, $message)
{
    $_SESSION['status'] = $status;
    $_SESSION['message'] = $message;

    $redirect = '../../academic_details.php?tab=campuses' ?? '../../campuses.php';
    header("Location: $redirect");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectBack('error', 'Invalid request method.');
}

try {

    /*
    ============================
    ADD CAMPUS
    ============================
    */
    if ($action === 'add') {

        $name = trim($_POST['campus_name'] ?? '');
        $location = trim($_POST['campus_location'] ?? '');
        $type = trim($_POST['campus_type'] ?? '');
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
        $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

        if ($name === '') {
            redirectBack('error', 'Campus name is required.');
        }

        // Duplicate check
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM campuses WHERE LOWER(campus_name) = LOWER(?)");
        $stmt->execute([$name]);

        if ($stmt->fetchColumn() > 0) {
            redirectBack('error', 'A campus with this name already exists.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO campuses 
            (campus_name, campus_location, campus_type, latitude, longitude, parent_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([$name, $location, $type, $latitude, $longitude, $parent_id]);

        redirectBack('success', 'Campus added successfully.');
    }

    /*
    ============================
    UPDATE CAMPUS
    ============================
    */ elseif ($action === 'update') {

        $id = (int)($_POST['campus_id'] ?? 0);
        $name = trim($_POST['campus_name'] ?? '');
        $location = trim($_POST['campus_location'] ?? '');
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
        $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

        if (!$id || $name === '') {
            redirectBack('error', 'Campus ID and name are required.');
        }

        // Duplicate check excluding current record
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM campuses 
            WHERE LOWER(campus_name) = LOWER(?) 
            AND campus_id != ?
        ");
        $stmt->execute([$name, $id]);

        if ($stmt->fetchColumn() > 0) {
            redirectBack('error', 'Another campus with this name already exists.');
        }

        $stmt = $pdo->prepare("
            UPDATE campuses
            SET campus_name = ?, campus_location = ?, latitude = ?, longitude = ?, parent_id = ?
            WHERE campus_id = ?
        ");

        $stmt->execute([$name, $location, $latitude, $longitude, $parent_id, $id]);

        redirectBack('success', 'Campus updated successfully.');
    }

    /*
    ============================
    DELETE CAMPUS
    ============================
    */ elseif ($action === 'delete') {

        $id = (int)($_POST['campus_id'] ?? 0);

        if (!$id) {
            redirectBack('error', 'Campus ID is required.');
        }

        // Get campus
        $stmt = $pdo->prepare("SELECT campus_name FROM campuses WHERE campus_id = ?");
        $stmt->execute([$id]);
        $campus = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$campus) {
            redirectBack('error', 'Campus not found.');
        }

        // Prevent deletion of protected campuses
        if ($campus['campus_name'] === 'WMSU ESU' || $campus['campus_name'] === 'Main Campus') {
            redirectBack('error', 'This campus cannot be deleted.');
        }

        // Prevent deletion if child campuses exist
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM campuses WHERE parent_id = ?");
        $stmt->execute([$id]);

        if ($stmt->fetchColumn() > 0) {
            redirectBack('error', 'Cannot delete campus with child campuses.');
        }

        // Delete
        $stmt = $pdo->prepare("DELETE FROM campuses WHERE campus_id = ?");
        $stmt->execute([$id]);

        redirectBack('success', 'Campus deleted successfully.');
    } else {
        redirectBack('error', 'Invalid action.');
    }
} catch (Exception $e) {
    redirectBack('error', $e->getMessage());
}
