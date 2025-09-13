<?php
require_once 'config/config_local.php';

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
    <title>Shared Notes - Note App (Local)</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .shared-indicator {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: inline-block;
        }
        .permission-indicator {
            float: right;
            background: #f0f0f0;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-size: 0.75rem;
        }
        .permission-read {
            background: #fff3cd;
            color: #856404;
        }
        .permission-edit {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Shared With Me <span style="font-size: 0.6em; color: #666;">(Local Dev)</span></h1>
            <nav class="nav">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="dashboard_local.php" class="btn btn-secondary">My Notes</a>
                <a href="logout_local.php" class="btn btn-secondary">Logout</a>
            </nav>
        </header>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <p><?php echo htmlspecialchars($flash['message']); ?></p>
            </div>
        <?php endif; ?>

        <main class="main">
            <?php if (empty($shared_notes)): ?>
                <div class="empty-state">
                    <h3>No shared notes</h3>
                    <p>When other users share notes with you, they will appear here.</p>
                    <a href="dashboard_local.php" class="btn btn-primary">Go to My Notes</a>
                </div>
            <?php else: ?>
                <div class="notes-grid">
                    <?php foreach ($shared_notes as $note): ?>
                        <div class="note-card">
                            <div class="shared-indicator">
                                📤 Shared by <?php echo htmlspecialchars($note['owner_username']); ?>
                                <span class="permission-indicator permission-<?php echo $note['permission']; ?>">
                                    <?php echo ucfirst($note['permission']); ?>
                                </span>
                            </div>
                            <h3 class="note-title"><?php echo htmlspecialchars($note['title']); ?></h3>
                            <p class="note-content"><?php echo nl2br(htmlspecialchars(substr($note['content'], 0, 150))); ?><?php echo strlen($note['content']) > 150 ? '...' : ''; ?></p>
                            <div class="note-meta">
                                <div style="display: flex; flex-direction: column; font-size: 0.85rem;">
                                    <span class="note-date">Shared: <?php echo date('M j, Y g:i A', strtotime($note['shared_at'])); ?></span>
                                    <span style="color: #999;">Updated: <?php echo date('M j, Y g:i A', strtotime($note['updated_at'])); ?></span>
                                </div>
                                <div class="note-actions">
                                    <a href="view_shared_note_local.php?id=<?php echo $note['id']; ?>" class="btn btn-small">View</a>
                                    <?php if ($note['permission'] === 'edit'): ?>
                                        <a href="edit_shared_note_local.php?id=<?php echo $note['id']; ?>" class="btn btn-small btn-primary">Edit</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>