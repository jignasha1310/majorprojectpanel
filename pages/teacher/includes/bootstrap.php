<?php
require_once __DIR__ . '/../../../config/db.php';

function teacherEnsureSchema(mysqli $conn): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    $conn->query(
        "CREATE TABLE IF NOT EXISTS teachers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(120) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $teacherIdColumn = $conn->query("SHOW COLUMNS FROM exams LIKE 'teacher_id'");
    if ($teacherIdColumn && $teacherIdColumn->num_rows === 0) {
        $conn->query("ALTER TABLE exams ADD COLUMN teacher_id INT NULL AFTER id");
    }

    $isActiveColumn = $conn->query("SHOW COLUMNS FROM exams LIKE 'is_active'");
    if ($isActiveColumn && $isActiveColumn->num_rows === 0) {
        $conn->query("ALTER TABLE exams ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER status");
    }

    $defaultTeacherEmail = 'teacher@exampro.com';
    $defaultTeacherHash = '$2y$10$tLg3ve.46FC5bbhiCM5TWe5DqaBHydQneJjf6zVqZBlvPB0f69Jpu';

    $checkStmt = $conn->prepare('SELECT id FROM teachers WHERE email = ? LIMIT 1');
    $checkStmt->bind_param('s', $defaultTeacherEmail);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!$exists) {
        $name = 'Default Teacher';
        $insertStmt = $conn->prepare('INSERT INTO teachers (name, email, password) VALUES (?, ?, ?)');
        $insertStmt->bind_param('sss', $name, $defaultTeacherEmail, $defaultTeacherHash);
        $insertStmt->execute();
        $insertStmt->close();
    }

    if ($teacherIdColumn) {
        $teacherIdColumn->free();
    }
    if ($isActiveColumn) {
        $isActiveColumn->free();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $initialized = true;
}

function teacherCsrfToken(): string
{
    return $_SESSION['csrf_token'] ?? '';
}

function teacherVerifyCsrf(?string $token): bool
{
    if (!isset($_SESSION['csrf_token']) || !$token) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function teacherFlash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['teacher_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['teacher_flash'][$key] ?? null;
    if (isset($_SESSION['teacher_flash'][$key])) {
        unset($_SESSION['teacher_flash'][$key]);
    }
    return $value;
}

teacherEnsureSchema($conn);
