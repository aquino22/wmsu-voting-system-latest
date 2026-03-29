<?php
session_start();
require '../../includes/conn.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $adviser_id         = trim($_POST['adviser_id'] ?? '');
    $first_name         = trim($_POST['firstName'] ?? '');
    $middle_name        = trim($_POST['middleName'] ?? '');
    $last_name          = trim($_POST['lastName'] ?? '');
    $email              = trim($_POST['user_email'] ?? '');
    $passwordPlain      = $_POST['password'] ?? '';
    $college_id         = $_POST['college_id'] ?? null;
    $department_id      = $_POST['department_id'] ?? null;
    $major_id           = !empty($_POST['major_id']) ? $_POST['major_id'] : null;
    $wmsu_campus_id     = $_POST['wmsu_campus_id'] ?? null;
    $external_campus_id = !empty($_POST['external_campus_id']) ? $_POST['external_campus_id'] : null;
    $year_level         = $_POST['year_level'] ?? null;
    $semester           = $_POST['semester'] ?? null;
    $school_year        = $_POST['school_year'] ?? null;
    $emailIds           = $_POST['email_ids'] ?? [];

    // Construct full name
    $fullName = trim("$first_name $middle_name $last_name");

    // Required check
    if (empty($adviser_id) || empty($email)) {
        $_SESSION['STATUS'] = "ADVISER_UPDATE_FAILED";
        header("Location: ../../advisers.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Check if email is taken by a DIFFERENT user
        $checkEmail = $pdo->prepare("
            SELECT u.id FROM users u
            JOIN advisers a ON a.email = u.email
            WHERE u.email = ? AND a.id != ?
        ");
        $checkEmail->execute([$email, $adviser_id]);
        if ($checkEmail->fetchColumn()) {
            $_SESSION['STATUS'] = "USER_EMAIL_ALREADY_EXISTS";
            header("Location: ../../advisers.php");
            exit();
        }

        // Build password update conditionally
        if (!empty($passwordPlain)) {
            $hashedPassword = password_hash($passwordPlain, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("
                UPDATE advisers SET
                    first_name         = ?,
                    middle_name        = ?,
                    last_name          = ?,
                    full_name          = ?,
                    email              = ?,
                    password           = ?,
                    college_id         = ?,
                    department_id      = ?,
                    major_id           = ?,
                    wmsu_campus_id     = ?,
                    external_campus_id = ?,
                    year_level         = ?,
                    semester           = ?,
                    school_year        = ?
                WHERE id = ?
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
                $external_campus_id,
                $year_level,
                $semester,
                $school_year,
                $adviser_id
            ]);

            // Also update password in users table
            $updateUserPass = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $updateUserPass->execute([$hashedPassword, $email]);
        } else {
            // No password change
            $stmt = $pdo->prepare("
                UPDATE advisers SET
                    first_name         = ?,
                    middle_name        = ?,
                    last_name          = ?,
                    full_name          = ?,
                    email              = ?,
                    college_id         = ?,
                    department_id      = ?,
                    major_id           = ?,
                    wmsu_campus_id     = ?,
                    external_campus_id = ?,
                    year_level         = ?,
                    semester           = ?,
                    school_year        = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $first_name,
                $middle_name,
                $last_name,
                $fullName,
                $email,
                $college_id,
                $department_id,
                $major_id,
                $wmsu_campus_id,
                $external_campus_id,
                $year_level,
                $semester,
                $school_year,
                $adviser_id
            ]);
        }

        // Update email in users table (in case email changed)
        $updateUserEmail = $pdo->prepare("
            UPDATE users u
            JOIN advisers a ON a.id = ?
            SET u.email = ?
            WHERE u.email = a.email
        ");
        $updateUserEmail->execute([$adviser_id, $email]);

        // ── SMTP emails ──────────────────────────────────────────────
        // 1. Release all previously assigned emails for this adviser
        $releaseSmtp = $pdo->prepare("
            UPDATE email SET status = 'available', adviser_id = NULL
            WHERE adviser_id = ?
        ");
        $releaseSmtp->execute([$adviser_id]);

        // 2. Assign newly selected emails
        if (!empty($emailIds)) {
            $assignSmtp = $pdo->prepare("
                UPDATE email SET status = 'taken', adviser_id = ?
                WHERE id = ?
            ");
            foreach ($emailIds as $smtpId) {
                if (is_numeric($smtpId)) {
                    $assignSmtp->execute([$adviser_id, $smtpId]);
                }
            }
        }

        $pdo->commit();
        $_SESSION['STATUS'] = "ADVISER_UPDATED_SUCCESSFULLY";
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Adviser update failed: " . $e->getMessage());
        $_SESSION['STATUS'] = "ADVISER_UPDATE_FAILED";
    }

    header("Location: ../../advisers.php");
    exit();
}
