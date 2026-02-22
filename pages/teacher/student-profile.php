<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
if ($studentId <= 0) {
    teacherFlash('error', 'Invalid student profile request.');
    header('Location: dashboard.php');
    exit;
}

$stmt = $conn->prepare('SELECT id, name, first_name, last_name, email, phone, roll_number, department, profile_image, created_at, updated_at FROM students WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    teacherFlash('error', 'Student not found.');
    header('Location: dashboard.php');
    exit;
}

$statsStmt = $conn->prepare('SELECT COUNT(*) AS attempts, COALESCE(AVG(percentage), 0) AS avg_score FROM student_exams WHERE student_id = ?');
$statsStmt->bind_param('i', $studentId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();

$displayName = trim((string) ($student['name'] ?? 'Student'));
$firstName = trim((string) ($student['first_name'] ?? ''));
$lastName = trim((string) ($student['last_name'] ?? ''));
if ($firstName === '' && $displayName !== '') {
    $parts = preg_split('/\s+/', $displayName, 2);
    $firstName = trim((string) ($parts[0] ?? ''));
    $lastName = $lastName !== '' ? $lastName : trim((string) ($parts[1] ?? ''));
}

$imagePath = trim((string) ($student['profile_image'] ?? ''));
$imageUrl = '';
if ($imagePath !== '' && preg_match('/^uploads\/profile\/[A-Za-z0-9._-]+$/', $imagePath) && is_file(__DIR__ . '/../../' . $imagePath)) {
    $imageUrl = '../../' . $imagePath;
}

teacherRenderHeader('Student Profile', 'dashboard');
?>

<?php if ($flash = teacherFlash('error')): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<div class="mb-3">
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
</div>

<div class="card content-card">
    <div class="card-body">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="border rounded-4 p-4 text-center bg-light h-100">
                    <?php if ($imageUrl !== ''): ?>
                        <img src="<?= htmlspecialchars($imageUrl) ?>" alt="Student image" class="rounded-circle border border-3 border-primary-subtle mb-3" style="width: 140px; height: 140px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle border border-3 border-primary-subtle bg-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 140px; height: 140px;">
                            <span class="fw-bold fs-1 text-primary"><?= htmlspecialchars(strtoupper(substr($displayName, 0, 1))) ?></span>
                        </div>
                    <?php endif; ?>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($displayName) ?></h4>
                    <p class="text-secondary mb-0"><?= htmlspecialchars((string) $student['email']) ?></p>
                </div>
            </div>
            <div class="col-lg-8">
                <h5 class="fw-semibold mb-3">Student Information</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-secondary small mb-1">First Name</label>
                        <div class="form-control bg-light"><?= htmlspecialchars($firstName) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small mb-1">Last Name</label>
                        <div class="form-control bg-light"><?= htmlspecialchars($lastName) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small mb-1">Roll Number</label>
                        <div class="form-control bg-light"><?= htmlspecialchars((string) $student['roll_number']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small mb-1">Class / Department</label>
                        <div class="form-control bg-light"><?= htmlspecialchars((string) $student['department']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small mb-1">Phone</label>
                        <div class="form-control bg-light"><?= htmlspecialchars((string) ($student['phone'] ?: 'N/A')) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary small mb-1">Email</label>
                        <div class="form-control bg-light"><?= htmlspecialchars((string) $student['email']) ?></div>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="fw-semibold mb-3">Exam Performance Snapshot</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="text-secondary small">Total Attempts</div>
                            <div class="h4 fw-bold mb-0"><?= (int) ($stats['attempts'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="text-secondary small">Average Score</div>
                            <div class="h4 fw-bold mb-0"><?= round((float) ($stats['avg_score'] ?? 0), 2) ?>%</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small">Registered At</div>
                        <div class="fw-medium"><?= htmlspecialchars((string) $student['created_at']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small">Last Updated</div>
                        <div class="fw-medium"><?= htmlspecialchars((string) ($student['updated_at'] ?: $student['created_at'])) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php teacherRenderFooter(); ?>
