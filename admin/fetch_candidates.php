<?php
include('includes/conn.php');

$voting_period_id = $_POST['voting_period_id'] ?? null;
$college_filter = $_POST['college'] ?? '';
$department_filter = $_POST['department'] ?? '';

if (!$voting_period_id) {
    echo "<p>No voting period specified.</p>";
    exit();
}

try {
    // Step 1: Get the active registration form for the voting period
    $stmt = $pdo->prepare("
        SELECT id 
        FROM registration_forms 
        WHERE election_name = (SELECT name FROM voting_periods WHERE id = ?) 
        AND status = 'active' 
        LIMIT 1
    ");
    $stmt->execute([$voting_period_id]);
    $formId = $stmt->fetchColumn();

    if (!$formId) {
        echo "<p>No active registration form found for this voting period.</p>";
        exit();
    }

    // Step 2: Get accepted candidates for this form
    $stmt = $pdo->prepare("SELECT id FROM candidates WHERE form_id = ? AND status = 'accept'");
    $stmt->execute([$formId]);
    $candidateIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($candidateIds)) {
        echo "<p>No accepted candidates found.</p>";
        exit();
    }

    // Step 3: Organize candidates by level and precinct type
    $candidatesByLevel = ['Central' => [], 'Local' => ['internal' => [], 'external' => []]];
    foreach ($candidateIds as $candidateId) {
        // Fetch candidate responses
        $stmt = $pdo->prepare("
            SELECT cr.field_id, cr.value, ff.field_name 
            FROM candidate_responses cr 
            JOIN form_fields ff ON cr.field_id = ff.id 
            WHERE cr.candidate_id = ?
        ");
        $stmt->execute([$candidateId]);
        $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch candidate photo
        $stmt = $pdo->prepare("
            SELECT file_path 
            FROM candidate_files 
            WHERE candidate_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$candidateId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        $photoPath = $file ? $file['file_path'] : 'https://cdn.vectorstock.com/i/500p/08/19/gray-photo-placeholder-icon-design-ui-vector-35850819.jpg';

        // Initialize candidate data
        $candidateData = [
            'id' => $candidateId,
            'photo' => $photoPath,
            'name' => '',
            'party' => '',
            'position' => '',
            'college' => '',
            'department' => '',
            'votes' => 0,
            'precinct_type' => ''
        ];

        // Populate candidate data from responses
        foreach ($responses as $response) {
            if ($response['field_name'] === 'full_name') {
                $candidateData['name'] = $response['value'];
            } elseif ($response['field_name'] === 'party') {
                $candidateData['party'] = $response['value'];
            } elseif ($response['field_name'] === 'position') {
                $candidateData['position'] = $response['value'];
            }
        }

        // Determine position level
        $stmt = $pdo->prepare("SELECT level FROM positions WHERE name = ? LIMIT 1");
        $stmt->execute([$candidateData['position']]);
        $positionLevel = $stmt->fetchColumn();
        $level = $positionLevel === 'Central' ? 'Central' : 'Local';

        // Fetch voter data and precinct type
        $stmt = $pdo->prepare("
            SELECT v.college, v.department, p.type AS precinct_type
            FROM voters v
            JOIN precinct_voters pv ON v.student_id = pv.student_id
            JOIN precincts p ON pv.precinct = p.name
            WHERE CONCAT(
                TRIM(v.first_name), ' ',
                CASE WHEN v.middle_name IS NOT NULL AND v.middle_name != '' THEN CONCAT(TRIM(v.middle_name), ' ') ELSE '' END,
                TRIM(v.last_name)
            ) = ?
            LIMIT 1
        ");
        $stmt->execute([$candidateData['name']]);
        $voterData = $stmt->fetch(PDO::FETCH_ASSOC);
        $candidateData['college'] = $voterData['college'] ?? 'Unknown';
        $candidateData['department'] = $voterData['department'] ?? 'Unknown';
        $candidateData['precinct_type'] = $voterData['precinct_type'] ?? 'Unknown';

        // Fetch vote count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as vote_count 
            FROM votes 
            WHERE candidate_id = ? AND voting_period_id = ?
        ");
        $stmt->execute([$candidateId, $voting_period_id]);
        $candidateData['votes'] = $stmt->fetchColumn();

        // Apply filters
        if ($college_filter && $candidateData['college'] !== $college_filter) {
            continue;
        }
        if ($department_filter && $candidateData['department'] !== $department_filter) {
            continue;
        }

        // Organize candidates
        if ($level === 'Central') {
            $candidatesByLevel['Central'][$candidateData['position']][] = $candidateData;
        } else {
            $pType = strtolower($candidateData['precinct_type']);
            $precinct_type = (in_array($pType, ['internal', 'central', 'main campus'])) ? 'internal' : 'external';
            $candidatesByLevel['Local'][$precinct_type][$candidateData['college']][$candidateData['department']][$candidateData['position']][] = $candidateData;
        }
    }

    // Render Central Votes
    echo "<h2 class='text-center text-primary'>Central Votes (Main Campus + External)</h2>";
    foreach ($candidatesByLevel['Central'] as $position => $candidates) {
        echo "<div class='bordered'>";
        echo "<h3 class='text-danger'>" . htmlspecialchars($position) . "</h3>";
        echo "<div class='row'>";
        foreach ($candidates as $candidate) {
            echo "<div class='col-md-3 text-center'>";
            echo "<img src='../login/uploads/candidates/" . htmlspecialchars($candidate['photo']) . "' class='profiler' alt='Candidate Photo'>";
            echo "<h4>" . htmlspecialchars($candidate['name']) . "</h4>";
            echo "<p>Party: " . htmlspecialchars($candidate['party']) . "</p>";
            echo "<p>Votes: " . $candidate['votes'] . "</p>";
            echo "<p>College: " . htmlspecialchars($candidate['college']) . "</p>";
            echo "</div>";
        }
        echo "</div></div>";
    }

    // Render Local Votes - Internal Precincts
    echo "<h2 class='text-center text-primary'>Local Votes</h2>";
    echo "<h3>Main Campus (Internal Precincts)</h3>";
    foreach ($candidatesByLevel['Local']['internal'] as $coll => $depts) {
        echo "<div class='bordered'>";
        echo "<h4>" . htmlspecialchars($coll) . "</h4>";
        foreach ($depts as $dept => $positions) {
            echo "<h5>" . htmlspecialchars($dept) . "</h5>";
            foreach ($positions as $position => $candidates) {
                echo "<h6>" . htmlspecialchars($position) . "</h6>";
                echo "<div class='row'>";
                foreach ($candidates as $candidate) {
                    echo "<div class='col-md-3 text-center'>";
                    echo "<img src='../login/uploads/candidates/" . htmlspecialchars($candidate['photo']) . "' class='profiler' alt='Candidate Photo'>";
                    echo "<h6>" . htmlspecialchars($candidate['name']) . "</h6>";
                    echo "<p>Party: " . htmlspecialchars($candidate['party']) . "</p>";
                    echo "<p>Votes: " . $candidate['votes'] . "</p>";
                    echo "</div>";
                }
                echo "</div>";
            }
        }
        echo "</div>";
    }

    // Render Local Votes - External Campuses
    echo "<h3>External Campuses</h3>";
    foreach ($candidatesByLevel['Local']['external'] as $coll => $depts) {
        echo "<div class='bordered'>";
        echo "<h4>" . htmlspecialchars($coll) . "</h4>";
        foreach ($depts as $dept => $positions) {
            echo "<h5>" . htmlspecialchars($dept) . "</h5>";
            foreach ($positions as $position => $candidates) {
                echo "<h6>" . htmlspecialchars($position) . "</h6>";
                echo "<div class='row'>";
                foreach ($candidates as $candidate) {
                    echo "<div class='col-md-3 text-center'>";
                    echo "<img src='../login/uploads/candidates/" . htmlspecialchars($candidate['photo']) . "' class='profiler' alt='Candidate Photo'>";
                    echo "<h6>" . htmlspecialchars($candidate['name']) . "</h6>";
                    echo "<p>Party: " . htmlspecialchars($candidate['party']) . "</p>";
                    echo "<p>Votes: " . $candidate['votes'] . "</p>";
                    echo "</div>";
                }
                echo "</div>";
            }
        }
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
