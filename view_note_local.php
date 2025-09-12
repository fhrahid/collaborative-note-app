<?php
require_once 'config/config_local.php';

// Require login
require_login();

$user_id = get_current_user_id();

// Get note ID from URL
if (!isset($_GET['id'])) {
    flash_message('Note ID is required.', 'error');
    redirect('dashboard_local.php');
}

$note_id = (int)$_GET['id'];

// Get the note and verify ownership
$stmt = $db->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
$stmt->execute([$note_id, $user_id]);
$note = $stmt->fetch();

if (!$note) {
    flash_message('Note not found.', 'error');
    redirect('dashboard_local.php');
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
        .note-view-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .note-title-display {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 0.5rem;
        }
        .note-content-display {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #333;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin-bottom: 2rem;
        }
        .note-meta-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            border-left: 4px solid #3498db;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><?php echo htmlspecialchars($note['title']); ?> <span style="font-size: 0.6em; color: #666;">(Local Dev)</span></h1>
            <nav class="nav">
                <a href="dashboard_local.php" class="btn btn-secondary">Back to My Notes</a>
                <a href="logout_local.php" class="btn btn-secondary">Logout</a>
            </nav>
        </header>

        <main class="main">
            <div class="action-buttons">
                <a href="note_local.php?id=<?php echo $note['id']; ?>" class="btn btn-primary">Edit Note</a>
                <a href="share_local.php?id=<?php echo $note['id']; ?>" class="btn btn-secondary">Share Note</a>
            </div>

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

            <div class="note-view-container">
                <div class="note-content-display">
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