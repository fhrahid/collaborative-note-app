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
            $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
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
    <title>Login - Note App</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
        }
        
        .auth-container {
            width: 100%;
            max-width: 450px;
        }
        
        .auth-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 3rem;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .auth-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .auth-form h2 {
            text-align: center;
            margin-bottom: 1rem;
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .auth-subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 2rem;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .welcome-icon {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
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
        
        .alert p {
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem 1.2rem;
            border: 2px solid #e0e6ed;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        
        .form-group input:hover {
            border-color: #bdc3c7;
        }
        
        .btn {
            width: 100%;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            font-family: inherit;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            box-shadow: 0 6px 25px rgba(99, 102, 241, 0.3);
            margin-bottom: 1.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-4px);
            background: linear-gradient(135deg, #5b21b6 0%, #4338ca 100%);
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(99, 102, 241, 0.35);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        
        .auth-link {
            text-align: center;
            margin-top: 2rem;
            color: #7f8c8d;
            font-size: 0.95rem;
        }
        
        .auth-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .auth-link a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: underline;
        }
        
        .dev-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #f1f3f4;
        }
        
        .dev-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            background: rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
        }
        
        .dev-link a:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        
        @media (max-width: 480px) {
            .auth-form {
                padding: 2rem 1.5rem;
                margin: 10px;
            }
            
            .auth-form h2 {
                font-size: 2rem;
            }
            
            .form-group input {
                padding: 0.9rem 1rem;
            }
            
            .btn {
                padding: 0.9rem 1.2rem;
            }
        }
        
        /* Loading animation for button */
        .btn-loading {
            pointer-events: none;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s ease infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <div class="welcome-icon">👋</div>
            <h2>Welcome Back</h2>
            <p class="auth-subtitle">Sign in to access your notes</p>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php if ($flash['type'] === 'success'): ?>
                        ✅ <?php echo htmlspecialchars($flash['message']); ?>
                    <?php else: ?>
                        ⚠️ <?php echo htmlspecialchars($flash['message']); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p>❌ <?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group">
                    <label for="username">📧 Username or Email</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">🔒 Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary" id="loginBtn">
                    🚀 Sign In
                </button>
            </form>

            <p class="auth-link">New to our platform? <a href="register_local.php">Create an account</a></p>
            
            <div class="dev-link">
                <a href="developers.php">
                    👨‍💻 Meet Our Development Team
                </a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('btn-loading');
            btn.innerHTML = 'Signing In...';
        });
    </script>
</body>
</html>