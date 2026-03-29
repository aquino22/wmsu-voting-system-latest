<?php
require '../../includes/conn.php'; // Include database connection

if (isset($_GET['id'])) {
    $id = $_GET['id']; // Get the election ID from the request

    try {
        $sql = "SELECT id, election_name, school_year_start, school_year_end, semester, start_period, end_period, status 
                FROM elections WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $election = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($election) {
            echo json_encode($election); // Return the election data as JSON
        } else {
            echo json_encode(["error" => "Election not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "No ID provided"]);
}
?>
