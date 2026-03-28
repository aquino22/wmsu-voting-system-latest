```php
<?php
session_start();
require '../../includes/conn.php';

// Set headers for streaming
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Flush output buffer
ob_implicit_flush(true);
ob_end_flush();

// Function to send stream data
function sendStreamData($data) {
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

// Function to log import details
function logImportDetails($pdo, $fileName, $votersAdded, $emailsSent, $adviserEmail, $mismatches) {
    $stmt = $pdo->prepare("
        INSERT INTO adviser_import_details (file, date, status, voters_added, emails_sent, adviser_email, mismatches)
        VALUES (?, NOW(), 'completed', ?, ?, ?, ?)
    ");
    $stmt->execute([$fileName, $votersAdded, $emailsSent, $adviserEmail, json_encode($mismatches)]);
}

try {
    // Validate session
    $adviserEmail = $_SESSION['email'] ?? null;
    $adviserId = $_SESSION['user_id'] ?? null;
    if (!$adviserEmail || !$adviserId) {
        sendStreamData(['status' => 'error', 'message' => 'Adviser not logged in']);
        exit;
    }

    // Path to the CSV file
    $csvFilePath = 'Uploads/student_records_2025.csv';
    if (!file_exists($csvFilePath)) {
        sendStreamData(['status' => 'error', 'message' => 'CSV file not found']);
        exit;
    }

    // Read CSV file
    $file = fopen($csvFilePath, 'r');
    if (!$file) {
        sendStreamData(['status' => 'error', 'message' => 'Failed to open CSV file']);
        exit;
    }

    // Skip header row
    $header = fgetcsv($file);
    $rows = [];
    while (($row = fgetcsv($file)) !== false) {
        $rows[] = array_combine($header, $row);
    }
    fclose($file);

    $totalRows = count($rows);
    $votersAdded = 0;
    $emailsSent = 0;
    $mismatches = [];

    // Begin transaction
    $pdo->beginTransaction();

    // Get adviser year level
    $stmt = $pdo->prepare("SELECT year FROM advisers WHERE id = ?");
    $stmt->execute([$adviserId]);
    $adviserYearLevel = $stmt->fetchColumn();

    if (!$adviserYearLevel) {
        sendStreamData(['status' => 'error', 'message' => 'Adviser year level not found']);
        exit;
    }

    foreach ($rows as $index => $row) {
        // Validate required fields
        if (empty($row['student_id']) || empty($row['email']) || empty($row['first_name']) || empty($row['last_name'])) {
            $mismatches[] = [
                'student_id' => $row['student_id'] ?? 'N/A',
                'reason' => 'Missing required fields'
            ];
            error_log("Skipping CSV row " . ($index + 2) . ": Missing required fields (Student ID: {$row['student_id']}, Email: {$row['email']}, First Name: {$row['first_name']}, Last Name: {$row['last_name']})");
            continue;
        }

        // Validate email format
        if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $mismatches[] = [
                'student_id' => $row['student_id'],
                'reason' => 'Invalid email format'
            ];
            error_log("Skipping CSV row " . ($index + 2) . ": Invalid email: {$row['email']}");
            continue;
        }

        // Validate year level
        if (strtolower(trim($row['year_level'])) !== strtolower(trim($adviserYearLevel))) {
            $mismatches[] = [
                'student_id' => $row['student_id'],
                'reason' => 'Year level mismatch'
            ];
            error_log("Skipping CSV row " . ($index + 2) . ": Year level mismatch (Adviser: $adviserYearLevel, Student: {$row['year_level']})");
            continue;
        }

        // Use provided password (already hashed)
        $password = $row['password'];

        // Insert into users table
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, role, created_at)
            VALUES (?, ?, 'voter', NOW())
            ON DUPLICATE KEY UPDATE password = ?, role = 'voter'
        ");
        $stmt->execute([$row['email'], $password, $password]);

        // Insert into voters table
        $stmt = $pdo->prepare("
            INSERT INTO voters (student_id, email, password, first_name, middle_name, last_name, course, year_level, college, department, wmsu_campus, external_campus)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                first_name = ?, middle_name = ?, last_name = ?, course = ?, year_level = ?, college = ?, department = ?, password = ?, wmsu_campus = ?, external_campus = ?
        ");
        $stmt->execute([
            $row['student_id'],
            $row['email'],
            $password,
            $row['first_name'],
            $row['middle_name'],
            $row['last_name'],
            $row['course'],
            $row['year_level'],
            $row['college'],
            $row['department'],
            $row['wmsu_campus'],
            $row['external_campus'],
            $row['first_name'],
            $row['middle_name'],
            $row['last_name'],
            $row['course'],
            $row['year_level'],
            $row['college'],
            $row['department'],
            $password,
            $row['wmsu_campus'],
            $row['external_campus']
        ]);

        $votersAdded++;
        sendStreamData([
            'status' => 'progress',
            'current' => $votersAdded,
            'total' => $totalRows
        ]);
    }

    // Log import details
    logImportDetails($pdo, 'student_records_2025.csv', $votersAdded, $emailsSent, $adviserEmail, $mismatches);

    // Commit transaction
    $pdo->commit();
    sendStreamData([
        'status' => 'complete',
        'message' => "Imported $votersAdded out of $totalRows voters successfully. Sent $emailsSent emails.",
        'mismatches' => $mismatches
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendStreamData([
        'status' => 'error',
        'message' => 'Import failed: ' . $e->getMessage()
    ]);
    error_log("CSV Import failed: " . $e->getMessage());
}
?>