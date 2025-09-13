<?php
// Database update script to add missing columns for public sharing
require_once 'config/database.php';

try {
    echo "Adding missing columns to notes table...\n";
    
    // Add is_public column
    $pdo->exec("ALTER TABLE notes ADD COLUMN is_public TINYINT(1) DEFAULT 0");
    echo "✅ Added is_public column\n";
    
    // Add share_token column
    $pdo->exec("ALTER TABLE notes ADD COLUMN share_token VARCHAR(32) NULL");
    echo "✅ Added share_token column\n";
    
    echo "\n🎉 Database update completed successfully!\n";
    echo "Public link generation should now work.\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "ℹ️ Columns already exist - no update needed.\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
?>