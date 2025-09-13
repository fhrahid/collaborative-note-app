<?php
require_once 'config/config_local.php';

// Require login
require_login();

$user_id = get_current_user_id();

// Handle note deletion
if (isset($_GET['delete']) && verify_csrf_token($_GET['token'] ?? '')) {
    $note_id = (int)$_GET['delete'];
    
    // First, get and delete all attachments for this note
    $stmt = $db->prepare("SELECT filename FROM attachments WHERE note_id = ?");
    $stmt->execute([$note_id]);
    $attachments = $stmt->fetchAll();
    
    // Delete attachment files from filesystem
    foreach ($attachments as $attachment) {
        $file_path = get_upload_path() . '/' . $attachment['filename'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Delete attachments from database
    $stmt = $db->prepare("DELETE FROM attachments WHERE note_id = ?");
    $stmt->execute([$note_id]);
    
    // Delete the note
    $stmt = $db->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$note_id, $user_id])) {
        flash_message('Note and attachments deleted successfully!', 'success');
    } else {
        flash_message('Failed to delete note.', 'error');
    }
    redirect('dashboard_local.php');
}

// Get user's notes with attachment count
$stmt = $db->prepare("
    SELECT n.*, 
           COUNT(a.id) as attachment_count 
    FROM notes n 
    LEFT JOIN attachments a ON n.id = a.note_id 
    WHERE n.user_id = ? 
    GROUP BY n.id 
    ORDER BY n.updated_at DESC
");
$stmt->execute([$user_id]);
$notes = $stmt->fetchAll();

$flash = get_flash_message();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Note App</title>
    <link rel="stylesheet" href="assets/css/themes.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header h1 {
            color: #2c3e50;
            font-weight: 800;
            font-size: 2.2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .user-welcome {
            color: #7f8c8d;
            font-size: 1rem;
            margin-bottom: 0.5rem;
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
            transition: all 0.3s ease;
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
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(149, 165, 166, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
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
            background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
            color: white;
            border: 2px solid rgba(0, 184, 148, 0.3);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ff7675 0%, #e17055 100%);
            color: white;
            border: 2px solid rgba(255, 118, 117, 0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .empty-state h3 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .empty-state p {
            color: #7f8c8d;
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
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .note-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15);
        }
        
        .note-title {
            margin-bottom: 1rem;
            font-size: 1.3rem;
            font-weight: 700;
            line-height: 1.4;
        }
        
        .note-title a {
            color: #2c3e50;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .note-title a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .note-content {
            color: #5a6c7d;
            margin-bottom: 1.5rem;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .note-attachments-info {
            margin-bottom: 1rem;
        }
        
        .attachment-badge {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .note-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f1f3f4;
        }
        
        .note-date {
            color: #95a5a6;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .note-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .welcome-section {
            margin-bottom: 2rem;
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
            
            .note-actions {
                gap: 0.3rem;
            }
            
            .note-meta {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .btn-small {
                padding: 0.4rem 0.8rem;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div>
                <h1>📝 My Notes</h1>
                <div class="user-welcome">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>! ✨</div>
            </div>
            <nav class="nav">
                <a href="note_local.php" class="btn btn-primary">✍️ New Note</a>
                <a href="shared_with_me_local.php" class="btn btn-secondary">🤝 Shared With Me</a>
                <a href="developers.php" class="btn btn-secondary">👨‍💻 Team</a>
                <a href="logout_local.php" class="btn btn-secondary">👋 Logout</a>
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

        <?php if (!empty($notes)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($notes); ?></div>
                    <div class="stat-label">Total Notes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo array_sum(array_column($notes, 'attachment_count')); ?></div>
                    <div class="stat-label">Attachments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($notes, function($note) { return !empty($note['is_public']); })); ?></div>
                    <div class="stat-label">Public Notes</div>
                </div>
            </div>
        <?php endif; ?>

        <main class="main">
            <?php if (empty($notes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🌟</div>
                    <h3>Start Your Journey</h3>
                    <p>Create your first note and begin organizing your thoughts, ideas, and important information!</p>
                    <a href="note_local.php" class="btn btn-primary">🚀 Create Your First Note</a>
                </div>
            <?php else: ?>
                <div class="notes-grid">
                    <?php foreach ($notes as $note): ?>
                        <div class="note-card">
                            <h3 class="note-title">
                                <a href="view_note_local.php?id=<?php echo $note['id']; ?>">
                                    <?php echo htmlspecialchars($note['title']); ?>
                                </a>
                            </h3>
                            <p class="note-content"><?php echo nl2br(htmlspecialchars(substr($note['content'], 0, 150))); ?><?php echo strlen($note['content']) > 150 ? '...' : ''; ?></p>
                            
                            <?php if ($note['attachment_count'] > 0): ?>
                                <div class="note-attachments-info">
                                    <span class="attachment-badge">
                                        📎 <?php echo $note['attachment_count']; ?> attachment<?php echo $note['attachment_count'] > 1 ? 's' : ''; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="note-meta">
                                <span class="note-date">
                                    🕒 <?php echo date('M j, Y g:i A', strtotime($note['updated_at'])); ?>
                                </span>
                                <div class="note-actions">
                                    <a href="view_note_local.php?id=<?php echo $note['id']; ?>" class="btn btn-small btn-primary">👀 View</a>
                                    <a href="note_local.php?id=<?php echo $note['id']; ?>" class="btn btn-small btn-secondary">✏️ Edit</a>
                                    <a href="share_local.php?id=<?php echo $note['id']; ?>" class="btn btn-small btn-secondary">🔗 Share</a>
                                    <a href="dashboard_local.php?delete=<?php echo $note['id']; ?>&token=<?php echo generate_csrf_token(); ?>" 
                                       class="btn btn-small btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this note? This action cannot be undone.')">🗑️ Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="assets/js/theme-manager.js"></script>
</body>
</html>