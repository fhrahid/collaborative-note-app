<?php
require_once 'config/config_local.php';
require_once 'includes/helpers.php';

// Require login
require_login();

$user_id = get_current_user_id();
$errors = [];

// Get note ID from URL
if (!isset($_GET['id'])) {
    flash_message('Note ID is required.', 'error');
    redirect('shared_with_me_local.php');
}

$note_id = (int)$_GET['id'];

// Check if user has edit permission for this note
$permission = can_user_access_note($note_id, $user_id, $db);

if ($permission !== 'write' && $permission !== 'owner') {
    flash_message('You do not have permission to edit this note.', 'error');
    redirect('shared_with_me_local.php');
}

// Get the note details
$stmt = $db->prepare("SELECT * FROM notes WHERE id = ?");
$stmt->execute([$note_id]);
$note = $stmt->fetch();

if (!$note) {
    flash_message('Note not found.', 'error');
    redirect('shared_with_me_local.php');
}

// Load existing attachments
$stmt = $db->prepare("SELECT * FROM attachments WHERE note_id = ? ORDER BY uploaded_at");
$stmt->execute([$note_id]);
$attachments = $stmt->fetchAll();

// Get who shared this note (if it's shared)
$shared_by = null;
if ($permission === 'edit') {
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
                        redirect('dashboard_local.php');
                    } else {
                        redirect('view_shared_note_local.php?id=' . $note_id);
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
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .shared-indicator {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Edit Note <span style="font-size: 0.6em; color: #666;">(Local Dev)</span></h1>
            <nav class="nav">
                <?php if ($permission === 'owner'): ?>
                    <a href="dashboard_local.php" class="btn btn-secondary">Back to My Notes</a>
                <?php else: ?>
                    <a href="view_shared_note_local.php?id=<?php echo $note_id; ?>" class="btn btn-secondary">View Note</a>
                    <a href="shared_with_me_local.php" class="btn btn-secondary">Shared With Me</a>
                <?php endif; ?>
                <a href="logout_local.php" class="btn btn-secondary">Logout</a>
            </nav>
        </header>

        <?php if ($shared_by): ?>
            <div class="shared-indicator">
                📤 Editing note shared by <strong><?php echo htmlspecialchars($shared_by); ?></strong>
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
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" 
                               value="<?php echo htmlspecialchars($note['title'] ?? $_POST['title'] ?? ''); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" rows="15" required><?php echo htmlspecialchars($note['content'] ?? $_POST['content'] ?? ''); ?></textarea>
                    </div>

                    <?php if (!empty($attachments)): ?>
                    <div class="form-group">
                        <label>Current Attachments</label>
                        <div class="attachments-list">
                            <?php foreach ($attachments as $attachment): ?>
                                <div class="attachment-item">
                                    <span class="attachment-name"><?php echo htmlspecialchars($attachment['original_filename']); ?></span>
                                    <span class="attachment-size">(<?php echo format_file_size($attachment['file_size']); ?>)</span>
                                    <a href="download_local.php?id=<?php echo $attachment['id']; ?>" class="btn btn-small">Download</a>
                                    <a href="delete_attachment_local.php?id=<?php echo $attachment['id']; ?>&note_id=<?php echo $note_id; ?>" 
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
                        <button type="submit" class="btn btn-primary">Update Note</button>
                        <?php if ($permission === 'owner'): ?>
                            <a href="dashboard_local.php" class="btn btn-secondary">Cancel</a>
                        <?php else: ?>
                            <a href="view_shared_note_local.php?id=<?php echo $note_id; ?>" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>