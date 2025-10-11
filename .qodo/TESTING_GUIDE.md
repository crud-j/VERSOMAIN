# VersoGym - Complete Testing Guide

## 🧪 Testing Checklist

### Phase 1: Database Setup ✅

1. **Import Database**
   ```bash
   # Via MySQL command line
   mysql -u root -p@l03e1t3 < database_setup.sql
   ```
   
   **Expected Result**: Database `versogym` created with all tables

2. **Verify Tables**
   ```sql
   USE versogym;
   SHOW TABLES;
   ```
   
   **Expected Output**:
   - users
   - workouts
   - memberships
   - payments
   - notifications
   - feed_posts
   - chat_messages

3. **Verify Admin Account**
   ```sql
   SELECT id, fullname, email, role FROM users WHERE role = 'admin';
   ```
   
   **Expected Output**:
   ```
   id: 1
   fullname: VersoGym Admin
   email: admin@versogym.com
   role: admin
   ```

---

### Phase 2: Authentication Testing 🔐

#### Test 1: Admin Login
1. Navigate to: `http://localhost/WebProj/login.php`
2. Enter credentials:
   - Email: `admin@versogym.com`
   - Password: `Admin123!`
3. Click "Login"

**Expected Result**: Redirected to `admindash.php` showing admin dashboard

**Verify**:
- [ ] Login successful
- [ ] Session created
- [ ] Admin dashboard displays
- [ ] Can see member statistics

---

#### Test 2: Customer Registration
1. Navigate to: `http://localhost/WebProj/register.php`
2. Fill signup form:
   - Full Name: `Test User`
   - Email: `test@example.com`
   - Password: `Test123!`
   - Confirm Password: `Test123!`
3. Click "Sign Up"
4. Complete profile setup:
   - Age: `25`
   - Gender: `Male`
   - Fitness Goals: `Build muscle`
5. Click "Save and Continue"

**Expected Result**: 
- Account created in database
- Profile setup completed
- Redirected to `customerdash.php`

**Verify**:
- [ ] User created in database
- [ ] Password hashed correctly
- [ ] Profile data saved
- [ ] Session established
- [ ] Dashboard loads

---

#### Test 3: Customer Login
1. Logout if logged in
2. Navigate to: `http://localhost/WebProj/login.php`
3. Enter credentials:
   - Email: `test@example.com`
   - Password: `Test123!`
4. Click "Login"

**Expected Result**: Redirected to `customerdash.php`

**Verify**:
- [ ] Login successful
- [ ] Correct user data displayed
- [ ] Dashboard fully functional

---

### Phase 3: Customer Dashboard Testing 📊

#### Test 4: Profile Update
1. Login as customer
2. Navigate to Profile section
3. Click "Edit Profile"
4. Update information:
   - Change name
   - Update age
   - Modify fitness goals
5. Upload profile picture
6. Click "Save Changes"

**Expected Result**: Profile updated in database

**Verify**:
- [ ] Changes saved to database
- [ ] Profile picture uploaded to `/uploads/avatars/`
- [ ] Success message displayed
- [ ] Updated data shows immediately

**SQL Verification**:
```sql
SELECT fullname, age, fitness_goals, picture FROM users WHERE email = 'test@example.com';
```

---

#### Test 5: Workout Scheduling
1. Navigate to "My Schedule"
2. Click "Add New Workout"
3. Fill form:
   - Title: `Morning Cardio`
   - Date: Select tomorrow
   - Time: `08:00`
   - Type: `Cardio`
4. Click "Add Workout"

**Expected Result**: Workout added to calendar

**Verify**:
- [ ] Workout appears in calendar
- [ ] Saved to database
- [ ] Shows in correct date

**SQL Verification**:
```sql
SELECT * FROM workouts WHERE user_id = (SELECT id FROM users WHERE email = 'test@example.com');
```

---

