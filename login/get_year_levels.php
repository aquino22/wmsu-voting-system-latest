<?php

include('includes/conn.php');

$course_id = $_GET['course_id'] ?? null;
$major_id  = $_GET['major_id'] ?? null;

try {

    if ($major_id) {

        // If a major is selected
        $stmt = $pdo->prepare("
            SELECT id, year_level
            FROM actual_year_levels
            WHERE major_id = ?
            ORDER BY year_level ASC
        ");

        $stmt->execute([$major_id]);
    } else {

        // If only course is selected (no majors)
        $stmt = $pdo->prepare("
            SELECT id, year_level
            FROM actual_year_levels
            WHERE course_id = ?
            AND major_id IS NULL
            ORDER BY year_level ASC
        ");

        $stmt->execute([$course_id]);
    }

    $yearLevels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($yearLevels);
} catch (PDOException $e) {

    echo json_encode([
        "error" => $e->getMessage()
    ]);
}
