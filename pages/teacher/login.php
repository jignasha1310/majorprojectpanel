<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_SESSION['teacher_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$prefillEmail = '';
$rememberChecked = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    $rememberChecked = isset($_POST['remember_me']);
    $prefillEmail = $email;

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

            if ($rememberChecked) {
                setcookie('remember_teacher_email', $email, time() + (60 * 60 * 24 * 30), '/');
            } else {
                setcookie('remember_teacher_email', '', time() - 3600, '/');
            }

            header('Location: dashboard.php');
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_COOKIE['remember_teacher_email'])) {
    $prefillEmail = trim((string) $_COOKIE['remember_teacher_email']);
    if ($prefillEmail !== '') {
        $rememberChecked = true;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teacher Login - ExamPro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #cfd7ff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-container {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #e8ebf6;
            box-shadow: 0 22px 50px rgba(67, 56, 202, 0.15);
            width: 100%;
            max-width: 520px;
            padding: 2.8rem 2.7rem 2.6rem;
        }
        .login-header { text-align: center; margin-bottom: 2.15rem; }
        .login-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            font-size: 2.15rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .login-header .logo i { color: #4f46e5; font-size: 2.2rem; }
        .logo-accent { color: #4f46e5; }
        .login-header h2 { font-size: 2.4rem; color: #0f1d3a; margin-bottom: 0.45rem; font-weight: 700; }
        .login-header p { color: #6b7280; font-size: 0.98rem; max-width: 330px; margin: 0 auto; line-height: 1.45; }
        .form-group { margin-bottom: 1.5rem; }
        .row-inline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.7rem;
            margin: 0.2rem 0 1.3rem;
        }
        .remember-wrap {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.92rem;
            color: #4b5563;
        }
        .remember-wrap input {
            width: 16px;
            height: 16px;
            accent-color: #4f46e5;
        }
        .forgot-link {
            font-size: 0.9rem;
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
        }
        .forgot-link:hover { text-decoration: underline; }
        .form-group label {
            display: block;
            font-size: 1.05rem;
            font-weight: 600;
            color: #1f2d4b;
            margin-bottom: 0.7rem;
        }
        .input-wrapper { position: relative; }
        .input-wrapper i {
            position: absolute;
            left: 1.15rem;
            top: 50%;
            transform: translateY(-50%);
            color: #98a1b3;
            font-size: 1rem;
        }
        .form-group input {
            width: 100%;
            border: 2px solid #e6eaf2;
            border-radius: 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            padding: 0.95rem 1rem 0.95rem 3rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        .form-group input:focus {
            outline: none;
            border-color: #4f46e5;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }
        .btn-login {
            width: 100%;
            padding: 0.95rem;
            color: white;
            border: none;
            border-radius: 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 1.08rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 52px;
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
        }
        .btn-login:hover {
            background: linear-gradient(90deg, #4338ca 0%, #3730a3 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.28);
        }
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 10px;
            font-size: 0.92rem;
            margin-bottom: 1.1rem;
        }
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .login-footer {
            text-align: center;
            margin-top: 1.55rem;
            font-size: 0.96rem;
            color: #667085;
        }
        .login-footer a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
        }
        .login-footer a:hover { text-decoration: underline; }
        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            color: #4f46e5;
            text-decoration: none;
            font-size: 0.95rem;
            margin-top: 0.95rem;
            transition: color 0.3s;
            font-weight: 600;
        }
        .back-home:hover { color: #3730a3; }
        @media (max-width: 900px) {
            .login-container { max-width: 520px; padding: 2.2rem 1.6rem 2.1rem; }
            .login-header .logo { font-size: 1.9rem; }
            .login-header .logo i { font-size: 1.6rem; }
            .login-header h2 { font-size: 2rem; }
            .login-header p { font-size: 0.95rem; }
            .form-group label { font-size: 1.03rem; }
            .login-footer { font-size: 0.92rem; }
            .back-home { font-size: 0.9rem; }
            .row-inline { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>Exam<span class="logo-accent">Pro</span></span>
        </div>
        <h2>Teacher Login</h2>
        <p>Enter your credentials to access the teacher panel</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($flash = teacherFlash('success')): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(teacherCsrfToken()) ?>">
        <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-wrapper">
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($prefillEmail) ?>" placeholder="teacher@email.com" required>
            </div>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
        </div>
        <div class="row-inline">
            <label class="remember-wrap" for="remember_me">
                <input type="checkbox" id="remember_me" name="remember_me" value="1" <?= $rememberChecked ? 'checked' : '' ?>>
                <span>Remember me</span>
            </label>
            <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
        </div>
        <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</button>
    </form>

    <div class="login-footer">
        <a href="../../index.html" class="back-home"><i class="fas fa-arrow-left"></i> Back to Home</a>
    </div>
</div>
</body>
</html>
