<?php
// Add missing columns to notes table for public sharing
require_once '../config/database.php';

try {
    echo "Adding missing columns to notes table...\n\n";
    
    // Check current notes table structure
    echo "Current notes table structure:\n";
    $result = $db->query("DESCRIBE notes");
    $existing_columns = [];
    while ($row = $result->fetch()) {
        $existing_columns[] = $row['Field'];
        echo "  {$row['Field']} - {$row['Type']}\n";
    }
    echo "\n";
    
    // Add is_public column if it doesn't exist
    if (!in_array('is_public', $existing_columns)) {
        echo "Adding is_public column...\n";
        $db->exec("ALTER TABLE notes ADD COLUMN is_public TINYINT(1) DEFAULT 0 AFTER content");
        echo "✓ is_public column added\n";
    } else {
        echo "✓ is_public column already exists\n";
    }
    
    // Add share_token column if it doesn't exist
    if (!in_array('share_token', $existing_columns)) {
        echo "Adding share_token column...\n";
        $db->exec("ALTER TABLE notes ADD COLUMN share_token VARCHAR(32) NULL AFTER is_public");
        echo "✓ share_token column added\n";
    } else {
        echo "✓ share_token column already exists\n";
    }
    
    // Show final structure
    echo "\nFinal notes table structure:\n";
    $result = $db->query("DESCRIBE notes");
    while ($row = $result->fetch()) {
        echo "  {$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
    }
    
    echo "\n✓ Notes table update completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error updating notes table: " . $e->getMessage() . "\n";
}
?>