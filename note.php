<?php
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
                $message = 'Note updated successfully!';
            } else {
                // Create new note
                $stmt = $db->prepare("INSERT INTO notes (user_id, title, content) VALUES (?, ?, ?)");
                $success = $stmt->execute([$user_id, $title, $content]);
                $message = 'Note created successfully!';
            }

            if ($success) {
                flash_message($message, 'success');
                redirect('dashboard.php');
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><?php echo $is_edit ? 'Edit Note' : 'New Note'; ?></h1>
            <nav class="nav">
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
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
                <form method="POST" action="">
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

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $is_edit ? 'Update Note' : 'Create Note'; ?>
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>