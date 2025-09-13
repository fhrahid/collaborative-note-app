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
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .developers-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 40px;
        }
        
        .hero-section h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .hero-section p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }
        
        .developer-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 2px solid transparent;
        }
        
        .developer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .developer-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3em;
            color: white;
            font-weight: bold;
        }
        
        .developer-photo img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .developer-name {
            font-size: 1.4em;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .student-id {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 15px;
            padding: 5px 10px;
            background: #f8f9fa;
            border-radius: 20px;
            display: inline-block;
        }
        
        .contribution {
            color: #555;
            font-style: italic;
            line-height: 1.5;
        }
        
        .project-info {
            background: #f8f9fa;
            padding: 40px;
            border-radius: 10px;
            margin: 40px 0;
        }
        
        .project-info h3 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .tech-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }
        
        .tech-tag {
            background: #667eea;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9em;
        }
        
        .back-link {
            display: inline-block;
            margin: 20px 0;
            padding: 12px 25px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        
        .back-link:hover {
            background: #5a6fd8;
        }
        
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2em;
            }
            
            .team-grid {
                grid-template-columns: 1fr;
            }
            
            .developer-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="developers-container">
        <div class="hero-section">
            <h1>🚀 Development Team</h1>
            <p>Meet the talented developers behind the Collaborative Note App</p>
        </div>
        
        <a href="index.php" class="back-link">← Back to App</a>
        
        <div class="team-grid">
            <div class="developer-card">
                <div class="developer-photo">
                    <?php 
                    $photo_found = false;
                    $photo_extensions = ['jpg', 'jpeg', 'png'];
                    foreach($photo_extensions as $ext) {
                        if (file_exists("team/jubair_moaj.$ext")) {
                            echo "<img src='team/jubair_moaj.$ext' alt='Jubair Moaj'>";
                            $photo_found = true;
                            break;
                        }
                    }
                    if (!$photo_found) echo "JM";
                    ?>
                </div>
                <div class="developer-name">Jubair Moaj</div>
                <div class="student-id">ID: 2022100000010</div>
                <div class="contribution">Computer Science Student passionate about database systems and web development.</div>
            </div>
            
            <div class="developer-card">
                <div class="developer-photo">
                    <?php 
                    $photo_found = false;
                    $photo_extensions = ['jpg', 'jpeg', 'png'];
                    foreach($photo_extensions as $ext) {
                        if (file_exists("team/sayeed_joy.$ext")) {
                            echo "<img src='team/sayeed_joy.$ext' alt='Md. Sayeed Al Mahmud Joy'>";
                            $photo_found = true;
                            break;
                        }
                    }
                    if (!$photo_found) echo "SJ";
                    ?>
                </div>
                <div class="developer-name">Md. Sayeed Al Mahmud Joy</div>
                <div class="student-id">ID: 2022100000088</div>
                <div class="contribution">Focused on backend development and creating secure, scalable applications.</div>
            </div>
            
            <div class="developer-card">
                <div class="developer-photo">
                    <?php 
                    $photo_found = false;
                    $photo_extensions = ['jpg', 'jpeg', 'png'];
                    foreach($photo_extensions as $ext) {
                        if (file_exists("team/ferdous_rahid.$ext")) {
                            echo "<img src='team/ferdous_rahid.$ext' alt='MD. Ferdous Hasan Rahid'>";
                            $photo_found = true;
                            break;
                        }
                    }
                    if (!$photo_found) echo "FR";
                    ?>
                </div>
                <div class="developer-name">MD. Ferdous Hasan Rahid</div>
                <div class="student-id">ID: 2023100000546</div>
                <div class="contribution">rahid</div>
            </div>
            
            <div class="developer-card">
                <div class="developer-photo">
                    <?php 
                    $photo_found = false;
                    $photo_extensions = ['jpg', 'jpeg', 'png'];
                    foreach($photo_extensions as $ext) {
                        if (file_exists("team/abed_hossain.$ext")) {
                            echo "<img src='team/abed_hossain.$ext' alt='Abed Hossain'>";
                            $photo_found = true;
                            break;
                        }
                    }
                    if (!$photo_found) echo "AH";
                    ?>
                </div>
                <div class="developer-name">Abed Hossain</div>
                <div class="student-id">ID: 2023100000180</div>
                <div class="contribution">Creative flutter developer specializing in mobile app development.</div>
            </div>
            
            <div class="developer-card">
                <div class="developer-photo">
                    <?php 
                    $photo_found = false;
                    $photo_extensions = ['jpg', 'jpeg', 'png'];
                    foreach($photo_extensions as $ext) {
                        if (file_exists("team/suchana_esha.$ext")) {
                            echo "<img src='team/suchana_esha.$ext' alt='Suchana Jaman Esha'>";
                            $photo_found = true;
                            break;
                        }
                    }
                    if (!$photo_found) echo "SE";
                    ?>
                </div>
                <div class="developer-name">Suchana Jaman Esha</div>
                <div class="student-id">ID: 2023100000146</div>
                <div class="contribution">Detail-oriented developer ensuring code quality and comprehensive application testing.</div>
            </div>
        </div>
        
        <div class="project-info">
            <h3>📚 About This Project</h3>
            <p>The Collaborative Note App is an advanced database management project that demonstrates:</p>
            <ul>
                <li><strong>Complex Database Design</strong> - Multi-table relationships with proper normalization</li>
                <li><strong>Advanced MySQL Features</strong> - Foreign keys, indexes, transactions, and joins</li>
                <li><strong>Security Implementation</strong> - CSRF protection, SQL injection prevention, secure authentication</li>
                <li><strong>Real-world Application</strong> - Full-featured note sharing and collaboration system</li>
                <li><strong>Production Deployment</strong> - Ready for cPanel hosting with custom domain support</li>
            </ul>
            
            <h3>🛠️ Technology Stack</h3>
            <div class="tech-stack">
                <span class="tech-tag">PHP 8.2+</span>
                <span class="tech-tag">MySQL 8.0+</span>
                <span class="tech-tag">HTML5</span>
                <span class="tech-tag">CSS3</span>
                <span class="tech-tag">JavaScript</span>
                <span class="tech-tag">PDO</span>
                <span class="tech-tag">Security Best Practices</span>
            </div>
            
            <h3>🎯 Academic Achievement</h3>
            <p>This project showcases advanced database concepts including:</p>
            <ul>
                <li>Entity-Relationship modeling and normalization</li>
                <li>Complex JOIN operations across multiple tables</li>
                <li>Transaction management and data integrity</li>
                <li>Performance optimization with proper indexing</li>
                <li>Real-world application development</li>
            </ul>
        </div>
        
        <div style="text-align: center; margin: 40px 0;">
            <a href="index.php" class="back-link">🏠 Return to Collaborative Note App</a>
        </div>
    </div>
</body>
</html>