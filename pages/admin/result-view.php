<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$resultId = isset($_GET['result_id']) ? (int) $_GET['result_id'] : 0;
if ($resultId <= 0) {
    adminFlash('error', 'Invalid result request.');
    header('Location: results.php');
    exit;
}

$stmt = $conn->prepare(
    "SELECT se.id, se.score, se.total, se.percentage, se.submitted_at,
            s.id AS student_id, s.name AS student_name, s.email, s.department, s.roll_number,
            e.id AS exam_id, e.title AS exam_title, e.subject, e.exam_date
     FROM student_exams se
     JOIN students s ON s.id = se.student_id
     JOIN exams e ON e.id = se.exam_id
     WHERE se.id = ?
     LIMIT 1"
);
$stmt->bind_param('i', $resultId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    adminFlash('error', 'Result not found.');
    header('Location: results.php');
    exit;
}

$passThreshold = 40;
$status = ((float) $result['percentage'] >= $passThreshold) ? 'Pass' : 'Fail';

adminRenderHeader('Result Details', 'results');
?>

<div class="mb-3">
    <a href="results.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to Results</a>
</div>

<div class="card content-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h4 fw-semibold mb-0">Result Details</h2>
            <a href="results-export.php?type=single&id=<?= (int) $result['id'] ?>" class="btn btn-outline-primary btn-sm">Download PDF</a>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-secondary small">Student</div>
                <div class="form-control bg-light"><?= htmlspecialchars((string) $result['student_name']) ?></div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary small">Email</div>
                <div class="form-control bg-light"><?= htmlspecialchars((string) $result['email']) ?></div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary small">Class</div>
                <div class="form-control bg-light"><?= htmlspecialchars((string) $result['department']) ?></div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary small">Roll Number</div>
                <div class="form-control bg-light"><?= htmlspecialchars((string) $result['roll_number']) ?></div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary small">Exam</div>
                <div class="form-control bg-light"><?= htmlspecialchars((string) $result['exam_title']) ?></div>
            </div>
            <div class="col-md-6">
                <div class="text-secondary small">Subject</div>
                <div class="form-control bg-light"><?= htmlspecialchars((string) $result['subject']) ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-secondary small">Score</div>
                <div class="form-control bg-light"><?= (int) $result['score'] ?> / <?= (int) $result['total'] ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-secondary small">Percentage</div>
                <div class="form-control bg-light"><?= htmlspecialchars((string) $result['percentage']) ?>%</div>
            </div>
            <div class="col-md-3">
                <div class="text-secondary small">Status</div>
                <div class="form-control bg-light">
                    <span class="badge <?= $status === 'Pass' ? 'bg-success' : 'bg-danger' ?>"><?= $status ?></span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-secondary small">Submitted</div>
                <div class="form-control bg-light"><?= htmlspecialchars((string) $result['submitted_at']) ?></div>
            </div>
        </div>
    </div>
</div>

<?php adminRenderFooter(); ?>
