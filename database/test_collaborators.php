<?php
// Test collaborators functionality
require_once '../config/database.php';
require_once '../config/config_local.php';

echo "=== TESTING COLLABORATORS FUNCTIONALITY ===\n\n";

try {
    // Check if collaborators table exists and has data
    echo "1. Checking collaborators table structure:\n";
    $result = $db->query("DESCRIBE collaborators");
    while ($row = $result->fetch()) {
        echo "  {$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
    }
    echo "\n";
    
    // Check if there are any collaborators
    echo "2. Checking existing collaborators:\n";
    $result = $db->query("SELECT COUNT(*) as count FROM collaborators");
    $count = $result->fetch()['count'];
    echo "  Found $count collaborators in database\n\n";
    
    // Test the updated can_user_access_note function
    echo "3. Testing permission checking function:\n";
    
    // Test with a non-existent note
    $permission = can_user_access_note(999, 1, $db);
    if ($permission === false) {
        echo "  ✓ Non-existent note correctly returns false\n";
    } else {
        echo "  ✗ Non-existent note incorrectly returned: $permission\n";
    }
    
    // Get a real note to test with
    $result = $db->query("SELECT id, user_id FROM notes LIMIT 1");
    $note = $result->fetch();
    
    if ($note) {
        echo "  Testing with note ID: {$note['id']} (owned by user {$note['user_id']})\n";
        
        // Test owner access
        $permission = can_user_access_note($note['id'], $note['user_id'], $db);
        if ($permission === 'owner') {
            echo "  ✓ Note owner correctly has 'owner' permission\n";
        } else {
            echo "  ✗ Note owner incorrectly has '$permission' permission\n";
        }
        
        // Test non-owner access (should be false unless shared/collaborator)
        $other_user_id = $note['user_id'] + 1;
        $permission = can_user_access_note($note['id'], $other_user_id, $db);
        echo "  User $other_user_id access to note {$note['id']}: " . ($permission ?: 'false') . "\n";
    }
    
    echo "\n4. Testing file size formatting function:\n";
    if (function_exists('format_file_size')) {
        echo "  ✓ format_file_size function is available\n";
        echo "  Testing: format_file_size(1024) = " . format_file_size(1024) . "\n";
        echo "  Testing: format_file_size(1048576) = " . format_file_size(1048576) . "\n";
    } else {
        echo "  ✗ format_file_size function is NOT available\n";
    }
    
    echo "\n=== COLLABORATORS FUNCTIONALITY TEST COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}
?>