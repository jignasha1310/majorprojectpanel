<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_SESSION['teacher_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!teacherVerifyCsrf($csrf)) {
        $error = 'Invalid request token. Please refresh and try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $stmt = $conn->prepare('SELECT id, name, email, password FROM teachers WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $teacher = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$teacher || !password_verify($password, $teacher['password'])) {
            $error = 'Invalid credentials.';
        } else {
            $_SESSION['teacher_id'] = (int) $teacher['id'];
            $_SESSION['teacher_name'] = $teacher['name'];
            $_SESSION['teacher_email'] = $teacher['email'];
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teacher Login - ExamPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
    <div class="card shadow border-0" style="max-width: 460px; width: 100%; border-radius: 16px;">
        <div class="card-body p-4 p-md-5">
            <h3 class="fw-bold mb-1">Teacher Panel Login</h3>
            <p class="text-secondary mb-4">Use your teacher credentials to continue.</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($flash = teacherFlash('success')): ?>
                <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(teacherCsrfToken()) ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <a href="../../index.html" class="btn btn-link text-decoration-none px-0 mt-2">
                <i class="bi bi-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
</div>
</body>
</html>
