<?php
require_once 'config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h1>Upload Test Results</h1>";
    
    echo "<h2>POST Data:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h2>FILES Data:</h2>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    if (!empty($_FILES['test_file']['name'])) {
        echo "<h2>Processing File Upload:</h2>";
        
        $file_data = [
            'name' => $_FILES['test_file']['name'],
            'type' => $_FILES['test_file']['type'],
            'tmp_name' => $_FILES['test_file']['tmp_name'],
            'error' => $_FILES['test_file']['error'],
            'size' => $_FILES['test_file']['size']
        ];
        
        echo "<p>File data:</p><pre>";
        print_r($file_data);
        echo "</pre>";
        
        echo "<p>File validation:</p>";
        $validation = validate_file_upload($file_data);
        print_r($validation);
        
        if ($validation['valid']) {
            echo "<p>✅ File validation passed</p>";
            
            // Test creating a note first
            $stmt = $db->prepare("INSERT INTO notes (user_id, title, content) VALUES (?, ?, ?)");
            $success = $stmt->execute([get_current_user_id(), 'Test Note', 'Test content']);
            $note_id = $db->lastInsertId();
            
            echo "<p>Test note created with ID: " . $note_id . "</p>";
            
            echo "<p>Attempting to save file...</p>";
            $saved_file = save_uploaded_file($file_data, $note_id);
            
            if ($saved_file) {
                echo "<p>✅ File saved successfully!</p>";
                echo "<pre>";
                print_r($saved_file);
                echo "</pre>";
                
                // Check if it's in the database
                $stmt = $db->prepare("SELECT * FROM attachments WHERE note_id = ?");
                $stmt->execute([$note_id]);
                $attachments = $stmt->fetchAll();
                echo "<p>Attachments in database:</p>";
                echo "<pre>";
                print_r($attachments);
                echo "</pre>";
                
            } else {
                echo "<p>❌ File save failed!</p>";
            }
        } else {
            echo "<p>❌ File validation failed: " . $validation['error'] . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Test</title>
</head>
<body>
    <h1>File Upload Test</h1>
    <form method="POST" enctype="multipart/form-data">
        <p>Select a file to test upload:</p>
        <input type="file" name="test_file" required>
        <button type="submit">Test Upload</button>
    </form>
    
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>