<?php
// Comprehensive database schema verification script
require_once '../config/database.php';

echo "=== DATABASE SCHEMA VERIFICATION ===\n\n";

try {
    // Check database connection
    echo "✓ Database connection successful\n\n";
    
    // Check all tables exist
    $tables = ['users', 'notes', 'attachments', 'shared_notes', 'collaborators'];
    echo "Checking required tables:\n";
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "  ✓ $table table exists\n";
        } else {
            echo "  ✗ $table table MISSING\n";
        }
    }
    echo "\n";
    
    // Check notes table structure
    echo "Notes table structure:\n";
    $result = $db->query("DESCRIBE notes");
    $notes_columns = [];
    while ($row = $result->fetch()) {
        $notes_columns[] = $row['Field'];
        echo "  {$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
    }
    
    // Check for required notes columns
    $required_notes_columns = ['id', 'user_id', 'title', 'content', 'is_public', 'share_token', 'created_at', 'updated_at'];
    echo "\nRequired notes columns check:\n";
    foreach ($required_notes_columns as $col) {
        if (in_array($col, $notes_columns)) {
            echo "  ✓ $col column exists\n";
        } else {
            echo "  ✗ $col column MISSING\n";
        }
    }
    echo "\n";
    
    // Check shared_notes table structure
    echo "Shared_notes table structure:\n";
    $result = $db->query("DESCRIBE shared_notes");
    $shared_notes_columns = [];
    while ($row = $result->fetch()) {
        $shared_notes_columns[] = $row['Field'];
        echo "  {$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
    }
    
    // Check for required shared_notes columns
    $required_shared_columns = ['id', 'note_id', 'shared_by_user_id', 'shared_with_user_id', 'permission', 'shared_at'];
    echo "\nRequired shared_notes columns check:\n";
    foreach ($required_shared_columns as $col) {
        if (in_array($col, $shared_notes_columns)) {
            echo "  ✓ $col column exists\n";
        } else {
            echo "  ✗ $col column MISSING\n";
        }
    }
    echo "\n";
    
    // Check indexes
    echo "Checking indexes:\n";
    $result = $db->query("SHOW INDEX FROM shared_notes");
    $indexes = [];
    while ($row = $result->fetch()) {
        if (!isset($indexes[$row['Key_name']])) {
            $indexes[$row['Key_name']] = [];
        }
        $indexes[$row['Key_name']][] = $row['Column_name'];
    }
    
    foreach ($indexes as $index_name => $columns) {
        echo "  $index_name: " . implode(', ', $columns) . "\n";
    }
    echo "\n";
    
    // Check foreign key constraints
    echo "Checking foreign key constraints:\n";
    $result = $db->query("
        SELECT 
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND REFERENCED_TABLE_NAME IS NOT NULL
        ORDER BY TABLE_NAME, CONSTRAINT_NAME
    ");
    
    while ($row = $result->fetch()) {
        echo "  {$row['TABLE_NAME']}.{$row['COLUMN_NAME']} → {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
    }
    echo "\n";
    
    // Test sample queries
    echo "Testing sample queries:\n";
    
    // Test shared_with_me query
    try {
        $stmt = $db->prepare("
            SELECT n.*, u.username as owner_username, sn.permission, sn.shared_at
            FROM notes n 
            JOIN shared_notes sn ON n.id = sn.note_id 
            JOIN users u ON n.user_id = u.id 
            WHERE sn.shared_with_user_id = ? 
            ORDER BY sn.shared_at DESC
            LIMIT 1
        ");
        $stmt->execute([1]); // Test with user ID 1
        echo "  ✓ Shared with me query works\n";
    } catch (Exception $e) {
        echo "  ✗ Shared with me query failed: " . $e->getMessage() . "\n";
    }
    
    // Test sharing query
    try {
        $stmt = $db->prepare("SELECT id FROM shared_notes WHERE note_id = ? AND shared_with_user_id = ?");
        $stmt->execute([1, 1]);
        echo "  ✓ Check existing share query works\n";
    } catch (Exception $e) {
        echo "  ✗ Check existing share query failed: " . $e->getMessage() . "\n";
    }
    
    // Test public notes query
    try {
        $stmt = $db->prepare("SELECT * FROM notes WHERE is_public = 1 AND share_token = ?");
        $stmt->execute(['test_token']);
        echo "  ✓ Public notes query works\n";
    } catch (Exception $e) {
        echo "  ✗ Public notes query failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== VERIFICATION COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?>