<?php
require_once __DIR__ . '/../../../config/db.php';

function adminEnsureSchema(mysqli $conn): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    $conn->query(
        "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(120) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS teachers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(120) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            roll_number VARCHAR(50) NOT NULL,
            department VARCHAR(50) DEFAULT 'BCA',
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS exams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NULL,
            title VARCHAR(200) NOT NULL,
            subject VARCHAR(100) NOT NULL,
            semester VARCHAR(50) DEFAULT 'Semester 2',
            total_questions INT NOT NULL,
            duration_minutes INT NOT NULL,
            status ENUM('scheduled','active','completed') DEFAULT 'scheduled',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            exam_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS student_exams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            exam_id INT NOT NULL,
            score INT DEFAULT 0,
            total INT DEFAULT 0,
            percentage DECIMAL(5,2) DEFAULT 0.00,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $defaultAdminEmail = 'admin@exampro.com';
    $defaultAdminHash = '$2y$10$EVXBMePlOUidA6OCtD23E.XoVLOjCKuj9LCIX5yfavAnBry.y.6I2';

    $checkStmt = $conn->prepare('SELECT id FROM admins WHERE email = ? LIMIT 1');
    $checkStmt->bind_param('s', $defaultAdminEmail);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!$exists) {
        $name = 'Default Admin';
        $insertStmt = $conn->prepare('INSERT INTO admins (name, email, password) VALUES (?, ?, ?)');
        $insertStmt->bind_param('sss', $name, $defaultAdminEmail, $defaultAdminHash);
        $insertStmt->execute();
        $insertStmt->close();
    }

    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }

    $initialized = true;
}

function adminCsrfToken(): string
{
    return $_SESSION['admin_csrf_token'] ?? '';
}

function adminVerifyCsrf(?string $token): bool
{
    if (!isset($_SESSION['admin_csrf_token']) || !$token) {
        return false;
    }
    return hash_equals($_SESSION['admin_csrf_token'], $token);
}

function adminFlash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['admin_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['admin_flash'][$key] ?? null;
    if (isset($_SESSION['admin_flash'][$key])) {
        unset($_SESSION['admin_flash'][$key]);
    }
    return $value;
}

adminEnsureSchema($conn);
