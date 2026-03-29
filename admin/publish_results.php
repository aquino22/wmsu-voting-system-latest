<?php
ob_start();
session_start();
require 'includes/conn.php';
require 'includes/archive_conn.php';

$voting_period_id = isset($_GET['voting_period_id']) ? (int)$_GET['voting_period_id'] : null;
if (!$voting_period_id) {
    die("Please provide a voting_period_id via GET parameter.");
}

$academic_year_id = 0;

/**
 * 1️⃣ Fetch voting period, election, and academic year info
 */
$stmt = $pdo->prepare("
    SELECT vp.id AS voting_period_id,
           vp.election_id,
           vp.start_period,
           vp.end_period,
           vp.status AS vp_status,
           e.election_name,
           e.academic_year_id,
           ay.year_label,
           ay.semester,
           ay.start_date AS ay_start,
           ay.end_date AS ay_end
    FROM voting_periods vp
    JOIN elections e ON vp.election_id = e.id
    JOIN academic_years ay ON e.academic_year_id = ay.id
    WHERE vp.id = ?
    LIMIT 1
");
$stmt->execute([$voting_period_id]);
$vpData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vpData) {
    die("Voting period not found: $voting_period_id");
}

$academic_year_id = $vpData['academic_year_id'];
$election_id      = $vpData['election_id'];
$election_name    = $vpData['election_name'];
$voting_period_id = $vpData['voting_period_id'];


