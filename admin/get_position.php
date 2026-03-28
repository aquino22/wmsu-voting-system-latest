<?php
require '../../includes/conn.php'; // Include your database connection

if (isset($_GET['id'])) {
    $positionId = $_GET['id'];

    try {
        // Fetch position details from the database
        $query = "SELECT p.*, p.allowed_colleges, p.allowed_departments,
                         e.election_name,
                         ay.year_label AS school_year, ay.semester
                  FROM positions p
                  LEFT JOIN elections      e  ON e.id  = p.election_id
                  LEFT JOIN academic_years ay ON ay.id = e.academic_year_id
                  WHERE p.id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $positionId);
        $stmt->execute();
        $position = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($position) {
            // Ensure restriction columns are never null so JS JSON.parse never throws
            $position['allowed_colleges']    = $position['allowed_colleges']    ?? '[]';
            $position['allowed_departments'] = $position['allowed_departments'] ?? '[]';
            echo json_encode(['status' => 'success', 'position' => $position]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Position not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
