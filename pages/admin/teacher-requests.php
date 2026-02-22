<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/../../config/pdo.php';
require_once __DIR__ . '/../../config/teacher_module.php';

ensureTeacherModuleSchema($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string) ($_POST['csrf_token'] ?? '');
    $teacherId = (int) ($_POST['teacher_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    if (!adminVerifyCsrf($csrf)) {
        adminFlash('error', 'Invalid request token. Please refresh and try again.');
        header('Location: teacher-requests.php');
        exit;
    }

    if ($teacherId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
        adminFlash('error', 'Invalid request payload.');
        header('Location: teacher-requests.php');
        exit;
    }

    if ($action === 'approve') {
        $teacherLookup = $pdo->prepare("SELECT id, email, first_name, last_name FROM teachers WHERE id = :id AND status = 'pending' LIMIT 1");
        $teacherLookup->execute([':id' => $teacherId]);
        $teacher = $teacherLookup->fetch();
        if (!$teacher) {
            adminFlash('error', 'No pending application found for this teacher.');
            header('Location: teacher-requests.php');
            exit;
        }

        $stmt = $pdo->prepare("UPDATE teachers SET status = 'approved', rejection_reason = NULL, approval_date = NOW() WHERE id = :id AND status = 'pending'");
        $stmt->execute([':id' => $teacherId]);
        if ($stmt->rowCount() > 0) {
            teacherNotification($pdo, $teacherId, 'teacher', 'Application Approved', 'Your teacher registration has been approved. You can now log in.');
            $emailOk = sendTeacherStatusEmail(
                (string) $teacher['email'],
                trim((string) ($teacher['first_name'] . ' ' . $teacher['last_name'])),
                'approved'
            );
            if ($emailOk) {
                adminFlash('success', 'Teacher application approved and email sent.');
            } else {
                adminFlash('success', 'Teacher application approved. Email could not be sent (mail not configured).');
            }
        } else {
            adminFlash('error', 'No pending application found for this teacher.');
        }
    }

    if ($action === 'reject') {
        $reason = trim((string) ($_POST['reason'] ?? ''));
        if ($reason === '') {
            adminFlash('error', 'Rejection reason is required.');
            header('Location: teacher-requests.php');
            exit;
        }

        $teacherLookup = $pdo->prepare("SELECT id, email, first_name, last_name FROM teachers WHERE id = :id AND status = 'pending' LIMIT 1");
        $teacherLookup->execute([':id' => $teacherId]);
        $teacher = $teacherLookup->fetch();
        if (!$teacher) {
            adminFlash('error', 'No pending application found for this teacher.');
            header('Location: teacher-requests.php');
            exit;
        }

        $stmt = $pdo->prepare("UPDATE teachers SET status = 'rejected', rejection_reason = :reason, approval_date = NOW() WHERE id = :id AND status = 'pending'");
        $stmt->execute([
            ':id' => $teacherId,
            ':reason' => $reason,
        ]);
        if ($stmt->rowCount() > 0) {
            teacherNotification($pdo, $teacherId, 'teacher', 'Application Rejected', 'Your teacher registration was rejected. Reason: ' . $reason);
            $emailOk = sendTeacherStatusEmail(
                (string) $teacher['email'],
                trim((string) ($teacher['first_name'] . ' ' . $teacher['last_name'])),
                'rejected',
                $reason
            );
            if ($emailOk) {
                adminFlash('success', 'Teacher application rejected and email sent.');
            } else {
                adminFlash('success', 'Teacher application rejected. Email could not be sent (mail not configured).');
            }
        } else {
            adminFlash('error', 'No pending application found for this teacher.');
        }
    }

    header('Location: teacher-requests.php');
    exit;
}

$pendingStmt = $pdo->prepare(
    "SELECT id, registration_id, first_name, last_name, email, phone, qualification, bed_status, university, passing_year, experience_years, subjects, photo_path, signature_path, certificate_path, id_proof_path, created_at
     FROM teachers
     WHERE status = 'pending'
     ORDER BY created_at DESC"
);
$pendingStmt->execute();
$pendingTeachers = $pendingStmt->fetchAll();

adminRenderHeader('Teacher Requests', 'teacher_requests');
?>

<?php if ($flash = adminFlash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>
<?php if ($flash = adminFlash('error')): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<div class="card content-card">
    <div class="card-header bg-transparent border-0 pt-4 px-4">
        <h2 class="h5 fw-semibold mb-0"><i class="fa-solid fa-user-check me-2"></i>Pending Teacher Applications</h2>
    </div>
    <div class="card-body pt-3 px-4 pb-4">
        <?php if (!$pendingTeachers): ?>
            <div class="text-secondary">No pending applications found.</div>
        <?php else: ?>
            <div class="table-responsive table-shell">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Reg ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Academic</th>
                        <th>Documents</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pendingTeachers as $teacher): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $teacher['registration_id']) ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars(trim((string) ($teacher['first_name'] . ' ' . $teacher['last_name']))) ?></div>
                                <div class="small text-secondary">Subject: <?= htmlspecialchars((string) $teacher['subjects']) ?></div>
                            </td>
                            <td><?= htmlspecialchars((string) $teacher['email']) ?></td>
                            <td><?= htmlspecialchars((string) $teacher['phone']) ?></td>
                            <td>
                                <div class="small"><?= htmlspecialchars((string) $teacher['qualification']) ?></div>
                                <div class="small text-secondary"><?= htmlspecialchars((string) $teacher['university']) ?> (<?= htmlspecialchars((string) $teacher['passing_year']) ?>)</div>
                                <div class="small text-secondary">B.Ed: <?= htmlspecialchars((string) $teacher['bed_status']) ?> | Exp: <?= htmlspecialchars((string) $teacher['experience_years']) ?> yrs</div>
                            </td>
                            <td>
                                <?php
                                $docs = [
                                    'Photo' => $teacher['photo_path'],
                                    'Signature' => $teacher['signature_path'],
                                    'Certificate' => $teacher['certificate_path'],
                                    'ID Proof' => $teacher['id_proof_path'],
                                ];
                                ?>
                                <?php foreach ($docs as $label => $path): ?>
                                    <?php if (!empty($path)): ?>
                                        <a class="d-block small" target="_blank" href="../../<?= htmlspecialchars((string) $path) ?>"><?= htmlspecialchars($label) ?></a>
                                    <?php else: ?>
                                        <span class="d-block small text-secondary"><?= htmlspecialchars($label) ?>: N/A</span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <form method="post" class="d-flex flex-column gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(adminCsrfToken()) ?>">
                                    <input type="hidden" name="teacher_id" value="<?= (int) $teacher['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason (for reject)">
                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php adminRenderFooter(); ?>
