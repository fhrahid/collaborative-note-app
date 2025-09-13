<?php
require_once 'config/config_local.php';

echo "Updating shared_notes permissions to use consistent naming...\n";

try {
    // Update the enum to use read, edit, admin (consistent with the UI)
    $db->exec("ALTER TABLE shared_notes MODIFY COLUMN permission ENUM('read', 'edit', 'admin') DEFAULT 'read'");
    echo "✓ Updated permission enum to use 'read', 'edit', 'admin'\n";
    
    // Update any existing 'write' permissions to 'edit'
    $stmt = $db->prepare("UPDATE shared_notes SET permission = 'edit' WHERE permission = 'write'");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "✓ Updated $updated existing 'write' permissions to 'edit'\n";
    
    // Show current table structure
    $stmt = $db->query('DESCRIBE shared_notes');
    foreach($stmt->fetchAll() as $col) {
        if($col['Field'] === 'permission') {
            echo "Final permission type: " . $col['Type'] . "\n";
        }
    }
    
    echo "✅ Permission system updated successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error updating permissions: " . $e->getMessage() . "\n";
}
?>