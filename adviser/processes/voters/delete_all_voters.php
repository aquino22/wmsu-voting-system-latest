<?php
require '../../includes/conn.php'; // Adjust path to your PDO connection file

header('Content-Type: application/json'); // Ensure JSON response

// Check if college and department are provided
if (!isset($_POST['college']) || !isset($_POST['department']) || !isset($_POST['year_level'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'College and department are required.'
    ]);
    exit;
}

$college = trim($_POST['college']);
$department = trim($_POST['department']);
$year_level = $_POST['year_level'];

try {
    // Begin transaction to ensure atomicity
    $pdo->beginTransaction();

    // Step 1: Delete from 'users' where role = 'voter' and email matches voters with specified college and department
    $userStmt = $pdo->prepare("
        DELETE u FROM users u
        INNER JOIN voters v ON u.email = v.email
        WHERE u.role = 'voter'
        AND LOWER(v.college) = LOWER(?)
        AND LOWER(v.department) = LOWER(?)
        AND year_level = ?
    ");
    $userStmt->execute([$college, $department, $year_level]);
    $usersDeleted = $userStmt->rowCount();

    // Step 2: Delete from 'voters' where college and department match
    $voterStmt = $pdo->prepare("
        DELETE FROM voters
        WHERE LOWER(college) = LOWER(?)
        AND LOWER(department) = LOWER(?)
        AND year_level = ?
    ");
    $voterStmt->execute([$college, $department, $year_level]);
    $votersDeleted = $voterStmt->rowCount();

    // Commit transaction
    $pdo->commit();

    // Prepare response
    $response = [
        'status' => 'success',
        'message' => "Deleted $usersDeleted users and $votersDeleted voters successfully for college '$college' and department '$department'."
    ];
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    $response = [
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ];
    error_log("Delete Voters by Department Error: " . $e->getMessage());
}

echo json_encode($response);
exit;
?>