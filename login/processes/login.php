<?php
date_default_timezone_set('Asia/Manila');
session_start();
require_once '../includes/conn.php'; // Database connection

try {

    // Get ongoing election with connected academic year
    $sql = "SELECT 
                ay.year_label AS school_year,
                ay.semester,
				ay.custom_voter_option,
                e.start_period,
                e.end_period
            FROM elections e
            INNER JOIN academic_years ay 
                ON e.academic_year_id = ay.id
            WHERE e.status = 'Ongoing'
            LIMIT 1";

    $stmt = $pdo->query($sql);
    $electionData = $stmt->fetch(PDO::FETCH_ASSOC);

    $schoolYear        = $electionData['school_year'] ?? '';
    $semester          = $electionData['semester'] ?? '';
    $voterCustomFields = $electionData['custom_voter_option'] ?? '';
} catch (Exception $e) {
    echo $e->getMessage();
    error_log($e->getMessage());

    $electionData = [
        'school_year' => '',
        'semester' => '',
        'start_period' => '',
        'end_period' => ''
    ];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $location = trim($_POST['location'] ?? 'N/A'); // Get location from form, default to N/A

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please fill in all fields.";
        header("Location: ../index.php");
        exit();
    }

    // // --- RATE LIMITING CHECK ---
    // $ip_address = $_SERVER['REMOTE_ADDR'];
    // $checkLimit = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE (ip_address = ? OR email = ?) AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
    // $checkLimit->execute([$ip_address, $email]);
    // if ($checkLimit->fetchColumn() >= 5) {
    //     $_SESSION['error'] = "Too many failed login attempts. Please try again in 15 minutes.";
    //     header("Location: ../index.php");
    //     exit();
    // }
    // ---------------------------

    try {
        // Check user credentials
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Log failed attempt (User not found)
            $logFail = $pdo->prepare("INSERT INTO login_attempts (ip_address, email, attempt_time) VALUES (?, ?, NOW())");
            $logFail->execute([$ip_address, $email]);

            $_SESSION['error'] = "Email not found. Please check your credentials.";
            header("Location: ../index.php");
            exit();
        }

        // Check if account is active
        if ($user['is_active'] == 0) {
            $_SESSION['error'] = "Your account is inactive. If you are a student-voter, please ask your adviser to activate your account first. If you are a staff, please contact the administrator.";
            header("Location: ../index.php");
            exit();
        }

        $storedHash = $user['password'];

        // Compare plain text password directly
        if (password_verify($password, $storedHash)) {

            // Clear failed attempts on success
            $clearFail = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR email = ?");
            $clearFail->execute([$ip_address, $email]);

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $user_id = $user['id'];
            $_SESSION['STATUS'] = "LOGIN_SUCCESSFUL";

            // Gather data for user_activities
            $action = 'LOGIN';
            $timestamp = date('Y-m-d H:i:s'); // Current timestamp
            $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'; // Browser/Device info
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; // User's IP address

            $behavior_patterns = 'Successful login'; // Basic description

            // Log the login activity
            $activityStmt = $pdo->prepare("
                INSERT INTO user_activities (
                    user_id, action, timestamp, device_info, ip_address, location, behavior_patterns
                ) VALUES (
                    :user_id, :action, :timestamp, :device_info, :ip_address, :location, :behavior_patterns
                )
            ");
            $activityStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $activityStmt->bindParam(':action', $action, PDO::PARAM_STR);
            $activityStmt->bindParam(':timestamp', $timestamp, PDO::PARAM_STR);
            $activityStmt->bindParam(':device_info', $device_info, PDO::PARAM_STR);
            $activityStmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
            $activityStmt->bindParam(':location', $location, PDO::PARAM_STR);
            $activityStmt->bindParam(':behavior_patterns', $behavior_patterns, PDO::PARAM_STR);
            $activityStmt->execute();

            // Redirect based on role
            switch ($user['role']) {
                case "admin":
                    header("Location: ../../admin/index.php");
                    break;
                case "moderator":
                    $email = $user['email'];
                    $stmt = $pdo->prepare("SELECT status FROM moderators WHERE email = :email LIMIT 1");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    $moderator = $stmt->fetch(PDO::FETCH_ASSOC);
                    $status = $moderator ? $moderator['status'] : null;

                    if ($status == null) {
                        $_SESSION['error'] = "Account not activated by admin.";
                        header("Location: ../index.php");
                        exit();
                    } else {
                        header("Location: ../../moderator/index.php");
                    }
                    break;
                case "candidate":
                    header("Location: ../../candidate/index.php");
                    break;
                case "adviser":
                    $stmt = $pdo->prepare("SELECT * FROM advisers WHERE email = :email LIMIT 1");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    $adviser = $stmt->fetch(PDO::FETCH_ASSOC);
                    $_SESSION['email'] = $adviser['email'];
                    $_SESSION['user_id'] = $adviser['id'];
                    header("Location: ../../adviser/index.php");
                    break;
                case "voter":
                    $stmt = $pdo->prepare("SELECT * FROM voters WHERE email = :email LIMIT 1");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    $voter = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$voter) {
                        $_SESSION['error'] = "Voter record not found.";
                        header("Location: ../index.php");
                        exit();
                    }

                    // If voter needs to update info, force registration update
                    if ($voter['needs_update'] == 1) {
                        $_SESSION['STATUS'] = "ACCOUNT NEEDS UPDATE!";
                        $_SESSION['MESSAGE'] = "Your account needs to be updated!";

                        if ($voterCustomFields == 1) {
                            header("Location: ../register_custom.php");
                        }
                        header("Location: ../register.php");
                        exit();
                    }

                    // Normal voter login
                    $_SESSION['user_id'] = $voter['id'];
                    $_SESSION['email'] = $voter['email'];

                    header("Location: ../../voter/index.php");
                    exit();
            }
            exit();
        } else {
            // Log failed attempt (Incorrect password)
            $logFail = $pdo->prepare("INSERT INTO login_attempts (ip_address, email, attempt_time) VALUES (?, ?, NOW())");
            $logFail->execute([$ip_address, $email]);

            $_SESSION['error'] = "Incorrect password.";
            header("Location: ../index.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: ../index.php");
        exit();
    }
}
