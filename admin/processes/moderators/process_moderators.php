<?php
// Secure session configuration
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true, // Ensure HTTPS
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true
]);

require '../../includes/conn.php';

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

ini_set('display_errors', 0);
error_reporting(E_ALL);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Sanitizers
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

// Authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

try {

    $pdo->beginTransaction();

    // Required fields
    $required = ['name', 'email', 'password', 'gender', 'college', 'department'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("Missing or empty field: $field");
        }
    }

    // Max lengths
    $max = [
        'name' => 100,
        'email' => 255,
        'gender' => 20,
        'college' => 100,
        'department' => 100
    ];

    // Sanitize user inputs
    $name = sanitizeString($_POST['name'], $max['name']);
    $email = sanitizeEmail($_POST['email']);
    $passwordPlain = $_POST['password'];
    $password = password_hash($passwordPlain, PASSWORD_DEFAULT);
    $gender = sanitizeString($_POST['gender'], $max['gender']);
    $college = sanitizeString($_POST['college'], $max['college']);
    $department = sanitizeString($_POST['department'], $max['department']);

    // Precinct (single ID only)
    $precinct = isset($_POST['precinct']) ? intval($_POST['precinct']) : 0;

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate gender
    if (!in_array($gender, ['Male', 'Female', 'Other'])) {
        throw new Exception('Invalid gender');
    }

    // Check duplicate email
    $checkEmail = $pdo->prepare("SELECT COUNT(*) FROM moderators WHERE email = ?");
    $checkEmail->execute([$email]);
    if ($checkEmail->fetchColumn() > 0) {
        throw new Exception('Email address already exists');
    }

    // Check duplicate name
    $checkName = $pdo->prepare("SELECT COUNT(*) FROM moderators WHERE name = ?");
    $checkName->execute([$name]);
    if ($checkName->fetchColumn() > 0) {
        throw new Exception('Name already exists');
    }

   // Check duplicate moderator based on same college + department
$checkMod = $pdo->prepare("
    SELECT COUNT(*) 
    FROM moderators 
    WHERE college = ? AND department = ?
");
$checkMod->execute([$college, $department]);

// if ($checkMod->fetchColumn() > 0) {
//     throw new Exception('A moderator already exists for this college and department');
// }

    // Validate precinct (must be unassigned)
    $checkPrecinct = $pdo->prepare("
        SELECT COUNT(*) 
        FROM precincts 
        WHERE id = ? AND assignment_status = 'unassigned'
    ");
    $checkPrecinct->execute([$precinct]);

   

    // Insert moderator (precinct stored as SINGLE ID)
    $stmt = $pdo->prepare("
        INSERT INTO moderators (
            name, email, password, gender, college, department, precinct, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $name,
        $email,
        $password,
        $gender,
        $college,
        $department,
        $precinct
    ]);

    // Insert into users table
    $userStmt = $pdo->prepare("
        INSERT INTO users (email, password, role, is_active)
        VALUES (?, ?, 'moderator', 1)
    ");
    $userStmt->execute([$email, $password]);

    // Assign precinct
    $updatePrecinct = $pdo->prepare("
        UPDATE precincts 
        SET assignment_status = 'assigned', updated_at = NOW()
        WHERE id = ?
    ");
    $updatePrecinct->execute([$precinct]);

    // Log activity
    if (isset($_SESSION['user_id'])) {

        $activity = $pdo->prepare("
            INSERT INTO user_activities (
                user_id, action, timestamp, device_info, ip_address, location, behavior_patterns
            ) VALUES (?, ?, NOW(), ?, ?, ?, ?)
        ");

        $activity->execute([
            $_SESSION['user_id'],
            'ADD_MODERATOR',
            sanitizeString($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 255),
            filter_var($_SERVER['REMOTE_ADDR'] ?? 'Unknown', FILTER_VALIDATE_IP) ?: 'Unknown',
            'N/A',
            sanitizeString("Added moderator: $name, Email: $email, Precinct ID: $precinct", 500)
        ]);
    }

    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Moderator added, user created, and precinct assigned successfully']);
} catch (Exception $e) {

    if ($pdo->inTransaction()) $pdo->rollBack();

    error_log("Error in add_moderator.php: " . $e->getMessage());

    echo json_encode(['status' => 'error', 'message' => htmlspecialchars($e->getMessage())]);
} finally {
    $pdo = null;
}
