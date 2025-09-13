<?php
echo "=== XAMPP MYSQL CONNECTION TEST ===\n\n";

// Test basic MySQL connection with XAMPP defaults
try {
    echo "1. Testing basic MySQL connection (root, no password)...\n";
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    echo "✅ Basic MySQL connection successful\n\n";
    
    // Check if note_app database exists
    echo "2. Checking if 'note_app' database exists...\n";
    $result = $pdo->query('SHOW DATABASES LIKE "note_app"');
    if ($result->rowCount() > 0) {
        echo "✅ Database 'note_app' exists\n\n";
        
        // Test connection to note_app database
        echo "3. Testing connection to note_app database...\n";
        $db = new PDO('mysql:host=localhost;dbname=note_app', 'root', '');
        echo "✅ Connection to note_app database successful\n\n";
        
        // Check tables
        echo "4. Checking tables in note_app database...\n";
        $tables = $db->query('SHOW TABLES');
        $tableCount = $tables->rowCount();
        echo "Found $tableCount tables:\n";
        while ($row = $tables->fetch()) {
            echo "  - " . array_values($row)[0] . "\n";
        }
        
    } else {
        echo "❌ Database 'note_app' does NOT exist\n";
        echo "👉 SOLUTION: Create the database using phpMyAdmin or run:\n";
        echo "   CREATE DATABASE note_app;\n\n";
        
        // Try to create the database
        echo "5. Attempting to create 'note_app' database...\n";
        $pdo->exec('CREATE DATABASE note_app');
        echo "✅ Database 'note_app' created successfully\n";
        echo "👉 Now import the schema: database/mysql_schema.sql\n";
    }
    
} catch (PDOException $e) {
    echo "❌ MySQL Connection Error: " . $e->getMessage() . "\n\n";
    
    if ($e->getCode() == 1045) {
        echo "SOLUTION for Access Denied Error:\n";
        echo "1. Make sure XAMPP MySQL is running\n";
        echo "2. Try these alternative credentials:\n";
        echo "   - User: root, Password: (empty)\n";
        echo "   - User: root, Password: root\n";
        echo "   - User: root, Password: password\n";
        echo "3. Check XAMPP phpMyAdmin to verify credentials\n";
    } elseif ($e->getCode() == 2002) {
        echo "SOLUTION for Connection Refused Error:\n";
        echo "1. Start XAMPP Control Panel\n";
        echo "2. Start MySQL service\n";
        echo "3. Make sure port 3306 is not blocked\n";
    }
}
?>