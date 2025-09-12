# Note App - Multi-User PHP Web Application

A simple, secure multi-user note-taking web application built with PHP and MySQL.

## Features

- **User Authentication**
  - User registration with validation
  - Secure login/logout system
  - Password hashing using PHP's `password_hash()`
  - Session management

- **Note Management**
  - Create, read, update, and delete notes
  - User-specific notes (users can only see their own notes)
  - Rich text content support
  - Timestamps for creation and updates

- **Security Features**
  - CSRF protection on all forms
  - SQL injection prevention using prepared statements
  - Input sanitization and validation
  - Session-based authentication
  - User isolation (users can't access other users' notes)

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB)
- Web server (Apache, Nginx, or PHP built-in server)

### Setup Steps

1. **Clone or download the project files**
   ```
   Place all files in your web server directory (e.g., htdocs, www, public_html)
   ```

2. **Create the database**
   - Create a new MySQL database named `note_app`
   - Import the `database.sql` file to create the required tables:
   ```sql
   mysql -u your_username -p note_app < database.sql
   ```

3. **Configure database connection**
   - Edit `config/database.php`
   - Update the database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'note_app');
   define('DB_USER', 'your_database_username');
   define('DB_PASS', 'your_database_password');
   ```

4. **Set up web server**
   - Ensure your web server is running
   - Make sure PHP is properly configured
   - Point your browser to the application directory

5. **Test the application**
   - Navigate to `http://localhost/note-app` (adjust URL as needed)
   - You should be redirected to the login page
   - Create a new account and start using the app

## File Structure

```
note-app/
├── assets/
│   └── css/
│       └── style.css          # Application styles
├── config/
│   ├── config.php             # Helper functions and utilities
│   └── database.php           # Database connection
├── database.sql               # Database schema
├── dashboard.php              # Main notes dashboard
├── index.php                  # Entry point (redirects to login)
├── login.php                  # User login page
├── logout.php                 # Logout handler
├── note.php                   # Create/edit note page
├── register.php               # User registration page
└── README.md                  # This file
```

## Security Features Implemented

### 1. Authentication & Authorization
- Secure password hashing using `password_hash()` and `password_verify()`
- Session-based authentication
- Login requirement for protected pages
- User isolation (users can only access their own notes)

### 2. Input Validation & Sanitization
- Server-side validation for all form inputs
- HTML sanitization using `htmlspecialchars()`
- Email validation using `filter_var()`
- Password strength requirements

### 3. SQL Injection Prevention
- All database queries use prepared statements with parameter binding
- No direct SQL string concatenation

### 4. CSRF Protection
- CSRF tokens generated and validated on all forms
- Tokens stored in sessions and verified on form submission

### 5. XSS Prevention
- Output escaping with `htmlspecialchars()`
- Content Security Policy headers can be added for additional protection

## Usage

### User Registration
1. Visit the registration page
2. Fill out username, email, and password
3. Passwords must be at least 6 characters long
4. Username must be at least 3 characters long
5. Email must be valid format

### Creating Notes
1. Log in to your account
2. Click "New Note" on the dashboard
3. Enter a title and content
4. Click "Create Note" to save

### Managing Notes
1. View all your notes on the dashboard
2. Click "Edit" to modify a note
3. Click "Delete" to remove a note (with confirmation)
4. Notes are sorted by last update time

## Development Notes

### Database Schema
- `users` table: Stores user account information
- `notes` table: Stores note content with foreign key to users
- Proper indexes for performance
- Foreign key constraints for data integrity

### Session Management
- Sessions store user ID and username
- Session timeout handled by PHP configuration
- Secure session handling with regeneration

### Error Handling
- User-friendly error messages
- Flash message system for feedback
- Form validation with error display

## Customization

### Styling
- Modify `assets/css/style.css` to change the appearance
- Responsive design included for mobile devices
- CSS Grid used for note layout

### Database
- Update `config/database.php` for different database settings
- Modify `database.sql` to add additional fields or tables

### Features
- Add note categories or tags
- Implement note sharing between users
- Add rich text editor for note content
- Implement note search functionality

## Security Recommendations

1. **Production Deployment**
   - Use HTTPS in production
   - Set secure session cookie settings
   - Implement rate limiting for login attempts
   - Add Content Security Policy headers
   - Regular security updates

2. **Database Security**
   - Use a dedicated database user with minimal privileges
   - Enable SQL strict mode
   - Regular database backups
   - Monitor for suspicious activity

3. **Server Configuration**
   - Disable PHP error display in production
   - Set appropriate file permissions
   - Keep PHP and server software updated
   - Use a Web Application Firewall (WAF)

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL server is running
   - Verify database name exists

2. **Session Issues**
   - Check PHP session configuration
   - Ensure session directory is writable
   - Clear browser cookies if needed

3. **Permission Errors**
   - Ensure web server has read access to all files
   - Check file ownership and permissions

4. **Styling Issues**
   - Verify CSS file path is correct
   - Check for CSS syntax errors
   - Clear browser cache

## License

This project is open source and available under the MIT License.