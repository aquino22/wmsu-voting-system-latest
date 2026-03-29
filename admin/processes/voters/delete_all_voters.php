<?php
require '../../includes/conn.php'; // Adjust path to your PDO connection file

header('Content-Type: application/json'); // Ensure JSON response

try {
    // Begin transaction to ensure atomicity
    $pdo->beginTransaction();

    // Step 1: Delete from 'users' where role = 'voter'
    $userStmt = $pdo->prepare("DELETE FROM users WHERE role = 'voter'");
    $userStmt->execute();
    $usersDeleted = $userStmt->rowCount();

    // Step 2: Delete all from 'voters' table
    $voterStmt = $pdo->prepare("DELETE FROM voters");
    $voterStmt->execute();
    $votersDeleted = $voterStmt->rowCount();

    // Commit transaction
    $pdo->commit();

    // Prepare response
    $response = [
        'status' => 'success',
        'message' => "Deleted $usersDeleted users and $votersDeleted voters successfully."
    ];
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    $response = [
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ];
    error_log("Delete All Voters Error: " . $e->getMessage());
}

echo json_encode($response);
exit;
?>