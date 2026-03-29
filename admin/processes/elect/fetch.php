<?php
require '../../includes/conn.php'; // Include database connection

header('Content-Type: application/json'); // ✅ Ensure correct JSON response

try {
    $sql = "SELECT id, election_name, school_year_start, school_year_end, semester, 
                   DATE_FORMAT(start_period, '%Y-%m-%d') as start_period, 
                   DATE_FORMAT(end_period, '%Y-%m-%d') as end_period, 
                   status, 
                   DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i:%s') as updated_at
            FROM elections ORDER BY id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["data" => $elections]); // ✅ Wrap in "data" for DataTables
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
