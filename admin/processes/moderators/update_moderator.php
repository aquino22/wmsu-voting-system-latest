<?php
session_start();

require '../../includes/conn.php';

header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0); // silence on-screen errors
error_reporting(E_ALL);       // still logged via error_log()

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Sanitization functions
function sanitizeString($input, $maxLength)
{
    $input = trim($input);
    $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $input = substr($input, 0, $maxLength);
    return preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $input);
}

function sanitizeEmail($email)
{
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return strlen($email) <= 255 ? $email : '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    // CSRF validation
    $csrfTokenAge = 1800;
    if (
        !isset($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !isset($_SESSION['csrf_token_time']) ||
        $_POST['csrf_token'] !== $_SESSION['csrf_token'] ||
        (time() - $_SESSION['csrf_token_time']) > $csrfTokenAge
    ) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired CSRF token.']);
        exit;
    }

    // Rate limiting
    $attemptsKey = 'update_moderator_attempts';
    if (!isset($_SESSION[$attemptsKey])) {
        $_SESSION[$attemptsKey] = ['count' => 0, 'time' => time()];
    }
    if ((time() - $_SESSION[$attemptsKey]['time']) < 3600 && $_SESSION[$attemptsKey]['count'] >= 5) {
        echo json_encode(['success' => false, 'message' => 'Too many attempts. Try again later.']);
        exit;
    }
    $_SESSION[$attemptsKey]['count']++;

    $pdo->beginTransaction();

    // Required fields
    $required = ['id', 'name', 'email', 'gender', 'college', 'department'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception('All required fields must be filled');
        }
    }

    $maxLengths = [
        'name' => 100,
        'email' => 255,
        'gender' => 20,
        'college' => 100,
        'department' => 100,
      
    ];

    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $name = sanitizeString($_POST['name'], $maxLengths['name']);
    $email = sanitizeEmail($_POST['email']);
    $gender = sanitizeString($_POST['gender'], $maxLengths['gender']);
    $college = sanitizeString($_POST['college'], $maxLengths['college']);
    $department = sanitizeString($_POST['department'], $maxLengths['department']);
    $precincts = $_POST['precincts'] ?? []; // MULTIPLE CHECKBOXES
    $password = !empty($_POST['password']) ? trim($_POST['password']) : '';

    if ($id === false || $id <= 0) {
        throw new Exception('Invalid moderator ID');
    }

    // Verify moderator exists
    $checkModeratorStmt = $pdo->prepare("SELECT COUNT(*) FROM moderators WHERE id = ?");
    $checkModeratorStmt->execute([$id]);
    if ($checkModeratorStmt->fetchColumn() == 0) {
        throw new Exception('Moderator not found');
    }

    // Validate email and gender
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    if (!in_array($gender, ['Male', 'Female', 'Other'])) {
        throw new Exception('Invalid gender value');
    }

    // Validate precincts exist
    if (!empty($precincts)) {
        $placeholders = str_repeat('?,', count($precincts)-1) . '?';
        $stmt = $pdo->prepare("SELECT name FROM precincts WHERE name IN ($placeholders)");
        $stmt->execute($precincts);
        $found = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (count($found) != count($precincts)) {
            throw new Exception('One or more selected precincts are invalid');
        }
    }

    // Check for duplicate email/name
    $checkEmailStmt = $pdo->prepare("SELECT COUNT(*) FROM moderators WHERE email = ? AND id != ?");
    $checkEmailStmt->execute([$email, $id]);
    if ($checkEmailStmt->fetchColumn() > 0) throw new Exception('Email address already exists');

    $checkNameStmt = $pdo->prepare("SELECT COUNT(*) FROM moderators WHERE name = ? AND id != ?");
    $checkNameStmt->execute([$name, $id]);
    if ($checkNameStmt->fetchColumn() > 0) throw new Exception('Name already exists');

    // Password validation
    if (!empty($password)) {
        if (
            strlen($password) < 12 ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[^A-Za-z0-9]/', $password)
        ) {
            throw new Exception('Password must be at least 12 characters long and contain uppercase, lowercase, number, and special character');
        }
        error_log("Warning: Password for moderator ID $id stored in plain text.");
    }

    // Reset old precincts
    $stmt = $pdo->prepare("SELECT precinct FROM moderators WHERE id=?");
    $stmt->execute([$id]);
    $oldPrecincts = json_decode($stmt->fetchColumn(), true) ?: [];
    if (!empty($oldPrecincts)) {
        $placeholders = str_repeat('?,', count($oldPrecincts)-1) . '?';
        $pdo->prepare("UPDATE precincts SET assignment_status='unassigned' WHERE name IN ($placeholders)")
            ->execute($oldPrecincts);
    }

    // Encode new precincts as JSON
    $precinctJson = !empty($precincts) ? json_encode(array_map('trim', $precincts)) : null;

    // Update moderators
    $sql = "UPDATE moderators SET name = ?, email = ?, gender = ?, college = ?, department = ?, precinct = ?";
    $params = [$name, $email, $gender, $college, $department, $precinctJson];

    if (!empty($password)) {
        $sql .= ", password = ?";
        $params[] = $password;
    }
    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute($params);

    if ($success && $stmt->rowCount() > 0) {
        // Sync users table
        $syncQuery = "UPDATE users SET email = ?";
        $syncParams = [$email];
        if (!empty($password)) {
            $syncQuery .= ", password = ?";
            $syncParams[] = $password;
        }
        $syncQuery .= " WHERE moderator_id = ? AND role = 'moderator'";
        $syncParams[] = $id;

        $syncStmt = $pdo->prepare($syncQuery);
        if (!$syncStmt->execute($syncParams)) throw new Exception('Failed to execute users table update');
        if ($syncStmt->rowCount() === 0) throw new Exception('No matching user found in users table');

        // Log activity
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $action = 'UPDATE_MODERATOR';
            $timestamp = date('Y-m-d H:i:s');
            $device_info = sanitizeString($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 255);
            $ip_address = filter_var($_SERVER['REMOTE_ADDR'] ?? 'Unknown', FILTER_VALIDATE_IP) ?: 'Unknown';
            $location = 'N/A';
            $behavior_patterns = sanitizeString(
                "Updated moderator: ID $id, Name: $name, Email: $email, Precincts: " . implode(', ', $precincts),
                500
            );

            $activityStmt = $pdo->prepare("
                INSERT INTO user_activities (
                    user_id, action, timestamp, device_info, ip_address, location, behavior_patterns
                ) VALUES (
                    :user_id, :action, :timestamp, :device_info, :ip_address, :location, :behavior_patterns
                )
            ");
            $activityStmt->execute([
                ':user_id' => $user_id,
                ':action' => $action,
                ':timestamp' => $timestamp,
                ':device_info' => $device_info,
                ':ip_address' => $ip_address,
                ':location' => $location,
                ':behavior_patterns' => $behavior_patterns
            ]);
        }

        // Assign new precincts
        if (!empty($precincts)) {
            $stmt = $pdo->prepare("UPDATE precincts SET assignment_status='assigned', updated_at=NOW() WHERE name=?");
            foreach ($precincts as $p) {
                $stmt->execute([$p]);
            }
        }

        $pdo->commit();

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
        $_SESSION[$attemptsKey] = ['count' => 0, 'time' => time()];

        echo json_encode(['success' => true, 'message' => 'Moderator updated successfully']);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'No changes made or moderator not found']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Database error in edit_moderator.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error in edit_moderator.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => htmlspecialchars($e->getMessage())]);
} finally {
    $pdo = null;
}
