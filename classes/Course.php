<?php
// classes/Course.php
require_once __DIR__ . '/Paginator.php';

class Course {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    public function all() {
        return $this->db->query("SELECT * FROM courses ORDER BY course_name")->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM courses WHERE course_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create($code, $name, $description) {
        $stmt = $this->db->prepare(
            "INSERT INTO courses (course_code, course_name, description)
             VALUES (:code, :name, :description)");
        $stmt->execute([
            ':code' => $code, ':name' => $name, ':description' => $description,
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $code, $name, $description) {
        $stmt = $this->db->prepare(
            "UPDATE courses
                SET course_code = :code, course_name = :name, description = :description
              WHERE course_id = :id");
        return $stmt->execute([
            ':code' => $code, ':name' => $name,
            ':description' => $description, ':id' => $id,
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM courses WHERE course_id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // True when another course already uses this code (ignores $excludeId).
    public function codeExists($code, $excludeId = 0) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM courses WHERE course_code = :code AND course_id <> :id");
        $stmt->execute([':code' => $code, ':id' => (int) $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // A course with classes cannot be deleted (foreign key).
    public function hasClasses($id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM classes WHERE course_id = :id");
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // Search + sort + paginate. Each course row also carries class_count
    // (LEFT JOIN keeps courses that have no classes yet).
    public function search($q = '', $sort = 'course_name', $dir = 'asc', $page = 1, $perPage = 10) {
        $sortMap = [
            'course_code' => 'co.course_code',
            'course_name' => 'co.course_name',
            'class_count' => 'class_count',
        ];
        $sort = Paginator::pick($sort, array_keys($sortMap), 'course_name');
        $dir  = strtoupper(Paginator::dir($dir));

        $where = '';
        $params = [];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where = "WHERE (co.course_code LIKE :q1 OR co.course_name LIKE :q2
                             OR co.description LIKE :q3)";
            $params = [':q1' => $like, ':q2' => $like, ':q3' => $like];
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM courses co $where");
        $stmt->execute($params);
        $win = Paginator::window($stmt->fetchColumn(), $page, $perPage);

        $sql = "SELECT co.course_id, co.course_code, co.course_name, co.description,
                       COUNT(c.class_id) AS class_count
                  FROM courses co
             LEFT JOIN classes c ON c.course_id = co.course_id
                $where
              GROUP BY co.course_id, co.course_code, co.course_name, co.description
              ORDER BY {$sortMap[$sort]} $dir, co.course_id ASC
                 LIMIT {$win['perPage']} OFFSET {$win['offset']}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'sort' => $sort, 'dir' => strtolower($dir)] + $win;
    }
}