#### Test 6: Membership Purchase
1. Navigate to "Membership" section
2. Select a plan (e.g., "Membership - ₱850/year")
3. Click "Join Now"
4. Fill payment form:
   - First Name: `Test`
   - Last Name: `User`
   - Email: `test@example.com`
   - Phone: `09171234567`
   - Payment Method: Select `GCash`
5. Click "Proceed to Confirm"
6. Click "Confirm & Pay"

**Expected Result**: 
- Payment recorded
- Membership created
- User status updated to 'active'

**Verify**:
- [ ] Payment entry in database
- [ ] Membership entry created
- [ ] User membership_status = 'active'
- [ ] Success modal displayed

**SQL Verification**:
```sql
SELECT * FROM payments WHERE user_id = (SELECT id FROM users WHERE email = 'test@example.com');
SELECT * FROM memberships WHERE user_id = (SELECT id FROM users WHERE email = 'test@example.com');
SELECT membership_status FROM users WHERE email = 'test@example.com';
```

---

#### Test 7: Feed Posts
1. Navigate to "Feeds" section
2. Type a post: `Just finished my workout! 💪`
3. Optionally upload an image
4. Click "Post"

**Expected Result**: Post appears in feed

**Verify**:
- [ ] Post saved to database
- [ ] Appears in feed immediately
- [ ] Image uploaded (if added)
- [ ] Timestamp correct

**SQL Verification**:
```sql
SELECT * FROM feed_posts WHERE user_id = (SELECT id FROM users WHERE email = 'test@example.com');
```

---

#### Test 8: Chat System
1. Navigate to "Chat" section
2. Select a user from the list
3. Type a message: `Hello!`
4. Click send

**Expected Result**: Message sent and displayed

**Verify**:
- [ ] Message saved to database
- [ ] Appears in chat window
- [ ] Timestamp displayed
- [ ] Can send multiple messages

**SQL Verification**:
```sql
SELECT * FROM chat_messages WHERE from_user_id = (SELECT id FROM users WHERE email = 'test@example.com');
```

---

#### Test 9: Notifications
1. Perform actions that trigger notifications:
   - Add a workout
   - Purchase membership
   - Book appointment
2. Navigate to "Notifications" section

**Expected Result**: Notifications displayed

**Verify**:
- [ ] Notifications appear
- [ ] Can mark as read
- [ ] Can clear all
- [ ] Real-time updates work

**SQL Verification**:
```sql
SELECT * FROM notifications WHERE user_id = (SELECT id FROM users WHERE email = 'test@example.com');
```

---

### Phase 4: Booking System Testing 📅

#### Test 10: Public Booking (Not Logged In)
1. Logout or open incognito window
2. Navigate to: `http://localhost/WebProj/index.php#booking`
3. Fill booking form:
   - Name: `Guest User`
   - Email: `guest@example.com`
   - Phone: `09171234567`
   - Service: `Personal Training`
   - Date: Select a date
   - Time: Select a time
   - Coach: `Allynah Mendoza`
   - Notes: `First time booking`
4. Click "Book Now"

**Expected Result**: Booking confirmation message

**Verify**:
- [ ] Form submits successfully
- [ ] Confirmation message displayed
- [ ] Data processed by backend

---

#### Test 11: Logged-In User Booking
1. Login as customer
2. Navigate to: `http://localhost/WebProj/index.php#booking`
3. Fill and submit booking form

**Expected Result**: 
- Booking saved
- Notification created
- Workout entry added

**Verify**:
- [ ] Booking appears in "My Schedule"
- [ ] Notification received
- [ ] Email pre-filled from profile

**SQL Verification**:
```sql
SELECT * FROM workouts WHERE user_id = (SELECT id FROM users WHERE email = 'test@example.com') AND title LIKE '%Personal Training%';
SELECT * FROM notifications WHERE user_id = (SELECT id FROM users WHERE email = 'test@example.com') AND type = 'Booking';
```

---

### Phase 5: Admin Dashboard Testing 👨‍💼

