<?php
require '../../includes/conn.php'; // Include database connection

header('Content-Type: application/json');

if (isset($_GET['college']) && isset($_GET['department'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, location, current_capacity
            FROM precincts 
            WHERE college = ? 
            AND department = ? 
            AND assignment_status = 'assigned'
        ");
        
        $stmt->execute([$_GET['college'], $_GET['department']]);
        $precincts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'precincts' => $precincts
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}