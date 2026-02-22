<?php
require_once '../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($email === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        $checkStmt = $conn->prepare('SELECT id FROM students WHERE email = ? LIMIT 1');

        if (!$checkStmt) {
            $error = 'Unable to process request. Please try again.';
        } else {
            $checkStmt->bind_param('s', $email);
            $checkStmt->execute();
            $student = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();

            if (!$student) {
                $error = 'No student account found with this email.';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare('UPDATE students SET password = ? WHERE email = ?');

                if (!$updateStmt) {
                    $error = 'Unable to update password. Please try again.';
                } else {
                    $updateStmt->bind_param('ss', $hashedPassword, $email);

                    if ($updateStmt->execute()) {
                        $success = 'Password updated successfully. You can now login.';
                    } else {
                        $error = 'Failed to update password. Please try again.';
                    }

                    $updateStmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ExamPro</title>
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
        .reset-container {
            width: 100%;
            max-width: 520px;
            background: #fff;
            border: 1px solid #e8ebf6;
            border-radius: 24px;
            box-shadow: 0 22px 50px rgba(67, 56, 202, 0.15);
            padding: 2.5rem 2.3rem;
        }
        .header {
            text-align: center;
            margin-bottom: 1.7rem;
        }
        .header .logo {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.9rem;
            font-weight: 700;
            margin-bottom: 0.6rem;
        }
        .header .logo i { color: #4f46e5; }
        .accent { color: #4f46e5; }
        .header h2 {
            font-size: 1.75rem;
            color: #0f1d3a;
            margin-bottom: 0.3rem;
        }
        .header p {
            color: #6b7280;
            font-size: 0.95rem;
        }
        .form-group { margin-bottom: 1.1rem; }
        label {
            display: block;
            margin-bottom: 0.45rem;
            font-size: 0.95rem;
            color: #1f2d4b;
            font-weight: 600;
        }
        .input-wrap { position: relative; }
        .input-wrap i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #98a1b3;
            font-size: 0.95rem;
        }
        input {
            width: 100%;
            border: 2px solid #e6eaf2;
            border-radius: 12px;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            background: #f8fafc;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            transition: all 0.25s ease;
        }
        input:focus {
            outline: none;
            border-color: #4f46e5;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }
        .btn {
            width: 100%;
            border: none;
            border-radius: 12px;
            padding: 0.92rem;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(90deg, #4f46e5 0%, #4338ca 100%);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin-top: 0.35rem;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.24);
        }
        .alert {
            border-radius: 10px;
            padding: 0.75rem 0.9rem;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
        .footer {
            text-align: center;
            margin-top: 1.2rem;
            font-size: 0.92rem;
        }
        .footer a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
        }
        .footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <span>Exam<span class="accent">Pro</span></span>
            </div>
            <h2>Forgot Password</h2>
            <p>Reset your student account password</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password" minlength="8" required>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" minlength="8" required>
                </div>
            </div>
            <button type="submit" class="btn"><i class="fas fa-key"></i> Reset Password</button>
        </form>

        <div class="footer">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>
