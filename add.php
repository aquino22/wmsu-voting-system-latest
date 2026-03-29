<?php
session_start();
require '../../includes/conn.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name      = trim($_POST['firstName'] ?? '');
    $middle_name     = trim($_POST['middleName'] ?? '');
    $last_name       = trim($_POST['lastName'] ?? '');
    $email           = trim($_POST['user_email'] ?? '');
    $passwordPlain   = $_POST['password'] ?? '';
    $college         = trim($_POST['college'] ?? '');
    $department      = trim($_POST['department'] ?? '');
    $wmsu_campus     = trim($_POST['wmsu_campus'] ?? '');
    $external_campus = trim($_POST['external_campus'] ?? '');
    $year_level      = trim($_POST['year_level'] ?? '');
    $major           = trim($POST['major'] ?? '');
    $emailIds        = $_POST['email_ids'] ?? [];
    $school_year = $_POST['school_year'] ?? '';

    // Construct full name properly
    $fullName = trim("$first_name $middle_name $last_name");

    // Basic required check
    if (empty($email) || empty($passwordPlain)) {
        $_SESSION['STATUS'] = "MISSING_REQUIRED_FIELDS";
        header("Location: ../../advisers.php");
        exit();
    }

    // Check if email already exists in users table
    $checkUser = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $checkUser->execute([$email]);

    if ($checkUser->fetchColumn() > 0) {
        $_SESSION['STATUS'] = "USER_EMAIL_ALREADY_EXISTS";
        header("Location: ../../advisers.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        $hashedPassword = password_hash($passwordPlain, PASSWORD_BCRYPT);

        // Insert adviser record
        $stmt = $pdo->prepare("
            INSERT INTO advisers 
            (first_name, middle_name, last_name, full_name, email, password, college, wmsu_campus, external_campus, department, major, year, created_at, school_year) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([
            $first_name,
            $middle_name,
            $last_name,
            $fullName,
            $email,
            $hashedPassword,
            $college,
            $wmsu_campus,
            $external_campus !== '' ? $external_campus : null,
            $department,
            $major,
            $year_level,
            $school_year
        ]);

        $adviserId = $pdo->lastInsertId();

        // Assign SMTP email accounts (if any selected)
        if (!empty($emailIds) && is_array($emailIds)) {
            $updateSmtp = $pdo->prepare("UPDATE email SET status = 'taken', adviser_id = ? WHERE id = ?");
            foreach ($emailIds as $smtpId) {
                if (is_numeric($smtpId)) {
                    $updateSmtp->execute([$adviserId, $smtpId]);
                }
            }
        }

        // Create user login
        $insertUser = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'adviser')");
        $insertUser->execute([$email, $hashedPassword]);

        $pdo->commit();
        $_SESSION['STATUS'] = "ADVISER_ADDED_SUCCESSFULLY";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['STATUS'] = "ADVISER_ADD_FAILED";
        error_log("Adviser creation failed: " . $e->getMessage());
    }

    header("Location: ../../advisers.php");
    exit();
}
