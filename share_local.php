<?php
require_once 'config/config_local.php';

// Require login
require_login();

$user_id = get_current_user_id();
$errors = [];
$success_messages = [];

// Get note ID from URL
if (!isset($_GET['id'])) {
    flash_message('Note ID is required.', 'error');
    redirect('dashboard_local.php');
}

$note_id = (int)$_GET['id'];

// Verify user owns the note
$stmt = $db->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
$stmt->execute([$note_id, $user_id]);
$note = $stmt->fetch();

if (!$note) {
    flash_message('Note not found or you do not have permission to share it.', 'error');
    redirect('dashboard_local.php');
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
    <link rel="stylesheet" href="assets/css/style.css">
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
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            color: #2c3e50;
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
            border-radius: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            font-family: inherit;
            letter-spacing: 0.02em;
            border: 2px solid transparent;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.8s ease;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.25);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            background: linear-gradient(135deg, #5b21b6 0%, #4338ca 100%);
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.35);
        }
        
        .btn-primary:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(100, 116, 139, 0.25);
        }
        
        .btn-secondary:hover {
            transform: translateY(-3px);
            background: linear-gradient(135deg, #475569 0%, #334155 100%);
            box-shadow: 0 8px 30px rgba(100, 116, 139, 0.35);
        }
        
        .btn-secondary:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(100, 116, 139, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.25);
        }
        
        .btn-success:hover {
            transform: translateY(-3px);
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.35);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.25);
        }
        
        .btn-danger:hover {
            transform: translateY(-3px);
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            box-shadow: 0 8px 30px rgba(239, 68, 68, 0.35);
        }
        
        .btn-danger:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(239, 68, 68, 0.3);
        }
        
        .btn-outline {
            background: rgba(255, 255, 255, 0.1);
            color: #374151;
            border: 2px solid #e5e7eb;
            backdrop-filter: blur(10px);
        }
        
        .btn-outline:hover {
            background: rgba(99, 102, 241, 0.1);
            border-color: #6366f1;
            color: #6366f1;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.15);
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            border-radius: 10px;
        }
        
        .btn-large {
            padding: 1rem 2rem;
            font-size: 1rem;
            border-radius: 16px;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        
        .btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }
        }
        
        .share-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .share-section h3 {
            margin-bottom: 1.5rem;
            color: #2c3e50;
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .public-link {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem;
            border-radius: 10px;
            border: 2px solid #dee2e6;
            word-break: break-all;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
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
            background: white;
            border: 2px solid #3498db;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .search-result {
            padding: 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f1f3f4;
            transition: background 0.2s ease;
        }
        
        .search-result:hover {
            background: #f8f9fa;
        }
        
        .search-result:last-child {
            border-bottom: none;
        }
        
        .shared-user {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            margin-bottom: 1rem;
            background: white;
            transition: all 0.3s ease;
        }
        
        .shared-user:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #3498db;
        }
        
        .shared-user-info {
            flex: 1;
        }
        
        .shared-user-info strong {
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .shared-user-info small {
            color: #7f8c8d;
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
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
        }
        
        .permission-edit {
            background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
            color: white;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            font-weight: 500;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ff7675 0%, #e17055 100%);
            color: white;
            border: 2px solid #d63031;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
            color: white;
            border: 2px solid #00b894;
        }
        
        .note-info {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .note-info h2 {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .note-info p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
        }
        
        /* Custom URL styling */
        .custom-url-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem;
            border-radius: 10px;
            border: 2px solid #dee2e6;
            margin-top: 1rem;
        }
        
        .url-preview {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .url-preview span {
            color: #6c757d;
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
                <a href="note_local.php?id=<?php echo $note_id; ?>" class="btn btn-secondary">Edit Note</a>
                <a href="dashboard_local.php" class="btn btn-secondary">Back to Dashboard</a>
                <a href="logout_local.php" class="btn btn-secondary">Logout</a>
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
                <p style="color: #666;">Manage sharing settings for this note</p>
            </div>

            <!-- Public Link Sharing -->
            <div class="share-section">
                <h3>🌐 Public Link Sharing</h3>
                <p>Anyone with the link can view this note (no login required).</p>
                
                <?php if ($note['is_public']): ?>
                    <div class="public-link">
                        <strong style="color: #2c3e50;">🔗 Your Public Link:</strong><br>
                        <span id="publicLink" style="color: #3498db; font-weight: 600;"><?php echo generate_share_url($note['id'], $note['share_token']); ?></span>
                        <button onclick="copyToClipboard('publicLink')" class="btn btn-small btn-secondary" style="margin-left: 15px;">📋 Copy Link</button>
                    </div>
                    
                    <!-- Custom URL Update Form -->
                    <div class="custom-url-section">
                        <strong style="color: #2c3e50; font-size: 1.1rem;">🎨 Customize Your URL</strong>
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
                                       style="flex: 1; padding: 0.75rem; border: 2px solid #e1e8ed; border-radius: 8px; font-family: monospace;" required>
                                <button type="submit" class="btn btn-small btn-primary">✨ Update</button>
                            </div>
                            <small style="color: #7f8c8d; margin-top: 8px; display: block; font-style: italic;">
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
                                           style="flex: 1; padding: 0.75rem; border: 2px solid #e1e8ed; border-radius: 8px; font-family: monospace;">
                                </div>
                                <small style="color: #7f8c8d; margin-top: 8px; display: block; font-style: italic;">
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
                            <small style="color: #666; display: block;"><?php echo htmlspecialchars($share['email']); ?></small>
                            <small style="color: #999;">Shared on <?php echo date('M j, Y', strtotime($share['shared_at'])); ?></small>
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

    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            navigator.clipboard.writeText(text).then(function() {
                // Create a better notification
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = '✅ Copied!';
                button.style.background = '#00b894';
                
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
                fetch(`share_local.php?id=<?php echo $note_id; ?>&search_users=1&q=${encodeURIComponent(query)}`)
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
</body>
</html>