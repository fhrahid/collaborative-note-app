<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        
        // Check if email is already taken by another user
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->execute([$email, $user_id]);
        
        if ($check_stmt->rowCount() > 0) {
            $error = "Email already exists";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            if ($stmt->execute([$name, $email, $user_id])) {
                $_SESSION['user_name'] = $name;
                $success = "Profile updated successfully";
            } else {
                $error = "Failed to update profile";
            }
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Get current password hash
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!password_verify($current_password, $user['password'])) {
            $password_error = "Current password is incorrect";
        } elseif ($new_password !== $confirm_password) {
            $password_error = "New passwords do not match";
        } elseif (strlen($new_password) < 6) {
            $password_error = "Password must be at least 6 characters";
        } else {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$new_password_hash, $user_id])) {
                $password_success = "Password changed successfully";
            } else {
                $password_error = "Failed to change password";
            }
        }
    }
}

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user statistics
$stats = [];

// Total notes
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notes WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats['total_notes'] = $stmt->fetch()['count'];

// Shared notes (notes shared with user)
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM shared_notes WHERE shared_with_user_id = ?");
$stmt->execute([$user_id]);
$stats['shared_notes'] = $stmt->fetch()['count'];

// Notes shared by user
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT note_id) as count FROM shared_notes sn 
                      JOIN notes n ON sn.note_id = n.id 
                      WHERE n.user_id = ?");
$stmt->execute([$user_id]);
$stats['notes_shared_by_user'] = $stmt->fetch()['count'];

// Total attachments
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attachments a 
                      JOIN notes n ON a.note_id = n.id 
                      WHERE n.user_id = ?");
$stmt->execute([$user_id]);
$stats['total_attachments'] = $stmt->fetch()['count'];

// Recent activity (last 30 days)
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notes WHERE user_id = ? 
                      AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute([$user_id]);
$stats['recent_notes'] = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Note App</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>📝 Note App</h2>
            </div>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                    <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="create_note.php" class="nav-item">
                    <span class="nav-icon">✏️</span>
                    <span class="nav-text">New Note</span>
                </a>
                <a href="profile.php" class="nav-item active">
                    <span class="nav-icon">👤</span>
                    <span class="nav-text">Profile</span>
                </a>
                <a href="logout.php" class="nav-item">
                    <span class="nav-icon">🚪</span>
                    <span class="nav-text">Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>Profile Management</h1>
                <p>Manage your account settings and view statistics</p>
            </header>

            <div class="profile-container">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📝</div>
                        <div class="stat-info">
                            <h3><?= $stats['total_notes'] ?></h3>
                            <p>Total Notes</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🤝</div>
                        <div class="stat-info">
                            <h3><?= $stats['shared_notes'] ?></h3>
                            <p>Shared With Me</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📤</div>
                        <div class="stat-info">
                            <h3><?= $stats['notes_shared_by_user'] ?></h3>
                            <p>Shared By Me</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📎</div>
                        <div class="stat-info">
                            <h3><?= $stats['total_attachments'] ?></h3>
                            <p>Attachments</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🔥</div>
                        <div class="stat-info">
                            <h3><?= $stats['recent_notes'] ?></h3>
                            <p>Notes This Month</p>
                        </div>
                    </div>
                </div>

                <!-- Profile Forms -->
                <div class="profile-forms">
                    <!-- Update Profile -->
                    <div class="profile-section">
                        <h2>Update Profile</h2>
                        
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-error"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" class="profile-form">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                Update Profile
                            </button>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="profile-section">
                        <h2>Change Password</h2>
                        
                        <?php if (isset($password_success)): ?>
                            <div class="alert alert-success"><?= $password_success ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($password_error)): ?>
                            <div class="alert alert-error"><?= $password_error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" class="profile-form">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required>
                                <small class="form-help">Minimum 6 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">
                                Change Password
                            </button>
                        </form>
                    </div>

                    <!-- Account Information -->
                    <div class="profile-section">
                        <h2>Account Information</h2>
                        <div class="account-info">
                            <div class="info-item">
                                <label>Member Since</label>
                                <span><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
                            </div>
                            <div class="info-item">
                                <label>Last Updated</label>
                                <span><?= date('F j, Y', strtotime($user['updated_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stat cards on page load
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fadeIn');
            });
            
            // Form validation
            const forms = document.querySelectorAll('.profile-form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const button = form.querySelector('button[type="submit"]');
                    button.style.opacity = '0.7';
                    button.textContent = 'Saving...';
                });
            });
        });
    </script>
</body>
</html>