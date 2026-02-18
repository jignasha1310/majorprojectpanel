<?php
require_once '../config/db.php';

$error = '';
$success = '';

if (isset($_SESSION['student_id'])) {
    header('Location: student-panel.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $roll_number = trim($_POST['roll_number'] ?? '');
    $department = trim($_POST['department'] ?? 'BCA');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($roll_number) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check duplicate email
        $check = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO students (name, email, roll_number, department, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $roll_number, $department, $hashed);
            if ($stmt->execute()) {
                header('Location: login.php?registered=1');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - ExamPro</title>
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
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(79, 70, 229, 0.15);
            width: 100%;
            max-width: 500px;
            padding: 2.5rem 2.5rem;
        }
        .register-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }
        .register-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .register-header .logo i { color: #4F46E5; font-size: 2rem; }
        .logo-accent { color: #4F46E5; }
        .register-header h2 { font-size: 1.5rem; color: #1E293B; margin-bottom: 0.25rem; }
        .register-header p { color: #64748B; font-size: 0.9rem; }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .form-group {
            margin-bottom: 1.1rem;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #1E293B;
            margin-bottom: 0.35rem;
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
            font-size: 0.9rem;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.75rem;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: #F8FAFC;
        }
        .form-group select {
            padding-left: 2.75rem;
            appearance: none;
            cursor: pointer;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4F46E5;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .btn-register {
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
        .btn-register:hover {
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
        .register-footer {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.9rem;
            color: #64748B;
        }
        .register-footer a {
            color: #4F46E5;
            text-decoration: none;
            font-weight: 500;
        }
        .register-footer a:hover { text-decoration: underline; }
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
            .register-container { padding: 2rem 1.5rem; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .register-header .logo { font-size: 1.5rem; }
            .register-header h2 { font-size: 1.25rem; }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <span>Exam<span class="logo-accent">Pro</span></span>
            </div>
            <h2>Create Student Account</h2>
            <p>Fill in your details to register</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Full Name</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="name" name="name" placeholder="Your full name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="roll_number">Roll Number</label>
                    <div class="input-wrapper">
                        <i class="fas fa-id-card"></i>
                        <input type="text" id="roll_number" name="roll_number" placeholder="BCA/2024/001" value="<?= htmlspecialchars($_POST['roll_number'] ?? '') ?>" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="department">Department</label>
                <div class="input-wrapper">
                    <i class="fas fa-building"></i>
                    <select id="department" name="department">
                        <option value="BCA" <?= (($_POST['department'] ?? '') === 'BCA') ? 'selected' : '' ?>>BCA</option>
                        <option value="BBA" <?= (($_POST['department'] ?? '') === 'BBA') ? 'selected' : '' ?>>BBA</option>
                        <option value="BSc" <?= (($_POST['department'] ?? '') === 'BSc') ? 'selected' : '' ?>>BSc</option>
                        <option value="BCom" <?= (($_POST['department'] ?? '') === 'BCom') ? 'selected' : '' ?>>BCom</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Min 6 characters" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="register-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
            <a href="../index.html" class="back-home"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>
</body>
</html>
