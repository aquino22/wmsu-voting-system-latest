<?php
include('includes/conn.php');
$passwordPlain = "moderator";
$password = password_hash($passwordPlain, PASSWORD_DEFAULT);

// Get semester from POST
$semester = "1st Semester";
// Prepare query
$stmt = $pdo->prepare("SELECT election_name FROM elections WHERE status = 'Ongoing' LIMIT 1");

// Execute the query
$stmt->execute();

// Fetch result
$ongoingElection = $stmt->fetch(PDO::FETCH_ASSOC);

if ($ongoingElection) {
    $electionName = $ongoingElection['election_name'];
} else {
    $electionName = null; // no ongoing election
}




$newpass = "WmsuVoter123!";
$hashedPassword = password_hash($newpass, PASSWORD_DEFAULT);

echo "voter" . " " . $hashedPassword . "<br>";

$newpass = "WmsuAdviser123!";
$hashedPassword = password_hash($newpass, PASSWORD_DEFAULT);

echo "Adviser" . " " . $hashedPassword . "<br>";

$newpass = "WmsuModerator123!";
$hashedPassword = password_hash($newpass, PASSWORD_DEFAULT);

echo "Moderator" . " " . $hashedPassword . "<br>";
