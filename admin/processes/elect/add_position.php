<?php
require '../../includes/conn.php';
session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');
// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in as an admin.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

try {
    // Input validation
    if (!isset($_POST['position']) || !isset($_POST['level']) || !isset($_POST['parties'])) {
        throw new Exception("Missing required fields");
    }

    $position = trim($_POST['position']);
    $level = trim($_POST['level']);
    $parties = is_array($_POST['parties']) ? $_POST['parties'] : [];

    // Field length validation
    $maxLengths = [
        'position' => 100,
        'level' => 50,
        'party' => 100
    ];

    if (empty($position)) {
        throw new Exception("Position name cannot be empty");
    }

    if (strlen($position) > $maxLengths['position']) {
        throw new Exception("Position name is too long (max {$maxLengths['position']} characters)");
    }

    if (empty($level)) {
        throw new Exception("Level cannot be empty");
    }

    if (strlen($level) > $maxLengths['level']) {
        throw new Exception("Level is too long (max {$maxLengths['level']} characters)");
    }

    if (empty($parties)) {
        throw new Exception("At least one party must be selected");
    }

    // ── College/Department Restrictions (Local only) ──────────────────────────
    $allowedCollegesJson = null;
    $allowedDeptsJson    = null;

    if ($level === 'Local') {
        $cIds = array_values(array_filter(array_map('intval', $_POST['allowed_colleges']    ?? [])));
        $dIds = array_values(array_filter(array_map('intval', $_POST['allowed_departments'] ?? [])));

        if (!empty($cIds)) {
            $ph   = implode(',', array_fill(0, count($cIds), '?'));
            $stmt = $pdo->prepare("SELECT college_id AS id, college_name AS name, college_abbreviation AS abbr FROM colleges WHERE college_id IN ($ph)");
            $stmt->execute($cIds);
            $colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($colleges as &$c) {
                $c['id'] = (int)$c['id'];
            }
            unset($c);
            $allowedCollegesJson = json_encode(array_values($colleges));

            if (!empty($dIds)) {
                $ph   = implode(',', array_fill(0, count($dIds), '?'));
                $stmt = $pdo->prepare("SELECT department_id AS id, department_name AS name, college_id FROM departments WHERE department_id IN ($ph)");
                $stmt->execute($dIds);
                $depts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($depts as &$d) {
                    $d['id'] = (int)$d['id'];
                    $d['college_id'] = (int)$d['college_id'];
                }
                unset($d);
                $allowedDeptsJson = json_encode(array_values($depts));
            }
        }
    }
    // ─────────────────────────────────────────────────────────────────────────

    // ✅ FIXED: Get ALL ongoing elections and their parties
    $electionsStmt = $pdo->prepare("
        SELECT e.id AS election_id, e.election_name, p.name AS party_name
        FROM elections e
        INNER JOIN parties p ON e.id = p.election_id
        WHERE e.status = 'Ongoing'
        AND p.name = ?
    ");

    // ✅ FIXED: Check for duplicates within the same election
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) FROM positions 
        WHERE name = ? AND party = ? AND level = ? AND election_id = ?
    ");

    $insertStmt = $pdo->prepare("
        INSERT INTO positions (name, party, level, election_id, allowed_colleges, allowed_departments, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $inserted = 0;
    $duplicates = [];
    $errors = [];
    $multipleElections = [];

    // Begin transaction
    $pdo->beginTransaction();

    foreach ($parties as $party) {
        $party = trim($party);
        $party = htmlspecialchars($party, ENT_QUOTES, 'UTF-8');

        if (empty($party)) {
            $errors[] = "Empty party name provided";
            continue;
        }

        if (strlen($party) > $maxLengths['party']) {
            $errors[] = "Party '$party' is too long (max {$maxLengths['party']} characters)";
            continue;
        }

        // ✅ FIXED: Get ALL elections where this party exists and is ongoing
        $electionsStmt->execute([$party]);
        $elections = $electionsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($elections)) {
            $errors[] = "Party '$party' not found in any ongoing election";
            continue;
        }

        // If party exists in multiple elections, we need to handle each one
        foreach ($elections as $election) {
            $election_id = (int) $election['election_id'];
            $election_name = $election['election_name'];

            // Check for duplicate only within this specific election
            $checkStmt->execute([$position, $party, $level, $election_id]);
            $exists = $checkStmt->fetchColumn();

            if ($exists > 0) {
                $duplicates[] = "Position '$position' already exists under '$party' at level '$level' in '$election_name' election";
                continue;
            }

            // Insert new position for this specific election
            try {
                $insertStmt->execute([$position, $party, $level, $election_id, $allowedCollegesJson, $allowedDeptsJson]);
                if ($insertStmt->rowCount() === 1) {
                    $inserted++;

                    // Track if we inserted into multiple elections
                    if (count($elections) > 1) {
                        $multipleElections[] = "Added to '$election_name' election";
                    }
                } else {
                    $errors[] = "Failed to insert position for party '$party' in '$election_name' election";
                }
            } catch (PDOException $e) {
                $errors[] = "Database error for party '$party' in '$election_name': " . $e->getMessage();
            }
        }
    }

    // Commit transaction if no errors
    if (empty($errors)) {
        $pdo->commit();
    } else {
        $pdo->rollBack();
    }

    // Prepare response
    $response = [];

    if ($inserted > 0) {
        $response = [
            'status' => 'success',
            'message' => "$inserted position(s) added successfully",
            'inserted' => $inserted
        ];

        if (!empty($multipleElections)) {
            $response['multiple_elections'] = $multipleElections;
        }

        if (!empty($duplicates)) {
            $response['message'] .= ", but some were skipped";
            $response['duplicates'] = $duplicates;
        }

        if (!empty($errors)) {
            $response['status'] = 'partial';
            $response['message'] .= ", with some errors";
            $response['errors'] = $errors;
        }
    } else {
        if (!empty($duplicates)) {
            $response = [
                'status' => 'warning',
                'message' => 'No new positions added. All selected entries already exist or are invalid.',
                'duplicates' => $duplicates
            ];
            $_SESSION['STATUS'] = 'POSITION_DUPLICATE';
        } else {
            $response = [
                'status' => 'error',
                'message' => 'No positions added.',
                'errors' => $errors
            ];
        }
    }

    echo json_encode($response);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in add_position.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . htmlspecialchars($e->getMessage())
    ]);
}
