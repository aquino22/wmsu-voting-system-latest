<?php
session_start();
require '../../includes/conn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$election_id  = intval($_POST['election_id'] ?? 0);
$start_period = $_POST['start_period'] ?? '';
$end_period   = $_POST['end_period'] ?? '';
$status       = $_POST['status'] ?? '';

if (!$election_id || !$start_period || !$end_period || !$status) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

/* =========================
   DATE VALIDATION
========================= */
// try {
//     $startDT = new DateTime($start_period);
//     $endDT   = new DateTime($end_period);

//     if ($startDT >= $endDT) {
//         throw new Exception("End period must be after start period");
//     }
// } catch (Exception $e) {
//     echo json_encode(["success" => false, "message" => $e->getMessage()]);
//     exit;
// }

/* =========================
   FETCH ELECTION (BOUND CHECK)
========================= */
$stmt = $pdo->prepare("
    SELECT start_period, end_period 
    FROM elections 
    WHERE id = :id
");
$stmt->execute([':id' => $election_id]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$election) {
    echo json_encode(["success" => false, "message" => "Election not found"]);
    exit;
}

if ($endDT > new DateTime($election['end_period'])) {
    echo json_encode([
        "success" => false,
        "message" => "Candidacy end period exceeds election period"
    ]);
    exit;
}

/* =========================
   DUPLICATE CHECK
========================= */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM candidacy 
    WHERE election_id = :id
");
$stmt->execute([':id' => $election_id]);

if ($stmt->fetchColumn() > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Candidacy already exists for this election"
    ]);
    exit;
}

/* =========================
   INSERT
========================= */
$stmt = $pdo->prepare("
    INSERT INTO candidacy (
        election_id,
        start_period,
        end_period,
        status,
        created_at,
        updated_at
    ) VALUES (
        :election_id,
        :start_period,
        :end_period,
        :status,
        NOW(),
        NOW()
    )
");

$stmt->execute([
    ':election_id'  => $election_id,
    ':start_period' => $startDT->format('Y-m-d H:i:s'),
    ':end_period'   => $endDT->format('Y-m-d H:i:s'),
    ':status'       => $status
]);

echo json_encode([
    "success" => true,
    "message" => "Candidacy period created successfully"
]);

$pdo = null;
