<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$teacherId = (int) $_SESSION['teacher_id'];

$countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM exams WHERE teacher_id = ? OR teacher_id IS NULL');
$countStmt->bind_param('i', $teacherId);
$countStmt->execute();
$totalExams = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$activeStmt = $conn->prepare("SELECT COUNT(*) AS total FROM exams WHERE (teacher_id = ? OR teacher_id IS NULL) AND is_active = 1");
$activeStmt->bind_param('i', $teacherId);
$activeStmt->execute();
$activeExams = (int) ($activeStmt->get_result()->fetch_assoc()['total'] ?? 0);
$activeStmt->close();

$questionStmt = $conn->prepare('SELECT COUNT(*) AS total FROM questions q JOIN exams e ON e.id = q.exam_id WHERE e.teacher_id = ? OR e.teacher_id IS NULL');
$questionStmt->bind_param('i', $teacherId);
$questionStmt->execute();
$totalQuestions = (int) ($questionStmt->get_result()->fetch_assoc()['total'] ?? 0);
$questionStmt->close();

$listStmt = $conn->prepare('SELECT id, title, semester, total_questions, duration_minutes, exam_date, status, is_active FROM exams WHERE teacher_id = ? OR teacher_id IS NULL ORDER BY exam_date ASC LIMIT 5');
$listStmt->bind_param('i', $teacherId);
$listStmt->execute();
$upcomingExams = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

teacherRenderHeader('Exam Scheduler', 'dashboard');
?>

<?php if ($flash = teacherFlash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-calendar-week"></i></div>
                <div>
                    <div class="display-6 fw-bold text-primary mb-0"><?= $totalExams ?></div>
                    <div class="text-secondary">Total Exams</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-play-circle"></i></div>
                <div>
                    <div class="display-6 fw-bold text-primary mb-0"><?= $activeExams ?></div>
                    <div class="text-secondary">Active Exams</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-journal-check"></i></div>
                <div>
                    <div class="display-6 fw-bold text-primary mb-0"><?= $totalQuestions ?></div>
                    <div class="text-secondary">Total MCQ Questions</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h3 fw-semibold mb-0">Upcoming and Active Exams</h2>
    <a href="exams.php" class="btn btn-indigo"><i class="bi bi-plus-circle me-1"></i> Schedule New Exam</a>
</div>

<div class="card content-card">
    <div class="card-body">
        <?php if (empty($upcomingExams)): ?>
            <p class="text-secondary mb-0">No exams found. Create your first exam.</p>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($upcomingExams as $exam): ?>
                    <?php
                    $dateObj = new DateTime($exam['exam_date']);
                    $statusText = $exam['is_active'] ? 'Active' : 'Inactive';
                    $statusClass = $exam['is_active'] ? 'success' : 'secondary';
                    ?>
                    <div class="exam-row d-flex flex-column flex-md-row align-items-md-center gap-3">
                        <div class="exam-date">
                            <div><?= $dateObj->format('d') ?></div>
                            <small><?= strtoupper($dateObj->format('M')) ?></small>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="h4 mb-1"><?= htmlspecialchars($exam['title']) ?></h3>
                            <p class="text-secondary mb-0"><?= htmlspecialchars($exam['semester']) ?> | <?= (int) $exam['total_questions'] ?> Questions | <?= (int) $exam['duration_minutes'] ?> mins</p>
                        </div>
                        <span class="badge text-bg-<?= $statusClass ?> px-3 py-2"><?= $statusText ?></span>
                        <a href="questions.php?exam_id=<?= (int) $exam['id'] ?>" class="btn btn-outline-primary btn-sm">Manage Questions</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php teacherRenderFooter(); ?>
