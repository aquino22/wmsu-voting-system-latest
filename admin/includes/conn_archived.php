<?php
// $host = "sql305.hstn.me"; // Change to your database host
// $dbname = "mseet_40355535_wmsu_voting_system_archived"; // Change to your database name
// $username = "mseet_40355535"; // Change to your database username
// $password = "m5UZShKEs8iY"; // Change to your database password

$host = "localhost"; // Change to your database host
$dbname = "wmsu_voting_system_archived"; // Change to your database name
$username = "root"; // Change to your database username
$password = ""; // Change to your database password

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Set PDO error mode to Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Set PDO default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle connection error
    die("Connection failed: " . $e->getMessage());
}
