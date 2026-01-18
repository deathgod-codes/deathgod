# Realtime Chat Application

A comprehensive, feature-rich PHP-based chat application with enhanced security, real-time messaging, and modern features.

## 🔒 Security Features (Phase 1 - Implemented)

### Enhanced Security
- ✅ **bcrypt Password Hashing**: Replaced MD5 with secure bcrypt (password_hash/password_verify)
- ✅ **Prepared Statements**: All SQL queries use prepared statements to prevent SQL injection
- ✅ **CSRF Protection**: CSRF tokens implemented on all forms
- ✅ **XSS Protection**: All user input is sanitized with htmlspecialchars
- ✅ **Rate Limiting**: Login rate limiting (5 attempts, 15-minute lockout)
- ✅ **Password Strength Validation**: Enforces strong passwords (8+ chars, uppercase, lowercase, number, special character)
- ✅ **Session Management**: Session timeout after 30 minutes of inactivity
- ✅ **Secure Headers**: Content-Security-Policy, X-Frame-Options, X-XSS-Protection, etc.

## 📋 Requirements

- **PHP**: 7.4+ (8.0+ recommended)
- **MySQL**: 5.7+ or MariaDB 10.2+
- **Web Server**: Apache or Nginx
- **Extensions**: mysqli, session

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/deathgod-codes/deathgod.git
cd deathgod
```

### 2. Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE chatapp;
```

2. Import the base schema:
```bash
mysql -u root -p chatapp < chatapp.sql
```

3. Run the security migration:
```bash
mysql -u root -p chatapp < migrations/001_security_enhancements.sql
```

### 3. Configure Database Connection

Edit `php/config.php` with your database credentials:

```php
$hostname = "localhost";
$username = "your_username";
$password = "your_password";
$dbname = "chatapp";
```

### 4. Set Up File Permissions

Create and set permissions for the images directory:

```bash
mkdir -p php/images
chmod 755 php/images
```

### 5. Migrate Existing Users (If Applicable)

If you have existing users with MD5 passwords, they will need to reset their passwords. The new system uses bcrypt hashing for enhanced security.

**Option 1: Reset All Passwords**
- Users will need to create new accounts

**Option 2: Migration Script**
You can create a migration tool that:
1. Asks users to verify their old password
2. Re-hashes it with bcrypt
3. Updates the database

## 🔐 Security Best Practices

### Password Requirements
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one special character

### Rate Limiting
- Maximum 5 failed login attempts
- Account locked for 15 minutes after 5 failures
- Automatic unlock after timeout

### Session Security
- Sessions expire after 30 minutes of inactivity
- Secure session cookies
- CSRF tokens on all forms

## 📁 Project Structure

```
/
├── php/
│   ├── config.php          # Database configuration
│   ├── Security.php        # Security class (CSRF, XSS, rate limiting)
│   ├── login.php           # Login handler
│   ├── signup.php          # Signup handler
│   ├── logout.php          # Logout handler
│   ├── insert-chat.php     # Send message handler
│   ├── get-chat.php        # Retrieve messages handler
│   ├── users.php           # List users handler
│   ├── search.php          # Search users handler
│   ├── data.php            # User data helper
│   └── images/             # User profile images
├── javascript/
│   ├── chat.js             # Chat functionality
│   ├── users.js            # Users list functionality
│   ├── login.js            # Login form handling
│   ├── signup.js           # Signup form handling
│   └── pass-show-hide.js   # Password visibility toggle
├── migrations/
│   └── 001_security_enhancements.sql  # Database migration
├── index.php               # Signup page
├── login.php               # Login page
├── users.php               # Users list page
├── chat.php                # Chat interface
├── style.css               # Styles
├── chatapp.sql             # Base database schema
└── README.md               # This file
```

## 🎯 Usage

### Sign Up
1. Navigate to `index.php`
2. Fill in your details (first name, last name, email, password)
3. Upload a profile image
4. Password must meet strength requirements
5. Click "Continue to Chat"

### Login
1. Navigate to `login.php`
2. Enter your email and password
3. Maximum 5 failed attempts before account lockout
4. Click "Continue to Chat"

### Chatting
1. After login, you'll see a list of available users
2. Click on a user to start chatting
3. Type your message and send
4. Messages are sanitized for security

## 🔧 Configuration

### Adjusting Session Timeout

Edit `php/Security.php`, line 106:

```php
// Change 1800 (30 minutes) to your desired timeout in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
```

