# Training Enrollment System — Laboratory Documentation

**Course:** IPT – Integrative Programming and Technology
**Name:** [Your full name]
**Section:** [Your section]
**Date:** October 9, 2026
**GitHub repository:** [paste public repository URL here]

> **How to use this file:** paste it into a new Google Doc, take the screenshots listed in section 5 (steps are given for each), insert them under their headings, then set sharing to *Anyone with the link can view* and test the link in an incognito window.

---

## 1. Project Description

The Training Enrollment System is a multi-table web application built with PHP and PDO (MySQL). An organization offers training programs. Each **course** (for example "Web Development Fundamentals") has one or more scheduled **classes** with a schedule, an instructor and a limited number of slots. A single Administrator manages courses and classes, records students, enrolls them, cancels enrollments and reads reports.

The system prevents over-enrollment, prevents duplicate enrollments, and uses **database transactions** so that related changes either all succeed or all fail.

**Tables:** `students`, `courses`, `classes`, `enrollments` (junction table between students and classes), connected by foreign keys.

**Main techniques:** INNER JOIN / LEFT JOIN queries, transactions (`beginTransaction`, `commit`, `rollBack`), prepared statements, server-side validation, and dynamic search / filter / sort / pagination.

## 2. Setup Instructions

1. Copy the project to `C:\xampp\htdocs\training_enrollment\`.
2. Start Apache and MySQL in XAMPP.
3. In phpMyAdmin, import `sql/schema.sql` (creates `training_db` with sample data).
4. Copy `config/config.sample.php` to `config/config.php` and adjust credentials if needed.
5. Open `http://localhost/training_enrollment/`.

## 3. Design Patterns

**Singleton (`Database`).** The `Database` class extends PDO, has a private constructor and a static `getInstance()`. The whole application shares one connection, so connection settings are defined in one place and every statement of a transaction runs on the same connection.

**Repository (`EnrollmentRepository`).** Recording, enrolling and cancelling involve several tables and must be atomic. The repository hides that logic behind methods (`recordStudent`, `enroll`, `cancel`, `allWithDetails`) so the pages contain no SQL and the transaction code lives in one place.

**Supporting practices.** Constructor dependency injection (`new Course($db)`), one class per file, exceptions for business rules (`EnrollmentException`), and prepared statements everywhere.

## 4. Transaction Design

| Operation | Steps inside ONE transaction |
|---|---|
| `recordStudent()` | check slots → insert student → insert enrollment → decrement slots → commit (any failure → rollback) |
| `enroll()` | check slots + duplicate → insert enrollment → decrement slots → commit |
| `cancel()` | set status `cancelled` → increment slots → commit |

## 5. Screenshot Evidence

Use the sample data that comes with `schema.sql`. Capture the full browser window including the address bar.

### Requirement 1 — Course Management
*How:* open `admin/courses.php`.
**[Insert screenshot]**
*Caption:* Course Management page listing all courses with code, name, description and number of classes.

### Requirement 2 — Add / Edit Course form
*How:* fill the form (e.g. code `NETSEC`, name `Network Security Basics`) and click **Add course**; then click **Edit** on a course, change the description and update. Capture the filled form, then the updated list.
**[Insert screenshot of filled form]**
**[Insert screenshot of updated list]**
*Caption:* Add Course form filled in, followed by the course list showing the new / edited record.

### Requirement 3 — Class Management
*How:* open `admin/classes.php`.
**[Insert screenshot]**
*Caption:* Classes listed with their course, schedule, instructor, enrolled count and remaining slots.

### Requirement 4 — Student recording form
*How:* open `admin/students.php`, fill in name, email, phone and choose class **WEB-A** (note its remaining slots: 3) — capture **before** submitting.
**[Insert screenshot]**
*Caption:* Student recording form with name, email, phone and class dropdown.

### Requirement 5 — Successful recording
*How:* submit the form. Capture (a) the success message, (b) phpMyAdmin `students` table with the new row, (c) `enrollments` table with the new row, (d) `admin/classes.php` showing WEB-A with one fewer slot (3 → 2).
**[Insert screenshots]**
*Caption:* Confirmation message, new rows in `students` and `enrollments`, and the class slot count reduced by one.

### Requirement 6 — Enrollments list
*How:* open `admin/enrollments.php`.
**[Insert screenshot]**
*Caption:* Enrollment records joined with student, class and course details (4-table JOIN).

### Requirement 7 — Cancelled enrollment
*How:* on `admin/enrollments.php` click **Cancel** on the enrollment you just created and confirm. Capture the list (status `cancelled`) and `admin/classes.php` (WEB-A back to 3).
**[Insert screenshots]**
*Caption:* Enrollment with status `cancelled` and the class slot count restored.

### Requirement 8 — Rollback case
*How:* open `admin/students.php`, choose class **PY-A** (shows FULL, 0 slots), fill the form and submit. Capture the error message, then phpMyAdmin `students` and `enrollments` to show no new rows were added.
**[Insert screenshots]**
*Caption:* Recording into a full class shows "No slots available … transaction rolled back", and no partial rows exist in `students` or `enrollments`.

### Requirement 9 — Database evidence
*How:* in phpMyAdmin open `training_db` and take screenshots of the **Browse** tab for `students`, `enrollments` and `classes`.
**[Insert screenshots]**
*Caption:* Saved rows in the `students`, `enrollments` and `classes` tables.

## 6. Submission Links

* **Google Document URL:** [paste link — Anyone with the link can view]
* **GitHub Repository URL:** [paste public repository link]
