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
    <title>Dashboard - Note App (Local)</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>My Notes <span style="font-size: 0.6em; color: #666;">(Local Dev)</span></h1>
            <nav class="nav">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="note_local.php" class="btn btn-primary">New Note</a>
                <a href="shared_with_me_local.php" class="btn btn-secondary">Shared With Me</a>
                <a href="logout_local.php" class="btn btn-secondary">Logout</a>
            </nav>
        </header>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <p><?php echo htmlspecialchars($flash['message']); ?></p>
            </div>
        <?php endif; ?>

        <main class="main">
            <?php if (empty($notes)): ?>
                <div class="empty-state">
                    <h3>No notes yet</h3>
                    <p>Create your first note to get started!</p>
                    <a href="note_local.php" class="btn btn-primary">Create Note</a>
                </div>
            <?php else: ?>
                <div class="notes-grid">
                    <?php foreach ($notes as $note): ?>
                        <div class="note-card">
                            <h3 class="note-title">
                                <a href="view_note_local.php?id=<?php echo $note['id']; ?>" style="color: inherit; text-decoration: none;">
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
                                <span class="note-date"><?php echo date('M j, Y g:i A', strtotime($note['updated_at'])); ?></span>
                                <div class="note-actions">
                                    <a href="view_note_local.php?id=<?php echo $note['id']; ?>" class="btn btn-small btn-primary">View</a>
                                    <a href="note_local.php?id=<?php echo $note['id']; ?>" class="btn btn-small">Edit</a>
                                    <a href="share_local.php?id=<?php echo $note['id']; ?>" class="btn btn-small">Share</a>
                                    <a href="dashboard_local.php?delete=<?php echo $note['id']; ?>&token=<?php echo generate_csrf_token(); ?>" 
                                       class="btn btn-small btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this note?')">Delete</a>
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