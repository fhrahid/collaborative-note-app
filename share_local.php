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
            $share_token = generate_share_token();
            $stmt = $db->prepare("UPDATE notes SET is_public = 1, share_token = ? WHERE id = ?");
            if ($stmt->execute([$share_token, $note_id])) {
                $success_messages[] = 'Public share link generated successfully!';
                $note['is_public'] = 1;
                $note['share_token'] = $share_token;
            } else {
                $errors[] = 'Failed to generate public link';
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
    SELECT sn.id, sn.permission, u.username, u.email, sn.created_at 
    FROM shared_notes sn 
    JOIN users u ON sn.shared_with_user_id = u.id 
    WHERE sn.note_id = ? AND sn.shared_by_user_id = ?
    ORDER BY sn.created_at DESC
");
$stmt->execute([$note_id, $user_id]);
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
        .share-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .share-section h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        .public-link {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            word-break: break-all;
            font-family: monospace;
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
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
        }
        .search-result {
            padding: 0.75rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .search-result:hover {
            background: #f8f9fa;
        }
        .shared-user {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 0.5rem;
        }
        .shared-user-info {
            flex: 1;
        }
        .permission-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .permission-read {
            background: #d1ecf1;
            color: #0c5460;
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
                        <strong>Public Link:</strong><br>
                        <span id="publicLink">http://localhost:8000/shared_note_local.php?token=<?php echo $note['share_token']; ?></span>
                        <button onclick="copyToClipboard('publicLink')" class="btn btn-small" style="margin-left: 10px;">Copy</button>
                    </div>
                    <form method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="disable_public_link">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to disable public sharing?')">Disable Public Sharing</button>
                    </form>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="generate_public_link">
                        <button type="submit" class="btn btn-primary">Generate Public Link</button>
                    </form>
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

                    <button type="submit" class="btn btn-primary" id="shareButton" disabled>Share Note</button>
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
                            <small style="color: #999;">Shared on <?php echo date('M j, Y', strtotime($share['created_at'])); ?></small>
                        </div>
                        <div>
                            <span class="permission-badge permission-<?php echo $share['permission']; ?>">
                                <?php echo ucfirst($share['permission']); ?>
                            </span>
                            <form method="POST" style="display: inline; margin-left: 10px;">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="action" value="remove_share">
                                <input type="hidden" name="shared_id" value="<?php echo $share['id']; ?>">
                                <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Remove sharing with this user?')">Remove</button>
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
                alert('Link copied to clipboard!');
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