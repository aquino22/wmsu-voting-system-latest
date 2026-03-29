<?php
include('../includes/conn.php');

$election_name      = $_POST['election_name']      ?? '';
$party_name         = $_POST['party_name']          ?? '';
// ✅ FIX 1: Match the exact keys sent by file_candidacy.php
$student_college_id = (int)($_POST['student_college_id'] ?? 0);
$student_dept_id    = (int)($_POST['student_dept_id']    ?? 0);

if (!$election_name || !$party_name) {
    echo '<option value="">Invalid request</option>';
    exit;
}

// Fetch positions for this party — include college/department restriction columns
$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.allowed_colleges, p.allowed_departments
    FROM positions p
    WHERE p.election_id = ?
      AND p.party = ?
    ORDER BY p.name
");
$stmt->execute([$election_name, $party_name]);
$positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filtered = array_filter($positions, function ($pos) use ($student_college_id, $student_dept_id) {
    // ✅ FIX 2: allowed_colleges/allowed_departments are JSON arrays of {id, name, ...}
    //    Parse them and compare by numeric ID, not by string substring.
    $col_json  = trim($pos['allowed_colleges']    ?? '');
    $dept_json = trim($pos['allowed_departments'] ?? '');

    $col_list  = (!empty($col_json)  && $col_json  !== 'null') ? json_decode($col_json,  true) : [];
    $dept_list = (!empty($dept_json) && $dept_json !== 'null') ? json_decode($dept_json, true) : [];

    // No restriction on either → always show
    if (empty($col_list) && empty($dept_list)) {
        return true;
    }

    // College restriction set → student's college ID must be in the list
    if (!empty($col_list)) {
        $allowed_college_ids = array_column($col_list, 'id');

        if (!in_array($student_college_id, $allowed_college_ids)) {
            return false;
        }
    }

    // Department restriction set → student's department ID must be in the list
    if (!empty($dept_list)) {
        $allowed_dept_ids = array_column($dept_list, 'id');

        if (!in_array($student_dept_id, $allowed_dept_ids)) {
            return false;
        }
    }

    return true;
});

if (empty($filtered)) {
    echo '<option value="">No eligible positions available</option>';
} else {
    echo '<option value="">Select Position</option>';
    foreach ($filtered as $pos) {
        echo '<option value="' . htmlspecialchars($pos['name']) . '">'
            . htmlspecialchars($pos['name'])
            . '</option>';
    }
}
