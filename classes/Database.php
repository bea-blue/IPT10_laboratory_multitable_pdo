<?php
// classes/Database.php
// PDO wrapper implementing the Singleton pattern.
class Database extends PDO {
    private static $instance = null;

    private function __construct($dsn, $user, $pass) {
        parent::__construct($dsn, $user, $pass);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public static function getInstance($dsn, $user = null, $pass = null) {
        if (self::$instance === null) {
            self::$instance = new Database($dsn, $user, $pass);
        }
        return self::$instance;
    }

    public function insert($table, array $data) {
        $cols = implode(", ", array_keys($data));
        $phs  = ":" . implode(", :", array_keys($data));
        $sql  = "INSERT INTO $table ($cols) VALUES ($phs)";
        $stmt = $this->prepare($sql);
        $stmt->execute($data);
        return $this->lastInsertId();
    }

    // ---- Added to match the class diagram: update() and delete() ----

    // UPDATE $table SET col = val, ... WHERE col = val AND ...
    // $data and $where are column => value arrays; values are always bound.
    // Returns the number of affected rows.
    public function update($table, array $data, array $where) {
        if (empty($data) || empty($where)) {
            throw new InvalidArgumentException('update() needs data and a WHERE condition.');
        }
        $this->assertIdentifier($table);
        $set = [];
        $params = [];
        foreach ($data as $col => $val) {
            $this->assertIdentifier($col);
            $set[] = "$col = :set_$col";
            $params[":set_$col"] = $val;
        }
        $cond = [];
        foreach ($where as $col => $val) {
            $this->assertIdentifier($col);
            $cond[] = "$col = :where_$col";
            $params[":where_$col"] = $val;
        }
        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $cond);
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // DELETE FROM $table WHERE col = val AND ...  (returns affected rows)
    public function delete($table, array $where) {
        if (empty($where)) {
            throw new InvalidArgumentException('delete() needs a WHERE condition.');
        }
        $this->assertIdentifier($table);
        $cond = [];
        $params = [];
        foreach ($where as $col => $val) {
            $this->assertIdentifier($col);
            $cond[] = "$col = :where_$col";
            $params[":where_$col"] = $val;
        }
        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $cond);
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // Table/column names cannot be bound as parameters, so they are restricted
    // to plain identifiers to rule out SQL injection through them.
    private function assertIdentifier($name) {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string) $name)) {
            throw new InvalidArgumentException("Invalid SQL identifier: $name");
        }
    }

    public function getRows($sql, array $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getRow($sql, array $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}
