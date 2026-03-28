<?php
require 'includes/conn.php';

$sql = "
SELECT 
    a.id,
    a.full_name,
    a.first_name,
    a.middle_name,
    a.last_name,
    a.email,
    '' AS password,
    c.college_name AS college,
    d.department_name AS department,
    m.major_name AS major,
    
    -- The actual year level integer (1, 2, 3, 4)
    ayl.year_level AS year_level_display, 

    wc.campus_name AS wmsu_campus,
    ec.campus_name AS external_campus,
    a.school_year,
    a.semester,
    u.is_active,

    CONCAT(
        '<ul>',
        IFNULL(GROUP_CONCAT(
            CONCAT('<li>', e.email, ' (', e.capacity, '/500)</li>')
            ORDER BY e.id SEPARATOR ''
        ), ''),
        '</ul>'
    ) AS smtp_email,

    GROUP_CONCAT(e.id) AS selected_email_ids

FROM advisers a

-- 1. Standard lookups
LEFT JOIN users u ON u.email = a.email
LEFT JOIN colleges c ON a.college_id = c.college_id
LEFT JOIN departments d ON a.department_id = d.department_id
LEFT JOIN majors m ON a.major_id = m.major_id

-- 2. Join courses by matching department_name to course_code
LEFT JOIN courses cl ON d.department_name = cl.course_code

-- 3. Join actual_year_levels using the course ID we just found
LEFT JOIN actual_year_levels ayl
    ON a.year_level = ayl.id 
    AND (
        (a.major_id IS NOT NULL AND ayl.major_id = a.major_id)
        OR
        (a.major_id IS NULL AND ayl.course_id = cl.id)
    )

LEFT JOIN email e ON e.adviser_id = a.id
LEFT JOIN campuses wc ON a.wmsu_campus_id = wc.campus_id
LEFT JOIN campuses ec ON a.external_campus_id = ec.campus_id

GROUP BY a.id
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($data as &$row) {
    $row['selected_email_ids'] = $row['selected_email_ids']
        ? explode(',', $row['selected_email_ids'])
        : [];
    $row['is_active'] = isset($row['is_active']) ? (int)$row['is_active'] : 0;
}

echo json_encode(["data" => $data]);
