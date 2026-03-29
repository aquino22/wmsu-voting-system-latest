<?php
session_start();
require '../../includes/conn.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['adviser_id'])) {

    $id              = (int) $_POST['adviser_id'];
    $first_name      = trim($_POST['firstName'] ?? '');
    $middle_name     = trim($_POST['middleName'] ?? '');
    $last_name       = trim($_POST['lastName'] ?? '');
    $email           = trim($_POST['user_email'] ?? '');
    $prev_email      = trim($_POST['prev_email'] ?? '');
    $password        = trim($_POST['password'] ?? '');

    $college         = $_POST['college_id'] ?? null;
    $department      = $_POST['department_id'] ?? null;
    $major           = $_POST['major_id'] ?? null;
    $wmsu_campus     = $_POST['wmsu_campus_id'] ?? null;
    $external_campus = $_POST['external_campus_id'] ?? null;
    $year_level      = $_POST['year_level'] ?? null;
    $semester        = $_POST['semester'] ?? null;

    $emailIds        = $_POST['email_ids'] ?? [];

    $fullName = trim("$first_name $middle_name $last_name");

    if (empty($email)) {
        $_SESSION['STATUS'] = "MISSING_REQUIRED_FIELDS";
        header("Location: ../../advisers.php");
        exit();
    }

    try {

        $pdo->beginTransaction();

        /*
        ---------------------------
        GET USER ID
        ---------------------------
        */
        $getUserIdStmt = $pdo->prepare("
            SELECT id FROM users 
            WHERE role='adviser' AND email=?
        ");
        $getUserIdStmt->execute([$prev_email]);

        $userId = $getUserIdStmt->fetchColumn();

        /*
        ---------------------------
        GET PREVIOUS ADVISER DATA
        ---------------------------
        */
        $prev = $pdo->prepare("
            SELECT college_id, department_id, major_id, wmsu_campus_id, external_campus_id, year_level, semester, school_year
            FROM advisers 
            WHERE id = ?
        ");
        $prev->execute([$id]);
        $prevData = $prev->fetch(PDO::FETCH_ASSOC);

        /*
        ---------------------------
        GET CURRENT ACADEMIC YEAR
        ---------------------------
        */
        $currentAY = $pdo->query("
            SELECT id FROM academic_years
            WHERE status='Ongoing'
            LIMIT 1
        ")->fetchColumn();

        if (!$currentAY) {
            throw new Exception("No ongoing academic year found");
        }

        /*
        ---------------------------
        ARCHIVE VOTERS IF CHANGED
        ---------------------------
        */
        if ($prevData) {

            $deptChanged = $prevData['department_id'] != $department;
            $yearChanged = $prevData['year_level'] != $year_level;

            if ($deptChanged || $yearChanged) {

                $archive = $pdo->prepare("
    INSERT INTO voters_copy_adviser (
        adviser_id,
        student_id,
        email,
        password,
        first_name,
        middle_name,
        last_name,

        course_old,
        major_old,
        college_old,
        department_old,
        wmsu_campus_old,
        external_campus_old,

        course_id,
        major_id,
        college_id,
        department_id,
        wmsu_campus_id,
        external_campus_id,

        year_level,
        first_cor,
        second_cor,
        activation_token,
        activation_expiry,
        is_active,
        status,
        academic_year_id
    )
    SELECT
        ?,
        student_id,
        email,
        password,
        first_name,
        middle_name,
        last_name,

        course,
        major,
        college,
        department,
        wmsu_campus,
        external_campus,

        course_id,
        major_id,
        college_id,
        department_id,
        wmsu_campus_id,
        external_campus_id,

        year_level,
        first_cor,
        second_cor,
        activation_token,
        activation_expiry,
        is_active,
        status,
        academic_year_id
    FROM voters
    WHERE college_id = ?
    AND department_id = ?
    AND year_level = ?
    AND academic_year_id = ?
");

                $archive->execute([
                    $id,
                    $prevData['college_id'],
                    $prevData['department_id'],
                    $prevData['year_level'],
                    $currentAY
                ]);

                $pdo->prepare("
                    UPDATE advisers 
                    SET has_changed = 1 
                    WHERE id = ?
                ")->execute([$id]);
            }
        }

        /*
        ---------------------------
        PASSWORD HANDLING
        ---------------------------
        */
        $hashedPassword = !empty($password)
            ? password_hash($password, PASSWORD_DEFAULT)
            : null;

        /*
        ---------------------------
        UPDATE ADVISER
        ---------------------------
        */
        $adviserSql = "
         UPDATE advisers SET
    first_name = ?,
    middle_name = ?,
    last_name = ?,
    full_name = ?,
    email = ?,
    college_id = ?,
    department_id = ?,
    major_id = ?,
    wmsu_campus_id = ?,
    external_campus_id = ?,
    year_level = ?,
    semester = ?
        ";
        $params = [
            $first_name,
            $middle_name,
            $last_name,
            $fullName,
            $email,
            $college,
            $department,
            $major ?: null,
            $wmsu_campus,
            $external_campus ?: null,
            $year_level,
            $semester
        ];

        if ($hashedPassword) {
            $adviserSql .= ", password = ?";
            $params[] = $hashedPassword;
        }

        $adviserSql .= " WHERE id = ?";
        $params[] = $id;

        $pdo->prepare($adviserSql)->execute($params);

        /*
        ---------------------------
        UPDATE USERS TABLE
        ---------------------------
        */
        if ($userId) {

            $userSql = "UPDATE users SET email=?";
            $userParams = [$email];

            if ($hashedPassword) {
                $userSql .= ", password=?";
                $userParams[] = $hashedPassword;
            }

            $userSql .= " WHERE id=? AND role='adviser'";
            $userParams[] = $userId;

            $pdo->prepare($userSql)->execute($userParams);
        }

        /*
        ---------------------------
        RESET SMTP
        ---------------------------
        */
        $pdo->prepare("
            UPDATE email
            SET status='available', adviser_id=NULL
            WHERE adviser_id=?
        ")->execute([$id]);

        /*
        ---------------------------
        ASSIGN SMTP
        ---------------------------
        */
        if (!empty($emailIds) && is_array($emailIds)) {

            $assign = $pdo->prepare("
                UPDATE email
                SET status='taken', adviser_id=?
                WHERE id=?
            ");

            foreach ($emailIds as $smtpId) {
                if (is_numeric($smtpId)) {
                    $assign->execute([$id, $smtpId]);
                }
            }
        }

        $pdo->commit();

        $_SESSION['STATUS'] = "ADVISER_UPDATED_SUCCESSFULLY";
    } catch (Exception $e) {
        echo $e->getMessage();
        $pdo->rollBack();

        error_log("Adviser update failed: " . $e->getMessage());

        $_SESSION['STATUS'] = "ADVISER_UPDATE_FAILED";
    }

    header("Location: ../../advisers.php");
    exit();
}
