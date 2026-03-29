<?php
session_start();
require '../../includes/conn.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get IDs directly from POST
    $first_name        = trim($_POST['firstName'] ?? '');
    $middle_name       = trim($_POST['middleName'] ?? '');
    $last_name         = trim($_POST['lastName'] ?? '');
    $email             = trim($_POST['user_email'] ?? '');
    $passwordPlain     = $_POST['password'] ?? '';
    $college_id        = $_POST['college_id'] ?? null;
    $department_id     = $_POST['department_id'] ?? null;
    $major_id          = $_POST['major_id'];
    $wmsu_campus_id    = $_POST['wmsu_campus_id'] ?? null;
    $external_campus_id = $_POST['external_campus_id'] ?? null;
    $year_level        = $_POST['year_level'] ?? null;
    $semester          = $_POST['semester'] ?? null;
    $school_year       = $_POST['school_year'] ?? null;
    $emailIds          = $_POST['email_ids'] ?? [];

    // Construct full name
    $fullName = trim("$first_name $middle_name $last_name");

    // Required check
    if (empty($email) || empty($passwordPlain)) {
        $_SESSION['STATUS'] = "MISSING_REQUIRED_FIELDS";
        header("Location: ../../advisers.php");
        exit();
    }

    // Check if email exists
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

        // Insert adviser record using IDs
        $stmt = $pdo->prepare("
    INSERT INTO advisers 
    (first_name, middle_name, last_name, full_name, email, password,
     college_id, department_id, major_id, wmsu_campus_id, external_campus_id,
     year_level, semester, school_year, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

        $stmt->execute([
            $first_name,
            $middle_name,
            $last_name,
            $fullName,
            $email,
            $hashedPassword,
            $college_id,
            $department_id,
            $major_id,
            $wmsu_campus_id,
            $external_campus_id ?: null,
            $year_level,
            $semester,
            $school_year
        ]);

        $adviserId = $pdo->lastInsertId();

        // Assign SMTP emails if selected
        if (!empty($emailIds)) {
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
        echo "work";
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Adviser creation failed: " . $e->getMessage());
        $_SESSION['STATUS'] = "ADVISER_ADD_FAILED";
        echo "not work: " . $e->getMessage();
    }
    header("Location: ../../advisers.php");
    exit();
}
