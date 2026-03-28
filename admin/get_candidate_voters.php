<?php

/**
 * get_candidate_voters.php
 * AJAX endpoint — returns an HTML table of voters who voted for a given candidate.
 * Resolves all ID fields (college, department, course, major, year_level) to human-readable names.
 */

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<p class="text-danger">Unauthorized.</p>';
    exit();
}

include('includes/conn.php');       // Main DB  → $pdo  (has votes table)
$main_pdo = $pdo;
include('includes/conn_archived.php'); // Archive DB → $pdo

$candidate_id     = (int)($_POST['candidate_id']     ?? 0);
$voting_period_id = (int)($_POST['voting_period_id'] ?? 0);

if (!$candidate_id || !$voting_period_id) {
    echo '<p class="text-danger">Invalid parameters.</p>';
    exit();
}

// ----------------------------------------------------------------
// Build lookup maps from archive DB
// ----------------------------------------------------------------

// College map: college_id (string) => college_name
$collegeMap = [];
$stmt = $pdo->query("SELECT college_id, college_name FROM archived_colleges ORDER BY college_name ASC");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $collegeMap[(string)$row['college_id']] = $row['college_name'];
}

// Department map: department_id (string) => department_name
$departmentMap = [];
$stmt = $pdo->query("SELECT department_id, department_name FROM archived_departments ORDER BY department_name ASC");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $departmentMap[(string)$row['department_id']] = $row['department_name'];
}

// Course map: course_id (string) => course_name
$courseMap = [];
$stmt = $pdo->query("SELECT id, course_name FROM archived_courses ORDER BY course_name ASC");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $courseMap[(string)$row['id']] = $row['course_name'];
}

// Major map: major_id (string) => major_name
$majorMap = [];
$stmt = $pdo->query("SELECT major_id, major_name FROM archived_majors ORDER BY major_name ASC");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $majorMap[(string)$row['major_id']] = $row['major_name'];
}

// Year level map: id (string) => description
// year_level in archived_voters stores archived_actual_year_levels.id
// Map that id -> the year_level integer (1-5) -> ordinal label
$actualYearLevelMap = [];
$stmt = $pdo->query("SELECT id, year_level FROM archived_actual_year_levels");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $ord = (int)$row['year_level'];
    $suffix = match ($ord) {
        1 => 'st',
        2 => 'nd',
        3 => 'rd',
        default => 'th'
    };
    $actualYearLevelMap[(string)$row['id']] = $ord . $suffix . ' Year';
}

// ----------------------------------------------------------------
// Fetch student_ids who voted for this candidate from main DB
// ----------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT student_id
    FROM archived_votes
    WHERE candidate_id = ? AND voting_period_id = ?
");
$stmt->execute([$candidate_id, $voting_period_id]);
$voterStudentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($voterStudentIds)) {
    echo '<p class="text-muted text-center mt-3">No voters found for this candidate.</p>';
    exit();
}

// ----------------------------------------------------------------
// Fetch voter details from archive DB using student IDs
// ----------------------------------------------------------------
$placeholders = implode(',', array_fill(0, count($voterStudentIds), '?'));
$stmt = $pdo->prepare("
    SELECT
        student_id,
        first_name,
        middle_name,
        last_name,
        college,
        department,
        course,
        year_level,
        major,
        precinct_name
    FROM archived_voters
    WHERE student_id IN ($placeholders)
      AND voting_period_id = ?
    ORDER BY last_name ASC, first_name ASC
");
$params = array_merge($voterStudentIds, [$voting_period_id]);
$stmt->execute($params);
$voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($voters)) {
    echo '<p class="text-muted text-center mt-3">No voter details available.</p>';
    exit();
}

// ----------------------------------------------------------------
// Resolve each voter's IDs to names, then group by college
// ----------------------------------------------------------------
$groupedByCollege = []; // college_name => [voter_rows]

