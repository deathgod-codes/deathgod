# Deployment Guide

This guide covers deploying the Realtime Chat Application to production environments.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Server Requirements](#server-requirements)
3. [Installation Steps](#installation-steps)
4. [Security Hardening](#security-hardening)
5. [Performance Optimization](#performance-optimization)
6. [Backup Strategy](#backup-strategy)
7. [Monitoring](#monitoring)
8. [Troubleshooting](#troubleshooting)

---

## Prerequisites

- Linux server (Ubuntu 20.04+ or CentOS 8+ recommended)
- Root or sudo access
- Domain name (optional but recommended)
- SSL certificate (Let's Encrypt recommended)

---

## Server Requirements

### Minimum Requirements
- **CPU**: 2 cores
- **RAM**: 2GB
- **Storage**: 20GB SSD
- **Bandwidth**: 100GB/month

### Recommended Requirements
- **CPU**: 4+ cores
- **RAM**: 4GB+
- **Storage**: 50GB+ SSD
- **Bandwidth**: Unlimited

### Software Requirements
- **PHP**: 7.4+ (8.0+ recommended)
- **MySQL/MariaDB**: 5.7+/10.2+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **SSL**: Valid SSL certificate (Let's Encrypt)

---

## Installation Steps

### 1. Update System Packages

```bash
# Ubuntu/Debian
sudo apt update && sudo apt upgrade -y

# CentOS/RHEL
sudo yum update -y
```

### 2. Install LAMP Stack

#### Ubuntu/Debian:

```bash
# Install Apache
sudo apt install apache2 -y

# Install MySQL
sudo apt install mysql-server -y
sudo mysql_secure_installation

# Install PHP 8.0 and extensions
sudo apt install php8.0 php8.0-cli php8.0-mysql php8.0-curl php8.0-json php8.0-mbstring -y

# Enable Apache modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo systemctl restart apache2
```

#### CentOS/RHEL:

```bash
# Install Apache
sudo yum install httpd -y

# Install MySQL
sudo yum install mysql-server -y
sudo systemctl start mysqld
sudo mysql_secure_installation

# Install PHP 8.0
sudo yum install php php-cli php-mysqlnd php-curl php-json php-mbstring -y

# Start and enable services
sudo systemctl start httpd
sudo systemctl enable httpd
sudo systemctl start mysqld
sudo systemctl enable mysqld
```

### 3. Configure MySQL

```bash
# Login to MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE chatapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'chatapp_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON chatapp.* TO 'chatapp_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Deploy Application Files

```bash
# Clone repository
cd /var/www/
sudo git clone https://github.com/deathgod-codes/deathgod.git chatapp
cd chatapp

# Set ownership
sudo chown -R www-data:www-data /var/www/chatapp
# Or for CentOS: sudo chown -R apache:apache /var/www/chatapp

# Set permissions
sudo find /var/www/chatapp -type d -exec chmod 755 {} \;
sudo find /var/www/chatapp -type f -exec chmod 644 {} \;

# Create uploads directory
sudo mkdir -p /var/www/chatapp/php/images
sudo chmod 755 /var/www/chatapp/php/images
```

### 5. Configure Application

```bash
# Copy environment file
cp .env.example .env

# Edit configuration
nano .env
```

Update the following in `.env`:
```env
DB_HOST=localhost
DB_USERNAME=chatapp_user
DB_PASSWORD=strong_password_here
DB_NAME=chatapp
```

Edit `php/config.php`:
```php
<?php
  $hostname = getenv('DB_HOST') ?: "localhost";
  $username = getenv('DB_USERNAME') ?: "chatapp_user";
  $password = getenv('DB_PASSWORD') ?: "strong_password_here";
  $dbname = getenv('DB_NAME') ?: "chatapp";

  $conn = mysqli_connect($hostname, $username, $password, $dbname);
  if(!$conn){
    error_log("Database connection error: " . mysqli_connect_error());
    die("Connection failed. Please try again later.");
  }
?>
```

### 6. Import Database Schema

```bash
# Import base schema
mysql -u chatapp_user -p chatapp < chatapp.sql

# Run migrations
mysql -u chatapp_user -p chatapp < migrations/001_security_enhancements.sql
```

### 7. Configure Apache Virtual Host

Create `/etc/apache2/sites-available/chatapp.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/chatapp
    
    <Directory /var/www/chatapp>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Protect sensitive files
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>
    
    <DirectoryMatch "^/.*/\.git/">
        Require all denied
    </DirectoryMatch>
    
    ErrorLog ${APACHE_LOG_DIR}/chatapp_error.log
    CustomLog ${APACHE_LOG_DIR}/chatapp_access.log combined
</VirtualHost>
```

Enable site:
```bash
sudo a2ensite chatapp
sudo systemctl reload apache2
```

### 8. Install SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Obtain certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal is configured automatically
# Test renewal:
sudo certbot renew --dry-run
```

---

## Security Hardening

### 1. File Permissions

```bash
# Restrict sensitive files
sudo chmod 600 /var/www/chatapp/.env
sudo chmod 600 /var/www/chatapp/php/config.php

# Prevent execution in uploads directory
echo "php_flag engine off" | sudo tee /var/www/chatapp/php/images/.htaccess
```

### 2. PHP Configuration

Edit `/etc/php/8.0/apache2/php.ini`:

```ini
# Security settings
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log

# Upload limits
upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 60

# Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = "Strict"

# Disable dangerous functions
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

### 3. MySQL Security

```bash
# MySQL configuration
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add/update:
```ini
[mysqld]
bind-address = 127.0.0.1
max_connections = 100
connect_timeout = 10
wait_timeout = 600
max_allowed_packet = 16M

# Security
local-infile = 0
```

Restart MySQL:
```bash
sudo systemctl restart mysql
```

### 4. Firewall Configuration

```bash
# Install UFW (Ubuntu)
sudo apt install ufw -y

# Allow SSH, HTTP, HTTPS
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable
sudo ufw status
```

### 5. Fail2Ban (Brute Force Protection)

```bash
# Install Fail2Ban
sudo apt install fail2ban -y

# Configure
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
sudo nano /etc/fail2ban/jail.local
```

Add Apache jail:
```ini
[apache-auth]
enabled = true
port = http,https
logpath = /var/log/apache2/*error.log
maxretry = 3
bantime = 3600
```

Restart:
```bash
sudo systemctl restart fail2ban
```

---

## Performance Optimization

### 1. Enable PHP OpCache

Edit `/etc/php/8.0/apache2/php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

### 2. Enable Apache Compression

```bash
sudo a2enmod deflate
sudo a2enmod expires
sudo a2enmod headers
```

Add to Apache config:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### 3. MySQL Optimization

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Add:
```ini
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
query_cache_type = 1
query_cache_size = 32M
```

---

## Backup Strategy

### 1. Database Backup Script

Create `/root/backup-chatapp.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/backup/chatapp"
DATE=$(date +%Y%m%d_%H%M%S)
DB_USER="chatapp_user"
DB_PASS="your_password"
DB_NAME="chatapp"

mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/chatapp_db_$DATE.sql.gz

# Backup files
tar -czf $BACKUP_DIR/chatapp_files_$DATE.tar.gz /var/www/chatapp/php/images/

# Keep only last 7 days
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

Make executable and schedule:
```bash
sudo chmod +x /root/backup-chatapp.sh

# Add to crontab (daily at 2 AM)
sudo crontab -e
0 2 * * * /root/backup-chatapp.sh
```

### 2. Offsite Backup

Consider using:
- AWS S3
- Google Cloud Storage
- Backblaze B2
- rsync to remote server

---

## Monitoring

### 1. Server Monitoring

Install monitoring tools:
```bash
sudo apt install htop iotop nethogs -y
```

### 2. Log Monitoring

Monitor logs:
```bash
# Apache logs
sudo tail -f /var/log/apache2/chatapp_error.log

# MySQL logs
sudo tail -f /var/log/mysql/error.log

# PHP logs
sudo tail -f /var/log/php/error.log
```

### 3. Uptime Monitoring

Use services like:
- UptimeRobot
- Pingdom
- StatusCake

---

## Troubleshooting

### Database Connection Issues

```bash
# Check MySQL is running
sudo systemctl status mysql

# Check MySQL logs
sudo tail -f /var/log/mysql/error.log

# Test connection
mysql -u chatapp_user -p -h localhost chatapp
```

### File Upload Issues

```bash
# Check directory permissions
ls -la /var/www/chatapp/php/images/

# Check PHP upload limits
php -i | grep upload_max_filesize
```

### Performance Issues

```bash
# Check Apache status
sudo systemctl status apache2

# Check resource usage
htop

# Check MySQL queries
mysql -u root -p -e "SHOW PROCESSLIST;"

# Enable slow query log
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
# Add: slow_query_log = 1
# Add: slow_query_log_file = /var/log/mysql/slow.log
# Add: long_query_time = 2
```

### SSL Certificate Issues

```bash
# Check certificate status
sudo certbot certificates

# Renew certificate manually
sudo certbot renew

# Check Apache SSL config
sudo apache2ctl -t -D DUMP_VHOSTS
```

---

## Post-Deployment Checklist

- [ ] All database migrations applied
- [ ] SSL certificate installed and working
- [ ] File upload directory writable
- [ ] Cron jobs configured for backups
- [ ] Firewall rules configured
- [ ] Monitoring tools set up
- [ ] Error logging configured
- [ ] PHP security settings applied
- [ ] Test user registration
- [ ] Test login functionality
- [ ] Test message sending
- [ ] Test file uploads
- [ ] Check all API endpoints
- [ ] Review security headers
- [ ] Test on mobile devices
- [ ] Set up uptime monitoring

---

## Maintenance

### Regular Tasks

**Daily:**
- Monitor error logs
- Check backup completion

**Weekly:**
- Review access logs
- Check disk space
- Monitor database size

**Monthly:**
- Update system packages
- Review security settings
- Test backup restoration
- Check SSL certificate expiry

---

## Support

For deployment issues, please:
1. Check the troubleshooting section above
2. Review server logs
3. Open an issue on GitHub with detailed information

---

**Last Updated:** January 2026
