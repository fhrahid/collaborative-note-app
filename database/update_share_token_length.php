<?php
// Update share_token column to shorter length
require_once '../config/database.php';

try {
    echo "Updating share_token column length...\n\n";
    
    // Check current column definition
    $result = $db->query("SHOW COLUMNS FROM notes LIKE 'share_token'");
    $column = $result->fetch();
    
    if ($column) {
        echo "Current share_token column: {$column['Type']}\n";
        
        // Update column to shorter length
        $db->exec("ALTER TABLE notes MODIFY COLUMN share_token VARCHAR(6) NULL");
        echo "✓ Updated share_token column to VARCHAR(6)\n\n";
        
        // Show updated column
        $result = $db->query("SHOW COLUMNS FROM notes LIKE 'share_token'");
        $column = $result->fetch();
        echo "New share_token column: {$column['Type']}\n";
        
        // Clear existing tokens to regenerate with new format
        $db->exec("UPDATE notes SET share_token = NULL WHERE share_token IS NOT NULL");
        echo "✓ Cleared existing tokens (they will be regenerated as 6-character tokens)\n";
        
    } else {
        echo "share_token column not found\n";
    }
    
    echo "\n✓ Share token update completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>