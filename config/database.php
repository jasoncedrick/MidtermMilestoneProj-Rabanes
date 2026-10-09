<?php
// This is the only file that opens a database connection.
// Default settings for MySQL/MariaDB in XAMPP on Windows.
$host = '127.0.0.1';
$dbname = 'kusina_barangay';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $error) {
    error_log($error->getMessage());
    http_response_code(500);
    exit('Could not connect to the database. Check config/database.php and make sure MySQL is running.');
}
