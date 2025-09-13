# Production Configuration Instructions

## 1. Database Setup

### Update database.php
Replace the placeholders in `config/database.php` with your actual cPanel database credentials:

```php
// Production MySQL configuration
define('DB_HOST', 'localhost');  // Usually 'localhost' for cPanel
define('DB_NAME', 'your_cpanel_username_dbname');  // Your actual database name
define('DB_USER', 'your_cpanel_username_dbuser');  // Your database username  
define('DB_PASS', 'your_database_password');       // Your database password
```

### Import Database Schema
1. Go to your cPanel → phpMyAdmin
2. Select your database
3. Click "Import" tab
4. Upload the file: `database/mysql_schema.sql`
5. Click "Go" to execute

## 2. File Upload Directory
Create a `uploads` directory in your web root and set permissions to 755:
```bash
mkdir uploads
chmod 755 uploads
```

## 3. Domain Configuration
The app now automatically detects your domain and generates correct URLs for:
- Share links
- File downloads
- Navigation

## 4. Environment Detection
The app automatically switches between:
- **Local**: SQLite database (for development)
- **Production**: MySQL database (when deployed)

Detection is based on the domain name - anything other than localhost uses MySQL.

## 5. Security Considerations
- Change the default admin credentials after first login
- Ensure your database credentials are secure
- Set proper file permissions on uploaded files
- Consider using HTTPS for production

## 6. Testing
After deployment:
1. Register a new user
2. Create a note
3. Upload an attachment
4. Share a note and verify the public link uses your domain
5. Check the profile page for statistics

## Common cPanel Database Names Format:
- Database: `username_dbname`
- User: `username_dbuser`
- Replace `username` with your cPanel username