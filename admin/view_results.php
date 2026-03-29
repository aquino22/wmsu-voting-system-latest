<?php
session_start();
include('includes/conn_archived.php'); // Only archived DB needed — no separate votes table exists
$pdo = $pdo_archived;

$voting_period_id = (int)($_GET['id'] ?? 0);
if (!$voting_period_id) {
    die('<div class="alert alert-danger">No voting period specified.</div>');
}

// ============================================================
// 1. Election details
// ============================================================
$stmt = $pdo->prepare("
    SELECT ae.id, ae.election_name, ae.status, ae.start_period, ae.end_period,
           ae.parties, ae.semester, ae.school_year_start, ae.school_year_end,
           ae.turnout, ae.archived_on,
           aay.year_label, aay.semester AS academic_semester
    FROM archived_elections ae
    LEFT JOIN archived_academic_years aay ON ae.academic_year_id = aay.id
    WHERE ae.voting_period_id = ?
    LIMIT 1
");
$stmt->execute([$voting_period_id]);
$election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$election) {
    die('<div class="alert alert-danger">No archived election found for this voting period.</div>');
}
$election_id   = $election['id'];
$election_name = $election['election_name'];

// ============================================================
// 2. Parties
// ============================================================
$stmt = $pdo->prepare("SELECT name FROM archived_parties WHERE voting_period_id = ?");
$stmt->execute([$voting_period_id]);
$party_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($party_names)) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT TRIM(party) AS name
        FROM archived_candidates
        WHERE voting_period_id = ? AND party IS NOT NULL AND TRIM(party) != ''
        ORDER BY name ASC
    ");
    $stmt->execute([$voting_period_id]);
    $party_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ============================================================
// 3. Candidacy period
// ============================================================
$stmt = $pdo->prepare("
    SELECT total_filed, start_period, end_period
    FROM archived_candidacies
    WHERE election_id = ? AND voting_period_id = ?
    LIMIT 1
");
$stmt->execute([$election_id, $voting_period_id]);
$candidacy = $stmt->fetch(PDO::FETCH_ASSOC);

// ============================================================
// 4. Voting period dates
// ============================================================
$stmt = $pdo->prepare("SELECT start_period, end_period FROM archived_voting_periods WHERE id = ? LIMIT 1");
$stmt->execute([$voting_period_id]);
$voting_period_details = $stmt->fetch(PDO::FETCH_ASSOC);

// ============================================================
// 5. College / Department / Course / Major lookup maps
// ============================================================
$stmt = $pdo->query("SELECT college_id, college_name FROM archived_colleges ORDER BY college_name ASC");
$collegeMap = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $collegeMap[(string)$r['college_id']] = $r['college_name'];
}
$colleges = array_values($collegeMap); // ordered list of names

$stmt = $pdo->query("SELECT department_id, department_name, college_id FROM archived_departments ORDER BY department_name ASC");
$departmentMap = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $departmentMap[(string)$r['department_id']] = [
        'name'        => $r['department_name'],
        'college_id'  => (string)$r['college_id'],
        'college_name' => $collegeMap[(string)$r['college_id']] ?? 'Unknown',
    ];
}

$stmt = $pdo->query("SELECT id, course_name, college_id FROM archived_courses ORDER BY course_name ASC");
$courseMap = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $courseMap[(string)$r['id']] = ['name' => $r['course_name'], 'college_id' => (string)$r['college_id']];
}

$stmt = $pdo->query("SELECT major_id, major_name, course_id FROM archived_majors ORDER BY major_name ASC");
$majorMap = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $majorMap[(string)$r['major_id']] = ['name' => $r['major_name'], 'course_id' => (string)$r['course_id']];
}

// ============================================================
// 6. Voter stats  (has_voted flag — no individual vote log)
// ============================================================
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total_voters,
           SUM(CASE WHEN has_voted = 1 THEN 1 ELSE 0 END) AS voted_count
    FROM archived_voters
    WHERE voting_period_id = ?
");
$stmt->execute([$voting_period_id]);
$voter_stats  = $stmt->fetch(PDO::FETCH_ASSOC);
$total_voters = (int)($voter_stats['total_voters'] ?? 0);
$voted_count  = (int)($voter_stats['voted_count']  ?? 0);
$did_not_vote = $total_voters - $voted_count;

// ============================================================
// 7. Build voterMap and demographic lookup structures
//    (used to attribute vote breakdowns via voters who voted)
// ============================================================
$stmt = $pdo->prepare("
    SELECT student_id, college, department, course, major, has_voted
    FROM archived_voters
    WHERE voting_period_id = ?
");
$stmt->execute([$voting_period_id]);
$votersInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);

$voterMap            = [];   // student_id => ['college','department','course','major','has_voted']
$departmentsByCollege = [];  // college_name => [dept_name, ...]
$coursesByCollege     = [];  // college_name => [course_name, ...]
$majorsByCourse       = [];  // course_name  => [major_name, ...]

// Count of voted voters per demographic dimension (used for vote distribution)
$votedByCollege     = [];  // college_name => count of voters who voted
$votedByDept        = [];  // dept_name    => count of voters who voted
$votedByCourse      = [];  // course_name  => count of voters who voted
$votedByMajor       = [];  // major_name   => count of voters who voted

foreach ($votersInfo as $v) {
    $ck = (string)trim($v['college']     ?? '');
    $dk = (string)trim($v['department']  ?? '');
    $crk = (string)trim($v['course']     ?? '');
    $mk  = (string)trim($v['major']      ?? '');

    $collegeName = $collegeMap[$ck]    ?? ($ck  !== '' ? $ck  : 'Unknown');
    $deptName    = isset($departmentMap[$dk])   ? $departmentMap[$dk]['name']   : ($dk  !== '' ? $dk  : 'Unknown');
    $courseName  = isset($courseMap[$crk])      ? $courseMap[$crk]['name']      : ($crk !== '' ? $crk : 'Unknown');
    $majorName   = '';
    if ($mk !== '' && isset($majorMap[$mk]))         $majorName = $majorMap[$mk]['name'];
    elseif ($mk !== '')                              $majorName = $mk;

    $voterMap[$v['student_id']] = [
        'college'    => $collegeName,
        'department' => $deptName,
        'course'     => $courseName,
        'major'      => $majorName,
        'has_voted'  => (int)$v['has_voted'],
    ];

    if ($collegeName !== 'Unknown') {
        if ($deptName   !== 'Unknown') $departmentsByCollege[$collegeName][$deptName]   = true;
        if ($courseName !== 'Unknown') $coursesByCollege[$collegeName][$courseName]      = true;
    }
    // if ($courseName !== 'Unknown' && $majorName !== '') {
    //     $majorsByCourse[$courseName][$majorName] = true;
    // }

    // Only count voters who actually voted for distribution
    if ((int)$v['has_voted'] === 1) {
        $votedByCollege[$collegeName] = ($votedByCollege[$collegeName] ?? 0) + 1;
        $votedByDept[$deptName]       = ($votedByDept[$deptName]       ?? 0) + 1;
        $votedByCourse[$courseName]   = ($votedByCourse[$courseName]   ?? 0) + 1;
        if ($majorName !== '') $votedByMajor[$majorName] = ($votedByMajor[$majorName] ?? 0) + 1;
    }
}

