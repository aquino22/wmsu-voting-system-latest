<?php
require '../../includes/conn.php';

header('Content-Type: application/json');

try {
    // ─────────────────────────────
    // 1. INPUT & SANITIZATION
    // ─────────────────────────────
    $name              = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $longitude         = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);
    $latitude          = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
    $location          = trim(filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING));
    $college_id        = filter_input(INPUT_POST, 'college', FILTER_VALIDATE_INT);
    $department        = trim(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING));
    $major             = filter_input(INPUT_POST, 'major', FILTER_SANITIZE_STRING);
    $type              = trim(filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING)); // Main / External
    $college_external = trim(filter_input(INPUT_POST, 'college_external', FILTER_SANITIZE_STRING));
    $college_external = ($college_external === '') ? null : $college_external;
    $assignment_status = filter_input(INPUT_POST, 'assignment_status', FILTER_SANITIZE_STRING) ?? 'Unassigned';
    $occupied_status   = filter_input(INPUT_POST, 'occupied_status', FILTER_SANITIZE_STRING) ?? 'Unoccupied';
    $election          = trim(filter_input(INPUT_POST, 'election', FILTER_SANITIZE_STRING));
    $max_capacity      = filter_input(INPUT_POST, 'max_capacity', FILTER_VALIDATE_INT);

    $status            = 'Active';
    $current_capacity  = 0;
    $timestamp         = date('Y-m-d H:i:s');

    // ─────────────────────────────
    // 2. VALIDATION
    // ─────────────────────────────
    if (!$name)         throw new Exception('Precinct name is required');
    if (!$college_id)   throw new Exception('College is required');
    if (!$department)   throw new Exception('Department is required');
    if (!$type)         throw new Exception('Campus type is required');
    if (!$location)     throw new Exception('Location is required');
    if (!$max_capacity) throw new Exception('Max capacity is required');

    if ($type === 'External' && !$college_external) {
        throw new Exception('External campus location is required');
    }

    // ─────────────────────────────
    // 3. START TRANSACTION
    // ─────────────────────────────
    $pdo->beginTransaction();

    // ─────────────────────────────
    // 4. DUPLICATE NAME CHECK
    // ─────────────────────────────
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM precincts WHERE name = ?");
    $stmt->execute([$name]);

    if ($stmt->fetchColumn() > 0) {
        throw new Exception('A precinct with this name already exists');
    }

    // ─────────────────────────────
    // 5. DUPLICATE COMBINATION CHECK
    // ─────────────────────────────
    $stmt = $pdo->prepare("
        SELECT id FROM precincts
        WHERE college = ?
        AND department = ?
        AND type = ?
        AND (
            college_external = ?
            OR (college_external IS NULL AND ? IS NULL)
        )
    ");

    $stmt->execute([
        $college_id,
        $department,
        $type,
        $college_external,
        $college_external
    ]);

    if ($stmt->fetchColumn()) {
        $pdo->rollBack();

        $locationText = ($type === 'External')
            ? 'WMSU ESU — ' . ($college_external ?? 'Unknown')
            : 'Main Campus';

        echo json_encode([
            'status' => 'error',
            'message' => "A precinct for this college + department already exists at {$locationText}"
        ]);
        exit();
    }

    // ─────────────────────────────
    // 6. INSERT PRECINCT
    // ─────────────────────────────
    $finalLocation = ($type === 'External') ? $college_external : $location;

    $stmt = $pdo->prepare("
        INSERT INTO precincts (
            name, longitude, latitude, location,
            created_at, updated_at,
            assignment_status, occupied_status,
            college, department, major_id,
            current_capacity,
            type, status,
            college_external,
            election,
            max_capacity
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?,
            ?, ?,
            ?, ?, ?,
            ?,
            ?, ?,
            ?, ?,
            ?
        )
    ");

    $stmt->execute([
        $name,
        $longitude,
        $latitude,
        $finalLocation,
        $timestamp,
        $timestamp,
        $assignment_status,
        $occupied_status,
        $college_id,
        $department,
        $major,
        $current_capacity,
        $type,
        $status,
        $college_external ?? null,
        $election,
        $max_capacity
    ]);

    $precinct_id = $pdo->lastInsertId();

    // ─────────────────────────────
    // 7. LINK TO ELECTION (OPTIONAL)
    // ─────────────────────────────
    if (!empty($election)) {
        $stmt = $pdo->prepare("
            INSERT INTO precinct_elections (precinct_id, precinct_name, election_name)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$precinct_id, $name, $election]);
    }

    // ─────────────────────────────
    // 8. COMMIT
    // ─────────────────────────────
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Precinct added successfully'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
