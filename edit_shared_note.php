<?php
require_once 'config/config.php';

// Require login
require_login();

$user_id = get_current_user_id();
$errors = [];

// Get note ID from URL
if (!isset($_GET['id'])) {
    flash_message('Note ID is required.', 'error');
    redirect('shared_with_me.php');
}

$note_id = (int)$_GET['id'];

// Check if user has edit permission for this note
$permission = can_user_access_note($note_id, $user_id, $db);

if ($permission !== 'write' && $permission !== 'owner') {
    flash_message('You do not have permission to edit this note.', 'error');
    redirect('shared_with_me.php');
}

// Get the note details
$stmt = $db->prepare("SELECT * FROM notes WHERE id = ?");
$stmt->execute([$note_id]);
$note = $stmt->fetch();

if (!$note) {
    flash_message('Note not found.', 'error');
    redirect('shared_with_me.php');
}

// Load existing attachments
$stmt = $db->prepare("SELECT * FROM attachments WHERE note_id = ? ORDER BY uploaded_at");
$stmt->execute([$note_id]);
$attachments = $stmt->fetchAll();

// Get who shared this note (if it's shared)
$shared_by = null;
if ($permission === 'write') {
    $stmt = $db->prepare("
        SELECT u.username 
        FROM shared_notes sn 
        JOIN users u ON sn.shared_by_user_id = u.id 
        WHERE sn.note_id = ? AND sn.shared_with_user_id = ?
    ");
    $stmt->execute([$note_id, $user_id]);
    $result = $stmt->fetch();
    $shared_by = $result['username'] ?? null;
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

        // Update note if no errors
        if (empty($errors)) {
            $stmt = $db->prepare("UPDATE notes SET title = ?, content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $success = $stmt->execute([$title, $content, $note_id]);

            if ($success) {
                // Handle file uploads
                if (!empty($_FILES['attachments']['name'][0])) {
                    foreach ($_FILES['attachments']['name'] as $key => $filename) {
                        if (!empty($filename)) {
                            $file_data = [
                                'name' => $_FILES['attachments']['name'][$key],
                                'type' => $_FILES['attachments']['type'][$key],
                                'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                                'error' => $_FILES['attachments']['error'][$key],
                                'size' => $_FILES['attachments']['size'][$key]
                            ];
                            
                            $validation = validate_file_upload($file_data);
                            if ($validation['valid']) {
                                $saved_file = save_uploaded_file($file_data, $note_id);
                                if (!$saved_file) {
                                    $errors[] = "Failed to save file: {$filename}";
                                }
                            } else {
                                $errors[] = "File upload error for {$filename}: " . $validation['error'];
                            }
                        }
                    }
                }
                
                if (empty($errors)) {
                    flash_message('Note updated successfully!', 'success');
                    if ($permission === 'owner') {
                        redirect('dashboard.php');
                    } else {
                        redirect('view_shared_note.php?id=' . $note_id);
                    }
                }
            } else {
                $errors[] = 'Failed to update note';
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
    <title>Edit Note - <?php echo htmlspecialchars($note['title']); ?></title>
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
            padding: 1.5rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
        }
        
        .header h1 {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 1.8rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: var(--theme-transition);
            font-size: 0.9rem;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: var(--gradient-blue);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }
        
        .btn-secondary {
            background: var(--gradient-gray);
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }
        
        .btn-danger {
            background: var(--gradient-red);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
        
        .shared-indicator {
            background: var(--card-bg);
            color: var(--text-primary);
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow-light);
            border: 2px solid var(--border-focus);
            backdrop-filter: var(--blur-backdrop);
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            font-weight: 500;
        }
        
        .alert-error {
            background: var(--gradient-red);
            color: white;
            border: 2px solid rgba(231, 76, 60, 0.3);
        }
        
        .note-form {
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
        }
        
        .form-group {
            margin-bottom: 2rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1.1rem;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--input-border);
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--theme-transition);
            background: var(--input-bg);
            color: var(--text-primary);
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 400px;
            line-height: 1.6;
        }
        
        .form-help {
            display: block;
            margin-top: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-style: italic;
        }
        
        .attachments-list {
            background: var(--attachment-bg);
            padding: 1.5rem;
            border-radius: 10px;
            border: 2px solid var(--attachment-border);
        }
        
        .attachment-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--card-bg);
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid var(--card-border);
            transition: var(--theme-transition);
        }
        
        .attachment-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
        }
        
        .attachment-item:last-child {
            margin-bottom: 0;
        }
        
        .attachment-name {
            flex: 1;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .attachment-size {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid var(--card-border);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .nav {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .attachment-item {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>✏️ Edit Note</h1>
            <nav class="nav">
                <?php if ($permission === 'owner'): ?>
                    <a href="dashboard.php" class="btn btn-secondary">📚 Back to My Notes</a>
                <?php else: ?>
                    <a href="view_shared_note.php?id=<?php echo $note_id; ?>" class="btn btn-secondary">👀 View Note</a>
                    <a href="shared_with_me.php" class="btn btn-secondary">🤝 Shared With Me</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-secondary">👋 Logout</a>
            </nav>
        </header>

        <?php if ($shared_by): ?>
            <div class="shared-indicator">
                <span>📤 Editing note shared by <strong><?php echo htmlspecialchars($shared_by); ?></strong></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <main class="main">
            <div class="note-form">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="form-group">
                        <label for="title">📝 Title</label>
                        <input type="text" id="title" name="title" 
                               value="<?php echo htmlspecialchars($note['title'] ?? $_POST['title'] ?? ''); ?>" 
                               placeholder="Enter your note title..."
                               required>
                    </div>

                    <div class="form-group">
                        <label for="content">📄 Content</label>
                        <textarea id="content" name="content" rows="15" 
                                  placeholder="Start writing your note..."
                                  required><?php echo htmlspecialchars($note['content'] ?? $_POST['content'] ?? ''); ?></textarea>
                    </div>

                    <?php if (!empty($attachments)): ?>
                    <div class="form-group">
                        <label>📎 Current Attachments</label>
                        <div class="attachments-list">
                            <?php foreach ($attachments as $attachment): ?>
                                <div class="attachment-item">
                                    <span class="attachment-name">
                                        <?php
                                        $ext = strtolower(pathinfo($attachment['original_filename'], PATHINFO_EXTENSION));
                                        switch ($ext) {
                                            case 'pdf': echo '📄'; break;
                                            case 'doc': case 'docx': echo '📝'; break;
                                            case 'xls': case 'xlsx': echo '📊'; break;
                                            case 'jpg': case 'jpeg': case 'png': case 'gif': echo '🖼️'; break;
                                            default: echo '📎'; break;
                                        }
                                        ?>
                                        <?php echo htmlspecialchars($attachment['original_filename']); ?>
                                    </span>
                                    <span class="attachment-size">(<?php echo format_file_size($attachment['file_size']); ?>)</span>
                                    <a href="download.php?id=<?php echo $attachment['id']; ?>" class="btn btn-small btn-secondary">💾 Download</a>
                                    <a href="delete_attachment.php?id=<?php echo $attachment['id']; ?>&note_id=<?php echo $note_id; ?>" 
                                       class="btn btn-small btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this attachment?')">🗑️ Delete</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="attachments">📎 Add New Attachments</label>
                        <input type="file" id="attachments" name="attachments[]" multiple 
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                        <small class="form-help">
                            📋 Allowed file types: Images (JPG, PNG, GIF), Documents (PDF, DOC, DOCX), Spreadsheets (XLS, XLSX), Text files. 
                            Maximum file size: <?php echo ini_get('upload_max_filesize'); ?>
                        </small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Update Note</button>
                        <?php if ($permission === 'owner'): ?>
                            <a href="dashboard.php" class="btn btn-secondary">❌ Cancel</a>
                        <?php else: ?>
                            <a href="view_shared_note.php?id=<?php echo $note_id; ?>" class="btn btn-secondary">❌ Cancel</a>
                        <?php endif; ?>
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
    </script>
</body>
</html>