<?php
require_once 'config/config.php';

// Require login
require_login();

$user_id = get_current_user_id();

// Get notes shared with current user
$stmt = $db->prepare("
    SELECT n.*, u.username as owner_username, sn.permission, sn.shared_at
    FROM notes n 
    JOIN shared_notes sn ON n.id = sn.note_id 
    JOIN users u ON n.user_id = u.id 
    WHERE sn.shared_with_user_id = ? 
    ORDER BY sn.shared_at DESC
");
$stmt->execute([$user_id]);
$shared_notes = $stmt->fetchAll();

$flash = get_flash_message();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Notes - Note App</title>
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
            max-width: 1200px;
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
            font-size: 2.2rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
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
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success {
            background: var(--gradient-green);
            color: white;
            border: 2px solid rgba(0, 184, 148, 0.3);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ff7675 0%, #e17055 100%);
            color: white;
            border: 2px solid rgba(255, 118, 117, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            border-radius: 20px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
        }
        
        .empty-state h3 {
            font-size: 2rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .empty-state p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .note-card {
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
            transition: var(--theme-transition);
            position: relative;
            overflow: hidden;
        }
        
        .note-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        .note-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-medium);
        }
        
        .shared-indicator {
            background: var(--attachment-bg);
            color: var(--border-focus);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border-focus);
        }
        
        .permission-indicator {
            float: right;
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .permission-read {
            background: rgba(255, 193, 7, 0.2);
            color: #856404;
            border: 1px solid #ffc107;
        }
        
        .permission-edit {
            background: rgba(40, 167, 69, 0.2);
            color: #155724;
            border: 1px solid #28a745;
        }
        
        .note-title {
            margin-bottom: 1rem;
            font-size: 1.3rem;
            font-weight: 700;
            line-height: 1.4;
        }
        
        .note-title a {
            color: var(--text-primary);
            text-decoration: none;
            transition: var(--theme-transition);
        }
        
        .note-title a:hover {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .note-content {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .note-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--card-border);
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .note-owner {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .note-date {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .note-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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
                font-size: 1.8rem;
            }
            
            .nav {
                justify-content: center;
                gap: 0.5rem;
            }
            
            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.8rem;
            }
            
            .notes-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .note-card {
                padding: 1.5rem;
            }
            
            .note-meta {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>🤝 Shared with Me</h1>
            <nav class="nav">
                <a href="dashboard.php" class="btn btn-secondary">🏠 My Notes</a>
                <a href="developers.php" class="btn btn-secondary">👨‍💻 Team</a>
                <a href="logout.php" class="btn btn-secondary">👋 Logout</a>
            </nav>
        </header>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php if ($flash['type'] === 'success'): ?>
                    ✅ <?php echo htmlspecialchars($flash['message']); ?>
                <?php else: ?>
                    ⚠️ <?php echo htmlspecialchars($flash['message']); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <main class="main">
            <?php if (empty($shared_notes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>No Shared Notes</h3>
                    <p>You don't have any notes shared with you yet. When someone shares a note with you, it will appear here!</p>
                    <a href="dashboard.php" class="btn btn-primary">🏠 Go to My Notes</a>
                </div>
            <?php else: ?>
                <div class="notes-grid">
                    <?php foreach ($shared_notes as $note): ?>
                        <div class="note-card">
                            <div class="shared-indicator">
                                🤝 Shared by <?php echo htmlspecialchars($note['owner_username']); ?>
                                <span class="permission-indicator permission-<?php echo $note['permission']; ?>">
                                    <?php echo $note['permission'] === 'write' ? '✏️ Edit' : '👀 Read'; ?>
                                </span>
                            </div>
                            <h3 class="note-title">
                                <a href="view_shared_note.php?id=<?php echo $note['id']; ?>">
                                    <?php echo htmlspecialchars($note['title']); ?>
                                </a>
                            </h3>
                            <p class="note-content"><?php echo nl2br(htmlspecialchars(substr($note['content'], 0, 150))); ?><?php echo strlen($note['content']) > 150 ? '...' : ''; ?></p>
                            
                            <div class="note-meta">
                                <div>
                                    <div class="note-owner">👤 By <?php echo htmlspecialchars($note['owner_username']); ?></div>
                                    <div class="note-date">🕒 Shared <?php echo date('M j, Y g:i A', strtotime($note['shared_at'])); ?></div>
                                </div>
                                <div class="note-actions">
                                    <a href="view_shared_note.php?id=<?php echo $note['id']; ?>" class="btn btn-small btn-primary">👀 View</a>
                                    <?php if ($note['permission'] === 'write'): ?>
                                        <a href="edit_shared_note.php?id=<?php echo $note['id']; ?>" class="btn btn-small btn-secondary">✏️ Edit</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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