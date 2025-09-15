<?php
// Add a test collaborator to verify the permission system
require_once '../config/database.php';

try {
    // Get first note and users
    $note_result = $db->query("SELECT id, user_id FROM notes LIMIT 1");
    $note = $note_result->fetch();
    
    $users_result = $db->query("SELECT id FROM users WHERE id != {$note['user_id']} LIMIT 1");
    $other_user = $users_result->fetch();
    
    if ($note && $other_user) {
        echo "Adding test collaborator...\n";
        echo "Note ID: {$note['id']} (owned by user {$note['user_id']})\n";
        echo "Adding user {$other_user['id']} as collaborator with 'write' permission\n\n";
        
        // Add collaborator
        $stmt = $db->prepare("INSERT INTO collaborators (note_id, user_id, permission) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE permission = VALUES(permission)");
        $stmt->execute([$note['id'], $other_user['id'], 'write']);
        
        echo "✓ Collaborator added successfully\n";
        
        // Test the permission
        require_once '../config/config_local.php';
        $permission = can_user_access_note($note['id'], $other_user['id'], $db);
        echo "Permission check result: $permission\n";
        
        if ($permission === 'write') {
            echo "✓ Collaborator permission system working correctly!\n";
        } else {
            echo "✗ Expected 'write' permission, got '$permission'\n";
        }
        
    } else {
        echo "No suitable notes or users found for testing\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>