<?php
require_once '../config/config.php';

try {
    echo "🚀 Starting category migration...\n\n";
    
    // Read and execute the SQL migration
    $sql = file_get_contents(__DIR__ . '/add_categories.sql');
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
            echo "✅ Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            echo "⚠️  Warning: " . $e->getMessage() . "\n";
            echo "   Statement: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\n🎉 Category migration completed!\n";
    echo "📊 Verifying results...\n\n";
    
    // Verify the migration
    $stmt = $db->query("DESCRIBE categories");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Categories table columns: " . implode(', ', $columns) . "\n";
    
    $stmt = $db->query("SELECT COUNT(*) FROM categories");
    $count = $stmt->fetchColumn();
    echo "✅ Default categories created: $count\n";
    
    $stmt = $db->query("SELECT COUNT(*) FROM notes WHERE category_id IS NOT NULL");
    $updated = $stmt->fetchColumn();
    echo "✅ Notes updated with categories: $updated\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>