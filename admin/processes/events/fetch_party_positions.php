<?php
header('Content-Type: application/json');
require '../../includes/conn.php'; // Database connection
try {


    $election_name = $_GET['election_name'] ?? '';
    $type = $_GET['type'] ?? '';

    if (empty($election_name)) {
        throw new Exception("Election name is required");
    }

    $response = [];

    if ($type === 'parties') {
        $sql = "SELECT name FROM parties WHERE election_name = :election_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['election_name' => $election_name]);
        $response['parties'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($type === 'positions') {
        $sql = "SELECT p.id, p.name AS position_name, p.party, p.level, p.max_votes, p.created_at 
                FROM positions p 
                JOIN parties pa ON p.party = pa.name 
                WHERE pa.election_name = :election_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['election_name' => $election_name]);
        $response['positions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}