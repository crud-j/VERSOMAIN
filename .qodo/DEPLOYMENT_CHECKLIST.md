# VersoGym - Deployment Checklist

## 📋 Pre-Deployment Checklist

Use this checklist before deploying to production.

---

## 🔧 Configuration

### Database Configuration
- [ ] Update database credentials in `config.php`
- [ ] Update database credentials in `backend/config.php`
- [ ] Test database connection
- [ ] Verify all tables exist
- [ ] Check admin account exists
- [ ] Backup database

```php
// config.php
define('DB_HOST', 'your_production_host');
define('DB_USERNAME', 'your_production_user');
define('DB_PASSWORD', 'your_strong_password');
define('DB_NAME', 'versogym');
```

### Application Configuration
- [ ] Update `BASE_URL` in `config.php`
- [ ] Set production domain
- [ ] Configure email settings (if applicable)
- [ ] Update Google OAuth credentials (if using)

```php
// config.php
define('BASE_URL', 'https://yourdomain.com');
```

---

## 🔒 Security

### Error Handling
- [ ] Disable error display
- [ ] Enable error logging
- [ ] Set custom error pages

```php
// config.php
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', '/path/to/error.log');
```

### Session Security
- [ ] Enable HTTPS
- [ ] Set secure cookie flags
- [ ] Configure session timeout
- [ ] Set session save path

```php
// config.php
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
```

### File Permissions
- [ ] Set proper file permissions
- [ ] Secure uploads directory
- [ ] Protect config files
- [ ] Remove unnecessary files

```bash
# Linux/Mac
chmod 644 *.php
chmod 755 uploads/
chmod 600 config.php
chmod 600 backend/config.php
```

### Database Security
- [ ] Use strong database password
- [ ] Limit database user permissions
- [ ] Enable MySQL firewall rules
- [ ] Disable remote root access
- [ ] Regular security updates

---

## 📁 File Management

### Required Directories
- [ ] Create `uploads/avatars/` directory
- [ ] Create `uploads/feed_images/` directory
- [ ] Set proper permissions (755)
- [ ] Test file upload functionality

```bash
mkdir -p uploads/avatars
mkdir -p uploads/feed_images
chmod 755 uploads -R
```

### Remove Development Files
- [ ] Remove test files
- [ ] Remove debug code
- [ ] Remove commented code
- [ ] Remove unused files
- [ ] Clean up temporary files

### Backup Important Files
- [ ] Backup database
- [ ] Backup config files
- [ ] Backup uploaded files
- [ ] Document backup location

---

## 🌐 Web Server Configuration

### Apache (.htaccess)
- [ ] Enable mod_rewrite
- [ ] Set up URL rewriting
- [ ] Configure security headers
- [ ] Set up HTTPS redirect

```apache
# .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security Headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
```

### PHP Configuration
- [ ] Set appropriate memory limit
- [ ] Configure upload limits
- [ ] Set execution time limits
- [ ] Enable OPcache (if available)

```ini
; php.ini
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 30
```

---

## 🔐 SSL/HTTPS

