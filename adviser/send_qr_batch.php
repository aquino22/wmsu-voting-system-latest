<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once '../includes/conn.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

session_start();

// 1. Validate session
if (
    !isset($_SESSION['email']) ||
    !isset($_SESSION['role'])  ||
    $_SESSION['role'] !== 'adviser'
) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// 2. Validate input
$votingPeriodId = isset($_POST['election_id']) ? (int)$_POST['election_id'] : null;
if (!$votingPeriodId) {
    echo json_encode(['status' => 'error', 'message' => 'Voting period ID missing']);
    exit;
}

// 3. Fetch adviser info
$stmt = $pdo->prepare("
    SELECT id, college_id, department_id, wmsu_campus_id, external_campus_id, year_level
    FROM advisers
    WHERE email = ?
    LIMIT 1
");
$stmt->execute([$_SESSION['email']]);
$adviser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adviser) {
    echo json_encode(['status' => 'error', 'message' => 'Adviser not found']);
    exit;
}

// 4. Fetch voting period + election + academic year
$stmt = $pdo->prepare("
    SELECT
        vp.id,
        vp.start_period,
        vp.end_period,
        vp.re_start_period,
        vp.re_end_period,
        vp.status,
        e.election_name,
        ay.semester,
        ay.start_date AS school_year_start,
        ay.end_date   AS school_year_end
    FROM voting_periods vp
    JOIN elections e    ON vp.election_id      = e.id
    JOIN academic_years ay ON e.academic_year_id = ay.id
    WHERE vp.id = ?
    LIMIT 1
");
$stmt->execute([$votingPeriodId]);
$votingPeriod = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$votingPeriod) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid voting period ID']);
    exit;
}

// 5. Fetch SMTP accounts
$stmt = $pdo->prepare("SELECT id, email, app_password, capacity FROM email WHERE adviser_id = ?");
$stmt->execute([$adviser['id']]);
$emailConfigs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($emailConfigs)) {
    echo json_encode(['status' => 'error', 'message' => 'No email configurations found']);
    exit;
}

// 6. Fetch voters under adviser
$params = [
    $adviser['college_id'],
    $adviser['department_id'],
    $adviser['wmsu_campus_id'],
];

if ($adviser['external_campus_id'] === 'None' || is_null($adviser['external_campus_id'])) {
    $query = "
        SELECT student_id, email
        FROM voters
        WHERE college    = ?
          AND department = ?
          AND wmsu_campus = ?
          AND (external_campus IS NULL OR external_campus = 'None')
          AND year_level = ?
          AND status = 'confirmed'
    ";
    $params[] = $adviser['year_level'];
} else {
    $query = "
        SELECT student_id, email
        FROM voters
        WHERE college    = ?
          AND department = ?
          AND wmsu_campus = ?
          AND external_campus = ?
          AND year_level = ?
          AND status = 'confirmed'
    ";
    $params[] = $adviser['external_campus_id'];
    $params[] = $adviser['year_level'];
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($voters)) {
    echo json_encode(['status' => 'error', 'message' => 'No voters found']);
    exit;
}

// 7. Batch controls
$batchSize = isset($_POST['limit'])  ? (int)$_POST['limit']  : 1000;
$offset    = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;

$batchVoters = array_slice($voters, $offset, $batchSize);
$results     = [];

// Track in-memory capacity so we don't re-query the DB on every iteration
// but still reflect increments made during this request
$capacityMap = [];
foreach ($emailConfigs as $cfg) {
    $capacityMap[$cfg['id']] = (int)$cfg['capacity'];
}

