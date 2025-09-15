# Collaborative Note App

A full-featured multi-user collaborative note-taking application built with PHP and MySQL. Perfect for team collaboration, personal note management, and sharing knowledge with public links.

## 🚀 Features

### Core Functionality
- **Multi-user Support** - Individual user accounts with secure authentication
- **Rich Note Management** - Create, edit, delete, and organize notes with timestamps
- **File Attachments** - Upload and manage files with notes (images, documents, etc.)
- **Responsive Design** - Works seamlessly on desktop and mobile devices

### Advanced Sharing System
- **User-to-User Sharing** - Share notes with specific users with read/write permissions
- **Collaborator System** - Add team members as collaborators with different access levels
- **Public Link Sharing** - Generate public links for notes with token-based security
- **Custom URLs** - Create memorable custom URLs like `yourdomain.com/my-note`
- **Permission Management** - Fine-grained control over who can view and edit notes

### Database Features
- **MySQL Backend** - Production-ready database with proper indexing and foreign keys
- **ACID Compliance** - Reliable data integrity with transaction support
- **Optimized Queries** - Efficient database operations with proper indexing
- **Schema Validation** - Built-in database verification and migration tools

## 🛠️ Technical Stack

- **Backend**: PHP 8.2+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Security**: CSRF protection, SQL injection prevention, XSS protection
- **Deployment**: XAMPP (local), cPanel (production)

## 📋 Requirements

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- PDO MySQL extension
- GD extension (for file handling)

## 🚀 Installation

### Local Development (XAMPP)

1. **Clone the repository**
   ```bash
   git clone https://github.com/fhrahid/collaborative-note-app.git
   cd collaborative-note-app
   ```

2. **Configure XAMPP**
   - Start Apache and MySQL in XAMPP Control Panel
   - Create a database named `note_app` in phpMyAdmin

3. **Database Setup**
   ```bash
   # Import the database schema
   mysql -u root -p note_app < database/mysql_schema.sql
   ```

4. **Configuration**
   - Update database credentials in `config/database.php` if needed
   - Default XAMPP settings should work out of the box

5. **Access the Application**
   - Open: `http://localhost/collaborative-note-app`
   - Register a new account or use the demo admin account

### Production Deployment (cPanel)

1. **Upload Files**
   - Upload all files to your web hosting public_html directory
   - Ensure `uploads/` directory has write permissions (755)

2. **Database Setup**
   - Create a MySQL database through cPanel
   - Import `database/mysql_schema.sql` via phpMyAdmin
   - Update database credentials in `config/database.php`

3. **Configuration**
   - The app automatically detects the production environment
   - URLs are dynamically generated based on your domain

## 🌐 Complete cPanel Deployment Tutorial

### Prerequisites
- cPanel hosting account with PHP 8.2+ and MySQL support
- Custom domain name (e.g., yourdomain.com)
- FTP client or cPanel File Manager access

### Step 1: Domain Setup

#### Option A: Main Domain Deployment
1. **Point Domain to Hosting**
   - Update your domain's nameservers to your hosting provider
   - Wait 24-48 hours for DNS propagation

2. **Verify Domain in cPanel**
   - Login to cPanel
   - Go to "Subdomains" or "Addon Domains" 
   - Ensure your domain points to `public_html/`

#### Option B: Subdomain Deployment
1. **Create Subdomain in cPanel**
   ```
   Subdomain: notes
   Domain: yourdomain.com
   Document Root: public_html/notes
   ```

2. **Result**: App will be accessible at `https://notes.yourdomain.com`

### Step 2: File Upload

#### Method A: cPanel File Manager (Recommended)
1. **Access File Manager**
   - Login to cPanel
   - Open "File Manager"
   - Navigate to `public_html/` (or `public_html/notes/` for subdomain)

2. **Upload Project Files**
   ```
   1. Download project as ZIP from GitHub
   2. Upload ZIP file to cPanel File Manager
   3. Extract in the correct directory
   4. Delete the ZIP file after extraction
   ```

#### Method B: FTP Upload
1. **FTP Connection Details**
   ```
   Host: ftp.yourdomain.com (or your hosting FTP server)
   Username: your_cpanel_username
   Password: your_cpanel_password
   Port: 21 (or 22 for SFTP)
   ```

2. **Upload Structure**
   ```
   public_html/
   ├── config/
   ├── database/
   ├── includes/
   ├── uploads/          ← Create this directory
   ├── index.php
   ├── login.php
   └── ... (all other files)
   ```

