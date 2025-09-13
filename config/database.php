<?php
// MySQL Database Configuration
// Works with both XAMPP (local) and cPanel (production)

// Detect environment
$isProduction = isset($_SERVER['HTTP_HOST']) && !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', 'localhost:8000']);

if ($isProduction) {
    // Production MySQL configuration (cPanel)
    define('DB_HOST', 'localhost');  // Your cPanel MySQL host
    define('DB_NAME', 'your_database_name');  // Replace with your actual database name
    define('DB_USER', 'your_username');       // Replace with your actual username
    define('DB_PASS', 'your_password');       // Replace with your actual password
} else {
    // Local XAMPP MySQL configuration
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'note_app');        // XAMPP database name
    define('DB_USER', 'root');            // XAMPP default user
    define('DB_PASS', '');                // XAMPP default password (empty)
}

// MySQL Database connection
try {
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
} catch(PDOException $exception) {
    die("MySQL Database connection error: " . $exception->getMessage() . 
        "<br><br>Make sure:<br>1. XAMPP MySQL is running (locally)<br>2. Database exists<br>3. Credentials are correct");
}

// Legacy variable for backward compatibility
$db = $pdo;
?>