foreach ($departmentsByCollege as &$items) {
    $items = array_keys($items);
    sort($items);
}
foreach ($coursesByCollege      as &$items) {
    $items = array_keys($items);
    sort($items);
}
$majorsByCourse = [];
foreach ($majorMap as $majorId => $mm) {
    $courseId   = (string)$mm['course_id'];
    if (!isset($courseMap[$courseId])) continue;
    $courseName = $courseMap[$courseId]['name'];
    if ($courseName === 'Unknown') continue;
    $majorsByCourse[$courseName][$mm['name']] = true;
}
foreach ($majorsByCourse as &$items) {
    $items = array_keys($items);
    sort($items);
}
unset($items);
unset($items);

// Total voted voters (for proportional distribution)
$totalVotedVoters = array_sum($votedByCollege);

$majorNameToColleges = [];
foreach ($majorMap as $mm) {
    $courseId  = (string)$mm['course_id'];
    $colId     = $courseMap[$courseId]['college_id'] ?? null;
    $colName   = $colId ? ($collegeMap[(string)$colId] ?? '') : '';
    if ($colName !== '') {
        $majorNameToColleges[$mm['name']][] = $colName;
    }
}
// ============================================================
// 8. Candidates  (votes_received is the authoritative total)
// ============================================================
$stmt = $pdo->prepare("
    SELECT id, original_id, candidate_name AS name, party, position,
           votes_received AS vote_count, level, college, outcome, picture_path,
           external_votes, internal_votes
    FROM archived_candidates
    WHERE voting_period_id = ?
    ORDER BY CASE WHEN level = 'Central' THEN 1 ELSE 2 END,
             votes_received DESC
");
$stmt->execute([$voting_period_id]);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Resolve college ID → name in candidates
foreach ($candidates as &$cand) {
    $ck = (string)trim($cand['college'] ?? '');
    if (isset($collegeMap[$ck])) $cand['college'] = $collegeMap[$ck];
}
unset($cand);

// ============================================================
// 9. Apply college filter (if provided)
// ============================================================
$college_filter = trim($_GET['college'] ?? '');
if ($college_filter) {
    $candidates = array_filter(
        $candidates,
        fn($c) =>
        $c['level'] === 'Central' || $c['college'] === $college_filter
    );
}

// ============================================================
// 10. Build candidatesByLevel and helper arrays
// ============================================================
$candidatesByLevel   = ['Central' => [], 'Local' => []];
$positionTotals      = [];
$central_positions   = [];
$local_positions     = [];

foreach ($candidates as $cand) {
    $level   = $cand['level'] === 'Central' ? 'Central' : 'Local';
    $college = $cand['college'] ?? 'Unknown';
    $key     = $cand['position'] . ($level === 'Local' ? '|' . $college : '');

    $positionTotals[$key] = ($positionTotals[$key] ?? 0) + $cand['vote_count'];

    $data = [
        'name'        => $cand['name'],
        'party'       => $cand['party'],
        'total'       => $cand['vote_count'],
        'position'    => $cand['position'],
        'college'     => $college,
        'original_id' => $cand['original_id'],
        'outcome'     => $cand['outcome'],
        'picture_path' => $cand['picture_path'],
        'external_votes' => $cand['external_votes'],
        'internal_votes' => $cand['internal_votes'],
    ];

    if ($level === 'Central') {
        $candidatesByLevel['Central'][$cand['position']][$cand['name']] = $data;
        if (!in_array($cand['position'], $central_positions)) $central_positions[] = $cand['position'];
    } else {
        $candidatesByLevel['Local'][$college][$cand['position']][$cand['name']] = $data;
        if (!in_array($cand['position'], $local_positions)) $local_positions[] = $cand['position'];
    }
}

// ============================================================
// 11. Compute vote breakdowns by college / dept / course / major
// ============================================================
$voteBreakdown       = [];
$voteBreakdownDept   = [];
$voteBreakdownCourse = [];
$voteBreakdownMajor  = [];
$partyBreakdownCollege      = [];
$partyBreakdownCollegeLocal = [];

if ($totalVotedVoters > 0) {
    foreach ($candidates as $cand) {
        $oid   = $cand['original_id'];
        $votes = (int)$cand['vote_count'];
        $level = $cand['level'] === 'Central' ? 'Central' : 'Local';
        $party = trim($cand['party'] ?? '');
        $candCollege = $cand['college'] ?? 'Unknown';

        // --- By College ---
        foreach ($votedByCollege as $colName => $colVoterCount) {
            if ($level === 'Local' && $colName !== $candCollege) continue;
            $share = $colVoterCount / $totalVotedVoters;
            $allocated = (int)round($votes * $share);
            if (!isset($voteBreakdown[$oid][$colName])) $voteBreakdown[$oid][$colName] = 0;
            $voteBreakdown[$oid][$colName] += $allocated;

            if ($party !== '') {
                if ($level === 'Central') {
                    if (!isset($partyBreakdownCollege[$party][$colName])) $partyBreakdownCollege[$party][$colName] = 0;
                    $partyBreakdownCollege[$party][$colName] += $allocated;
                } else {
                    if (!isset($partyBreakdownCollegeLocal[$party][$colName])) $partyBreakdownCollegeLocal[$party][$colName] = 0;
                    $partyBreakdownCollegeLocal[$party][$colName] += $allocated;
                }
            }
        }

        // Rounding correction for college
        if (isset($voteBreakdown[$oid])) {
            $distributed = array_sum($voteBreakdown[$oid]);
            $diff = $votes - $distributed;
            if ($diff !== 0 && !empty($votedByCollege)) {
                arsort($votedByCollege);
                $topCollege = array_key_first($votedByCollege);
                if ($level === 'Central' || $topCollege === $candCollege) {
                    $voteBreakdown[$oid][$topCollege] = ($voteBreakdown[$oid][$topCollege] ?? 0) + $diff;
                }
            }
        }

        // --- By Department ---
        $totalVotedInScope = ($level === 'Local')
            ? array_sum(array_filter($votedByDept, function ($deptName) use ($candCollege, $departmentMap) {
                foreach ($departmentMap as $dm) {
                    if ($dm['name'] === $deptName && $dm['college_name'] === $candCollege) return true;
                }
                return false;
            }, ARRAY_FILTER_USE_KEY))
            : $totalVotedVoters;

        if ($totalVotedInScope > 0) {
            $deptRunningTotal = 0;
            $deptKeys = [];
            foreach ($votedByDept as $deptName => $deptVoterCount) {
                if ($level === 'Local') {
                    $deptBelongsToCollege = false;
                    foreach ($departmentMap as $dm) {
                        if ($dm['name'] === $deptName && $dm['college_name'] === $candCollege) {
                            $deptBelongsToCollege = true;
                            break;
                        }
                    }
                    if (!$deptBelongsToCollege) continue;
                }
                $share = $deptVoterCount / $totalVotedInScope;
                $allocated = (int)round($votes * $share);
                if (!isset($voteBreakdownDept[$oid][$deptName])) $voteBreakdownDept[$oid][$deptName] = 0;
                $voteBreakdownDept[$oid][$deptName] += $allocated;
                $deptRunningTotal += $allocated;
                $deptKeys[] = $deptName;
            }
            $deptDiff = $votes - $deptRunningTotal;
            if ($deptDiff !== 0 && !empty($deptKeys)) {
                arsort($votedByDept);
                foreach (array_keys($votedByDept) as $topDept) {
                    if (isset($voteBreakdownDept[$oid][$topDept])) {
                        $voteBreakdownDept[$oid][$topDept] += $deptDiff;
                        break;
                    }
                }
            }
        }

        // --- By Course ---
        $totalVotedCourseScope = ($level === 'Local')
            ? array_sum(array_filter($votedByCourse, function ($crsName) use ($candCollege, $courseMap, $collegeMap) {
                foreach ($courseMap as $cm) {
                    $cn = $collegeMap[$cm['college_id']] ?? '';
                    if ($cm['name'] === $crsName && $cn === $candCollege) return true;
                }
                return false;
            }, ARRAY_FILTER_USE_KEY))
            : $totalVotedVoters;

        if ($totalVotedCourseScope > 0) {
            $crsRunningTotal = 0;
            foreach ($votedByCourse as $crsName => $crsVoterCount) {
                if ($level === 'Local') {
                    $crsBelongs = false;
                    foreach ($courseMap as $cm) {
                        $cn = $collegeMap[$cm['college_id']] ?? '';
                        if ($cm['name'] === $crsName && $cn === $candCollege) {
                            $crsBelongs = true;
                            break;
                        }
                    }
                    if (!$crsBelongs) continue;
                }
                $share = $crsVoterCount / $totalVotedCourseScope;
                $allocated = (int)round($votes * $share);
                if (!isset($voteBreakdownCourse[$oid][$crsName])) $voteBreakdownCourse[$oid][$crsName] = 0;
                $voteBreakdownCourse[$oid][$crsName] += $allocated;
                $crsRunningTotal += $allocated;
            }
            // Rounding correction for course
            $crsDiff = $votes - $crsRunningTotal;
            if ($crsDiff !== 0 && !empty($votedByCourse)) {
                arsort($votedByCourse);
                foreach (array_keys($votedByCourse) as $topCrs) {
                    if (isset($voteBreakdownCourse[$oid][$topCrs])) {
                        $voteBreakdownCourse[$oid][$topCrs] += $crsDiff;
                        break;
                    }
                }
            }
        }

        // --- By Major ---
        // Scope: for Local candidates, only majors whose course belongs to their college
        $scopedVotedByMajor = [];
        foreach ($votedByMajor as $majName => $majVoterCount) {
            if ($level === 'Local') {
                $majorBelongs = false;
                foreach ($majorMap as $mm) {
                    $courseCollegeId   = $courseMap[$mm['course_id']]['college_id'] ?? null;
                    $courseCollegeName = $collegeMap[$courseCollegeId] ?? '';
                    if ($mm['name'] === $majName && $courseCollegeName === $candCollege) {
                        $majorBelongs = true;
                        break;
                    }
                }
                if (!$majorBelongs) continue;
            }
            $scopedVotedByMajor[$majName] = $majVoterCount;
        }

        $totalVotedMajorScope = array_sum($scopedVotedByMajor);
        if ($totalVotedMajorScope > 0) {
            $majRunningTotal = 0;
            foreach ($scopedVotedByMajor as $majName => $majVoterCount) {
                $share = $majVoterCount / $totalVotedMajorScope;
                $allocated = (int)round($votes * $share);
                if (!isset($voteBreakdownMajor[$oid][$majName])) $voteBreakdownMajor[$oid][$majName] = 0;
                $voteBreakdownMajor[$oid][$majName] += $allocated;
                $majRunningTotal += $allocated;
            }
            // Rounding correction for major
            $majDiff = $votes - $majRunningTotal;
            if ($majDiff !== 0 && !empty($scopedVotedByMajor)) {
                arsort($scopedVotedByMajor);
                $topMaj = array_key_first($scopedVotedByMajor);
                $voteBreakdownMajor[$oid][$topMaj] = ($voteBreakdownMajor[$oid][$topMaj] ?? 0) + $majDiff;
            }
        }
    }
}

// ============================================================
// 12. Group candidates by position for display
// ============================================================
$candidates_by_position = [];
$position_totals        = [];
foreach ($candidates as $cand) {
    $position = $cand['position'];
    $candidates_by_position[$position][] = $cand;
    $position_totals[$position] = ($position_totals[$position] ?? 0) + $cand['vote_count'];
}

// Helper: determine winner(s) in a position group
function getWinnerInfo(array $position_candidates): array
{
    $maxVotes = max(array_column($position_candidates, 'vote_count'));
    $winnersCount = count(array_filter($position_candidates, fn($c) => $c['vote_count'] == $maxVotes));
    return ['maxVotes' => $maxVotes, 'winnersCount' => $winnersCount, 'count' => count($position_candidates)];
}

function isWinner(array $candidate, array $winnerInfo): bool
{
    $c = $candidate['vote_count'] ?? $candidate['total'] ?? 0;
    if ($winnerInfo['count'] == 1) return true;
    if ($winnerInfo['maxVotes'] > 0 && $winnerInfo['winnersCount'] == 1 && $c == $winnerInfo['maxVotes']) return true;
    return false;
}

// All parties (Central + Local)
$allParties = $party_names;
foreach ($candidates as $c) $allParties[] = $c['party'];
$allParties = array_values(array_unique(array_filter(array_map('trim', $allParties))));
sort($allParties);

// All combined candidates list for combined tables
$allCombinedCandidates = [];
foreach ($central_positions as $pos) {
    if (empty($candidatesByLevel['Central'][$pos])) continue;
    $posCands = array_values($candidatesByLevel['Central'][$pos]);
    usort($posCands, fn($a, $b) => $b['total'] <=> $a['total']);
    foreach ($posCands as $c) $allCombinedCandidates[] = array_merge($c, ['level_label' => 'Central', 'sort_college' => '']);
}
foreach ($colleges as $col) {
    if (empty($candidatesByLevel['Local'][$col])) continue;
    foreach ($local_positions as $pos) {
        if (empty($candidatesByLevel['Local'][$col][$pos])) continue;
        $posCands = array_values($candidatesByLevel['Local'][$col][$pos]);
        usort($posCands, fn($a, $b) => $b['total'] <=> $a['total']);
        foreach ($posCands as $c) $allCombinedCandidates[] = array_merge($c, ['level_label' => 'Local', 'sort_college' => $col]);
    }
}

// Pre-compute group metadata for combined table
$combinedGroupMeta   = [];
$combinedEmitted     = [];
foreach ($allCombinedCandidates as $c) {
    $pkey = $c['level_label'] . '|' . $c['position'] . '|' . $c['sort_college'];
    if (!isset($combinedGroupMeta[$pkey])) {
        $combinedGroupMeta[$pkey] = ['count' => 0, 'max' => 0];
    }
    $combinedGroupMeta[$pkey]['count']++;
    if ($c['total'] > $combinedGroupMeta[$pkey]['max']) $combinedGroupMeta[$pkey]['max'] = $c['total'];
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WMSU i-Elect | Election Results</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="external/css/styles.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .ballot-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, .1);
            border-radius: 10px;
        }

        .ballot-header {
            text-align: center;
            border-bottom: 3px solid #dc3545;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .ballot-header h1 {
            color: #dc3545;
            font-weight: 700;
        }

        .position-section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }

        .position-title {
            background: #dc3545;
            color: white;
            padding: 12px 20px;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            font-size: 1.1rem;
            position: relative;
        }

        .candidate-card {
            border: 2px solid #e9ecef;
            border-radius: 0 0 8px 8px;

            margin-bottom: 10px;
            transition: all .3s;
            position: relative;
            background: #fff;
        }

        .winner-card {
            border-color: #28a745;
            background: linear-gradient(135deg, #f8fff9, #e8f5e8);
        }

        .loser-card {
            opacity: .85;
            background: #f8f9fa;
        }

        .winner-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 4px 14px;
            border-radius: 15px;
            font-size: .78rem;
            font-weight: 600;
            z-index: 2;
        }

        .candidate-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #dee2e6;
            margin-right: 20px;
        }

        .winner-card .candidate-photo {
            border-color: #28a745;
            box-shadow: 0 0 10px rgba(40, 167, 69, .3);
        }

        .candidate-info {
            flex: 1;
            text-align: left;
        }

        .candidate-name {
            font-weight: 600;
            font-size: 1rem;
            color: #333;
        }

        .winner-card .candidate-name {
            color: #155724;
        }

        .vote-count {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
            color: #495057;
            text-align: center;
            min-width: 110px;
            font-size: .9rem;
        }

        .winner-card .vote-count {
            background: #28a745;
            color: white;
            box-shadow: 0 2px 5px rgba(40, 167, 69, .3);
        }

        .loser-card .vote-count {
            background: #6c757d;
            color: white;
        }

        .candidate-rank {
            position: absolute;
            left: -10px;
            top: 50%;
            transform: translateY(-50%);
            background: #dc3545;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: .85rem;
        }

        .winner-card .candidate-rank {
            background: #28a745;
        }

        .election-meta {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .meta-label {
            font-weight: 600;
            color: #495057;
        }

        .report-card {
            border: 1px solid #dee2e6;
            margin-bottom: 30px;
        }

        .report-card .card-header {
            background: #343a40;
            color: white;
        }

        .report-table thead {
            background: #495057;
            color: white;
        }

        .report-table .winner-row {
            background: rgba(40, 167, 69, .12) !important;
        }

        .report-table .pos-cell {
            background: #f8f9fa;
            font-weight: 600;
            font-size: .85rem;
        }

        .report-table .total-cell {
            font-weight: 700;
            background: #f1f3f5;
        }

        .null-cell {
            color: #adb5bd;
        }

        .note-proportional {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 8px 14px;
            font-size: .82rem;
            color: #856404;
            margin-bottom: 16px;
        }

        @media print {
            .ballot-container {
                box-shadow: none;
            }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="external/img/wmsu-logo.png" class="img-fluid logo">
                WMSU i - Elect |</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            About
                        </a>
                        <ul class="dropdown-menu">

                            <li><a class="dropdown-item" href="about_us.php">About
                                    Us</a></li>

                            <li><a class="dropdown-item" href="about_system.php">About
                                    the System</a></li>
                        </ul>
                    </li>


                    <li class="nav-item ">
                        <a class="nav-link" href="events.php" role="button">
                            Events
                        </a>

                    </li>

                    <li class="nav-item ">
                        <a class="nav-link" href="parties.php" role="button">
                            Parties
                        </a>

                    </li>

                </ul>
                <div class="d-flex">
                    <a class="nav-link" href="login/index.php">
                        <i class="bi bi-person-circle"></i>
                    </a>
                    </li>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="ballot-container">

            <!-- ── Header ── -->
            <div class="ballot-header">
                <h1>WMSU Election Official Result</h1>
                <h2 class="text-muted fw-bold"><?= htmlspecialchars(ucwords(strtolower($election_name))) ?></h2>
                <h5 class="text-muted">
                    <?php
                    $sy  = !empty($election['year_label'])          ? $election['year_label']          : ($election['school_year_start'] . ' – ' . $election['school_year_end']);
                    $sem = !empty($election['academic_semester'])   ? $election['academic_semester']   : $election['semester'];
                    echo htmlspecialchars($sy . ' | ' . $sem);
                    ?>
                </h5>
            </div>

            <!-- ── Elected Candidates Summary ── -->
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white fw-bold">
                    <i class="bi bi-trophy-fill me-2"></i>Elected Candidates
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($candidates_by_position as $position => $pos_cands):
                            $wi = getWinnerInfo($pos_cands);
                            $pos_total = $position_totals[$position] ?? 0;
                            foreach ($pos_cands as $c):
                                if (!isWinner($c, $wi)) continue;
                                $pct = $pos_total > 0 ? round(($c['vote_count'] / $pos_total) * 100) : 0;
                        ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><strong><?= htmlspecialchars($position) ?>:</strong> <?= htmlspecialchars($c['name']) ?>
                                        <span class="badge bg-secondary ms-1"><?= htmlspecialchars($c['party']) ?></span>
                                    </span>
                                    <span class="text-success fw-bold"><?= number_format($c['vote_count']) ?> votes (<?= $pct ?>%)</span>
                                </li>
                        <?php endforeach;
                        endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- ── Election Metadata ── -->
            <div class="election-meta">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <span class="meta-label">Candidacy Period: </span>
                            <?php
                            if (!empty($candidacy['start_period']) && !empty($candidacy['end_period'])) {
                                echo htmlspecialchars(
                                    (new DateTime($candidacy['start_period']))->format('M j, Y') . ' – ' .
                                        (new DateTime($candidacy['end_period']))->format('M j, Y')
                                );
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </div>
                        <div class="mb-2">
                            <span class="meta-label">Voting Period: </span>
                            <?php
                            if (!empty($voting_period_details['start_period'])) {
                                echo htmlspecialchars(
                                    (new DateTime($voting_period_details['start_period']))->format('M j, Y g:i A') . ' – ' .
                                        (new DateTime($voting_period_details['end_period']))->format('M j, Y g:i A')
                                );
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </div>
                        <div class="mb-2">
                            <span class="meta-label">Published: </span>
                            <?= htmlspecialchars((new DateTime($election['archived_on']))->format('F j, Y')) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2"><span class="meta-label">Total Registered Voters: </span><?= number_format($total_voters) ?></div>
                        <div class="mb-2"><span class="meta-label">Voted: </span><?= number_format($voted_count) ?></div>
                        <div class="mb-2"><span class="meta-label">Did Not Vote: </span><?= number_format($did_not_vote) ?></div>
                        <?php if (!empty($election['turnout'])): ?>
                            <div class="mb-2"><span class="meta-label">Turnout: </span><?= htmlspecialchars($election['turnout']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Candidates by Position ── -->
            <?php foreach ($candidates_by_position as $position => $pos_cands):
                $pos_total = $position_totals[$position] ?? 0;
                $wi = getWinnerInfo($pos_cands);
            ?>
                <div class="position-section">
                    <div class="position-title">
                        <?= htmlspecialchars($position) ?>
                        <span style="position:absolute;right:20px;top:50%;transform:translateY(-50%);">
                            <span class="badge bg-success"><i class="bi bi-trophy-fill"></i> Winner</span>
                        </span>
                    </div>
                    <?php foreach ($pos_cands as $idx => $cand):
                        $win = isWinner($cand, $wi);
                        $pct = $pos_total > 0 ? round(($cand['vote_count'] / $pos_total) * 100, 1) : 0;
                    ?>
                        <div class="candidate-card <?= $win ? 'winner-card' : 'loser-card' ?>">
                            <div class="candidate-rank"><?= $idx + 1 ?></div>
                            <?php if ($win): ?>
                                <div class="winner-badge"><i class="bi bi-trophy-fill"></i> WINNER</div>
                            <?php endif; ?>
                            <div class="d-flex align-items-center">
                                <img src="login/uploads/candidates/<?= htmlspecialchars($cand['picture_path'] ?? '') ?>"
                                    class="candidate-photo"
                                    alt="<?= htmlspecialchars($cand['name']) ?>"
                                    onerror="this.src='https://via.placeholder.com/80?text=No+Image'">
                                <div class="candidate-info">
                                    <div class="candidate-name"><?= htmlspecialchars($cand['name']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($cand['party']) ?></div>
                                    <div class="text-muted small">
                                        <?= htmlspecialchars($cand['college'] ?? '') ?>
                                        &bull; <?= htmlspecialchars($cand['level'] ?? '') ?>
                                    </div>
                                    <?php if (!empty($cand['external_votes']) || !empty($cand['internal_votes'])): ?>
                                        <div class="text-muted small">
                                            Internal: <?= number_format((int)$cand['internal_votes']) ?> &bull;
                                            External: <?= number_format((int)$cand['external_votes']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="small <?= $win ? 'text-success fw-semibold' : 'text-muted' ?>"><?= $pct ?>% of position votes</div>
                                </div>
                                <div class="vote-count">
                                    <?= number_format($cand['vote_count']) ?><br>
                                    <span style="font-size:.75rem">votes</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <?php if (empty($candidates_by_position)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-info-circle display-1 text-muted"></i>
                    <h4 class="text-muted mt-3">No candidate data available</h4>
                </div>
            <?php endif; ?>

            <?php if ($totalVotedVoters > 0): ?>
                <div class="note-proportional">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Note on breakdown tables:</strong> The archived database stores total votes per candidate but does not record individual vote logs.
                    The per-college, per-department, per-course, and per-major breakdowns below are <em>estimated proportionally</em>
                    based on the demographic distribution of voters who cast ballots. Totals always match the official <code>votes_received</code> figure.
                </div>
            <?php endif; ?>

            <!-- ══════════════════════════════════════════════════
         PARTY BREAKDOWN — CENTRAL
    ══════════════════════════════════════════════════ -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card report-card">
                        <div class="card-header">
                            <h5 class="mb-0">Votes Breakdown by Party (Central Positions)</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover report-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Party</th>
                                            <?php foreach ($colleges as $col): ?><th style="min-width:130px;font-size:.78rem;"><?= htmlspecialchars($col) ?></th><?php endforeach; ?>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allParties as $party): $rowTotal = 0; ?>
                                            <tr>
                                                <td class="fw-semibold"><?= htmlspecialchars($party) ?></td>
                                                <?php foreach ($colleges as $col):
                                                    $v = $partyBreakdownCollege[$party][$col] ?? 0;
                                                    $rowTotal += $v;
                                                    echo '<td>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                endforeach; ?>
                                                <td class="total-cell"><?= number_format($rowTotal) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PARTY BREAKDOWN — LOCAL -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card report-card">
                        <div class="card-header">
                            <h5 class="mb-0">Votes Breakdown by Party (Local Positions)</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover report-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Party</th>
                                            <?php foreach ($colleges as $col): ?><th style="min-width:130px;font-size:.78rem;"><?= htmlspecialchars($col) ?></th><?php endforeach; ?>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allParties as $party): $rowTotal = 0; ?>
                                            <tr>
                                                <td class="fw-semibold"><?= htmlspecialchars($party) ?></td>
                                                <?php foreach ($colleges as $col):
                                                    $v = $partyBreakdownCollegeLocal[$party][$col] ?? 0;
                                                    $rowTotal += $v;
                                                    echo '<td>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                endforeach; ?>
                                                <td class="total-cell"><?= number_format($rowTotal) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════
         COLLEGE BREAKDOWN — CENTRAL CANDIDATES
    ══════════════════════════════════════════════════ -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card report-card">
                        <div class="card-header">
                            <h5 class="mb-0">Votes Breakdown by College (Central Positions)</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover report-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Position</th>
                                            <th>Candidate</th>
                                            <th>Party</th>
                                            <?php foreach ($colleges as $col): ?><th style="min-width:120px;font-size:.78rem;"><?= htmlspecialchars($col) ?></th><?php endforeach; ?>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($central_positions as $pos):
                                            if (empty($candidatesByLevel['Central'][$pos])) continue;
                                            $posCands = array_values($candidatesByLevel['Central'][$pos]);
                                            usort($posCands, fn($a, $b) => $b['total'] <=> $a['total']);
                                            $maxT = max(array_column($posCands, 'total'));
                                            foreach ($posCands as $cand):
                                                $win = ($maxT > 0 && $cand['total'] == $maxT);
                                                $rowTotal = 0;
                                        ?>
                                                <tr class="<?= $win ? 'winner-row' : '' ?>">
                                                    <td><?= htmlspecialchars($pos) ?></td>
                                                    <td><?php if ($win): ?><span class="badge bg-success me-1">★</span><?php endif; ?><?= htmlspecialchars($cand['name']) ?></td>
                                                    <td><?= htmlspecialchars($cand['party']) ?></td>
                                                    <?php foreach ($colleges as $col):
                                                        $v = $voteBreakdown[$cand['original_id']][$col] ?? 0;
                                                        $rowTotal += $v;
                                                        echo '<td>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                    endforeach; ?>
                                                    <td class="total-cell"><?= number_format($rowTotal) ?></td>
                                                </tr>
                                        <?php endforeach;
                                        endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLLEGE BREAKDOWN — LOCAL CANDIDATES -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card report-card">
                        <div class="card-header">
                            <h5 class="mb-0">Votes Breakdown by College (Local Positions)</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover report-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Position</th>
                                            <th>Candidate</th>
                                            <th>Party</th>
                                            <?php foreach ($colleges as $col): ?><th style="min-width:120px;font-size:.78rem;"><?= htmlspecialchars($col) ?></th><?php endforeach; ?>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($local_positions as $pos):
                                            $localCands = [];
                                            foreach ($colleges as $col) {
                                                if (!empty($candidatesByLevel['Local'][$col][$pos]))
                                                    foreach ($candidatesByLevel['Local'][$col][$pos] as $c) $localCands[] = $c;
                                            }
                                            if (empty($localCands)) continue;
                                            usort($localCands, fn($a, $b) => $b['total'] <=> $a['total']);
                                            $maxT = max(array_column($localCands, 'total'));
                                            foreach ($localCands as $cand):
                                                $win = ($maxT > 0 && $cand['total'] == $maxT);
                                                $rowTotal = 0;
                                        ?>
                                                <tr class="<?= $win ? 'winner-row' : '' ?>">
                                                    <td><?= htmlspecialchars($pos . ' (' . $cand['college'] . ')') ?></td>
                                                    <td><?php if ($win): ?><span class="badge bg-success me-1">★</span><?php endif; ?><?= htmlspecialchars($cand['name']) ?></td>
                                                    <td><?= htmlspecialchars($cand['party']) ?></td>
                                                    <?php foreach ($colleges as $col):
                                                        $v = $voteBreakdown[$cand['original_id']][$col] ?? 0;
                                                        $rowTotal += $v;
                                                        echo '<td>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                    endforeach; ?>
                                                    <td class="total-cell"><?= number_format($rowTotal) ?></td>
                                                </tr>
                                        <?php endforeach;
                                        endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════
         DEPARTMENT BREAKDOWN — PER COLLEGE (LOCAL)
    ══════════════════════════════════════════════════ -->
            <?php foreach ($colleges as $college):
                if (empty($candidatesByLevel['Local'][$college])) continue;
                $collegeDepts = $departmentsByCollege[$college] ?? [];
                if (empty($collegeDepts)) continue;
            ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card report-card">
                            <div class="card-header">
                                <h5 class="mb-0">Votes by Department — <?= htmlspecialchars($college) ?></h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover report-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Position</th>
                                                <th>Candidate</th>
                                                <th>Party</th>
                                                <?php foreach ($collegeDepts as $d): ?><th style="min-width:100px;font-size:.78rem;"><?= htmlspecialchars($d) ?></th><?php endforeach; ?>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($local_positions as $pos):
                                                if (empty($candidatesByLevel['Local'][$college][$pos])) continue;
                                                $posCands = array_values($candidatesByLevel['Local'][$college][$pos]);
                                                usort($posCands, fn($a, $b) => $b['total'] <=> $a['total']);
                                                $maxT = max(array_column($posCands, 'total'));
                                                foreach ($posCands as $cand):
                                                    $win = ($maxT > 0 && $cand['total'] == $maxT);
                                                    $rowTotal = 0;
                                            ?>
                                                    <tr class="<?= $win ? 'winner-row' : '' ?>">
                                                        <td><?= htmlspecialchars($pos) ?></td>
                                                        <td><?php if ($win): ?><span class="badge bg-success me-1">★</span><?php endif; ?><?= htmlspecialchars($cand['name']) ?></td>
                                                        <td><?= htmlspecialchars($cand['party']) ?></td>
                                                        <?php foreach ($collegeDepts as $d):
                                                            $v = $voteBreakdownDept[$cand['original_id']][$d] ?? 0;
                                                            $rowTotal += $v;
                                                            echo '<td>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                        endforeach; ?>
                                                        <td class="total-cell"><?= number_format($rowTotal) ?></td>
                                                    </tr>
                                            <?php endforeach;
                                            endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- ══════════════════════════════════════════════════
         COMBINED CANDIDATE SUMMARY — ALL POSITIONS BY COLLEGE
    ══════════════════════════════════════════════════ -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card report-card">
                        <div class="card-header">
                            <h5 class="mb-0">Combined Candidate Summary — Votes by College (All Positions)</h5>
                            <small class="opacity-75">Central and Local candidates. Winner per position highlighted in green.</small>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm report-table mb-0">
                                    <thead>
                                        <tr>
                                            <th style="min-width:80px;">Level</th>
                                            <th style="min-width:130px;">Position</th>
                                            <th style="min-width:160px;">Candidate</th>
                                            <th style="min-width:100px;">Party</th>
                                            <?php foreach ($colleges as $col): ?><th style="min-width:90px;font-size:.75rem;"><?= htmlspecialchars($col) ?></th><?php endforeach; ?>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allCombinedCandidates as $cand):
                                            $pkey = $cand['level_label'] . '|' . $cand['position'] . '|' . $cand['sort_college'];
                                            $meta = $combinedGroupMeta[$pkey];
                                            $win  = ($cand['total'] == $meta['max'] && $meta['max'] > 0);
                                            $isFirst = !isset($combinedEmitted[$pkey]);
                                            if ($isFirst) $combinedEmitted[$pkey] = true;
                                            $rowTotal = 0;
                                            $badge = $cand['level_label'] === 'Central'
                                                ? '<span class="badge bg-primary">Central</span>'
                                                : '<span class="badge bg-warning text-dark">Local</span>';
                                        ?>
                                            <tr class="<?= $win ? 'winner-row' : '' ?>">
                                                <?php if ($isFirst): ?>
                                                    <td rowspan="<?= $meta['count'] ?>" class="align-middle">
                                                        <?= $badge ?>
                                                        <?php if ($cand['level_label'] === 'Local'): ?>
                                                            <div style="font-size:.7rem;color:#6c757d;margin-top:3px;"><?= htmlspecialchars($cand['sort_college']) ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td rowspan="<?= $meta['count'] ?>" class="pos-cell align-middle"><?= htmlspecialchars($cand['position']) ?></td>
                                                <?php endif; ?>
                                                <td><?php if ($win): ?><span class="badge bg-success me-1">★</span><?php endif; ?><?= htmlspecialchars($cand['name']) ?></td>
                                                <td><?= htmlspecialchars($cand['party']) ?></td>
                                                <?php foreach ($colleges as $col):
                                                    $v = $voteBreakdown[$cand['original_id']][$col] ?? 0;
                                                    $rowTotal += $v;
                                                    echo '<td>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                endforeach; ?>
                                                <td class="total-cell"><?= number_format($rowTotal) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════
         COMBINED BY DEPARTMENT — PER COLLEGE
    ══════════════════════════════════════════════════ -->
            <?php foreach ($colleges as $college):
                $collegeDepts = $departmentsByCollege[$college] ?? [];
                if (empty($collegeDepts)) continue;

                // Central + Local for this college
                $deptCands = [];
                foreach ($central_positions as $pos) {
                    if (empty($candidatesByLevel['Central'][$pos])) continue;
                    $pc = array_values($candidatesByLevel['Central'][$pos]);
                    usort($pc, fn($a, $b) => $b['total'] <=> $a['total']);
                    foreach ($pc as $c) $deptCands[] = array_merge($c, ['level_label' => 'Central', 'sort_college' => '']);
                }
                if (!empty($candidatesByLevel['Local'][$college])) {
                    foreach ($local_positions as $pos) {
                        if (empty($candidatesByLevel['Local'][$college][$pos])) continue;
                        $pc = array_values($candidatesByLevel['Local'][$college][$pos]);
                        usort($pc, fn($a, $b) => $b['total'] <=> $a['total']);
                        foreach ($pc as $c) $deptCands[] = array_merge($c, ['level_label' => 'Local', 'sort_college' => $college]);
                    }
                }
                if (empty($deptCands)) continue;

                $hasDeptVotes = false;
                foreach ($deptCands as $tc) {
                    foreach ($collegeDepts as $d) {
                        if (!empty($voteBreakdownDept[$tc['original_id']][$d])) {
                            $hasDeptVotes = true;
                            break 2;
                        }
                    }
                }
                if (!$hasDeptVotes) continue;

                // Group meta
                $deptGM = [];
                $deptEM = [];
                foreach ($deptCands as $c) {
                    $pk = $c['level_label'] . '|' . $c['position'] . '|' . $c['sort_college'];
                    if (!isset($deptGM[$pk])) $deptGM[$pk] = ['count' => 0, 'max' => 0];
                    $deptGM[$pk]['count']++;
                    if ($c['total'] > $deptGM[$pk]['max']) $deptGM[$pk]['max'] = $c['total'];
                }
            ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card report-card">
                            <div class="card-header">
                                <h5 class="mb-0">Combined Summary — Votes by Department</h5>
                                <small class="opacity-75"><?= htmlspecialchars($college) ?> &nbsp;·&nbsp; Central + Local candidates</small>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm report-table mb-0">
                                        <thead>
                                            <tr>
                                                <th style="min-width:80px;">Level</th>
                                                <th style="min-width:130px;">Position</th>
                                                <th style="min-width:160px;">Candidate</th>
                                                <th style="min-width:100px;">Party</th>
                                                <?php foreach ($collegeDepts as $d): ?><th style="min-width:90px;font-size:.75rem;"><?= htmlspecialchars($d) ?></th><?php endforeach; ?>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($deptCands as $cand):
                                                $pk   = $cand['level_label'] . '|' . $cand['position'] . '|' . $cand['sort_college'];
                                                $meta = $deptGM[$pk];
                                                $win  = ($cand['total'] == $meta['max'] && $meta['max'] > 0);
                                                $isFirst = !isset($deptEM[$pk]);
                                                if ($isFirst) $deptEM[$pk] = true;
                                                $rowTotal = 0;
                                                $badge = $cand['level_label'] === 'Central'
                                                    ? '<span class="badge bg-primary">Central</span>'
                                                    : '<span class="badge bg-warning text-dark">Local</span>';
                                            ?>
                                                <tr class="<?= $win ? 'winner-row' : '' ?>">
                                                    <?php if ($isFirst): ?>
                                                        <td rowspan="<?= $meta['count'] ?>" class="align-middle"><?= $badge ?></td>
                                                        <td rowspan="<?= $meta['count'] ?>" class="pos-cell align-middle"><?= htmlspecialchars($cand['position']) ?></td>
                                                    <?php endif; ?>
                                                    <td><?php if ($win): ?><span class="badge bg-success me-1">★</span><?php endif; ?><?= htmlspecialchars($cand['name']) ?></td>
                                                    <td><?= htmlspecialchars($cand['party']) ?></td>
                                                    <?php foreach ($collegeDepts as $d):
                                                        $v = $voteBreakdownDept[$cand['original_id']][$d] ?? 0;
                                                        $rowTotal += $v;
                                                        echo '<td>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                    endforeach; ?>
                                                    <td class="total-cell"><?= number_format($rowTotal) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- ══════════════════════════════════════════════════
         VOTES BY COURSE — PER COLLEGE
    ══════════════════════════════════════════════════ -->
            <?php foreach ($colleges as $college):
                $collegeCourses = $coursesByCollege[$college] ?? [];
                if (empty($collegeCourses)) continue;

                $relevantCands = [];
                foreach ($candidatesByLevel['Central'] as $posCands) foreach ($posCands as $c) $relevantCands[] = $c;
                if (!empty($candidatesByLevel['Local'][$college]))
                    foreach ($candidatesByLevel['Local'][$college] as $posCands) foreach ($posCands as $c) $relevantCands[] = $c;

                $hasCourseVotes = false;
                foreach ($relevantCands as $rc) {
                    foreach ($collegeCourses as $crs) {
                        if (!empty($voteBreakdownCourse[$rc['original_id']][$crs])) {
                            $hasCourseVotes = true;
                            break 2;
                        }
                    }
                }
                if (!$hasCourseVotes) continue;
            ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card report-card">
                            <div class="card-header">
                                <h5 class="mb-0">Votes by Course — <?= htmlspecialchars($college) ?></h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover table-sm report-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Position</th>
                                                <th>Candidate</th>
                                                <th>Party</th>
                                                <?php foreach ($collegeCourses as $crs): ?><th style="min-width:100px;font-size:.78rem;"><?= htmlspecialchars($crs) ?></th><?php endforeach; ?>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($central_positions as $pos):
                                                if (empty($candidatesByLevel['Central'][$pos])) continue;
                                                $pc = array_values($candidatesByLevel['Central'][$pos]);
                                                usort($pc, fn($a, $b) => $b['total'] <=> $a['total']);
                                                foreach ($pc as $cand): $rowTotal = 0; ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($pos) ?> <span class="badge bg-primary ms-1">Central</span></td>
                                                        <td><?= htmlspecialchars($cand['name']) ?></td>
                                                        <td><?= htmlspecialchars($cand['party']) ?></td>
                                                        <?php foreach ($collegeCourses as $crs):
                                                            $v = $voteBreakdownCourse[$cand['original_id']][$crs] ?? 0;
                                                            $rowTotal += $v;
                                                            echo '<td>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                        endforeach; ?>
                                                        <td class="total-cell"><?= number_format($rowTotal) ?></td>
                                                    </tr>
                                            <?php endforeach;
                                            endforeach; ?>
                                            <?php foreach ($local_positions as $pos):
                                                if (empty($candidatesByLevel['Local'][$college][$pos])) continue;
                                                $pc = array_values($candidatesByLevel['Local'][$college][$pos]);
                                                usort($pc, fn($a, $b) => $b['total'] <=> $a['total']);
                                                foreach ($pc as $cand): $rowTotal = 0; ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($pos) ?> <span class="badge bg-warning text-dark ms-1">Local</span></td>
                                                        <td><?= htmlspecialchars($cand['name']) ?></td>
                                                        <td><?= htmlspecialchars($cand['party']) ?></td>
                                                        <?php foreach ($collegeCourses as $crs):
                                                            $v = $voteBreakdownCourse[$cand['original_id']][$crs] ?? 0;
                                                            $rowTotal += $v;
                                                            echo '<td>' . ($v > 0 ? number_format($v) : '<span class="null-cell">—</span>') . '</td>';
                                                        endforeach; ?>
                                                        <td class="total-cell"><?= number_format($rowTotal) ?></td>
                                                    </tr>
                                            <?php endforeach;
                                            endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- ══════════════════════════════════════════════════
         VOTES BY MAJOR — PER COURSE
    ══════════════════════════════════════════════════ -->
            <?php if (!empty($majorsByCourse)):
                $allCands = [];
                foreach ($candidatesByLevel['Central'] as $posCands) foreach ($posCands as $c) $allCands[] = $c;
                foreach ($candidatesByLevel['Local'] as $positions) foreach ($positions as $posCands) foreach ($posCands as $c) $allCands[] = $c;
            ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card report-card">
                            <div class="card-header">
                                <h5 class="mb-0">Votes by Major (per Course)</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($majorsByCourse as $crsName => $majors):
                                    $hasMV = false;
                                    foreach ($allCands as $c)
                                        foreach ($majors as $m)
                                            if (!empty($voteBreakdownMajor[$c['original_id']][$m])) {
                                                $hasMV = true;
                                                break 2;
                                            }
                                    if (!$hasMV) continue;
                                ?>
                                    <h6 class="mt-3 fw-bold"><?= htmlspecialchars($crsName) ?></h6>
                                    <div class="table-responsive mb-3">
                                        <table class="table table-bordered table-hover table-sm report-table">
                                            <thead>
                                                <tr>
                                                    <th>Position</th>
                                                    <th>Candidate</th>
                                                    <th>Party</th>
                                                    <?php foreach ($majors as $maj): ?><th style="min-width:110px;font-size:.78rem;"><?= htmlspecialchars($maj) ?></th><?php endforeach; ?>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($allCands as $cand):
                                                    $hasAny = false;
                                                    foreach ($majors as $m) if (!empty($voteBreakdownMajor[$cand['original_id']][$m])) {
                                                        $hasAny = true;
                                                        break;
                                                    }
                                                    if (!$hasAny) continue;
                                                    $rowTotal = 0;
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($cand['position']) ?></td>
                                                        <td><?= htmlspecialchars($cand['name']) ?></td>
                                                        <td><?= htmlspecialchars($cand['party']) ?></td>
                                                        <?php foreach ($majors as $maj):
                                                            $v = $voteBreakdownMajor[$cand['original_id']][$maj] ?? 0;
                                                            $rowTotal += $v;
                                                            echo '<td>' . ($v > 0 ? number_format($majVoterCount) : '<span class="null-cell">—</span>') . '</td>';
                                                        endforeach; ?>
                                                        <td class="total-cell"><?= number_format($majVoterCount) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div><!-- /.ballot-container -->
    </div><!-- /.container -->

    <!-- Footer -->
    <footer>
        <div class="container-fluid">
            <div class="row mt-5">
                <div class="col-md-6">
                    <h1 class="bold c-red"> <img src="external/img/wmsu-logo.png" class="img-fluid logo"> WMSU i-Elect
                    </h1>
                    <p>Your friendly WMSU Voting Web Application</p>
                </div>
                <div class="col">
                    <h5 class="c-red bold">About</h5>
                    <p><a href="about_us.php" class="linker">About Us</a></p>
                    <p><a href="about_system.php" class="linker">About the System</a></p>
                </div>
                <div class="col">
                    <h5 class="c-red bold">Help</h5>
                    <p><a href="login/index.php" class="linker">Login</a></p>
                    <p><a href="file_candidacy.php" class="linker">Filing of Candidacy</a></p>
                    <p><a href="voting.php" class="linker">Voting</a></p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php $pdo = null; ?>