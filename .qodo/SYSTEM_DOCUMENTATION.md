# VersoGym - Complete System Documentation

## 📚 Table of Contents
1. [System Overview](#system-overview)
2. [Architecture](#architecture)
3. [Database Design](#database-design)
4. [Authentication System](#authentication-system)
5. [User Roles & Permissions](#user-roles--permissions)
6. [Core Features](#core-features)
7. [API Documentation](#api-documentation)
8. [Security Implementation](#security-implementation)
9. [File Structure](#file-structure)
10. [Code Standards](#code-standards)

---

## 1. System Overview

### Purpose
VersoGym is a comprehensive gym management system that handles:
- User registration and authentication
- Customer profile management
- Workout scheduling and tracking
- Membership management
- Payment processing
- Social features (feed, chat)
- Admin dashboard for management

### Technology Stack
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: HTML5, Tailwind CSS, Alpine.js
- **Authentication**: Session-based with bcrypt password hashing
- **Optional**: Google OAuth 2.0

---

## 2. Architecture

### System Architecture
```
┌─────────────────────────────────────────────────┐
│                   Client Layer                   │
│  (Browser - HTML/CSS/JS/Alpine.js/Tailwind)     │
└────────────────┬────────────────────────────────┘
                 │
                 │ HTTP/HTTPS
                 │
┌────────────────▼────────────────────────────────┐
│              Application Layer                   │
│  ┌──────────────────────────────────────────┐  │
│  │  index.php (Landing Page)                │  │
│  │  login.php (Authentication)              │  │
│  │  register.php (User Registration)        │  │
│  │  customerdash.php (Customer Dashboard)   │  │
│  │  admindash.php (Admin Dashboard)         │  │
│  └─────────────────���────────────────────────┘  │
│  ┌──────────────────────────────────────────┐  │
│  │  Backend Handlers                        │  │
│  │  - booking.php                           │  │
│  │  - logout.php                            │  │
│  │  - payment.php                           │  │
│  └──────────────────────────────────────────┘  │
└────────────────┬────────────────────────────────┘
                 │
                 │ MySQLi
                 │
┌────────────────▼────────────────────────────────┐
│               Database Layer                     │
│  ┌──────────────────────────────────────────┐  │
│  │  MySQL Database (versogym)               │  │
│  │  - users                                 │  │
│  │  - workouts                              │  │
���  │  - memberships                           │  │
│  │  - payments                              │  │
│  │  - notifications                         │  │
│  │  - feed_posts                            │  │
│  │  - chat_messages                         │  │
│  └──────────────────────────────────────────┘  │
└─────────────────────────────────────────────────┘
```

### Request Flow

#### Authentication Flow
```
User → login.php → POST credentials
                 ↓
         Validate credentials
                 ↓
         Query users table
                 ↓
         Verify password_hash
                 ↓
         Create session
                 ↓
         Redirect based on role
         ├─→ customer → customerdash.php
         └─→ admin → admindash.php
```

#### Data Flow (Customer Dashboard)
```
customerdash.php loads
         ↓
GET ?action=initial_data
         ↓
Fetch user data from database
         ↓
Return JSON response
         ↓
Alpine.js renders UI
         ↓
User interactions trigger AJAX
         ↓
POST actions to server
         ↓
Update database
         ↓
Return JSON response
         ↓
Update UI dynamically
```

---

## 3. Database Design

### Entity Relationship Diagram

```
┌─────────────────┐
│     users       │
│─────────────────│
│ id (PK)         │
│ oauth_provider  │
│ oauth_uid       │
│ fullname        │
│ email (UNIQUE)  │
│ password        │
│ picture         │
│ age             │
│ gender          │
│ fitness_goals   │
│ membership_stat │
│ role            │
│ created_at      │
│ updated_at      │
└────────┬────────┘
         │
         │ 1:N
         │
    ┌────┴─────┬──────────┬──────────┬──────────┬──────────┐
    │          │          │          │          │          │
┌───▼────┐ ┌──▼──────┐ ┌─▼────────┐ ┌▼────────┐ ┌▼────────┐
│workouts│ │membership│ │payments  │ │notificat│ │feed_post│
│────────│ │──────────│ │──────────│ │─────────│ │─────────│
│id (PK) │ │id (PK)   │ │id (PK)   │ │id (PK)  │ │id (PK)  │
│user_id │ │user_id   │ │user_id   │ │user_id  │ │user_id  │
│title   │ │plan      │ │plan      │ │type     │ │content  │
│date    │ │start_date│ │amount    │ │message  │ │image    │
│time    │ │end_date  │ │first_name│ │icon     │ │created  │
│type    │ │status    │ │last_name │ │is_read  │ │         │
│recurr  │ │created   │ │email     │ │category │ │         │
│created │ │          │ │payment_m │ │created  │ │         │
└────────┘ └──────────┘ │payment_s │ └─────────┘ └─────────┘
                        │created   │
                        └──────────┘

┌──────────────────┐
│  chat_messages   │
│──────────────────│
│ id (PK)          │
│ from_user_id (FK)│──┐
│ to_user_id (FK)  │──┤
│ message          │  │
│ created_at       │  │
└──────────────────┘  │
         │            │
         └────────────┴─→ users.id
```

### Table Descriptions

#### users
Stores all user accounts (customers and admins)
- **Primary Key**: id
- **Unique**: email
- **Indexes**: oauth_provider+oauth_uid, role
- **Foreign Keys**: None (parent table)

#### workouts
Stores workout schedules and appointments
- **Primary Key**: id
- **Foreign Key**: user_id → users.id (CASCADE DELETE)
- **Indexes**: user_id+date

#### memberships
Tracks user membership plans
- **Primary Key**: id
- **Foreign Key**: user_id → users.id (CASCADE DELETE)
- **Indexes**: user_id+status

#### payments
Records all payment transactions
- **Primary Key**: id
- **Foreign Key**: user_id → users.id (CASCADE DELETE)
- **Indexes**: user_id+payment_status

#### notifications
User notifications and alerts
- **Primary Key**: id
- **Foreign Key**: user_id → users.id (CASCADE DELETE)
- **Indexes**: user_id+is_read, created_at

#### feed_posts
Social feed posts by users
- **Primary Key**: id
- **Foreign Key**: user_id → users.id (CASCADE DELETE)
- **Indexes**: created_at

#### chat_messages
Direct messages between users
- **Primary Key**: id
- **Foreign Keys**: 
  - from_user_id → users.id (CASCADE DELETE)
  - to_user_id → users.id (CASCADE DELETE)
- **Indexes**: from_user_id+to_user_id, created_at

---

## 4. Authentication System

### Registration Process

```php
// register.php

1. User submits registration form
   ↓
2. Validate input (email format, password strength)
   ↓
3. Check if email already exists
   ↓
4. Hash password using password_hash()
   ↓
5. Insert user into database
   ↓
6. Create session
   ↓
7. Redirect to profile setup
   ↓
8. Complete profile (age, gender, goals)
   ↓
9. Update user record
   ↓
10. Redirect to customerdash.php
```

### Login Process

```php
// login.php

1. User submits credentials
   ↓
2. Query database for user by email
   ↓
3. Verify password using password_verify()
   ↓
4. If valid:
   - Regenerate session ID
   - Set session variables:
     * user_id
     * user_email
     * user_fullname
     * role
   ↓
5. Redirect based on role:
   - customer → customerdash.php
   - admin → admindash.php
```

### Session Management

```php
// Session Variables
$_SESSION['user_id']       // int: User ID
$_SESSION['user_email']    // string: User email
$_SESSION['user_fullname'] // string: User full name
$_SESSION['role']          // string: 'customer' or 'admin'

// Session Security
- session_regenerate_id() on login
- Session timeout: PHP default (24 minutes)
- Secure cookie flags (httponly)
```

### Password Security

```php
// Registration
$hashed = password_hash($password, PASSWORD_DEFAULT);
// Uses bcrypt algorithm (cost factor 10)

// Login
$valid = password_verify($password, $hashed_from_db);

// Password Requirements
- Minimum 8 characters
- No maximum (bcrypt handles up to 72 chars)
- Recommended: Mix of upper, lower, numbers, symbols
```

---

## 5. User Roles & Permissions

### Role: Customer

**Access**:
- ✅ customerdash.php
- ✅ index.php (public pages)
- ❌ admindash.php

**Permissions**:
- View own profile
- Update own profile
- Schedule workouts
- Purchase memberships
- Make payments
- Post to feed
- Send chat messages
- View notifications
- Book appointments

### Role: Admin

**Access**:
- ✅ admindash.php
- ✅ index.php (public pages)
- ✅ customerdash.php (can view as customer)

**Permissions**:
- View all users
- View all memberships
- View all payments
- View statistics
- Manage members (future: edit, delete)
- View bookings/appointments

### Permission Checking

```php
// In protected pages
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Role-specific check
if ($_SESSION['role'] !== 'admin') {
    header('Location: customerdash.php');
    exit();
}
```

---

## 6. Core Features

### 6.1 Profile Management

**Location**: `customerdash.php` → Profile section

**Features**:
- Update full name
- Change age
- Update gender
- Modify fitness goals
- Upload profile picture
- View profile statistics

**Implementation**:
```php
// AJAX endpoint
POST ?action=update_profile
Parameters:
- fullName: string
- age: int
- gender: string
- fitnessGoals: string
- picture: string (data URL or path)

Response:
{
  "success": true
}
```

### 6.2 Workout Scheduling

**Location**: `customerdash.php` → My Schedule

**Features**:
- Add workouts
- View calendar (monthly/weekly/daily)
- Set recurring workouts
- Categorize by type (Cardio, Strength, Flexibility)

**Implementation**:
```php
// AJAX endpoint
POST ?action=add_workout
Parameters:
- title: string
- date: YYYY-MM-DD
- time: HH:MM
- type: string
- recurring: 0|1

Response:
{
  "success": true,
  "id": 123,
  "title": "Morning Run",
  "date": "2024-01-15",
  "time": "08:00:00",
  "type": "Cardio",
  "recurring": 0
}
```

### 6.3 Membership Management

**Location**: `customerdash.php` → Membership

**Plans**:
1. **Coaching Fees**
   - ₱150/session
   - Packages: 1 week, 2 weeks, 1 month

2. **Membership**
   - ₱850/year
   - Benefits: Free treadmill, promos, T-shirt

3. **Gym Fees**
   - ₱60/walk-in
   - Packages: 1 week, 2 weeks, 1 month

**Implementation**:
```php
// AJAX endpoint
POST ?action=purchase_membership
Parameters:
- plan: string
- amount: float
- first_name: string
- last_name: string
- email: string
- payment_method: string

Response:
{
  "success": true
}

// Database Updates:
1. INSERT INTO payments
2. INSERT INTO memberships
3. UPDATE users SET membership_status='active'
```

### 6.4 Feed System

**Location**: `customerdash.php` → Feeds

**Features**:
- Create text posts
- Upload images
- Add links
- Like posts
- Comment on posts
- View feed chronologically

**Implementation**:
```php
// AJAX endpoint
POST ?action=post_feed
Parameters:
- content: string
- link: string (optional)
- image_file: File (optional)

Response:
{
  "success": true,
  "post": {
    "id": 123,
    "author_name": "John Doe",
    "author_avatar": "path/to/avatar.jpg",
    "content": "Post content",
    "image": "path/to/image.jpg",
    "created_at": "2024-01-15 10:30:00"
  }
}
```

### 6.5 Chat System

**Location**: `customerdash.php` → Chat

**Features**:
- Search users
- Start conversations
- Send messages
- View message history
- Real-time updates (polling)

**Implementation**:
```php
// AJAX endpoint
POST ?action=send_message
Parameters:
- to_user: int (user ID)
- message: string

Response:
{
  "success": true,
  "message": {
    "id": 456,
    "from_user_id": 1,
    "to_user_id": 2,
    "message": "Hello!",
    "created_at": "2024-01-15 10:30:00",
    "from_name": "John Doe",
    "from_avatar": "path/to/avatar.jpg"
  }
}
```

### 6.6 Notifications

**Location**: `customerdash.php` → Notifications

**Types**:
- Booking confirmations
- Payment confirmations
- Membership updates
- System alerts
- Promotions

**Implementation**:
```php
// AJAX endpoint
POST ?action=mark_notification
Parameters:
- notification_id: int
- is_read: 0|1

Response:
{
  "success": true
}

// Clear all
POST ?action=clear_notifications
Response:
{
  "success": true
}
```

### 6.7 Booking System

**Location**: `index.php#booking`

**Features**:
- Public booking form
- Select service
- Choose date/time
- Add coach preference
- Additional notes

**Implementation**:
```php
// Form submission
POST backend/booking.php
Parameters:
- name: string
- email: string
- phone: string
- service: string
- date: YYYY-MM-DD
- time: HH:MM
- coach: string
- notes: string

Process:
1. Validate input
2. Create workout entry (if logged in)
3. Create notification (if logged in)
4. Send confirmation
5. Redirect with success message
```

---

## 7. API Documentation

### Base URL
All AJAX endpoints are relative to the current page.

### Authentication
All API calls require an active session (except public endpoints).

### Response Format
All responses are JSON:
```json
{
  "success": true,
  "data": {},
  "error": null
}
```

### Endpoints

#### GET /customerdash.php?action=initial_data
Load all user data for dashboard initialization.

**Response**:
```json
{
  "user": {
    "id": 1,
    "fullname": "John Doe",
    "email": "john@example.com",
    "picture": "path/to/avatar.jpg",
    "age": 25,
    "gender": "Male",
    "fitness_goals": "Build muscle",
    "membership_status": "active"
  },
  "workouts": [...],
  "memberships": [...],
  "payments": [...],
  "notifications": [...],
  "feed_posts": [...],
  "chat_users": [...],
  "chat_messages": {...}
}
```

#### POST /customerdash.php
All POST actions use the same endpoint with different `action` parameter.

**Actions**:
- `update_profile`
- `add_workout`
- `post_feed`
- `send_message`
- `mark_notification`
- `clear_notifications`
- `purchase_membership`

---

## 8. Security Implementation

### 8.1 SQL Injection Prevention

**Method**: Prepared Statements (MySQLi)

```php
// Example
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
```

**Coverage**: 100% of database queries use prepared statements

### 8.2 XSS Prevention

**Method**: HTML Entity Encoding

```php
// Output escaping
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Usage
echo e($user_input);
```

**Coverage**: All user-generated content is escaped before display

### 8.3 Password Security

**Method**: Bcrypt Hashing

```php
// Hashing
$hash = password_hash($password, PASSWORD_DEFAULT);

// Verification
$valid = password_verify($password, $hash);
```

**Strength**: Cost factor 10 (default), ~0.1 seconds per hash

### 8.4 Session Security

**Measures**:
- Session ID regeneration on login
- HTTP-only cookies
- Secure flag (HTTPS)
- Session timeout
- Logout destroys session completely

```php
// Login
session_regenerate_id(true);

// Logout
session_unset();
session_destroy();
setcookie(session_name(), '', time()-3600, '/');
```

### 8.5 File Upload Security

**Validation**:
- File type checking (MIME type)
- File size limits (3MB)
- Unique filenames
- Restricted upload directory

```php
// Example
$allowed = ['image/jpeg', 'image/png', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
if (!in_array($mime, $allowed)) {
    // Reject
}
```

### 8.6 CSRF Protection

**Status**: Partially implemented

**Recommendation**: Add CSRF tokens to all forms

```php
// Generate token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Validate
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF token mismatch');
}
```

---

## 9. File Structure

```
WebProj/
├── index.php                    # Landing page
├── login.php                    # Login page
├── register.php                 # Registration page
├── customerdash.php             # Customer dashboard (main app)
├── admindash.php                # Admin dashboard
├── config.php                   # Main configuration
├── database_setup.sql           # Database schema
├── SETUP_INSTRUCTIONS.md        # Setup guide
├── TESTING_GUIDE.md             # Testing procedures
├── SYSTEM_DOCUMENTATION.md      # This file
│
├── backend/
│   ├── config.php              # Database connection
│   ├── logout.php              # Logout handler
│   ├── booking.php             # Booking handler
│   └── payment.php             # Payment handler
│
├── uploads/
│   ├── avatars/                # User profile pictures
│   └── feed_images/            # Feed post images
│
├── img/                        # Static images
│   ├── logo.png
│   ├── hero-1.png
│   ├── gallery-*.jpg
│   └── trainer-*.jpg
│
└── auth/                       # OAuth handlers (optional)
    ├── google_login.php
    └── google_callback.php
```

---

## 10. Code Standards

### PHP Standards

**Version**: PHP 7.4+

**Naming Conventions**:
- Functions: `snake_case`
- Variables: `$camelCase`
- Constants: `UPPER_CASE`
- Classes: `PascalCase`

**Example**:
```php
function get_user_data($userId) {
    $conn = getDbConnection();
    // ...
}
```

### Database Standards

**Naming**:
- Tables: `lowercase_plural`
- Columns: `lowercase_underscore`
- Primary Keys: `id`
- Foreign Keys: `table_id`

**Example**:
```sql
CREATE TABLE users (
    id INT PRIMARY KEY,
    fullname VARCHAR(255),
    created_at DATETIME
);
```

### JavaScript Standards

**Framework**: Alpine.js

**Naming**:
- Variables: `camelCase`
- Functions: `camelCase`
- Constants: `UPPER_CASE`

**Example**:
```javascript
function loadInitialData() {
    fetch('?action=initial_data')
        .then(res => res.json())
        .then(data => {
            this.user = data.user;
        });
}
```

### CSS Standards

**Framework**: Tailwind CSS

**Custom Classes**: Use Tailwind utilities, minimal custom CSS

**Example**:
```html
<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-900">Title</h2>
</div>
```

---

## 📞 Support & Maintenance

### Logging

**Error Logs**: Check PHP error log
```bash
tail -f /var/log/apache2/error.log
```

**Database Logs**: Enable MySQL slow query log
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

### Backup Procedures

**Database Backup**:
```bash
mysqldump -u root -p versogym > backup_$(date +%Y%m%d).sql
```

**File Backup**:
```bash
tar -czf webproj_backup_$(date +%Y%m%d).tar.gz /path/to/WebProj
```

### Monitoring

**Key Metrics**:
- Active users
- Failed login attempts
- Database query times
- Error rates
- Disk space (uploads directory)

---

## 🔄 Version History

**v1.0.0** (2024)
- Initial release
- Complete authentication system
- Customer dashboard
- Admin dashboard
- All core features implemented

---

**End of Documentation**

For questions or issues, refer to:
- SETUP_INSTRUCTIONS.md
- TESTING_GUIDE.md
- PHP error logs
- MySQL error logs