### Step 3: Database Configuration

#### Create MySQL Database
1. **Access MySQL Databases in cPanel**
   - Find "MySQL Databases" in cPanel
   - Create a new database: `youruser_noteapp`

2. **Create Database User**
   ```
   Username: youruser_noteapp
   Password: (generate strong password)
   ```

3. **Assign User to Database**
   - Select the user and database
   - Grant "ALL PRIVILEGES"

#### Import Database Schema
1. **Access phpMyAdmin**
   - Find "phpMyAdmin" in cPanel
   - Select your database (`youruser_noteapp`)

2. **Import Schema**
   ```
   1. Click "Import" tab
   2. Choose file: database/mysql_schema.sql
   3. Click "Go" to import
   4. Verify all tables are created
   ```

### Step 4: Configure Database Connection

#### Update config/database.php
```php
<?php
// Production Database Configuration
$host = 'localhost';  // Usually localhost for cPanel
$dbname = 'youruser_noteapp';  // Your actual database name
$username = 'youruser_noteapp';  // Your database username
$password = 'your_secure_password';  // Your database password

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
```

### Step 5: Set Directory Permissions

#### Required Permissions
```bash
# Via cPanel File Manager or FTP client
uploads/           755 (rwxr-xr-x)
config/            755 (rwxr-xr-x)
database/          755 (rwxr-xr-x)
All PHP files      644 (rw-r--r--)
```

#### Setting Permissions in cPanel
1. **File Manager Method**
   - Right-click on `uploads/` directory
   - Select "Change Permissions"
   - Set to 755 (or check: Owner read/write/execute, Group read/execute, World read/execute)

### Step 6: SSL Certificate Setup

#### Enable HTTPS (Recommended)
1. **Free SSL via cPanel**
   - Go to "SSL/TLS" in cPanel
   - Select "Let's Encrypt SSL"
   - Install certificate for your domain

