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

// 1. Verify session
if (!isset($_SESSION['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'No user email found in session. Please log in.']);
    exit;
}

// 2. Validate POST input
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['student_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request: student_id is required.']);
    exit;
}

$student_id   = trim($_POST['student_id']);
$votingPeriodId = isset($_POST['election_id']) ? (int)$_POST['election_id'] : null;
$electionId     = $votingPeriodId; // alias kept for QR serial / log

if (!$votingPeriodId) {
    echo json_encode(['status' => 'error', 'message' => 'Voting period ID missing']);
    exit;
}

// 3. Fetch adviser info (single query — no duplicate fetch)
try {
    $stmt = $pdo->prepare("
        SELECT id, college_id, department_id, wmsu_campus_id, external_campus_id, year_level
        FROM advisers
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['email']]);
    $adviser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$adviser || empty($adviser['college_id']) || empty($adviser['department_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Adviser not found or missing college/department.']);
        exit;
    }

    $adviser_id         = $adviser['id'];
    $adviser_college    = $adviser['college_id'];
    $adviser_department = $adviser['department_id'];
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error fetching adviser data: ' . $e->getMessage()]);
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

// 5. Fetch SMTP credentials
try {
    $stmt = $pdo->prepare("SELECT id, email, app_password FROM email WHERE adviser_id = ?");
    $stmt->execute([$adviser_id]);
    $emailConfig = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emailConfig || empty($emailConfig['email']) || empty($emailConfig['app_password'])) {
        echo json_encode(['status' => 'error', 'message' => 'No valid email configuration found for adviser.']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error fetching email configuration: ' . $e->getMessage()]);
    exit;
}

// 6. Fetch voter and verify college/department
try {
    $stmt = $pdo->prepare("SELECT * FROM voters WHERE student_id = ? LIMIT 1");
    $stmt->execute([$student_id]);
    $voter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voter || empty($voter['email'])) {
        echo json_encode(['status' => 'error', 'message' => 'No valid voter found for student ID ' . htmlspecialchars($student_id)]);
        exit;
    }

    if (
        strtolower($voter['college'])    !== strtolower($adviser_college) ||
        strtolower($voter['department']) !== strtolower($adviser_department)
    ) {
        echo json_encode(['status' => 'error', 'message' => "Voter does not belong to adviser's college and department."]);
        exit;
    }

    $email = $voter['email'];
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error fetching voter data: ' . $e->getMessage()]);
    exit;
}

// 7. Fetch precinct details — initialise defaults so variables are always defined
$precinctName             = 'Not Assigned';
$precinctLocation         = 'N/A';
$precinctCollege          = 'N/A';
$precinctDepartment       = 'N/A';
$campusType               = 'Main Campus';
$precinctStatus           = 'N/A';
$precinctCapacity         = '0 / 0';
$precinctAssignmentStatus = 'N/A';
$precinctOccupiedStatus   = 'N/A';

try {
    $stmt = $pdo->prepare("SELECT precinct FROM precinct_voters WHERE student_id = ? LIMIT 1");
    $stmt->execute([$student_id]);
    $pvRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pvRow && !empty($pvRow['precinct'])) {
        $precinctId = $pvRow['precinct'];

        $stmt = $pdo->prepare("
            SELECT
                p.id,
                p.name     AS precinct_name,
                p.location AS precinct_location,
                COALESCE(c.college_name, p.college_external) AS precinct_college,
                p.college_external,
                d.department_name AS precinct_department,
                cpc.campus_name   AS precinct_type_name,
                p.status          AS precinct_status,
                p.current_capacity,
                p.max_capacity,
                p.assignment_status,
                p.occupied_status
            FROM precincts p
            LEFT JOIN colleges    c   ON p.college    = c.college_id
            LEFT JOIN departments d   ON p.department = d.department_id
            LEFT JOIN campuses    cpc ON p.type        = cpc.campus_id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$precinctId]);
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
    }
} catch (PDOException $e) {
    error_log("Error fetching precinct details: " . $e->getMessage());
}

// 8. Generate QR code
$tempDir = __DIR__ . '/../qrcodes/';
if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to create QR code directory']);
    exit;
}

$qrCodeSerial = $voter['student_id'] . '_WMSU_ELEC_' . $electionId;
$qrMd5        = md5($qrCodeSerial) . '.png';
$qrFilePath   = $tempDir . $qrMd5;

$options = new QROptions([
    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
    'eccLevel'   => QRCode::ECC_H,
]);

try {
    (new QRCode($options))->render($qrCodeSerial, $qrFilePath);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'QR Code generation failed: ' . $e->getMessage()]);
    exit;
}

// 9. Send email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $emailConfig['email'];
    $mail->Password   = $emailConfig['app_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->SMTPDebug  = 2;
    $mail->Debugoutput = function ($str, $level) {
        error_log("SMTP: $str");
    };

    // Use __DIR__ so paths resolve correctly regardless of CWD
    $mail->addEmbeddedImage(__DIR__ . '/images/logo-left.png',  'logo_left');
    $mail->addEmbeddedImage(__DIR__ . '/images/logo-right.png', 'logo_right');
    $mail->addEmbeddedImage(__DIR__ . '/images/banner.png',     'banner_bottom');

    $mail->setFrom($emailConfig['email'], 'WMSU - Student Affairs');
    $mail->addAddress($email);
    $mail->addAttachment($qrFilePath, 'qr_code.png');
    $mail->isHTML(true);
    $mail->Subject = "WMSU General Election's QR Code";

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

    <p><strong>Precinct Information</strong></p>
    Precinct Name: <strong>{$precinctName}</strong><br>
    Location: <strong>{$precinctLocation}</strong><br>
    College: <strong>{$precinctCollege}</strong><br>
    Department: <strong>{$precinctDepartment}</strong><br>
    Campus Type: <strong>{$campusType}</strong><br>

    <p>Your QR code is attached to this email. Kindly present it at your assigned precinct during the voting period.</p>

    <p>Your participation is highly appreciated.</p>

    <br>
    <p>Respectfully,<br><strong>Election Committee</strong></p>

    <hr style='border:none; border-top:1px solid #ddd; margin:20px 0;'>
    <div style='text-align:center;'><img src='cid:banner_bottom' style='max-width:100%; height:auto;'></div>

</div>
";

    $mail->send();

    // 10. Log QR send
    try {
        $stmt = $pdo->prepare("
            INSERT INTO qr_sending_log (email, student_id, election_id, qr_img, status, sent_at, notes)
            VALUES (:email, :student_id, :election_id, :qr_img, :status, NOW(), :notes)
        ");
        $stmt->execute([
            ':email'      => $_SESSION['email'],
            ':student_id' => $student_id,
            ':election_id' => $electionId,
            ':qr_img'     => $qrMd5,
            ':status'     => 'sent',
            ':notes'      => 'QR sent successfully',
        ]);
    } catch (PDOException $e) {
        error_log("QR Log Insert Error: " . $e->getMessage());
    }

    // 11. Update email capacity
    try {
        $pdo->prepare("UPDATE email SET capacity = capacity + 1 WHERE id = ?")
            ->execute([$emailConfig['id']]);
    } catch (PDOException $e) {
        error_log("Capacity Update Error: " . $e->getMessage());
    }

    echo json_encode(['status' => 'success', 'message' => 'QR Code sent successfully to ' . htmlspecialchars($email)]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Mail Error: ' . $e->getMessage()]);
}
exit;