### Adjusting Rate Limiting

Edit `php/Security.php`, line 75:

```php
// Change 5 to your desired max attempts
if ($row['failed_login_attempts'] >= 5) {
    // Change 900 (15 minutes) to your desired lockout in seconds
    $lockUntil = date('Y-m-d H:i:s', time() + 900);
```

### Customizing Secure Headers

Edit `php/Security.php`, method `setSecureHeaders()` to customize Content-Security-Policy and other headers.

## 🐛 Troubleshooting

### "Invalid security token" Error
- Clear your browser cache and cookies
- Ensure sessions are enabled in your PHP configuration
- Check that session data is being stored correctly

### "Account is temporarily locked" Error
- Wait 15 minutes after 5 failed login attempts
- Check `account_locked_until` in the database if needed
- Manually reset: `UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE email = 'user@example.com'`

### Images Not Displaying
- Ensure `php/images/` directory exists and is writable
- Check file permissions: `chmod 755 php/images`
- Verify uploaded images are in the correct location

### Database Connection Error
- Verify database credentials in `php/config.php`
- Ensure MySQL service is running
- Check that the database exists: `SHOW DATABASES;`

## 🔄 Upgrading from Previous Version

If you're upgrading from a version that used MD5 passwords:

1. **Backup your database** before proceeding
2. Run the migration script:
   ```bash
   mysql -u root -p chatapp < migrations/001_security_enhancements.sql
   ```
3. **Important**: Existing users will need to:
   - Create a new account with the new password requirements, OR
   - Use a password migration script (not included) to re-hash their passwords

### Manual Password Migration

You can manually update a user's password:

```php
<?php
include_once "php/config.php";

$email = "user@example.com";
$new_password = "NewSecurePass123!";
$hashed = password_hash($new_password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashed, $email);
$stmt->execute();
echo "Password updated successfully";
?>
```

## 📊 Database Schema

### Users Table
- `user_id`: Primary key
- `unique_id`: Unique user identifier
- `fname`: First name
- `lname`: Last name
- `email`: Email address (indexed)
- `password`: Bcrypt hashed password
- `img`: Profile image filename
- `status`: Online/offline status
- `created_at`: Account creation timestamp
- `last_seen`: Last activity timestamp
- `failed_login_attempts`: Failed login counter
- `account_locked_until`: Account lock expiration
- Additional fields for future features

### Messages Table
- `msg_id`: Primary key
- `incoming_msg_id`: Recipient user ID
- `outgoing_msg_id`: Sender user ID
- `msg`: Message content (XSS protected)
- `created_at`: Message timestamp
- Additional fields for future features (editing, deletion, file attachments, etc.)

## 🚀 Roadmap

### Phase 2: Enhanced Messaging Features (Planned)
- Message timestamps display
- Message editing
- Message deletion
- Read receipts
- Typing indicators
- Message reactions
- Reply to messages

### Phase 3: Real-Time Communication (Planned)
- WebSocket implementation
- Real-time message delivery
- Real-time online/offline status
- No more AJAX polling

### Phase 4: Advanced Features (Planned)
- Group chats
- File sharing (images, videos, documents)
- Voice and video calls (WebRTC)
- Emoji picker
- Dark mode/themes
- Mobile application (React Native)

## 📝 License

This project is open source and available under the MIT License.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📞 Support

For issues, questions, or suggestions, please open an issue on GitHub.

## ⚠️ Important Security Notes

1. **HTTPS Required**: Use HTTPS in production to protect data in transit
2. **Environment Variables**: Store sensitive data (DB credentials) in environment variables
3. **Regular Updates**: Keep PHP and MySQL updated
4. **Backup Regularly**: Implement regular database backups
5. **Monitor Logs**: Check logs for suspicious activity
6. **File Upload Security**: Validate and scan uploaded files
7. **CSP Policy**: Adjust Content-Security-Policy as needed for your domain

## 🎨 Customization

### Changing the App Name
Edit the header in `login.php`, `index.php`, and other pages:
```html
<header>Your App Name</header>
```

### Styling
Edit `style.css` to customize colors, fonts, and layout.

### Adding Features
Follow the existing code patterns:
- Use prepared statements for all database queries
- Sanitize all output with `$security->sanitizeOutput()`
- Validate all input
- Include CSRF tokens on forms
- Maintain session security

---

**Version**: 1.0.0 (Security Enhanced)  
**Last Updated**: January 2026
