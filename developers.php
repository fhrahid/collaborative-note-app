<?php
require_once 'config/config_local.php';

$page_title = 'About the Developers';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Collaborative Note App</title>
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
        
        .developers-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .hero-section {
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            color: var(--text-primary);
            padding: 60px 40px;
            text-align: center;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient-primary);
        }
        
        .hero-section h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-section p {
            font-size: 1.3rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }
        
        .developer-card {
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
            transition: var(--theme-transition);
            position: relative;
            overflow: hidden;
        }
        
        .developer-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        .developer-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-medium);
        }
        
        .developer-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            border: 4px solid var(--border-focus);
            object-fit: cover;
            transition: var(--theme-transition);
        }
        
        .developer-card:hover .developer-photo {
            transform: scale(1.05);
            border-color: var(--gradient-primary);
        }
        
        .developer-card h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--text-primary);
            font-weight: 700;
        }
        
        .developer-card p {
            color: var(--text-secondary);
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .skills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .skill-tag {
            background: var(--attachment-bg);
            color: var(--text-secondary);
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid var(--attachment-border);
            transition: var(--theme-transition);
        }
        
        .skill-tag:hover {
            background: var(--border-focus);
            color: white;
        }
        
        .developer-links {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient-primary);
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            transition: var(--theme-transition);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .social-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            padding: 0.75rem 1.5rem;
            background: var(--gradient-gray);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            box-shadow: var(--shadow-light);
            transition: var(--theme-transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-button:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }
        
        .project-info {
            background: var(--card-bg);
            backdrop-filter: var(--blur-backdrop);
            border-radius: 20px;
            padding: 30px;
            margin-top: 40px;
            text-align: center;
            box-shadow: var(--shadow-light);
            border: 1px solid var(--card-border);
        }
        
        .project-info h2 {
            color: var(--text-primary);
            margin-bottom: 15px;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .project-info p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        .tech-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .tech-item {
            background: var(--gradient-blue);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            transition: var(--theme-transition);
        }
        
        .tech-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }
        
        @media (max-width: 768px) {
            .developers-container {
                padding: 10px;
            }
            
            .hero-section {
                padding: 40px 20px;
            }
            
            .hero-section h1 {
                font-size: 2.2rem;
            }
            
            .hero-section p {
                font-size: 1.1rem;
            }
            
            .team-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .developer-card {
                padding: 20px;
            }
            
            .back-button {
                position: static;
                margin-bottom: 20px;
                display: inline-flex;
            }
            
            .developer-links {
                gap: 10px;
            }
        }
    </style>
</head>
<body>

    </style>
</head>
<body>
    <a href="dashboard.php" class="back-button">
        ← Back to Dashboard
    </a>

    <div class="developers-container">
        <section class="hero-section">
            <h1>Meet Our Team</h1>
            <p>The passionate developers behind this collaborative note-taking experience</p>
        </section>

        <div class="team-grid">
            <div class="developer-card">
                <div class="developer-photo">
                    <?php 
                    $photo_found = false;
                    $photo_extensions = ['jpg', 'jpeg', 'png'];
                    foreach($photo_extensions as $ext) {
                        if (file_exists("team/jubair_moaj.$ext")) {
                            echo "<img src='team/jubair_moaj.$ext' alt='Jubair Moaj' class='developer-photo'>";
                            $photo_found = true;
                            break;
                        }
                    }
                    if (!$photo_found) echo "<div style='width:120px;height:120px;border-radius:50%;background:var(--gradient-primary);display:flex;align-items:center;justify-content:center;color:white;font-size:2rem;font-weight:bold;'>JM</div>";
                    ?>
                </div>
                <h3>Jubair Moaj</h3>
                <div class="student-id">ID: 2022100000010</div>
                <p>Computer Science Student passionate about database systems and web development.</p>
                <div class="skills">
                    <span class="skill-tag">PHP</span>
                    <span class="skill-tag">MySQL</span>
                    <span class="skill-tag">JavaScript</span>
                    <span class="skill-tag">Database Design</span>
                </div>
                <div class="developer-links">
                    <a href="#" class="social-link" title="GitHub">
                        <i class="fab fa-github"></i>
                    </a>
                    <a href="#" class="social-link" title="LinkedIn">
                        <i class="fab fa-linkedin"></i>
                    </a>
                </div>
            </div>

            <div class="developer-card">
                <div class="developer-photo">
                    <?php 
                    $photo_found = false;
                    $photo_extensions = ['jpg', 'jpeg', 'png'];
                    foreach($photo_extensions as $ext) {
                        if (file_exists("team/sayeed_joy.$ext")) {
                            echo "<img src='team/sayeed_joy.$ext' alt='Md. Sayeed Al Mahmud Joy' class='developer-photo'>";
                            $photo_found = true;
                            break;
                        }
                    }
                    if (!$photo_found) echo "<div style='width:120px;height:120px;border-radius:50%;background:var(--gradient-blue);display:flex;align-items:center;justify-content:center;color:white;font-size:2rem;font-weight:bold;'>SJ</div>";
                    ?>
                </div>
                <h3>Md. Sayeed Al Mahmud Joy</h3>
                <div class="student-id">ID: 2022100000088</div>
                <p>Computer Science Student focused on backend development and creating secure, scalable applications.</p>
                <div class="skills">
                    <span class="skill-tag">Backend</span>
                    <span class="skill-tag">Security</span>
                    <span class="skill-tag">API Design</span>
                    <span class="skill-tag">Performance</span>
                </div>
                <div class="developer-links">
                    <a href="#" class="social-link" title="GitHub">
                        <i class="fab fa-github"></i>
                    </a>
                    <a href="#" class="social-link" title="Email">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>

            <div class="developer-card">
                <div class="developer-photo">
                    <?php 
                    $photo_found = false;
                    $photo_extensions = ['jpg', 'jpeg', 'png'];
                    foreach($photo_extensions as $ext) {
                        if (file_exists("team/ferdous_rahid.$ext")) {
                            echo "<img src='team/ferdous_rahid.$ext' alt='MD. Ferdous Hasan Rahid' class='developer-photo'>";
                            $photo_found = true;
                            break;
                        }
                    }
                    if (!$photo_found) echo "<div style='width:120px;height:120px;border-radius:50%;background:var(--gradient-purple);display:flex;align-items:center;justify-content:center;color:white;font-size:2rem;font-weight:bold;'>FR</div>";
                    ?>
                </div>
                <h3>MD. Ferdous Hasan Rahid</h3>
                <div class="student-id">ID: 2023100000546</div>
                <p>Computer Science Student focused on creating intuitive user interfaces and responsive designs.</p>
                <div class="skills">
                    <span class="skill-tag">Frontend</span>
                    <span class="skill-tag">CSS3</span>
                    <span class="skill-tag">UI Design</span>
                    <span class="skill-tag">Responsive</span>
                </div>
                <div class="developer-links">
                    <a href="#" class="social-link" title="GitHub">
                        <i class="fab fa-github"></i>
                    </a>
                    <a href="#" class="social-link" title="Portfolio">
                        <i class="fas fa-globe"></i>
                    </a>
                </div>
            </div>

            <div class="developer-card">
                <div class="developer-photo">
                    <?php 
                    $photo_found = false;
                    $photo_extensions = ['jpg', 'jpeg', 'png'];
                    foreach($photo_extensions as $ext) {
                        if (file_exists("team/abed_hossain.$ext")) {
                            echo "<img src='team/abed_hossain.$ext' alt='Abed Hossain' class='developer-photo'>";
                            $photo_found = true;
                            break;
                        }
                    }
                    if (!$photo_found) echo "<div style='width:120px;height:120px;border-radius:50%;background:var(--gradient-green);display:flex;align-items:center;justify-content:center;color:white;font-size:2rem;font-weight:bold;'>AH</div>";
                    ?>
                </div>
                <h3>Abed Hossain</h3>
                <div class="student-id">ID: 2023100000180</div>
                <p>Computer Science Student with interest in mobile app development and cross-platform solutions.</p>
                <div class="skills">
                    <span class="skill-tag">Flutter</span>
                    <span class="skill-tag">Mobile Dev</span>
                    <span class="skill-tag">Dart</span>
                    <span class="skill-tag">UI/UX</span>
                </div>
                <div class="developer-links">
                    <a href="#" class="social-link" title="GitHub">
                        <i class="fab fa-github"></i>
                    </a>
                    <a href="#" class="social-link" title="LinkedIn">
                        <i class="fab fa-linkedin"></i>
                    </a>
                </div>
            </div>

            <div class="developer-card">
                <div class="developer-photo">
                    <?php 
                    $photo_found = false;
                    $photo_extensions = ['jpg', 'jpeg', 'png'];
                    foreach($photo_extensions as $ext) {
                        if (file_exists("team/suchana_esha.$ext")) {
                            echo "<img src='team/suchana_esha.$ext' alt='Suchana Jaman Esha' class='developer-photo'>";
                            $photo_found = true;
                            break;
                        }
                    }
                    if (!$photo_found) echo "<div style='width:120px;height:120px;border-radius:50%;background:var(--gradient-pink);display:flex;align-items:center;justify-content:center;color:white;font-size:2rem;font-weight:bold;'>SE</div>";
                    ?>
                </div>
                <h3>Suchana Jaman Esha</h3>
                <div class="student-id">ID: 2023100000146</div>
                <p>Computer Science Student ensuring code quality and comprehensive application testing.</p>
                <div class="skills">
                    <span class="skill-tag">Testing</span>
                    <span class="skill-tag">QA</span>
                    <span class="skill-tag">Debugging</span>
                    <span class="skill-tag">Quality</span>
                </div>
                <div class="developer-links">
                    <a href="#" class="social-link" title="GitHub">
                        <i class="fab fa-github"></i>
                    </a>
                    <a href="#" class="social-link" title="LinkedIn">
                        <i class="fab fa-linkedin"></i>
                    </a>
                </div>
            </div>
        </div>

        <section class="project-info">
            <h2>About This Project</h2>
            <p>
                The Collaborative Note App is an advanced database management project that demonstrates 
                complex database design, real-time collaboration features, and modern web development practices. 
                Built as part of our database course, it showcases our skills in full-stack development and 
                database optimization.
            </p>
            <div class="tech-stack">
                <span class="tech-item">PHP 8.2+</span>
                <span class="tech-item">MySQL 8.0+</span>
                <span class="tech-item">JavaScript ES6+</span>
                <span class="tech-item">CSS3</span>
                <span class="tech-item">HTML5</span>
                <span class="tech-item">PDO</span>
                <span class="tech-item">Security Best Practices</span>
                <span class="tech-item">Responsive Design</span>
            </div>
        </section>
    </div>

    <style>
        .student-id {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 15px;
            padding: 0.4rem 0.8rem;
            background: var(--attachment-bg);
            border-radius: 15px;
            display: inline-block;
            border: 1px solid var(--attachment-border);
            font-weight: 600;
        }
    </style>

    <!-- Theme System -->
    <script src="assets/js/theme-manager.js"></script>
    <script>
        // Initialize theme manager
        const themeManager = new ThemeManager();
    </script>
</body>
</html>