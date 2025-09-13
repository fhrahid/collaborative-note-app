<?php
require_once 'config/config.php';

// Require login
require_login();

$user_id = get_current_user_id();
$errors = [];
$success_messages = [];

// Get note ID from URL
if (!isset($_GET['id'])) {
    flash_message('Note ID is required.', 'error');
    redirect('dashboard.php');
}

$note_id = (int)$_GET['id'];

// Verify user owns the note
$stmt = $db->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
$stmt->execute([$note_id, $user_id]);
$note = $stmt->fetch();

if (!$note) {
    flash_message('Note not found or you do not have permission to share it.', 'error');
    redirect('dashboard.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'generate_public_link') {
            // Generate or update public share link
            $custom_slug = trim($_POST['custom_slug'] ?? '');
            
            if (!empty($custom_slug)) {
                // Validate custom slug
                if (!preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $custom_slug)) {
                    $errors[] = 'Custom URL must be 3-20 characters long and contain only letters, numbers, hyphens, and underscores.';
                } else {
                    // Check if slug is already taken
                    $stmt = $db->prepare("SELECT id FROM notes WHERE share_token = ? AND id != ?");
                    $stmt->execute([$custom_slug, $note_id]);
                    if ($stmt->rowCount() > 0) {
                        $errors[] = 'This custom URL is already taken. Please choose another.';
                    } else {
                        $share_token = $custom_slug;
                    }
                }
            } else {
                // Generate random token if no custom slug provided
                $share_token = generate_share_token();
            }
            
            if (empty($errors)) {
                $stmt = $db->prepare("UPDATE notes SET is_public = 1, share_token = ? WHERE id = ?");
                if ($stmt->execute([$share_token, $note_id])) {
                    $success_messages[] = 'Public share link generated successfully!';
                    $note['is_public'] = 1;
                    $note['share_token'] = $share_token;
                } else {
                    $errors[] = 'Failed to generate public link';
                }
            }
        } elseif ($action === 'disable_public_link') {
            // Disable public sharing
            $stmt = $db->prepare("UPDATE notes SET is_public = 0, share_token = NULL WHERE id = ?");
            if ($stmt->execute([$note_id])) {
                $success_messages[] = 'Public sharing disabled.';
                $note['is_public'] = 0;
                $note['share_token'] = null;
            } else {
                $errors[] = 'Failed to disable public sharing';
            }
        } elseif ($action === 'update_custom_url') {
            // Update existing public link with custom URL
            $custom_slug = trim($_POST['custom_slug'] ?? '');
            
            if (empty($custom_slug)) {
                $errors[] = 'Please enter a custom URL.';
            } elseif (!preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $custom_slug)) {
                $errors[] = 'Custom URL must be 3-20 characters long and contain only letters, numbers, hyphens, and underscores.';
            } else {
                // Check if slug is already taken
                $stmt = $db->prepare("SELECT id FROM notes WHERE share_token = ? AND id != ?");
                $stmt->execute([$custom_slug, $note_id]);
                if ($stmt->rowCount() > 0) {
                    $errors[] = 'This custom URL is already taken. Please choose another.';
                } else {
                    $stmt = $db->prepare("UPDATE notes SET share_token = ? WHERE id = ?");
                    if ($stmt->execute([$custom_slug, $note_id])) {
                        $success_messages[] = 'Custom URL updated successfully!';
                        $note['share_token'] = $custom_slug;
                    } else {
                        $errors[] = 'Failed to update custom URL';
                    }
                }
            }
        } elseif ($action === 'share_with_user') {
            // Share with specific user
            $share_with_user_id = (int)($_POST['share_with_user_id'] ?? 0);
            $permission = $_POST['permission'] ?? 'read';
            
            if ($share_with_user_id === $user_id) {
                $errors[] = 'You cannot share a note with yourself';
            } elseif (!in_array($permission, ['read', 'edit'])) {
                $errors[] = 'Invalid permission level';
            } else {
                // Check if user exists
                $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$share_with_user_id]);
                $target_user = $stmt->fetch();
                
                if (!$target_user) {
                    $errors[] = 'User not found';
                } else {
                    // Check if already shared
                    $stmt = $db->prepare("SELECT id FROM shared_notes WHERE note_id = ? AND shared_with_user_id = ?");
                    $stmt->execute([$note_id, $share_with_user_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        // Update existing share
                        $stmt = $db->prepare("UPDATE shared_notes SET permission = ? WHERE note_id = ? AND shared_with_user_id = ?");
                        if ($stmt->execute([$permission, $note_id, $share_with_user_id])) {
                            $success_messages[] = "Note sharing updated for {$target_user['username']}";
                        } else {
                            $errors[] = 'Failed to update sharing';
                        }
                    } else {
                        // Create new share
                        $stmt = $db->prepare("INSERT INTO shared_notes (note_id, shared_by_user_id, shared_with_user_id, permission) VALUES (?, ?, ?, ?)");
                        if ($stmt->execute([$note_id, $user_id, $share_with_user_id, $permission])) {
                            $success_messages[] = "Note shared with {$target_user['username']}";
                        } else {
                            $errors[] = 'Failed to share note';
                        }
                    }
                }
            }
        } elseif ($action === 'remove_share') {
            // Remove sharing with specific user
            $shared_id = (int)($_POST['shared_id'] ?? 0);
            $stmt = $db->prepare("DELETE FROM shared_notes WHERE id = ? AND note_id = ? AND shared_by_user_id = ?");
            if ($stmt->execute([$shared_id, $note_id, $user_id])) {
                $success_messages[] = 'Sharing removed successfully';
            } else {
                $errors[] = 'Failed to remove sharing';
            }
        }
    }
}

