<?php
session_start();
require_once '../includes/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

$voting_period_id = (int)($_POST['voting_period_id'] ?? 0);
$student_id = $_POST['student_id'] ?? '';

if ($voting_period_id <= 0 || empty($student_id)) {
    die("Invalid voting period or student ID.");
}

// Verify session and parameters match
if (!isset($_SESSION['student_id']) || $_SESSION['student_id'] !== $student_id) {
    die("Session mismatch or unauthorized access.");
}

// Fetch the election_name from voting_periods
$stmt = $pdo->prepare("SELECT name FROM voting_periods WHERE id = :voting_period_id");
$stmt->execute([':voting_period_id' => $voting_period_id]);
$election_name = $stmt->fetchColumn();

if (!$election_name) {
    die("Voting period not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cast Your Vote</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Cast Your Vote</h2>
        <form id="voteForm" class="mt-4">
            <input type="hidden" name="voting_period_id" value="<?php echo $voting_period_id; ?>">
            <input type="hidden" name="qrData" value="<?php echo htmlspecialchars($student_id); ?>">
            <div class="mb-3">
                <?php echo $voting_period_id; // For debugging ?>
                <label for="candidate_id" class="form-label">Select Candidate:</label>
                <select name="candidate_id" id="candidate_id" class="form-select" required>
                    <option value="">Select a Candidate</option>
                    <?php
                    // Query candidates with their full_name from candidate_responses
                    $stmt = $pdo->prepare("
                        SELECT c.id, cr.value AS full_name
                        FROM candidates c
                        JOIN registration_forms rf ON c.form_id = rf.id
                        JOIN candidate_responses cr ON c.id = cr.candidate_id
                        JOIN form_fields ff ON cr.field_id = ff.id
                        WHERE rf.election_name = :election_name
                        AND rf.status = 'active'
                        AND c.status = 'accept'
                        AND ff.field_name = 'full_name'
                    ");
                    $stmt->execute([':election_name' => $election_name]);
                    while ($candidate = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='{$candidate['id']}'>{$candidate['full_name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Submit Vote</button>
        </form>
    </div>

    <script>
    $(document).ready(function() {
        $('#voteForm').on('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Submitting Vote...',
                text: 'Please wait.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: 'submit_vote.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.status === 'success') {
                        Swal.fire('Success!', response.message, 'success').then(() => {
                            window.location.href = 'qr_scanner.php'; // Redirect back to QR scanner
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.close();
                    Swal.fire('Error!', 'Failed to submit vote.', 'error');
                }
            });
        });
    });
    </script>
</body>
</html>