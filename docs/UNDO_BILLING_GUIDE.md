# Undo Billing Feature - User Guide

## Overview

The **Undo Billing** feature allows you to reverse accidental billing operations for an entire class. This is particularly useful when you accidentally bill students for the wrong term.

## Safety Features

✅ **Protected Invoices**: Only invoices with **zero payments** can be deleted  
✅ **Audit Trail**: All undo operations are logged with user information and timestamp  
✅ **Confirmation Required**: A detailed confirmation modal prevents accidental deletions  
✅ **Transaction Safety**: All operations use database transactions for data integrity

## How to Use

### Step 1: Navigate to Billing Status Page

1. Go to **Finance** → **Billing Status** from the sidebar
2. Or navigate directly to: `http://localhost:8000/modules/finance/billing_status.php`

### Step 2: Select the Term

1. Use the **Academic Term** dropdown to select the term you want to review
2. Optionally, use the **Narrow to Class** dropdown to filter by specific class
3. Click **Filter Results**

### Step 3: Identify Fully Billed Classes

In the **Class-wise Billing Summary** table, look for classes that show:

- **Status**: "X / X Billed" (all students billed)
- **Progress bar**: 100% filled (green)
- **Action column**: Shows "Complete" badge with an **"Undo" button**

### Step 4: Click the Undo Button

1. Click the **Undo** button (red/orange with undo icon) next to the "Complete" badge
2. A confirmation modal will appear

### Step 5: Review the Confirmation Modal

The modal displays:

**⚠️ WARNING Section (Red)**

- Confirms the number of invoices to be deleted
- States that the action is PERMANENT
- Mentions audit logging

**✓ Safety Checks Section (Green)**

- Only invoices with zero payments will be deleted
- Invoices with payments will be protected
- All operations are logged for accountability

### Step 6: Confirm or Cancel

- Click **"Yes, Undo Billing"** to proceed with deletion
- Click **"Cancel"** to abort the operation

### Step 7: Review the Result

**If successful:**

- A green success toast appears: "Successfully undone billing for X student(s) in [Class Name]"
- The page automatically refreshes after 1.5 seconds
- The class will now show unbilled students again

**If there are payments:**

- A red error toast appears: "Cannot undo billing: X students have made payments..."
- No invoices are deleted
- You must reverse the payments first before undoing billing

## Example Scenario

**Problem**: You accidentally billed Grade 5A students for Term 3 instead of Term 2.

**Solution**:

1. Navigate to Billing Status page
2. Select **Term 3** from the dropdown
3. Find **Grade 5A** in the table
4. Click the **Undo** button next to "Complete"
5. Review the confirmation modal
6. Click **"Yes, Undo Billing"**
7. Wait for success message and page refresh
8. Now select **Term 2** and bill Grade 5A correctly

## Important Notes

⚠️ **Cannot Undo If Payments Exist**

- If any student in the class has made a payment, the undo operation will fail
- You must first reverse/refund all payments before undoing billing
- This protects financial data integrity

📋 **Audit Trail**

- Every undo operation is logged in the `billing_audit_log` table
- Logs include: class, term, number of invoices deleted, total amount, user who performed the action, and timestamp
- Administrators can query this table for accountability

🔒 **Permission Required**

- Only users with `super_admin`, `admin`, or `accountant` roles can undo billing
- Other users will not see the undo button

## Troubleshooting

### "Cannot undo billing: X students have made payments"

**Cause**: One or more students have made payments against their invoices.

**Solution**:

1. Navigate to the Payments page
2. Identify and reverse the payments for affected students
3. Return to Billing Status and try the undo operation again

### "No unpaid invoices found for this class and term"

**Cause**: All invoices have already been deleted or have payments.

**Solution**: This is expected if you've already undone the billing or if all invoices have payments.

### Undo button not visible

**Possible causes**:

1. Not all students are billed (some are still unbilled)
2. You don't have the required permissions
3. The class has no fee structure defined

**Solution**: Ensure all students are billed and you're logged in with appropriate permissions.

## Database Schema

The undo feature uses the following database table:

```sql
CREATE TABLE `billing_audit_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `action_type` enum('UNDO_BILLING','BULK_BILLING') NOT NULL,
  `class_id` int(11) NOT NULL,
  `term_id` int(11) NOT NULL,
  `invoices_affected` int(11) DEFAULT 0,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `performed_by` int(11) NOT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `details` text DEFAULT NULL,
  PRIMARY KEY (`log_id`)
);
```

## Viewing Audit Logs

To view undo billing history, run this SQL query:

```sql
SELECT
    bal.log_id,
    bal.action_type,
    CONCAT(c.class_name, ' ', c.section_name) as class,
    CONCAT(y.year_name, ' - ', t.term_name) as term,
    bal.invoices_affected,
    bal.total_amount,
    u.full_name as performed_by,
    bal.performed_at
FROM billing_audit_log bal
JOIN classes c ON bal.class_id = c.class_id
JOIN terms t ON bal.term_id = t.term_id
JOIN academic_years y ON t.academic_year_id = y.year_id
JOIN users u ON bal.performed_by = u.user_id
WHERE bal.action_type = 'UNDO_BILLING'
ORDER BY bal.performed_at DESC;
```

## Support

If you encounter any issues with the undo billing feature, please contact your system administrator.
