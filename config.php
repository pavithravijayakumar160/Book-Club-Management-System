<?php
// Update these settings to match your local MySQL setup.
$host = 'localhost';
$dbname = 'book_club_db';
$username = 'root';
$password = ''; // XAMPP default is often blank; change if your MySQL has a password.

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
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Check config.php and confirm MySQL is running and database.sql has been imported.');
}
