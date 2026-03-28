<?php
session_start();
include('includes/conn.php');

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $electionId = $_POST['election_id'] ?? '';

    if (!$electionId) {
        echo json_encode(['success' => false, 'message' => 'Election ID is required']);
        exit();
    }

    try {
        if ($action === 'update_status') {
            $status = $_POST['status'] ?? '';
            if (!$status) {
                throw new Exception('Status is required');
            }

            $stmt = $pdo->prepare("UPDATE elections SET status = ? WHERE id = ?");
            $stmt->execute([$status, $electionId]);

            // Update voting period status if it exists
            $stmt = $pdo->prepare("UPDATE voting_periods SET status = ? WHERE election_id = ?");
            $stmt->execute([$status, $electionId]);

            echo json_encode(['success' => true, 'message' => 'Election status updated successfully']);
        } elseif ($action === 'delete') {
            $pdo->beginTransaction();

            // Get election name
            $stmt = $pdo->prepare("SELECT election_name FROM elections WHERE id = ?");
            $stmt->execute([$electionId]);
            $electionName = $stmt->fetchColumn();

            if ($electionName) {
                // Delete from precinct_elections
                $pdo->prepare("DELETE FROM precinct_elections WHERE election_name = ?")->execute([$electionId]);

                // Delete parties
                $pdo->prepare("DELETE FROM parties WHERE election_id = ?")->execute([$electionId]);

                // Delete events
                $pdo->prepare("DELETE FROM events WHERE candidacy = ?")->execute([$electionId]);

                // Delete registration forms and candidates
                $stmt = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ?");
                $stmt->execute([$electionId]);
                $formIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($formIds)) {
                    $inForms = implode(',', $formIds);

                    // Get candidate IDs to delete related data first
                    $stmt = $pdo->query("SELECT id FROM candidates WHERE form_id IN ($inForms)");
                    $candidateIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($candidateIds)) {
                        $inCandidates = implode(',', $candidateIds);
                        $pdo->exec("DELETE FROM candidate_responses WHERE candidate_id IN ($inCandidates)");
                        $pdo->exec("DELETE FROM candidate_files WHERE candidate_id IN ($inCandidates)");
                        $pdo->exec("DELETE FROM candidates WHERE id IN ($inCandidates)");
                    }

                    // Delete registration forms
                    $pdo->exec("DELETE FROM registration_forms WHERE id IN ($inForms)");
                }
            }

            // Delete voting periods and votes
            $stmt = $pdo->prepare("SELECT id FROM voting_periods WHERE election_id = ?");
            $stmt->execute([$electionId]);
            $vpIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($vpIds)) {
                $inVps = implode(',', $vpIds);
                $pdo->exec("DELETE FROM votes WHERE voting_period_id IN ($inVps)");
                $pdo->exec("DELETE FROM voting_periods WHERE id IN ($inVps)");
            }

            // Delete candidacy
            $pdo->prepare("DELETE FROM candidacy WHERE election_id = ?")->execute([$electionId]);

            // Delete positions and related position_parties
            $stmt = $pdo->prepare("SELECT id FROM positions WHERE election_id = ?");
            $stmt->execute([$electionId]);
            $positionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($positionIds)) {
                $inPositions = implode(',', $positionIds);
                $pdo->exec("DELETE FROM positions WHERE id IN ($inPositions)");
            }

            // Delete election
            $stmt = $pdo->prepare("DELETE FROM elections WHERE id = ?");
            $stmt->execute([$electionId]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Election deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
