<?php
require_once 'config/config_local.php';

// Require login
require_login();

$user_id = get_current_user_id();

// Get note ID from URL
if (!isset($_GET['id'])) {
    flash_message('Note ID is required.', 'error');
    redirect('shared_with_me_local.php');
}

$note_id = (int)$_GET['id'];

// Check if user has access to this note
$permission = can_user_access_note($note_id, $user_id, $db);

if (!$permission) {
    flash_message('You do not have permission to view this note.', 'error');
    redirect('shared_with_me_local.php');
}

// Get the note details
if ($permission === 'owner') {
    // User owns the note
    $stmt = $db->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
    $stmt->execute([$note_id, $user_id]);
    $note = $stmt->fetch();
    $shared_by = null;
} else {
    // Note is shared with user
    $stmt = $db->prepare("
        SELECT n.*, u.username as shared_by_username, sn.permission 
        FROM notes n 
        JOIN shared_notes sn ON n.id = sn.note_id 
        JOIN users u ON sn.shared_by_user_id = u.id 
        WHERE n.id = ? AND sn.shared_with_user_id = ?
    ");
    $stmt->execute([$note_id, $user_id]);
    $note = $stmt->fetch();
    $shared_by = $note['shared_by_username'] ?? null;
}

if (!$note) {
    flash_message('Note not found.', 'error');
    redirect('shared_with_me_local.php');
}

// Load attachments for this note
$stmt = $db->prepare("SELECT * FROM attachments WHERE note_id = ? ORDER BY uploaded_at");
$stmt->execute([$note_id]);
$attachments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($note['title']); ?> - Note App (Local)</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .note-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .note-content-display {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            line-height: 1.6;
        }
        .note-meta-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            border-left: 4px solid #3498db;
        }
        .shared-indicator {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: inline-block;
        }
        .permission-badge {
            background: #d4edda;
            color: #155724;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }
        .permission-read {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><?php echo htmlspecialchars($note['title']); ?></h1>
            <nav class="nav">
                <?php if ($permission === 'owner'): ?>
                    <a href="note_local.php?id=<?php echo $note['id']; ?>" class="btn btn-primary">Edit</a>
                    <a href="share_local.php?id=<?php echo $note['id']; ?>" class="btn btn-secondary">Share</a>
                    <a href="dashboard_local.php" class="btn btn-secondary">My Notes</a>
                <?php elseif ($permission === 'edit'): ?>
                    <a href="edit_shared_note_local.php?id=<?php echo $note['id']; ?>" class="btn btn-primary">Edit</a>
                    <a href="shared_with_me_local.php" class="btn btn-secondary">Shared With Me</a>
                <?php else: ?>
                    <a href="shared_with_me_local.php" class="btn btn-secondary">Shared With Me</a>
                <?php endif; ?>
                <a href="logout_local.php" class="btn btn-secondary">Logout</a>
            </nav>
        </header>

        <main class="main">
            <?php if ($shared_by): ?>
                <div class="shared-indicator">
                    📤 Shared by <strong><?php echo htmlspecialchars($shared_by); ?></strong>
                    <span class="permission-badge permission-<?php echo $note['permission']; ?>">
                        <?php echo ucfirst($note['permission']); ?> Access
                    </span>
                </div>
            <?php endif; ?>

            <div class="note-meta-info">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <strong>📅 Created:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($note['created_at'])); ?>
                    </div>
                    <div>
                        <strong>🔄 Last Updated:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($note['updated_at'])); ?>
                    </div>
                </div>
            </div>

            <div class="note-content-display">
                <div style="white-space: pre-wrap; word-wrap: break-word;">
                    <?php echo nl2br(htmlspecialchars($note['content'])); ?>
                </div>
                
                <?php if (!empty($attachments)): ?>
                    <div class="note-attachments">
                        <h4>📎 Attachments</h4>
                        <div class="attachment-grid">
                            <?php foreach ($attachments as $attachment): ?>
                                <div class="attachment-card">
                                    <div class="attachment-icon">
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
                                    </div>
                                    <div class="attachment-filename"><?php echo htmlspecialchars($attachment['original_filename']); ?></div>
                                    <div class="attachment-meta"><?php echo format_file_size($attachment['file_size']); ?></div>
                                    <a href="download_local.php?id=<?php echo $attachment['id']; ?>" 
                                       class="btn btn-small btn-primary">Download</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>