2. **Force HTTPS Redirect**
   - Create/edit `.htaccess` in public_html:
   ```apache
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

### Step 7: Testing Your Deployment

#### Verification Checklist
1. **Basic Access Test**
   ```
   ✅ Visit: https://yourdomain.com (or subdomain)
   ✅ Should see login/register page
   ✅ No PHP errors displayed
   ```

2. **Database Connection Test**
   ```
   ✅ Try to register a new account
   ✅ Login with the account
   ✅ Create a test note
   ```

3. **File Upload Test**
   ```
   ✅ Create a note with file attachment
   ✅ Verify file uploads to uploads/ directory
   ✅ Download the attachment
   ```

4. **Sharing Features Test**
   ```
   ✅ Share a note with another user
   ✅ Generate a public link
   ✅ Test collaborative editing
   ✅ Create custom URL
   ```

### Step 8: Custom URL Feature

The application now supports custom URLs for public sharing, allowing you to create memorable links like `yourdomain.com/my-note` instead of random tokens.

#### How to Use Custom URLs:

1. **Creating a New Public Link with Custom URL**
   - Go to Share settings for any note
   - Under "Public Link Sharing", enter your desired custom URL
   - URL must be 3-20 characters: letters, numbers, hyphens, underscores only
   - Click "Generate Public Link"

2. **Updating Existing Public Links**
   - For notes that already have public links
   - Use the "Customize Your URL" section
   - Enter new custom URL and click "Update"

3. **URL Requirements**
   ```
   ✅ Length: 3-20 characters
   ✅ Allowed: letters (a-z, A-Z)
   ✅ Allowed: numbers (0-9)  
   ✅ Allowed: hyphens (-) and underscores (_)
   ❌ Spaces, special characters, or symbols
   ```

4. **Examples of Good Custom URLs**
   ```
   ✅ meeting-notes
   ✅ project_2024
   ✅ team-guidelines
   ✅ MyReport
   ```

#### Technical Implementation:
- Custom URLs are validated for uniqueness
- Short links work via `s.php` handler
- Seamless fallback to public view for valid tokens
- Database enforced unique constraints

### Step 9: Production Optimization

#### Performance Settings
1. **PHP Configuration** (if accessible)
   ```php
   ; php.ini or .htaccess
   upload_max_filesize = 10M
   post_max_size = 10M
   max_execution_time = 300
   memory_limit = 128M
   ```

2. **MySQL Optimization**
   ```sql
   -- Run these in phpMyAdmin for better performance
   OPTIMIZE TABLE notes;
   OPTIMIZE TABLE shared_notes;
   OPTIMIZE TABLE attachments;
   ```

#### Security Hardening
1. **Hide Sensitive Files**
   ```apache
   # Add to .htaccess
   <Files "config/database.php">
       Order Allow,Deny
       Deny from all
   </Files>
   
   <Files "*.sql">
       Order Allow,Deny
       Deny from all
   </Files>
   ```

2. **Regular Backups**
   - Use cPanel backup tools
   - Schedule automatic database backups
   - Keep backups of uploads/ directory

### Step 9: Domain-Specific Configuration

#### Custom Domain Features
1. **Automatic URL Generation**
   - The app automatically detects your domain
   - Sharing links use your custom domain
   - Email notifications (if implemented) use your domain

2. **Branding Opportunities**
   - Update app title in templates
   - Add your logo/favicon
   - Customize CSS for your brand

### Troubleshooting Common Issues

#### Database Connection Errors
```
Error: "Database connection failed"
Solution: 
1. Verify database credentials in config/database.php
2. Check if database user has correct privileges
3. Confirm database name format (usually: username_dbname)
```

#### File Upload Issues
```
Error: "Failed to upload file"
Solution:
1. Check uploads/ directory permissions (755)
2. Verify PHP upload limits in hosting settings
3. Ensure uploads/ directory exists and is writable
```

#### SSL/HTTPS Issues
```
Error: "Mixed content warnings"
Solution:
1. Ensure all internal links use relative paths
2. Force HTTPS with .htaccess redirect
3. Update any hardcoded HTTP links
```

#### Permission Denied Errors
```
Error: Various permission errors
Solution:
1. Set correct file permissions (644 for files, 755 for directories)
2. Check if your hosting account has necessary privileges
3. Contact hosting support if issues persist
```

### Step 10: Going Live Checklist

#### Pre-Launch Verification
- [ ] Database connection working
- [ ] User registration/login functional
- [ ] Note creation and editing works
- [ ] File upload/download working
- [ ] Sharing features operational
- [ ] SSL certificate installed
- [ ] Custom domain pointing correctly
- [ ] All sensitive files protected
- [ ] Backup system in place

#### Post-Launch Monitoring
- [ ] Check error logs regularly
- [ ] Monitor database performance
- [ ] Track user registrations
- [ ] Verify sharing links work externally
- [ ] Test from different devices/browsers

### Example: Complete Deployment for "mynotes.example.com"

```bash
# Final deployment structure
https://mynotes.example.com/
├── index.php                 (Main app entry)
├── login.php                 (User authentication)
├── register.php              (User registration)
├── dashboard_local.php       (User dashboard)
├── config/
│   └── database.php          (Database credentials)
├── uploads/                  (File storage - 755 permissions)
└── database/
    └── mysql_schema.sql      (Database structure)

