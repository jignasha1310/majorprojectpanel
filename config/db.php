<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

if (isset($_SESSION['session_user_agent'])) {
    $currentAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (!hash_equals((string) $_SESSION['session_user_agent'], $currentAgent)) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }
}

if (!isset($_SESSION['session_user_agent'])) {
    $_SESSION['session_user_agent'] = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
}

if (!isset($_SESSION['session_last_regen'])) {
    $_SESSION['session_last_regen'] = time();
} elseif (time() - (int) $_SESSION['session_last_regen'] > 900) {
    session_regenerate_id(true);
    $_SESSION['session_last_regen'] = time();
}

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'exampro_db';

// First connect without database to create it if needed
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS `$database`");
$conn->select_db($database);
$conn->set_charset("utf8mb4");

// Auto-create tables if they don't exist
$tables_check = $conn->query("SHOW TABLES LIKE 'students'");
if ($tables_check->num_rows === 0) {
    // Import the SQL schema
    $sql_file = __DIR__ . '/../database.sql';
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        // Remove the CREATE DATABASE and USE lines since we already selected it
        $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
        $sql = preg_replace('/USE.*?;/i', '', $sql);
        $conn->multi_query($sql);
        // Flush all results
        while ($conn->next_result()) {
            if ($res = $conn->store_result()) {
                $res->free();
            }
        }
    }
}

function ensure_student_profile_columns(mysqli $conn): void
{
    $requiredColumns = [
        'first_name' => "ALTER TABLE students ADD COLUMN first_name VARCHAR(80) NULL AFTER name",
        'last_name' => "ALTER TABLE students ADD COLUMN last_name VARCHAR(80) NULL AFTER first_name",
        'phone' => "ALTER TABLE students ADD COLUMN phone VARCHAR(20) NULL AFTER email",
        'profile_image' => "ALTER TABLE students ADD COLUMN profile_image VARCHAR(255) NULL AFTER phone",
        'updated_at' => "ALTER TABLE students ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
    ];

    foreach ($requiredColumns as $column => $alterSql) {
        $check = $conn->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
        if (!$check) {
            continue;
        }
        $tableName = 'students';
        $check->bind_param('ss', $tableName, $column);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$exists) {
            $conn->query($alterSql);
        }
    }

    $conn->query("UPDATE students SET first_name = TRIM(SUBSTRING_INDEX(name, ' ', 1)) WHERE (first_name IS NULL OR first_name = '') AND name IS NOT NULL AND name <> ''");
    $conn->query("UPDATE students SET last_name = TRIM(SUBSTRING(name, LENGTH(SUBSTRING_INDEX(name, ' ', 1)) + 1)) WHERE (last_name IS NULL OR last_name = '') AND name LIKE '% %'");
    $conn->query("UPDATE students SET last_name = '' WHERE last_name IS NULL");
}

ensure_student_profile_columns($conn);
?>
