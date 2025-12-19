# 🎓 Student Promotion - Quick Reference Card

## 📍 Access

**Navigation:** Students → 🎓 Student Promotion  
**Permissions:** Super Admin & Admin only

---

## ⚡ Quick Start (3 Steps)

### 1️⃣ **Select Years**

```
From: [Current Year] → To: [Next Year]
Batch Name: "2024 Year-End Promotion"
```

### 2️⃣ **Map Classes**

```
Grade 1  →  Grade 2
Grade 2  →  Grade 3
...
Grade 12 →  Alumni ✓
```

### 3️⃣ **Execute**

```
Review → Confirm → Done! ✓
```

---

## 🎯 Common Actions

| Action                   | How To                             |
| ------------------------ | ---------------------------------- |
| **Promote entire grade** | Map class → next class             |
| **Graduate students**    | Map final class → Alumni           |
| **Retain students**      | Map class → Retain in Same Class   |
| **Undo promotion**       | Recent Batches → Rollback button   |
| **View history**         | Student Profile → Academic History |

---

## 🚨 Important Notes

### ✅ **Before Promotion:**

- [ ] Create next academic year first
- [ ] Backup database
- [ ] Verify student data is current
- [ ] All students have status = "Active"

### ⚠️ **During Promotion:**

- Only "Active" students are promoted
- Preview shows ALL affected students
- Batch name helps identify operations
- Can't undo after data changes

### 🔄 **Rollback Rules:**

- Only works on recent promotions
- One rollback per batch
- Don't rollback if new data entered
- Creates audit trail

---

## 📊 What Gets Updated

### **For Promoted Students:**

✓ Class changed to next grade  
✓ History record created  
✓ Status remains "Active"

### **For Graduating Students:**

✓ Status changed to "Alumni"  
✓ Class preserved (final class)  
✓ History record created

### **For Retained Students:**

✓ Class stays the same  
✓ History record created  
✓ Status remains "Active"

---

## 🎨 Status Colors

| Status          | Color     | Icon | Meaning               |
| --------------- | --------- | ---- | --------------------- |
| **Promoted**    | 🟢 Green  | ⬆️   | Moved to next grade   |
| **Retained**    | 🟡 Yellow | 🔄   | Repeated same grade   |
| **Graduated**   | 🟣 Purple | 🎓   | Completed school      |
| **Transferred** | 🔵 Blue   | ➡️   | Moved to other school |

---

## 🔍 Troubleshooting

| Problem                | Solution                           |
| ---------------------- | ---------------------------------- |
| No students in preview | Check students are "Active" status |
| Can't rollback         | Batch already rolled back          |
| Student count is 0     | Verify class assignments           |
| Migration error        | Check if tables already exist      |

---

## 📞 Need Help?

1. Read: `PROMOTION_SYSTEM_GUIDE.md`
2. Check: `IMPLEMENTATION_SUMMARY.md`
3. Verify: Database migration ran successfully
4. Test: Try with small batch first

---

## 💾 Database Tables

- `student_academic_history` - Student progression records
- `promotion_batches` - Bulk operation tracking

---

## 🎯 Best Practice Workflow

```
1. End of Academic Year
   ↓
2. Create Next Year (Academic Years page)
   ↓
3. Go to Student Promotion
   ↓
4. Select Years (Current → Next)
   ↓
5. Map All Classes
   ↓
6. Review Preview Carefully
   ↓
7. Execute Promotion
   ↓
8. Verify Sample Students
   ↓
9. Done! ✓
```

---

## 📈 Time Savings

| Method                  | Time for 500 Students |
| ----------------------- | --------------------- |
| **Manual (one-by-one)** | ~8 hours              |
| **Promotion System**    | ~5 minutes            |
| **Savings**             | **95% faster!**       |

---

**Version:** 1.0  
**Last Updated:** 2025-12-16
