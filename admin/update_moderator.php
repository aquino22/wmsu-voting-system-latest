<?php
session_start();
require 'includes/conn.php';

header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ---------------- Sanitization ----------------
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

// ---------------- Validate Method ----------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // ---------------- Required Fields ----------------
    $required = ['id', 'name', 'email', 'gender', 'college', 'department', 'prev_email'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception('All required fields must be filled');
        }
    }

   

    $precinct = intval($_POST['precinct']);

    // ---------------- Sanitize ----------------
    $maxLengths = [
        'name' => 100,
        'email' => 255,
        'gender' => 20,
        'college' => 100,
        'department' => 100
    ];

    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $name = sanitizeString($_POST['name'], $maxLengths['name']);
    $email = sanitizeEmail($_POST['email']);
    $prevEmail = sanitizeEmail($_POST['prev_email']);
    $gender = sanitizeString($_POST['gender'], $maxLengths['gender']);
    $college = sanitizeString($_POST['college'], $maxLengths['college']);
    $department = sanitizeString($_POST['department'], $maxLengths['department']);
    $password = !empty($_POST['password']) ? $_POST['password'] : '';

    if ($id === false || $id <= 0) {
        throw new Exception('Invalid moderator ID');
    }

    // ---------------- Verify Moderator Exists ----------------
    $stmt = $pdo->prepare("SELECT precinct FROM moderators WHERE id = ?");
    $stmt->execute([$id]);
    $oldPrecinct = $stmt->fetchColumn();

    // ---------------- Validate Email ----------------
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    if (!in_array($gender, ['Male', 'Female', 'Other'])) {
        throw new Exception('Invalid gender');
    }

    // ---------------- Check Duplicate Email / Name ----------------
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM moderators WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetchColumn() > 0) throw new Exception('Email address already exists');

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM moderators WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    if ($stmt->fetchColumn() > 0) throw new Exception('Name already exists');

    // ---------------- Check Duplicate Moderator for Same College + Department ----------------
    $checkMod = $pdo->prepare("
    SELECT COUNT(*) 
    FROM moderators 
    WHERE college = ? AND department = ? AND id != ?
");
    $checkMod->execute([$college, $department, $id]);

    // if ($checkMod->fetchColumn() > 0) {
    //     throw new Exception("A moderator already exists for this college and department");
    // }

    // ---------------- Validate Precinct Exists ----------------
    $stmt = $pdo->prepare("SELECT assignment_status FROM precincts WHERE id = ?");
    $stmt->execute([$precinct]);
    $precinctRow = $stmt->fetch(PDO::FETCH_ASSOC);

 
    // ---------------- Check if Precinct Already Assigned ----------------
    if ($precinctRow['assignment_status'] === 'assigned' && intval($oldPrecinct) !== $precinct) {
        throw new Exception("Selected precinct is already assigned to another moderator");
    }

    // ---------------- Password ----------------
    $hashedPassword = null;
    if (!empty($password)) {
        if (
            strlen($password) < 12 ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[^A-Za-z0-9]/', $password)
        ) {
            throw new Exception('Password must be at least 12 chars with upper, lower, number & special char');
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    }

    // ---------------- Unassign Old Precinct ----------------
    if (!empty($oldPrecinct)) {
        $pdo->prepare("UPDATE precincts SET assignment_status = 'unassigned' WHERE id = ?")
            ->execute([intval($oldPrecinct)]);
    }

    // ---------------- Assign New Precinct ----------------
    $pdo->prepare("UPDATE precincts SET assignment_status = 'assigned' WHERE id = ?")
        ->execute([$precinct]);

    // ---------------- Update Moderators Table ----------------
    $sql = "UPDATE moderators 
            SET name = ?, email = ?, gender = ?, college = ?, department = ?, precinct = ?";
    $params = [$name, $email, $gender, $college, $department, $precinct];

    if (!empty($hashedPassword)) {
        $sql .= ", password = ?";
        $params[] = $hashedPassword;
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // ---------------- Update Users Table ----------------
    $sqlUsers = "UPDATE users SET email = ?";
    $paramsUsers = [$email];

    if (!empty($hashedPassword)) {
        $sqlUsers .= ", password = ?";
        $paramsUsers[] = $hashedPassword;
    }

    $sqlUsers .= " WHERE email = ?";
    $paramsUsers[] = $prevEmail;

    $pdo->prepare($sqlUsers)->execute($paramsUsers);

    // ---------------- Activity Log ----------------
    if ($stmt->rowCount() > 0 && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $stmtAct = $pdo->prepare("
            INSERT INTO user_activities (user_id, action, timestamp, device_info, ip_address, location, behavior_patterns)
            VALUES (:user_id, 'UPDATE_MODERATOR', NOW(), :device_info, :ip_address, 'N/A', :behavior)
        ");
        $stmtAct->execute([
            ':user_id' => $user_id,
            ':device_info' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            ':behavior' => "Updated moderator $id - precinct $precinct"
        ]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Moderator updated successfully']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error in update_moderator.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $pdo = null;
}
