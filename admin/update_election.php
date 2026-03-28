<?php
require_once 'includes/conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['election_id'];
    $election_name = $_POST['election_name'];
    $semester = $_POST['semester'];
    $start_period = $_POST['start_period'];
    $end_period = $_POST['end_period'];
    $status = $_POST['status'];
    
    try {
        $query = "UPDATE elections SET 
                    election_name = :election_name,
                    start_period = :start_period,
                    end_period = :end_period,
                    status = :status,
                    updated_at = NOW()
                  WHERE id = :id";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':election_name', $election_name, PDO::PARAM_STR);
        $stmt->bindParam(':semester', $semester, PDO::PARAM_STR); // Changed to PARAM_STR since semester is text
        $stmt->bindParam(':start_period', $start_period, PDO::PARAM_STR);
        $stmt->bindParam(':end_period', $end_period, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Election updated successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update election."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
}
?>