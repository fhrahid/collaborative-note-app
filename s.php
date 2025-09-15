<?php
require_once 'config/config_local.php';

// Get share token/custom slug from URL
$slug = $_GET['s'] ?? $_SERVER['REQUEST_URI'];
$slug = trim($slug, '/');

// If slug is empty, redirect to main page
if (empty($slug)) {
    redirect('index.php');
}

// Look up note by share_token (which now can be custom)
$stmt = $db->prepare("SELECT * FROM notes WHERE share_token = ? AND is_public = 1");
$stmt->execute([$slug]);
$note = $stmt->fetch();

if (!$note) {
    // Try to find by note ID if it's numeric
    if (is_numeric($slug)) {
        $stmt = $db->prepare("SELECT * FROM notes WHERE id = ? AND is_public = 1");
        $stmt->execute([$slug]);
        $note = $stmt->fetch();
    }
}

if (!$note) {
    flash_message('Note not found or not publicly accessible.', 'error');
    redirect('index.php');
}

// Get note owner info
$stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$note['user_id']]);
$owner = $stmt->fetch();

// Load attachments for this note
$stmt = $db->prepare("SELECT * FROM attachments WHERE note_id = ? ORDER BY uploaded_at");
$stmt->execute([$note['id']]);
$attachments = $stmt->fetchAll();

$page_title = $note['title'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Shared Note</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .share-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .share-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .custom-url {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-weight: bold;
            color: #1976d2;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="share-header">
            <h1>📄 Shared Note</h1>
            <p>Public link: <span class="custom-url"><?php echo $_SERVER['HTTP_HOST'] ?? 'localhost'; ?>/<?php echo htmlspecialchars($slug); ?></span></p>
        </div>
        
        <div class="share-info">
            <strong>📝 Note:</strong> <?php echo htmlspecialchars($note['title']); ?><br>
            <strong>👤 Shared by:</strong> <?php echo htmlspecialchars($owner['username'] ?? 'Unknown'); ?><br>
            <strong>📅 Created:</strong> <?php echo date('M j, Y g:i A', strtotime($note['created_at'])); ?>
        </div>
        
        <div class="note-content">
            <h2><?php echo htmlspecialchars($note['title']); ?></h2>
            <div class="note-text">
                <?php echo nl2br(htmlspecialchars($note['content'])); ?>
            </div>
            
            <?php if (!empty($attachments)): ?>
                <div class="attachments-section">
                    <h3>📎 Attachments</h3>
                    <div class="attachments-grid">
                        <?php foreach ($attachments as $attachment): ?>
                            <div class="attachment-item">
                                <div class="attachment-icon">
                                    📄
                                </div>
                                <div class="attachment-info">
                                    <div class="attachment-filename"><?php echo htmlspecialchars($attachment['original_filename']); ?></div>
                                    <div class="attachment-meta"><?php echo format_file_size($attachment['file_size']); ?></div>
                                    <a href="download_local.php?id=<?php echo $attachment['id']; ?>&public=1" 
                                       class="btn btn-small btn-primary">Download</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">
            <p><a href="index.php" style="color: #667eea;">🏠 Create your own notes</a></p>
            <p style="font-size: 0.9em; color: #666;">Powered by Collaborative Note App</p>
        </div>
    </div>
</body>
</html>