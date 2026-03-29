<?php
require '../../includes/conn.php'; // Adjust path

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $candidacyName = $_POST['candidacyName'];
    $requirements = json_decode($_POST['requirements'], true);
    
    try {
        $pdo->beginTransaction();

        // Insert Candidacy Name
        $stmt = $pdo->prepare("INSERT INTO candidacy_requirements (candidacy_name) VALUES (?)");
        $stmt->execute([$candidacyName]);
        $candidacyId = $pdo->lastInsertId();

        // Insert Requirements
        foreach ($requirements as $requirement) {
            $stmt = $pdo->prepare("INSERT INTO requirements (candidacy_id, title, type) VALUES (?, ?, ?)");
            $stmt->execute([$candidacyId, $requirement['title'], $requirement['type']]);
        }

        $pdo->commit();
        echo "Candidacy and requirements saved successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

header("Location: " . $referer);

