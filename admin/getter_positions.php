<?php
require_once 'includes/conn.php';

if (isset($_POST['election_name']) && isset($_POST['party_name'])) {
    $election_name = $_POST['election_name'];
    $party_name = $_POST['party_name'];

    $stmt = $pdo->prepare("
        SELECT name, level 
        FROM positions 
        WHERE party = ? 
        AND party IN (
            SELECT name FROM parties 
            WHERE election_name = ?
        )
        ORDER BY level, name
    ");
    $stmt->execute([$party_name, $election_name]);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check for taken positions
    $stmtForm = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ?");
    $stmtForm->execute([$election_name]);
    $formId = $stmtForm->fetchColumn();

    $takenPositions = [];
    if ($formId) {
        $stmtTaken = $pdo->prepare("
            SELECT pos.value 
            FROM candidates c
            JOIN candidate_responses party ON c.id = party.candidate_id
            JOIN form_fields ff_party ON party.field_id = ff_party.id
            JOIN candidate_responses pos ON c.id = pos.candidate_id
            JOIN form_fields ff_pos ON pos.field_id = ff_pos.id
            WHERE c.form_id = ? 
            AND c.status != 'rejected'
            AND ff_party.field_name = 'party' 
            AND party.value = ?
            AND ff_pos.field_name = 'position'
        ");
        $stmtTaken->execute([$formId, $party_name]);
        $takenPositions = $stmtTaken->fetchAll(PDO::FETCH_COLUMN);
    }

    $output = '<option value="">Select Position</option>';
    $current_level = '';

    foreach ($positions as $position) {
        if (in_array($position['name'], $takenPositions)) {
            continue;
        }

        if ($current_level !== $position['level']) {
            if ($current_level !== '') {
                $output .= '</optgroup>';
            }
            $output .= '<optgroup label="Level ' . htmlspecialchars($position['level']) . '">';
            $current_level = $position['level'];
        }
        $output .= "<option value='" . htmlspecialchars($position['name']) . "'>" .
            htmlspecialchars($position['name']) . "</option>";
    }

    if ($current_level !== '') {
        $output .= '</optgroup>';
    }

    echo $output;
}
