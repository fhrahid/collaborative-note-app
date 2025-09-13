<?php
require_once 'config/config_local.php';
require_once 'includes/helpers.php';

// Get share token from URL
$share_token = $_GET['token'] ?? '';

if (empty($share_token)) {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><title>Note Not Found</title><link rel='stylesheet' href='assets/css/style.css'></head><body><div class='container'><div class='empty-state'><h3>Note Not Found</h3><p>The shared note you're looking for doesn't exist or the link is invalid.</p></div></div></body></html>";
    exit;
}

// Get the shared note
$stmt = $db->prepare("
    SELECT n.*, u.username as author_username 
    FROM notes n 
    JOIN users u ON n.user_id = u.id 
    WHERE n.share_token = ? AND n.is_public = 1
");
$stmt->execute([$share_token]);
$note = $stmt->fetch();

if (!$note) {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><title>Note Not Found</title><link rel='stylesheet' href='assets/css/style.css'></head><body><div class='container'><div class='empty-state'><h3>Note Not Found</h3><p>The shared note you're looking for doesn't exist or is no longer publicly available.</p></div></div></body></html>";
    exit;
}

// Get attachments for this note
$stmt = $db->prepare("SELECT * FROM attachments WHERE note_id = ? ORDER BY uploaded_at");
$stmt->execute([$note['id']]);
$attachments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($note['title']); ?> - Shared Note</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .shared-note-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .shared-note-content {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            line-height: 1.6;
        }
        .note-meta {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            border-left: 4px solid #3498db;
        }
        .note-content {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .app-link {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="shared-note-header">
            <h1><?php echo htmlspecialchars($note['title']); ?></h1>
            <p>📝 Shared by <strong><?php echo htmlspecialchars($note['author_username']); ?></strong></p>
        </div>

        <div class="note-meta">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <strong>📅 Created:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($note['created_at'])); ?>
                </div>
                <div>
                    <strong>🔄 Last Updated:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($note['updated_at'])); ?>
                </div>
            </div>
        </div>

        <div class="shared-note-content">
            <div class="note-content"><?php echo nl2br(htmlspecialchars($note['content'])); ?></div>
            
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
                                <a href="download_shared_local.php?id=<?php echo $attachment['id']; ?>&token=<?php echo $share_token; ?>" 
                                   class="btn btn-small btn-primary">Download</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="app-link">
            <p>💡 <strong>Like what you see?</strong></p>
            <p>Create your own notes and share them with others!</p>
            <a href="register_local.php" class="btn btn-primary">Sign Up for Free</a>
            <a href="login_local.php" class="btn btn-secondary">Login</a>
        </div>
    </div>
</body>
</html>