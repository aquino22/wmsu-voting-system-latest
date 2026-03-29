<?php
require '../../includes/conn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        if (!isset($_POST['id']) || empty($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'Moderator ID is required']);
            exit;
        }

        // First get the email and current status from moderators table
        $getModeratorStmt = $pdo->prepare("SELECT email, status FROM moderators WHERE id = ?");
        $getModeratorStmt->execute([$_POST['id']]);
        $moderator = $getModeratorStmt->fetch(PDO::FETCH_ASSOC);

        if (!$moderator) {
            echo json_encode(['success' => false, 'message' => 'Moderator not found']);
            exit;
        }

        $userEmail = $moderator['email'];
        $currentModeratorStatus = $moderator['status'];

        // Get current is_active status from users table
        $checkStmt = $pdo->prepare("SELECT is_active FROM users WHERE email = ?");
        $checkStmt->execute([$userEmail]);
        $currentUserStatus = $checkStmt->fetchColumn();

        if ($currentUserStatus === false) {
            echo json_encode(['success' => false, 'message' => 'User account not found']);
            exit;
        }

        // Toggle statuses (assuming is_active is 1/0 and status is 'active'/'inactive')
        $newUserStatus = $currentUserStatus ? 0 : 1;
        $newModeratorStatus = $currentModeratorStatus === 'active' ? 'inactive' : 'active';

        // Update status in users table
        $updateUserStmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE email = ?");
        $userUpdateSuccess = $updateUserStmt->execute([$newUserStatus, $userEmail]);

        // Update status in moderators table
        $updateModeratorStmt = $pdo->prepare("UPDATE moderators SET status = ?, updated_at = NOW() WHERE id = ?");
        $moderatorUpdateSuccess = $updateModeratorStmt->execute([$newModeratorStatus, $_POST['id']]);

        if ($userUpdateSuccess && $moderatorUpdateSuccess) {
            $pdo->commit();
            echo json_encode([
                'success' => true, 
                'message' => "Status updated successfully",
                'user_status' => $newUserStatus ? 'active' : 'inactive',
                'moderator_status' => $newModeratorStatus
            ]);
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>