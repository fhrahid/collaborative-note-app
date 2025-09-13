<?php
// Test upload functionality
echo "<h1>PHP Upload Configuration Test</h1>";

echo "<h2>PHP Configuration:</h2>";
echo "file_uploads: " . (ini_get('file_uploads') ? 'enabled' : 'disabled') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";

echo "<h2>Upload Directory Test:</h2>";
$upload_dir = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
echo "Upload directory: " . $upload_dir . "<br>";
echo "Directory exists: " . (file_exists($upload_dir) ? 'Yes' : 'No') . "<br>";
echo "Directory writable: " . (is_writable($upload_dir) ? 'Yes' : 'No') . "<br>";

if (file_exists($upload_dir)) {
    $files = scandir($upload_dir);
    echo "Files in upload directory: " . count($files) - 2 . "<br>"; // -2 for . and ..
}

echo "<h2>Database Connection Test:</h2>";
try {
    require_once 'config/config.php';
    echo "Database connection: OK<br>";
    
    // Test if attachments table exists
    $stmt = $db->query("SHOW TABLES LIKE 'attachments'");
    if ($stmt->rowCount() > 0) {
        echo "Attachments table: EXISTS<br>";
        
        // Count attachments
        $stmt = $db->query("SELECT COUNT(*) as count FROM attachments");
        $count = $stmt->fetch();
        echo "Total attachments in database: " . $count['count'] . "<br>";
    } else {
        echo "Attachments table: NOT FOUND<br>";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h2>Upload Test Result:</h2>";
    $file = $_FILES['test_file'];
    echo "File name: " . $file['name'] . "<br>";
    echo "File size: " . $file['size'] . " bytes<br>";
    echo "File error: " . $file['error'] . "<br>";
    echo "Temp file: " . $file['tmp_name'] . "<br>";
    echo "Temp file exists: " . (file_exists($file['tmp_name']) ? 'Yes' : 'No') . "<br>";
}
?>

<h2>Test File Upload:</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_file" required>
    <button type="submit">Test Upload</button>
</form>