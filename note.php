<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';

// Require login
require_login();

$user_id = get_current_user_id();
$note = null;
$errors = [];
$is_edit = false;

// Check if editing existing note
if (isset($_GET['id'])) {
    $note_id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
    $stmt->execute([$note_id, $user_id]);
    $note = $stmt->fetch();
    
    if (!$note) {
        flash_message('Note not found.', 'error');
        redirect('dashboard.php');
    }
    $is_edit = true;
    
    // Load existing attachments
    $stmt = $db->prepare("SELECT * FROM attachments WHERE note_id = ? ORDER BY uploaded_at");
    $stmt->execute([$note_id]);
    $attachments = $stmt->fetchAll();
} else {
    $attachments = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $title = sanitize_input($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';

        // Validation
        if (empty($title)) {
            $errors[] = 'Title is required';
        }

        if (empty($content)) {
            $errors[] = 'Content is required';
        }

        // Save note if no errors
        if (empty($errors)) {
            if ($is_edit) {
                // Update existing note
                $stmt = $db->prepare("UPDATE notes SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
                $success = $stmt->execute([$title, $content, $note['id'], $user_id]);
                $note_id = $note['id'];
                $message = 'Note updated successfully!';
            } else {
                // Create new note
                $stmt = $db->prepare("INSERT INTO notes (user_id, title, content) VALUES (?, ?, ?)");
                $success = $stmt->execute([$user_id, $title, $content]);
                $note_id = $db->lastInsertId();
                $message = 'Note created successfully!';
            }

            if ($success) {
                // Debug: Log file upload attempt
                error_log("Note saved successfully. Checking for file uploads...");
                error_log("FILES data: " . print_r($_FILES, true));
                
                // Handle file uploads
                if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                    error_log("File upload detected. Processing files...");
                    $upload_success_count = 0;
                    $total_files = 0;
                    
                    foreach ($_FILES['attachments']['name'] as $key => $filename) {
                        if (!empty($filename)) {
                            $total_files++;
                            error_log("Processing file {$key}: {$filename}");
                            
                            $file_data = [
                                'name' => $_FILES['attachments']['name'][$key],
                                'type' => $_FILES['attachments']['type'][$key],
                                'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                                'error' => $_FILES['attachments']['error'][$key],
                                'size' => $_FILES['attachments']['size'][$key]
                            ];
                            
                            error_log("File data: " . print_r($file_data, true));
                            
                            $validation = validate_file_upload($file_data);
                            error_log("Validation result: " . print_r($validation, true));
                            
                            if ($validation['valid']) {
                                $saved_file = save_uploaded_file($file_data, $note_id);
                                error_log("Save result: " . print_r($saved_file, true));
                                
                                if ($saved_file) {
                                    $upload_success_count++;
                                    error_log("File {$filename} uploaded successfully");
                                } else {
                                    $errors[] = "Failed to save file: {$filename}";
                                    error_log("Failed to save file: {$filename}");
                                }
                            } else {
                                $errors[] = "File upload error for {$filename}: " . $validation['error'];
                                error_log("File validation failed for {$filename}: " . $validation['error']);
                            }
                        }
                    }
                    
                    // Add success message for uploads
                    if ($upload_success_count > 0) {
                        $message .= " {$upload_success_count} file(s) uploaded successfully.";
                    }
                }
                
                if (empty($errors)) {
                    flash_message($message, 'success');
                    if ($is_edit) {
                        // For edits, redirect to view the note so user can see attachments
                        redirect('view_note.php?id=' . $note_id);
                    } else {
                        // For new notes, go back to dashboard
                        redirect('dashboard.php');
                    }
                }
            } else {
                $errors[] = 'Failed to save note';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Note' : 'New Note'; ?> - Note App</title>
    <link rel="stylesheet" href="assets/css/themes.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
        }
        
        .header h1 {
            color: var(--text-primary);
            font-weight: 800;
            font-size: 2rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            font-family: inherit;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: var(--gradient-blue);
            color: white;
            box-shadow: var(--shadow-medium);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-large);
        }
        
        .btn-secondary {
            background: var(--gradient-gray);
            color: white;
            box-shadow: var(--shadow-medium);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-large);
        }
        
        .btn-danger {
            background: var(--gradient-red);
            color: white;
            box-shadow: var(--shadow-medium);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-large);
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            border-radius: 8px;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-weight: 500;
            background: var(--gradient-red);
            color: white;
            border: 2px solid rgba(231, 76, 60, 0.3);
        }
        
        .alert p {
            margin: 0.2rem 0;
        }
        
        .note-form {
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
        }
        
        .form-group {
            margin-bottom: 2rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-group input[type="text"],
        .form-group textarea,
        .form-group input[type="file"] {
            width: 100%;
            padding: 1rem 1.2rem;
            border: 2px solid var(--input-border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--input-bg);
            color: var(--text-primary);
            font-family: inherit;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            transform: translateY(-2px);
        }
        
        .form-group textarea {
            min-height: 300px;
            line-height: 1.6;
        }
        
        .form-help {
            display: block;
            margin-top: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.85rem;
            line-height: 1.4;
        }
        
        .attachments-list {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .attachment-item {
            background: var(--attachment-bg);
            padding: 1rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 2px solid var(--attachment-border);
            transition: all 0.3s ease;
        }
        
        .attachment-item:hover {
            border-color: var(--border-focus);
            transform: translateY(-2px);
        }
        
        .attachment-name {
            font-weight: 600;
            color: var(--text-primary);
            flex: 1;
        }
        
        .attachment-size {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 2px solid var(--card-border);
        }
        
        .file-upload-area {
            border: 2px dashed var(--input-border);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            background: var(--attachment-bg);
            position: relative;
            overflow: hidden;
        }
        
        .file-upload-area:hover {
            border-color: var(--border-focus);
            background: rgba(102, 126, 234, 0.05);
        }
        
        .file-upload-area.dragover {
            border-color: var(--border-focus);
            background: rgba(102, 126, 234, 0.1);
        }
        
        .file-upload-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }
        
        .file-upload-text {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        
        .file-upload-input {
            opacity: 0;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
                padding: 1.5rem;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .nav {
                justify-content: center;
            }
            
            .note-form {
                padding: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .attachment-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>
                <?php if ($is_edit): ?>
                    ✏️ Edit Note
                <?php else: ?>
                    ✍️ New Note
                <?php endif; ?>
            </h1>
            <nav class="nav">
                <a href="dashboard.php" class="btn btn-secondary">🏠 Dashboard</a>
                <a href="logout.php" class="btn btn-secondary">👋 Logout</a>
            </nav>
        </header>

        <?php if (!empty($errors)): ?>
            <div class="alert">
                <?php foreach ($errors as $error): ?>
                    <p>❌ <?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <main class="main">
            <div class="note-form">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="form-group">
                        <label for="title">📝 Note Title</label>
                        <input type="text" id="title" name="title" 
                               value="<?php echo htmlspecialchars($note['title'] ?? $_POST['title'] ?? ''); ?>" 
                               placeholder="Enter a descriptive title for your note..."
                               required>
                    </div>

                    <div class="form-group">
                        <label for="content">📄 Note Content</label>
                        <textarea id="content" name="content" rows="15" 
                                  placeholder="Start writing your note content here..."
                                  required><?php echo htmlspecialchars($note['content'] ?? $_POST['content'] ?? ''); ?></textarea>
                    </div>

                    <?php if ($is_edit && !empty($attachments)): ?>
                    <div class="form-group">
                        <label>📎 Current Attachments</label>
                        <div class="attachments-list">
                            <?php foreach ($attachments as $attachment): ?>
                                <div class="attachment-item">
                                    <span class="attachment-name">📄 <?php echo htmlspecialchars($attachment['original_filename']); ?></span>
                                    <span class="attachment-size">(<?php echo format_file_size($attachment['file_size']); ?>)</span>
                                    <div>
                                        <a href="download.php?id=<?php echo $attachment['id']; ?>" class="btn btn-small btn-primary">⬇️ Download</a>
                                        <a href="delete_attachment.php?id=<?php echo $attachment['id']; ?>&note_id=<?php echo $note['id']; ?>" 
                                           class="btn btn-small btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this attachment?')">🗑️ Delete</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="attachments">📎 Add Attachments</label>
                        <div class="file-upload-area" id="fileUploadArea">
                            <div class="file-upload-icon">📁</div>
                            <div class="file-upload-text">
                                <strong>Drag and drop files here</strong> or click to browse
                            </div>
                            <input type="file" id="attachments" name="attachments[]" multiple 
                                   accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                                   class="file-upload-input">
                        </div>
                        <small class="form-help">
                            📋 Allowed file types: Images (JPG, PNG, GIF), Documents (PDF, DOC, DOCX), Spreadsheets (XLS, XLSX), Text files<br>
                            💾 Maximum file size: <?php echo ini_get('upload_max_filesize'); ?>
                        </small>
                    </div>

                    <div class="form-actions">
                        <a href="dashboard.php" class="btn btn-secondary">❌ Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <?php if ($is_edit): ?>
                                💾 Update Note
                            <?php else: ?>
                                ✨ Create Note
                            <?php endif; ?>
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Theme System -->
    <script src="assets/js/theme-manager.js"></script>
    <script>
        // Initialize theme manager
        const themeManager = new ThemeManager();
        
        // File upload drag and drop functionality
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('attachments');

        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            // Set the files to the actual input element
            const dt = new DataTransfer();
            for (let i = 0; i < files.length; i++) {
                dt.items.add(files[i]);
            }
            fileInput.files = dt.files;
            
            updateFileDisplay();
        });

        fileInput.addEventListener('change', updateFileDisplay);

        function updateFileDisplay() {
            const files = fileInput.files;
            if (files.length > 0) {
                const fileList = Array.from(files).map(file => file.name).join(', ');
                // Update only the visual elements, keep the input
                const icon = fileUploadArea.querySelector('.file-upload-icon');
                const text = fileUploadArea.querySelector('.file-upload-text');
                
                if (icon) icon.textContent = '✅';
                if (text) {
                    text.innerHTML = `<strong>${files.length} file${files.length > 1 ? 's' : ''} selected</strong><br><small>${fileList}</small>`;
                }
            }
        }

        // Auto-save functionality (save to localStorage)
        const titleInput = document.getElementById('title');
        const contentTextarea = document.getElementById('content');
        
        function saveToLocalStorage() {
            localStorage.setItem('note_draft_title', titleInput.value);
            localStorage.setItem('note_draft_content', contentTextarea.value);
        }

        function loadFromLocalStorage() {
            const savedTitle = localStorage.getItem('note_draft_title');
            const savedContent = localStorage.getItem('note_draft_content');
            
            if (savedTitle && !titleInput.value) {
                titleInput.value = savedTitle;
            }
            
            if (savedContent && !contentTextarea.value) {
                contentTextarea.value = savedContent;
            }
        }

        function clearLocalStorage() {
            localStorage.removeItem('note_draft_title');
            localStorage.removeItem('note_draft_content');
        }

        // Load draft on page load (only for new notes)
        <?php if (!$is_edit): ?>
        loadFromLocalStorage();
        <?php endif; ?>

        // Auto-save every 30 seconds
        titleInput.addEventListener('input', saveToLocalStorage);
        contentTextarea.addEventListener('input', saveToLocalStorage);

        // Clear draft when form is submitted
        document.querySelector('form').addEventListener('submit', clearLocalStorage);
    </script>
</body>
</html>