<?php
require_once '../config/db.php';

$error = '';
$success = '';

if (isset($_SESSION['student_id'])) {
    header('Location: student-panel.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, password FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $student = $result->fetch_assoc();
            if (password_verify($password, $student['password'])) {
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_name'] = $student['name'];
                header('Location: student-panel.php');
                exit;
            } else {
                $error = 'Invalid password.';
            }
        } else {
            $error = 'No account found with this email.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - ExamPro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #EEF2FF 0%, #C7D2FE 50%, #E0E7FF 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(79, 70, 229, 0.15);
            width: 100%;
            max-width: 440px;
            padding: 3rem 2.5rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .login-header .logo i { color: #4F46E5; font-size: 2rem; }
        .logo-accent { color: #4F46E5; }
        .login-header h2 { font-size: 1.5rem; color: #1E293B; margin-bottom: 0.25rem; }
        .login-header p { color: #64748B; font-size: 0.9rem; }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            color: #1E293B;
            margin-bottom: 0.4rem;
        }
        .form-group .input-wrapper {
            position: relative;
        }
        .form-group .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
        }
        .form-group input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.75rem;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #F8FAFC;
        }
        .form-group input:focus {
            outline: none;
            border-color: #4F46E5;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 0.9rem;
            background: #4F46E5;
            color: white;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
            min-height: 48px;
        }
        .btn-login:hover {
            background: #3730A3;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .alert-error {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        .alert-success {
            background: #F0FDF4;
            color: #16A34A;
            border: 1px solid #BBF7D0;
        }
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #64748B;
        }
        .login-footer a {
            color: #4F46E5;
            text-decoration: none;
            font-weight: 500;
        }
        .login-footer a:hover { text-decoration: underline; }
        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: #64748B;
            text-decoration: none;
            font-size: 0.85rem;
            margin-top: 1rem;
            transition: color 0.3s;
        }
        .back-home:hover { color: #4F46E5; }
        @media (max-width: 480px) {
            .login-container { padding: 2rem 1.5rem; }
            .login-header .logo { font-size: 1.5rem; }
            .login-header h2 { font-size: 1.25rem; }
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
            <h2>Student Login</h2>
            <p>Enter your credentials to access the student panel</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Registration successful! Please login.</div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="login-footer">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <a href="../index.html" class="back-home"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>
</body>
</html>