# Database: example_mynotes
# User: example_noteuser
# Tables: users, notes, attachments, shared_notes, collaborators
```

Your collaborative note app is now live at your custom domain with full functionality! 🚀

## 📊 Database Schema

### Core Tables
- **users** - User accounts and authentication
- **notes** - Note content with public sharing support
- **attachments** - File uploads linked to notes
- **shared_notes** - User-to-user sharing relationships
- **collaborators** - Team collaboration management

### Key Features
- Foreign key constraints for data integrity
- Indexes for optimal query performance
- Support for public sharing with secure tokens
- Comprehensive permission system

## 🔧 Database Management

### Schema Verification
```bash
php database/verify_schema.php
```

### Update Database Structure
```bash
# Add missing columns to existing database
php database/update_notes_table.php
php database/update_shared_notes_table.php
```

### Test Functionality
```bash
# Comprehensive functionality test
php database/final_verification.php
```

## 🔐 Security Features

- **Password Hashing** - Secure bcrypt password storage
- **CSRF Protection** - Token-based form protection
- **SQL Injection Prevention** - Prepared statements throughout
- **XSS Protection** - Input sanitization and output escaping
- **File Upload Security** - MIME type validation and secure storage
- **Session Management** - Secure session handling

## 🎯 Use Cases

### Educational
- **Database Course Projects** - Demonstrates advanced MySQL concepts
- **Web Development Learning** - Full-stack PHP application example
- **Security Best Practices** - Real-world security implementation

### Professional
- **Team Documentation** - Collaborative note-taking for teams
- **Knowledge Sharing** - Public links for sharing information
- **Project Management** - Attach files and collaborate on project notes

## 🚀 Deployment Guide

### For Database Course Demo

1. **XAMPP Setup**
   ```bash
   # Start XAMPP services
   # Import database/mysql_schema.sql
   # Access via http://localhost/collaborative-note-app
   ```

2. **Key Features to Demonstrate**
   - Multi-table relationships with foreign keys
   - Complex JOIN queries in sharing system
   - Transaction support for data integrity
   - Indexing for performance optimization
   - CRUD operations across multiple tables

### For Production Use

1. **Performance Optimization**
   - Enable MySQL query cache
   - Configure proper Apache/PHP settings
   - Set up SSL certificate for HTTPS

2. **Backup Strategy**
   - Regular database backups
   - File upload directory backups
   - Configuration file versioning

## 📝 API Documentation

### Core Functions
- `can_user_access_note($note_id, $user_id, $db)` - Permission checking
- `generate_share_url($note_id, $share_token)` - Public link generation
- `format_file_size($bytes)` - File size formatting
- `search_users($query, $current_user_id, $db)` - User search

## 🐛 Troubleshooting

### Common Issues
1. **Function Redeclaration Errors** - Fixed in latest version
2. **Database Connection Issues** - Check credentials in `config/database.php`
3. **File Upload Problems** - Verify `uploads/` directory permissions
4. **Sharing Not Working** - Run database verification scripts

### Debug Tools
- `database/verify_schema.php` - Check database structure
- `database/test_sharing_functionality.php` - Test sharing features
- `database/final_verification.php` - Complete system test

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 👨‍💻 Development Team

This collaborative note app was developed by a talented team of computer science students as part of their database course project.

### Core Development Team

<table>
<tr>
<td align="center">
<img src="team/jubair_moaj.jpg" width="150" height="150" style="border-radius: 50%;" alt="Jubair Moaj"/><br>
<b>Jubair Moaj</b><br>
<sub>Student ID: 2022100000010</sub><br>
<sub>Lead Developer & Database Architecture</sub>
</td>
<td align="center">
<img src="team/sayeed_joy.jpg" width="150" height="150" style="border-radius: 50%;" alt="Md. Sayeed Al Mahmud Joy"/><br>
<b>Md. Sayeed Al Mahmud Joy</b><br>
<sub>Student ID: 2022100000088</sub><br>
<sub>Backend Development & Security</sub>
</td>
<td align="center">
<img src="team/ferdous_rahid.jpg" width="150" height="150" style="border-radius: 50%;" alt="MD. Ferdous Hasan Rahid"/><br>
<b>MD. Ferdous Hasan Rahid</b><br>
<sub>Student ID: 2023100000546</sub><br>
<sub>Full-Stack Development & Project Lead</sub>
</td>
</tr>
<tr>
<td align="center">
<img src="team/abed_hossain.jpg" width="150" height="150" style="border-radius: 50%;" alt="Abed Hossain"/><br>
<b>Abed Hossain</b><br>
<sub>Student ID: 2023100000180</sub><br>
<sub>Frontend Development & UI/UX</sub>
</td>
<td align="center">
<img src="team/suchana_esha.jpg" width="150" height="150" style="border-radius: 50%;" alt="Suchana Jaman Esha"/><br>
<b>Suchana Jaman Esha</b><br>
<sub>Student ID: 2023100000146</sub><br>
<sub>Testing & Quality Assurance</sub>
</td>
<td align="center">
<br><br>
<i>Collaborative<br>Team Effort</i><br>
<sub>🤝 Working together to create<br>an innovative note-sharing platform</sub>
</td>
</tr>
</table>

### Team Contributions

- **Database Design**: Complex relational schema with proper normalization and foreign key relationships
- **Security Implementation**: CSRF protection, SQL injection prevention, secure file uploads
- **Sharing System**: Advanced multi-user collaboration with permission management
- **User Experience**: Intuitive interface design and responsive layout
- **Testing & QA**: Comprehensive testing across different scenarios and edge cases
- **Documentation**: Detailed documentation and deployment guides

### Academic Project Details

- **Course**: Database Management Systems
- **Institution**: [Your University Name]
- **Semester**: Fall 2025
- **Project Type**: Advanced Database Application Development
- **Technologies**: PHP 8.2+, MySQL 8.0+, HTML5, CSS3, JavaScript

---

## 👨‍💻 Author & Contact

**Primary Contact**: MD. Ferdous Hasan Rahid  
**GitHub**: [fhrahid](https://github.com/fhrahid)  
**Project Repository**: https://github.com/fhrahid/collaborative-note-app

---

**Ready for production deployment and database course demonstrations!** 🎉
