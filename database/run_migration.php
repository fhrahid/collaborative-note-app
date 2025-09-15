<?php
require_once 'config/config_local.php';

echo "Running database migration for custom URLs...\n";

try {
    // Modify the share_token column to support custom URLs up to 20 characters
    echo "Updating share_token column length...\n";
    $db->exec("ALTER TABLE notes MODIFY COLUMN share_token VARCHAR(20) NULL");
    echo "✓ share_token column updated to VARCHAR(20)\n";

    // Add unique index on share_token to ensure no duplicates
    echo "Adding unique index...\n";
    try {
        $db->exec("DROP INDEX idx_notes_share_token ON notes");
        echo "✓ Dropped existing index\n";
    } catch (PDOException $e) {
        echo "ℹ No existing index to drop\n";
    }
    
    $db->exec("CREATE UNIQUE INDEX idx_notes_share_token ON notes(share_token)");
    echo "✓ Created unique index on share_token\n";

    // Show updated table structure
    echo "\nUpdated table structure:\n";
    $stmt = $db->query("DESCRIBE notes");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'share_token') {
            echo "share_token: {$column['Type']} {$column['Null']} {$column['Key']}\n";
        }
    }

    echo "\n✅ Migration completed successfully!\n";
    echo "Your database now supports custom URLs up to 20 characters.\n";

} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>