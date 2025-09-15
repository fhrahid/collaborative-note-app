<?php
require_once 'config/config.php';

// Get share token from URL
$share_token = $_GET['token'] ?? '';

if (empty($share_token)) {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><title>Note Not Found</title><link rel='stylesheet' href='assets/css/themes.css'></head><body><div class='container'><div class='empty-state'><h3>Note Not Found</h3><p>The shared note you're looking for doesn't exist or the link is invalid.</p></div></div></body></html>";
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
    echo "<!DOCTYPE html><html><head><title>Note Not Found</title><link rel='stylesheet' href='assets/css/themes.css'></head><body><div class='container'><div class='empty-state'><h3>Note Not Found</h3><p>The shared note you're looking for doesn't exist or is no longer publicly available.</p></div></div></body></html>";
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
            color: var(--text-primary);
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .shared-note-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: var(--shadow-medium);
        }
        
        .shared-note-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .shared-note-content {
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
            line-height: 1.6;
        }
        
        .note-meta {
            background: var(--attachment-bg);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            border-left: 4px solid var(--border-focus);
            border: 1px solid var(--attachment-border);
        }
        
        .note-content {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-primary);
        }
        
        .note-attachments {
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 2px solid var(--card-border);
        }
        
        .note-attachments h4 {
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: 700;
        }
        
        .attachment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .attachment-card {
            background: var(--attachment-bg);
            padding: 1.5rem;
            border-radius: 12px;
            border: 2px solid var(--attachment-border);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .attachment-card:hover {
            transform: translateY(-2px);
            border-color: var(--border-focus);
            box-shadow: var(--shadow-medium);
        }
        
        .attachment-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .attachment-filename {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            word-break: break-word;
        }
        
        .attachment-meta {
            color: var(--text-secondary);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
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
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
        
        .app-link {
            text-align: center;
            margin-top: 2rem;
            padding: 2rem;
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            border-radius: 20px;
            border: 1px solid var(--card-border);
            box-shadow: var(--shadow-light);
        }
        
        .app-link h3 {
            color: var(--text-primary);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .app-link p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }
        
        .app-link .btn {
            margin: 0 0.5rem;
        }
        
        .meta-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .meta-item {
            color: var(--text-secondary);
        }
        
        .meta-item strong {
            color: var(--text-primary);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .shared-note-header {
                padding: 2rem;
            }
            
            .shared-note-header h1 {
                font-size: 2rem;
            }
            
            .shared-note-content {
                padding: 1.5rem;
            }
            
            .meta-flex {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .attachment-grid {
                grid-template-columns: 1fr;
            }
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
            <div class="meta-flex">
                <div class="meta-item">
                    <strong>📅 Created:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($note['created_at'])); ?>
                </div>
                <div class="meta-item">
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
                                <a href="download.php?id=<?php echo $attachment['id']; ?>&token=<?php echo $share_token; ?>" 
                                   class="btn btn-small btn-primary">⬇️ Download</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="app-link">
            <h3>✨ Like what you see?</h3>
            <p>Create your own notes and share them with others!</p>
            <a href="register.php" class="btn btn-primary">🚀 Sign Up for Free</a>
            <a href="login.php" class="btn btn-secondary">🔑 Login</a>
        </div>
    </div>

    <!-- Theme System -->
    <script src="assets/js/theme-manager.js"></script>
    <script>
        // Initialize theme manager
        const themeManager = new ThemeManager();
    </script>
</body>
</html>