<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

function adminCount(mysqli $conn, string $sql): int
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    $stmt->execute();
    $value = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
    return $value;
}

$stats = [
    ['title' => 'Total Students', 'value' => adminCount($conn, 'SELECT COUNT(*) AS total FROM students'), 'icon' => 'fa-user-graduate'],
    ['title' => 'Total Teachers', 'value' => adminCount($conn, 'SELECT COUNT(*) AS total FROM teachers'), 'icon' => 'fa-chalkboard-user'],
    ['title' => 'Total Exams', 'value' => adminCount($conn, 'SELECT COUNT(*) AS total FROM exams'), 'icon' => 'fa-file-pen'],
    ['title' => 'Total Results', 'value' => adminCount($conn, 'SELECT COUNT(*) AS total FROM student_exams'), 'icon' => 'fa-square-poll-vertical'],
];

$studentStmt = $conn->prepare('SELECT id, name, roll_number, department, created_at FROM students ORDER BY id DESC LIMIT ?');
$studentLimit = 5;
$studentStmt->bind_param('i', $studentLimit);
$studentStmt->execute();
$recentStudents = $studentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$studentStmt->close();

$examStmt = $conn->prepare('SELECT title, subject, exam_date, is_active FROM exams ORDER BY id DESC LIMIT ?');
$examLimit = 5;
$examStmt->bind_param('i', $examLimit);
$examStmt->execute();
$recentExams = $examStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$examStmt->close();

adminRenderHeader('Admin Dashboard', 'dashboard');
?>

<div class="row g-3 mb-4">
    <?php foreach ($stats as $stat): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon">
                        <i class="fa-solid <?= htmlspecialchars($stat['icon']) ?>"></i>
                    </div>
                    <div>
                        <div class="display-6 fw-bold text-primary mb-0"><?= (int) $stat['value'] ?></div>
                        <div class="text-secondary"><?= htmlspecialchars($stat['title']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-6">
        <div class="card content-card h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h2 class="h5 fw-semibold mb-0"><i class="fa-solid fa-users me-2"></i>Recent Students</h2>
            </div>
            <div class="card-body pt-3 px-4 pb-4">
                <div class="table-responsive table-shell">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Roll No</th>
                            <th>Class</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentStudents)): ?>
                            <tr><td colspan="4" class="text-center text-secondary">No students found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentStudents as $student): ?>
                                <?php
                                $createdAt = strtotime((string) ($student['created_at'] ?? 'now'));
                                $isActive = (time() - $createdAt) <= 60 * 60 * 24 * 365;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) $student['name']) ?></td>
                                    <td><?= htmlspecialchars((string) $student['roll_number']) ?></td>
                                    <td><?= htmlspecialchars((string) $student['department']) ?></td>
                                    <td>
                                        <span class="badge-status <?= $isActive ? 'badge-active' : 'badge-inactive' ?>">
                                            <?= $isActive ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card content-card h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h2 class="h5 fw-semibold mb-0"><i class="fa-solid fa-file-lines me-2"></i>Recent Exams</h2>
            </div>
            <div class="card-body pt-3 px-4 pb-4">
                <div class="table-responsive table-shell">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentExams)): ?>
                            <tr><td colspan="4" class="text-center text-secondary">No exams found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentExams as $exam): ?>
                                <?php $isActive = (int) ($exam['is_active'] ?? 0) === 1; ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) $exam['title']) ?></td>
                                    <td><?= htmlspecialchars((string) $exam['subject']) ?></td>
                                    <td><?= htmlspecialchars((string) $exam['exam_date']) ?></td>
                                    <td>
                                        <span class="badge-status <?= $isActive ? 'badge-active' : 'badge-inactive' ?>">
                                            <?= $isActive ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php adminRenderFooter(); ?>
