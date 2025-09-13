<?php
require_once 'config/config_local.php';

echo "Testing custom URL and collaborator functionality...\n\n";

// Test 1: Check if get_base_url function works
echo "1. Testing get_base_url() function:\n";
if (function_exists('get_base_url')) {
    echo "✓ get_base_url() function exists\n";
    // Simulate a request for testing
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['SCRIPT_NAME'] = '/note-app/test.php';
    $_SERVER['HTTPS'] = 'off';
    
    $base_url = get_base_url();
    echo "✓ Base URL: $base_url\n";
} else {
    echo "❌ get_base_url() function not found\n";
}

// Test 2: Check database schema
echo "\n2. Testing database schema:\n";
try {
    $stmt = $db->query("DESCRIBE notes");
    $columns = $stmt->fetchAll();
    
    $share_token_found = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'share_token') {
            $share_token_found = true;
            echo "✓ share_token column: {$column['Type']}\n";
            if (strpos($column['Type'], '20') !== false) {
                echo "✓ Column supports 20 characters\n";
            } else {
                echo "❌ Column might not support 20 characters\n";
            }
            break;
        }
    }
    
    if (!$share_token_found) {
        echo "❌ share_token column not found\n";
    }
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 3: Check collaborators table
echo "\n3. Testing collaborators table:\n";
try {
    $stmt = $db->query("DESCRIBE collaborators");
    $columns = $stmt->fetchAll();
    echo "✓ Collaborators table exists with " . count($columns) . " columns\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']}: {$column['Type']}\n";
    }
} catch (PDOException $e) {
    echo "❌ Collaborators table error: " . $e->getMessage() . "\n";
}

// Test 4: Test search_users function
echo "\n4. Testing search_users function:\n";
if (function_exists('search_users')) {
    echo "✓ search_users() function exists\n";
    try {
        $users = search_users('admin', 999, $db); // Search for admin user, exclude user ID 999
        echo "✓ Search function works, found " . count($users) . " users\n";
    } catch (Exception $e) {
        echo "❌ Search function error: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ search_users() function not found\n";
}

echo "\n🎉 Test completed!\n";
echo "Custom URL and collaborator functionality should now be working.\n\n";

echo "Next steps:\n";
echo "1. Visit your note app in a browser\n";
echo "2. Create or edit a note\n";
echo "3. Go to Share settings\n";
echo "4. Test custom URL generation\n";
echo "5. Test collaborator addition\n";
?>