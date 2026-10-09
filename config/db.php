<?php
// config/db.php
// Creates the single shared PDO connection ($db) used by every page.
require_once __DIR__ . '/../classes/Database.php';

// Credentials live in config/config.php (not committed). Fall back to the
// sample file so the project still runs out-of-the-box on a default XAMPP.
$configFile = file_exists(__DIR__ . '/config.php')
    ? __DIR__ . '/config.php'
    : __DIR__ . '/config.sample.php';
$config = require $configFile;

$dsn  = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$user = $config['user'];
$pass = $config['pass'];

try {
    // Singleton: every call returns the same Database (PDO) object.
    $db = Database::getInstance($dsn, $user, $pass);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('DB connection failed: ' . $e->getMessage());
    exit('Could not connect to the database. Check config/config.php and make sure '
       . 'MySQL is running and sql/schema.sql has been imported.');
}
