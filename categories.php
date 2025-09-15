<?php
require_once 'config/config.php';

// Require login
require_login();

$user_id = get_current_user_id();
$errors = [];
$success_messages = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $color = trim($_POST['color'] ?? '#3498db');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($name)) {
                $errors[] = 'Category name is required';
            } elseif (strlen($name) > 100) {
                $errors[] = 'Category name must be 100 characters or less';
            } else {
                // Check if category name already exists for this user
                $stmt = $db->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ?");
                $stmt->execute([$user_id, $name]);
                
                if ($stmt->rowCount() > 0) {
                    $errors[] = 'Category name already exists';
                } else {
                    if (create_category($user_id, $name, $color, $description, $db)) {
                        $success_messages[] = 'Category created successfully!';
                    } else {
                        $errors[] = 'Failed to create category';
                    }
                }
            }
        } elseif ($action === 'update') {
            $category_id = (int)($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $color = trim($_POST['color'] ?? '#3498db');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($name)) {
                $errors[] = 'Category name is required';
            } elseif (strlen($name) > 100) {
                $errors[] = 'Category name must be 100 characters or less';
            } else {
                // Check if category name already exists for this user (excluding current category)
                $stmt = $db->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND id != ?");
                $stmt->execute([$user_id, $name, $category_id]);
                
                if ($stmt->rowCount() > 0) {
                    $errors[] = 'Category name already exists';
                } else {
                    if (update_category($category_id, $user_id, $name, $color, $description, $db)) {
                        $success_messages[] = 'Category updated successfully!';
                    } else {
                        $errors[] = 'Failed to update category';
                    }
                }
            }
        } elseif ($action === 'delete') {
            $category_id = (int)($_POST['category_id'] ?? 0);
            
            if (delete_category($category_id, $user_id, $db)) {
                $success_messages[] = 'Category deleted successfully! Notes in this category are now uncategorized.';
            } else {
                $errors[] = 'Failed to delete category';
            }
        }
    }
}

// Get all categories for this user
$categories = get_user_categories($user_id, $db);
$category_stats = get_category_stats($user_id, $db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Notelify</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        .header h1 {
            color: var(--text-primary);
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }
        
        .btn-secondary {
            background: var(--card-bg);
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }
        
        .btn-danger {
            background: var(--gradient-red);
            color: white;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 2px solid transparent;
        }
        
        .alert-error {
            background: var(--gradient-red);
            color: white;
            border-color: rgba(231, 76, 60, 0.3);
        }
        
        .alert-success {
            background: var(--gradient-green);
            color: white;
            border-color: rgba(0, 184, 148, 0.3);
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .section {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 12px;
            border: 2px solid var(--border-color);
        }
        
        .section h2 {
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            font-weight: 600;
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
        .form-group textarea {
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
        .form-group textarea:focus {
            outline: none;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .color-input {
            width: 60px !important;
            height: 40px;
            padding: 0;
            border: 2px solid var(--input-border);
            cursor: pointer;
        }
        
        .category-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .category-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: var(--attachment-bg);
            border: 2px solid var(--attachment-border);
            border-radius: 8px;
        }
        
        .category-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }
        
        .category-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid var(--border-color);
        }
        
        .category-details h3 {
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .category-details p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .category-stats {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-right: 1rem;
        }
        
        .category-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--attachment-bg);
            padding: 1.5rem;
            border-radius: 8px;
            border: 2px solid var(--attachment-border);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .category-item {
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
            <h1>📁 Manage Categories</h1>
            <nav class="nav">
                <a href="dashboard.php" class="btn btn-secondary">🏠 Dashboard</a>
                <a href="logout.php" class="btn btn-secondary">🚪 Logout</a>
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($categories); ?></div>
                <div class="stat-label">Total Categories</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo array_sum(array_column($category_stats, 'note_count')); ?></div>
                <div class="stat-label">Total Notes</div>
            </div>
        </div>

        <div class="main-grid">
            <!-- Create Category Form -->
            <div class="section">
                <h2>🆕 Create New Category</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="name">Category Name</label>
                        <input type="text" name="name" id="name" required maxlength="100" placeholder="Enter category name">
                    </div>
                    
                    <div class="form-group">
                        <label for="color">Color</label>
                        <input type="color" name="color" id="color" value="#3498db" class="color-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description (Optional)</label>
                        <textarea name="description" id="description" rows="3" placeholder="Brief description of this category"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">✨ Create Category</button>
                </form>
            </div>

            <!-- Category List -->
            <div class="section">
                <h2>📋 Your Categories</h2>
                <?php if (empty($category_stats)): ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">
                        No categories yet. Create your first category to organize your notes!
                    </p>
                <?php else: ?>
                    <div class="category-list">
                        <?php foreach ($category_stats as $stat): ?>
                            <div class="category-item">
                                <div class="category-info">
                                    <div class="category-color" style="background-color: <?php echo htmlspecialchars($stat['color']); ?>"></div>
                                    <div class="category-details">
                                        <h3><?php echo htmlspecialchars($stat['name']); ?></h3>
                                        <p><?php echo $stat['note_count']; ?> notes</p>
                                    </div>
                                </div>
                                <div class="category-stats">
                                    <a href="dashboard.php?category=<?php echo $stat['id']; ?>" class="btn btn-small btn-secondary">
                                        👁️ View Notes
                                    </a>
                                </div>
                                <?php if ($stat['id'] !== 'uncategorized'): ?>
                                <div class="category-actions">
                                    <button onclick="editCategory(<?php echo $stat['id']; ?>)" class="btn btn-small btn-secondary">
                                        ✏️ Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure? This will uncategorize all notes in this category.')">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="category_id" value="<?php echo $stat['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-danger">🗑️ Delete</button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Theme System -->
    <script src="assets/js/theme-manager.js"></script>
    <script>
        // Initialize theme manager
        const themeManager = new ThemeManager();
        
        // Edit category function (simplified for now)
        function editCategory(categoryId) {
            // For now, we'll use a simple prompt - you can enhance this with a modal later
            const name = prompt('Enter new category name:');
            if (name && name.trim()) {
                const color = prompt('Enter new color (hex code):', '#3498db');
                const description = prompt('Enter new description (optional):');
                
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="category_id" value="${categoryId}">
                    <input type="hidden" name="name" value="${name.trim()}">
                    <input type="hidden" name="color" value="${color}">
                    <input type="hidden" name="description" value="${description || ''}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>