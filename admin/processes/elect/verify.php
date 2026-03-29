<?php
require '../../includes/conn.php'; // Database connection

if (isset($_GET['id'])) {
    $partyId = $_GET['id'];

    try {
        // Fetch current status
        $query = "SELECT status FROM parties WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $partyId);
        $stmt->execute();
        $party = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($party) {
         

            // Update status in DB
            $updateQuery = "UPDATE parties SET status = :status WHERE id = :id";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->bindParam(':status', $newStatus);
            $updateStmt->bindParam(':id', $partyId);

            if ($updateStmt->execute()) {
                // Redirect back to the referring page
                $referrer = $_SERVER['HTTP_REFERER'] ?? '../../dashboard.php';
                header("Location: $referrer?success=Party status updated");
                exit();
            } else {
                $referrer = $_SERVER['HTTP_REFERER'] ?? '../../dashboard.php';
                header("Location: $referrer?error=Failed to update party status");
                exit();
            }
        } else {
            $referrer = $_SERVER['HTTP_REFERER'] ?? '../../dashboard.php';
            header("Location: $referrer?error=Party not found");
            exit();
        }
    } catch (PDOException $e) {
        $referrer = $_SERVER['HTTP_REFERER'] ?? '../../dashboard.php';
        header("Location: $referrer?error=Database error: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    $referrer = $_SERVER['HTTP_REFERER'] ?? '../../dashboard.php';
    header("Location: $referrer?error=Invalid request");
    exit();
}
?>
