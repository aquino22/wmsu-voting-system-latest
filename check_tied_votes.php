<?php
require_once 'includes/conn.php';

header('Content-Type: application/json');

$voting_period_id = $_GET['voting_period_id'] ?? 0;

function checkForTiedVotes($voting_period_id) {
    global $pdo;

    // First get all positions with their level (Central/Local)
    $stmtPositions = $pdo->prepare("SELECT name, level FROM positions");
    $stmtPositions->execute();
    $positionsData = $stmtPositions->fetchAll(PDO::FETCH_KEY_PAIR); // ['position_name' => 'level']

    // Get all votes grouped by position and candidate
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
        if (count($candidates) < 2) continue; // Need at least 2 candidates to have a tie

        // Get position level from our positions data
        $positionLevel = $positionsData[$position] ?? 'Local'; // Default to Local if not found

        // Get the highest vote count
        $topVoteCount = $candidates[0]['vote_count'];

        // Find all candidates with the top vote count
        $tiedCandidates = array_filter($candidates, function ($c) use ($topVoteCount) {
            return $c['vote_count'] == $topVoteCount;
        });

        // Only proceed if more than one candidate has the top vote count
        if (count($tiedCandidates) > 1) {
            if ($positionLevel === 'Central') {
                // For Central positions, we don't need to group by department
                $groupedCandidates = [['candidates' => $tiedCandidates, 'department' => null, 'college' => null]];
            } else {
                // For Local positions, we need to group by department
                $groupedCandidates = [];
                foreach ($tiedCandidates as $c) {
                    // Get the candidate's student_id from candidate_responses
                    $stmtStudentId = $pdo->prepare("
                        SELECT cr.value 
                        FROM candidate_responses cr
                        JOIN form_fields ff ON cr.field_id = ff.id
                        WHERE cr.candidate_id = ? AND ff.field_name = 'student_id'
                        LIMIT 1
                    ");
                    $stmtStudentId->execute([$c['candidate_id']]);
                    $studentId = $stmtStudentId->fetchColumn();

                    if ($studentId) {
                        // Get department/college from voters table
                        $stmtVoter = $pdo->prepare("
                            SELECT department, college 
                            FROM voters 
                            WHERE student_id = ? 
                            LIMIT 1
                        ");
                        $stmtVoter->execute([$studentId]);
                        $voterData = $stmtVoter->fetch(PDO::FETCH_ASSOC);

                        if ($voterData) {
                            $department = $voterData['department'];
                            $college = $voterData['college'];
                            
                            // Create a key for grouping
                            $key = $department . '|' . $college;
                            if (!isset($groupedCandidates[$key])) {
                                $groupedCandidates[$key] = [
                                    'candidates' => [],
                                    'department' => $department,
                                    'college' => $college
                                ];
                            }
                            $groupedCandidates[$key]['candidates'][] = $c;
                        }
                    }
                }
                $groupedCandidates = array_values($groupedCandidates);
            }

            // Process each group of candidates (either all Central candidates or department-specific Local candidates)
            foreach ($groupedCandidates as $group) {
                if (count($group['candidates']) > 1) {  // Changed to > 1 to ensure we only report actual ties
                    foreach ($group['candidates'] as $c) {
                        // Build full candidate info
                        $candidateData = [
                            'id' => $c['candidate_id'],
                            'name' => '',
                            'party' => '',
                            'position' => $position,
                            'department' => $group['department'],
                            'student_id' => null,
                            'platform' => '',
                            'photo' => 'https://cdn.vectorstock.com/i/500p/08/19/gray-photo-placeholder-icon-design-ui-vector-35850819.jpg',
                            'level' => $positionLevel,
                            'college' => $group['college'],
                            'vote_count' => $c['vote_count']
                        ];

                        // Get other candidate details
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

                        // Get photo
                        $stmtPhoto = $pdo->prepare("SELECT file_path FROM candidate_files WHERE candidate_id = ? LIMIT 1");
                        $stmtPhoto->execute([$c['candidate_id']]);
                        $file = $stmtPhoto->fetch(PDO::FETCH_ASSOC);
                        if ($file) {
                            $candidateData['photo'] = $file['file_path'];
                        }

                        // Add to final result
                        if ($positionLevel === 'Central') {
                            $tiedPositions[$position]['Central'][] = $candidateData;
                        } else {
                            $tiedPositions[$position][$group['department']][] = $candidateData;
                        }
                    }
                }
            }
        }
    }

    return $tiedPositions;
}

// Call the function to check for tied votes
$tiedPositions = checkForTiedVotes($voting_period_id);

// Clear existing tied_candidates for the new process
$pdo->exec("TRUNCATE TABLE tied_candidates");

if (!empty($tiedPositions)) {
    // Extract candidate IDs from tiedPositions
    $candidateIds = [];
    foreach ($tiedPositions as $position => $departments) {
        foreach ($departments as $department => $candidates) {
            foreach ($candidates as $candidate) {
                $candidateIds[] = $candidate['id'];
            }
        }
    }

    if (!empty($candidateIds)) {
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
}

echo json_encode([
    'hasTies' => !empty($tiedPositions),
    'tiedPositions' => $tiedPositions
]);
?>