#### Test 12: Admin Dashboard Access
1. Login as admin (`admin@versogym.com` / `Admin123!`)
2. Navigate to: `http://localhost/WebProj/admindash.php`

**Expected Result**: Admin dashboard displays

**Verify**:
- [ ] Total Members count correct
- [ ] Active Memberships count correct
- [ ] Total Revenue calculated
- [ ] Statistics cards display

---

#### Test 13: View Members
1. In admin dashboard, click "Members" tab
2. View member list

**Expected Result**: All registered customers displayed

**Verify**:
- [ ] Member list shows all customers
- [ ] Profile pictures display
- [ ] Membership status shown
- [ ] Join dates correct
- [ ] Can click "Details" link

**SQL Verification**:
```sql
SELECT COUNT(*) as total_customers FROM users WHERE role = 'customer';
```

---

#### Test 14: Admin Statistics
1. Check dashboard statistics

**Verify**:
- [ ] Total Members = Count of customers
- [ ] Active Memberships = Count where status='active'
- [ ] Total Revenue = Sum of completed payments

**SQL Verification**:
```sql
-- Total Members
SELECT COUNT(*) FROM users WHERE role = 'customer';

-- Active Memberships
SELECT COUNT(*) FROM memberships WHERE status = 'active';

-- Total Revenue
SELECT SUM(amount) FROM payments WHERE payment_status = 'Completed';
```

---

### Phase 6: Security Testing 🔒

#### Test 15: SQL Injection Prevention
1. Try SQL injection in login:
   - Email: `admin@versogym.com' OR '1'='1`
   - Password: `anything`

**Expected Result**: Login fails, no SQL error

**Verify**:
- [ ] Login rejected
- [ ] No database error
- [ ] Prepared statements working

---

#### Test 16: XSS Prevention
1. Try posting XSS in feed:
   - Content: `<script>alert('XSS')</script>`

**Expected Result**: Script tags escaped, no alert

**Verify**:
- [ ] Script not executed
- [ ] Content displayed as text
- [ ] HTML entities escaped

---

#### Test 17: Session Security
1. Login as customer
2. Copy session cookie
3. Logout
4. Try to access `customerdash.php` with old cookie

**Expected Result**: Redirected to login

**Verify**:
- [ ] Session destroyed on logout
- [ ] Cannot access dashboard
- [ ] Redirected to login page

---

#### Test 18: Password Security
1. Check database for password storage

**SQL Verification**:
```sql
SELECT email, password FROM users LIMIT 5;
```

**Verify**:
- [ ] Passwords are hashed (start with $2y$)
- [ ] No plain text passwords
- [ ] Bcrypt algorithm used

---

### Phase 7: Error Handling Testing ⚠️

#### Test 19: Invalid Login
1. Try login with wrong password
2. Try login with non-existent email

**Expected Result**: Error message displayed

**Verify**:
- [ ] "Invalid credentials" message
- [ ] No sensitive information leaked
- [ ] Redirected back to login

---

#### Test 20: Form Validation
1. Try submitting empty forms
2. Try invalid email formats
3. Try mismatched passwords

**Expected Result**: Validation errors shown

**Verify**:
- [ ] Required field validation works
- [ ] Email format validated
- [ ] Password match checked
- [ ] User-friendly error messages

---

#### Test 21: File Upload Validation
1. Try uploading non-image file as profile picture
2. Try uploading very large file

**Expected Result**: Upload rejected with error

**Verify**:
- [ ] File type validation works
- [ ] Size limits enforced
- [ ] Error message displayed

---

### Phase 8: Performance Testing ⚡

#### Test 22: Page Load Times
1. Measure load times for each page:
   - index.php
   - login.php
   - register.php
   - customerdash.php
   - admindash.php

**Expected Result**: All pages load under 3 seconds

**Verify**:
- [ ] Fast initial load
- [ ] AJAX requests quick
- [ ] Images optimized
- [ ] No unnecessary queries

