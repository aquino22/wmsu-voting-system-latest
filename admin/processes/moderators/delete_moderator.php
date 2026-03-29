<?php
require '../../includes/conn.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT precinct, email FROM moderators WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $moderator = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($moderator) {

            // FIX: Normalize precinct into array
            $precinctValue = $moderator['precinct'];
            $precincts = [];

            if (!empty($precinctValue)) {
                $decoded = json_decode($precinctValue, true);
                $precincts = is_array($decoded) ? $decoded : [$precinctValue];
            }

            $email = $moderator['email'];

            // 1. Unassign all precincts
            if (!empty($precincts)) {
                $updateStmt = $pdo->prepare(
                    "UPDATE precincts SET assignment_status = 'unassigned', updated_at = NOW() WHERE id = ?"
                );

                foreach ($precincts as $precinct) {
                    $updateStmt->execute([trim($precinct)]);
                }
            }

            // 2. Delete user
            if (!empty($email)) {
                $deleteUserStmt = $pdo->prepare("DELETE FROM users WHERE email = :email");
                $deleteUserStmt->bindParam(':email', $email, PDO::PARAM_STR);
                $deleteUserStmt->execute();
            }

            // 3. Delete moderator
            $deleteModeratorStmt = $pdo->prepare("DELETE FROM moderators WHERE id = :id");
            $deleteModeratorStmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($deleteModeratorStmt->execute()) {
                $pdo->commit();
                echo json_encode(["success" => true]);
            } else {
                $pdo->rollBack();
                echo json_encode(["success" => false, "message" => "Failed to delete moderator."]);
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in delete_moderator.php: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "An error occurred: " . htmlspecialchars($e->getMessage())]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
