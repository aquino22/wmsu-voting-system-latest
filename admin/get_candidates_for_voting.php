<?php
require_once 'includes/conn.php';
header('Content-Type: application/json');

$votingPeriodId = $_POST['voting_period_id'] ?? 0;
$adminMode = $_POST['admin_mode'] ?? 0;
$collegeGrouping = $_POST['college_grouping'] ?? 0;

try {
    // Get election details based on voting period
    $stmt = $pdo->prepare("
    SELECT 
        e.id AS election_id,
        e.election_name
    FROM voting_periods vp
    INNER JOIN elections e ON e.id = vp.election_id
    WHERE vp.id = ?
");
    $stmt->execute([$votingPeriodId]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$election) {
        throw new Exception("Election not found for this voting period");
    }

    // Access values
    $electionId   = $election['election_id'];
    $electionName = $election['election_name'];




    // Get active registration form ID
    $stmt = $pdo->prepare("SELECT id FROM registration_forms WHERE election_name = ? LIMIT 1");
    $stmt->execute([$electionId]);
    $formId = $stmt->fetchColumn();

    if (!$formId) {
        throw new Exception("No registration form found for this voting period");
    }

    // Get accepted candidates
    $stmt = $pdo->prepare("SELECT id FROM tied_candidates WHERE form_id = ? AND status = 'accepted'");
    $stmt->execute([$formId]);
    $candidateIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($candidateIds)) {
        throw new Exception("No accepted candidates found");
    }

    // Organize candidates
    $central = [];
    $localByCollege = []; // New structure for college-based grouping

    foreach ($candidateIds as $candidateId) {
        // Get candidate details
        $stmt = $pdo->prepare("
            SELECT cr.field_id, cr.value, ff.field_name 
            FROM candidate_responses cr 
            JOIN form_fields ff ON cr.field_id = ff.id 
            WHERE cr.candidate_id = ?
        ");
        $stmt->execute([$candidateId]);
        $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get candidate photo
        $stmt = $pdo->prepare("SELECT file_path FROM candidate_files WHERE candidate_id = ? LIMIT 1");
        $stmt->execute([$candidateId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        $photo = $file ? $file['file_path'] : '';

        $loginPath = "../login/uploads/candidates/" . $photo;
        $adminPath = "admin/uploads/candidates/" . $photo;

        if (!empty($photo) && file_exists($loginPath)) {
            $candidateImage = $loginPath;
        } elseif (!empty($photo) && file_exists($adminPath)) {
            $candidateImage = $adminPath;
        } else {
            $candidateImage = "admin/uploads/candidates/default.jpg"; // fallback
        }


        // Extract candidate data
        $candidate = [
            'id' => $candidateId,
            'photo' => $photo,
            'name' => '',
            'party' => '',
            'position' => '',
            'college' => 'N/A',
            'department' => 'N/A'
        ];

        foreach ($responses as $response) {
            if ($response['field_name'] === 'full_name') $candidate['name'] = $response['value'];
            elseif ($response['field_name'] === 'party') $candidate['party'] = $response['value'];
            elseif ($response['field_name'] === 'position') $candidate['position'] = $response['value'];
        }

        // Get position level
        $stmt = $pdo->prepare("SELECT level FROM positions WHERE name = ? LIMIT 1");
        $stmt->execute([$candidate['position']]);
        $level = $stmt->fetchColumn();

        // Get college and department names along with voter info
        $stmt = $pdo->prepare("
    SELECT 
        v.college AS college_id,
        c.college_name,
        v.department AS department_id,
        d.department_name
    FROM voters v
    LEFT JOIN colleges c ON v.college = c.college_id
    LEFT JOIN departments d ON v.department = d.department_id
    WHERE CONCAT(TRIM(v.first_name), ' ',
                 CASE WHEN v.middle_name IS NOT NULL AND v.middle_name != '' 
                      THEN CONCAT(TRIM(v.middle_name), ' ') ELSE '' END,
                 TRIM(v.last_name)) = ?
    LIMIT 1
");

        $stmt->execute([$candidate['name']]);
        $voterData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($voterData) {
            $candidate['college']    = $voterData['college_name'] ?? 'N/A';
            $candidate['department'] = $voterData['department_name'] ?? 'N/A';
        }

        // Organize by position and level
        if ($level === 'Central') {
            if (!isset($central[$candidate['position']])) {
                $central[$candidate['position']] = [
                    'name' => $candidate['position'],
                    'candidates' => []
                ];
            }
            $central[$candidate['position']]['candidates'][] = $candidate;
        } else {
            // Group by college first
            $college = $candidate['college'];
            if (!isset($localByCollege[$college])) {
                $localByCollege[$college] = [];
            }

            // Then by position within college
            if (!isset($localByCollege[$college][$candidate['position']])) {
                $localByCollege[$college][$candidate['position']] = [
                    'name' => $candidate['position'],
                    'candidates' => []
                ];
            }

            $localByCollege[$college][$candidate['position']]['candidates'][] = $candidate;
        }
    }

    // Convert associative arrays to indexed arrays for JSON
    $central = array_values($central);

    // Convert localByCollege to proper structure
    $formattedLocal = [];
    foreach ($localByCollege as $college => $positions) {
        $formattedLocal[$college] = array_values($positions);
    }

    echo json_encode([
        'success' => true,
        'election_name' => $electionName,
        'central' => $central,
        'local_by_college' => $formattedLocal
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