---

#### Test 23: Database Query Optimization
1. Enable MySQL slow query log
2. Perform various operations
3. Check for slow queries

**Verify**:
- [ ] Indexes used properly
- [ ] No N+1 query problems
- [ ] Efficient JOINs
- [ ] Proper LIMIT clauses

---

### Phase 9: Cross-Browser Testing 🌐

#### Test 24: Browser Compatibility
Test on multiple browsers:
- Chrome
- Firefox
- Safari
- Edge

**Verify**:
- [ ] Layout correct on all browsers
- [ ] JavaScript works
- [ ] Forms submit properly
- [ ] Styles render correctly

---

### Phase 10: Mobile Responsiveness 📱

#### Test 25: Mobile View
1. Open site on mobile device or use browser dev tools
2. Test all pages in mobile view

**Verify**:
- [ ] Responsive design works
- [ ] Navigation accessible
- [ ] Forms usable
- [ ] Images scale properly
- [ ] Touch interactions work

---

## 🐛 Common Issues & Solutions

### Issue 1: Database Connection Failed
**Solution**: Check `config.php` credentials match MySQL settings

### Issue 2: Session Not Working
**Solution**: Ensure session directory is writable
```bash
chmod 777 /tmp
```

### Issue 3: Images Not Uploading
**Solution**: Create uploads directory and set permissions
```bash
mkdir -p uploads/avatars uploads/feed_images
chmod 755 uploads -R
```

### Issue 4: Redirect Loop
**Solution**: Clear browser cookies and session data

### Issue 5: Google OAuth Not Working
**Solution**: Check Google API credentials in `config.php`

---

## 📊 Test Results Template

```
Test Date: ___________
Tester: ___________

Phase 1: Database Setup
[ ] Test 1: Import Database
[ ] Test 2: Verify Tables
[ ] Test 3: Verify Admin Account

Phase 2: Authentication
[ ] Test 4: Admin Login
[ ] Test 5: Customer Registration
[ ] Test 6: Customer Login

Phase 3: Customer Dashboard
[ ] Test 7: Profile Update
[ ] Test 8: Workout Scheduling
[ ] Test 9: Membership Purchase
[ ] Test 10: Feed Posts
[ ] Test 11: Chat System
[ ] Test 12: Notifications

Phase 4: Booking System
[ ] Test 13: Public Booking
[ ] Test 14: Logged-In Booking

Phase 5: Admin Dashboard
[ ] Test 15: Admin Access
[ ] Test 16: View Members
[ ] Test 17: Statistics

Phase 6: Security
[ ] Test 18: SQL Injection
[ ] Test 19: XSS Prevention
[ ] Test 20: Session Security
[ ] Test 21: Password Security

Phase 7: Error Handling
[ ] Test 22: Invalid Login
[ ] Test 23: Form Validation
[ ] Test 24: File Upload

Phase 8: Performance
[ ] Test 25: Page Load Times
[ ] Test 26: Query Optimization

Phase 9: Cross-Browser
[ ] Test 27: Browser Compatibility

Phase 10: Mobile
[ ] Test 28: Mobile Responsiveness

Overall Status: [ ] PASS [ ] FAIL
Notes: ___________
```

---

## ✅ Final Checklist

Before marking the project as complete:

- [ ] All database tables created
- [ ] Admin account accessible
- [ ] Customer registration works
- [ ] Login/logout functional
- [ ] Profile management works
- [ ] Workout scheduling operational
- [ ] Membership system functional
- [ ] Payment processing works
- [ ] Booking system operational
- [ ] Feed posts working
- [ ] Chat system functional
- [ ] Notifications displaying
- [ ] Admin dashboard shows data
- [ ] Security measures in place
- [ ] Error handling implemented
- [ ] Mobile responsive
- [ ] Cross-browser compatible
- [ ] Documentation complete

---

**Testing Complete!** 🎉

If all tests pass, the system is ready for use.
