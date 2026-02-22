<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$teacherId = (int) ($_SESSION['teacher_id'] ?? 0);
$stmt = $conn->prepare(
    "SELECT id, registration_id, name, first_name, last_name, email, phone, dob, gender, qualification, bed_status, university, passing_year, experience_years, subjects, photo_path, signature_path, certificate_path, id_proof_path, status, rejection_reason, approval_date, submitted_at, created_at, updated_at
     FROM teachers
     WHERE id = ?
     LIMIT 1"
);
$stmt->bind_param('i', $teacherId);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teacher) {
    header('Location: logout.php');
    exit;
}

function tf(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$fullName = trim((string) ($teacher['name'] ?? 'Teacher'));
$status = strtolower((string) ($teacher['status'] ?? 'approved'));
$badgeClass = 'bg-success';
if ($status === 'pending') {
    $badgeClass = 'bg-warning text-dark';
} elseif ($status === 'rejected') {
    $badgeClass = 'bg-danger';
}

$docs = [
    'Photo' => (string) ($teacher['photo_path'] ?? ''),
    'Signature' => (string) ($teacher['signature_path'] ?? ''),
    'Certificate' => (string) ($teacher['certificate_path'] ?? ''),
    'ID Proof' => (string) ($teacher['id_proof_path'] ?? ''),
];

teacherRenderHeader('Teacher Profile', 'profile');
?>

<div class="card content-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h4 fw-semibold mb-0">Application Profile</h2>
            <span class="badge <?= $badgeClass ?>"><?= tf(ucfirst($status)) ?></span>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="border rounded-4 p-4 bg-light text-center h-100">
                    <?php if (!empty($teacher['photo_path']) && is_file(__DIR__ . '/../../' . $teacher['photo_path'])): ?>
                        <img src="../../<?= tf((string) $teacher['photo_path']) ?>" alt="Teacher Photo" class="rounded-circle border border-3 border-primary-subtle mb-3" style="width:140px;height:140px;object-fit:cover;">
                    <?php else: ?>
                        <div class="rounded-circle border border-3 border-primary-subtle bg-white d-inline-flex align-items-center justify-content-center mb-3" style="width:140px;height:140px;">
                            <span class="fw-bold fs-1 text-primary"><?= tf(strtoupper(substr($fullName, 0, 1))) ?></span>
                        </div>
                    <?php endif; ?>
                    <h5 class="fw-bold mb-1"><?= tf($fullName) ?></h5>
                    <div class="text-secondary small"><?= tf((string) $teacher['email']) ?></div>
                    <div class="text-secondary small mt-1">Reg ID: <?= tf((string) ($teacher['registration_id'] ?? 'N/A')) ?></div>
                </div>
            </div>

            <div class="col-lg-8">
                <h3 class="h5 fw-semibold mb-3">Personal & Academic Details</h3>
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-secondary small">First Name</div><div class="form-control bg-light"><?= tf((string) ($teacher['first_name'] ?? '')) ?></div></div>
                    <div class="col-md-6"><div class="text-secondary small">Last Name</div><div class="form-control bg-light"><?= tf((string) ($teacher['last_name'] ?? '')) ?></div></div>
                    <div class="col-md-6"><div class="text-secondary small">Phone</div><div class="form-control bg-light"><?= tf((string) ($teacher['phone'] ?? '')) ?></div></div>
                    <div class="col-md-6"><div class="text-secondary small">Gender</div><div class="form-control bg-light"><?= tf((string) ($teacher['gender'] ?? '')) ?></div></div>
                    <div class="col-md-6"><div class="text-secondary small">DOB</div><div class="form-control bg-light"><?= tf((string) ($teacher['dob'] ?? '')) ?></div></div>
                    <div class="col-md-6"><div class="text-secondary small">Qualification</div><div class="form-control bg-light"><?= tf((string) ($teacher['qualification'] ?? '')) ?></div></div>
                    <div class="col-md-6"><div class="text-secondary small">B.Ed Status</div><div class="form-control bg-light"><?= tf((string) ($teacher['bed_status'] ?? '')) ?></div></div>
                    <div class="col-md-6"><div class="text-secondary small">University</div><div class="form-control bg-light"><?= tf((string) ($teacher['university'] ?? '')) ?></div></div>
                    <div class="col-md-6"><div class="text-secondary small">Passing Year</div><div class="form-control bg-light"><?= tf((string) ($teacher['passing_year'] ?? '')) ?></div></div>
                    <div class="col-md-6"><div class="text-secondary small">Experience</div><div class="form-control bg-light"><?= tf((string) ($teacher['experience_years'] ?? '')) ?> years</div></div>
                    <div class="col-12"><div class="text-secondary small">Subjects</div><div class="form-control bg-light"><?= tf((string) ($teacher['subjects'] ?? '')) ?></div></div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <h3 class="h5 fw-semibold mb-3">Uploaded Documents</h3>
        <div class="row g-3">
            <?php foreach ($docs as $label => $path): ?>
                <div class="col-md-6 col-xl-3">
                    <div class="border rounded-3 p-3 bg-light h-100">
                        <div class="fw-semibold small mb-2"><?= tf($label) ?></div>
                        <?php if ($path !== '' && is_file(__DIR__ . '/../../' . $path)): ?>
                            <a href="../../<?= tf($path) ?>" target="_blank" class="btn btn-sm btn-outline-primary">Preview</a>
                        <?php else: ?>
                            <span class="text-secondary small">Not uploaded</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <hr class="my-4">

        <h3 class="h5 fw-semibold mb-3">Application Timeline</h3>
        <div class="row g-3">
            <div class="col-md-3"><div class="text-secondary small">Submitted At</div><div class="fw-medium"><?= tf((string) ($teacher['submitted_at'] ?? 'N/A')) ?></div></div>
            <div class="col-md-3"><div class="text-secondary small">Approval Date</div><div class="fw-medium"><?= tf((string) ($teacher['approval_date'] ?? 'N/A')) ?></div></div>
            <div class="col-md-3"><div class="text-secondary small">Created At</div><div class="fw-medium"><?= tf((string) ($teacher['created_at'] ?? 'N/A')) ?></div></div>
            <div class="col-md-3"><div class="text-secondary small">Last Updated</div><div class="fw-medium"><?= tf((string) ($teacher['updated_at'] ?? 'N/A')) ?></div></div>
        </div>

        <?php if ($status === 'rejected' && !empty($teacher['rejection_reason'])): ?>
            <div class="alert alert-danger mt-4 mb-0">
                <strong>Rejection Reason:</strong> <?= tf((string) $teacher['rejection_reason']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php teacherRenderFooter(); ?>

