# Student Promotion System - Setup Guide

## 📋 Overview

The Student Promotion System allows administrators to efficiently manage year-end student transitions, including:

- Bulk promotion of students to next grade levels
- Automatic alumni conversion for graduating students
- Academic history tracking
- Rollback capability for mistakes

## 🚀 Installation Steps

### 1. Run Database Migration

First, you need to add the required database tables:

```bash
# Navigate to the database_updates folder
cd "c:\Apache24\htdocs\ONGOING PROJECTS\school\database_updates"

# Run the migration script
php run_migration.php
```

Or manually execute the SQL file in phpMyAdmin:

- Open `database_updates/add_student_history.sql`
- Copy the contents
- Run in phpMyAdmin SQL tab

### 2. Verify Installation

After running the migration, verify these tables exist:

- `student_academic_history` - Tracks student class history per year
- `promotion_batches` - Records bulk promotion operations

### 3. Access the Promotion System

Navigate to: **Students → 🎓 Student Promotion** in the sidebar

## 📖 How to Use

### Year-End Promotion Workflow

#### **Step 1: Select Academic Years**

1. Choose the **current academic year** (from)
2. Choose the **next academic year** (to)
3. Optionally name the promotion batch (e.g., "2024 Year-End Promotion")

#### **Step 2: Class Mapping**

For each class, select what happens to students:

- **Promote to [Class]** - Move students to next grade level
- **Mark as Alumni** - For graduating classes (e.g., Grade 12 → Alumni)
- **Retain in Same Class** - For students repeating the grade

The system shows student counts for each class to help you plan.

#### **Step 3: Review & Confirm**

- Review the complete list of students and their actions
- Verify all mappings are correct
- Click "Execute Promotion" to proceed

#### **Step 4: Complete**

- System processes all promotions
- Academic history is recorded
- Student records are updated
- Batch is saved for potential rollback

## 🔄 Rollback Feature

If you make a mistake, you can rollback a promotion:

1. Scroll to **Recent Promotion Batches**
2. Find the batch you want to undo
3. Click **Rollback** button
4. Confirm the action

**What happens during rollback:**

- Students promoted to new classes → returned to original classes
- Students marked as Alumni → restored to Active status
- Academic history records → deleted
- Batch marked as "ROLLED BACK"

⚠️ **Important:** Only rollback immediately after promotion. Don't rollback if:

- New data has been entered for the new year
- Significant time has passed
- Students have been manually edited

## 📊 Academic History Tracking

Every promotion creates a permanent record in `student_academic_history`:

- Student ID
- Academic Year
- Class they were in
- Final status (Promoted/Retained/Graduated)
- Who performed the promotion
- Date of promotion

This allows you to:

- View complete student academic journey
- Generate historical reports
- Track retention rates
- Audit promotion decisions

## 💡 Best Practices

### Before Promotion

1. ✅ Create the new academic year first
2. ✅ Verify all student data is up-to-date
3. ✅ Backup your database
4. ✅ Test with a small batch first (optional)

### During Promotion

1. ✅ Double-check class mappings
2. ✅ Review the student preview carefully
3. ✅ Use descriptive batch names
4. ✅ Document any special cases

### After Promotion

1. ✅ Verify student records are correct
2. ✅ Check a few students manually
3. ✅ Keep the batch record for audit trail
4. ✅ Only rollback if absolutely necessary

## 🎯 Common Scenarios

### Scenario 1: Standard Year-End Promotion

**Example:** Grade 1-11 students move up one grade, Grade 12 graduates

**Steps:**

1. Map Grade 1 → Grade 2
2. Map Grade 2 → Grade 3
3. ... continue for all grades
4. Map Grade 12 → Alumni
5. Execute promotion

### Scenario 2: Some Students Repeat

**Example:** Most students promoted, but some retained

**Steps:**

1. First, manually change retained students to "Suspended" or different status
2. Run promotion (only "Active" students are promoted)
3. Manually update retained students back to "Active" in their current class

### Scenario 3: Mid-Year Correction

**Example:** Realized a student was promoted incorrectly

**Steps:**

1. Use the rollback feature if promotion was recent
2. OR manually edit the student's class in Students → Edit
3. Add a note in remarks for audit trail

## 🔧 Troubleshooting

### Problem: Migration fails

**Solution:** Check if tables already exist. Drop them first if re-running.

### Problem: No students showing in preview

**Solution:** Ensure students have status = "Active" and are assigned to classes.

### Problem: Rollback button not working

**Solution:** Check if batch is already rolled back. Each batch can only be rolled back once.

### Problem: Student count is 0 for all classes

**Solution:** Verify students are assigned to the selected academic year's classes.

## 📞 Support

For issues or questions:

1. Check this guide first
2. Verify database migration ran successfully
3. Check browser console for JavaScript errors
4. Review server error logs

---

**Created:** 2025-12-16
**Version:** 1.0
