# Training Enrollment System

A multi-table PHP + PDO web application for the **IPT – Integrative Programming and Technology** laboratory activity. A single administrator registers training courses, schedules classes with limited slots, records and enrolls students, cancels enrollments and views reports. Every multi-step operation is protected by a **database transaction**, so slot counts and enrollment rows never go out of sync.

## Features

| Area | What it does |
|---|---|
| Courses | Add / edit / delete courses; search, sort and paginate the list |
| Classes | Schedule classes per course (schedule, instructor, slots); filter by course; shows enrolled count and remaining slots |
| Record Student | One form inserts the student, inserts the enrollment and decrements the slot **in one transaction** |
| Enroll | Enroll an existing student (rejects full classes and duplicate active enrollments) |
| Enrollments | 4-table JOIN list (enrollments + students + classes + courses) with search, status/course/class filters, sorting, pagination and **Cancel** |
| Cancel | Marks the enrollment `cancelled` and restores the slot **in one transaction** |
| Reports | Aggregate queries (GROUP BY, LEFT JOIN) per course, per class and per student |

## Requirements

* PHP 7.4 or newer with the `pdo_mysql` extension (tested on PHP 8.3)
* MySQL 5.7+ / MariaDB 10.3+ (InnoDB)
* XAMPP / WAMP / any Apache + PHP setup

## Setup (XAMPP on Windows)

1. Copy this folder to `C:\xampp\htdocs\training_enrollment\`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open phpMyAdmin (`http://localhost/phpmyadmin`) → **Import** → choose `sql/schema.sql` → **Go**.
   This creates the `training_db` database, the four tables and sample data.
   (`training_db.sql` in the project root is the same database export.)
4. Create your local config: copy `config/config.sample.php` to `config/config.php` and edit host, user and password if they differ from the XAMPP defaults (`root` / empty password).
   `config/config.php` is in `.gitignore`, so your password is never pushed to GitHub.
5. Open **http://localhost/training_enrollment/** in your browser.

## Folder structure

```
training_enrollment/
|-- config/        db.php (creates the shared connection), config.sample.php
|-- classes/       Database, Pet, Student, Course, ClassSection, EnrollmentRepository,
|                  EnrollmentException, Paginator, Report   (one class per file)
|-- admin/         courses, classes, students, enroll, enrollments, reports
|-- includes/      header.php, footer.php, helpers.php
|-- css/           style.css
|-- sql/           schema.sql (schema + sample data)
|-- training_db.sql  database export
`-- index.php      landing page
```

Pages in `admin/` only handle the request and render HTML; all SQL lives in the classes.

## Database design

```
courses 1 ──── * classes 1 ──── * enrollments * ──── 1 students
```

* `enrollments` is the junction table that resolves the many-to-many relationship between students and classes.
* Foreign keys: `classes.course_id → courses`, `enrollments.student_id → students`, `enrollments.class_id → classes`.
* `courses.course_code` is `UNIQUE`.
* `classes.slots` stores the **remaining** slots. Invariant: `remaining slots + active enrollments = class capacity`.

## Design patterns used and why

### Singleton — `Database`
`Database extends PDO` has a private constructor and a static `getInstance()`. Every page and class receives the **same single connection**, so there is exactly one place where the DSN, error mode (`ERRMODE_EXCEPTION`) and fetch mode are configured. Because a transaction belongs to a connection, using one shared connection also guarantees that all statements of one operation run inside the same transaction. `Database` also offers reusable CRUD helpers (`insert`, `update`, `delete`, `getRow`, `getRows`) that always use prepared statements.

### Repository — `EnrollmentRepository`
Enrolling a student touches three tables (`students`, `enrollments`, `classes`). That coordination is business logic, not page logic, so it is hidden behind methods such as `recordStudent()`, `enroll()`, `cancel()` and `allWithDetails()`. The pages just call one method and show the result; they contain no SQL. This keeps the transaction code in one testable place and lets the pages stay simple.

### Supporting ideas
* **Dependency injection** – every data class receives the `PDO` object through its constructor (`new Course($db)`), instead of creating its own connection.
* **Transaction script** – each repository method is one atomic unit of work: `beginTransaction()` → checks → writes → `commit()`, and `rollBack()` on any failure.
* **Exceptions for business rules** – `EnrollmentException` carries user-safe messages ("No slots available…"). Genuine database errors stay `PDOException`, are logged with `error_log()` and shown to the user only as a generic message.

## How the transactions work

**`recordStudent()`** — `beginTransaction` → lock and check the class still has slots → reject duplicate email → `INSERT students` → `INSERT enrollments` → guarded `UPDATE classes SET slots = slots - 1 WHERE slots > 0` → `commit`. If any step throws, `rollBack` undoes everything, so a full class leaves **no partial rows** in `students` or `enrollments`.

**`cancel()`** — `beginTransaction` → lock the enrollment row, require status `active` → `UPDATE enrollments SET status = 'cancelled'` → `UPDATE classes SET slots = slots + 1` → `commit`. Cancelling twice is rejected, so a slot can never be restored twice.

Concurrency: `SELECT … FOR UPDATE` locks the row on MySQL/InnoDB, and the guarded `UPDATE … WHERE slots > 0` means the slot count can never go negative even if two administrators click at the same moment.

## Security and validation

* **PDO prepared statements only**; user input is never concatenated into SQL. Sort columns are chosen from a whitelist, and page numbers / limits are cast to integers.
* **Server-side validation** for every form (required fields, length limits, email format, phone format, numeric ranges, valid foreign-key ids, unique course code).
* **Output escaping** with `htmlspecialchars` (`e()`) on all printed data (prevents XSS).
* **CSRF tokens** on every POST form. Delete and Cancel are POST buttons, not GET links, so they cannot be triggered by a link or image.
* **Post/Redirect/Get** after successful saves, so refreshing the page never re-submits a form.
* Referential integrity: a course with classes, or a class with enrollment records, cannot be deleted (friendly message instead of a raw FK error).
* No secrets in the repository: real credentials go in `config/config.php`, which is git-ignored (`config/config.sample.php` is provided).

## Professional UI / UX Design

* **Modern Design Tokens**: Inter typography, deep slate navy navigation bar (`#0f172a`), refined cards, soft shadows, and clean border radii.
* **Proportioned Multi-Column Layouts**: Responsive 2-column grids for Course and Class management, keeping forms easily accessible alongside data tables.
* **Interactive Dashboard**: KPI stat cards, a 6-module management action hub, and real-time class capacity progress meters.
* **Visual Status Indicators**: Color-coded badges for active, cancelled, and full sections with live indicator dots.
* **Responsive Mobile Navigation**: Collapsible drawer menu for mobile and tablet screens.
* **Lightweight Embedded SVG Icons**: Zero external icon font dependencies, rendering crisply and fast offline.

## Repository Submission

Repository URL: [https://github.com/bea-blue/IPT10_laboratory_multitable_pdo.git](https://github.com/bea-blue/IPT10_laboratory_multitable_pdo.git)

```bash
cd training_enrollment
git init
git add .
git commit -m "Complete Training Enrollment System with professional UI and transaction protection"
git branch -M main
git remote add origin https://github.com/bea-blue/IPT10_laboratory_multitable_pdo.git
git push -u origin main
```

