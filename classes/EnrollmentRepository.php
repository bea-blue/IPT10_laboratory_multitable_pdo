<?php
// classes/EnrollmentRepository.php
// Data-access class that records students, enrollments and slot updates
// inside a single database transaction (Repository pattern).
//
// Business-rule failures (no slots, duplicate enrollment, unknown class ...)
// throw EnrollmentException. Real database errors surface as PDOException.
// In BOTH cases the transaction is rolled back first, so the tables never
// contain half-finished work.
require_once __DIR__ . '/EnrollmentException.php';
require_once __DIR__ . '/Paginator.php';

class EnrollmentRepository {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    // Record a NEW student AND enroll them into a class.
    // Returns the new student_id. Throws if the class is full / missing.
    public function recordStudent($full_name, $email, $phone, $class_id) {
        $email = ($email === null || $email === '') ? null : $email;
        $phone = ($phone === null || $phone === '') ? null : $phone;

        $this->db->beginTransaction();                                   // 1
        try {
            $this->assertSlotsAvailable($class_id);                      // 2

            if ($email !== null) {
                $stmt = $this->db->prepare("SELECT student_id FROM students WHERE email = :email");
                $stmt->execute([':email' => $email]);
                if ($stmt->fetch()) {
                    throw new EnrollmentException(
                        'A student with this email already exists. Use the Enroll page for existing students.');
                }
            }

            $stmt = $this->db->prepare(                                  // 3
                "INSERT INTO students (full_name, email, phone)
                 VALUES (:full_name, :email, :phone)");
            $stmt->execute([':full_name' => $full_name, ':email' => $email, ':phone' => $phone]);
            $student_id = (int) $this->db->lastInsertId();               // 4

            $this->insertEnrollment($student_id, $class_id);             // 5
            $this->takeSlot($class_id);                                  // 6

            $this->db->commit();                                         // 7
            return $student_id;
        } catch (Throwable $e) {
            $this->rollBackQuietly();                                    // 7 (otherwise)
            throw $e;
        }
    }

    // Enroll an EXISTING student into a class.
    // Returns the new enrollment_id.
    public function enroll($student_id, $class_id) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT student_id FROM students WHERE student_id = :id");
            $stmt->execute([':id' => $student_id]);
            if (!$stmt->fetch()) {
                throw new EnrollmentException('The selected student does not exist.');
            }

            $this->assertSlotsAvailable($class_id);                      // 1

            // Avoid duplicate enrollments: one ACTIVE enrollment per student per class.
            $stmt = $this->db->prepare(
                "SELECT enrollment_id FROM enrollments
                  WHERE student_id = :student_id AND class_id = :class_id AND status = 'active'");
            $stmt->execute([':student_id' => $student_id, ':class_id' => $class_id]);
            if ($stmt->fetch()) {
                throw new EnrollmentException('This student is already enrolled in the selected class.');
            }

            $enrollment_id = $this->insertEnrollment($student_id, $class_id); // 2
            $this->takeSlot($class_id);                                  // 3

            $this->db->commit();
            return $enrollment_id;
        } catch (Throwable $e) {
            $this->rollBackQuietly();
            throw $e;
        }
    }

    // Cancel an enrollment and give the slot back.
    public function cancel($enrollment_id) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT enrollment_id, class_id, status FROM enrollments
                  WHERE enrollment_id = :id" . $this->lockClause());
            $stmt->execute([':id' => $enrollment_id]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new EnrollmentException('Enrollment not found.');
            }
            if ($row['status'] !== 'active') {
                throw new EnrollmentException('This enrollment is already cancelled.');
            }

            $stmt = $this->db->prepare(                                  // 1
                "UPDATE enrollments SET status = 'cancelled' WHERE enrollment_id = :id");
            $stmt->execute([':id' => $enrollment_id]);