// Get current shares
$stmt = $db->prepare("
    SELECT sn.id, sn.permission, u.username, u.email, sn.shared_at 
    FROM shared_notes sn 
    JOIN users u ON sn.shared_with_user_id = u.id 
    WHERE sn.note_id = ? 
    ORDER BY sn.shared_at DESC
");
$stmt->execute([$note_id]);
$current_shares = $stmt->fetchAll();

// Handle AJAX user search
if (isset($_GET['search_users'])) {
    header('Content-Type: application/json');
    $query = $_GET['q'] ?? '';
    if (strlen($query) >= 2) {
        $users = search_users($query, $user_id, $db);
        echo json_encode($users);
    } else {
        echo json_encode([]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Note - <?php echo htmlspecialchars($note['title']); ?></title>
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
            padding: 1.5rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
        }
        
        /* Enhanced dark mode header */
        [data-theme="dark"] .header {
            background: rgba(45, 45, 45, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .header h1 {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 1.8rem;
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
        
        .btn-danger {
            background: var(--gradient-red);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
        
        .share-section {
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow-light);
            margin-bottom: 2rem;
            border: 1px solid var(--card-border);
        }
        
        /* Enhanced dark mode support */
        [data-theme="dark"] .share-section {
            background: rgba(45, 45, 45, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        [data-theme="dark"] .public-link {
            background: rgba(54, 54, 54, 0.98);
            border: 2px solid rgba(255, 255, 255, 0.15);
            color: #e8e8e8;
        }
        
        [data-theme="dark"] .form-group input,
        [data-theme="dark"] .form-group select {
            background: rgba(58, 58, 58, 0.98);
            border: 2px solid rgba(255, 255, 255, 0.15);
            color: #e8e8e8;
        }
        
        [data-theme="dark"] .search-results {
            background: rgba(45, 45, 45, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        [data-theme="dark"] .search-result {
            color: #e8e8e8;
        }
        
        [data-theme="dark"] .search-result:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .share-section h3 {
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .public-link {
            background: var(--attachment-bg);
            padding: 1.5rem;
            border-radius: 10px;
            border: 2px solid var(--attachment-border);
            word-break: break-all;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            position: relative;
            color: var(--text-primary);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--input-border);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--theme-transition);
            background: var(--input-bg);
            color: var(--text-primary);
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .user-search {
            position: relative;
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--card-bg);
            border: 2px solid var(--border-focus);
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: var(--shadow-medium);
        }
        
        /* Enhanced dark mode for search results */
        [data-theme="dark"] .search-results {
            background: rgba(45, 45, 45, 0.98);
            border: 2px solid rgba(102, 126, 234, 0.6);
            backdrop-filter: blur(10px);
        }
        
        .search-result {
            padding: 1rem;
            cursor: pointer;
            border-bottom: 1px solid var(--card-border);
            transition: background 0.2s ease;
            color: var(--text-primary);
        }
        
        .search-result:hover {
            background: var(--attachment-bg);
        }
        
        .search-result:last-child {
            border-bottom: none;
        }
        
        .shared-user {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border: 2px solid var(--card-border);
            border-radius: 10px;
            margin-bottom: 1rem;
            background: var(--card-bg);
            transition: var(--theme-transition);
        }
        
        /* Enhanced dark mode for shared user cards */
        [data-theme="dark"] .shared-user {
            background: rgba(45, 45, 45, 0.98);
            border: 2px solid rgba(255, 255, 255, 0.15);
        }
        
        [data-theme="dark"] .shared-user:hover {
            border-color: rgba(102, 126, 234, 0.6);
            background: rgba(54, 54, 54, 0.98);
        }
        
        .shared-user:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
            border-color: var(--border-focus);
        }
        
        .shared-user-info {
            flex: 1;
        }
        
        .shared-user-info strong {
            color: var(--text-primary);
            font-size: 1.1rem;
        }
        
        .shared-user-info small {
            color: var(--text-secondary);
            display: block;
            margin-top: 0.25rem;
        }
        
        .permission-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-right: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .permission-read {
            background: var(--gradient-blue);
            color: white;
        }
        
        .permission-edit {
            background: var(--gradient-green);
            color: white;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            font-weight: 500;
        }
        
        .alert-error {
            background: var(--gradient-red);
            color: white;
            border: 2px solid rgba(231, 76, 60, 0.3);
        }
        
        .alert-success {
            background: var(--gradient-green);
            color: white;
            border: 2px solid rgba(0, 184, 148, 0.3);
        }
        
        .note-info {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .note-info h2 {
            color: var(--text-primary);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .note-info p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        /* Custom URL styling */
        .custom-url-section {
            background: var(--attachment-bg);
            padding: 1.5rem;
            border-radius: 10px;
            border: 2px solid var(--attachment-border);
            margin-top: 1rem;
        }
        
        .url-preview {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .url-preview span {
            color: var(--text-secondary);
            font-weight: 600;
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
            
            .shared-user {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Share Note</h1>
            <nav class="nav">
                <a href="note.php?id=<?php echo $note_id; ?>" class="btn btn-secondary">Edit Note</a>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </nav>
        </header>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_messages)): ?>
            <div class="alert alert-success">
                <?php foreach ($success_messages as $message): ?>
                    <p><?php echo htmlspecialchars($message); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <main class="main">
            <div class="note-info">
                <h2><?php echo htmlspecialchars($note['title']); ?></h2>
                <p>Manage sharing settings for this note</p>
            </div>

            <!-- Public Link Sharing -->
            <div class="share-section">
                <h3>🌐 Public Link Sharing</h3>
                <p>Anyone with the link can view this note (no login required).</p>
                
                <?php if ($note['is_public']): ?>
                    <div class="public-link">
                        <strong style="color: var(--text-primary);">🔗 Your Public Link:</strong><br>
                        <span id="publicLink" style="color: var(--border-focus); font-weight: 600;"><?php echo generate_share_url($note['id'], $note['share_token']); ?></span>
                        <button onclick="copyToClipboard('publicLink')" class="btn btn-small btn-secondary" style="margin-left: 15px;">📋 Copy Link</button>
                    </div>
                    
                    <!-- Custom URL Update Form -->
                    <div class="custom-url-section">
                        <strong style="color: var(--text-primary); font-size: 1.1rem;">🎨 Customize Your URL</strong>
                        <form method="POST" style="margin-top: 15px;">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="update_custom_url">
                            <div class="url-preview">
                                <span><?php echo rtrim(get_base_url(), '/'); ?>/</span>
                                <input type="text" name="custom_slug" 
                                       value="<?php echo htmlspecialchars($note['share_token']); ?>" 
                                       placeholder="your-custom-url" 
                                       pattern="[a-zA-Z0-9_-]{3,20}"
                                       title="3-20 characters: letters, numbers, hyphens, underscores only"
                                       style="flex: 1; padding: 0.75rem; border: 2px solid var(--input-border); border-radius: 8px; font-family: monospace; background: var(--input-bg); color: var(--text-primary);" required>
                                <button type="submit" class="btn btn-small btn-primary">✨ Update</button>
                            </div>
                            <small style="color: var(--text-secondary); margin-top: 8px; display: block; font-style: italic;">
                                💡 Create a memorable URL with 3-20 characters (letters, numbers, hyphens, underscores)
                            </small>
                        </form>
                    </div>
                    
                    <form method="POST" style="margin-top: 1.5rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="disable_public_link">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to disable public sharing?')">🚫 Disable Public Sharing</button>
                    </form>
                <?php else: ?>
                    <div class="custom-url-section">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="generate_public_link">
                            
                            <div class="form-group">
                                <label for="custom_slug">🎯 Custom URL (optional)</label>
                                <div class="url-preview">
                                    <span><?php echo rtrim(get_base_url(), '/'); ?>/</span>
                                    <input type="text" name="custom_slug" id="custom_slug"
                                           placeholder="your-custom-url" 
                                           pattern="[a-zA-Z0-9_-]{3,20}"
                                           title="3-20 characters: letters, numbers, hyphens, underscores only"
                                           style="flex: 1; padding: 0.75rem; border: 2px solid var(--input-border); border-radius: 8px; font-family: monospace; background: var(--input-bg); color: var(--text-primary);">
                                </div>
                                <small style="color: var(--text-secondary); margin-top: 8px; display: block; font-style: italic;">
                                    💡 Leave empty for a random URL or create your own memorable link
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">🚀 Generate Public Link</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- User-Specific Sharing -->
            <div class="share-section">
                <h3>👥 Share with Specific Users</h3>
                <p>Share this note with registered users and control their permissions.</p>

                <form method="POST" id="shareForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="share_with_user">
                    <input type="hidden" name="share_with_user_id" id="selectedUserId">

                    <div class="form-group">
                        <label for="userSearch">Search for users</label>
                        <div class="user-search">
                            <input type="text" id="userSearch" placeholder="Type username or email..." autocomplete="off">
                            <div class="search-results" id="searchResults" style="display: none;"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="permission">Permission Level</label>
                        <select name="permission" id="permission">
                            <option value="read">Read Only</option>
                            <option value="edit">Read & Edit</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" id="shareButton" disabled>🤝 Share Note</button>
                </form>
            </div>

            <!-- Current Shares -->
            <?php if (!empty($current_shares)): ?>
            <div class="share-section">
                <h3>📋 Currently Shared With</h3>
                <?php foreach ($current_shares as $share): ?>
                    <div class="shared-user">
                        <div class="shared-user-info">
                            <strong><?php echo htmlspecialchars($share['username']); ?></strong>
                            <small style="color: var(--text-secondary); display: block;"><?php echo htmlspecialchars($share['email']); ?></small>
                            <small style="color: var(--text-secondary);">Shared on <?php echo date('M j, Y', strtotime($share['shared_at'])); ?></small>
                        </div>
                        <div>
                            <span class="permission-badge permission-<?php echo $share['permission']; ?>">
                                <?php echo ucfirst($share['permission']); ?>
                            </span>
                            <form method="POST" style="display: inline; margin-left: 10px;">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="action" value="remove_share">
                                <input type="hidden" name="shared_id" value="<?php echo $share['id']; ?>">
                                <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Remove sharing with this user?')">❌ Remove</button>
                            </form>
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
        
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            navigator.clipboard.writeText(text).then(function() {
                // Create a better notification
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = '✅ Copied!';
                button.style.background = 'var(--gradient-green)';
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '';
                }, 2000);
            }).catch(function() {
                alert('Failed to copy. Please copy manually.');
            });
        }

        // User search functionality
        let searchTimeout;
        const userSearch = document.getElementById('userSearch');
        const searchResults = document.getElementById('searchResults');
        const selectedUserId = document.getElementById('selectedUserId');
        const shareButton = document.getElementById('shareButton');

        userSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`share.php?id=<?php echo $note_id; ?>&search_users=1&q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(users => {
                        searchResults.innerHTML = '';
                        if (users.length > 0) {
                            users.forEach(user => {
                                const div = document.createElement('div');
                                div.className = 'search-result';
                                div.innerHTML = `<strong>${user.username}</strong><br><small>${user.email}</small>`;
                                div.onclick = function() {
                                    selectedUserId.value = user.id;
                                    userSearch.value = user.username;
                                    searchResults.style.display = 'none';
                                    shareButton.disabled = false;
                                };
                                searchResults.appendChild(div);
                            });
                            searchResults.style.display = 'block';
                        } else {
                            searchResults.innerHTML = '<div class="search-result">No users found</div>';
                            searchResults.style.display = 'block';
                        }
                    });
            }, 300);
        });

        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-search')) {
                searchResults.style.display = 'none';
            }
        });
    </script>
    
    <!-- Theme System -->
    <script src="assets/js/theme-manager.js"></script>
    <script>
        // Initialize theme manager
        const themeManager = new ThemeManager();
    </script>
</body>
</html>