<?php
// Database update script to fix shared_notes table structure
require_once '../config/database.php';

try {
    // Start transaction
    $db->beginTransaction();
    
    // First, let's see what the current table structure looks like
    echo "Current shared_notes table structure:\n";
    $result = $db->query("DESCRIBE shared_notes");
    while ($row = $result->fetch()) {
        echo "  {$row['Field']} - {$row['Type']}\n";
    }
    echo "\n";
    
    // Check if the table has the old structure (user_id) or new structure (shared_with_user_id)
    $columns = $db->query("SHOW COLUMNS FROM shared_notes LIKE 'user_id'")->fetchAll();
    $has_user_id = count($columns) > 0;
    
    $columns = $db->query("SHOW COLUMNS FROM shared_notes LIKE 'shared_with_user_id'")->fetchAll();
    $has_shared_with_user_id = count($columns) > 0;
    
    $columns = $db->query("SHOW COLUMNS FROM shared_notes LIKE 'shared_by_user_id'")->fetchAll();
    $has_shared_by_user_id = count($columns) > 0;
    
    if ($has_user_id && !$has_shared_with_user_id && !$has_shared_by_user_id) {
        echo "Detected old table structure with 'user_id' column.\n";
        echo "Converting to new structure with 'shared_by_user_id' and 'shared_with_user_id'...\n\n";
        
        // Add the new columns
        echo "Adding shared_by_user_id column...\n";
        $db->exec("ALTER TABLE shared_notes ADD COLUMN shared_by_user_id INT NOT NULL AFTER note_id");
        
        echo "Adding shared_with_user_id column...\n";
        $db->exec("ALTER TABLE shared_notes ADD COLUMN shared_with_user_id INT NOT NULL AFTER shared_by_user_id");
        
        // Copy data from user_id to shared_with_user_id
        echo "Copying data from user_id to shared_with_user_id...\n";
        $db->exec("UPDATE shared_notes SET shared_with_user_id = user_id");
        
        // For shared_by_user_id, we'll need to make an assumption
        // Let's set it to the note owner for existing shares
        echo "Setting shared_by_user_id to note owners for existing shares...\n";
        $db->exec("
            UPDATE shared_notes sn 
            JOIN notes n ON sn.note_id = n.id 
            SET sn.shared_by_user_id = n.user_id
        ");
        
        // Add foreign key constraints for new columns
        echo "Adding foreign key constraints...\n";
        $db->exec("ALTER TABLE shared_notes ADD CONSTRAINT fk_shared_by_user FOREIGN KEY (shared_by_user_id) REFERENCES users(id) ON DELETE CASCADE");
        $db->exec("ALTER TABLE shared_notes ADD CONSTRAINT fk_shared_with_user FOREIGN KEY (shared_with_user_id) REFERENCES users(id) ON DELETE CASCADE");
        
        // Drop the old user_id column and its constraint
        echo "Dropping old user_id column...\n";
        // First drop the foreign key constraint
        $result = $db->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'shared_notes' 
            AND COLUMN_NAME = 'user_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $constraint = $result->fetch();
        if ($constraint) {
            $db->exec("ALTER TABLE shared_notes DROP FOREIGN KEY {$constraint['CONSTRAINT_NAME']}");
        }
        
        // Drop the old unique key constraint
        $result = $db->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'shared_notes' 
            AND CONSTRAINT_TYPE = 'UNIQUE'
            AND CONSTRAINT_NAME = 'unique_share'
        ");
        $constraint = $result->fetch();
        if ($constraint) {
            $db->exec("ALTER TABLE shared_notes DROP INDEX unique_share");
        }
        
        // Now drop the user_id column
        $db->exec("ALTER TABLE shared_notes DROP COLUMN user_id");
        
        // Add new unique constraint
        echo "Adding new unique constraint...\n";
        $db->exec("ALTER TABLE shared_notes ADD CONSTRAINT unique_share UNIQUE (note_id, shared_with_user_id)");
        
        echo "Table structure update completed successfully!\n\n";
        
    } elseif ($has_shared_with_user_id && $has_shared_by_user_id) {
        echo "Table already has the correct structure!\n\n";
    } else {
        echo "Unexpected table structure. Please check manually.\n\n";
    }
    
    // Show final structure
    echo "Final shared_notes table structure:\n";
    $result = $db->query("DESCRIBE shared_notes");
    while ($row = $result->fetch()) {
        echo "  {$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
    }
    
    // Commit transaction
    $db->commit();
    echo "\nDatabase update completed successfully!\n";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollback();
    echo "Error updating database: " . $e->getMessage() . "\n";
    echo "Transaction rolled back.\n";
}
?>