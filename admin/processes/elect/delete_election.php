<?php
session_start();
require '../../includes/conn.php'; // Database connection

header('Content-Type: application/json'); // Ensure JSON response

try {
    // Use the existing $pdo from conn.php (assumes it’s already defined)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the request is POST and has 'election_id'
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['election_id'])) {
        $electionId = $_POST['election_id'];

        // Validate election ID
        if (!is_numeric($electionId) || $electionId <= 0) {
            throw new Exception("Invalid election ID provided.");
        }

        // Start a transaction
        $pdo->beginTransaction();

        // Get election_name and status for validation and logging
        $stmt = $pdo->prepare("SELECT election_name, status FROM elections WHERE id = :id");
        $stmt->bindParam(':id', $electionId, PDO::PARAM_INT);
        $stmt->execute();
        $election = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$election) {
            throw new Exception("No election found with the provided ID.");
        }
        $electionName = $election['election_name'];
        $electionStatus = $election['status'];

        // Prevent deletion if status is 'Ongoing'
        if (strtolower($electionStatus) === 'ongoing') {
            throw new Exception("Cannot delete an ongoing election.");
        }

        // Step 2: Check if voting_period has started
        $stmt = $pdo->prepare("SELECT status FROM voting_periods WHERE election_id = ?");
        $stmt->execute([$electionId]);
        $votingStatus = $stmt->fetchColumn();
        if ($votingStatus && in_array(strtolower($votingStatus), ['ongoing', 'finished'])) {
            throw new Exception("Cannot delete an ongoing election.");
        }


        // Delete related records (in order of dependency)

        // 1.   _elections
        $stmt = $pdo->prepare("DELETE FROM precinct_elections WHERE election_name = :election_name");
        $stmt->bindParam(':election_name', $electionId, PDO::PARAM_STR);
        $stmt->execute();

        // 2. candidate_responses (depends on candidates)
        $stmt = $pdo->prepare("
            DELETE cr FROM candidate_responses cr
            INNER JOIN candidates c ON cr.candidate_id = c.id
            INNER JOIN registration_forms rf ON c.form_id = rf.id
            WHERE rf.election_name = :election_name
        ");
        $stmt->bindParam(':election_name', $electionId, PDO::PARAM_STR);
        $stmt->execute();

        // 3. candidate_files (depends on candidates)
        $stmt = $pdo->prepare("
            DELETE cf FROM candidate_files cf
            INNER JOIN candidates c ON cf.candidate_id = c.id
            INNER JOIN registration_forms rf ON c.form_id = rf.id
            WHERE rf.election_name = :election_name
        ");
        $stmt->bindParam(':election_name', $electionId, PDO::PARAM_STR);
        $stmt->execute();

        // 4. candidates (depends on registration_forms)
        $stmt = $pdo->prepare("
            DELETE c FROM candidates c
            INNER JOIN registration_forms rf ON c.form_id = rf.id
            WHERE rf.election_name = :election_name
        ");
        $stmt->bindParam(':election_name', $electionId, PDO::PARAM_STR);
        $stmt->execute();

        // 5. registration_forms
        $stmt = $pdo->prepare("DELETE FROM registration_forms WHERE election_name = :election_name");
        $stmt->bindParam(':election_name', $electionId, PDO::PARAM_STR);
        $stmt->execute();

        // 6. positions (depends on parties)
        $stmt = $pdo->prepare("SELECT name FROM parties WHERE election_id = :election_name");
        $stmt->bindParam(':election_name', $electionId);
        $stmt->execute();
        $partyNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($partyNames)) {
            $placeholders = str_repeat('?,', count($partyNames) - 1) . '?';
            $deleteStmt = $pdo->prepare("DELETE FROM positions WHERE party IN ($placeholders)");
            $deleteStmt->execute($partyNames);
        }

        // 7. parties
        $stmt = $pdo->prepare("DELETE FROM parties WHERE election_id = :election_name");
        $stmt->bindParam(':election_name', $electionId);
        $stmt->execute();

        // 8. candidacy
        $stmt = $pdo->prepare("DELETE FROM candidacy WHERE election_id = :election_id");
        $stmt->bindParam(':election_id', $electionId, PDO::PARAM_INT);
        $stmt->execute();

        // 9. voting_periods
        $stmt = $pdo->prepare("DELETE FROM voting_periods WHERE election_id = :election_id");
        $stmt->bindParam(':election_id', $electionId, PDO::PARAM_INT);
        $stmt->execute();

        // 10. elections (main table)
        $stmt = $pdo->prepare("DELETE FROM elections WHERE id = :id");
        $stmt->bindParam(':id', $electionId, PDO::PARAM_INT);
        $stmt->execute();

        // Check if the election was deleted
        if ($stmt->rowCount() > 0) {
            // Log to user_activities (if user is authenticated)
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $action = 'DELETE_ELECTION';
                $timestamp = date('Y-m-d H:i:s');
                $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $location = 'N/A'; // Could be enhanced with geolocation
                $behavior_patterns = "Deleted election: $electionName";

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

            // Commit the transaction
            $pdo->commit();
            $response = [
                'status' => 'success',
                'message' => 'Election and all related data deleted successfully.'
            ];
        } else {
            $pdo->rollBack();
            throw new Exception("No election found with the provided ID.");
        }
    } else {
        throw new Exception("Invalid request method or missing election ID.");
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response = [
        'status' => 'error',
        'message' => "Database error: " . $e->getMessage()
    ];
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Output JSON response
echo json_encode($response);

// Close connection (optional)
$pdo = null;
