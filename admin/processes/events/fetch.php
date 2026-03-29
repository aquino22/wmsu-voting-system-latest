<?php
require '../../includes/conn.php'; // Database connection

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT *  FROM candidacy");
    $stmt->execute();
    $candidacies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($candidacies);
} catch (PDOException $e) {
    echo json_encode(["error" => "Failed to fetch candidacy events"]);
}
?>
