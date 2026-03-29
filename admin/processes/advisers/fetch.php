<?php
session_start();
require '../../includes/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $id = (int)$_POST['id'];

        $stmt = $pdo->prepare("
            SELECT 
                a.id,
                a.full_name,
                a.first_name,
                a.middle_name,
                a.last_name,
                a.email,
                a.college,
                a.department,
                a.wmsu_campus,
                a.external_campus,
                a.year AS year_level,
                GROUP_CONCAT(e.id) AS email_ids
            FROM advisers a
            LEFT JOIN email e ON e.adviser_id = a.id
            WHERE a.id = ?
            GROUP BY a.id
        ");

        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $data['email_ids'] = $data['email_ids'] ? explode(',', $data['email_ids']) : [];
            echo json_encode([
                'status' => 'success',
                'data' => $data
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Adviser not found'
            ]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }

} else {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request: adviser ID is missing'
    ]);
}
