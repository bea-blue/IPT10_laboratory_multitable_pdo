<?php
// classes/ClassSection.php
require_once __DIR__ . '/Paginator.php';

class ClassSection {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    // Every class with its course (INNER JOIN) - used by the class dropdowns.
    public function allWithCourse() {
        return $this->db->query(
            "SELECT c.*, co.course_name, co.course_code
               FROM classes c
               JOIN courses co ON c.course_id = co.course_id
           ORDER BY co.course_name, c.class_code"
        )->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM classes WHERE class_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create($course_id, $code, $schedule, $instructor, $slots) {
        $stmt = $this->db->prepare(
            "INSERT INTO classes (course_id, class_code, schedule, instructor, slots)
             VALUES (:course_id, :code, :schedule, :instructor, :slots)");
        $stmt->execute([
            ':course_id' => $course_id, ':code' => $code, ':schedule' => $schedule,
            ':instructor' => $instructor, ':slots' => $slots,
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $course_id, $code, $schedule, $instructor, $slots) {
        $stmt = $this->db->prepare(
            "UPDATE classes
                SET course_id = :course_id, class_code = :code, schedule = :schedule,
                    instructor = :instructor, slots = :slots
              WHERE class_id = :id");
        return $stmt->execute([
            ':course_id' => $course_id, ':code' => $code, ':schedule' => $schedule,
            ':instructor' => $instructor, ':slots' => $slots, ':id' => $id,
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM classes WHERE class_id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Remaining slots for a class (null when the class does not exist).
    public function getSlots($class_id) {
        $stmt = $this->db->prepare("SELECT slots FROM classes WHERE class_id = :id");
        $stmt->execute([':id' => $class_id]);
        $value = $stmt->fetchColumn();
        return $value === false ? null : (int) $value;
    }

    // A class that has any enrollment rows (even cancelled) cannot be deleted.
    public function hasEnrollments($id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM enrollments WHERE class_id = :id");
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // Search + filter by course + sort + paginate.
    // Each row has remaining slots (slots) and enrolled (active enrollments).
    public function search($q = '', $course_id = 0, $sort = 'class_code', $dir = 'asc', $page = 1, $perPage = 10) {
        $sortMap = [
            'class_code'  => 'c.class_code',
            'course_name' => 'co.course_name',
            'schedule'    => 'c.schedule',
            'instructor'  => 'c.instructor',
            'slots'       => 'c.slots',
            'enrolled'    => 'enrolled',
        ];
        $sort = Paginator::pick($sort, array_keys($sortMap), 'course_name');
        $dir  = strtoupper(Paginator::dir($dir));

        $conds = [];
        $params = [];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $conds[] = "(c.class_code LIKE :q1 OR c.schedule LIKE :q2
                         OR c.instructor LIKE :q3 OR co.course_name LIKE :q4)";
            $params[':q1'] = $params[':q2'] = $params[':q3'] = $params[':q4'] = $like;
        }
        if ((int) $course_id > 0) {
            $conds[] = "c.course_id = :course_id";
            $params[':course_id'] = (int) $course_id;
        }
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM classes c
               JOIN courses co ON co.course_id = c.course_id $where");
        $stmt->execute($params);
        $win = Paginator::window($stmt->fetchColumn(), $page, $perPage);

        $sql = "SELECT c.class_id, c.course_id, c.class_code, c.schedule, c.instructor, c.slots,
                       co.course_code, co.course_name,
                       COUNT(e.enrollment_id) AS enrolled
                  FROM classes c
                  JOIN courses co ON co.course_id = c.course_id
             LEFT JOIN enrollments e ON e.class_id = c.class_id AND e.status = 'active'
                $where
              GROUP BY c.class_id, c.course_id, c.class_code, c.schedule, c.instructor,
                       c.slots, co.course_code, co.course_name
              ORDER BY {$sortMap[$sort]} $dir, c.class_id ASC
                 LIMIT {$win['perPage']} OFFSET {$win['offset']}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'sort' => $sort, 'dir' => strtolower($dir)] + $win;
    }
}
