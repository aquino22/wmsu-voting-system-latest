<?php
session_start();

include('../../../includes/conn.php');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

try {
    $id     = $_POST['id']     ?? 0;
    $action = $_POST['action'] ?? '';

    if ($id <= 0 || empty($action)) {
        throw new Exception("Invalid ID or action");
    }

    // Get voting period
    $stmt = $pdo->prepare("SELECT status, election_id FROM voting_periods WHERE id = ?");
    $stmt->execute([$id]);
    $voting_period = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voting_period) {
        throw new Exception("Voting period not found");
    }

    $current_status            = $voting_period['status'];
    $voting_period_election_id = $voting_period['election_id'];

    $stmt = $pdo->prepare("SELECT id FROM elections WHERE id = ? LIMIT 1");
    $stmt->execute([$voting_period_election_id]);
    $vp_id = $stmt->fetchColumn();

    $message = '';

    switch (strtolower($action)) {

        // ══════════════════════════════════════════════════════════════════════
        case 'start':
            // ══════════════════════════════════════════════════════════════════════

            if ($current_status !== 'Scheduled' && $current_status !== 'Paused') {
                throw new Exception("Cannot start voting period in $current_status status");
            }

            // ──────────────────────────────────────────────────────────────────
            // PRE-FLIGHT VALIDATION
            // Goal: ensure every confirmed voter has a matching active precinct
            //       with enough max_capacity to accommodate their group.
            //
            // Grouping key: college + department + campus_type (wmsu_campus)
            //               + college_external (only for ESU voters)
            // ──────────────────────────────────────────────────────────────────

            // 1. Count confirmed voters per group
            $voterGroupStmt = $pdo->prepare("
                SELECT
                    v.college,
                    v.department,
                    v.wmsu_campus          AS campus_type,
                    v.external_campus      AS college_external,
                    c.college_name,
                    d.department_name,
                    wc.campus_name         AS campus_name,
                    ec.campus_name         AS esu_campus_name,
                    COUNT(v.student_id)    AS voter_count
                FROM voters v
                LEFT JOIN colleges    c  ON v.college          = c.college_id
                LEFT JOIN departments d  ON v.department        = d.department_id
                LEFT JOIN campuses    wc ON v.wmsu_campus       = wc.campus_id
                LEFT JOIN campuses    ec ON v.external_campus   = ec.campus_id
                WHERE v.status = 'confirmed'
                GROUP BY
                    v.college,
                    v.department,
                    v.wmsu_campus,
                    v.external_campus
            ");
            $voterGroupStmt->execute();
            $voterGroups = $voterGroupStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($voterGroups)) {
                throw new Exception("No confirmed voters found in the system.");
            }

            // 2. Load all active precincts for this election
            $precinctStmt = $pdo->prepare("
                SELECT
                    p.id,
                    p.name,
                    p.college,
                    p.department,
                    p.type             AS campus_type,
                    p.college_external,
                    p.max_capacity,
                    p.current_capacity
                FROM precincts p
                JOIN precinct_elections pe ON p.name = pe.precinct_name
                WHERE pe.election_name = ?
                AND   p.status         = 'active'
            ");
            $precinctStmt->execute([$vp_id]);
            $precincts = $precinctStmt->fetchAll(PDO::FETCH_ASSOC);

            // Index precincts by their group key for O(1) lookup
            // Key: "{college}_{department}_{campus_type}_{college_external}"
            //      college_external is '' for non-ESU precincts
            $precinctIndex = [];
            foreach ($precincts as $p) {
                $key = implode('_', [
                    $p['college'],
                    $p['department'],
                    $p['campus_type'],
                    $p['college_external'] ?? ''
                ]);
                $precinctIndex[$key] = $p;
            }

            // 3. Validate each voter group
            $missingPrecincts  = []; // groups with no precinct at all
            $capacityIssues    = []; // groups where max_capacity < voter_count

            foreach ($voterGroups as $group) {
                $key = implode('_', [
                    $group['college'],
                    $group['department'],
                    $group['campus_type'],
                    $group['college_external'] ?? ''
                ]);

                // Build a human-readable label for this group
                $collegeName = $group['college_name']     ?? "College ID {$group['college']}";
                $deptName    = $group['department_name']  ?? "Dept ID {$group['department']}";
                $campusLabel = $group['campus_name']      ?? "Campus ID {$group['campus_type']}";
                if (!empty($group['college_external'])) {
                    $campusLabel .= ' — ' . ($group['esu_campus_name'] ?? "ESU ID {$group['college_external']}");
                }
                $groupLabel = "$collegeName › $deptName › $campusLabel";

                if (!isset($precinctIndex[$key])) {
                    // No precinct exists for this group
                    $missingPrecincts[] = [
                        'group'       => $groupLabel,
                        'voter_count' => $group['voter_count'],
                    ];
                } else {
                    $precinct = $precinctIndex[$key];
                    if ((int)$precinct['max_capacity'] < (int)$group['voter_count']) {
                        // Precinct exists but can't fit all voters
                        $shortfall = (int)$group['voter_count'] - (int)$precinct['max_capacity'];
                        $capacityIssues[] = [
                            'group'        => $groupLabel,
                            'precinct'     => $precinct['name'],
                            'max_capacity' => (int)$precinct['max_capacity'],
                            'voter_count'  => (int)$group['voter_count'],
                            'shortfall'    => $shortfall,
                        ];
                    }
                }
            }

            // ──────────────────────────────────────────────────────────────────
            // 3b. Validate that every active precinct has at least one moderator
            // ──────────────────────────────────────────────────────────────────

            // Collect IDs of all active precincts for this election
            $precinctIds = array_column($precincts, 'id');
            $missingModerators = [];

            if (!empty($precinctIds)) {
                // Fetch all active moderators assigned to these precincts in one query
                $inPlaceholders = implode(',', array_fill(0, count($precinctIds), '?'));
                $modStmt = $pdo->prepare("
        SELECT precinct, COUNT(*) AS mod_count
        FROM moderators
        WHERE precinct IN ($inPlaceholders)
          AND status = 'active'
        GROUP BY precinct
    ");
                $modStmt->execute($precinctIds);
                $moderatedPrecincts = $modStmt->fetchAll(PDO::FETCH_KEY_PAIR); // [precinct_id => mod_count]

                foreach ($precincts as $p) {
                    if (empty($moderatedPrecincts[$p['id']])) {
                        $missingModerators[] = [
                            'precinct_id'   => $p['id'],
                            'precinct_name' => $p['name'],
                        ];
                    }
                }
            }

            // 4. If any issues found, block and return structured error report
            if (!empty($missingPrecincts) || !empty($capacityIssues) || !empty($missingModerators)) {
                $errorLines = [];

                if (!empty($missingPrecincts)) {
                    $errorLines[] = "MISSING PRECINCTS — No active precinct found for these groups:";
                    foreach ($missingPrecincts as $mp) {
                        $errorLines[] = " <br><br> • {$mp['group']} ({$mp['voter_count']} confirmed voters — needs a precinct) <a href='precincts.php' target='_blank'>Go to Precincts</a>";
                    }
                }

                if (!empty($capacityIssues)) {
                    if (!empty($missingPrecincts)) $errorLines[] = "";
                    $errorLines[] = "CAPACITY ISSUES — Precinct max_capacity is too low:";
                    foreach ($capacityIssues as $ci) {
                        $errorLines[] = "  • {$ci['group']}";
                        $errorLines[] = "    Precinct: {$ci['precinct']}";
                        $errorLines[] = "    Current max: {$ci['max_capacity']} | Voters: {$ci['voter_count']} | Needs +{$ci['shortfall']} more capacity";
                    }
                }

                // ── NEW ──
                if (!empty($missingModerators)) {
                    if (!empty($missingPrecincts) || !empty($capacityIssues)) $errorLines[] = "";
                    $errorLines[] = "MISSING MODERATORS — These precincts have no active moderator assigned:";
                    foreach ($missingModerators as $mm) {
                        $errorLines[] = " <br><br> • Precinct \"{$mm['precinct_name']}\" (ID {$mm['precinct_id']}) — <a href='moderators.php' target='_blank'>Go to Moderators</a>";
                    }
                }

                echo json_encode([
                    'status'             => 'preflight_error',
                    'message'            => implode("\n", $errorLines),
                    'missing_precincts'  => $missingPrecincts,
                    'capacity_issues'    => $capacityIssues,
                    'missing_moderators' => $missingModerators,   // ← new key
                ]);
                exit();
            }

            // ──────────────────────────────────────────────────────────────────
            // PRE-FLIGHT PASSED — proceed with voter assignment
            // ──────────────────────────────────────────────────────────────────

            $pdo->beginTransaction();

            $update_stmt = $pdo->prepare("
                UPDATE voting_periods
                SET status       = 'Ongoing',
                    start_period = IFNULL(start_period, NOW())
                WHERE id = ?
            ");
            $update_stmt->execute([$id]);

            // Load voters
            $voters_stmt = $pdo->prepare("
                SELECT student_id, college, course, department, year_level, wmsu_campus, external_campus
                FROM voters
                WHERE status = 'confirmed'
            ");
            $voters_stmt->execute();
            $all_voters = $voters_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Load precincts (fresh, with capacity)
            $precinct_stmt = $pdo->prepare("
                SELECT p.id, p.name, p.college, p.department,
                       p.current_capacity, p.max_capacity, p.type, p.status, p.college_external
                FROM precincts p
                JOIN precinct_elections pe ON p.name = pe.precinct_name
                WHERE pe.election_name = ? AND p.status = 'active'
                ORDER BY p.id ASC
            ");
            $precinct_stmt->execute([$vp_id]);
            $available_precincts = $precinct_stmt->fetchAll(PDO::FETCH_ASSOC);

            $insert_stmt = $pdo->prepare("
                INSERT INTO precinct_voters (precinct, student_id, created_at, status)
                VALUES (?, ?, NOW(), 'verified')
                ON DUPLICATE KEY UPDATE status = 'verified'
            ");

            $capacity_stmt = $pdo->prepare("
                UPDATE precincts SET current_capacity = ? WHERE id = ?
            ");

            $assigned_voters   = 0;
            $unassigned_voters = 0;

            foreach ($all_voters as $voter) {
                $student_id  = $voter['student_id'];
                $assigned    = false;

                $voter_campus_id = $voter['wmsu_campus'];
                $voter_external  = $voter['external_campus'];

                foreach ($available_precincts as &$precinct) {
                    $precinct_type_id = (int)$precinct['type'];

                    $college_match    = $precinct['college']    == $voter['college'];
                    $department_match = $precinct['department'] == $voter['department'];

                    $campus_match = false;
                    if ($voter_campus_id && $precinct_type_id == $voter_campus_id) {
                        $campus_match = true;
                    } elseif (
                        !empty($precinct['college_external']) &&
                        $precinct['college_external'] == $voter_external
                    ) {
                        $campus_match = true;
                    }

                    if ($college_match && $department_match && $campus_match) {
                        if ((int)$precinct['current_capacity'] < (int)$precinct['max_capacity']) {
                            $insert_stmt->execute([$precinct['id'], $student_id]);
                            $assigned = true;
                            $assigned_voters++;
                            $precinct['current_capacity']++;
                            $capacity_stmt->execute([$precinct['current_capacity'], $precinct['id']]);
                            break;
                        }
                    }
                }
                unset($precinct);

                if (!$assigned) {
                    $unassigned_voters++;
                    error_log("Voter $student_id could not be assigned to a precinct after pre-flight pass.");
                }
            }

            // Verify candidates
            $candidate_check_stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM candidates c
                JOIN registration_forms rf ON c.form_id = rf.id
                WHERE rf.election_name = ? AND c.status = 'accepted'
            ");
            $candidate_check_stmt->execute([$voting_period_election_id]);
            $candidate_count = $candidate_check_stmt->fetchColumn();

            if ($candidate_count == 0) {
                throw new Exception("No accepted candidates found for this election.");
            }

            // Disable registration forms
            $pdo->prepare("UPDATE events SET registration_enabled = 0 WHERE candidacy = ?")
                ->execute([$voting_period_election_id]);
            $pdo->prepare("UPDATE registration_forms SET status = 'disabled' WHERE election_name = ?")
                ->execute([$voting_period_election_id]);

            $pdo->commit();

            $message = "Voting period started successfully. "
                . "$assigned_voters voters assigned to precincts."
                . ($unassigned_voters > 0 ? " Warning: $unassigned_voters voters could not be assigned." : "");
            break;


        // ══════════════════════════════════════════════════════════════════════
        case 'pause':
            // ══════════════════════════════════════════════════════════════════════
            if ($current_status == 'Ongoing') {
                $pdo->prepare("UPDATE voting_periods SET status = 'Paused' WHERE id = ?")
                    ->execute([$id]);
                $message = "Voting period paused successfully";
            } else {
                throw new Exception("Cannot pause voting period in $current_status status");
            }
            break;


        // ══════════════════════════════════════════════════════════════════════
        case 'delete':
            // ══════════════════════════════════════════════════════════════════════
            if ($current_status == 'Scheduled') {
                $pdo->prepare("DELETE FROM voting_periods WHERE id = ?")->execute([$id]);
                $message = "Voting period deleted successfully";
            } else {
                throw new Exception("Cannot delete voting period in $current_status status");
            }
            break;


        // ══════════════════════════════════════════════════════════════════════
        case 'end':
            // ══════════════════════════════════════════════════════════════════════
            if ($current_status == 'Ongoing' || $current_status == 'Paused') {
                $pdo->prepare("
                    UPDATE voting_periods
                    SET status     = 'Ended',
                        end_period = NOW()
                    WHERE id = ?
                ")->execute([$id]);
                $message = "Voting period ended successfully";
            } else {
                throw new Exception("Cannot end voting period in $current_status status");
            }
            break;


        // ══════════════════════════════════════════════════════════════════════
        case 'reschedule':
            // ══════════════════════════════════════════════════════════════════════
            $new_start = $_POST['start_period'] ?? null;
            $new_end   = $_POST['end_period']   ?? null;

            if ($current_status !== 'Scheduled' && $current_status !== 'Paused') {
                throw new Exception("Cannot reschedule voting period in $current_status status");
            }
            if (!$new_start || !strtotime($new_start)) {
                throw new Exception("Invalid new start date provided");
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT start_period, end_period FROM voting_periods WHERE id = ?");
            $stmt->execute([$id]);
            $prev_period = $stmt->fetch(PDO::FETCH_ASSOC);

            $pdo->prepare("
                UPDATE voting_periods
                SET start_period    = ?,
                    end_period      = ?,
                    re_start_period = ?,
                    re_end_period   = ?,
                    status          = 'Scheduled'
                WHERE id = ?
            ")->execute([$new_start, $new_end, $prev_period['start_period'], $prev_period['end_period'], $id]);

            $pdo->prepare("
                UPDATE precinct_voters pv
                JOIN precincts p          ON pv.precinct    = p.id
                JOIN precinct_elections pe ON p.name        = pe.precinct_name
                SET pv.status = 'verified'
                WHERE pe.election_name = ?
            ")->execute([$vp_id]);

            $pdo->commit();
            $message = "Voting period rescheduled successfully to $new_start";
            break;


        default:
            throw new Exception("Unknown action: $action");
    }

    echo json_encode(['status' => 'success', 'message' => $message]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
