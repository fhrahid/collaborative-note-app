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
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($username)) {
            $errors[] = 'Username is required';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long';
        }

        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long';
        }

        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }

        // Check if username or email already exists
        if (empty($errors)) {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Username or email already exists';
            }
        }

        // Create user if no errors
        if (empty($errors)) {
            // Check if email already exists
            $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $check_stmt->execute([$email]);
            
            if ($check_stmt->rowCount() > 0) {
                $errors[] = 'Email address is already registered';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password, name) VALUES (?, ?, ?, ?)");
                
                try {
                    if ($stmt->execute([$username, $email, $password_hash, $username])) {
                        flash_message('Account created successfully! Please login.', 'success');
                        redirect('login_local.php');
                    } else {
                        $errors[] = 'Failed to create account';
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {  // Integrity constraint violation
                        $errors[] = 'Username or email already exists';
                    } else {
                        $errors[] = 'Failed to create account: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Note App</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
        }
        
        .auth-container {
            width: 100%;
            max-width: 500px;
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
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ff7675 0%, #e17055 100%);
            color: white;
            border: 2px solid rgba(255, 118, 117, 0.3);
        }
        
        .alert p {
            margin: 0.2rem 0;
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
        
        .form-group input:valid {
            border-color: #00b894;
        }
        
        .btn {
            width: 100%;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-family: inherit;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
            margin-bottom: 1.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(-1px);
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
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        
        .strength-bar {
            width: 100%;
            height: 4px;
            background: #ecf0f1;
            border-radius: 2px;
            margin-top: 0.3rem;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(135deg, #e74c3c 0%, #f39c12 50%, #27ae60 100%);
            border-radius: 2px;
            transition: width 0.3s ease;
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
            <div class="welcome-icon">🚀</div>
            <h2>Join Us</h2>
            <p class="auth-subtitle">Create your account to get started</p>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p>❌ <?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group">
                    <label for="username">👤 Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required minlength="3">
                </div>

                <div class="form-group">
                    <label for="email">📧 Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">🔒 Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span id="strengthText">Enter a password</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">🔐 Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary" id="registerBtn">
                    ✨ Create Account
                </button>
            </form>

            <p class="auth-link">Already have an account? <a href="login_local.php">Sign in here</a></p>
            
            <div class="dev-link">
                <a href="developers.php">
                    👨‍💻 Meet Our Development Team
                </a>
            </div>
        </div>
    </div>

    <script src="assets/js/theme-manager.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('registerBtn');
            btn.classList.add('btn-loading');
            btn.innerHTML = 'Creating Account...';
        });

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
            if (password.match(/\d/)) strength += 25;
            if (password.match(/[^a-zA-Z\d]/)) strength += 25;
            
            strengthFill.style.width = strength + '%';
            
            if (strength === 0) {
                strengthText.textContent = 'Enter a password';
            } else if (strength <= 25) {
                strengthText.textContent = 'Weak password';
            } else if (strength <= 50) {
                strengthText.textContent = 'Fair password';
            } else if (strength <= 75) {
                strengthText.textContent = 'Good password';
            } else {
                strengthText.textContent = 'Strong password';
            }
        });

        // Password confirmation check
        document.getElementById('confirm_password').addEventListener('input', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = e.target.value;
            
            if (confirmPassword && password !== confirmPassword) {
                e.target.style.borderColor = '#e74c3c';
            } else if (confirmPassword) {
                e.target.style.borderColor = '#00b894';
            }
        });
    </script>
</body>
</html>