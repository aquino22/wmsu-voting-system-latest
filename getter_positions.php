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
    
    $output = '<option value="">Select Position</option>';
    $current_level = '';
    
    foreach ($positions as $position) {
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
?>