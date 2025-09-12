<?php
require_once 'config/config_local.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('dashboard_local.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validation
        if (empty($username)) {
            $errors[] = 'Username is required';
        }

        if (empty($password)) {
            $errors[] = 'Password is required';
        }

        // Authenticate user
        if (empty($errors)) {
            $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                flash_message('Welcome back, ' . $user['username'] . '!', 'success');
                redirect('dashboard_local.php');
            } else {
                $errors[] = 'Invalid username or password';
            }
        }
    }
}

// Display flash message if any
$flash = get_flash_message();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Note App (Local)</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <h2>Login</h2>
            <p style="color: #666; font-size: 0.9em; margin-bottom: 1rem;">Local Development Version</p>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <p><?php echo htmlspecialchars($flash['message']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>

            <p class="auth-link">Don't have an account? <a href="register_local.php">Register here</a></p>
        </div>
    </div>
</body>
</html>