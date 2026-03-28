<?php
require_once 'includes/conn.php';

header('Content-Type: application/json');

$voting_period_id = $_GET['voting_period_id'] ?? 0;
$voting_period_id = (int)$voting_period_id;
if ($voting_period_id <= 0) {
    echo json_encode(['hasTies' => false, 'tiedPositions' => [], 'message' => 'Invalid voting period ID']);
    exit;
}

// Step 1: Load positions with levels
$stmtPositions = $pdo->query("SELECT name, level FROM positions");
$positionsData = $stmtPositions->fetchAll(PDO::FETCH_KEY_PAIR); // ['position_name' => 'Central'|'Local']

// Step 2: Aggregate votes
$sql = "
    SELECT 
        v.position,
        v.candidate_id,
        COUNT(v.id) AS vote_count,
        MAX(vtr.department) AS department,
        MAX(vtr.college) AS college
    FROM votes v
    LEFT JOIN voters vtr ON v.student_id = vtr.student_id
    WHERE v.voting_period_id = ?
    GROUP BY v.position, v.candidate_id
    ORDER BY v.position, vote_count DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$voting_period_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Step 3: Group votes by position
$positionVotes = [];
foreach ($results as $row) {
    $key = trim($row['position']);
    $positionVotes[$key][] = $row;
}

$tiedPositions = [];

foreach ($positionVotes as $position => $candidates) {
    $positionLevel = $positionsData[$position] ?? 'Local'; // default to Local

    // Group candidates by department for Local positions
    $grouped = [];
    if ($positionLevel === 'Central') {
        $grouped[] = ['candidates' => $candidates, 'department' => null, 'college' => null];
    } else {
        foreach ($candidates as $c) {
            $dept = $c['department'] ?? 'Unknown';
            $college = $c['college'] ?? 'Unknown';
            $key = $dept . '|' . $college;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'candidates' => [],
                    'department' => $dept,
                    'college' => $college
                ];
            }
            $grouped[$key]['candidates'][] = $c;
        }
        $grouped = array_values($grouped);
    }

    // Check each group for ties
    foreach ($grouped as $group) {
        $topVote = max(array_column($group['candidates'], 'vote_count'));
        $tied = array_filter($group['candidates'], fn($c) => $c['vote_count'] == $topVote);

        if (count($tied) > 1) {
            // Build full candidate info
            foreach ($tied as $c) {
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

                // Load candidate responses
                $stmtDetails = $pdo->prepare("
                    SELECT cr.value, ff.field_name
                    FROM candidate_responses cr
                    JOIN form_fields ff ON cr.field_id = ff.id
                    WHERE cr.candidate_id = ?
                ");
                $stmtDetails->execute([$c['candidate_id']]);
                $responses = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

                foreach ($responses as $resp) {
                    switch ($resp['field_name']) {
                        case 'full_name':
                            $candidateData['name'] = $resp['value'];
                            break;
                        case 'party':
                            $candidateData['party'] = $resp['value'];
                            break;
                        case 'position':
                            $candidateData['position'] = $resp['value'];
                            break;
                        case 'student_id':
                            $candidateData['student_id'] = $resp['value'];
                            break;
                        case 'platform':
                            $candidateData['platform'] = $resp['value'];
                            break;
                    }
                }

                // Load candidate photo
                $stmtPhoto = $pdo->prepare("SELECT file_path FROM candidate_files WHERE candidate_id = ? LIMIT 1");
                $stmtPhoto->execute([$c['candidate_id']]);
                $file = $stmtPhoto->fetch(PDO::FETCH_ASSOC);
                if ($file) {
                    $candidateData['photo'] = $file['file_path'];
                }

                // Add to result array
                if ($positionLevel === 'Central') {
                    $tiedPositions[$position]['Central'][] = $candidateData;
                } else {
                    $tiedPositions[$position][$group['department']][] = $candidateData;
                }
            }
        }
    }
}

// Step 4: Insert tied candidates into tied_candidates table
if (!empty($tiedPositions)) {
    $candidateIds = [];
    foreach ($tiedPositions as $position => $departments) {
        foreach ($departments as $dept => $candidates) {
            foreach ($candidates as $cand) {
                $candidateIds[] = $cand['id'];
            }
        }
    }

    if (!empty($candidateIds)) {
        $placeholders = implode(',', array_fill(0, count($candidateIds), '?'));

        // Truncate first
        $pdo->exec("TRUNCATE TABLE tied_candidates");

        // Insert tied candidates
        $insertStmt = $pdo->prepare("
            INSERT INTO tied_candidates (id, form_id, created_at, status)
            SELECT id, form_id, created_at, status
            FROM candidates
            WHERE id IN ($placeholders)
        ");
        $insertStmt->execute($candidateIds);
    }
}

echo json_encode([
    'hasTies' => !empty($tiedPositions),
    'tiedPositions' => $tiedPositions
]);
