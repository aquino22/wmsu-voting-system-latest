<?php
require_once 'includes/conn.php';

if (isset($_POST['election_name'])) {
    $election_name = $_POST['election_name'];
    $party_name = $_POST['party_name'] ?? '';

    if (!empty($party_name)) {
        $stmt = $pdo->prepare("SELECT * FROM positions WHERE party = ? AND party IN (SELECT name FROM parties WHERE election_name = ?)");
        $stmt->execute([$party_name, $election_name]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM positions WHERE party IN (SELECT name FROM parties WHERE election_name = ?)");
        $stmt->execute([$election_name]);
    }

    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check for taken positions
    $takenPositions = [];
    $stmtForm = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ?");
    $stmtForm->execute([$election_name]);
    $formId = $stmtForm->fetchColumn();

    if ($formId) {
        $sql = "
            SELECT pos.value 
            FROM candidates c
            JOIN candidate_responses party ON c.id = party.candidate_id
            JOIN form_fields ff_party ON party.field_id = ff_party.id
            JOIN candidate_responses pos ON c.id = pos.candidate_id
            JOIN form_fields ff_pos ON pos.field_id = ff_pos.id
            WHERE c.form_id = ? 
            AND c.status != 'rejected'
            AND ff_party.field_name = 'party' 
            AND ff_pos.field_name = 'position'
        ";
        $params = [$formId];

        if (!empty($party_name)) {
            $sql .= " AND party.value = ?";
            $params[] = $party_name;
        }

        $stmtTaken = $pdo->prepare($sql);
        $stmtTaken->execute($params);
        $takenPositions = $stmtTaken->fetchAll(PDO::FETCH_COLUMN);
    }

    $output = '<option value="">Select Position</option>';
    foreach ($positions as $position) {
        if (in_array($position['name'], $takenPositions)) {
            continue;
        }
        $output .= "<option value='{$position['name']}'>{$position['name']} (Level: {$position['level']})</option>";
    }
    echo $output;
}
