<?php
require '../../includes/conn.php';
session_start();

header('Content-Type: application/json');

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Validate input
    if (!isset($_POST['position']) || empty($_POST['position'])) {
        echo json_encode(['status' => 'error', 'message' => 'Position name is required']);
        exit;
    }
    if (!isset($_POST['level']) || empty($_POST['level'])) {
        echo json_encode(['status' => 'error', 'message' => 'Level is required']);
        exit;
    }
    if (!isset($_POST['parties']) || !is_array($_POST['parties']) || empty($_POST['parties'])) {
        echo json_encode(['status' => 'error', 'message' => 'At least one party must be selected']);
        exit;
    }

    $positionName = htmlspecialchars(trim($_POST['position']));
    $level = htmlspecialchars(trim($_POST['level']));

    // Clean parties list
    $parties = array_unique(array_filter(array_map('trim', $_POST['parties'])));

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

    // --- NEW CHECK: If candidacy already exists for ongoing election, block ---
    $checkCandidacyStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM candidacy c
        JOIN positions p ON c.position_id = p.id
        JOIN elections e ON p.election_id = e.id
        WHERE p.name = ? AND p.level = ? AND e.status = 'Ongoing'
    ");
    $checkCandidacyStmt->execute([$positionName, $level]);
    $candidacyCount = $checkCandidacyStmt->fetchColumn();

    if ($candidacyCount > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => "Cannot edit. There are already candidates for '$positionName' ($level) in an ongoing election."
        ]);
        exit;
    }
    // --- END NEW CHECK ---

    $pdo->beginTransaction();

    $insertStmt = $pdo->prepare("
        INSERT INTO positions (name, level, party, election_id, allowed_colleges, allowed_departments)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM positions 
        WHERE name = ? AND level = ? AND party = ?
    ");

    $alreadyExists = [];
    $insertedParties = [];

    foreach ($parties as $party) {
        // Check if this party for this position already exists
        $checkStmt->execute([$positionName, $level, $party]);
        $exists = $checkStmt->fetchColumn();

        if ($exists > 0) {
            if (count($parties) === 1) {
                // Only one selected and it already exists
                $pdo->rollBack();
                echo json_encode([
                    'status' => 'error',
                    'message' => "The position '$positionName' ($level) is already assigned to party '$party'."
                ]);
                exit;
            } else {
                // Skip existing party if multiple selected
                $alreadyExists[] = $party;
                continue;
            }
        }

        // Get election_id for this party
        $stmt = $pdo->prepare("
            SELECT e.id AS election_id
            FROM parties p
            JOIN elections e ON p.election_name = e.election_name
            WHERE p.name = ? AND e.status = 'Ongoing'
            LIMIT 1
        ");
        $stmt->execute([$party]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => "Election not found for party: $party"]);
            exit;
        }

        $election_id = $result['election_id'];

        // Insert new row
        $insertStmt->execute([$positionName, $level, $party, $election_id, $allowedCollegesJson, $allowedDeptsJson]);
        $insertedParties[] = $party;
    }

    // Log activity
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $action = 'UPDATE_POSITION_MULTIPLE';
        $timestamp = date('Y-m-d H:i:s');
        $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $location = 'N/A';
        $behavior_patterns = "Updated position '$positionName' ($level) for parties: " . implode(', ', $insertedParties);

        $activityStmt = $pdo->prepare("
            INSERT INTO user_activities (
                user_id, action, timestamp, device_info, ip_address, location, behavior_patterns
            ) VALUES (
                :user_id, :action, :timestamp, :device_info, :ip_address, :location, :behavior_patterns
            )
        ");
        $activityStmt->execute([
            ':user_id' => $user_id,
            ':action' => $action,
            ':timestamp' => $timestamp,
            ':device_info' => $device_info,
            ':ip_address' => $ip_address,
            ':location' => $location,
            ':behavior_patterns' => $behavior_patterns
        ]);
    }

    $pdo->commit();

    $msg = [];
    if (!empty($insertedParties)) {
        $msg[] = "Inserted for: " . implode(', ', $insertedParties);
    }
    if (!empty($alreadyExists)) {
        $msg[] = "Skipped existing: " . implode(', ', $alreadyExists);
    }

    echo json_encode([
        'status' => 'success',
        'message' => implode('. ', $msg)
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}

$pdo = null;
