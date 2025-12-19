# School Management System - Deployment Guide for InfinityFree

## 📋 Pre-Deployment Checklist

### 1. **Database Configuration**

Before deploying, you need to update the database credentials in `config/database.php`:

1. Log in to your InfinityFree Control Panel
2. Go to **MySQL Databases**
3. Create a new database (or use an existing one)
4. Note down the following credentials:

   - **Database Host** (e.g., `sql200.infinityfree.com`)
   - **Database Username** (e.g., `if0_12345678`)
   - **Database Password** (your chosen password)
   - **Database Name** (e.g., `if0_12345678_school`)

5. Open `config/database.php` and replace the placeholders:
   ```php
   define('DB_HOST', 'sql200.infinityfree.com');  // Your actual host
   define('DB_USER', 'if0_XXXXXXXX');              // Your actual username
   define('DB_PASS', 'your_password_here');        // Your actual password
   define('DB_NAME', 'if0_XXXXXXXX_school');       // Your actual database name
   ```

### 2. **Import Database**

1. In InfinityFree Control Panel, go to **phpMyAdmin**
2. Select your database
3. Click **Import** tab
4. Upload the `database.sql` file from your project
5. Click **Go** to import

### 3. **File Upload**

You can upload files using:

- **FileZilla** (FTP Client) - Recommended
- **InfinityFree File Manager** (for small files)

#### Using FileZilla:

1. Download and install FileZilla
2. Get your FTP credentials from InfinityFree Control Panel
3. Connect to your server
4. Upload all files to the `htdocs` folder (or your domain's root folder)

**Important Files to Upload:**

- All PHP files
- `assets/` folder (CSS, JS, images)
- `config/` folder
- `includes/` folder
- `modules/` folder
- `uploads/` folder (create if doesn't exist)
- `.htaccess` file
- `database.sql` (for reference, but import via phpMyAdmin)

### 4. **Folder Permissions**

Set the following folder permissions (via FTP or File Manager):

- `uploads/` → **755** or **777** (for file uploads)
- `assets/uploads/` → **755** or **777**

### 5. **Test the Application**

1. Visit your domain (e.g., `http://yoursite.infinityfreeapp.com`)
2. You should see the login page
3. Default login credentials:
   - **Username:** `admin`
   - **Password:** `password` (or check your database for the actual password)

---

## 🔧 Post-Deployment Configuration

### 1. **Change Default Password**

Immediately after first login:

1. Go to **Settings** → **Admin Management**
2. Change the default admin password

### 2. **Configure School Settings**

1. Go to **Settings**
2. Update:
   - School Name
   - School Motto
   - Currency Symbol
   - Upload School Logo

### 3. **Set Up Academic Year and Terms**

1. Go to **Academic** → **Years & Terms**
2. Create your academic year
3. Add terms (e.g., Term 1, Term 2, Term 3)

### 4. **Add Classes**

1. Go to **Classes**
2. Add your school's classes and sections

---

## 🚨 Common Issues & Solutions

### Issue 1: "Database connection failed"

**Solution:**

- Verify database credentials in `config/database.php`
- Ensure database is created in InfinityFree
- Check if database is imported correctly

### Issue 2: "500 Internal Server Error"

**Solution:**

- Check `.htaccess` file syntax
- Verify file permissions
- Check PHP error logs in InfinityFree control panel

### Issue 3: Images/CSS not loading

**Solution:**

- Ensure `assets/` folder is uploaded
- Check file paths in code
- Verify folder permissions

### Issue 4: File upload not working

**Solution:**

- Set `uploads/` folder permission to **755** or **777**
- Check PHP upload limits in `.htaccess`

### Issue 5: Session timeout issues

**Solution:**

- InfinityFree has session limitations
- The app has a 5-minute timeout configured
- Users will be logged out after 5 minutes of inactivity

---

## 📊 InfinityFree Limitations to Be Aware Of

1. **Database Size:** Limited to 400MB
2. **File Storage:** Limited to 5GB
3. **Bandwidth:** Unlimited (but with fair usage policy)
4. **MySQL Connections:** Limited concurrent connections
5. **Cron Jobs:** Not available on free plan
6. **Email:** Limited email sending capabilities
7. **Sessions:** May expire faster than on paid hosting

---

## 🔒 Security Recommendations

### 1. **Enable HTTPS**

Once your site is live:

1. Get a free SSL certificate from InfinityFree
2. Uncomment the HTTPS redirect lines in `.htaccess`

### 2. **Regular Backups**

- Export database regularly via phpMyAdmin
- Download important files via FTP
- Store backups in a safe location

### 3. **Update Passwords**

- Change all default passwords
- Use strong passwords for all admin accounts

### 4. **Monitor Access**

- Regularly check user accounts
- Remove inactive users
- Monitor login attempts

---

## 📁 Files That Should NOT Be Uploaded

These files are for development only:

- `.git/` folder
- `README.md` (optional)
- Any local configuration files
- Development/debug scripts in `modules/finance/debug_*.php`
- `modules/students/add_guardian_fields.php` (migration script - run once then delete)

---

## 🎯 Performance Optimization

### 1. **Image Optimization**

- Compress images before uploading
- Use WebP format where possible
- Keep logo files under 500KB

### 2. **Database Optimization**

- Regularly clean old data
- Archive old academic years
- Remove test/dummy data

### 3. **Caching**

- The `.htaccess` file includes browser caching
- Static files will be cached for better performance

---

## 📞 Support

If you encounter issues:

1. Check InfinityFree documentation
2. Review error logs in control panel
3. Verify all configuration settings
4. Test locally first before deploying changes

---

## ✅ Deployment Checklist

- [ ] Database created in InfinityFree
- [ ] Database credentials updated in `config/database.php`
- [ ] Database imported via phpMyAdmin
- [ ] All files uploaded via FTP
- [ ] Folder permissions set correctly
- [ ] Application accessible via domain
- [ ] Login working with default credentials
- [ ] Default password changed
- [ ] School settings configured
- [ ] Academic year and terms created
- [ ] Classes added
- [ ] Test student added
- [ ] Test invoice generated
- [ ] File upload tested
- [ ] All modules tested

---

## 🚀 You're Ready to Go!

Your School Management System is now deployed on InfinityFree and ready to use!

**Next Steps:**

1. Add your students
2. Configure fee structures
3. Start taking attendance
4. Record exam results
5. Generate reports

Good luck with your school management! 🎓
