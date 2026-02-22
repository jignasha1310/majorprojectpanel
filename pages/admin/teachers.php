<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!adminVerifyCsrf($csrf)) {
        adminFlash('error', 'Invalid request token. Please refresh and try again.');
        header('Location: teachers.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        adminFlash('error', 'All teacher fields are required.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        adminFlash('error', 'Invalid email format.');
    } elseif (strlen($password) < 8) {
        adminFlash('error', 'Password must be at least 8 characters.');
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO teachers (name, email, password) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $name, $email, $hashedPassword);

        if ($stmt->execute()) {
            adminFlash('success', 'Teacher added successfully.');
        } elseif ((int) $stmt->errno === 1062) {
            adminFlash('error', 'Teacher email already exists.');
        } else {
            adminFlash('error', 'Failed to add teacher.');
        }

        $stmt->close();
    }

    header('Location: teachers.php');
    exit;
}

$listStmt = $conn->prepare('SELECT id, name, email, created_at FROM teachers ORDER BY id DESC LIMIT ?');
$limit = 20;
$listStmt->bind_param('i', $limit);
$listStmt->execute();
$teachers = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

adminRenderHeader('Teachers', 'teachers');
?>

<?php if ($error = adminFlash('error')): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success = adminFlash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card content-card mb-4">
    <div class="card-body">
        <h2 class="h4 mb-3">Add Teacher</h2>
        <form method="post" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(adminCsrfToken()) ?>">
            <div class="col-md-5">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" minlength="8" required>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-indigo">Add Teacher</button>
            </div>
        </form>
    </div>
</div>

<div class="card content-card">
    <div class="card-body">
        <h2 class="h4 mb-3">Teachers List</h2>
        <div class="table-responsive table-shell">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($teachers)): ?>
                    <tr><td colspan="3" class="text-center text-secondary">No teachers found.</td></tr>
                <?php else: ?>
                    <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $teacher['name']) ?></td>
                            <td><?= htmlspecialchars((string) $teacher['email']) ?></td>
                            <td><?= htmlspecialchars((string) $teacher['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php adminRenderFooter(); ?>
