<?php
// classes/Student.php
class Student {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM students WHERE student_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function all() {
        return $this->db->query("SELECT * FROM students ORDER BY full_name")->fetchAll();
    }

    // Most recently recorded students (shown under the recording form).
    public function recent($limit = 10) {
        $limit = max(1, (int) $limit);   // cast to int: safe to inline in LIMIT
        return $this->db->query(
            "SELECT * FROM students ORDER BY student_id DESC LIMIT $limit"
        )->fetchAll();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM students WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }
}