foreach ($voters as $voter) {
    $ck = (string)trim($voter['college']     ?? '');
    $dk = (string)trim($voter['department']  ?? '');
    $crk = (string)trim($voter['course']     ?? '');
    $yk  = (string)trim($voter['year_level'] ?? '');
    $mk  = (string)trim($voter['major']      ?? '');

    $collegeName    = isset($collegeMap[$ck])     ? $collegeMap[$ck]     : ($ck  !== '' ? $ck  : '—');
    $departmentName = isset($departmentMap[$dk])   ? $departmentMap[$dk]  : ($dk  !== '' ? $dk  : '—');
    $courseName     = isset($courseMap[$crk])      ? $courseMap[$crk]     : ($crk !== '' ? $crk : '—');
    $yearLevelName  = isset($actualYearLevelMap[$yk]) ? $actualYearLevelMap[$yk] : ($yk !== '' ? $yk . ' (raw)' : '—');
    $majorName      = ($mk !== '' && isset($majorMap[$mk])) ? $majorMap[$mk] : ($mk !== '' ? $mk : '—');

    $fullName = trim(
        htmlspecialchars($voter['last_name'])   . ', ' .
            htmlspecialchars($voter['first_name'])  .
            (!empty($voter['middle_name']) ? ' ' . htmlspecialchars($voter['middle_name']) : '')
    );

    $groupedByCollege[$collegeName][] = [
        'student_id'  => htmlspecialchars($voter['student_id']),
        'name'        => $fullName,
        'department'  => $departmentName,
        'course'      => $courseName,
        'year_level'  => $yearLevelName,
        'major'       => $majorName,
        'precinct'    => htmlspecialchars($voter['precinct_name'] ?? '—'),
    ];
}

ksort($groupedByCollege); // Sort colleges alphabetically
?>

<style>
    .voters-modal-table {
        font-size: .82rem;
        border-collapse: collapse;
        width: 100%;
    }

    .voters-modal-table thead tr th {
        background: #6c1a1a !important;
        color: #fff !important;
        border-color: #8B0000 !important;
        font-weight: 600;
        padding: 7px 10px;
        white-space: nowrap;
    }

    .voters-modal-table tbody tr:nth-child(even) {
        background: #fff8f8;
    }

    .voters-modal-table tbody tr:hover {
        background: #fce8e8 !important;
    }

    .voters-modal-table tbody td {
        border-color: #d9b8b8 !important;
        padding: 5px 10px;
        vertical-align: middle;
    }

    .college-group-header {
        background: #B22222;
        color: #fff;
        padding: 7px 14px;
        border-radius: 5px 5px 0 0;
        font-weight: 700;
        font-size: .9rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .college-group-header .badge {
        background: rgba(255, 255, 255, .25) !important;
        font-size: .8rem;
    }
</style>
<div class="mb-2 d-flex align-items-center gap-2">
    <span class="badge" style="background:#B22222;"><?php echo count($voters); ?> voter(s) found</span>
</div>

<?php foreach ($groupedByCollege as $collegeName => $collegeVoters): ?>
    <div class="mb-4">
        <div class="college-group-header mb-0">
            <span><i class="mdi mdi-school me-2"></i><?php echo htmlspecialchars($collegeName); ?></span>
            <span class="badge"><?php echo count($collegeVoters); ?> voter(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0 voters-modal-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Major</th>
                        <th>Year Level</th>
                        <th>Department</th>
                        <th>Precinct</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($collegeVoters as $i => $v): ?>
                        <tr>
                            <td class="text-muted"><?php echo $i + 1; ?></td>
                            <td><?php echo $v['student_id']; ?></td>
                            <td><?php echo $v['name']; ?></td>
                            <td><?php echo $v['course']; ?></td>
                            <td><?php echo $v['major']; ?></td>
                            <td><?php echo $v['year_level']; ?></td>
                            <td><?php echo $v['department']; ?></td>
                            <td><?php echo $v['precinct']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>