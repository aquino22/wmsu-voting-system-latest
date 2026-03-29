<?php
require 'includes/conn.php';

$sql = "SELECT 
    a.id,
    a.full_name,
    a.first_name,
    a.middle_name,
    a.last_name,
    a.email,
    '' AS password,
    a.college,
    a.department,
    a.year,
    a.wmsu_campus,
    a.external_campus,
    a.school_year,
    a.semester,
    u.is_active,
    CONCAT('<ul>', GROUP_CONCAT(CONCAT('<li>', e.email, ' (', e.capacity, '/500)</li>') ORDER BY e.id SEPARATOR ''), '</ul>') AS smtp_email,
    GROUP_CONCAT(e.id) AS selected_email_ids
FROM advisers a
LEFT JOIN email e ON e.adviser_id = a.id
LEFT JOIN users u ON u.email = a.email
GROUP BY a.id";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert CSV string of IDs into array
foreach ($data as &$row) {
    $row['selected_email_ids'] = $row['selected_email_ids'] ? explode(',', $row['selected_email_ids']) : [];
    // Ensure is_active is set (default to 0 if not found in users table)
    $row['is_active'] = isset($row['is_active']) ? (int)$row['is_active'] : 0;
}

echo json_encode(["data" => $data]);
?>