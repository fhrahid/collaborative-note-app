<?php
// Detect environment
$isProduction = !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', 'localhost:8000']);

// Database configuration
if ($isProduction) {
    // Production MySQL configuration
    define('DB_HOST', 'localhost');  // Your cPanel MySQL host
    define('DB_NAME', 'your_database_name');  // Replace with your actual database name
    define('DB_USER', 'your_username');       // Replace with your actual username
    define('DB_PASS', 'your_password');       // Replace with your actual password
    define('DB_TYPE', 'mysql');
} else {
    // Local SQLite configuration
    define('DB_PATH', __DIR__ . '/../database/notes.db');
    define('DB_TYPE', 'sqlite');
}

// Database connection
try {
    if ($isProduction) {
        // MySQL connection for production
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
    } else {
        // SQLite connection for local development
        if (!file_exists(DB_PATH)) {
            $dir = dirname(DB_PATH);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        $pdo = new PDO(
            "sqlite:" . DB_PATH,
            null,
            null,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
} catch(PDOException $exception) {
    die("Database connection error: " . $exception->getMessage());
}

// Legacy variable for backward compatibility
$db = $pdo;
?>