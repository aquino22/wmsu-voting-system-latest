<?php
ob_start(); // Start output buffering to handle header redirect

// Database connections (adjust credentials as needed)
try {
    $pdo = new PDO("mysql:host=localhost;dbname=wmsu_voting_system", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $archivePdo = new PDO("mysql:host=localhost;dbname=wmsu_voting_system_archived", "root", "");
    $archivePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to update statuses
function updateStatuses($pdo, $voting_period_id)
{
    try {
        // Get election_name
        $stmt = $pdo->prepare("
            SELECT vp.name, e.id 
            FROM voting_periods vp 
            LEFT JOIN elections e ON vp.name = e.election_name 
            WHERE vp.id = ?
        ");
        $stmt->execute([$voting_period_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $election_name = $row['name'] ?? null;

        if (!$election_name) {
            return "No election found for voting period ID: $voting_period_id";
        }

        // 1. Update voting_periods status to 'published'
        $stmt = $pdo->prepare("UPDATE voting_periods SET status = 'Published' WHERE id = ?");
        $stmt->execute([$voting_period_id]);

        // 2. Update registration_forms status to 'published'
        $stmt = $pdo->prepare("UPDATE registration_forms SET status = 'Published' WHERE election_name = ?");
        $stmt->execute([$election_name]);

        // 3. Get precinct_names from precinct_elections and update precincts
        $stmt = $pdo->prepare("SELECT precinct_name FROM precinct_elections WHERE election_name = ?");
        $stmt->execute([$election_name]);
        $precinct_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($precinct_names)) {
            $placeholders = implode(',', array_fill(0, count($precinct_names), '?'));
            $stmt = $pdo->prepare("UPDATE precincts SET status = 'active' WHERE name IN ($placeholders)");
            $stmt->execute($precinct_names);
        }

        // Update Moderator's precinct into No elections assigned after voting

        // 4. Update parties status to 'published'
        $stmt = $pdo->prepare("UPDATE parties SET status = 'Published' WHERE election_name = ?");
        $stmt->execute([$election_name]);

        // 5. Update events status to 'ended'
        $stmt = $pdo->prepare("UPDATE events SET status = 'Ended' WHERE candidacy = ?");
        $stmt->execute([$election_name]);

        // 6. Update elections status to 'published'
        $stmt = $pdo->prepare("UPDATE elections SET status = 'Published' WHERE election_name = ?");
        $stmt->execute([$election_name]);

        // 7. Update candidacy status to 'published'
        $stmt = $pdo->prepare("UPDATE candidacy SET status = 'Published' WHERE election_name = ?");
        $stmt->execute([$election_name]);

        return "Status updates completed for election: $election_name";
    } catch (PDOException $e) {
        return "Error updating statuses: " . $e->getMessage();
    }
}

// Function to archive election data
function archiveElectionData($pdo, $archivePdo, $election_name, $voting_period_id)
{
    try {
        // Join with voting_periods to match voting_period_id
        $stmt = $pdo->prepare("
            SELECT e.id, e.election_name, e.semester, e.school_year_start, e.school_year_end, 
                   e.start_period, e.end_period, e.status,
                   GROUP_CONCAT(p.name) AS parties
            FROM elections e
            LEFT JOIN parties p ON p.election_name = e.election_name
            JOIN voting_periods vp ON vp.name = e.election_name AND vp.id = ?
            WHERE e.election_name = ?
            GROUP BY e.id
        ");
        $stmt->execute([$voting_period_id, $election_name]);
        $election = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($election) {
            // Get voter statistics including external votes
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT v.student_id) AS votes_cast, 
                    (SELECT COUNT(*) FROM voters) AS total_voters,
                    SUM(CASE WHEN p.type = 'WMSU ESU' THEN 1 ELSE 0 END) AS external_votes,
                    SUM(CASE WHEN p.type = 'Main Campus' THEN 1 ELSE 0 END) AS internal_votes
                FROM votes v
                JOIN precinct_voters pv ON v.student_id = pv.student_id
                JOIN precincts p ON pv.precinct = p.name
                WHERE v.voting_period_id = ?
            ");
            $stmt->execute([$voting_period_id]);
            $turnoutData = $stmt->fetch(PDO::FETCH_ASSOC);

            $turnoutPercentage = $turnoutData['total_voters'] > 0
                ? round(($turnoutData['votes_cast'] / $turnoutData['total_voters']) * 100)
                : 0;

            $externalPercentage = $turnoutData['votes_cast'] > 0
                ? round(($turnoutData['external_votes'] / $turnoutData['votes_cast']) * 100)
                : 0;

            $internalPercentage = $turnoutData['votes_cast'] > 0
                ? round(($turnoutData['internal_votes'] / $turnoutData['votes_cast']) * 100)
                : 0;

            $stmt = $archivePdo->prepare("
                INSERT INTO archived_elections (
                    id, election_name, semester, school_year_start, school_year_end, 
                    start_period, end_period, parties, turnout, archived_on, status, 
                    voting_period_id, external_votes, internal_votes
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $election['id'],
                $election['election_name'],
                $election['semester'],
                $election['school_year_start'],
                $election['school_year_end'],
                $election['start_period'],
                $election['end_period'],
                $election['parties'],
                $turnoutPercentage,
                date('Y-m-d'),
                'archived',
                $voting_period_id,
                $externalPercentage,
                $internalPercentage
            ]);
            return "Election archived: $election_name (Voting Period ID: $voting_period_id)";
        }
        return "No election data found for: $election_name (Voting Period ID: $voting_period_id)";
    } catch (PDOException $e) {
        return "Error archiving election: " . $e->getMessage();
    }
}
function archiveCandidateData($pdo, $archivePdo, $election_name, $voting_period_id)
{
    try {
        // Step 1: Get all candidates for this election including their picture paths
        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                rf.election_name,
                MAX(CASE WHEN ff.field_name = 'full_name' THEN cr.value END) AS full_name,
                MAX(CASE WHEN ff.field_name = 'position' THEN cr.value END) AS position,
                MAX(CASE WHEN ff.field_name = 'party' THEN cr.value END) AS party,
                MAX(CASE WHEN ff.field_name = 'student_id' THEN cr.value END) AS student_id,
                vtr.college,
                p.level AS position_level,
                c.created_at AS filed_on,
                (
                    SELECT cf.file_path 
                    FROM candidate_files cf 
                    JOIN form_fields fff ON cf.field_id = fff.id 
                    WHERE cf.candidate_id = c.id 
                    AND fff.field_name = 'picture'
                    LIMIT 1
                ) AS picture_path
            FROM candidates c
            JOIN registration_forms rf ON c.form_id = rf.id
            LEFT JOIN candidate_responses cr ON cr.candidate_id = c.id
            LEFT JOIN form_fields ff ON cr.field_id = ff.id
            LEFT JOIN voters vtr ON vtr.student_id = (
                SELECT cr2.value 
                FROM candidate_responses cr2 
                JOIN form_fields ff2 ON cr2.field_id = ff2.id 
                WHERE cr2.candidate_id = c.id AND ff2.field_name = 'student_id'
                LIMIT 1
            )
            LEFT JOIN positions p ON p.name = (
                SELECT cr3.value 
                FROM candidate_responses cr3 
                JOIN form_fields ff3 ON cr3.field_id = ff3.id 
                WHERE cr3.candidate_id = c.id AND ff3.field_name = 'position'
                LIMIT 1
            )
            WHERE rf.election_name = ?
            GROUP BY c.id, rf.election_name, vtr.college, p.level, c.created_at
        ");
        $stmt->execute([$election_name]);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($candidates)) {
            return "No candidates found for: $election_name (Voting Period ID: $voting_period_id)";
        }

        // Step 2: Get votes for each candidate (separating internal/external)
        $results = [];
        foreach ($candidates as $candidate) {
            // Get internal votes (Central precincts)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS votes
                FROM votes v
                JOIN precinct_voters pv ON v.student_id = pv.student_id
                JOIN precincts p ON pv.precinct = p.name
                WHERE v.candidate_id = ? 
                AND v.voting_period_id = ?
                AND p.type = 'Main Campus'
            ");
            $stmt->execute([$candidate['id'], $voting_period_id]);
            $internalVotes = (int)$stmt->fetchColumn();

            // Get external votes (External precincts)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS votes
                FROM votes v
                JOIN precinct_voters pv ON v.student_id = pv.student_id
                JOIN precincts p ON pv.precinct = p.name
                WHERE v.candidate_id = ? 
                AND v.voting_period_id = ?
                AND p.type = 'WMSU ESU'
            ");
            $stmt->execute([$candidate['id'], $voting_period_id]);
            $externalVotes = (int)$stmt->fetchColumn();

            $totalVotes = $internalVotes + $externalVotes;

            // Determine if this candidate won their position
            $position = $candidate['position'] ?? 'Unknown';
            $college = $candidate['college'] ?? 'Unknown';
            $level = $candidate['position_level'] ?? 'Unknown';

            // Get all candidates for this position/college to determine winner
            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    (SELECT COUNT(*) FROM votes v 
                     JOIN precinct_voters pv ON v.student_id = pv.student_id
                     JOIN precincts p ON pv.precinct = p.name
                     WHERE v.candidate_id = c.id 
                     AND v.voting_period_id = ?
                     AND (p.type = 'Main Campus' OR p.type = 'WMSU ESU')) AS total_votes
                FROM candidates c
                JOIN candidate_responses cr ON c.id = cr.candidate_id
                JOIN form_fields ff ON cr.field_id = ff.id AND ff.field_name = 'position'
                LEFT JOIN voters v ON (
                    SELECT cr2.value 
                    FROM candidate_responses cr2 
                    JOIN form_fields ff2 ON cr2.field_id = ff2.id 
                    WHERE cr2.candidate_id = c.id AND ff2.field_name = 'student_id'
                    LIMIT 1
                ) = v.student_id
                WHERE cr.value = ?
                AND v.college = ?
                AND c.id IN (
                    SELECT c2.id 
                    FROM candidates c2
                    JOIN registration_forms rf ON c2.form_id = rf.id
                    WHERE rf.election_name = ?
                )
            ");
            $stmt->execute([$voting_period_id, $position, $college, $election_name]);
            $competitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $isWinner = true;
            foreach ($competitors as $competitor) {
                if ($competitor['total_votes'] > $totalVotes) {
                    $isWinner = false;
                    break;
                }
            }

            $outcome = $isWinner ? 'Won' : 'Lost';

            // Archive the candidate with vote counts and picture path
            $stmt = $archivePdo->prepare("
                INSERT INTO archived_candidates (
                    id, election_name, candidate_name, position, party, filed_on, outcome, 
                    votes_received, archived_on, college, voting_period_id, level,
                    internal_votes, external_votes, picture_path
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    election_name = VALUES(election_name),
                    candidate_name = VALUES(candidate_name),
                    position = VALUES(position),
                    party = VALUES(party),
                    filed_on = VALUES(filed_on),
                    outcome = VALUES(outcome),
                    votes_received = VALUES(votes_received),
                    archived_on = VALUES(archived_on),
                    college = VALUES(college),
                    voting_period_id = VALUES(voting_period_id),
                    level = VALUES(level),
                    internal_votes = VALUES(internal_votes),
                    external_votes = VALUES(external_votes),
                    picture_path = VALUES(picture_path)
            ");
            $stmt->execute([
                $candidate['id'],
                $candidate['election_name'],
                $candidate['full_name'],
                $position,
                $candidate['party'],
                $candidate['filed_on'],
                $outcome,
                $totalVotes,
                date('Y-m-d'),
                $college,
                $voting_period_id,
                $level,
                $internalVotes,
                $externalVotes,
                $candidate['picture_path']
            ]);

            $results[] = "Candidate archived: {$candidate['full_name']} (Position: $position, Votes: $totalVotes [$internalVotes internal, $externalVotes external])";
        }

        return implode("\n", $results);
    } catch (PDOException $e) {
        return "Error archiving candidates: " . $e->getMessage();
    }
}

function archiveVotersData($pdo, $archivePdo, $election_name, $voting_period_id)
{
    try {
        // Clear existing voter data for this election and voting period
        $archivePdo->prepare("DELETE FROM archived_voters WHERE election_name = ? AND voting_period_id = ?")
            ->execute([$election_name, $voting_period_id]);

        // Fetch all voters from the voters table with all details
        $stmt = $pdo->prepare("
            SELECT 
                user_id,
                student_id,
                email,
                password,
                first_name,
                middle_name,
                last_name,
                course,
                year_level,
                college,
                department
            FROM voters
        ");
        $stmt->execute();
        $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($voters)) {
            return "No voters found in the system for election: $election_name (Voting Period ID: $voting_period_id)";
        }

        $results = [];
        foreach ($voters as $voter) {
            $stmt = $archivePdo->prepare("
                INSERT INTO archived_voters (
                    user_id, student_id, email, password, first_name, middle_name, last_name, 
                    course, year_level, college, department, election_name, voting_period_id, 
                    archived_on, status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    user_id = VALUES(user_id),
                    student_id = VALUES(student_id),
                    email = VALUES(email),
                    password = VALUES(password),
                    first_name = VALUES(first_name),
                    middle_name = VALUES(middle_name),
                    last_name = VALUES(last_name),
                    course = VALUES(course),
                    year_level = VALUES(year_level),
                    college = VALUES(college),
                    department = VALUES(department),
                    election_name = VALUES(election_name),
                    voting_period_id = VALUES(voting_period_id),
                    archived_on = VALUES(archived_on),
                    status = VALUES(status)
            ");
            $stmt->execute([
                $voter['user_id'],
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
                $election_name,
                $voting_period_id,
                date('Y-m-d'),
                'archived'
            ]);
            $results[] = "Voter archived: {$voter['student_id']} ({$voter['first_name']} {$voter['last_name']}, College: {$voter['college']})";
        }
        return implode("\n", $results);
    } catch (PDOException $e) {
        return "Error archiving voters: " . $e->getMessage();
    }
}

function archiveCandidacyData($pdo, $archivePdo, $election_name, $voting_period_id)
{
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.election_name, c.semester, c.school_year_start, c.school_year_end, 
                   c.start_period, c.end_period, c.status
            FROM candidacy c
            JOIN voting_periods vp ON vp.name = c.election_name
            WHERE c.election_name = ? AND vp.id = ?
        ");
        $stmt->execute([$election_name, $voting_period_id]);
        $candidacy = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($candidacy) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS total_filed
                FROM candidates c
                JOIN registration_forms rf ON c.form_id = rf.id
                WHERE rf.election_name = ?
            ");
            $stmt->execute([$election_name]);
            $totalFiled = $stmt->fetchColumn();

            $stmt = $archivePdo->prepare("
                INSERT INTO archived_candidacies (id, election_name, semester, school_year_start, school_year_end, 
                    start_period, end_period, total_filed, archived_on, status, voting_period_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $candidacy['id'],
                $candidacy['election_name'],
                $candidacy['semester'],
                $candidacy['school_year_start'],
                $candidacy['school_year_end'],
                $candidacy['start_period'],
                $candidacy['end_period'],
                $totalFiled,
                date('Y-m-d'),
                'archived',
                $voting_period_id
            ]);
            return "Candidacy archived: $election_name (Voting Period ID: $voting_period_id)";
        }
        return "No candidacy data found for: $election_name (Voting Period ID: $voting_period_id)";
    } catch (PDOException $e) {
        return "Error archiving candidacy: " . $e->getMessage();
    }
}

function archivePartiesData($pdo, $archivePdo, $election_name, $voting_period_id)
{
    try {
        // Clear existing party data for this election and voting period
        $archivePdo->prepare("DELETE FROM archived_parties WHERE election_name = ? AND voting_period_id = ?")
            ->execute([$election_name, $voting_period_id]);

        // Fetch all parties for this election
        $stmt = $pdo->prepare("
            SELECT 
                id,
                name,
                election_name,
                party_image,
                platforms,
                created_at,
                updated_at,
                status
            FROM parties
            WHERE election_name = ?
        ");
        $stmt->execute([$election_name]);
        $parties = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($parties)) {
            return "No parties found for election: $election_name (Voting Period ID: $voting_period_id)";
        }

        $results = [];
        foreach ($parties as $party) {
            // For each party, count how many candidates belong to it
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM candidates c
                JOIN candidate_responses cr ON c.id = cr.candidate_id
                JOIN form_fields ff ON cr.field_id = ff.id AND ff.field_name = 'party'
                WHERE cr.value = ?
                AND c.id IN (
                    SELECT c2.id 
                    FROM candidates c2
                    JOIN registration_forms rf ON c2.form_id = rf.id
                    WHERE rf.election_name = ?
                )
            ");
            $stmt->execute([$party['name'], $election_name]);
            $candidate_count = $stmt->fetchColumn();

            // Count how many candidates from this party won their positions
            $stmt = $archivePdo->prepare("
                SELECT COUNT(*) 
                FROM archived_candidates
                WHERE party = ?
                AND election_name = ?
                AND voting_period_id = ?
                AND outcome = 'Won'
            ");
            $stmt->execute([$party['name'], $election_name, $voting_period_id]);
            $winners_count = $stmt->fetchColumn();

            // Archive the party data
            $stmt = $archivePdo->prepare("
                INSERT INTO archived_parties (
                    id, name, election_name, party_image, platforms, 
                    created_at, updated_at, status, archived_on,
                    voting_period_id, candidate_count, winners_count
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $party['id'],
                $party['name'],
                $party['election_name'],
                $party['party_image'],
                $party['platforms'],
                $party['created_at'],
                $party['updated_at'],
                'archived',
                date('Y-m-d'),
                $voting_period_id,
                $candidate_count,
                $winners_count
            ]);

            $results[] = "Party archived: {$party['name']} (Candidates: $candidate_count, Winners: $winners_count)";
        }

        return implode("\n", $results);
    } catch (PDOException $e) {
        return "Error archiving parties: " . $e->getMessage();
    }
}



// Main execution
$voting_period_id = isset($_GET['voting_period_id']) ? (int)$_GET['voting_period_id'] : null;

if ($voting_period_id) {
    // Update statuses
    echo updateStatuses($pdo, $voting_period_id) . "\n";

    // Get election_name for archiving
    $stmt = $pdo->prepare("SELECT name FROM voting_periods WHERE id = ?");
    $stmt->execute([$voting_period_id]);
    $election_name = $stmt->fetchColumn();

    if ($election_name) {
        // Archive data
        echo archiveElectionData($pdo, $archivePdo, $election_name, $voting_period_id) . "\n";
        echo archiveCandidateData($pdo, $archivePdo, $election_name, $voting_period_id) . "\n";
        echo archiveVotersData($pdo, $archivePdo, $election_name, $voting_period_id) . "\n";
        echo archiveCandidacyData($pdo, $archivePdo, $election_name, $voting_period_id) . "\n";;
        echo archivePartiesData($pdo, $archivePdo, $election_name, $voting_period_id) . "\n";;

        // Redirect after completion
        header('Location: view_published.php?voting_period_id=' . $voting_period_id);
        exit();
    } else {
        echo "Could not determine election_name for archiving.\n";
    }
} else {
    echo "Please provide a voting_period_id via GET parameter (e.g., ?voting_period_id=1).\n";
}

ob_end_flush(); // End output buffering and send output to browser