// ─────────────────────────────────────────────────────────────────────────────
// HELPER: fetchAcademicYearData
// ─────────────────────────────────────────────────────────────────────────────
function fetchAcademicYearData($pdo, $academic_year_id)
{
    $ayData = [];

    // 1️⃣ Academic Year
    $stmt = $pdo->prepare("
        SELECT
            ay.id AS academic_year_id,
            ay.year_label,
            ay.semester,
            ay.start_date AS ay_start,
            ay.end_date AS ay_end,
            ay.status AS ay_status,
            ay.custom_voter_option
        FROM academic_years ay
        WHERE ay.id = ?
        LIMIT 1
    ");
    $stmt->execute([$academic_year_id]);
    $ayData['academic_year'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2️⃣ Campuses
    $stmt = $pdo->query("SELECT * FROM campuses");
    $ayData['campuses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3️⃣ Colleges + coordinates
    $stmt = $pdo->query("
        SELECT c.*, cc.coordinate_id, cc.campus_id AS cc_campus_id,
               cc.latitude AS cc_latitude, cc.longitude AS cc_longitude
        FROM colleges c
        LEFT JOIN college_coordinates cc ON cc.college_id = c.college_id
    ");
    $ayData['colleges'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4️⃣ Departments
    $stmt = $pdo->query("SELECT * FROM departments");
    $ayData['departments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5️⃣ Year Levels
    $stmt = $pdo->query("SELECT * FROM year_levels");
    $ayData['year_levels'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6️⃣ Actual Year Levels
    $stmt = $pdo->query("SELECT * FROM actual_year_levels");
    $ayData['actual_year_levels'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 7️⃣ Courses + course_year_levels
    $stmt = $pdo->query("SELECT * FROM courses");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($courses as &$course) {
        $stmt2 = $pdo->prepare("SELECT * FROM course_year_levels WHERE course_id = ?");
        $stmt2->execute([$course['id']]);
        $course['year_levels'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    $ayData['courses'] = $courses;

    // 8️⃣ Majors + major_year_levels
    $stmt = $pdo->query("SELECT * FROM majors");
    $majors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($majors as &$major) {
        $stmt2 = $pdo->prepare("SELECT * FROM major_year_levels WHERE major_id = ?");
        $stmt2->execute([$major['major_id']]);
        $major['year_levels'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    $ayData['majors'] = $majors;

    // 9️⃣ Custom voter data (only if enabled)
    if ($ayData['academic_year']['custom_voter_option'] == 1) {

        $stmt = $pdo->prepare("SELECT * FROM voter_columns WHERE academic_year_id = ?");
        $stmt->execute([$academic_year_id]);
        $ayData['voter_columns'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT * FROM voter_custom_fields WHERE academic_year_id = ?");
        $stmt->execute([$academic_year_id]);
        $ayData['voter_custom_fields'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $fieldIds = array_column($ayData['voter_custom_fields'], 'id');
        if (!empty($fieldIds)) {
            $placeholders = implode(',', array_fill(0, count($fieldIds), '?'));

            $stmt = $pdo->prepare("SELECT * FROM voter_custom_files WHERE field_id IN ($placeholders)");
            $stmt->execute($fieldIds);
            $ayData['voter_custom_files'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT * FROM voter_custom_responses WHERE field_id IN ($placeholders)");
            $stmt->execute($fieldIds);
            $ayData['voter_custom_responses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $ayData['voter_custom_files']     = [];
            $ayData['voter_custom_responses'] = [];
        }
    }

    return $ayData;
}


// ─────────────────────────────────────────────────────────────────────────────
// HELPER: archiveAcademicYear
// FIX (Bug 4): Added WHERE id = ? to only archive the current academic year,
//              not all active academic years in the system.
// ─────────────────────────────────────────────────────────────────────────────
function archiveAcademicYear($pdo, $archivePdo, $academic_year_id)
{
    try {
        // BUG FIX: Was "WHERE status != 'archived'" with no id filter —
        // this swept every active academic year on every archive run.
        // Now scoped to only the academic year tied to this election.
        $stmt = $pdo->prepare("
            SELECT *
            FROM academic_years
            WHERE id = ?
              AND status != 'archived'
        ");
        $stmt->execute([$academic_year_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return "No academic years to archive (already archived or not found).";
        }

        $insert = $archivePdo->prepare("
            INSERT INTO archived_academic_years
            (id, year_label, semester, start_date, end_date, status, archived_on, custom_voter_option)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                year_label          = VALUES(year_label),
                semester            = VALUES(semester),
                start_date          = VALUES(start_date),
                end_date            = VALUES(end_date),
                status              = VALUES(status),
                archived_on         = VALUES(archived_on),
                custom_voter_option = VALUES(custom_voter_option)
        ");

        foreach ($rows as $ayData) {
            $insert->execute([
                $ayData['id'],
                $ayData['year_label'],
                $ayData['semester'],
                $ayData['start_date'],
                $ayData['end_date'],
                'archived',
                date('Y-m-d H:i:s'),
                $ayData['custom_voter_option'] ?? null
            ]);

            $pdo->prepare("UPDATE academic_years SET status = 'archived' WHERE id = ?")
                ->execute([$ayData['id']]);
        }

        return count($rows) . " academic year(s) archived successfully.";
    } catch (PDOException $e) {
        return "Error archiving academic years: " . $e->getMessage();
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// HELPER: archiveAcademicInfo
// ─────────────────────────────────────────────────────────────────────────────
function archiveAcademicInfo($archivePdo, $ayData)
{
    $now = date('Y-m-d H:i:s');

    // 1️⃣ Actual Year Levels
    foreach ($ayData['actual_year_levels'] as $ayl) {
        $archivePdo->prepare("
            INSERT INTO archived_actual_year_levels
            (id, course_id, major_id, year_level, archived_on)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                course_id   = VALUES(course_id),
                major_id    = VALUES(major_id),
                year_level  = VALUES(year_level),
                archived_on = VALUES(archived_on)
        ")->execute([
            $ayl['id'],
            $ayl['course_id'],
            $ayl['major_id'],
            $ayl['year_level'],
            $now
        ]);
    }

    // Campuses
    foreach ($ayData['campuses'] as $campus) {
        $archivePdo->prepare("
            INSERT INTO archived_campuses
            (campus_id, parent_id, campus_name, campus_location, campus_type, latitude, longitude)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                parent_id       = VALUES(parent_id),
                campus_name     = VALUES(campus_name),
                campus_location = VALUES(campus_location),
                campus_type     = VALUES(campus_type),
                latitude        = VALUES(latitude),
                longitude       = VALUES(longitude)
        ")->execute([
            $campus['campus_id'],
            $campus['parent_id'],
            $campus['campus_name'],
            $campus['campus_location'],
            $campus['campus_type'],
            $campus['latitude'],
            $campus['longitude']
        ]);
    }

    // 2️⃣ Colleges + coordinates
    foreach ($ayData['colleges'] as $college) {
        $archivePdo->prepare("
            INSERT INTO archived_colleges
            (college_id, college_name, college_abbreviation)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                college_name         = VALUES(college_name),
                college_abbreviation = VALUES(college_abbreviation)
        ")->execute([
            $college['college_id'],
            $college['college_name'],
            $college['college_abbreviation']
        ]);

        if (!empty($college['coordinate_id'])) {
            $archivePdo->prepare("
                INSERT INTO archived_college_coordinates
                (coordinate_id, college_id, campus_id, latitude, longitude)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    college_id = VALUES(college_id),
                    campus_id  = VALUES(campus_id),
                    latitude   = VALUES(latitude),
                    longitude  = VALUES(longitude)
            ")->execute([
                $college['coordinate_id'],
                $college['college_id'],
                $college['cc_campus_id'],
                $college['cc_latitude'],
                $college['cc_longitude']
            ]);
        }
    }

    // 3️⃣ Courses + course year levels
    foreach ($ayData['courses'] as $course) {
        $archivePdo->prepare("
            INSERT INTO archived_courses
            (id, college_id, course_name, course_code)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                college_id  = VALUES(college_id),
                course_name = VALUES(course_name),
                course_code = VALUES(course_code)
        ")->execute([
            $course['id'],
            $course['college_id'],
            $course['course_name'],
            $course['course_code']
        ]);

        foreach ($course['year_levels'] as $cyl) {
            $archivePdo->prepare("
                INSERT INTO archived_course_year_levels
                (id, course_id, year_level_id)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    course_id     = VALUES(course_id),
                    year_level_id = VALUES(year_level_id)
            ")->execute([
                $cyl['id'],
                $cyl['course_id'],
                $cyl['year_level_id']
            ]);
        }
    }

    // 4️⃣ Departments
    foreach ($ayData['departments'] as $dept) {
        $archivePdo->prepare("
            INSERT INTO archived_departments
            (department_id, department_name, college_id)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                department_name = VALUES(department_name),
                college_id      = VALUES(college_id)
        ")->execute([
            $dept['department_id'],
            $dept['department_name'],
            $dept['college_id']
        ]);
    }

    // 5️⃣ Majors + major year levels
    foreach ($ayData['majors'] as $major) {
        $archivePdo->prepare("
            INSERT INTO archived_majors
            (major_id, major_name, course_id)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                major_name = VALUES(major_name),
                course_id  = VALUES(course_id)
        ")->execute([
            $major['major_id'],
            $major['major_name'],
            $major['course_id']
        ]);

        foreach ($major['year_levels'] as $myl) {
            $archivePdo->prepare("
                INSERT INTO archived_major_year_levels
                (id, major_id, year_level_id)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    major_id      = VALUES(major_id),
                    year_level_id = VALUES(year_level_id)
            ")->execute([
                $myl['id'],
                $myl['major_id'],
                $myl['year_level_id']
            ]);
        }
    }

    // 6️⃣ Custom voter data (if enabled)
    if ($ayData['academic_year']['custom_voter_option'] == 1) {

        foreach ($ayData['voter_columns'] as $vc) {
            $archivePdo->prepare("
                INSERT INTO archived_voters_columns
                (id, academic_year_id, number)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    number = VALUES(number)
            ")->execute([
                $vc['id'],
                $vc['academic_year_id'],
                $vc['number']
            ]);
        }

        foreach ($ayData['voter_custom_fields'] as $vcf) {
            $archivePdo->prepare("
                INSERT INTO archived_voters_custom_fields
                (id, academic_year_id, field_label, field_order, field_type, is_required,
                 options, field_description, field_sample, sort_order, is_visible, column_width)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    field_label       = VALUES(field_label),
                    field_order       = VALUES(field_order),
                    field_type        = VALUES(field_type),
                    is_required       = VALUES(is_required),
                    options           = VALUES(options),
                    field_description = VALUES(field_description),
                    field_sample      = VALUES(field_sample),
                    sort_order        = VALUES(sort_order),
                    is_visible        = VALUES(is_visible),
                    column_width      = VALUES(column_width)
            ")->execute([
                $vcf['id'],
                $vcf['academic_year_id'],
                $vcf['field_label'],
                $vcf['field_order'],
                $vcf['field_type'],
                $vcf['is_required'],
                $vcf['options'],
                $vcf['field_description'],
                $vcf['field_sample'],
                $vcf['sort_order'],
                $vcf['is_visible'],
                $vcf['column_width']
            ]);
        }

        foreach ($ayData['voter_custom_files'] as $vcf) {
            $archivePdo->prepare("
                INSERT INTO archived_voters_custom_files
                (id, voter_id, field_id, file_path, uploaded_at, archived_on)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    file_path   = VALUES(file_path),
                    uploaded_at = VALUES(uploaded_at),
                    archived_on = VALUES(archived_on)
            ")->execute([
                $vcf['id'],
                $vcf['voter_id'],
                $vcf['field_id'],
                $vcf['file_path'],
                $vcf['uploaded_at'],
                $now
            ]);
        }

        foreach ($ayData['voter_custom_responses'] as $vcr) {
            $archivePdo->prepare("
                INSERT INTO archived_voters_custom_responses
                (id, voter_id, field_id, field_value, created_at, archived_on)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    field_value = VALUES(field_value),
                    created_at  = VALUES(created_at),
                    archived_on = VALUES(archived_on)
            ")->execute([
                $vcr['id'],
                $vcr['voter_id'],
                $vcr['field_id'],
                $vcr['field_value'],
                $vcr['created_at'],
                $now
            ]);
        }
    }

    // 7️⃣ Year Levels
    foreach ($ayData['year_levels'] as $yl) {
        $archivePdo->prepare("
            INSERT INTO archived_year_levels
            (id, level, description)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                level       = VALUES(level),
                description = VALUES(description)
        ")->execute([
            $yl['id'],
            $yl['level'],
            $yl['description']
        ]);
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// HELPER: archiveVotingPeriod
// ─────────────────────────────────────────────────────────────────────────────
function archiveVotingPeriod($archivePdo, $vpData)
{
    $stmt = $archivePdo->prepare("
        INSERT INTO archived_voting_periods
        (id, election_id, start_period, end_period, re_start_period, re_end_period, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            election_id      = VALUES(election_id),
            start_period     = VALUES(start_period),
            end_period       = VALUES(end_period),
            re_start_period  = VALUES(re_start_period),
            re_end_period    = VALUES(re_end_period),
            status           = VALUES(status),
            created_at       = VALUES(created_at)
    ");

    $stmt->execute([
        $vpData['voting_period_id'],
        $vpData['election_id'],
        $vpData['start_period'],
        $vpData['end_period'],
        $vpData['re_start_period'],
        $vpData['re_end_period'],
        'archived',
        date('Y-m-d H:i:s')
    ]);

    return "Archived voting period: {$vpData['voting_period_id']}";
}

// ─────────────────────────────────────────────────────────────────────────────
// HELPER: archiveElection
// ─────────────────────────────────────────────────────────────────────────────
function archiveElection($pdo, $archivePdo, $election_id, $voting_period_id)
{
    $stmt = $pdo->prepare("
        SELECT
            e.id,
            e.academic_year_id,
            e.election_name,
            e.start_period,
            e.end_period,
            ay.semester,
            ay.start_date AS school_year_start,
            ay.end_date   AS school_year_end
        FROM elections e
        JOIN academic_years ay ON e.academic_year_id = ay.id
        WHERE e.id = ?
        LIMIT 1
    ");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$election) return "Election not found";

    $archivePdo->prepare("
        INSERT INTO archived_elections
        (id, academic_year_id, election_name, semester, school_year_start, school_year_end,
         start_period, end_period, voting_period_id, status, archived_on)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            academic_year_id  = VALUES(academic_year_id),
            election_name     = VALUES(election_name),
            semester          = VALUES(semester),
            school_year_start = VALUES(school_year_start),
            school_year_end   = VALUES(school_year_end),
            start_period      = VALUES(start_period),
            end_period        = VALUES(end_period),
            voting_period_id  = VALUES(voting_period_id),
            status            = VALUES(status),
            archived_on       = VALUES(archived_on)
    ")->execute([
        $election['id'],
        $election['academic_year_id'],
        $election['election_name'],
        $election['semester'],
        $election['school_year_start'],
        $election['school_year_end'],
        $election['start_period'],
        $election['end_period'],
        $voting_period_id,
        'archived',
        date('Y-m-d H:i:s')
    ]);

    return "Archived election: {$election['election_name']}";
}


// ─────────────────────────────────────────────────────────────────────────────
// HELPER: archivePositions
// ─────────────────────────────────────────────────────────────────────────────
function archivePositions($pdo, $archivePdo, $election_id)
{
    $stmt = $pdo->prepare("SELECT * FROM positions WHERE election_id = ?");
    $stmt->execute([$election_id]);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$positions) return "No positions found";

    foreach ($positions as $pos) {
        $archivePdo->prepare("
            INSERT INTO archived_positions
            (name, party, level, election_id, allowed_colleges, allowed_departments, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                name                 = VALUES(name),
                party                = VALUES(party),
                level                = VALUES(level),
                election_id          = VALUES(election_id),
                allowed_colleges     = VALUES(allowed_colleges),
                allowed_departments  = VALUES(allowed_departments),
                created_at           = VALUES(created_at)
        ")->execute([
            $pos['name'],
            $pos['party'],
            $pos['level'],
            $pos['election_id'],
            $pos['allowed_colleges']    ?? null,
            $pos['allowed_departments'] ?? null,
            $pos['created_at']
        ]);
    }

    return "Archived " . count($positions) . " positions";
}


// ─────────────────────────────────────────────────────────────────────────────
// HELPER: archiveCandidates
// ─────────────────────────────────────────────────────────────────────────────
function archiveCandidates($pdo, $archivePdo, $election_id, $voting_period_id)
{
    $stmt = $pdo->prepare("
        SELECT c.id, rf.election_name,
               MAX(CASE WHEN ff.field_name = 'full_name'  THEN cr.value END) AS full_name,
               MAX(CASE WHEN ff.field_name = 'position'   THEN cr.value END) AS position,
               MAX(CASE WHEN ff.field_name = 'party'      THEN cr.value END) AS party,
               MAX(CASE WHEN ff.field_name = 'student_id' THEN cr.value END) AS student_id,
               c.created_at AS filed_on
        FROM candidates c
        JOIN registration_forms rf ON c.form_id = rf.id
        LEFT JOIN candidate_responses cr ON cr.candidate_id = c.id
        LEFT JOIN form_fields ff ON cr.field_id = ff.id
        WHERE rf.election_name = ?
        GROUP BY c.id
    ");
    $stmt->execute([$election_id]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$candidates) return "No candidates found";

    $processedCandidates = [];
    $groupMaxVotes       = [];

    foreach ($candidates as $cand) {
        $stmtLevel = $pdo->prepare("SELECT level FROM positions WHERE name = ? LIMIT 1");
        $stmtLevel->execute([$cand['position']]);
        $level = $stmtLevel->fetchColumn();

        $stmtCollege = $pdo->prepare("SELECT college FROM voters WHERE student_id = ? LIMIT 1");
        $stmtCollege->execute([$cand['student_id']]);
        $college = $stmtCollege->fetchColumn();

        $stmtPic = $pdo->prepare("SELECT file_path FROM candidate_files WHERE candidate_id = ? LIMIT 1");
        $stmtPic->execute([$cand['id']]);
        $picPath      = $stmtPic->fetchColumn();
        $picture_path = $picPath ? basename($picPath) : '';

        $stmt2 = $pdo->prepare("
            SELECT
                COUNT(DISTINCT v.id) AS total,
                SUM(CASE WHEN p.type = 'Main Campus' THEN 1 ELSE 0 END) AS internal,
                SUM(CASE WHEN p.type != 'Main Campus' THEN 1 ELSE 0 END) AS external
            FROM votes v
            LEFT JOIN precinct_voters pv ON v.student_id = pv.student_id
            LEFT JOIN precincts p ON pv.precinct = p.id
            WHERE v.candidate_id = ? AND v.voting_period_id = ?
        ");
        $stmt2->execute([$cand['id'], $voting_period_id]);
        $voteStats = $stmt2->fetch(PDO::FETCH_ASSOC);

        $cand['level']        = $level;
        $cand['college']      = $college;
        $cand['picture_path'] = $picture_path;
        $cand['votes']        = (int)$voteStats['total'];
        $cand['internal']     = (int)($voteStats['internal'] ?? 0);
        $cand['external']     = (int)($voteStats['external'] ?? 0);

        $processedCandidates[] = $cand;

        $groupKey = $level . '|' . $cand['position'];
        $groupKey .= ($level !== 'Central') ? '|' . $college : '|All';

        if (!isset($groupMaxVotes[$groupKey])) {
            $groupMaxVotes[$groupKey] = 0;
        }
        if ($cand['votes'] > $groupMaxVotes[$groupKey]) {
            $groupMaxVotes[$groupKey] = $cand['votes'];
        }
    }

    foreach ($processedCandidates as $cand) {
        $groupKey = $cand['level'] . '|' . $cand['position'];
        $groupKey .= ($cand['level'] !== 'Central') ? '|' . $cand['college'] : '|All';

        $maxVotes = $groupMaxVotes[$groupKey];
        $outcome  = ($cand['votes'] > 0 && $cand['votes'] == $maxVotes) ? 'Won' : 'Lost';

        $archivePdo->prepare("
            INSERT INTO archived_candidates
            (original_id, election_name, candidate_name, position, party, filed_on,
             outcome, votes_received, archived_on, voting_period_id, college, level,
             internal_votes, external_votes, picture_path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                candidate_name   = VALUES(candidate_name),
                position         = VALUES(position),
                party            = VALUES(party),
                filed_on         = VALUES(filed_on),
                outcome          = VALUES(outcome),
                votes_received   = VALUES(votes_received),
                archived_on      = VALUES(archived_on),
                college          = VALUES(college),
                level            = VALUES(level),
                internal_votes   = VALUES(internal_votes),
                external_votes   = VALUES(external_votes),
                picture_path     = VALUES(picture_path)
        ")->execute([
            $cand['id'],
            $cand['election_name'],
            $cand['full_name'],
            $cand['position'],
            $cand['party'],
            $cand['filed_on'],
            $outcome,
            $cand['votes'],
            date('Y-m-d H:i:s'),
            $voting_period_id,
            $cand['college'],
            $cand['level'],
            $cand['internal'],
            $cand['external'],
            $cand['picture_path']
        ]);
    }

    return "Archived " . count($processedCandidates) . " candidates";
}


// ─────────────────────────────────────────────────────────────────────────────
// HELPER: archiveCandidacyData
// ─────────────────────────────────────────────────────────────────────────────
function archiveCandidacyData($pdo, $archivePdo, $election_id, $voting_period_id)
{
    echo $election_id . " - " . $voting_period_id . "\n";

    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.start_period, c.end_period, c.status,
                   e.election_name,
                   ay.semester,
                   ay.start_date AS school_year_start,
                   ay.end_date   AS school_year_end
            FROM candidacy c
            JOIN elections e ON c.election_id = e.id
            JOIN academic_years ay ON e.academic_year_id = ay.id
            WHERE c.election_id = ?
        ");
        $stmt->execute([$election_id]);
        $candidacy = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$candidacy) {
            return "No candidacy found for election ID: $election_id";
        }

        $stmtCount = $pdo->prepare("
            SELECT COUNT(*)
            FROM candidates ca
            JOIN registration_forms rf ON ca.form_id = rf.id
            WHERE rf.election_name = ?
        ");
        $stmtCount->execute([$election_id]);
        $totalFiled = (int)$stmtCount->fetchColumn();

        $archivePdo->prepare("
            INSERT INTO archived_candidacies
            (id, election_id, semester, school_year_start, school_year_end,
             start_period, end_period, total_filed, archived_on, status, voting_period_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                election_id       = VALUES(election_id),
                semester          = VALUES(semester),
                school_year_start = VALUES(school_year_start),
                school_year_end   = VALUES(school_year_end),
                start_period      = VALUES(start_period),
                end_period        = VALUES(end_period),
                total_filed       = VALUES(total_filed),
                archived_on       = VALUES(archived_on),
                status            = VALUES(status),
                voting_period_id  = VALUES(voting_period_id)
        ")->execute([
            $candidacy['id'],
            $election_id,
            $candidacy['semester'],
            $candidacy['school_year_start'],
            $candidacy['school_year_end'],
            $candidacy['start_period'],
            $candidacy['end_period'],
            $totalFiled,
            date('Y-m-d H:i:s'),
            'archived',
            $voting_period_id
        ]);

        return "Archived candidacy: {$candidacy['election_name']} (ID: {$candidacy['id']})";
    } catch (PDOException $e) {
        return "Error archiving candidacy: " . $e->getMessage();
    }
}


// ─────────────────────────────────────────────────────────────────────────────
// HELPER: archiveParties
// ─────────────────────────────────────────────────────────────────────────────
function archiveParties($pdo, $archivePdo, $election_id, $voting_period_id)
{
    $stmt = $pdo->prepare("SELECT * FROM parties WHERE election_id = ?");
    $stmt->execute([$election_id]);
    $parties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$parties) return "No parties found";

    foreach ($parties as $party) {
        $archivePdo->prepare("
            INSERT INTO archived_parties
            (id, name, election_id, party_image, platforms, created_at, updated_at, status, archived_on, voting_period_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                name        = VALUES(name),
                platforms   = VALUES(platforms),
                updated_at  = VALUES(updated_at),
                status      = VALUES(status),
                archived_on = VALUES(archived_on)
        ")->execute([
            $party['id'],
            $party['name'],
            $party['election_id'],
            $party['party_image'],
            $party['platforms'],
            $party['created_at'],
            $party['updated_at'],
            'archived',
            date('Y-m-d H:i:s'),
            $voting_period_id
        ]);
    }

    return "Archived " . count($parties) . " parties";
}


// ─────────────────────────────────────────────────────────────────────────────
// HELPER: archiveVoters
// FIX (Bug 1 & 3): Must run BEFORE archivePrecincts, which deletes
//                  precinct_voters rows this function depends on.
// FIX (Bug 3):     Removed "OR pv.student_id IS NULL" — that catch-all
//                  pulled in voters from ALL elections, not just the current
//                  one, causing cross-contamination on repeated archive runs.
// ─────────────────────────────────────────────────────────────────────────────
function archiveVoters($pdo, $archivePdo, $election_id, $voting_period_id)
{
    echo "ELID:" . $election_id . "<br>";

    $stmt = $pdo->prepare("
        SELECT v.*,
               pv.precinct    AS precinct_id,
               p.name         AS precinct_name,
               p.type         AS precinct_type,
               pv.status      AS pv_status
        FROM voters v
        LEFT JOIN precinct_voters    pv  ON v.student_id   = pv.student_id
        LEFT JOIN precincts          p   ON pv.precinct    = p.id
        LEFT JOIN precinct_elections pe  ON pe.precinct_id = p.id
        WHERE pe.election_name = ? AND pe.archived = 0
    ");
    $stmt->execute([$election_id]);
    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$voters) return "No voters found";

    foreach ($voters as $voter) {
        $has_voted = (isset($voter['pv_status']) && strtolower($voter['pv_status']) === 'voted') ? 1 : 0;

        // INSERT IGNORE: if this voter is already archived, skip — never overwrite.
        $archivePdo->prepare("
            INSERT INTO archived_voters
            (student_id, email, password, first_name, middle_name, last_name,
             course, year_level, college, department, major, election_name,
             voting_period_id, archived_on, status, precinct_name, precinct_type,
             wmsu_campus, external_campus, has_voted)
            VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $voter['student_id'],
            $voter['email'],
            $voter['password'],
            $voter['first_name'],
            $voter['middle_name'],
            $voter['last_name'],
            $voter['course'],
            $voter['year_level'],
            $voter['college'],
            $voter['department'],
            $voter['major'],
            $election_id,
            $voting_period_id,
            date('Y-m-d H:i:s'),
            'archived',
            $voter['precinct_name'],
            $voter['precinct_type'],
            $voter['wmsu_campus'],
            $voter['external_campus'],
            $has_voted
        ]);
    }

    try {
        $stmt = $pdo->prepare("UPDATE voters SET status = ? WHERE status != ?");
        $stmt->execute(['archived', 'archived']);
        echo "Voters updated to 'archived'. Rows affected: " . $stmt->rowCount();
    } catch (PDOException $e) {
        echo "Error updating voters: " . $e->getMessage();
    }

    return "Archived " . count($voters) . " voters";
}


// ─────────────────────────────────────────────────────────────────────────────
// HELPER: archivePrecincts
// FIX (Bug 1): Moved to run AFTER archiveVoters. This function deletes
//              precinct_voters rows, which archiveVoters depends on for
//              precinct_name, precinct_type, and has_voted data.
// ─────────────────────────────────────────────────────────────────────────────
function archivePrecincts($pdo, $archivePdo, $election_id, $voting_period_id)
{
    $stmt = $pdo->prepare("
        SELECT p.*, pe.election_name AS pe_election_name,
               pe.id AS pe_id, pe.assigned_at AS pe_assigned_at
        FROM precincts p
        JOIN precinct_elections pe ON p.id = pe.precinct_id
        WHERE pe.election_name = ?
          AND pe.archived      = 0
    ");
    $stmt->execute([$election_id]);
    $precincts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$precincts) return "No precincts found";

    $archivedAt   = date('Y-m-d H:i:s');
    $precinctIds  = array_column($precincts, 'id');

    // ── 1. Archive precinct_voters BEFORE deleting them ──────────────────
    // We fetch them here while they still exist, then archive them.
    $placeholders = implode(',', array_fill(0, count($precinctIds), '?'));
    $pvStmt = $pdo->prepare("
        SELECT * FROM precinct_voters WHERE precinct IN ($placeholders)
    ");
    $pvStmt->execute($precinctIds);
    $precinctVoters = $pvStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($precinctVoters as $pv) {
        $archivePdo->prepare("
            INSERT INTO archived_precinct_voters
                (precinct, student_id, created_at, cor, status, archived_at, voting_period_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                status      = VALUES(status),
                archived_at = VALUES(archived_at)
        ")->execute([
            $pv['precinct'],
            $pv['student_id'],
            $pv['created_at'],
            $pv['cor'],
            $pv['status'],
            $archivedAt,
            $voting_period_id,
        ]);
    }

    // ── 2. Archive precinct_elections rows ────────────────────────────────
    foreach ($precincts as $prec) {
        $archivePdo->prepare("
            INSERT INTO archived_precinct_elections
                (precinct_id, precinct_name, election_name, assigned_at, archived_at, voting_period_id)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                archived_at       = VALUES(archived_at),
                voting_period_id  = VALUES(voting_period_id)
        ")->execute([
            $prec['id'],
            $prec['pe_election_name'] ?? $prec['name'],
            $prec['pe_election_name'] ?? $election_id,
            $prec['pe_assigned_at'],
            $archivedAt,
            $voting_period_id,
        ]);
    }

    // ── 3. Archive the precincts themselves ───────────────────────────────
    foreach ($precincts as $prec) {
        $archivePdo->prepare("
            INSERT INTO archived_precincts
                (id, name, longitude, latitude, location, created_at, updated_at,
                 assignment_status, occupied_status, college, department, major_id,
                 current_capacity, max_capacity, type, status, college_external,
                 election, archived_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                name              = VALUES(name),
                assignment_status = VALUES(assignment_status),
                occupied_status   = VALUES(occupied_status),
                status            = VALUES(status),
                major_id          = VALUES(major_id),
                current_capacity  = VALUES(current_capacity),
                max_capacity      = VALUES(max_capacity),
                college_external  = VALUES(college_external),
                archived_at       = VALUES(archived_at)
        ")->execute([
            $prec['id'],
            $prec['name'],
            $prec['longitude'],
            $prec['latitude'],
            $prec['location'],
            $prec['created_at'],
            $prec['updated_at'],
            'unassigned',
            'unoccupied',
            $prec['college']          ?? null,
            $prec['department']       ?? null,
            $prec['major_id']         ?? null,
            $prec['current_capacity'] ?? null,
            $prec['max_capacity']     ?? null,
            $prec['type']             ?? null,
            'archived',
            $prec['college_external'] ?? null,
            $prec['pe_election_name'] ?? $election_id,
            $archivedAt,
        ]);
    }

    // ── 4. Reset live precinct rows for reuse ─────────────────────────────
    $reset = $pdo->prepare("
        UPDATE precincts
        SET status            = 'archived',
            assignment_status = 'unassigned',
            occupied_status   = 'unoccupied',
            current_capacity  = 0,
            election          = NULL,
            updated_at        = ?
        WHERE id = ?
    ");
    foreach ($precincts as $prec) {
        $reset->execute([$archivedAt, $prec['id']]);
    }

    // ── 5. Mark precinct_elections rows as archived ───────────────────────
    $pdo->prepare("
        UPDATE precinct_elections
        SET archived    = 1,
            archived_at = ?
        WHERE election_name = ?
          AND archived      = 0
    ")->execute([$archivedAt, $election_id]);

    // ── 6. Delete precinct_voters — MUST be after archiveVoters ──────────
    $pdo->prepare("
        DELETE FROM precinct_voters WHERE precinct IN ($placeholders)
    ")->execute($precinctIds);

    return "Archived " . count($precincts) . " precincts";
}

// ─────────────────────────────────────────────────────────────────────────────
// HELPER: archiveVotes
// ─────────────────────────────────────────────────────────────────────────────
function archiveVotes($pdo, $archivePdo, $voting_period_id)
{
    $stmt = $pdo->prepare("
        SELECT candidate_id, student_id, voting_period_id, precinct
        FROM votes
        WHERE voting_period_id = ?
    ");
    $stmt->execute([$voting_period_id]);
    $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$votes) return "No votes found";

    $insert = $archivePdo->prepare("
        INSERT INTO archived_votes (candidate_id, student_id, voting_period_id, precinct, archived_on)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE archived_on = VALUES(archived_on)
    ");

    foreach ($votes as $vote) {
        $insert->execute([
            $vote['candidate_id'],
            $vote['student_id'],
            $vote['voting_period_id'],
            $vote['precinct'] ?? null,
        ]);
    }

    return "Archived " . count($votes) . " votes";
}


// ─────────────────────────────────────────────────────────────────────────────
// EXECUTE ARCHIVE STEPS
// ─────────────────────────────────────────────────────────────────────────────
$archivedCounts = [
    'precincts'  => 0,
    'voters'     => 0,
    'candidates' => 0,
];

// Academic Year & related info
// BUG FIX (Bug 4): Now passes $academic_year_id so only the relevant year is archived.
echo archiveAcademicYear($pdo, $archivePdo, $academic_year_id) . "\n";
$ayDataNew = fetchAcademicYearData($pdo, $academic_year_id);
echo archiveAcademicInfo($archivePdo, $ayDataNew) . "\n";

// Election & Voting Period
echo archiveElection($pdo, $archivePdo, $election_id, $voting_period_id) . "\n";
echo archiveVotingPeriod($archivePdo, $vpData) . "\n";

// Positions
echo archivePositions($pdo, $archivePdo, $election_id) . "\n";

// Candidates
$candidateMsg = archiveCandidates($pdo, $archivePdo, $election_id, $voting_period_id);
echo $candidateMsg . "\n";
preg_match('/\d+/', $candidateMsg, $matches);
$archivedCounts['candidates'] = $matches[0] ?? 0;

// Candidacy & Parties
echo archiveCandidacyData($pdo, $archivePdo, $election_id, $voting_period_id) . "\n";
echo archiveParties($pdo, $archivePdo, $election_id, $voting_period_id) . "\n";

// Votes
$voteMsg = archiveVotes($pdo, $archivePdo, $voting_period_id);
echo $voteMsg . "\n";

// ─────────────────────────────────────────────────────────────────────────────
// BUG FIX (Bug 1): archiveVoters MUST run before archivePrecincts.
// archivePrecincts deletes precinct_voters rows that archiveVoters needs
// to populate precinct_name, precinct_type, and has_voted correctly.
// ─────────────────────────────────────────────────────────────────────────────

// Voters — runs FIRST, while precinct_voters rows still exist
$voterMsg = archiveVoters($pdo, $archivePdo, $election_id, $voting_period_id);
echo $voterMsg . "\n";
preg_match('/\d+/', $voterMsg, $matches);
$archivedCounts['voters'] = $matches[0] ?? 0;

// Precincts — runs AFTER voters, then cleans up precinct_voters
$precinctMsg = archivePrecincts($pdo, $archivePdo, $election_id, $voting_period_id);
echo $precinctMsg . "\n";
preg_match('/\d+/', $precinctMsg, $matches);
$archivedCounts['precincts'] = $matches[0] ?? 0;

// Summary counts
$stmt = $pdo->prepare("
    INSERT INTO archived_details_short
    (election_id, candidates, precincts, voters)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        candidates = VALUES(candidates),
        precincts  = VALUES(precincts),
        voters     = VALUES(voters)
");
$stmt->execute([
    $election_id,
    $archivedCounts['candidates'],
    $archivedCounts['precincts'],
    $archivedCounts['voters']
]);
echo "Summary counts inserted/updated in archived_details_short.\n";

// Update voting period to Published
$pdo->prepare("UPDATE voting_periods SET status = 'Published' WHERE id = ?")
    ->execute([$voting_period_id]);

// Update related statuses if all voting periods are processed
$stmt = $pdo->query("SELECT COUNT(*) FROM voting_periods WHERE status = 'Ended'");
$remaining = $stmt->fetchColumn();

if ($remaining == 0) {
    $pdo->prepare("UPDATE academic_years    SET status = 'Archived'  WHERE id = ?")->execute([$academic_year_id]);
    $pdo->prepare("UPDATE elections         SET status = 'Published' WHERE id = ?")->execute([$election_id]);
    $pdo->prepare("UPDATE candidacy         SET status = 'Published' WHERE election_id = ?")->execute([$election_id]);
    $pdo->prepare("UPDATE registration_forms SET status = 'inactive' WHERE election_name = ?")->execute([$election_id]);
    echo "All statuses updated.\n";
} else {
    echo "Still $remaining voting period(s) remaining.\n";
}

// Redirect
header('Location: view_published.php?voting_period_id=' . $voting_period_id);
exit();
