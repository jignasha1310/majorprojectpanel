<?php
declare(strict_types=1);

function ensureTeacherModuleSchema(PDO $pdo): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS teacher_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NULL,
            target_role VARCHAR(20) NOT NULL DEFAULT 'teacher',
            title VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_teacher_notifications_teacher (teacher_id),
            INDEX idx_teacher_notifications_role (target_role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $columns = [
        'registration_id' => "ALTER TABLE teachers ADD COLUMN registration_id VARCHAR(30) NULL UNIQUE AFTER id",
        'phone' => "ALTER TABLE teachers ADD COLUMN phone VARCHAR(20) NULL AFTER email",
        'first_name' => "ALTER TABLE teachers ADD COLUMN first_name VARCHAR(80) NULL AFTER name",
        'last_name' => "ALTER TABLE teachers ADD COLUMN last_name VARCHAR(80) NULL AFTER first_name",
        'dob' => "ALTER TABLE teachers ADD COLUMN dob DATE NULL AFTER last_name",
        'gender' => "ALTER TABLE teachers ADD COLUMN gender VARCHAR(20) NULL AFTER dob",
        'qualification' => "ALTER TABLE teachers ADD COLUMN qualification VARCHAR(120) NULL AFTER gender",
        'bed_status' => "ALTER TABLE teachers ADD COLUMN bed_status VARCHAR(20) NULL AFTER qualification",
        'university' => "ALTER TABLE teachers ADD COLUMN university VARCHAR(150) NULL AFTER bed_status",
        'passing_year' => "ALTER TABLE teachers ADD COLUMN passing_year SMALLINT NULL AFTER university",
        'experience_years' => "ALTER TABLE teachers ADD COLUMN experience_years DECIMAL(4,1) NULL AFTER passing_year",
        'subjects' => "ALTER TABLE teachers ADD COLUMN subjects VARCHAR(255) NULL AFTER experience_years",
        'photo_path' => "ALTER TABLE teachers ADD COLUMN photo_path VARCHAR(255) NULL AFTER subjects",
        'signature_path' => "ALTER TABLE teachers ADD COLUMN signature_path VARCHAR(255) NULL AFTER photo_path",
        'certificate_path' => "ALTER TABLE teachers ADD COLUMN certificate_path VARCHAR(255) NULL AFTER signature_path",
        'id_proof_path' => "ALTER TABLE teachers ADD COLUMN id_proof_path VARCHAR(255) NULL AFTER certificate_path",
        'status' => "ALTER TABLE teachers ADD COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER id_proof_path",
        'registration_step' => "ALTER TABLE teachers ADD COLUMN registration_step TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER status",
        'rejection_reason' => "ALTER TABLE teachers ADD COLUMN rejection_reason TEXT NULL AFTER registration_step",
        'approval_date' => "ALTER TABLE teachers ADD COLUMN approval_date DATETIME NULL AFTER rejection_reason",
        'submitted_at' => "ALTER TABLE teachers ADD COLUMN submitted_at DATETIME NULL AFTER approval_date",
        'updated_at' => "ALTER TABLE teachers ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
    ];

    $checkStmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name LIMIT 1');
    foreach ($columns as $column => $alterSql) {
        $checkStmt->execute([
            ':table_name' => 'teachers',
            ':column_name' => $column,
        ]);
        if (!$checkStmt->fetchColumn()) {
            $pdo->exec($alterSql);
        }
    }

    $pdo->exec("UPDATE teachers SET status = 'approved' WHERE status IS NULL OR status = ''");
    $pdo->exec("UPDATE teachers SET first_name = TRIM(SUBSTRING_INDEX(name, ' ', 1)) WHERE (first_name IS NULL OR first_name = '') AND name IS NOT NULL AND name <> ''");
    $pdo->exec("UPDATE teachers SET last_name = TRIM(SUBSTRING(name, LENGTH(SUBSTRING_INDEX(name, ' ', 1)) + 1)) WHERE (last_name IS NULL OR last_name = '') AND name LIKE '% %'");
    $pdo->exec("UPDATE teachers SET registration_step = 3 WHERE status IN ('approved','rejected') AND registration_step = 0");

    $initialized = true;
}

function teacherModuleCsrfToken(string $key = 'teacher_module_csrf'): string
{
    if (empty($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION[$key];
}

function teacherModuleVerifyCsrf(string $token, string $key = 'teacher_module_csrf'): bool
{
    if ($token === '' || empty($_SESSION[$key])) {
        return false;
    }
    return hash_equals((string) $_SESSION[$key], $token);
}

function generateTeacherRegistrationId(PDO $pdo): string
{
    $year = (string) date('Y');
    $prefix = 'TCH' . $year;

    $stmt = $pdo->prepare('SELECT registration_id FROM teachers WHERE registration_id LIKE :prefix ORDER BY id DESC LIMIT 1');
    $stmt->execute([':prefix' => $prefix . '%']);
    $last = (string) ($stmt->fetchColumn() ?: '');

    $seq = 1;
    if (preg_match('/^TCH\d{4}(\d{3})$/', $last, $match)) {
        $seq = ((int) $match[1]) + 1;
    }

    return sprintf('%s%03d', $prefix, $seq);
}

function teacherNotification(PDO $pdo, ?int $teacherId, string $targetRole, string $title, string $message): void
{
    $stmt = $pdo->prepare('INSERT INTO teacher_notifications (teacher_id, target_role, title, message) VALUES (:teacher_id, :target_role, :title, :message)');
    $stmt->execute([
        ':teacher_id' => $teacherId,
        ':target_role' => $targetRole,
        ':title' => $title,
        ':message' => $message,
    ]);
}

function sendTeacherStatusEmail(string $toEmail, string $toName, string $status, ?string $reason = null): bool
{
    $safeName = trim($toName) !== '' ? $toName : 'Teacher';
    $status = strtolower($status);

    if ($status === 'approved') {
        $subject = 'ExamPro - Teacher Application Approved';
        $body = "Hello {$safeName},\n\nYour teacher application has been approved.\nYou can now login to the teacher panel.\n\nRegards,\nExamPro Admin Team";
    } elseif ($status === 'rejected') {
        $subject = 'ExamPro - Teacher Application Rejected';
        $reasonText = trim((string) $reason) !== '' ? trim((string) $reason) : 'No specific reason provided.';
        $body = "Hello {$safeName},\n\nYour teacher application has been rejected.\nReason: {$reasonText}\n\nPlease contact the admin team for more details.\n\nRegards,\nExamPro Admin Team";
    } else {
        return false;
    }

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'From: ExamPro <no-reply@exampro.local>';

    return @mail($toEmail, $subject, $body, implode("\r\n", $headers));
}
