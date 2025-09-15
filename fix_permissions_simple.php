<?php
require_once 'config/config_local.php';

echo "Simplifying permissions to just 'read' and 'edit'...\n";

try {
    // Update the enum to use only read and edit
    $db->exec("ALTER TABLE shared_notes MODIFY COLUMN permission ENUM('read', 'edit') DEFAULT 'read'");
    echo "✓ Updated permission enum to use only 'read' and 'edit'\n";
    
    // Update any existing 'write' permissions to 'edit' and remove any 'admin'
    $stmt = $db->prepare("UPDATE shared_notes SET permission = 'edit' WHERE permission IN ('write', 'admin')");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "✓ Updated $updated existing permissions to 'edit'\n";
    
    // Show current table structure
    $stmt = $db->query('DESCRIBE shared_notes');
    foreach($stmt->fetchAll() as $col) {
        if($col['Field'] === 'permission') {
            echo "Final permission type: " . $col['Type'] . "\n";
        }
    }
    
    echo "✅ Simple permission system (read/edit only) is now active!\n";
    
} catch (Exception $e) {
    echo "❌ Error updating permissions: " . $e->getMessage() . "\n";
}
?>