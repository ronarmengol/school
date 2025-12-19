# 🗑️ Database Reset Feature - Documentation

## Overview

The Database Reset feature allows super administrators to selectively clear sections of the database during testing and initial setup phases.

## Location

**Settings → General Tab → Database Reset Tools**

## Access Control

- **Only Super Admin** can access this feature
- Password verification required for all reset operations
- Double confirmation prompts for safety

## Available Reset Sections

### 1. **Students**

Removes:

- All student records
- Attendance records
- Academic history entries

**Use when:** Starting fresh with student data or testing student management features

---

### 2. **Classes & Subjects**

Removes:

- All classes
- All subjects
- Class-subject mappings

**Use when:** Restructuring class organization or testing class management

---

### 3. **Exams & Results**

Removes:

- All exams
- All exam results

**Use when:** Testing exam entry or clearing test exam data

---

### 4. **Finance**

Removes:

- Fee structures
- Student invoices
- Payment records

**Use when:** Testing fee management or clearing test financial data

---

### 5. **Academic Years & Terms**

Removes:

- All academic years
- All terms
- Promotion batches

**Use when:** Resetting academic calendar or testing year management

---

### 6. **Staff (Teachers & Admin)**

Removes:

- All teachers
- All admin users
- **Preserves:** Super admin account

**Use when:** Clearing test staff accounts or starting fresh with staff

---

### 7. **Reset Everything** 🔥

Resets all sections above in one operation.

**Preserves:** Only the super admin account

**Use when:** Complete database reset to initial state

---

## How to Use

### Step 1: Navigate

Go to **Settings → General Tab**

### Step 2: Select Sections

Check the boxes for sections you want to reset:

- Individual sections, OR
- "Reset Everything" for complete reset

### Step 3: Enter Password

Enter your super admin password for verification

### Step 4: Confirm

- Click "Execute Reset"
- Confirm first warning dialog
- Confirm second (final) warning dialog

### Step 5: Complete

- System deletes selected data
- Success message shows what was deleted
- Page reloads automatically

---

## Safety Features

### 🔒 **Security**

- Super admin access only
- Password verification required
- Cannot be accessed by regular admins

### ⚠️ **Double Confirmation**

- Two confirmation dialogs
- Clear warning messages
- Lists what will be deleted

### 🛡️ **Transaction Safety**

- All operations use database transactions
- Automatic rollback on errors
- Data integrity maintained

### 💾 **Preservation**

- Super admin account never deleted
- Settings preserved
- System structure intact

---

## What Gets Reset

### ✅ **Data Cleared:**

- User-entered records
- Generated data
- Test data
- Historical records

### ❌ **NOT Cleared:**

- Database structure (tables remain)
- Super admin account
- System settings
- Application files

---

## Auto-Increment Reset

After deletion, auto-increment counters are reset to 1 for:

- Student IDs
- Class IDs
- Exam IDs
- Invoice IDs
- All other ID sequences

This ensures clean numbering when adding new records.

---

## Use Cases

### **Testing Phase**

Reset specific sections to test features without affecting others:

```
Example: Reset only "Exams & Results" to test exam entry
```

### **Initial Setup**

Clear sample/test data before going live:

```
Example: Reset "Students" and "Finance" after testing
```

### **Development**

Quickly reset to known state during development:

```
Example: Reset "Everything" to start fresh
```

### **Data Migration**

Clear old data before importing new:

```
Example: Reset "Students" before CSV import
```

---

## Important Warnings

### ⚠️ **Permanent Deletion**

- Data cannot be recovered after reset
- No undo functionality
- Backup before resetting if needed

### ⚠️ **Production Use**

- **NOT recommended** for production systems
- Designed for testing/setup only
- Use with extreme caution

### ⚠️ **Cascading Effects**

Some resets affect related data:

- Resetting "Classes" affects students (they lose class assignment)
- Resetting "Academic Years" affects exams and fees
- Resetting "Students" affects all student-related data

---

## Best Practices

### ✅ **DO:**

- Backup database before major resets
- Use during testing/setup phases
- Reset specific sections when possible
- Verify what will be deleted
- Test with small sections first

### ❌ **DON'T:**

- Use in production without backup
- Reset without understanding impact
- Share super admin password
- Reset during active use
- Forget to verify selections

---

## Troubleshooting

### Problem: "Invalid password" error

**Solution:** Ensure you're entering the correct super admin password

### Problem: Reset button doesn't work

**Solution:** Check that at least one section is selected

### Problem: Partial reset occurred

**Solution:** Transaction rollback should prevent this. Check error message.

### Problem: Can't access feature

**Solution:** Only super admin role has access. Regular admins cannot use this.

---

## Technical Details

### Database Operations

```sql
-- Example for Students section
DELETE FROM students;
DELETE FROM student_academic_history;
DELETE FROM attendance;
ALTER TABLE students AUTO_INCREMENT = 1;
```

### Transaction Flow

1. Verify super admin role
2. Verify password
3. Begin transaction
4. Delete selected sections
5. Reset auto-increments
6. Commit transaction
7. Return success/error

### Error Handling

- All operations wrapped in try-catch
- Automatic rollback on any error
- Detailed error messages returned
- No partial deletions

---

## Security Considerations

### Access Control

- PHP role check: `check_role(['super_admin'])`
- Password verification before execution
- Session validation required

### Audit Trail

Consider logging reset operations:

- Who performed reset
- What sections were reset
- When it occurred
- How many records deleted

---

## Future Enhancements

Potential improvements:

- [ ] Backup creation before reset
- [ ] Audit log of reset operations
- [ ] Scheduled resets
- [ ] Export data before reset
- [ ] Restore from backup feature

---

## Example Workflow

### Scenario: Testing Student Promotion

```
1. Add test students and classes
2. Test promotion feature
3. Go to Settings → General
4. Select "Students" and "Academic Years"
5. Enter password
6. Execute reset
7. Start fresh testing
```

---

## Support

For issues:

1. Verify super admin access
2. Check password is correct
3. Review browser console for errors
4. Check server error logs
5. Ensure database connection is active

---

**Remember:** This is a powerful tool. Use responsibly! 🚀

---

**Created:** 2025-12-16  
**Version:** 1.0  
**Access Level:** Super Admin Only
