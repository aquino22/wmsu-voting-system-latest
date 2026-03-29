<?php
require '../../includes/conn.php';   // DB connection

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $id = $_POST["id"];

        /* 1. Begin transaction */
        $pdo->beginTransaction();

        /* 2. Confirm no voting period is currently STARTED */
        $voting_check = $pdo->prepare("
            SELECT COUNT(*)
            FROM voting_periods
            WHERE status = 'STARTED'
        ");
        $voting_check->execute();
        if ($voting_check->fetchColumn() > 0) {
            echo json_encode([
                "success" => false,
                "message" => "Cannot delete precinct while voting has Started"
            ]);
            exit;
        }

        /* 3. Get precinct name from its id */
        $precincts_stmt = $pdo->prepare("SELECT name FROM precincts WHERE id = ?");
        $precincts_stmt->execute([$id]);
        $precinct = $precincts_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$precinct) {
            throw new Exception("Precinct not found");
        }
        $precinct_name = $precinct['name'];

        /* 4. Delete related precinct_voters records */
        $pdo->prepare("
            DELETE FROM precinct_voters
            WHERE precinct = ?
        ")->execute([$precinct_name]);

        /* 5. Delete related precinct_elections records */
        $pdo->prepare("
            DELETE FROM precinct_elections
            WHERE precinct_name = ?
        ")->execute([$precinct_name]);

        /* 6.  ─────────── NEW: clear precinct for affected moderators ─────────── */
        $pdo->prepare("
            UPDATE moderators
            SET precinct = NULL 
            WHERE precinct = ?
        ")->execute([$precinct_name]);
        /* --------------------------------------------------------------------- */

        /* 7. Finally delete the precinct itself */
        $pdo->prepare("
            DELETE FROM precincts
            WHERE id = ?
        ")->execute([$id]);

        /* 8. Commit the whole transaction */
        $pdo->commit();

        echo json_encode([
            "success" => true,
            "message" => "Precinct and related records deleted successfully"
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            "success" => false,
            "message" => "Error deleting precinct: " . $e->getMessage()
        ]);
    }
}
?>
