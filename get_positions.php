<?php
require_once 'includes/conn.php';

if(isset($_POST['election_name'])) {
    $election_name = $_POST['election_name'];
    $stmt = $conn->prepare("SELECT * FROM positions WHERE party IN 
        (SELECT name FROM parties WHERE election_name = ?)");
    $stmt->execute([$election_name]);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $output = '<option value="">Select Position</option>';
    foreach($positions as $position) {
        $output .= "<option value='{$position['name']}'>{$position['name']} (Level: {$position['level']})</option>";
    }
    echo $output;
}
?>