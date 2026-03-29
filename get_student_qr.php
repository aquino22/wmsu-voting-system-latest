<?php
header('Content-Type: application/json');
require '../includes/conn.php';

$studentId = $_POST['student_id'] ?? null;
$votingPeriodId = $_POST['voting_period_name'] ?? null; // THIS IS ACTUALLY AN ID

if (!$studentId || !$votingPeriodId) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

// 1️⃣ Get Voting Period Name from ID
$stmt = $pdo->prepare("SELECT vp.*, e.election_name, e.academic_year_id
    FROM voting_periods vp
    JOIN elections e ON vp.election_id = e.id
    WHERE vp.id = :id
    LIMIT 1");
$stmt->execute([':id' => $votingPeriodId]);
$vp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vp) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid voting period ID']);
    exit;
}

$votingPeriodName = $vp['election_name']; // REAL NAME

// 2️⃣ Find QR entry
$stmt = $pdo->prepare("
    SELECT qr_img 
    FROM qr_sending_log 
    WHERE student_id = :student_id 
      AND election_id = :election_id
    ORDER BY sent_at DESC
    LIMIT 1
");

$stmt->execute([
    ':student_id' => $studentId,
    ':election_id' => $votingPeriodId
]);

$qr = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$qr || empty($qr['qr_img'])) {
    echo json_encode(['status' => 'error', 'message' => 'QR not found']);
    exit;
}

$filename = $qr['qr_img'];

// 3️⃣ Build Paths
$filePath = __DIR__ . '/../qrcodes/' . $filename;
$urlPath = '../qrcodes/' . $filename;

// 4️⃣ Return JSON
if (file_exists($filePath)) {
    echo json_encode([
        'status' => 'success',
        'qr_url' => $urlPath,
        'voting_period_name' => $votingPeriodName   // ⬅ added here
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'QR file missing on server'
    ]);
}
