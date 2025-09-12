<?php
require_once 'config/config_local.php';

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
        redirect('dashboard_local.php');
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
                    flash_message($message, 'success');
                    redirect('dashboard_local.php');
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
    <title><?php echo $is_edit ? 'Edit Note' : 'New Note'; ?> - Note App (Local)</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><?php echo $is_edit ? 'Edit Note' : 'New Note'; ?> <span style="font-size: 0.6em; color: #666;">(Local Dev)</span></h1>
            <nav class="nav">
                <a href="dashboard_local.php" class="btn btn-secondary">Back to Dashboard</a>
                <a href="logout_local.php" class="btn btn-secondary">Logout</a>
            </nav>
        </header>

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
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" 
                               value="<?php echo htmlspecialchars($note['title'] ?? $_POST['title'] ?? ''); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" rows="15" required><?php echo htmlspecialchars($note['content'] ?? $_POST['content'] ?? ''); ?></textarea>
                    </div>

                    <?php if ($is_edit && !empty($attachments)): ?>
                    <div class="form-group">
                        <label>Current Attachments</label>
                        <div class="attachments-list">
                            <?php foreach ($attachments as $attachment): ?>
                                <div class="attachment-item">
                                    <span class="attachment-name"><?php echo htmlspecialchars($attachment['original_filename']); ?></span>
                                    <span class="attachment-size">(<?php echo format_file_size($attachment['file_size']); ?>)</span>
                                    <a href="download_local.php?id=<?php echo $attachment['id']; ?>" class="btn btn-small">Download</a>
                                    <a href="delete_attachment_local.php?id=<?php echo $attachment['id']; ?>&note_id=<?php echo $note['id']; ?>" 
                                       class="btn btn-small btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this attachment?')">Delete</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="attachments">Add Attachments</label>
                        <input type="file" id="attachments" name="attachments[]" multiple 
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                        <small class="form-help">
                            Allowed file types: Images (JPG, PNG, GIF), Documents (PDF, DOC, DOCX), Spreadsheets (XLS, XLSX), Text files. 
                            Maximum file size: <?php echo ini_get('upload_max_filesize'); ?>
                        </small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $is_edit ? 'Update Note' : 'Create Note'; ?>
                        </button>
                        <a href="dashboard_local.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>