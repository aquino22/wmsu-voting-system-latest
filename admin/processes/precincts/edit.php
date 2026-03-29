<?php
require '../../includes/conn.php'; // Include database connection

try {
    // Get and sanitize form data
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? null;
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING) ?? null;
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT) ?? null;
    $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT) ?? null;
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING) ?? null;

    $college = filter_input(INPUT_POST, 'college', FILTER_SANITIZE_STRING) ?? null;
    $department = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING) ?? null;
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING) ?? null;
    $college_location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING) ?? null;
    $college_external = filter_input(INPUT_POST, 'college_external', FILTER_SANITIZE_STRING) ?? null;
    $assignment_status = filter_input(INPUT_POST, 'assignment_status', FILTER_SANITIZE_STRING) ?? null;
    $occupied_status = filter_input(INPUT_POST, 'occupied_status', FILTER_SANITIZE_STRING) ?? null;
    $election = filter_input(INPUT_POST, 'election', FILTER_SANITIZE_STRING) ?? null;
    $status = "active";

    $max_capacity = filter_input(INPUT_POST, 'max_capacity', FILTER_VALIDATE_INT);

    $timestamp = date('Y-m-d H:i:s');

    // Validate required fields
    if (empty($id)) {
        throw new Exception('Precinct ID is required');
    }
    if (empty($name)) {
        throw new Exception('Precinct name is required');
    }



    // Start transaction
    $pdo->beginTransaction();

    // Check if precinct exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM precincts WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() == 0) {
        throw new Exception('Precinct does not exist');
    }

    // Build the UPDATE query dynamically based on provided fields
    $updates = [];
    $params = [];


    if ($name !== null) {
        $updates[] = "name = ?";
        $params[] = $name;
    }
    if ($longitude !== null) {
        $updates[] = "longitude = ?";
        $params[] = $longitude;
    }
    if ($latitude !== null) {
        $updates[] = "latitude = ?";
        $params[] = $latitude;
    }
    if ($location !== null) {
        $updates[] = "location = ?";
        $params[] = $type === 'External' ? $college_location : $location;
    }

    if ($college !== null) {
        $updates[] = "college = ?";
        $params[] = $college;
    }
    if ($department !== null) {
        $updates[] = "department = ?";
        $params[] = $department;
    }
    if ($type !== null) {
        $updates[] = "type = ?";
        $params[] = $type;
    }
    if ($college_external !== null) {
        $updates[] = "college_external = ?";
        $params[] = $college_external;
    }
    if ($assignment_status !== null) {
        $updates[] = "assignment_status = ?";
        $params[] = $assignment_status;
    }
    if ($occupied_status !== null) {
        $updates[] = "occupied_status = ?";
        $params[] = $occupied_status;
    }
    if ($election !== null) {
        $updates[] = "election = ?";
        $params[] = $election;
    }
    if ($max_capacity !== null) {
        $updates[] = "max_capacity = ?";
        $params[] = $max_capacity;
    }
    if ($status !== null) {
        $updates[] = "status = ?";
        $params[] = $status;
    }

    // Always update the updated_at timestamp
    $updates[] = "updated_at = ?";
    $params[] = $timestamp;

    // Add the id parameter for the WHERE clause
    $params[] = $id;

    // If there are fields to update, execute the UPDATE query
    if (!empty($updates)) {
        $query = "UPDATE precincts SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
    }

    // Handle election association
    if ($election !== null) {

        // Delete existing election association for this precinct
        $stmt = $pdo->prepare("
        DELETE FROM precinct_elections 
        WHERE precinct_id = ?
    ");
        $stmt->execute([$id]);

        // Insert new election association if provided
        if (!empty($election)) {
            $stmt = $pdo->prepare("
            INSERT INTO precinct_elections (precinct_id, precinct_name, election_name)
            VALUES (?, ?, ?)
        ");
            $stmt->execute([$id, $name, $election]);
        }
    }


    // Commit transaction
    $pdo->commit();

    // Send success response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Precinct updated successfully'
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Send error response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
