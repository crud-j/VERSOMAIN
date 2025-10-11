# VersoGym - Complete Gym Management System

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)
![Status](https://img.shields.io/badge/status-production--ready-green.svg)

## 🎯 Overview

VersoGym is a fully functional, database-driven gym management system built with PHP and MySQL. It provides complete user authentication, customer dashboards, admin dashboards, and secure database operations.

## ✨ Key Features

### 🔐 Authentication System
- ✅ Secure user registration
- ✅ Email/password login
- ✅ Google OAuth integration (optional)
- ✅ Password hashing with bcrypt
- ✅ Session-based authentication
- ✅ Role-based access control

### 👤 Customer Dashboard
- ✅ Profile management with photo upload
- ✅ Workout scheduling (calendar views)
- ✅ Membership management
- ✅ Payment processing
- ✅ Real-time notifications
- ✅ Social feed system
- ✅ Direct messaging/chat
- ✅ Booking appointments

### 👨‍💼 Admin Dashboard
- ✅ View all members
- ✅ Statistics overview
- ✅ Revenue tracking
- ✅ Member management
- ✅ Membership status monitoring

### 🔒 Security Features
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (output escaping)
- ✅ Password hashing (bcrypt)
- ✅ Secure session management
- ✅ Input validation & sanitization
- ✅ File upload security

## 🚀 Quick Start

### Prerequisites
- XAMPP (or similar) with PHP 7.4+ and MySQL 5.7+
- Web browser
- Text editor (optional)

### Installation (5 minutes)

1. **Import Database**
   ```bash
   # Via phpMyAdmin: http://localhost/phpmyadmin
   # Import file: database_setup.sql
   
   # OR via command line:
   mysql -u root -p < database_setup.sql
   ```

2. **Configure Database** (if needed)
   Edit `config.php`:
   ```php
   define('DB_PASSWORD', 'your_password');
   ```

3. **Access the System**
   - Landing Page: http://localhost/WebProj/index.php
   - Login: http://localhost/WebProj/login.php
   - Register: http://localhost/WebProj/register.php

4. **Login with Default Accounts**
   
   **Admin:**
   - Email: `admin@versogym.com`
   - Password: `Admin123!`
   
   **Test Customer:**
   - Email: `customer@test.com`
   - Password: `Test123!`

## 📚 Documentation

| Document | Description |
|----------|-------------|
| [QUICK_START.md](QUICK_START.md) | Get started in 5 minutes |
| [SETUP_INSTRUCTIONS.md](SETUP_INSTRUCTIONS.md) | Complete setup guide |
| [TESTING_GUIDE.md](TESTING_GUIDE.md) | Comprehensive testing procedures |
| [SYSTEM_DOCUMENTATION.md](SYSTEM_DOCUMENTATION.md) | Technical documentation |
| [PROJECT_SUMMARY.md](PROJECT_SUMMARY.md) | Project overview & deliverables |

## 🗄️ Database Schema

### Tables
- **users** - User accounts (customers & admins)
- **workouts** - Workout schedules & appointments
- **memberships** - Membership plans & status
- **payments** - Payment transactions
- **notifications** - User notifications
- **feed_posts** - Social feed posts
- **chat_messages** - Direct messages

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: HTML5, Tailwind CSS, Alpine.js
- **Authentication**: Session-based with bcrypt
- **Optional**: Google OAuth 2.0

## 📁 Project Structure

```
WebProj/
├── index.php              # Landing page
├── login.php              # Login page
├── register.php           # Registration page
├── customerdash.php       # Customer dashboard
├── admindash.php          # Admin dashboard
├── config.php             # Main configuration
├── database_setup.sql     # Database schema
├── backend/
│   ├── config.php        # Database connection
│   ├── logout.php        # Logout handler
│   ├── booking.php       # Booking handler
│   └── payment.php       # Payment handler
├── uploads/
│   ├── avatars/          # Profile pictures
│   └── feed_images/      # Feed images
└── img/                  # Static images
```

## 🔄 User Flow

### Registration → Login → Dashboard

```
1. User registers (register.php)
   ↓
2. Completes profile setup
   ↓
3. Redirected to customerdash.php
   ↓
4. Can access all customer features
```

### Admin Access

```
1. Admin logs in (login.php)
   ↓
2. Redirected to admindash.php
   ↓
3. Can view all members and statistics
```

## 🧪 Testing

Follow the [TESTING_GUIDE.md](TESTING_GUIDE.md) for comprehensive testing procedures.

**Quick Test:**
1. Import database
2. Login as admin
3. Register new customer
4. Test customer features
5. Verify admin can see new member

## 🔐 Security

### Implemented Measures
- ✅ Prepared statements (SQL injection prevention)
- ✅ Password hashing with `password_hash()`
- ✅ Input sanitization with `htmlspecialchars()`
- ✅ Session regeneration on login
- ✅ Secure logout (session destruction)
- ✅ File upload validation
- ✅ XSS protection

### Best Practices
- All database queries use prepared statements
- Passwords never stored in plain text
- User input always sanitized
- Sessions properly managed
- File uploads validated

## 📊 Features Overview

### Customer Features
| Feature | Status | Description |
|---------|--------|-------------|
| Profile Management | ✅ | Update info, upload photo |
| Workout Scheduling | ✅ | Calendar with multiple views |
| Membership | ✅ | Purchase and manage plans |
| Payments | ✅ | Process payments securely |
| Feed | ✅ | Social posts with images |
| Chat | ✅ | Direct messaging |
| Notifications | ✅ | Real-time alerts |
| Booking | ✅ | Book appointments |

### Admin Features
| Feature | Status | Description |
|---------|--------|-------------|
| Dashboard | ✅ | Statistics overview |
| Members | ✅ | View all customers |
| Analytics | ✅ | Revenue, memberships |
| Management | ✅ | Member details |

## 🎨 Screenshots

### Landing Page
Modern, responsive design with hero section, services, trainers, and booking.

### Customer Dashboard
Full-featured dashboard with profile, schedule, membership, chat, and more.

### Admin Dashboard
Clean interface showing member statistics and management tools.

## 🚀 Deployment

### Development
```bash
# Already configured for localhost
http://localhost/WebProj/
```

### Production
1. Update `config.php` with production database credentials
2. Set `BASE_URL` to your domain
3. Disable error display
4. Enable HTTPS
5. Set proper file permissions
6. Configure backups

See [SETUP_INSTRUCTIONS.md](SETUP_INSTRUCTIONS.md) for details.

## 🐛 Troubleshooting

### Common Issues

**Database Connection Failed**
- Check credentials in `config.php`
- Ensure MySQL is running

**Session Not Working**
- Clear browser cookies
- Check session directory permissions

**Images Not Uploading**
- Create `uploads/avatars` directory
- Set permissions: `chmod 755 uploads -R`

**Page Not Found**
- Verify XAMPP is running
- Check URL: `http://localhost/WebProj/index.php`

## 📝 API Endpoints

### Customer Dashboard (customerdash.php)

**GET Endpoints:**
- `?action=initial_data` - Load all user data

**POST Endpoints:**
- `action=update_profile` - Update user profile
- `action=add_workout` - Add workout/appointment
- `action=post_feed` - Create feed post
- `action=send_message` - Send chat message
- `action=mark_notification` - Mark notification as read
- `action=clear_notifications` - Clear all notifications
- `action=purchase_membership` - Purchase membership

## 🤝 Contributing

This is a complete, production-ready system. For modifications:
1. Follow existing code standards
2. Test thoroughly
3. Update documentation
4. Maintain security practices

## 📄 License

MIT License - Free to use and modify

## 👏 Credits

- **PHP** - Server-side logic
- **MySQL** - Database management
- **Tailwind CSS** - Styling framework
- **Alpine.js** - Reactive components
- **Material Icons** - Icon library

## 📞 Support

For issues or questions:
1. Check [QUICK_START.md](QUICK_START.md)
2. Review [SETUP_INSTRUCTIONS.md](SETUP_INSTRUCTIONS.md)
3. Follow [TESTING_GUIDE.md](TESTING_GUIDE.md)
4. Consult [SYSTEM_DOCUMENTATION.md](SYSTEM_DOCUMENTATION.md)

## ✅ Project Status

**Status**: ✅ **COMPLETE & PRODUCTION READY**

All features implemented, tested, and documented.

### Completed:
- [x] Database schema
- [x] Authentication system
- [x] Customer dashboard
- [x] Admin dashboard
- [x] Security implementation
- [x] Documentation
- [x] Testing procedures

### Ready For:
- [x] Development use
- [x] Testing
- [x] Demonstration
- [x] Production deployment

---

**Version**: 1.0.0  
**Last Updated**: 2024  
**Status**: Production Ready  

**Made with ❤️ for VersoGym**
