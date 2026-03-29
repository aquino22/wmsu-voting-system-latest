<?php
session_start();
require '../../includes/conn.php'; // Include database connection

// Optional: check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// Check if ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
    exit();
}

$academic_year_id = $_POST['id'];

try {
    $pdo->beginTransaction();

    // 1. Find all elections associated with this academic year
    $stmt_elections = $pdo->prepare("SELECT id, election_name FROM elections WHERE academic_year_id = ?");
    $stmt_elections->execute([$academic_year_id]);
    $elections = $stmt_elections->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($elections)) {
        foreach ($elections as $election) {
            $electionId = $election['id'];
            $electionName = $election['election_name'];

            // --- Start Deleting Election-Related Data ---

            // Get all voting period IDs for this election
            $stmt_vp = $pdo->prepare("SELECT id FROM voting_periods WHERE election_id = ?");
            $stmt_vp->execute([$electionId]);
            $vpIds = $stmt_vp->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($vpIds)) {
                $inVps = implode(',', array_fill(0, count($vpIds), '?'));
                // Delete votes associated with these voting periods
                $pdo->prepare("DELETE FROM votes WHERE voting_period_id IN ($inVps)")->execute($vpIds);
            }

            // Get all candidate IDs for this election
            $stmt_candidates = $pdo->prepare("
                SELECT c.id FROM candidates c
                INNER JOIN registration_forms rf ON c.form_id = rf.id
                WHERE rf.election_name = ?
            ");
            $stmt_candidates->execute([$electionName]);
            $candidateIds = $stmt_candidates->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($candidateIds)) {
                $inCandidates = implode(',', array_fill(0, count($candidateIds), '?'));
                // Delete candidate_responses
                $pdo->prepare("DELETE FROM candidate_responses WHERE candidate_id IN ($inCandidates)")->execute($candidateIds);
                // Delete candidate_files
                $pdo->prepare("DELETE FROM candidate_files WHERE candidate_id IN ($inCandidates)")->execute($candidateIds);
                // Also delete votes linked to candidates, just in case
                $pdo->prepare("DELETE FROM votes WHERE candidate_id IN ($inCandidates)")->execute($candidateIds);
                // Delete candidates
                $pdo->prepare("DELETE FROM candidates WHERE id IN ($inCandidates)")->execute($candidateIds);
            }

            // Delete registration forms and associated fields
            $stmt_forms = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ?");
            $stmt_forms->execute([$electionName]);
            $formIds = $stmt_forms->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($formIds)) {
                $inForms = implode(',', array_fill(0, count($formIds), '?'));
                $pdo->prepare("DELETE FROM form_fields WHERE form_id IN ($inForms)")->execute($formIds);
                $pdo->prepare("DELETE FROM registration_forms WHERE id IN ($inForms)")->execute($formIds);
            }

            // 1. Get registration forms associated with this election
            $stmt_forms = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ?");
            $stmt_forms->execute([$electionId]);
            $formIds = $stmt_forms->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($formIds)) {

                $inForms = implode(',', array_fill(0, count($formIds), '?'));

                // 2. Get candidates using form_id
                $stmt_candidates = $pdo->prepare("SELECT id FROM candidates WHERE form_id IN ($inForms)");
                $stmt_candidates->execute($formIds);
                $candidateIds = $stmt_candidates->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($candidateIds)) {

                    $inCandidates = implode(',', array_fill(0, count($candidateIds), '?'));

                    // 3. Delete candidate related data
                    $pdo->prepare("DELETE FROM candidate_files WHERE candidate_id IN ($inCandidates)")
                        ->execute($candidateIds);

                    $pdo->prepare("DELETE FROM candidate_responses WHERE candidate_id IN ($inCandidates)")
                        ->execute($candidateIds);

                    $pdo->prepare("DELETE FROM votes WHERE candidate_id IN ($inCandidates)")
                        ->execute($candidateIds);

                    // 4. Delete candidates
                    $pdo->prepare("DELETE FROM candidates WHERE id IN ($inCandidates)")
                        ->execute($candidateIds);
                }

                // 5. Delete form fields
                $pdo->prepare("DELETE FROM form_fields WHERE form_id IN ($inForms)")
                    ->execute($formIds);

                // 6. Delete registration forms
                $pdo->prepare("DELETE FROM registration_forms WHERE id IN ($inForms)")
                    ->execute($formIds);
            }

            $stmt_events = $pdo->prepare("SELECT id FROM events WHERE candidacy = ?");
            $stmt_events->execute([$electionId]);
            $eventIds = $stmt_events->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($eventIds)) {
                $inEvents = implode(',', array_fill(0, count($eventIds), '?'));
                $pdo->prepare("DELETE FROM events WHERE id IN ($inEvents)")->execute($eventIds);
            }


            // Delete candidacy
            $pdo->prepare("DELETE FROM candidacy WHERE election_id = ?")->execute([$electionId]);

            // Delete voting periods
            $pdo->prepare("DELETE FROM voting_periods WHERE election_id = ?")->execute([$electionId]);

            // Delete positions
            $pdo->prepare("DELETE FROM positions WHERE election_id = ?")->execute([$electionId]);

            // Delete parties and their images
            $stmt_party_images = $pdo->prepare("SELECT party_image FROM parties WHERE election_id = ?");
            $stmt_party_images->execute([$electionId]);
            $party_images = $stmt_party_images->fetchAll(PDO::FETCH_COLUMN);
            foreach ($party_images as $image) {
                $image_path = "../../../uploads/" . $image;
                if ($image && file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            $pdo->prepare("DELETE FROM parties WHERE election_id = ?")->execute([$electionId]);

            // Find precincts associated with this election
            $stmt_precincts = $pdo->prepare("SELECT precinct_id FROM precinct_elections WHERE election_name = ?");
            $stmt_precincts->execute([$electionId]);
            $precinct_ids = $stmt_precincts->fetchAll(PDO::FETCH_COLUMN);

            // Delete from precinct_elections
            $pdo->prepare("DELETE FROM precinct_elections WHERE election_name = ?")
                ->execute([$electionId]);

            // Delete the precincts themselves if any were found
            if (!empty($precinct_ids)) {

                $precinctPlaceholders = implode(',', array_fill(0, count($precinct_ids), '?'));

                // Unassign moderators from these precincts
                $pdo->prepare("UPDATE moderators SET precinct = NULL WHERE precinct IN ($precinctPlaceholders)")
                    ->execute($precinct_ids);

                // Delete voters linked to precincts
                $pdo->prepare("DELETE FROM precinct_voters WHERE precinct IN ($precinctPlaceholders)")
                    ->execute($precinct_ids);

                // Delete precincts
                $pdo->prepare("DELETE FROM precincts WHERE id IN ($precinctPlaceholders)")
                    ->execute($precinct_ids);
            }

            // Finally, delete the election record itself
            $pdo->prepare("DELETE FROM elections WHERE id = ?")->execute([$electionId]);
        }
    }

    // 2. After all associated data is deleted, delete the academic year
    $stmt_delete_ay = $pdo->prepare("DELETE FROM academic_years WHERE id = ?");
    $stmt_delete_ay->execute([$academic_year_id]);

    if ($stmt_delete_ay->rowCount() > 0) {
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => "Academic year and all related data deleted successfully."]);
    } else {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Academic year not found or no changes made.']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