            $stmt = $this->db->prepare(                                  // 2
                "UPDATE classes SET slots = slots + 1 WHERE class_id = :class_id");
            $stmt->execute([':class_id' => $row['class_id']]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->rollBackQuietly();
            throw $e;
        }
    }

    // All enrollments joined with student, class and course (newest first).
    public function allWithDetails() {
        return $this->db->query(
            self::SELECT_DETAILS . self::FROM_DETAILS .
            " ORDER BY e.enrollment_date DESC, e.enrollment_id DESC"
        )->fetchAll();
    }

    // Search + filter + sort + paginate version of allWithDetails().
    // $filters keys: q (text), status (active|cancelled), course_id, class_id.
    public function search(array $filters = [], $sort = 'date', $dir = 'desc', $page = 1, $perPage = 10) {
        $sortMap = [
            'date'    => 'e.enrollment_date',
            'student' => 's.full_name',
            'course'  => 'co.course_name',
            'class'   => 'c.class_code',
            'status'  => 'e.status',
        ];
        $sort = Paginator::pick($sort, array_keys($sortMap), 'date');
        $dir  = strtoupper(Paginator::dir($dir, 'desc'));

        $conds = [];
        $params = [];
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            $conds[] = "(s.full_name LIKE :q1 OR s.email LIKE :q2
                         OR co.course_name LIKE :q3 OR c.class_code LIKE :q4)";
            $params[':q1'] = $params[':q2'] = $params[':q3'] = $params[':q4'] = $like;
        }
        $status = $filters['status'] ?? '';
        if (in_array($status, ['active', 'cancelled'], true)) {
            $conds[] = "e.status = :status";
            $params[':status'] = $status;
        }
        if ((int) ($filters['course_id'] ?? 0) > 0) {
            $conds[] = "co.course_id = :course_id";
            $params[':course_id'] = (int) $filters['course_id'];
        }
        if ((int) ($filters['class_id'] ?? 0) > 0) {
            $conds[] = "c.class_id = :class_id";
            $params[':class_id'] = (int) $filters['class_id'];
        }
        $where = $conds ? ' WHERE ' . implode(' AND ', $conds) : '';

        $stmt = $this->db->prepare("SELECT COUNT(*) " . self::FROM_DETAILS . $where);
        $stmt->execute($params);
        $win = Paginator::window($stmt->fetchColumn(), $page, $perPage);

        $sql = self::SELECT_DETAILS . self::FROM_DETAILS . $where .
               " ORDER BY {$sortMap[$sort]} $dir, e.enrollment_id DESC" .
               " LIMIT {$win['perPage']} OFFSET {$win['offset']}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'sort' => $sort, 'dir' => strtolower($dir)] + $win;
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------
    const SELECT_DETAILS =
        "SELECT e.enrollment_id, e.enrollment_date, e.status,
                s.student_id, s.full_name, s.email,
                c.class_id, c.class_code, c.schedule, c.instructor,
                co.course_id, co.course_code, co.course_name ";

    const FROM_DETAILS =
        "FROM enrollments e
         INNER JOIN students s  ON s.student_id = e.student_id
         INNER JOIN classes c   ON c.class_id   = e.class_id
         INNER JOIN courses co  ON co.course_id = c.course_id";

    // MySQL/InnoDB: lock the row so two administrators cannot take the last
    // slot at the same time. (Other drivers have no FOR UPDATE.)
    private function lockClause() {
        return $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
    }

    // Step "check that the class still has available slots".
    private function assertSlotsAvailable($class_id) {
        $stmt = $this->db->prepare(
            "SELECT slots FROM classes WHERE class_id = :id" . $this->lockClause());
        $stmt->execute([':id' => $class_id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new EnrollmentException('The selected class does not exist.');
        }
        if ((int) $row['slots'] <= 0) {
            throw new EnrollmentException('No slots available for the selected class.');
        }
    }

    private function insertEnrollment($student_id, $class_id) {
        $stmt = $this->db->prepare(
            "INSERT INTO enrollments (student_id, class_id) VALUES (:student_id, :class_id)");
        $stmt->execute([':student_id' => $student_id, ':class_id' => $class_id]);
        return (int) $this->db->lastInsertId();
    }

    // Guarded decrement: the WHERE slots > 0 makes it impossible to go negative,
    // even if the earlier check was somehow raced.
    private function takeSlot($class_id) {
        $stmt = $this->db->prepare(
            "UPDATE classes SET slots = slots - 1 WHERE class_id = :id AND slots > 0");
        $stmt->execute([':id' => $class_id]);
        if ($stmt->rowCount() !== 1) {
            throw new EnrollmentException('No slots available for the selected class.');
        }
    }

    private function rollBackQuietly() {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
