<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!adminVerifyCsrf($csrf)) {
        adminFlash('error', 'Invalid request token. Please refresh and try again.');
        header('Location: students.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rollNumber = trim($_POST['roll_number'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';
    $classIdInput = (int) ($_POST['class_id'] ?? 0);

    if ($name === '' || $email === '' || $rollNumber === '' || $department === '' || $password === '') {
        adminFlash('error', 'All student fields are required.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        adminFlash('error', 'Invalid email format.');
    } elseif (strlen($password) < 8) {
        adminFlash('error', 'Password must be at least 8 characters.');
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO students (name, email, roll_number, department, class_id, password) VALUES (?, ?, ?, ?, ?, ?)');
        $classIdValue = $classIdInput > 0 ? $classIdInput : null;
        $stmt->bind_param('ssssis', $name, $email, $rollNumber, $department, $classIdValue, $hashedPassword);

        if ($stmt->execute()) {
            adminFlash('success', 'Student added successfully.');
        } elseif ((int) $stmt->errno === 1062) {
            adminFlash('error', 'Student email already exists.');
        } else {
            adminFlash('error', 'Failed to add student.');
        }

        $stmt->close();
    }

    header('Location: students.php');
    exit;
}

$classId = (int) ($_GET['class_id'] ?? 0);

$classes = [];
$classStmt = $conn->prepare('SELECT id, name FROM classes ORDER BY name');
if ($classStmt && $classStmt->execute()) {
    $classes = $classStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$classStmt?->close();

$listSql = 'SELECT s.id, s.name, s.email, s.roll_number, COALESCE(c.name, s.department) AS class_name, s.created_at FROM students s LEFT JOIN classes c ON c.id = s.class_id';
$params = [];
$types = '';
if ($classId > 0) {
    $listSql .= ' WHERE s.class_id = ?';
    $params[] = $classId;
    $types .= 'i';
}
$listSql .= ' ORDER BY s.id DESC LIMIT ?';
$limit = 20;
$params[] = $limit;
$types .= 'i';

$listStmt = $conn->prepare($listSql);
if ($types !== '') {
    $bind = [$types];
    foreach ($params as $i => $value) {
        $bind[] = &$params[$i];
    }
    call_user_func_array([$listStmt, 'bind_param'], $bind);
}
$listStmt->execute();
$students = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

adminRenderHeader('Students', 'students');
?>

<?php if ($error = adminFlash('error')): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success = adminFlash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card content-card mb-4">
    <div class="card-body">
        <h2 class="h4 mb-3">Add Student</h2>
        <form method="post" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(adminCsrfToken()) ?>">
            <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Roll Number</label>
                <input type="text" name="roll_number" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Department/Class</label>
                <input type="text" name="department" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Class (Optional)</label>
                <select name="class_id" class="form-select">
                    <option value="0">Select</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= (int) $class['id'] ?>"><?= htmlspecialchars((string) $class['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" minlength="8" required>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-indigo">Add Student</button>
            </div>
        </form>
    </div>
</div>

<div class="card content-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h4 mb-0">Students List</h2>
            <form method="get" class="d-flex gap-2">
                <select name="class_id" class="form-select form-select-sm">
                    <option value="0">All Classes</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= (int) $class['id'] ?>" <?= $classId === (int) $class['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $class['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
            </form>
        </div>
        <div class="table-responsive table-shell">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Roll No</th>
                    <th>Class</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="5" class="text-center text-secondary">No students found.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $student['name']) ?></td>
                            <td><?= htmlspecialchars((string) $student['email']) ?></td>
                            <td><?= htmlspecialchars((string) $student['roll_number']) ?></td>
                            <td><?= htmlspecialchars((string) $student['class_name']) ?></td>
                            <td><?= htmlspecialchars((string) $student['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php adminRenderFooter(); ?>
