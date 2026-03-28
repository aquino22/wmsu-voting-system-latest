<?php
session_start();
include('includes/conn.php');
function checkForTiedVotes($voting_period_id)
{
    global $pdo;

    $sql = "SELECT 
                v.position,
                v.candidate_id,
                COUNT(v.candidate_id) AS vote_count
            FROM votes v
            WHERE v.voting_period_id = ?
            GROUP BY v.position, v.candidate_id
            ORDER BY v.position, vote_count DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$voting_period_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $positionVotes = [];
    $tiedPositions = [];

    // Group votes by position
    foreach ($results as $row) {
        $positionVotes[$row['position']][] = $row;
    }

    foreach ($positionVotes as $position => $candidates) {
        if (count($candidates) < 2) continue; // Skip if fewer than 2 candidates

        // Get the highest vote count
        $topVoteCount = $candidates[0]['vote_count'];

        // Find all candidates with the top vote count
        $tiedCandidates = array_filter($candidates, function ($c) use ($topVoteCount) {
            return $c['vote_count'] == $topVoteCount;
        });

        // Only proceed if more than one candidate has the top vote count
        if (count($tiedCandidates) > 1) { // Changed from >= 1 to > 1
            foreach ($tiedCandidates as $c) {
                // Fetch full candidate info based on candidate_id
                $candidateData = [
                    'id' => $c['candidate_id'],
                    'name' => '',
                    'party' => '',
                    'position' => $position,
                    'student_id' => null,
                    'platform' => '',
                    'photo' => 'https://cdn.vectorstock.com/i/500p/08/19/gray-photo-placeholder-icon-design-ui-vector-35850819.jpg',
                    'level' => '',
                    'college' => '',
                    'vote_count' => $c['vote_count']
                ];

                // Candidate responses
                $stmtDetails = $pdo->prepare("
                    SELECT cr.value, ff.field_name
                    FROM candidate_responses cr
                    JOIN form_fields ff ON cr.field_id = ff.id
                    WHERE cr.candidate_id = ?
                ");
                $stmtDetails->execute([$c['candidate_id']]);
                $responses = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

                foreach ($responses as $response) {
                    switch ($response['field_name']) {
                        case 'full_name': $candidateData['name'] = $response['value']; break;
                        case 'party': $candidateData['party'] = $response['value']; break;
                        case 'position': $candidateData['position'] = $response['value']; break;
                        case 'student_id': $candidateData['student_id'] = $response['value']; break;
                        case 'platform': $candidateData['platform'] = $response['value']; break;
                    }
                }

                // Photo
                $stmtPhoto = $pdo->prepare("SELECT file_path FROM candidate_files WHERE candidate_id = ? LIMIT 1");
                $stmtPhoto->execute([$c['candidate_id']]);
                $file = $stmtPhoto->fetch(PDO::FETCH_ASSOC);
                if ($file) {
                    $candidateData['photo'] = $file['file_path'];
                }

                // Level
                if ($candidateData['position']) {
                    $stmtLevel = $pdo->prepare("SELECT level FROM positions WHERE name = ? LIMIT 1");
                    $stmtLevel->execute([$candidateData['position']]);
                    $candidateData['level'] = $stmtLevel->fetchColumn();
                }

                // College
                if ($candidateData['student_id']) {
                    $stmtCollege = $pdo->prepare("SELECT college FROM voters WHERE student_id = ? LIMIT 1");
                    $stmtCollege->execute([$candidateData['student_id']]);
                    $candidateData['college'] = $stmtCollege->fetchColumn();
                }

                // Add to final result
                $tiedPositions[$position][] = $candidateData;
            }
        }
    }

    return $tiedPositions;
}

function createTiedCandidatesTable()
{
    global $pdo;

    // Create tied_candidates table if it doesn't exist
    $sql = "
        CREATE TABLE IF NOT EXISTS tied_candidates (
            id INT PRIMARY KEY,
            form_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50),
            FOREIGN KEY (form_id) REFERENCES form_fields(form_id)
        )";
    $pdo->exec($sql);
}

function populateTiedCandidates($tiedPositions)
{
    global $pdo;

    // Clear existing tied_candidates for the new process
    $pdo->exec("TRUNCATE TABLE tied_candidates");

    // If no tied positions, return early
    if (empty($tiedPositions)) {
        return;
    }

    // Extract candidate IDs from tiedPositions
    $candidateIds = [];
    foreach ($tiedPositions as $position => $candidates) {
        foreach ($candidates as $candidate) {
            $candidateIds[] = $candidate['id'];
        }
    }

    // If no tied candidates, return early
    if (empty($candidateIds)) {
        return;
    }

    // Prepare placeholders for the IN clause
    $placeholders = implode(',', array_fill(0, count($candidateIds), '?'));

    // Insert tied candidates into tied_candidates table
    $insertStmt = $pdo->prepare("
        INSERT INTO tied_candidates (id, form_id, created_at, status)
        SELECT id, form_id, created_at, status
        FROM candidates
        WHERE id IN ($placeholders)
    ");

    // Execute with candidate IDs
    $insertStmt->execute($candidateIds);
}


$voting_period_id = filter_input(INPUT_GET, 'voting_period_id', FILTER_VALIDATE_INT) ?: 0;


try {
    

    // Create tied_candidates table
    createTiedCandidatesTable();

    // Get tied candidates
    $tiedPositions = checkForTiedVotes($voting_period_id);



    // Populate tied_candidates table
    populateTiedCandidates($tiedPositions);

    // Prepare update statement for precinct_voters
    $updateStmt = $pdo->prepare("
        UPDATE precinct_voters 
        SET status = 'revoted' 
        WHERE precinct = ? AND status = 'voted'
    ");

    foreach ($tiedPositions as $position => $candidates) {
 
        foreach ($candidates as $candidate) {
       
            $level = $candidate['level'] ?? '';
            if (empty($level)) {
                throw new Exception("No level found for position: $position");
            }

            if ($level === 'Central') {


                // For central level, mark all voters in all precincts as revoted
                $stmtPrecincts = $pdo->prepare("SELECT name FROM precincts");
                $stmtPrecincts->execute();
                $precincts = $stmtPrecincts->fetchAll(PDO::FETCH_COLUMN);
                echo $precincts;
            } else {
                // For local level, mark voters in precincts matching college and department
                if (empty($candidate['college']) || empty($candidate['department'])) {
                    continue; // Skip if college or department is missing
                }

                $stmtPrecincts = $pdo->prepare("
                    SELECT name 
                    FROM precincts 
                    WHERE college = ? AND (department = ? OR department IS NULL)
                    ORDER BY department IS NOT NULL DESC 
                    LIMIT 1
                ");
                $stmtPrecincts->execute([$candidate['college'], $candidate['department']]);
                $precincts = $stmtPrecincts->fetchAll(PDO::FETCH_COLUMN);
            }

            if (empty($precincts)) {
                throw new Exception("No precincts found for level: $level, college: {$candidate['college']}, department: {$candidate['department']}");
            }

            foreach ($precincts as $precinct) {
                echo $precinct;
                $updateStmt->execute([$precinct]);
            }
        }
    }



    echo json_encode([
        'status' => 'success',
        'tiedPositions' => $tiedPositions,
        'message' => 'Revote setup complete: voters marked as revoted'
    ]);
} catch (Exception $e) {

    echo json_encode([
        'status' => 'error',
        'tiedPositions' => [],
        'message' => 'Error processing revote setup: ' . $e->getMessage()
    ]);
}
