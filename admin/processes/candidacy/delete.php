<?php
session_start(); // Start session for user tracking
require '../../includes/conn.php'; // Database connection

header('Content-Type: application/json'); // Ensure JSON response

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["id"])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method or missing candidacy ID"
    ]);
    exit;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $candidacyId = $_POST["id"];

    // Validate candidacy ID
    if (!is_numeric($candidacyId) || $candidacyId <= 0) {
        throw new Exception("Invalid candidacy ID provided.");
    }

    // Start a transaction
    $pdo->beginTransaction();

    // Step 1: Get election_name from candidacy
    $stmt = $pdo->prepare("SELECT election_name FROM candidacy WHERE id = :id");
    $stmt->bindParam(':id', $candidacyId, PDO::PARAM_INT);
    $stmt->execute();
    $candidacy = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$candidacy) {
        throw new Exception("No candidacy found with the provided ID.");
    }
    $electionName = $candidacy['election_name'];

    // Step 2: Check voting_periods status
    $stmt = $pdo->prepare("SELECT status FROM voting_periods WHERE name = :election_name");
    $stmt->bindParam(':election_name', $electionName, PDO::PARAM_STR);
    $stmt->execute();
    $votingStatus = $stmt->fetchColumn();

    // If voting has started or finished, block deletion
    if ($votingStatus && in_array(strtolower($votingStatus), ['ongoing', 'finished'])) {
        throw new Exception("Cannot delete candidacy for an election where voting has started or finished.");
    }

    // Step 3: Proceed with deletion if voting hasn’t started

    // 3.1 candidate_responses
    $stmt = $pdo->prepare("
        DELETE cr FROM candidate_responses cr
        INNER JOIN candidates c ON cr.candidate_id = c.id
        INNER JOIN registration_forms rf ON c.form_id = rf.id
        WHERE rf.election_name = :election_name
    ");
    $stmt->bindParam(':election_name', $electionName, PDO::PARAM_STR);
    $stmt->execute();

    // 3.2 candidate_files
    $stmt = $pdo->prepare("
        DELETE cf FROM candidate_files cf
        INNER JOIN candidates c ON cf.candidate_id = c.id
        INNER JOIN registration_forms rf ON c.form_id = rf.id
        WHERE rf.election_name = :election_name
    ");
    $stmt->bindParam(':election_name', $electionName, PDO::PARAM_STR);
    $stmt->execute();

    // 3.3 candidates
    $stmt = $pdo->prepare("
        DELETE c FROM candidates c
        INNER JOIN registration_forms rf ON c.form_id = rf.id
        WHERE rf.election_name = :election_name
    ");
    $stmt->bindParam(':election_name', $electionName, PDO::PARAM_STR);
    $stmt->execute();

    // 3.4 form_fields
    $stmt = $pdo->prepare("
        DELETE ff FROM form_fields ff
        INNER JOIN registration_forms rf ON ff.form_id = rf.id
        WHERE rf.election_name = :election_name
    ");
    $stmt->bindParam(':election_name', $electionName, PDO::PARAM_STR);
    $stmt->execute();

    // 3.5 registration_forms
    $stmt = $pdo->prepare("DELETE FROM registration_forms WHERE election_name = :election_name");
    $stmt->bindParam(':election_name', $electionName, PDO::PARAM_STR);
    $stmt->execute();

    // 3.6 events
    $stmt = $pdo->prepare("DELETE FROM events WHERE candidacy = :election_name");
    $stmt->bindParam(':election_name', $electionName, PDO::PARAM_STR);
    $stmt->execute();

    // 3.7 precinct_elections
    $stmt = $pdo->prepare("DELETE FROM precinct_elections WHERE election_name = :election_name");
    $stmt->bindParam(':election_name', $electionName, PDO::PARAM_STR);
    $stmt->execute();

    // 3.8 votes
    $stmt = $pdo->prepare("
        DELETE v FROM votes v
        INNER JOIN voting_periods vp ON v.voting_period_id = vp.id
        WHERE vp.name = :election_name
    ");
    $stmt->bindParam(':election_name', $electionName, PDO::PARAM_STR);
    $stmt->execute();

    // 3.9 voting_periods
    $stmt = $pdo->prepare("DELETE FROM voting_periods WHERE name = :election_name");
    $stmt->bindParam(':election_name', $electionName, PDO::PARAM_STR);
    $stmt->execute();

    // 3.10 precinct_voters
    $stmt = $pdo->prepare("
        DELETE pv FROM precinct_voters pv
        INNER JOIN precinct_elections pe ON pv.precinct = pe.precinct_name
        WHERE pe.election_name = :election_name
    ");
    $stmt->bindParam(':election_name', $electionName, PDO::PARAM_STR);
    $stmt->execute();

    // 3.11 candidacy
    $stmt = $pdo->prepare("DELETE FROM candidacy WHERE id = :id");
    $stmt->bindParam(':id', $candidacyId, PDO::PARAM_INT);
    $stmt->execute();

    // Check if candidacy was deleted
    if ($stmt->rowCount() > 0) {
        // Log to user_activities (if user is authenticated)
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $action = 'DELETE_CANDIDACY';
            $timestamp = date('Y-m-d H:i:s');
            $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $location = 'N/A'; // Could be enhanced with geolocation if needed
            $behavior_patterns = "Deleted candidacy period for election: $electionName";

            $activityStmt = $pdo->prepare("
                INSERT INTO user_activities (
                    user_id, action, timestamp, device_info, ip_address, location, behavior_patterns
                ) VALUES (
                    :user_id, :action, :timestamp, :device_info, :ip_address, :location, :behavior_patterns
                )
            ");
            $activityStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $activityStmt->bindParam(':action', $action, PDO::PARAM_STR);
            $activityStmt->bindParam(':timestamp', $timestamp, PDO::PARAM_STR);
            $activityStmt->bindParam(':device_info', $device_info, PDO::PARAM_STR);
            $activityStmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
            $activityStmt->bindParam(':location', $location, PDO::PARAM_STR);
            $activityStmt->bindParam(':behavior_patterns', $behavior_patterns, PDO::PARAM_STR);
            $activityStmt->execute();
        }

        $pdo->commit();
        echo json_encode([
            "status" => "success",
            "message" => "Candidacy period and related data deleted successfully."
        ]);
    } else {
        $pdo->rollBack();
        throw new Exception("No candidacy found with the provided ID.");
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

// Close connection (optional)
$pdo = null;
?>