// 8. Process batch
foreach ($batchVoters as $voter) {

    $qrSerial = $voter['student_id'] . '_WMSU_ELEC_' . $votingPeriodId;
    $qrHash   = md5($qrSerial) . '.png';
    $qrDir    = __DIR__ . '/../qrcodes/';
    $qrPath   = $qrDir . $qrHash;

    // Precinct defaults
    $precinctName             = 'Not Assigned';
    $precinctLocation         = 'N/A';
    $precinctCollege          = 'N/A';
    $precinctDepartment       = 'N/A';
    $campusType               = 'Main Campus';
    $precinctStatus           = 'N/A';
    $precinctCapacity         = '0 / 0';
    $precinctAssignmentStatus = 'N/A';
    $precinctOccupiedStatus   = 'N/A';

    // Fetch assigned precinct
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name     AS precinct_name,
            p.location AS precinct_location,
            COALESCE(c.college_name, p.college_external) AS precinct_college,
            p.college_external,
            d.department_name AS precinct_department,
            COALESCE(cparent.campus_name, cpc.campus_name) AS precinct_type_name,
            p.status          AS precinct_status,
            p.current_capacity,
            p.max_capacity,
            p.assignment_status,
            p.occupied_status
        FROM precinct_voters pv
        JOIN precincts    p   ON pv.precinct   = p.id
        LEFT JOIN colleges    c   ON p.college    = c.college_id
        LEFT JOIN departments d   ON p.department = d.department_id
        LEFT JOIN campuses    cpc ON p.type        = cpc.campus_id
        LEFT JOIN campuses cparent ON cpc.parent_id = cparent.campus_id
        WHERE pv.student_id = ?
        LIMIT 1
    ");
    $stmt->execute([$voter['student_id']]);
    $precinctRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($precinctRow) {
        $precinctName             = $precinctRow['precinct_name'];
        $precinctLocation         = $precinctRow['precinct_location'];
        $precinctCollege          = $precinctRow['precinct_college']    ?? 'N/A';
        $precinctDepartment       = $precinctRow['precinct_department'] ?? 'N/A';
        $precinctStatus           = $precinctRow['precinct_status'];
        $precinctCapacity         = $precinctRow['current_capacity'] . ' / ' . $precinctRow['max_capacity'];
        $precinctAssignmentStatus = $precinctRow['assignment_status'];
        $precinctOccupiedStatus   = $precinctRow['occupied_status'];
        $campusType               = !empty($precinctRow['college_external'])
            ? 'WMSU ESU — ' . $precinctRow['college_external']
            : ($precinctRow['precinct_type_name'] ?? 'Main Campus');
    }

    if (!is_dir($qrDir)) {
        mkdir($qrDir, 0755, true);
    }

    $status = 'failed';
    $notes  = '';

    try {
        // Generate QR
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'   => QRCode::ECC_H,
        ]);
        (new QRCode($options))->render($qrSerial, $qrPath);

        // Pick SMTP account — use in-memory capacity so increments during this
        // batch are respected without extra DB queries
        $smtp   = null;
        $smtpKey = null;
        foreach ($emailConfigs as $idx => $cfg) {
            if ($capacityMap[$cfg['id']] < 500) {
                $smtp    = $cfg;
                $smtpKey = $idx;
                break;
            }
        }

        if (!$smtp) {
            throw new Exception('No available email capacity across all configured accounts');
        }

        // Send email
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['email'];
        $mail->Password   = $smtp['app_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($smtp['email'], 'WMSU - Student Affairs');
        $mail->addAddress($voter['email']);
        $mail->addAttachment($qrPath, 'qr_code.png');
        $mail->isHTML(true);
        $mail->Subject = "WMSU Election QR Code";

        // Use __DIR__ so paths resolve correctly regardless of CWD
        $mail->addEmbeddedImage(__DIR__ . '/images/logo-left.png',  'logo_left');
        $mail->addEmbeddedImage(__DIR__ . '/images/logo-right.png', 'logo_right');
        $mail->addEmbeddedImage(__DIR__ . '/images/banner.png',     'banner_bottom');

        $mail->Body = "
<div style='font-family: Arial, Helvetica, sans-serif; font-size:14px; color:#222; line-height:1.6;'>

    <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:20px;'>
        <tr>
            <td align='left'   width='25%'><img src='cid:logo_left'  style='width:70px; height:auto;'></td>
            <td align='center' width='50%' style='font-size:18px; font-weight:bold;'>WESTERN MINDANAO STATE UNIVERSITY</td>
            <td align='right'  width='25%'><img src='cid:logo_right' style='width:70px; height:auto;'></td>
        </tr>
    </table>

    <hr style='border:none; border-top:1px solid #ddd; margin:20px 0;'>

    <p>Dear Student,</p>

    <p>This is to formally inform you that the voting period for the election indicated below has been scheduled.
    Please review the details carefully and ensure your participation within the designated timeframe.</p>

    <p><strong>Election Details</strong></p>

    Election: <strong>{$votingPeriod['election_name']}</strong><br>
    Semester: <strong>{$votingPeriod['semester']}</strong><br>
    School Year: <strong>{$votingPeriod['school_year_start']} &ndash; {$votingPeriod['school_year_end']}</strong><br>
    Voting Opens: <strong>" . date('F j, Y g:i A', strtotime($votingPeriod['start_period'])) . "</strong><br>
    Voting Closes: <strong>" . date('F j, Y g:i A', strtotime($votingPeriod['end_period'])) . "</strong><br><br>

    <p><strong>Voter Information</strong></p>
    Student ID: <strong>{$voter['student_id']}</strong><br>
    Assigned Precinct: <strong>{$precinctName}</strong><br>
    Precinct Location: <strong>{$precinctLocation}</strong><br>
    College: <strong>{$precinctCollege}</strong><br>
    Department: <strong>{$precinctDepartment}</strong><br>
    Campus Type: <strong>{$campusType}</strong><br><br>

    <p>Your QR code is attached to this email. Kindly present it at your assigned precinct during the voting period.</p>

    <p>Your participation is highly appreciated.</p>

    <br>
    <p>Respectfully,<br><strong>Election Committee</strong></p>

    <hr style='border:none; border-top:1px solid #ddd; margin:20px 0;'>
    <div style='text-align:center;'><img src='cid:banner_bottom' style='max-width:100%; height:auto;'></div>

</div>
";
        $mail->send();

        // Update capacity in DB and in-memory map
        $pdo->prepare("UPDATE email SET capacity = capacity + 1 WHERE id = ?")
            ->execute([$smtp['id']]);
        $capacityMap[$smtp['id']]++;

        $status = 'sent';
        $notes  = 'QR sent successfully';
    } catch (Exception $e) {
        $notes = $e->getMessage();
    }

    // Log attempt
    $pdo->prepare("
        INSERT INTO qr_sending_log
            (email, student_id, election_id, status, notes, qr_img, sent_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ")->execute([
        $_SESSION['email'],
        $voter['student_id'],
        $votingPeriodId,
        $status,
        $notes,
        $qrHash,
    ]);

    $results[] = [
        'student_id' => $voter['student_id'],
        'status'     => $status,
        'message'    => $notes,
    ];
}

// 9. Response
echo json_encode([
    'status'      => 'completed',
    'results'     => $results,
    'offset'      => $offset,
    'limit'       => $batchSize,
    'total'       => count($voters),
    'next_offset' => ($offset + $batchSize) < count($voters)
        ? ($offset + $batchSize)
        : null,
]);
