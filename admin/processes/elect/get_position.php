<?php
include('../../includes/conn.php');

$election_id        = $_POST['election_name']        ?? '';  // actually the election ID
$party_name         = $_POST['party_name']           ?? '';
$student_college_id = (int)($_POST['student_college_id'] ?? 0);
$student_dept_id    = (int)($_POST['student_dept_id']    ?? 0);

if (!$election_id || !$party_name) {
    echo '<option value="">Invalid request</option>';
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, name, allowed_colleges, allowed_departments
    FROM positions
    WHERE election_id = ?
      AND party       = ?
    ORDER BY name
");
$stmt->execute([$election_id, $party_name]);
$positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filtered = array_filter($positions, function ($pos) use ($student_college_id, $student_dept_id) {
    $raw_colleges = $pos['allowed_colleges'];
    $raw_depts    = $pos['allowed_departments'];

    // NULL or empty string = no restriction → show to everyone
    if (empty($raw_colleges) && empty($raw_depts)) {
        return true;
    }

    $allowed_colleges    = json_decode($raw_colleges ?? '[]', true) ?? [];
    $allowed_departments = json_decode($raw_depts    ?? '[]', true) ?? [];

    $has_college_restriction = !empty($allowed_colleges);
    $has_dept_restriction    = !empty($allowed_departments);

    // Cast all IDs to int explicitly before comparing
    if ($has_college_restriction) {
        $college_ids = array_map('intval', array_column($allowed_colleges, 'id'));
        if (!in_array($student_college_id, $college_ids, true)) {
            return false;
        }
    }

    if ($has_dept_restriction) {
        $dept_ids = array_map('intval', array_column($allowed_departments, 'id'));
        if (!in_array($student_dept_id, $dept_ids, true)) {
            return false;
        }
    }

    return true;
});

if (empty($filtered)) {
    echo '<option value="">No eligible positions for your college/department</option>';
} else {
    echo '<option value="">Select Position</option>';
    foreach ($filtered as $pos) {
        echo '<option value="' . htmlspecialchars($pos['name']) . '">'
            . htmlspecialchars($pos['name'])
            . '</option>';
    }
}
