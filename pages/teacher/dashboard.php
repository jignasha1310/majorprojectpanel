<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

function teacherCount(mysqli $conn, string $sql): int
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

$statsCards = [
    ['title' => 'Students', 'icon' => 'bi-people-fill', 'bg' => 'bg-primary', 'value' => teacherCount($conn, 'SELECT COUNT(*) AS total FROM students')],
    ['title' => 'Classes', 'icon' => 'bi-journal-bookmark-fill', 'bg' => 'bg-warning', 'value' => teacherCount($conn, 'SELECT COUNT(DISTINCT department) AS total FROM students')],
    ['title' => 'Exams', 'icon' => 'bi-card-checklist', 'bg' => 'bg-success', 'value' => teacherCount($conn, 'SELECT COUNT(*) AS total FROM exams')],
    ['title' => 'Results', 'icon' => 'bi-bar-chart-fill', 'bg' => 'bg-danger', 'value' => teacherCount($conn, 'SELECT COUNT(*) AS total FROM student_exams')],
];

$recentStmt = $conn->prepare('SELECT id, name, roll_number, department, email FROM students ORDER BY id DESC LIMIT ?');
$limit = 8;
$recentStmt->bind_param('i', $limit);
$recentStmt->execute();
$recentStudents = $recentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recentStmt->close();

teacherRenderHeader('Teacher Dashboard', 'dashboard');
?>

<?php if ($flash = teacherFlash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ($statsCards as $card): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100 border-0 overflow-hidden <?= $card['bg'] ?> text-white">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <h3 class="h5 fw-semibold mb-0"><?= htmlspecialchars($card['title']) ?></h3>
                        <i class="bi <?= htmlspecialchars($card['icon']) ?> fs-4"></i>
                    </div>
                    <div class="h2 fw-bold mb-2"><?= (int) $card['value'] ?></div>
                    <a href="#" class="small text-white text-decoration-none d-inline-flex align-items-center gap-2 opacity-75">
                        <span>View Details</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<h2 class="h4 fw-semibold mb-3">Recently Registered Students</h2>

<div class="card content-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Roll No</th>
                    <th>Class</th>
                    <th>Username</th>
                    <th>Profile</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($recentStudents)): ?>
                    <tr><td colspan="5" class="text-center text-secondary">No students found.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentStudents as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $student['name']) ?></td>
                            <td><?= htmlspecialchars((string) $student['roll_number']) ?></td>
                            <td><?= htmlspecialchars((string) $student['department']) ?></td>
                            <td><?= htmlspecialchars(strstr((string) $student['email'], '@', true) ?: (string) $student['email']) ?></td>
                            <td><a href="#" class="text-decoration-none">Visit</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php teacherRenderFooter(); ?>
