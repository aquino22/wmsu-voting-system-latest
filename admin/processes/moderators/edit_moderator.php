<?php
session_start();
require '../../includes/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

// CSRF check
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit();
}

$moderator_id  = trim($_POST['moderator_id']  ?? '');
$name          = trim($_POST['name']          ?? '');
$email         = trim($_POST['email']         ?? '');
$passwordPlain = $_POST['password']           ?? '';
$gender        = trim($_POST['gender']        ?? '');
$college_id    = $_POST['college']            ?? null;
$department_id = $_POST['department']         ?? null;
$major_id      = !empty($_POST['major']) && $_POST['major'] != '0' ? $_POST['major'] : null;
$precinct_id   = !empty($_POST['edit_precinct'])   ? $_POST['edit_precinct'] : null;

// Required field check
if (empty($moderator_id) || empty($name) || empty($email) || empty($gender) || empty($college_id) || empty($department_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

if (empty($precinct_id)) {
    echo json_encode(['success' => false, 'message' => 'No precinct assigned. Please complete the campus selection.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Check if email is taken by a DIFFERENT moderator
    $checkEmail = $pdo->prepare("
        SELECT id FROM moderators
        WHERE email = ? AND id != ?
    ");
    $checkEmail->execute([$email, $moderator_id]);
    if ($checkEmail->fetchColumn()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'This email is already used by another moderator.']);
        exit();
    }

    // Also check users table for same email used by non-moderator
    $checkUser = $pdo->prepare("
    SELECT u.id FROM users u
    WHERE u.email = ?
    AND u.role != 'moderator'
");
    $checkUser->execute([$email]);
    if ($checkUser->fetchColumn()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'This email is already registered in the system.']);
        exit();
    }

    // Get current email before update (needed to update users table)
    $getCurrentEmail = $pdo->prepare("SELECT email FROM moderators WHERE id = ?");
    $getCurrentEmail->execute([$moderator_id]);
    $currentEmail = $getCurrentEmail->fetchColumn();

    if (!$currentEmail) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Moderator not found.']);
        exit();
    }

    if (!empty($passwordPlain)) {
        // Update with new password
        $hashedPassword = password_hash($passwordPlain, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            UPDATE moderators SET
                name          = ?,
                email         = ?,
                password      = ?,
                gender        = ?,
                college       = ?,
                department    = ?,
                major         = ?,
                precinct      = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $name,
            $email,
            $hashedPassword,
            $gender,
            $college_id,
            $department_id,
            $major_id,
            $precinct_id,
            $moderator_id
        ]);

        // Update password in users table
        $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")
            ->execute([$hashedPassword, $currentEmail]);
    } else {
        // No password change
        $stmt = $pdo->prepare("
            UPDATE moderators SET
                name          = ?,
                email         = ?,
                gender        = ?,
                college       = ?,
                department    = ?,
                major         = ?,
                precinct      = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $name,
            $email,
            $gender,
            $college_id,
            $department_id,
            $major_id,
            $precinct_id,
            $moderator_id
        ]);
    }

    // Update email in users table if it changed
    if ($email !== $currentEmail) {
        $pdo->prepare("UPDATE users SET email = ? WHERE email = ?")
            ->execute([$email, $currentEmail]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Moderator updated successfully.']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Moderator update failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' =>  $e->getMessage()]);
}
