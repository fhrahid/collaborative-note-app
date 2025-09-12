<?php
require_once 'config/config.php';

// Require login
require_login();

$user_id = get_current_user_id();

// Handle note deletion
if (isset($_GET['delete']) && verify_csrf_token($_GET['token'] ?? '')) {
    $note_id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$note_id, $user_id])) {
        flash_message('Note deleted successfully!', 'success');
    } else {
        flash_message('Failed to delete note.', 'error');
    }
    redirect('dashboard.php');
}

// Get user's notes
$stmt = $db->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY updated_at DESC");
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>My Notes</h1>
            <nav class="nav">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="note.php" class="btn btn-primary">New Note</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
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
                    <a href="note.php" class="btn btn-primary">Create Note</a>
                </div>
            <?php else: ?>
                <div class="notes-grid">
                    <?php foreach ($notes as $note): ?>
                        <div class="note-card">
                            <h3 class="note-title"><?php echo htmlspecialchars($note['title']); ?></h3>
                            <p class="note-content"><?php echo nl2br(htmlspecialchars(substr($note['content'], 0, 150))); ?><?php echo strlen($note['content']) > 150 ? '...' : ''; ?></p>
                            <div class="note-meta">
                                <span class="note-date"><?php echo date('M j, Y g:i A', strtotime($note['updated_at'])); ?></span>
                                <div class="note-actions">
                                    <a href="note.php?id=<?php echo $note['id']; ?>" class="btn btn-small">Edit</a>
                                    <a href="dashboard.php?delete=<?php echo $note['id']; ?>&token=<?php echo generate_csrf_token(); ?>" 
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