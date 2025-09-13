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

## 👨‍💻 Author

**fhrahid** - [GitHub Profile](https://github.com/fhrahid)

---

**Ready for production deployment and database course demonstrations!** 🎉
