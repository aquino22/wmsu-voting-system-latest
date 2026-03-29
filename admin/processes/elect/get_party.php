<?php
header('Content-Type: application/json');
require '../../includes/conn.php'; // Database connection

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if (isset($_GET['id'])) {
    $partyId = $_GET['id'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM parties WHERE id = ?");
        $stmt->execute([$partyId]);
        $party = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($party) {
            $response = ['fetch_status' => 'success', 'party' => $party];
        } else {
            $response['message'] = 'Party not found.';
        }
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response);
