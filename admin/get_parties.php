<?php
require_once 'includes/conn.php';

if (isset($_POST['election_name'])) {
    $election_name = $_POST['election_name'];
    
    $stmt = $pdo->prepare("SELECT name FROM parties WHERE election_name = ? AND status = 'active' ORDER BY name");
    $stmt->execute([$election_name]);
    $parties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $output = '<option value="">Select Party</option>';
    foreach ($parties as $party) {
        $output .= "<option value='" . htmlspecialchars($party['name']) . "'>" . 
                  htmlspecialchars($party['name']) . "</option>";
    }
    echo $output;
}
?>