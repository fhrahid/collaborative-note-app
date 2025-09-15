<?php
require_once 'config/config.php';

// Require login
require_login();

$user_id = get_current_user_id();

// Get note ID from URL
if (!isset($_GET['id'])) {
    flash_message('Note ID is required.', 'error');
    redirect('shared_with_me.php');
}

$note_id = (int)$_GET['id'];

// Check if user has access to this note
$permission = can_user_access_note($note_id, $user_id, $db);

if (!$permission) {
    flash_message('You do not have permission to view this note.', 'error');
    redirect('shared_with_me.php');
}

// Get the note details
if ($permission === 'owner') {
    // User owns the note
    $stmt = $db->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
    $stmt->execute([$note_id, $user_id]);
    $note = $stmt->fetch();
    $shared_by = null;
} else {
    // First try to get note from shared_notes
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
    
    // If not found in shared_notes, check collaborators
    if (!$note) {
        $stmt = $db->prepare("
            SELECT n.*, 'Collaborator' as access_type
            FROM notes n 
            JOIN collaborators c ON n.id = c.note_id 
            WHERE n.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$note_id, $user_id]);
        $note = $stmt->fetch();
        $shared_by = 'Added as collaborator';
    }
}

if (!$note) {
    flash_message('Note not found.', 'error');
    redirect('shared_with_me.php');
}

// Load attachments for this note
$stmt = $db->prepare("SELECT * FROM attachments WHERE note_id = ? ORDER BY uploaded_at");
$stmt->execute([$note_id]);
$attachments = $stmt->fetchAll();

// Calculate estimated reading time
function calculateReadingTime($content) {
    $words = str_word_count(strip_tags($content));
    $minutes = ceil($words / 200); // Average reading speed: 200 words per minute
    return $minutes;
}
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
            max-width: 1000px;
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
            font-size: 2rem;
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
        
        .permission-badge {
            background: var(--gradient-blue);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .permission-edit {
            background: var(--gradient-green);
        }
        
        .note-meta-info {
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
        }
        
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            align-items: center;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .meta-item strong {
            color: var(--text-primary);
        }
        
        .reading-time {
            background: var(--gradient-purple);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
        }
        
        .note-content-display {
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
            margin-bottom: 2rem;
        }
        
        .note-content-display h2, .note-content-display h3, .note-content-display h4 {
            color: var(--text-primary);
            margin: 1.5rem 0 1rem 0;
            font-weight: 700;
        }
        
        .note-content-display p {
            color: var(--text-primary);
            margin-bottom: 1rem;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .note-attachments {
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
        }
        
        .note-attachments h4 {
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .attachment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .attachment-card {
            background: var(--attachment-bg);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            transition: var(--theme-transition);
            border: 2px solid var(--attachment-border);
        }
        
        .attachment-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
            border-color: var(--border-focus);
        }
        
        .attachment-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .attachment-filename {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            word-break: break-word;
        }
        
        .attachment-meta {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
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
            
            .meta-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .shared-indicator {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><?php echo htmlspecialchars($note['title']); ?></h1>
            <nav class="nav">
                <?php if ($permission === 'owner'): ?>
                    <a href="note.php?id=<?php echo $note['id']; ?>" class="btn btn-primary">✏️ Edit</a>
                    <a href="share.php?id=<?php echo $note['id']; ?>" class="btn btn-secondary">🔗 Share</a>
                    <a href="dashboard.php" class="btn btn-secondary">📚 My Notes</a>
                <?php elseif ($permission === 'write'): ?>
                    <a href="edit_shared_note.php?id=<?php echo $note['id']; ?>" class="btn btn-primary">✏️ Edit</a>
                    <a href="shared_with_me.php" class="btn btn-secondary">🤝 Shared With Me</a>
                <?php else: ?>
                    <a href="shared_with_me.php" class="btn btn-secondary">🤝 Shared With Me</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-secondary">👋 Logout</a>
            </nav>
        </header>

        <main class="main">
            <?php if ($shared_by): ?>
                <div class="shared-indicator">
                    <span>📤 Shared by <strong><?php echo htmlspecialchars($shared_by); ?></strong></span>
                    <span class="permission-badge permission-<?php echo $note['permission'] ?? 'read'; ?>">
                        <?php echo ucfirst($note['permission'] ?? 'read'); ?> Access
                    </span>
                </div>
            <?php endif; ?>

            <div class="note-meta-info">
                <div class="meta-grid">
                    <div class="meta-item">
                        <span>📅</span>
                        <span><strong>Created:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($note['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span>🔄</span>
                        <span><strong>Last Updated:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($note['updated_at'])); ?></span>
                    </div>
                    <div class="reading-time">
                        ⏱️ <?php echo calculateReadingTime($note['content']); ?> min read
                    </div>
                </div>
            </div>

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
                                        case 'ppt': case 'pptx': echo '📊'; break;
                                        case 'jpg': case 'jpeg': case 'png': case 'gif': case 'webp': echo '🖼️'; break;
                                        case 'mp3': case 'wav': case 'ogg': echo '🎵'; break;
                                        case 'mp4': case 'avi': case 'mov': echo '🎬'; break;
                                        case 'zip': case 'rar': case '7z': echo '🗜️'; break;
                                        case 'txt': case 'md': echo '📄'; break;
                                        default: echo '📎'; break;
                                    }
                                    ?>
                                </div>
                                <div class="attachment-filename"><?php echo htmlspecialchars($attachment['original_filename']); ?></div>
                                <div class="attachment-meta"><?php echo format_file_size($attachment['file_size']); ?></div>
                                <a href="download.php?id=<?php echo $attachment['id']; ?>" 
                                   class="btn btn-small btn-primary">💾 Download</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
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