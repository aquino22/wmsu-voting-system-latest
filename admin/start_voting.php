<?php
require_once 'includes/conn.php'; // Database connection
session_start(); // Start session to use $_SESSION

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['election_id'])) {
    $election_id = $_GET['election_id'];

    try {
        // 1. Fetch election name
        $stmt = $pdo->prepare("SELECT election_name FROM elections WHERE id = :election_id");
        $stmt->execute([':election_id' => $election_id]);
        $election_name = $stmt->fetchColumn();
        if (!$election_name) {
            throw new Exception("Election not found.");
        }

        // 2. Get precinct name
        $stmt = $pdo->prepare("SELECT precinct_name FROM precinct_elections WHERE election_name = :election_name");
        $stmt->execute([':election_name' => $election_name]);
        $precinctData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$precinctData) {
            throw new Exception("No precinct found for the given election name.");
        }
        $precinct_name = $precinctData['precinct_name'];

        // 3. Get college
        $stmt = $pdo->prepare("SELECT college FROM precincts WHERE name = :precinct_name");
        $stmt->execute([':precinct_name' => $precinct_name]);
        $precinctInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$precinctInfo) {
            throw new Exception("No precinct found for the given election name.");
        }
        $college = $precinctInfo['college'];

        // 4. Get student_ids from voters
        $stmt = $pdo->prepare("SELECT student_id FROM voters WHERE college = :college");
        $stmt->execute([':college' => $college]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$students) {
            throw new Exception("No students found for this college.");
        }

        // 5. Insert voters into precinct_voters
        $stmt_insert = $pdo->prepare("INSERT INTO precinct_voters (precinct, student_id) VALUES (:precinct, :student_id)");
        foreach ($students as $student) {
            $stmt_insert->execute([
                ':precinct' => $precinct_name,
                ':student_id' => $student['student_id']
            ]);
        }

        // 6. Fetch event_id
        $stmt_event = $pdo->prepare("SELECT id FROM events WHERE candidacy = :election_name");
        $stmt_event->execute([':election_name' => $election_name]);
        $event_id = $stmt_event->fetchColumn();
        if (!$event_id) {
            throw new Exception("Election not found under events.");
        }

        // 7. Fetch registration data
        $stmt_registration = $pdo->prepare("SELECT registration_data FROM event_registrations WHERE event_id = :event_id");
        $stmt_registration->execute([':event_id' => $event_id]);
        $registrations = $stmt_registration->fetchAll(PDO::FETCH_ASSOC);
        if (!$registrations) {
            throw new Exception("No registration data found for the given event.");
        }

        // 8. Extract candidate_ids
        $candidate_ids_adder = [];
        foreach ($registrations as $registration) {
            $decoded_registration_data = json_decode($registration['registration_data'], true);
            if ($decoded_registration_data === null) {
                throw new Exception("Invalid registration data format.");
            }
            if (isset($decoded_registration_data['form_data']['candidate_id'])) {
                $candidate_ids_adder[] = $decoded_registration_data['form_data']['candidate_id'];
            }
        }

        // 9. Create voting table
        $voting_table_name = 'voting_' . str_replace(' ', '_', strtolower($election_name));
        $stmt_check = $pdo->prepare("SHOW TABLES LIKE :table_name");
        $stmt_check->execute([':table_name' => $voting_table_name]);
        if ($stmt_check->rowCount() == 0) {
            $create_table_query = "CREATE TABLE `$voting_table_name` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                election_name VARCHAR(255) NOT NULL,
                student_id VARCHAR(20) NOT NULL,
                status ENUM('awaiting', 'voted') DEFAULT 'awaiting'
            )";
            $pdo->exec($create_table_query);
        }

        // 10. Create voting statistics table
        $voting_stats_table_name = 'voting_statistics_' . str_replace(' ', '_', strtolower($election_name));
        $stmt_check_stats = $pdo->prepare("SHOW TABLES LIKE :table_name");
        $stmt_check_stats->execute([':table_name' => $voting_stats_table_name]);
        if ($stmt_check_stats->rowCount() == 0) {
            $create_stats_table_query = "CREATE TABLE `$voting_stats_table_name` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                candidate_id VARCHAR(255) NOT NULL,
                number_of_votes INT DEFAULT 0
            )";
            $pdo->exec($create_stats_table_query);
        }

        // 11. Insert candidate_ids into voting statistics
        if (!empty($candidate_ids_adder)) {
            $stmt_insert_stats = $pdo->prepare("INSERT INTO `$voting_stats_table_name` (candidate_id, number_of_votes) VALUES (:candidate_id, 0)");
            foreach ($candidate_ids_adder as $candidate_id) {
                $stmt_insert_stats->execute([':candidate_id' => $candidate_id]);
            }
        } else {
            throw new Exception("No candidates found in form data.");
        }

        // 12. Insert all voters into voting table
        $stmt = $pdo->prepare("SELECT student_id FROM voters");
        $stmt->execute();
        $student_ids = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$student_ids) {
            throw new Exception("No students found in the 'voters' table.");
        }

        $stmt_insert = $pdo->prepare("INSERT INTO `$voting_table_name` (election_name, student_id, status) VALUES (:election_name, :student_id, 'awaiting')");
        foreach ($student_ids as $student) {
            $stmt_insert->execute([
                ':election_name' => $election_name,
                ':student_id' => $student['student_id']
            ]);
        }

        $status = "Ongoing";

        // 13. Set voting period
        $start_date = date('Y-m-d H:i:s'); // Current date and time (e.g., 2025-03-05 14:30:00)
        $end_date = null; // Initially NULL, to be set later when voting ends
        $stmt_period = $pdo->prepare("INSERT INTO voting_periods (event_id, start_date, end_date, status) VALUES (:event_id, :start_date, :end_date, :status)");
        $stmt_period->execute([
            ':event_id' => $event_id,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':status' => $status
        ]);

        $_SESSION['STATUS'] = "VOTING_SETUP_SUCCESS";
        $_SESSION['MESSAGE'] = "Voting setup completed successfully!";
    } catch (Exception $e) {
        $_SESSION['STATUS'] = "VOTING_SETUP_ERROR";
        $_SESSION['MESSAGE'] = $e->getMessage();
    }
} else {
    $_SESSION['STATUS'] = "VOTING_SETUP_ERROR";
    $_SESSION['MESSAGE'] = "Invalid request method or missing election_id.";
}

// Redirect back to the calling page
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php'; // Fallback to index.php if no referer
header("Location: $referer");
exit;
?>