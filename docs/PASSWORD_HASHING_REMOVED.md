# Password Hashing Removal - Development Mode

## Summary

All password hashing has been removed from the system for development purposes. Passwords are now stored and compared as plain text.

## Changes Made

### 1. Authentication System

**File:** `includes/auth_functions.php`

- Already configured to use plain text password comparison
- Line 42: `if ($password == $row['password_hash'])`

### 2. Admin Actions

**File:** `modules/settings/admin_actions.php`

- Change Password: Stores new password as plain text (line 49)
- Change Username: Verifies password using plain text comparison (lines 87-90)
- Add Admin: Stores password as plain text (line 146)
- Edit Admin: Updates password as plain text (line 191)

### 3. Staff Management

**File:** `modules/staff/add.php`

- Stores passwords as plain text (lines 24-25, 33)

**File:** `modules/staff/edit.php`

- Updates passwords as plain text (lines 57-60)

### 4. Database Scripts

**File:** `database_updates/create_superadmin.php`

- Updated to store plain text password instead of hashed
- Creates/updates superadmin user with password: 123

**File:** `check_admin.php`

- Updated to store plain text password instead of hashed
- Creates/updates admin user with password: password123

## Current User Credentials

### Super Admin Users

1. **Username:** admin

   - **Password:** password123
   - **Role:** super_admin

2. **Username:** superadmin
   - **Password:** 123
   - **Role:** super_admin

## Security Warning

⚠️ **IMPORTANT:** This configuration is for DEVELOPMENT ONLY!

- Never use plain text passwords in production
- All passwords are visible in the database
- This makes the system extremely vulnerable to security breaches

## Files Modified

1. `database_updates/create_superadmin.php` - Removed password hashing
2. `check_admin.php` - Removed password hashing
3. All other authentication files were already configured for plain text passwords

## Database Column

The `password_hash` column in the `users` table now stores plain text passwords instead of bcrypt hashes.

## Testing

Both admin accounts have been updated with plain text passwords:

- Login with `admin` / `password123`
- Login with `superadmin` / `123`
