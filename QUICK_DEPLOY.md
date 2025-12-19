# 🚀 Quick Deployment Guide - InfinityFree

## Step 1: Get InfinityFree Database Credentials

1. Log in to **InfinityFree Control Panel**
2. Click **MySQL Databases**
3. Create a new database or use existing
4. **Copy these details:**
   ```
   Database Host: sql200.infinityfree.com (or similar)
   Database Name: if0_XXXXXXXX_school
   Database User: if0_XXXXXXXX
   Database Password: [your password]
   ```

## Step 2: Update Database Configuration

Open `config/database.php` and replace:

```php
// INFINITYFREE / PRODUCTION SETTINGS
define('DB_HOST', 'sql200.infinityfree.com');  // ← Your host
define('DB_USER', 'if0_XXXXXXXX');              // ← Your username
define('DB_PASS', 'your_password_here');        // ← Your password
define('DB_NAME', 'if0_XXXXXXXX_school');       // ← Your database name
```

## Step 3: Upload Files

### Using FileZilla (Recommended):

1. Download FileZilla from https://filezilla-project.org/
2. Get FTP credentials from InfinityFree Control Panel
3. Connect to your server
4. Upload ALL files to `htdocs` folder

### Files to Upload:

- ✅ All `.php` files
- ✅ `assets/` folder
- ✅ `config/` folder
- ✅ `includes/` folder
- ✅ `modules/` folder
- ✅ `auth/` folder
- ✅ `.htaccess` file
- ✅ `database.sql` (for import)

### Files to SKIP:

- ❌ `.git/` folder
- ❌ `README.md`
- ❌ Debug files (`debug_*.php`)
- ❌ `pre_deployment_check.php`
- ❌ `add_guardian_fields.php`

## Step 4: Import Database

1. In InfinityFree Control Panel, open **phpMyAdmin**
2. Select your database from left sidebar
3. Click **Import** tab
4. Click **Choose File** and select `database.sql`
5. Click **Go** button at bottom
6. Wait for import to complete

## Step 5: Set Permissions

Via FTP or File Manager, set these permissions:

- `uploads/` → **755** or **777**
- `assets/uploads/` → **755** or **777**

## Step 6: Test Your Site

1. Visit your domain: `http://yoursite.infinityfreeapp.com`
2. You should see the login page
3. Login with:
   - Username: `admin`
   - Password: `password`

## Step 7: Secure Your Site

1. **Change admin password immediately!**

   - Go to Settings → Admin Management
   - Update password

2. **Configure school settings:**

   - Settings → Update school name, logo, etc.

3. **Set up academic structure:**
   - Academic → Create years and terms
   - Classes → Add your classes

## 🆘 Troubleshooting

### "Database connection failed"

→ Double-check credentials in `config/database.php`

### "500 Internal Server Error"

→ Check `.htaccess` file and PHP error logs

### Images not loading

→ Verify `assets/` folder is uploaded

### Can't upload files

→ Set `uploads/` permission to 777

## 📞 Need Help?

- Read full guide: `DEPLOYMENT_GUIDE.md`
- InfinityFree Forum: https://forum.infinityfree.net
- Check error logs in control panel

---

**🎉 That's it! Your school management system is now live!**
