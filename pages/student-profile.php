<?php
require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$success = '';
$error = '';

// Get student data
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email)) {
        $error = 'Name and email are required.';
    } else {
        // Check duplicate email
        $check = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $student_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'This email is already used by another account.';
        } else {
            // Update profile
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error = 'Passwords do not match.';
                } elseif (strlen($new_password) < 6) {
                    $error = 'Password must be at least 6 characters.';
                } else {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $update = $conn->prepare("UPDATE students SET name=?, email=?, department=?, password=? WHERE id=?");
                    $update->bind_param("ssssi", $name, $email, $department, $hashed, $student_id);
                    $update->execute();
                    $success = 'Profile and password updated!';
                }
            }
            if (empty($error) && empty($new_password)) {
                $update = $conn->prepare("UPDATE students SET name=?, email=?, department=? WHERE id=?");
                $update->bind_param("sssi", $name, $email, $department, $student_id);
                $update->execute();
                $success = 'Profile updated successfully!';
            }

            if ($success) {
                $_SESSION['student_name'] = $name;
                // Refresh student data
                $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $student = $stmt->get_result()->fetch_assoc();
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
    <title>My Profile - ExamPro Student Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="student-panel.css">
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="../index.html" class="logo"><i class="fas fa-graduation-cap"></i><span>Exam<span class="logo-accent">Pro</span></span></a>
            <span class="panel-label">Student Panel</span>
        </div>
        <nav class="sidebar-nav">
            <a href="student-panel.php" class="nav-item"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="student-profile.php" class="nav-item active"><i class="fas fa-user"></i><span>My Profile</span></a>
            <a href="scheduled-exams.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Scheduled Exams</span></a>
            <a href="take-exam.php" class="nav-item"><i class="fas fa-pen-fancy"></i><span>Take Exam</span></a>
            <a href="my-results.php" class="nav-item"><i class="fas fa-trophy"></i><span>My Results</span></a>
        </nav>
        <div class="sidebar-footer"><a href="logout.php" class="back-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
        <header class="topbar">
            <h1>My Profile</h1>
            <div class="topbar-actions">
                <span class="user-greeting">Welcome, <?= htmlspecialchars($student['name']) ?></span>
                <a href="logout.php" class="btn btn-login">Logout</a>
            </div>
        </header>
        <div class="dashboard-content">
            <div class="content-card">
                <h2 class="section-title-inline">Profile Management</h2>

                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form class="exam-form" method="POST" action="">
                    <div class="form-row">
                        <div class="form-group"><label>Full Name</label><input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required></div>
                        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Roll Number</label><input type="text" value="<?= htmlspecialchars($student['roll_number']) ?>" disabled></div>
                        <div class="form-group"><label>Department</label>
                            <select name="department">
                                <option value="BCA" <?= $student['department'] === 'BCA' ? 'selected' : '' ?>>BCA</option>
                                <option value="BBA" <?= $student['department'] === 'BBA' ? 'selected' : '' ?>>BBA</option>
                                <option value="BSc" <?= $student['department'] === 'BSc' ? 'selected' : '' ?>>BSc</option>
                                <option value="BCom" <?= $student['department'] === 'BCom' ? 'selected' : '' ?>>BCom</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>New Password <small>(leave blank to keep current)</small></label><input type="password" name="new_password" placeholder="New password"></div>
                        <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" placeholder="Confirm password"></div>
                    </div>
                    <button type="submit" class="btn btn-primary-action"><i class="fas fa-save"></i> Save Profile</button>
                </form>
            </div>
        </div>
    </main>

    <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>

    <script>
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('active'); });
        overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });
    }
    </script>
</body>
</html>
