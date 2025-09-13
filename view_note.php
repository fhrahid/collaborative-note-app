<?php
require_once 'config/config.php';

// Require login
require_login();

$user_id = get_current_user_id();

// Get note ID from URL
if (!isset($_GET['id'])) {
    flash_message('Note ID is required.', 'error');
    redirect('dashboard.php');
}

$note_id = (int)$_GET['id'];

// Get the note and verify ownership
$stmt = $db->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
$stmt->execute([$note_id, $user_id]);
$note = $stmt->fetch();

if (!$note) {
    flash_message('Note not found.', 'error');
    redirect('dashboard.php');
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
    <title><?php echo htmlspecialchars($note['title']); ?> - Note App</title>
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
            font-size: 1.8rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            max-width: 70%;
            word-break: break-word;
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
            transition: var(--theme-transition);
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
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }
        
        .btn-secondary {
            background: var(--gradient-gray);
            color: white;
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(149, 165, 166, 0.4);
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            border-radius: 8px;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .note-meta-info {
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
            position: relative;
            overflow: hidden;
        }
        
        .note-meta-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        .meta-grid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .meta-value {
            color: var(--text-secondary);
            font-weight: normal;
        }
        
        .note-view-container {
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
            margin-bottom: 2rem;
        }
        
        .note-content-display {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-primary);
            white-space: pre-wrap;
            word-wrap: break-word;
            margin-bottom: 2rem;
        }
        
        .note-attachments {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid var(--card-border);
        }
        
        .note-attachments h4 {
            color: var(--text-primary);
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .attachment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .attachment-card {
            background: var(--attachment-bg);
            padding: 1.5rem;
            border-radius: 15px;
            border: 2px solid var(--attachment-border);
            transition: var(--theme-transition);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .attachment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-primary);
        }
        
        .attachment-card:hover {
            transform: translateY(-5px);
            border-color: var(--border-focus);
            box-shadow: var(--shadow-medium);
        }
        
        .attachment-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .attachment-filename {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            word-break: break-word;
        }
        
        .attachment-meta {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .reading-time {
            background: var(--attachment-bg);
            color: var(--border-focus);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            border: 1px solid var(--attachment-border);
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
                max-width: 100%;
                font-size: 1.5rem;
            }
            
            .nav {
                justify-content: center;
            }
            
            .note-view-container {
                padding: 1.5rem;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .meta-grid {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .attachment-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Print styles */
        @media print {
            body {
                background: white;
            }
            
            .header .nav,
            .action-buttons {
                display: none;
            }
            
            .note-view-container,
            .note-meta-info {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>📖 <?php echo htmlspecialchars($note['title']); ?></h1>
            <nav class="nav">
                <a href="dashboard.php" class="btn btn-secondary">🏠 Dashboard</a>
                <a href="logout.php" class="btn btn-secondary">👋 Logout</a>
            </nav>
        </header>

        <div class="action-buttons">
            <a href="note.php?id=<?php echo $note['id']; ?>" class="btn btn-primary">✏️ Edit Note</a>
            <a href="share.php?id=<?php echo $note['id']; ?>" class="btn btn-secondary">🔗 Share Note</a>
        </div>

        <div class="note-meta-info">
            <div class="meta-grid">
                <div class="meta-item">
                    <span>📅</span>
                    <strong>Created:</strong>
                    <span class="meta-value"><?php echo date('F j, Y \a\t g:i A', strtotime($note['created_at'])); ?></span>
                </div>
                <div class="meta-item">
                    <span>🔄</span>
                    <strong>Updated:</strong>
                    <span class="meta-value"><?php echo date('F j, Y \a\t g:i A', strtotime($note['updated_at'])); ?></span>
                </div>
                <div class="reading-time">
                    ⏱️ <?php echo max(1, ceil(str_word_count($note['content']) / 200)); ?> min read
                </div>
            </div>
        </div>

        <main class="main">
            <div class="note-view-container">
                <div class="note-content-display">
                    <?php echo nl2br(htmlspecialchars($note['content'])); ?>
                </div>
                
                <?php if (!empty($attachments)): ?>
                    <div class="note-attachments">
                        <h4>📎 Attachments (<?php echo count($attachments); ?>)</h4>
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
                                            case 'txt': echo '📋'; break;
                                            case 'zip': case 'rar': echo '🗜️'; break;
                                            default: echo '📎'; break;
                                        }
                                        ?>
                                    </div>
                                    <div class="attachment-filename"><?php echo htmlspecialchars($attachment['original_filename']); ?></div>
                                    <div class="attachment-meta">
                                        <?php echo format_file_size($attachment['file_size']); ?> • 
                                        <?php echo date('M j, Y', strtotime($attachment['uploaded_at'])); ?>
                                    </div>
                                    <a href="download.php?id=<?php echo $attachment['id']; ?>" 
                                       class="btn btn-small btn-primary">⬇️ Download</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Theme System -->
    <script src="assets/js/theme-manager.js"></script>
    <script>
        // Initialize theme manager
        const themeManager = new ThemeManager();
        
        // Add copy to clipboard functionality
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Note content copied to clipboard!');
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        }

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+E or Cmd+E to edit
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                window.location.href = 'note.php?id=<?php echo $note['id']; ?>';
            }
            
            // Ctrl+S or Cmd+S to share
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                window.location.href = 'share.php?id=<?php echo $note['id']; ?>';
            }
        });

        // Add reading progress indicator
        window.addEventListener('scroll', function() {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            
            // You could add a progress bar here if desired
        });
    </script>
</body>
</html>