<?php
require_once '../../includes/conn.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT 
            c.id AS candidacy_id,
            c.election_id,
            e.election_name,
            c.start_period,
            c.end_period,
            c.status
        FROM candidacy c
        INNER JOIN elections e ON c.election_id = e.id
        WHERE c.status = 'Ongoing'
        ORDER BY c.start_period ASC
    ");
    $stmt->execute();
    $candidacies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($candidacies);
    exit;
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
    exit;
}
