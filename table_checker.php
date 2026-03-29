<?php
// --- PDO connection ---
$host = 'localhost';
$db   = 'wmsu_voting_system';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // --- Fetch all tables in the database ---
    $stmt = $pdo->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :db");
    $stmt->execute(['db' => $db]);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "<h3>Table: <b>$table</b></h3>";
        echo "<ul>";

        // --- Fetch columns of the current table ---
        $stmtCols = $pdo->prepare("
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table
        ");
        $stmtCols->execute(['db' => $db, 'table' => $table]);
        $columns = $stmtCols->fetchAll();

        foreach ($columns as $col) {
            echo "<li><b>{$col['COLUMN_NAME']}</b> ({$col['DATA_TYPE']}) - Nullable: {$col['IS_NULLABLE']} - Default: {$col['COLUMN_DEFAULT']}</li>";
        }

        echo "</ul><hr>";
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
