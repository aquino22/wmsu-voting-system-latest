try {
        // Clear existing data for this election
        $archivePdo->prepare("DELETE FROM archived_candidates WHERE election_name = ?")->execute([$election_name]);

        // Fetch candidate data with college from voters and position from candidate_responses
        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                rf.election_name,
                MAX(CASE WHEN cr.field_id = (SELECT id FROM form_fields WHERE field_name = 'full_name') THEN cr.value END) AS full_name,
                MAX(CASE WHEN cr.field_id = (SELECT id FROM form_fields WHERE field_name = 'position') THEN cr.value END) AS position,
                vtr.college AS college,
                p.name AS party,
                c.created_at AS filed_on,
                COUNT(v.id) AS votes_received
            FROM candidates c
            JOIN registration_forms rf ON c.form_id = rf.id
            LEFT JOIN candidate_responses cr ON cr.candidate_id = c.id
            LEFT JOIN voters vtr ON vtr.student_id = (
                SELECT cr2.value 
                FROM candidate_responses cr2 
                WHERE cr2.candidate_id = c.id 
                AND cr2.field_id = (SELECT id FROM form_fields WHERE field_name = 'student_id')
                LIMIT 1
            )
            LEFT JOIN votes v ON v.candidate_id = c.id
            LEFT JOIN parties p ON p.election_name = rf.election_name
            WHERE rf.election_name = ?
            GROUP BY c.id, rf.election_name, vtr.college, p.name, c.created_at
        ");
        $stmt->execute([$election_name]);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($candidates)) {
            $debugStmt = $pdo->prepare("SELECT COUNT(*) FROM registration_forms WHERE election_name = ?");
            $debugStmt->execute([$election_name]);
            $rfCount = $debugStmt->fetchColumn();

            $debugStmt = $pdo->prepare("SELECT COUNT(*) FROM candidates c JOIN registration_forms rf ON c.form_id = rf.id WHERE rf.election_name = ?");
            $debugStmt->execute([$election_name]);
            $candidateCount = $debugStmt->fetchColumn();

            return "No candidates found for: $election_name\n" .
                   "Registration forms count: $rfCount\n" .
                   "Candidates count with matching election: $candidateCount";
        }

        // Group candidates by position and college
        $positionCollegeVotes = [];
        foreach ($candidates as $candidate) {
            $position = $candidate['position'] ?? 'Unknown'; // Handle NULL positions
            $college = $candidate['college'] ?? 'Unknown';   // Handle NULL colleges
            $key = "$position|$college"; // Unique key for position-college combo
            if (!isset($positionCollegeVotes[$key])) {
                $positionCollegeVotes[$key] = [];
            }
            $positionCollegeVotes[$key][] = [
                'id' => $candidate['id'],
                'votes' => $candidate['votes_received']
            ];
        }

        $winners = [];
        foreach ($positionCollegeVotes as $key => $candidatesForPositionCollege) {
            $maxVotes = -1;
            $winnerId = null;
            $totalVotes = array_sum(array_column($candidatesForPositionCollege, 'votes'));
            $candidateCount = count($candidatesForPositionCollege);

            if ($candidateCount === 1) {
                // If only one candidate, they win (even with 0 votes)
                $winnerId = $candidatesForPositionCollege[0]['id'];
            } elseif ($totalVotes > 0) {
                // If votes exist, highest vote count wins
                foreach ($candidatesForPositionCollege as $cand) {
                    if ($cand['votes'] > $maxVotes) {
                        $maxVotes = $cand['votes'];
                        $winnerId = $cand['id'];
                    }
                }
            }
            $winners[$key] = $winnerId; // Null if multiple candidates and no votes
        }

        $results = [];
        foreach ($candidates as $candidate) {
            $candidate_name = trim($candidate['full_name'] ?? '');
            if (empty($candidate_name)) {
                $candidate_name = "Candidate ID {$candidate['id']} (Name Missing)";
            }

            $position = $candidate['position'] ?? 'Unknown';
            $college = $candidate['college'] ?? 'Unknown';
            $key = "$position|$college";
            $outcome = ($winners[$key] === $candidate['id']) ? 'Won' : 'Lost';

            $stmt = $archivePdo->prepare("
                INSERT INTO archived_candidates (id, election_name, candidate_name, position, party, 
                    filed_on, outcome, votes_received, archived_on, college)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    election_name = VALUES(election_name),
                    candidate_name = VALUES(candidate_name),
                    position = VALUES(position),
                    party = VALUES(party),
                    filed_on = VALUES(filed_on),
                    outcome = VALUES(outcome),
                    votes_received = VALUES(votes_received),
                    archived_on = VALUES(archived_on),
                    college = VALUES(college)
            ");
            $stmt->execute([
                $candidate['id'], $candidate['election_name'], $candidate_name, 
                $candidate['position'], $candidate['party'], $candidate['filed_on'], 
                $outcome, $candidate['votes_received'], date('Y-m-d'), $candidate['college']
            ]);
            $results[] = "Candidate archived: $candidate_name (Position: $position, College: $college, Outcome: $outcome, Votes: {$candidate['votes_received']})";
        }
        return implode("\n", $results);
    } catch (PDOException $e) {
        return "Error archiving candidates: " . $e->getMessage();
    }
}