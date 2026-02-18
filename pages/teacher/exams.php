<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$teacherId = (int) $_SESSION['teacher_id'];

function teacherRedirectExams(): void
{
    header('Location: exams.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!teacherVerifyCsrf($csrf)) {
        teacherFlash('error', 'Invalid request token. Please refresh and try again.');
        teacherRedirectExams();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_exam') {
        $title = trim($_POST['title'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $totalQuestions = (int) ($_POST['total_questions'] ?? 0);
        $durationMinutes = (int) ($_POST['duration_minutes'] ?? 0);
        $examDate = $_POST['exam_date'] ?? '';

        if ($title === '' || $subject === '' || $semester === '' || $examDate === '' || $totalQuestions < 1 || $durationMinutes < 1) {
            teacherFlash('error', 'All exam fields are required and must be valid.');
            teacherRedirectExams();
        }

        $status = 'scheduled';
        $isActive = 0;
        $stmt = $conn->prepare('INSERT INTO exams (teacher_id, title, subject, semester, total_questions, duration_minutes, status, is_active, exam_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssiisis', $teacherId, $title, $subject, $semester, $totalQuestions, $durationMinutes, $status, $isActive, $examDate);
        $stmt->execute();
        $stmt->close();

        teacherFlash('success', 'Exam created successfully.');
        teacherRedirectExams();
    }

    if ($action === 'update_exam') {
        $examId = (int) ($_POST['exam_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $totalQuestions = (int) ($_POST['total_questions'] ?? 0);
        $durationMinutes = (int) ($_POST['duration_minutes'] ?? 0);
        $examDate = $_POST['exam_date'] ?? '';
        $status = $_POST['status'] ?? 'scheduled';

        $allowed = ['scheduled', 'active', 'completed'];
        if (!in_array($status, $allowed, true)) {
            $status = 'scheduled';
        }

        if ($examId < 1 || $title === '' || $subject === '' || $semester === '' || $examDate === '' || $totalQuestions < 1 || $durationMinutes < 1) {
            teacherFlash('error', 'Invalid exam update payload.');
            teacherRedirectExams();
        }

        $stmt = $conn->prepare('UPDATE exams SET title = ?, subject = ?, semester = ?, total_questions = ?, duration_minutes = ?, exam_date = ?, status = ? WHERE id = ? AND (teacher_id = ? OR teacher_id IS NULL)');
        $stmt->bind_param('sssiissii', $title, $subject, $semester, $totalQuestions, $durationMinutes, $examDate, $status, $examId, $teacherId);
        $stmt->execute();
        $stmt->close();

        teacherFlash('success', 'Exam updated successfully.');
        teacherRedirectExams();
    }

    if ($action === 'delete_exam') {
        $examId = (int) ($_POST['exam_id'] ?? 0);
        if ($examId > 0) {
            $stmt = $conn->prepare('DELETE FROM exams WHERE id = ? AND (teacher_id = ? OR teacher_id IS NULL)');
            $stmt->bind_param('ii', $examId, $teacherId);
            $stmt->execute();
            $stmt->close();
            teacherFlash('success', 'Exam deleted successfully.');
        }
        teacherRedirectExams();
    }

    if ($action === 'toggle_exam') {
        $examId = (int) ($_POST['exam_id'] ?? 0);
        $nextActive = (int) ($_POST['next_active'] ?? 0) === 1 ? 1 : 0;
        $status = $nextActive === 1 ? 'active' : 'scheduled';

        if ($examId > 0) {
            $stmt = $conn->prepare('UPDATE exams SET is_active = ?, status = ? WHERE id = ? AND (teacher_id = ? OR teacher_id IS NULL)');
            $stmt->bind_param('isii', $nextActive, $status, $examId, $teacherId);
            $stmt->execute();
            $stmt->close();
            teacherFlash('success', $nextActive ? 'Exam activated successfully.' : 'Exam deactivated successfully.');
        }
        teacherRedirectExams();
    }
}

$statsStmt = $conn->prepare("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) AS scheduled_count,
    SUM(CASE WHEN status = 'active' AND is_active = 1 THEN 1 ELSE 0 END) AS active_count,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count
    FROM exams
    WHERE teacher_id = ? OR teacher_id IS NULL");
$statsStmt->bind_param('i', $teacherId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();

$listStmt = $conn->prepare('SELECT id, title, subject, semester, total_questions, duration_minutes, status, is_active, exam_date FROM exams WHERE teacher_id = ? OR teacher_id IS NULL ORDER BY exam_date ASC');
$listStmt->bind_param('i', $teacherId);
$listStmt->execute();
$examList = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

teacherRenderHeader('Exam Scheduler', 'exams');
?>

<?php if ($error = teacherFlash('error')): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success = teacherFlash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-calendar-week"></i></div>
                <div>
                    <div class="display-6 fw-bold text-primary mb-0"><?= (int) ($stats['scheduled_count'] ?? 0) ?></div>
                    <div class="text-secondary">Scheduled</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-play-circle"></i></div>
                <div>
                    <div class="display-6 fw-bold text-primary mb-0"><?= (int) ($stats['active_count'] ?? 0) ?></div>
                    <div class="text-secondary">Active Now</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
                <div>
                    <div class="display-6 fw-bold text-primary mb-0"><?= (int) ($stats['completed_count'] ?? 0) ?></div>
                    <div class="text-secondary">Completed</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card content-card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Create New Exam</h2>
        </div>
        <form method="post" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(teacherCsrfToken()) ?>">
            <input type="hidden" name="action" value="create_exam">

            <div class="col-md-6">
                <label class="form-label">Exam Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Semester / Batch</label>
                <input type="text" name="semester" class="form-control" placeholder="BCA Semester 2" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Questions</label>
                <input type="number" name="total_questions" class="form-control" min="1" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Duration (mins)</label>
                <input type="number" name="duration_minutes" class="form-control" min="1" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Exam Date</label>
                <input type="date" name="exam_date" class="form-control" required>
            </div>
            <div class="col-12">
                <button class="btn btn-indigo" type="submit"><i class="bi bi-plus-circle me-1"></i> Schedule New Exam</button>
            </div>
        </form>
    </div>
</div>

<div class="card content-card">
    <div class="card-body">
        <h2 class="h4 mb-3">Upcoming and Active Exams</h2>

        <?php if (empty($examList)): ?>
            <p class="text-secondary mb-0">No exams available.</p>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($examList as $exam): ?>
                    <?php
                    $dateObj = new DateTime($exam['exam_date']);
                    $isActive = (int) $exam['is_active'] === 1;
                    ?>
                    <div class="exam-row d-flex flex-column flex-lg-row align-items-lg-center gap-3">
                        <div class="exam-date">
                            <div><?= $dateObj->format('d') ?></div>
                            <small><?= strtoupper($dateObj->format('M')) ?></small>
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="h4 mb-1"><?= htmlspecialchars($exam['title']) ?></h3>
                            <p class="text-secondary mb-1"><?= htmlspecialchars($exam['semester']) ?> | <?= (int) $exam['total_questions'] ?> Questions | <?= (int) $exam['duration_minutes'] ?> mins</p>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge badge-soft text-capitalize"><?= htmlspecialchars($exam['status']) ?></span>
                                <span class="badge text-bg-<?= $isActive ? 'success' : 'secondary' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="questions.php?exam_id=<?= (int) $exam['id'] ?>" class="btn btn-outline-primary btn-sm">Questions</a>

                            <button
                                type="button"
                                class="btn btn-primary btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#editExamModal"
                                data-id="<?= (int) $exam['id'] ?>"
                                data-title="<?= htmlspecialchars($exam['title']) ?>"
                                data-subject="<?= htmlspecialchars($exam['subject']) ?>"
                                data-semester="<?= htmlspecialchars($exam['semester']) ?>"
                                data-questions="<?= (int) $exam['total_questions'] ?>"
                                data-duration="<?= (int) $exam['duration_minutes'] ?>"
                                data-date="<?= htmlspecialchars($exam['exam_date']) ?>"
                                data-status="<?= htmlspecialchars($exam['status']) ?>"
                            >Edit</button>

                            <form method="post" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(teacherCsrfToken()) ?>">
                                <input type="hidden" name="action" value="toggle_exam">
                                <input type="hidden" name="exam_id" value="<?= (int) $exam['id'] ?>">
                                <input type="hidden" name="next_active" value="<?= $isActive ? 0 : 1 ?>">
                                <button type="submit" class="btn btn-<?= $isActive ? 'warning' : 'success' ?> btn-sm"><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
                            </form>

                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this exam and all its questions?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(teacherCsrfToken()) ?>">
                                <input type="hidden" name="action" value="delete_exam">
                                <input type="hidden" name="exam_id" value="<?= (int) $exam['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="editExamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Exam</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(teacherCsrfToken()) ?>">
                    <input type="hidden" name="action" value="update_exam">
                    <input type="hidden" name="exam_id" id="edit_exam_id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Exam Title</label>
                            <input type="text" name="title" id="edit_title" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" id="edit_subject" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Semester / Batch</label>
                            <input type="text" name="semester" id="edit_semester" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Questions</label>
                            <input type="number" name="total_questions" id="edit_questions" min="1" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Duration</label>
                            <input type="number" name="duration_minutes" id="edit_duration" min="1" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Exam Date</label>
                            <input type="date" name="exam_date" id="edit_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="scheduled">Scheduled</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const editExamModal = document.getElementById('editExamModal');
if (editExamModal) {
    editExamModal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        if (!button) return;

        document.getElementById('edit_exam_id').value = button.getAttribute('data-id') || '';
        document.getElementById('edit_title').value = button.getAttribute('data-title') || '';
        document.getElementById('edit_subject').value = button.getAttribute('data-subject') || '';
        document.getElementById('edit_semester').value = button.getAttribute('data-semester') || '';
        document.getElementById('edit_questions').value = button.getAttribute('data-questions') || '';
        document.getElementById('edit_duration').value = button.getAttribute('data-duration') || '';
        document.getElementById('edit_date').value = button.getAttribute('data-date') || '';
        document.getElementById('edit_status').value = button.getAttribute('data-status') || 'scheduled';
    });
}
</script>

<?php teacherRenderFooter(); ?>
