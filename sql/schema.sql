-- sql/schema.sql
-- Training Enrollment System: schema + sample data
-- Import with phpMyAdmin (Import tab) or:  mysql -u root < sql/schema.sql

CREATE DATABASE IF NOT EXISTS training_db;
USE training_db;

DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS students;

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(100) NOT NULL,
    email      VARCHAR(100),
    phone      VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE courses (
    course_id   INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE classes (
    class_id   INT AUTO_INCREMENT PRIMARY KEY,
    course_id  INT NOT NULL,
    class_code VARCHAR(20) NOT NULL,
    schedule   VARCHAR(100),
    instructor VARCHAR(100),
    slots      INT NOT NULL DEFAULT 0,
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
);

CREATE TABLE enrollments (
    enrollment_id   INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT NOT NULL,
    class_id        INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status          VARCHAR(20) DEFAULT 'active',
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (class_id)   REFERENCES classes(class_id)
);

-- ---------------------------------------------------------------
-- Sample data
-- NOTE: classes.slots holds the REMAINING slots. The sample rows are
-- consistent: remaining slots = capacity - active enrollments.
-- ---------------------------------------------------------------
INSERT INTO courses (course_code, course_name, description) VALUES
 ('WEBDEV', 'Web Development Fundamentals', 'HTML, CSS, JavaScript and PHP basics for building dynamic web pages.'),
 ('DBMS',   'Database Design with MySQL',   'Relational modelling, normalization, SQL joins and transactions.'),
 ('PYDATA', 'Python for Data Analysis',     'Python, pandas and basic data visualization.'),
 ('UIUX',   'UI/UX Design Essentials',      'User research, wireframing and prototyping.');

INSERT INTO classes (course_id, class_code, schedule, instructor, slots) VALUES
 (1, 'WEB-A', 'Mon & Wed 9:00-11:00 AM',  'Ms. Reyes',      3),  -- capacity 5, 2 active
 (1, 'WEB-B', 'Sat 1:00-5:00 PM',         'Mr. Santos',     5),  -- capacity 5, 0 active (1 cancelled)
 (2, 'DB-A',  'Tue & Thu 1:00-3:00 PM',   'Mr. Dela Cruz',  1),  -- capacity 3, 2 active
 (3, 'PY-A',  'Fri 9:00 AM-12:00 PM',     'Ms. Garcia',     0),  -- capacity 2, 2 active  => FULL
 (4, 'UX-A',  'Sun 10:00 AM-2:00 PM',     'Ms. Mendoza',    8);  -- capacity 8, 0 active

INSERT INTO students (full_name, email, phone) VALUES
 ('Juan Dela Cruz',  'juan.delacruz@example.com', '0917-111-2222'),
 ('Maria Clara',      'maria.clara@example.com',   '0917-333-4444'),
 ('Jose Rizal',       'jose.rizal@example.com',    '0917-555-6666'),
 ('Andres Bonifacio', 'andres.b@example.com',      '0917-777-8888'),
 ('Gabriela Silang',  'gabriela.s@example.com',    '0917-999-0000'),
 ('Emilio Aguinaldo', 'emilio.a@example.com',      '0918-123-4567');

INSERT INTO enrollments (student_id, class_id, status) VALUES
 (1, 1, 'active'),
 (2, 1, 'active'),
 (3, 3, 'active'),
 (4, 3, 'active'),
 (5, 4, 'active'),
 (6, 4, 'active'),
 (2, 2, 'cancelled');
