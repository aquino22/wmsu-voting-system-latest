<?php
session_start();
require_once 'includes/conn.php';

header('Content-Type: application/json');

// Ensure PDO is properly initialized
if (!isset($pdo) || !$pdo instanceof PDO) {
    echo json_encode([
        'status' => 'error',
        'tiedPositions' => [],
        'message' => 'Database connection not initialized.'
    ]);
    exit;
}

try {
    // Validate input
    $voting_period_id = isset($_POST['voting_period_id']) ? (int)$_POST['voting_period_id'] : 0;
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;

    if ($voting_period_id <= 0) {
        throw new Exception('Invalid voting period ID.');
    }

    // Validate dates
    try {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        if ($start >= $end) {
            throw new Exception('Start date must be before end date.');
        }
    } catch (Exception $e) {
        throw new Exception('Invalid start or end date: ' . $e->getMessage());
    }

    /**
     * Check for tied votes
     * @param int $voting_period_id
     * @return array
     */
    function checkForTiedVotes($voting_period_id, PDO $pdo)
    {
        $sql = "
            SELECT 
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
            if (count($candidates) < 2) {
                continue;
            }

            $topVoteCount = $candidates[0]['vote_count'];
            $tiedCandidates = array_filter($candidates, fn($c) => $c['vote_count'] == $topVoteCount);

            if (count($tiedCandidates) > 1) {
                foreach ($tiedCandidates as $c) {
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
                        'department' => '',
                        'vote_count' => $c['vote_count']
                    ];

                    // Fetch candidate details
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
                            case 'full_name':
                                $candidateData['name'] = $response['value'];
                                break;
                            case 'party':
                                $candidateData['party'] = $response['value'];
                                break;
                            case 'position':
                                $candidateData['position'] = $response['value'];
                                break;
                            case 'student_id':
                                $candidateData['student_id'] = $response['value'];
                                break;
                            case 'platform':
                                $candidateData['platform'] = $response['value'];
                                break;
                            case 'department':
                                $candidateData['department'] = $response['value'];
                                break;
                        }
                    }

                    // Fetch photo
                    $stmtPhoto = $pdo->prepare("SELECT file_path FROM candidate_files WHERE candidate_id = ? LIMIT 1");
                    $stmtPhoto->execute([$c['candidate_id']]);
                    if ($file = $stmtPhoto->fetch(PDO::FETCH_ASSOC)) {
                        $candidateData['photo'] = $file['file_path'];
                    }

                    // Fetch level
                    $stmtLevel = $pdo->prepare("SELECT level FROM positions WHERE name = ? LIMIT 1");
                    $stmtLevel->execute([$candidateData['position']]);
                    $candidateData['level'] = $stmtLevel->fetchColumn() ?: '';

                    // Fetch college and department
                    if ($candidateData['student_id']) {
                        $stmtVoter = $pdo->prepare("
                            SELECT college, department 
                            FROM voters 
                            WHERE student_id = ? 
                            LIMIT 1
                        ");
                        $stmtVoter->execute([$candidateData['student_id']]);
                        $voterData = $stmtVoter->fetch(PDO::FETCH_ASSOC);
                        $candidateData['college'] = $voterData['college'] ?? '';
                        $candidateData['department'] = $voterData['department'] ?? '';
                    }

                    $tiedPositions[$position][] = $candidateData;
                }
            }
        }

        return $tiedPositions;
    }

    /**
     * Create tied_candidates table
     * @param PDO $pdo
     */
    function createTiedCandidatesTable(PDO $pdo)
    {
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

    /**
     * Populate tied_candidates table
     * @param array $tiedPositions
     * @param PDO $pdo
     */
    function populateTiedCandidates(array $tiedPositions, PDO $pdo)
    {
        // Clear existing tied_candidates
        $pdo->exec("TRUNCATE TABLE tied_candidates");

        if (empty($tiedPositions)) {
            return;
        }

        $candidateIds = [];
        foreach ($tiedPositions as $position => $candidates) {
            foreach ($candidates as $candidate) {
                $candidateIds[] = $candidate['id'];
            }
        }

        if (empty($candidateIds)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($candidateIds), '?'));
        $insertStmt = $pdo->prepare("
            INSERT INTO tied_candidates (id, form_id, created_at, status)
            SELECT id, form_id, created_at, status
            FROM candidates
            WHERE id IN ($placeholders)
        ");
        $insertStmt->execute($candidateIds);
    }

    // Start transaction
    if (!$pdo->beginTransaction()) {
        throw new Exception('Failed to start database transaction.');
    }

    // Create tied_candidates table
    createTiedCandidatesTable($pdo);

    // Get tied candidates
    $tiedPositions = checkForTiedVotes($voting_period_id, $pdo);

    // Populate tied_candidates table
    populateTiedCandidates($tiedPositions, $pdo);

    // Prepare update statement for precinct_voters
    $updateStmt = $pdo->prepare("
        UPDATE precinct_voters 
        SET status = 'revoted' 
        WHERE precinct = ? 
    ");

    foreach ($tiedPositions as $position => $candidates) {
        foreach ($candidates as $candidate) {
            $level = $candidate['level'] ?? '';
            if (empty($level)) {
                throw new Exception("No level found for position: $position");
            }

            $precincts = [];
            if ($level === 'Central') {
                $stmtPrecincts = $pdo->prepare("SELECT id FROM precincts");
                $stmtPrecincts->execute();
                $precincts = $stmtPrecincts->fetchAll(PDO::FETCH_COLUMN);
            } else {
                if (empty($candidate['college'])) {
                    continue;
                }

                $stmtPrecincts = $pdo->prepare("
                    SELECT id 
                    FROM precincts 
                    WHERE college = ? AND (department = ? OR department IS NULL)
                    ORDER BY department IS NOT NULL DESC 
                    LIMIT 1
                ");
                $stmtPrecincts->execute([$candidate['college'], $candidate['department'] ?? null]);
                $precincts = $stmtPrecincts->fetchAll(PDO::FETCH_COLUMN);
            }

            if (empty($precincts)) {
                throw new Exception("No precincts found for level: $level, college: {$candidate['college']}, department: {$candidate['department']}");
            }

            foreach ($precincts as $precinct) {
                echo $updateStmt->execute([$precinct]);
            }
        }
    }

    // Update the voting period
    $stmt = $pdo->prepare("
        UPDATE voting_periods 
        SET    start_period = ?, end_period = ?, re_start_period = ?, re_end_period = ?, status = 'Scheduled' 
        WHERE id = ?
    ");
    $stmt->execute([$start_date, $end_date, $start_date, $end_date, $voting_period_id]);

    // Commit transaction
    if ($pdo->inTransaction()) {
        $pdo->commit();
    }

    // Set session status
    $_SESSION['STATUS'] = $stmt->rowCount() > 0 ? 'REVOTE_UPDATED_SUCCESSFULLY' : 'REVOTE_UPDATE_NO_CHANGES';

    // Return JSON response
    echo json_encode([
        'status' => 'success',
        'tiedPositions' => $tiedPositions,
        'message' => 'Revote setup complete: voters marked as revoted'
    ]);

    header("Location: view_reports.php?voting_period_id=" . $voting_period_id);
} catch (Exception $e) {
    // Roll back transaction if active
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log error
    error_log("Revote setup error: " . $e->getMessage());

    // Set session status
    $_SESSION['STATUS'] = 'REVOTE_UPDATE_ERROR: ' . $e->getMessage();

    // Return JSON response
    echo json_encode([
        'status' => 'error',
        'tiedPositions' => [],
        'message' => 'Error processing revote setup: ' . $e->getMessage()
    ]);
}
header("Location: view_reports.php?voting_period_id=" . $voting_period_id);


exit;
