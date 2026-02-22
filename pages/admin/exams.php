<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!adminVerifyCsrf($csrf)) {
        adminFlash('error', 'Invalid request token. Please refresh and try again.');
        header('Location: exams.php');
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $totalQuestions = (int) ($_POST['total_questions'] ?? 0);
    $durationMinutes = (int) ($_POST['duration_minutes'] ?? 0);
    $examDate = trim($_POST['exam_date'] ?? '');
    $teacherIdRaw = trim($_POST['teacher_id'] ?? '');

    $teacherId = null;
    if ($teacherIdRaw !== '') {
        $teacherId = (int) $teacherIdRaw;
    }

    if ($title === '' || $subject === '' || $semester === '' || $examDate === '' || $totalQuestions < 1 || $durationMinutes < 1) {
        adminFlash('error', 'All exam fields are required and must be valid.');
    } else {
        $status = 'scheduled';
        $isActive = 0;
        $stmt = $conn->prepare('INSERT INTO exams (teacher_id, title, subject, semester, total_questions, duration_minutes, status, is_active, exam_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssiisis', $teacherId, $title, $subject, $semester, $totalQuestions, $durationMinutes, $status, $isActive, $examDate);

        if ($stmt->execute()) {
            adminFlash('success', 'Exam added successfully.');
        } else {
            adminFlash('error', 'Failed to add exam.');
        }
        $stmt->close();
    }

    header('Location: exams.php');
    exit;
}

$teacherStmt = $conn->prepare('SELECT id, name FROM teachers ORDER BY name ASC');
$teacherStmt->execute();
$teachers = $teacherStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$teacherStmt->close();

$listStmt = $conn->prepare('SELECT e.id, e.title, e.subject, e.semester, e.total_questions, e.duration_minutes, e.exam_date, e.is_active, t.name AS teacher_name FROM exams e LEFT JOIN teachers t ON t.id = e.teacher_id ORDER BY e.id DESC LIMIT ?');
$limit = 20;
$listStmt->bind_param('i', $limit);
$listStmt->execute();
$exams = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

adminRenderHeader('Exams', 'exams');
?>

<?php if ($error = adminFlash('error')): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success = adminFlash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card content-card mb-4">
    <div class="card-body">
        <h2 class="h4 mb-3">Add Exam</h2>
        <form method="post" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(adminCsrfToken()) ?>">

            <div class="col-md-6">
                <label class="form-label">Exam Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Class/Semester</label>
                <input type="text" name="semester" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Questions</label>
                <input type="number" name="total_questions" class="form-control" min="1" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Duration (mins)</label>
                <input type="number" name="duration_minutes" class="form-control" min="1" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Exam Date</label>
                <input type="date" name="exam_date" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Teacher</label>
                <select name="teacher_id" class="form-select">
                    <option value="">Unassigned</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= (int) $teacher['id'] ?>"><?= htmlspecialchars((string) $teacher['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-indigo">Add Exam</button>
            </div>
        </form>
    </div>
</div>

<div class="card content-card">
    <div class="card-body">
        <h2 class="h4 mb-3">Exams List</h2>
        <div class="table-responsive table-shell">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Class</th>
                    <th>Teacher</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($exams)): ?>
                    <tr><td colspan="6" class="text-center text-secondary">No exams found.</td></tr>
                <?php else: ?>
                    <?php foreach ($exams as $exam): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $exam['title']) ?></td>
                            <td><?= htmlspecialchars((string) $exam['subject']) ?></td>
                            <td><?= htmlspecialchars((string) $exam['semester']) ?></td>
                            <td><?= htmlspecialchars((string) ($exam['teacher_name'] ?? 'Unassigned')) ?></td>
                            <td><?= htmlspecialchars((string) $exam['exam_date']) ?></td>
                            <td>
                                <?php $isActive = (int) ($exam['is_active'] ?? 0) === 1; ?>
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

<?php adminRenderFooter(); ?>
