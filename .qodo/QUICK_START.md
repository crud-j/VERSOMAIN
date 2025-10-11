# VersoGym - Quick Start Guide

## ⚡ Get Started in 5 Minutes

### Step 1: Import Database (2 minutes)
```bash
# Open phpMyAdmin: http://localhost/phpmyadmin
# Click "Import" → Choose "database_setup.sql" → Click "Go"
```

**OR** use command line:
```bash
mysql -u root -p@l03e1t3 < database_setup.sql
```

### Step 2: Verify Setup (1 minute)
Open: http://localhost/WebProj/index.php

You should see the VersoGym landing page.

### Step 3: Login as Admin (1 minute)
1. Go to: http://localhost/WebProj/login.php
2. Enter:
   - **Email**: `admin@versogym.com`
   - **Password**: `Admin123!`
3. Click "Login"

✅ You should see the Admin Dashboard!

### Step 4: Create Customer Account (1 minute)
1. Logout (if logged in as admin)
2. Go to: http://localhost/WebProj/register.php
3. Fill the form:
   - Full Name: `Your Name`
   - Email: `your@email.com`
   - Password: `YourPassword123!`
4. Complete profile setup
5. Click "Save and Continue"

✅ You should see the Customer Dashboard!

---

## 🎯 What to Test First

### As Customer:
1. **Update Profile** → Profile section → Edit Profile
2. **Add Workout** → My Schedule → Add New Workout
3. **Post to Feed** → Feeds → Type something → Post
4. **Book Appointment** → Go to index.php → Scroll to Booking section

### As Admin:
1. **View Members** → Click "Members" tab
2. **Check Statistics** → Dashboard shows totals
3. **View Payments** → (Future feature)

---

## 🔑 Default Credentials

### Admin Account
```
Email: admin@versogym.com
Password: Admin123!
URL: http://localhost/WebProj/admindash.php
```

### Test Customer (Optional)
```
Email: customer@test.com
Password: Test123!
URL: http://localhost/WebProj/customerdash.php
```

---

## 🐛 Quick Troubleshooting

### Problem: "Database connection failed"
**Solution**: Check `config.php` line 10-13:
```php
define('DB_PASSWORD', '@l03e1t3'); // Update this
```

### Problem: "Cannot upload images"
**Solution**: Create directories:
```bash
mkdir uploads/avatars
mkdir uploads/feed_images
chmod 755 uploads -R
```

### Problem: "Session not working"
**Solution**: Clear browser cookies and try again

### Problem: "Page not found"
**Solution**: Ensure XAMPP is running and URL is correct:
```
http://localhost/WebProj/index.php
```

---

## 📱 Quick Feature Guide

### Customer Dashboard Features:

| Feature | Location | What It Does |
|---------|----------|--------------|
| **Profile** | Profile tab | Update personal info, upload photo |
| **Schedule** | My Schedule | Add/view workouts, calendar |
| **Membership** | Membership tab | Purchase plans, view status |
| **Chat** | Chat tab | Message other users |
| **Notifications** | Notifications tab | View alerts and updates |
| **Feeds** | Feeds tab | Post updates, view community |
| **Settings** | Settings tab | Account settings, preferences |

### Admin Dashboard Features:

| Feature | Location | What It Does |
|---------|----------|--------------|
| **Dashboard** | Dashboard tab | View statistics overview |
| **Members** | Members tab | View all registered users |
| **Analytics** | Dashboard cards | Total members, revenue, etc. |

---

## 🚀 Next Steps

1. ✅ Complete setup (you're done!)
2. 📖 Read [SETUP_INSTRUCTIONS.md](SETUP_INSTRUCTIONS.md) for details
3. 🧪 Follow [TESTING_GUIDE.md](TESTING_GUIDE.md) to test all features
4. 📚 Check [SYSTEM_DOCUMENTATION.md](SYSTEM_DOCUMENTATION.md) for technical details

---

## 💡 Pro Tips

1. **Use Chrome DevTools** to see AJAX requests (Network tab)
2. **Check PHP error log** if something doesn't work
3. **Use phpMyAdmin** to view database changes in real-time
4. **Test on mobile** using browser responsive mode
5. **Create multiple test accounts** to test chat feature

---

## ✅ Success Checklist

- [ ] Database imported successfully
- [ ] Can access index.php
- [ ] Admin login works
- [ ] Customer registration works
- [ ] Customer dashboard loads
- [ ] Can add a workout
- [ ] Can post to feed
- [ ] Can book appointment
- [ ] Admin dashboard shows data

**All checked?** 🎉 **You're ready to use VersoGym!**

---

## 📞 Need Help?

1. Check error logs: `error_log` in PHP
2. Review documentation files
3. Verify database connection
4. Ensure all files are in correct location
5. Check XAMPP is running (Apache + MySQL)

---

**Happy Gym Managing!** 💪
