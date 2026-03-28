<?php
session_start();
require '../../includes/conn.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['adviser_id'])) {
    $id              = (int) $_POST['adviser_id'];
    $first_name      = trim($_POST['firstName'] ?? '');
    $middle_name     = trim($_POST['middleName'] ?? '');
    $last_name       = trim($_POST['lastName'] ?? '');
    $email           = trim($_POST['user_email'] ?? '');
    $password        = $_POST['password'] ?? ''; // New password field
    $college         = trim($_POST['college'] ?? '');
    $department      = trim($_POST['department'] ?? '');
    $wmsu_campus     = trim($_POST['wmsu_campus'] ?? '');
    $external_campus = trim($_POST['external_campus'] ?? '');
    $year_level      = trim($_POST['year_level'] ?? '');
    $school_year     = trim($_POST['school_year'] ?? '');
    $emailIds        = $_POST['email_ids'] ?? [];

    $fullName = trim("$first_name $middle_name $last_name");


    $checkAccount = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkAccount->execute([$email]);
    $userId = $checkAccount->fetchColumn(); // This will directly return the id or false


    if (empty($email)) {
        $_SESSION['STATUS'] = "MISSING_REQUIRED_FIELDS";
        header("Location: ../../advisers.php");
        exit();
    }

    // 1. Fetch previous adviser details
    $prev = $pdo->prepare("SELECT college, department, year AS year_level, school_year FROM advisers WHERE id = ?");
    $prev->execute([$id]);
    $prevData = $prev->fetch(PDO::FETCH_ASSOC);

    if ($prevData) {
        $deptChanged = $prevData['department'] !== $department;
        $yearChanged = $prevData['year_level'] !== $year_level;
        $syChanged = $prevData['school_year'] !== $school_year;

        if ($deptChanged || $yearChanged || $syChanged) {
            // 2. Archive voters handled by the adviser before
            $archive = $pdo->prepare("
                INSERT INTO voters_copy_adviser (
                    adviser_id, student_id, email, password, first_name, middle_name, last_name,
                    course, major, year_level, college, department, wmsu_campus,
                    external_campus, first_cor, second_cor, semester,
                    activation_token, activation_expiry, is_active, status, school_year
                )
                SELECT 
                    ?, student_id, email, password, first_name, middle_name, last_name,
                    course, major, year_level, college, department, wmsu_campus,
                    external_campus, first_cor, second_cor, semester,
                    activation_token, activation_expiry, is_active, status, school_year
                FROM voters
                WHERE college = ? AND department = ? AND year_level = ? AND school_year = ?
            ");
            $archive->execute([
                $id,
                $prevData['college'],
                $prevData['department'],
                $prevData['year_level'],
                $prevData['school_year']
            ]);

            // Update adviser has_changed flag
            $update = $pdo->prepare("
                UPDATE advisers SET 
                    has_changed = ?
                WHERE id = ?
            ");
            $update->execute([
                '1',
                $id
            ]);
        }
    }

    try {
        $pdo->beginTransaction();

        // Update adviser
        $update = $pdo->prepare("
            UPDATE advisers SET 
                first_name = ?, 
                middle_name = ?, 
                last_name = ?, 
                full_name = ?, 
                email = ?, 
                college = ?, 
                department = ?, 
                wmsu_campus = ?, 
                external_campus = ?, 
                year = ?, 
                school_year = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $update->execute([
            $first_name,
            $middle_name,
            $last_name,
            $fullName,
            $email,
            $college,
            $department,
            $wmsu_campus,
            $external_campus !== '' ? $external_campus : null,
            $year_level,
            $school_year,
            $id
        ]);

        // Validate adviser exists
        $checkAdviser = $pdo->prepare("SELECT COUNT(*) FROM advisers WHERE id = ?");
        $checkAdviser->execute([$id]);

        if ($checkAdviser->fetchColumn() == 0) {
            throw new Exception("Invalid adviser ID: $id");
        }

        // Unassign previous SMTPs
        $pdo->prepare("UPDATE email SET status = 'available', adviser_id = NULL WHERE adviser_id = ?")
            ->execute([$id]);

        // Assign new SMTPs
        if (!empty($emailIds) && is_array($emailIds)) {
            $assign = $pdo->prepare("UPDATE email SET status = 'taken', adviser_id = ? WHERE id = ?");
            $verify = $pdo->prepare("SELECT id FROM email WHERE id = ?");

            foreach ($emailIds as $smtpId) {
                if (is_numeric($smtpId)) {
                    $verify->execute([$smtpId]);
                    if ($verify->fetch()) {
                        $assign->execute([$id, $smtpId]);
                    }
                }
            }
        }

        // Update user login email and password
        $updateUser = $pdo->prepare("
            UPDATE users SET 
                email = ?,
                password = ?
            WHERE role = 'adviser' AND id = ?
        ");
        $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
        $updateUser->execute([
            $email,
            $hashedPassword ?: $prevData['password'], // Retain existing password if none provided
              $userId
        ]);

        $pdo->commit();
        $_SESSION['STATUS'] = "ADVISER_UPDATED_SUCCESSFULLY";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['STATUS'] = "ADVISER_UPDATE_FAILED";
        error_log("Adviser update failed: " . $e->getMessage());
    }

    header("Location: ../../advisers.php");
    exit();
}
