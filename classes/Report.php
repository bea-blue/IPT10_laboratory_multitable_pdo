<?php
// classes/Report.php
// Read-only summary queries used by admin/reports.php and the landing page.
class Report {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    // One-row overview of the whole system (scalar subqueries).
    public function totals() {
        return $this->db->query(
            "SELECT (SELECT COUNT(*) FROM students)  AS students,
                    (SELECT COUNT(*) FROM courses)   AS courses,
                    (SELECT COUNT(*) FROM classes)   AS classes,
                    (SELECT COUNT(*) FROM enrollments WHERE status = 'active')    AS active_enrollments,
                    (SELECT COUNT(*) FROM enrollments WHERE status = 'cancelled') AS cancelled_enrollments"
        )->fetch();
    }

    // Per course: number of classes, active enrollments, remaining slots.
    // LEFT JOINs keep courses/classes that have no enrollments yet.
    public function courseSummary() {
        return $this->db->query(
            "SELECT co.course_code, co.course_name,
                    COUNT(cs.class_id)             AS class_count,
                    COALESCE(SUM(cs.active_cnt), 0) AS enrolled,
                    COALESCE(SUM(cs.slots), 0)      AS slots_left
               FROM courses co
          LEFT JOIN (
                    SELECT c.class_id, c.course_id, c.slots,
                           COUNT(e.enrollment_id) AS active_cnt
                      FROM classes c
                 LEFT JOIN enrollments e
                        ON e.class_id = c.class_id AND e.status = 'active'
                  GROUP BY c.class_id, c.course_id, c.slots
                    ) cs ON cs.course_id = co.course_id
           GROUP BY co.course_id, co.course_code, co.course_name
           ORDER BY co.course_name"
        )->fetchAll();
    }

    // Per class: capacity = remaining slots + active enrollments.
    public function classSummary() {
        return $this->db->query(
            "SELECT c.class_code, co.course_name, c.schedule, c.instructor, c.slots AS slots_left,
                    SUM(CASE WHEN e.status = 'active'    THEN 1 ELSE 0 END) AS active_count,
                    SUM(CASE WHEN e.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
               FROM classes c
               JOIN courses co ON co.course_id = c.course_id
          LEFT JOIN enrollments e ON e.class_id = c.class_id
           GROUP BY c.class_id, c.class_code, co.course_name, c.schedule, c.instructor, c.slots
           ORDER BY co.course_name, c.class_code"
        )->fetchAll();
    }

    // Students and how many classes each is currently enrolled in
    // (LEFT JOIN: students with zero active enrollments still appear).
    public function studentSummary() {
        return $this->db->query(
            "SELECT s.full_name, s.email, COUNT(e.enrollment_id) AS active_classes
               FROM students s
          LEFT JOIN enrollments e ON e.student_id = s.student_id AND e.status = 'active'
           GROUP BY s.student_id, s.full_name, s.email
           ORDER BY active_classes DESC, s.full_name"
        )->fetchAll();
    }
}
