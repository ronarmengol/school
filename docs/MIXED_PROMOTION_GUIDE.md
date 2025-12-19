# 🔄 Handling Mixed Promotions - Workflow Guide

## Scenario

You have **Grade 5A** with 30 students:

- 27 students should be promoted to Grade 6A
- 3 students need to repeat Grade 5A

## Current System Limitation

The promotion wizard works at the **class level**, meaning all students in a class get the same action (promote, retain, or graduate).

---

## ✅ **Recommended Workflow**

### **Method 1: Temporary Status Change (Easiest)**

#### **Step 1: Mark Students for Retention**

Before running the promotion wizard:

1. Go to **Students** list
2. Find the 3 students who will repeat
3. Click **Edit** for each student
4. Change their **Status** to `Suspended` (temporarily)
5. Save each student

**Why?** The promotion wizard only processes students with `Active` status.

#### **Step 2: Run Promotion**

1. Go to **Student Promotion**
2. Map **Grade 5A → Grade 6A**
3. The 27 active students will be promoted
4. The 3 suspended students will be skipped

#### **Step 3: Restore Retained Students**

After promotion completes:

1. Go to **Students** list
2. Filter by `Suspended` status
3. Edit each of the 3 students:
   - Change **Status** back to `Active`
   - Verify **Class** is still Grade 5A
4. Save

**Result:**

- ✅ 27 students now in Grade 6A
- ✅ 3 students remain in Grade 5A
- ✅ All students have Active status

---

### **Method 2: Temporary Class Move**

#### **Step 1: Create Temporary Holding Class**

1. Go to **Classes**
2. Create a temporary class: "Grade 5 - Retained"

#### **Step 2: Move Students to Holding Class**

1. Edit the 3 students who will repeat
2. Change their **Class** to "Grade 5 - Retained"
3. Save

#### **Step 3: Run Promotion**

1. Map **Grade 5A → Grade 6A** (promotes 27 students)
2. Map **Grade 5 - Retained → Retain in Same Class** (keeps 3 students)

#### **Step 4: Move Students Back**

After promotion:

1. Edit the 3 students
2. Change **Class** from "Grade 5 - Retained" to "Grade 5A"
3. Delete the temporary holding class

---

### **Method 3: Two-Phase Promotion**

#### **Phase 1: Promote the Majority**

1. Temporarily move 3 students to different class
2. Promote Grade 5A → Grade 6A
3. Move 3 students back to Grade 5A

#### **Phase 2: Handle Retained Students**

1. Run a second promotion batch
2. Map Grade 5A → Retain in Same Class
3. Only the 3 students will be affected

---

## 🎯 **Quick Reference Table**

| Method              | Pros                     | Cons                    | Best For                       |
| ------------------- | ------------------------ | ----------------------- | ------------------------------ |
| **Status Change**   | Simple, no extra classes | Manual restore needed   | Small numbers (1-5 students)   |
| **Temporary Class** | Clean separation         | Requires class creation | Medium numbers (5-15 students) |
| **Two-Phase**       | Most organized           | More steps              | Large numbers (15+ students)   |

---

## 📝 **Step-by-Step Example**

### **Scenario:** Grade 5A (30 students) → 3 repeat, 27 promote

**Using Status Change Method:**

```
1. Before Promotion:
   - Edit John Doe → Status: Suspended
   - Edit Jane Smith → Status: Suspended
   - Edit Bob Jones → Status: Suspended

2. Run Promotion:
   - Grade 5A → Grade 6A
   - Result: 27 students promoted (only Active ones)

3. After Promotion:
   - Edit John Doe → Status: Active (still in Grade 5A)
   - Edit Jane Smith → Status: Active (still in Grade 5A)
   - Edit Bob Jones → Status: Active (still in Grade 5A)

4. Final Result:
   - Grade 6A: 27 students ✓
   - Grade 5A: 3 students ✓
```

---

## ⚠️ **Important Notes**

### **Status Field Behavior:**

- `Active` = Will be included in promotion
- `Suspended` = Will be skipped by promotion
- `Alumni` = Already graduated
- `Transferred` = Moved to another school

### **Class Field Behavior:**

- Students are promoted based on their **current_class_id**
- If you map "Grade 5A → Grade 6A", only students currently in Grade 5A are affected

### **Academic History:**

- All promotions are recorded in `student_academic_history`
- Retained students will have a "Retained" record when you map their class to "Retain in Same Class"

---

## 🔮 **Future Enhancement Idea**

For a more advanced solution, we could add:

### **Individual Student Selection (Future Feature)**

```
Step 2.5: Select Students
- Show all students in each class
- Checkboxes to select who gets promoted
- Unchecked students automatically retained
```

This would require:

- New UI for student selection
- Modified API to handle individual student IDs
- More complex mapping structure

**For now, the Status Change method is the most practical solution.**

---

## 💡 **Pro Tips**

1. **Document Your Decisions:**

   - Keep a list of retained students
   - Note reasons for retention
   - Add remarks in student records

2. **Communicate with Teachers:**

   - Verify retention decisions before promotion
   - Double-check student lists
   - Get approval from administration

3. **Test First:**

   - Use the database reset feature
   - Test with sample data
   - Verify the workflow works

4. **Backup Before Promotion:**
   - Always backup database
   - Can rollback if needed
   - Better safe than sorry

---

## 🎓 **Real-World Example**

**St. Mary's School - Grade 8 Promotion:**

**Classes:**

- Grade 8A: 35 students (2 repeat)
- Grade 8B: 32 students (1 repeat)
- Grade 8C: 30 students (0 repeat)

**Workflow:**

```
1. Mark for retention:
   - 8A: Mark 2 students as Suspended
   - 8B: Mark 1 student as Suspended

2. Run promotion:
   - Grade 8A → Grade 9A (33 promoted)
   - Grade 8B → Grade 9B (31 promoted)
   - Grade 8C → Grade 9C (30 promoted)

3. Restore retained:
   - 2 students in 8A → Active
   - 1 student in 8B → Active

4. Result:
   - Grade 9: 94 students
   - Grade 8 (repeating): 3 students
```

---

## 📞 **Need Help?**

If you're unsure about the process:

1. Start with a small test (1-2 students)
2. Use the database reset feature to practice
3. Document your workflow for future years
4. Consider creating a checklist

---

**Remember:** The key is to use the **Status** field to control which students are included in the promotion!

---

**Created:** 2025-12-16  
**Version:** 1.0
