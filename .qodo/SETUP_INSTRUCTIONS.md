# VersoGym - Complete Setup Instructions

## 📋 Project Overview
A fully functional gym management system with user authentication, customer dashboards, admin dashboards, and database integration.

## 🚀 Quick Start

### 1. Database Setup

#### Option A: Using phpMyAdmin
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Click "Import" tab
3. Choose file: `database_setup.sql`
4. Click "Go" to execute

#### Option B: Using MySQL Command Line
```bash
mysql -u root -p < database_setup.sql
```

### 2. Configure Database Connection

Edit `config.php` and update these constants if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '@l03e1t3');  // Your MySQL password
define('DB_NAME', 'versogym');
```

### 3. Create Required Directories

The system will auto-create these, but you can create them manually:
```
WebProj/
├── uploads/
│   ├── avatars/
│   └── feed_images/
```

Set permissions (Linux/Mac):
```bash
chmod 755 uploads
chmod 755 uploads/avatars
chmod 755 uploads/feed_images
```

### 4. Access the System

- **Main Website**: http://localhost/WebProj/index.php
- **Login Page**: http://localhost/WebProj/login.php
- **Registration**: http://localhost/WebProj/register.php

## 👤 Default Accounts

### Admin Account
- **Email**: admin@versogym.com
- **Password**: Admin123!
- **Access**: http://localhost/WebProj/admindash.php

### Test Customer Account
- **Email**: customer@test.com
- **Password**: Test123!
- **Access**: http://localhost/WebProj/customerdash.php

## 🔐 Security Features

### Implemented Security Measures:
1. **Password Hashing**: Using PHP's `password_hash()` with bcrypt
2. **Prepared Statements**: All database queries use prepared statements
3. **Input Sanitization**: All user inputs are sanitized
4. **Session Management**: Secure session handling with regeneration
5. **XSS Protection**: HTML special characters escaped
6. **SQL Injection Prevention**: Parameterized queries throughout

## 📊 Database Schema

### Tables:
1. **users** - User accounts (customers and admins)
2. **workouts** - Workout schedules and appointments
3. **memberships** - Membership plans and status
4. **payments** - Payment records
5. **notifications** - User notifications
6. **feed_posts** - Social feed posts
7. **chat_messages** - User-to-user messaging

## 🎯 Key Features

### Customer Features:
- ✅ User Registration & Login
- ✅ Profile Management
- ✅ Workout Scheduling
- ✅ Membership Management
- ✅ Payment Processing
- ✅ Real-time Notifications
- ✅ Social Feed
- ✅ Chat System
- ✅ Booking Appointments

### Admin Features:
- ✅ View All Members
- ✅ Manage Users
- ✅ View Payments
- ✅ View Memberships
- ✅ Dashboard Analytics

## 🔄 User Flow

### Registration Flow:
1. User visits `register.php`
2. Fills signup form (or uses Google OAuth)
3. Creates account in database
4. Completes profile setup
5. Redirected to `customerdash.php`

### Login Flow:
1. User visits `login.php`
2. Enters credentials
3. System verifies against database
4. If **customer**: Redirect to `customerdash.php`
5. If **admin**: Redirect to `admindash.php`

### Booking Flow:
1. User fills booking form on `index.php#booking`
2. Data submitted to `backend/booking.php`
3. Creates workout entry in database
4. Creates notification for user
5. Confirmation message displayed

## 🛠️ File Structure

```
WebProj/
├── index.php                 # Main landing page
├── login.php                 # Login page
├── register.php              # Registration page
├── customerdash.php          # Customer dashboard
├── admindash.php             # Admin dashboard
├── config.php                # Main configuration
├── database_setup.sql        # Database schema
├── backend/
│   ├── config.php           # Database connection
│   ├── logout.php           # Logout handler
│   ├── booking.php          # Booking handler
│   └── payment.php          # Payment handler
├── uploads/
│   ├── avatars/             # User profile pictures
│   └── feed_images/         # Feed post images
└── img/                     # Static images
```

## 🔧 Troubleshooting

### Database Connection Issues:
```php
// Check config.php has correct credentials
define('DB_PASSWORD', 'your_actual_password');
```

### Permission Errors:
```bash
# Linux/Mac - Set proper permissions
chmod -R 755 uploads/
chown -R www-data:www-data uploads/
```

### Session Issues:
```php
// Ensure session directory is writable
session_save_path('/tmp');
```

### Google OAuth Not Working:
- Google OAuth requires composer dependencies
- If not needed, users can register with email/password
- Update Google credentials in `config.php` if using OAuth

## 📝 API Endpoints (AJAX)

### Customer Dashboard (`customerdash.php`):
- `GET ?action=initial_data` - Load all user data
- `POST action=update_profile` - Update user profile
- `POST action=add_workout` - Add workout/appointment
- `POST action=post_feed` - Create feed post
- `POST action=send_message` - Send chat message
- `POST action=mark_notification` - Mark notification as read
- `POST action=clear_notifications` - Clear all notifications
- `POST action=purchase_membership` - Purchase membership

## 🎨 Customization

### Change Theme Colors:
Edit Tailwind config in HTML files:
```javascript
tailwind.config = {
  theme: {
    extend: {
      colors: {
        primary: "#your-color",
      }
    }
  }
}
```

### Update Logo:
Replace `img/logo.png` with your logo

### Modify Email Templates:
Edit notification messages in respective PHP files

## 📧 Support

For issues or questions:
1. Check error logs: `error_log` in PHP
2. Enable display_errors in `config.php` for development
3. Check browser console for JavaScript errors
4. Verify database connection in phpMyAdmin

## 🔒 Production Deployment

Before deploying to production:

1. **Disable Error Display**:
```php
error_reporting(0);
ini_set('display_errors', '0');
```

2. **Use HTTPS**: Ensure SSL certificate is installed

3. **Update Base URL**:
```php
define('BASE_URL', 'https://yourdomain.com');
```

4. **Secure Database**:
- Use strong passwords
- Limit database user permissions
- Enable firewall rules

5. **File Permissions**:
- Set uploads directory to 755
- Ensure PHP files are not writable by web server

## ✅ Testing Checklist

- [ ] Database created successfully
- [ ] Admin login works
- [ ] Customer registration works
- [ ] Customer login works
- [ ] Profile update works
- [ ] Workout scheduling works
- [ ] Booking system works
- [ ] Notifications display
- [ ] Feed posts work
- [ ] Chat system works
- [ ] Payment processing works
- [ ] Admin dashboard displays data

## 📚 Additional Resources

- PHP Documentation: https://www.php.net/docs.php
- MySQL Documentation: https://dev.mysql.com/doc/
- Tailwind CSS: https://tailwindcss.com/docs
- Alpine.js: https://alpinejs.dev/

---

**Version**: 1.0.0  
**Last Updated**: 2024  
**License**: MIT
