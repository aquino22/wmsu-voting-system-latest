<?php
require '../../includes/conn.php';

header('Content-Type: application/json');

$adviser_id = $_POST['adviser_id'] ?? 0;

$stmt = $pdo->prepare("SELECT 
    a.id, a.first_name, a.middle_name, a.last_name, a.email, a.year_level, a.semester, a.school_year,
    a.college_id, a.department_id, a.major_id, a.wmsu_campus_id, a.external_campus_id,
    GROUP_CONCAT(e.id) AS selected_email_ids
FROM advisers a
LEFT JOIN email e ON e.adviser_id = a.id
WHERE a.id = ?
GROUP BY a.id
LIMIT 1");

$stmt->execute([$adviser_id]);
$adviser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($adviser) {
    $adviser['selected_email_ids'] = $adviser['selected_email_ids'] ? explode(',', $adviser['selected_email_ids']) : [];
}

echo json_encode($adviser);
