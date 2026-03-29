<?php
date_default_timezone_set('Asia/Manila');
require_once '../includes/conn.php';  // Correct path

header('Content-Type: text/html');

try {
    $partyName = $_POST['party_name'] ?? '';

    // Validate input
    if (empty($partyName)) {
        throw new Exception("Election name and party name are required.");
    }

    // Fetch positions based on party name
    $stmt = $pdo->prepare("SELECT DISTINCT name, level FROM positions WHERE party = ?");
    $stmt->execute([$partyName]);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($positions) {
        $options = '<option value="">Select Position</option>';

        foreach ($positions as $position) {
            $positionName = htmlspecialchars($position['name']);
            $level = htmlspecialchars($position['level']);

            // Add Central or Local label
            $label = ($level === 'Central') ? 'Central' : 'Local';

            $options .= '<option value="' . $positionName . '">' . $positionName . ' <small>(' . $label . ')</small></option>';
        }

        echo $options;
    } else {
        echo '<option value="">No positions found</option>';
    }
} catch (Exception $e) {
    echo '<option value="">Error loading positions: ' . htmlspecialchars($e->getMessage()) . '</option>';
    exit;
}