### SSL Certificate
- [ ] Obtain SSL certificate
- [ ] Install SSL certificate
- [ ] Configure HTTPS
- [ ] Test HTTPS connection
- [ ] Set up auto-renewal (Let's Encrypt)

### Force HTTPS
- [ ] Update all URLs to HTTPS
- [ ] Add HTTPS redirect
- [ ] Update session cookie settings
- [ ] Test all pages with HTTPS

---

## 📊 Database

### Optimization
- [ ] Add necessary indexes
- [ ] Optimize queries
- [ ] Set up query caching
- [ ] Configure connection pooling

### Backup Strategy
- [ ] Set up automated backups
- [ ] Test backup restoration
- [ ] Document backup schedule
- [ ] Store backups securely

```bash
# Daily backup script
mysqldump -u user -p versogym > backup_$(date +%Y%m%d).sql
```

---

## 🧪 Testing

### Functionality Testing
- [ ] Test user registration
- [ ] Test user login
- [ ] Test admin login
- [ ] Test profile updates
- [ ] Test workout scheduling
- [ ] Test membership purchase
- [ ] Test booking system
- [ ] Test feed posts
- [ ] Test chat system
- [ ] Test notifications

### Security Testing
- [ ] Test SQL injection prevention
- [ ] Test XSS prevention
- [ ] Test CSRF protection
- [ ] Test session security
- [ ] Test file upload security
- [ ] Test password strength
- [ ] Test authentication bypass

### Performance Testing
- [ ] Test page load times
- [ ] Test database query performance
- [ ] Test concurrent users
- [ ] Test file upload speed
- [ ] Monitor server resources

### Browser Testing
- [ ] Test on Chrome
- [ ] Test on Firefox
- [ ] Test on Safari
- [ ] Test on Edge
- [ ] Test on mobile browsers

### Mobile Testing
- [ ] Test responsive design
- [ ] Test touch interactions
- [ ] Test mobile forms
- [ ] Test mobile navigation

---

## 📈 Monitoring

### Set Up Monitoring
- [ ] Configure error monitoring
- [ ] Set up uptime monitoring
- [ ] Configure performance monitoring
- [ ] Set up security monitoring
- [ ] Configure log rotation

### Analytics
- [ ] Set up Google Analytics (optional)
- [ ] Configure user tracking
- [ ] Set up conversion tracking
- [ ] Monitor user behavior

---

## 📧 Email Configuration

### Email Settings
- [ ] Configure SMTP settings
- [ ] Test email sending
- [ ] Set up email templates
- [ ] Configure email notifications

```php
// Example email configuration
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@yourdomain.com');
define('SMTP_PASS', 'your_password');
```

---

## 🚀 Deployment Steps

### Pre-Deployment
1. [ ] Complete all checklist items above
2. [ ] Create deployment plan
3. [ ] Schedule maintenance window
4. [ ] Notify users (if applicable)
5. [ ] Prepare rollback plan

### Deployment
1. [ ] Backup current production (if exists)
2. [ ] Upload files to server
3. [ ] Import database
4. [ ] Update configuration files
5. [ ] Set file permissions
6. [ ] Test basic functionality
7. [ ] Clear caches
8. [ ] Test all features

### Post-Deployment
1. [ ] Verify all pages load
2. [ ] Test critical features
3. [ ] Check error logs
4. [ ] Monitor performance
5. [ ] Verify SSL certificate
6. [ ] Test email functionality
7. [ ] Update documentation
8. [ ] Notify stakeholders

---

## 🔄 Rollback Plan

### If Deployment Fails
1. [ ] Stop web server
2. [ ] Restore previous files
3. [ ] Restore previous database
4. [ ] Restart web server
5. [ ] Verify functionality
6. [ ] Document issues
7. [ ] Plan fixes

---

## 📝 Documentation

### Update Documentation
- [ ] Update README.md
- [ ] Update API documentation
- [ ] Document configuration changes
- [ ] Update user guides
- [ ] Document known issues

### Create Runbooks
- [ ] Deployment procedure
- [ ] Backup procedure
- [ ] Restore procedure
- [ ] Troubleshooting guide
- [ ] Emergency contacts

---

## 🎯 Post-Deployment Tasks

### Week 1
- [ ] Monitor error logs daily
- [ ] Check performance metrics
- [ ] Gather user feedback
- [ ] Fix critical bugs
- [ ] Update documentation

### Week 2-4
- [ ] Review analytics
- [ ] Optimize performance
- [ ] Address user feedback
- [ ] Plan improvements
- [ ] Update security

### Monthly
- [ ] Security audit
- [ ] Performance review
- [ ] Backup verification
- [ ] Update dependencies
- [ ] Review logs

---

## ✅ Final Checklist

Before going live:
- [ ] All configuration updated
- [ ] Security measures in place
- [ ] SSL certificate installed
- [ ] Database optimized
- [ ] Backups configured
- [ ] Monitoring set up
- [ ] Testing completed
- [ ] Documentation updated
- [ ] Team trained
- [ ] Rollback plan ready

---

## 🆘 Emergency Contacts

```
Database Admin: _______________
Server Admin: _______________
Developer: _______________
Project Manager: _______________
```

---

## 📞 Support Resources

- **Documentation**: Check all .md files in project
- **Error Logs**: `/path/to/error.log`
- **Database Logs**: `/var/log/mysql/error.log`
- **Web Server Logs**: `/var/log/apache2/error.log`

---

## 🎉 Deployment Complete!

Once all items are checked:
- [ ] Mark deployment as complete
- [ ] Document deployment date
- [ ] Archive deployment checklist
- [ ] Celebrate! 🎊

---

**Deployment Date**: _______________  
**Deployed By**: _______________  
**Version**: 1.0.0  
**Status**: [ ] Success [ ] Failed  

**Notes**:
_______________________________________
_______________________________________
_______________________________________

---

**End of Deployment Checklist**
