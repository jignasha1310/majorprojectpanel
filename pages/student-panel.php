<?php
require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// Get stats
$upcoming = $conn->query("SELECT COUNT(*) as c FROM exams WHERE status IN ('scheduled','active')")->fetch_assoc()['c'];
$active = $conn->query("SELECT COUNT(*) as c FROM exams WHERE status = 'active'")->fetch_assoc()['c'];

$completed_q = $conn->prepare("SELECT COUNT(*) as c FROM student_exams WHERE student_id = ?");
$completed_q->bind_param("i", $student_id);
$completed_q->execute();
$completed = $completed_q->get_result()->fetch_assoc()['c'];

$avg_q = $conn->prepare("SELECT COALESCE(AVG(percentage), 0) as avg_score FROM student_exams WHERE student_id = ?");
$avg_q->bind_param("i", $student_id);
$avg_q->execute();
$avg_score = round($avg_q->get_result()->fetch_assoc()['avg_score']);

// Get upcoming exams
$exams_q = $conn->query("SELECT * FROM exams WHERE status IN ('scheduled','active') ORDER BY exam_date ASC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Panel - ExamPro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="student-panel.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="../index.html" class="logo">
                <i class="fas fa-graduation-cap"></i>
                <span>Exam<span class="logo-accent">Pro</span></span>
            </a>
            <span class="panel-label">Student Panel</span>
        </div>
        <nav class="sidebar-nav">
            <a href="student-panel.php" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="student-profile.php" class="nav-item">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
            <a href="scheduled-exams.php" class="nav-item">
                <i class="fas fa-calendar-check"></i>
                <span>Scheduled Exams</span>
            </a>
            <a href="take-exam.php" class="nav-item">
                <i class="fas fa-pen-fancy"></i>
                <span>Take Exam</span>
            </a>
            <a href="my-results.php" class="nav-item">
                <i class="fas fa-trophy"></i>
                <span>My Results</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="back-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Main Content -->
    <main class="main-content">
        <header class="topbar">
            <h1>Student Dashboard</h1>
            <div class="topbar-actions">
                <span class="user-greeting">Welcome, <?= htmlspecialchars($student_name) ?></span>
                <a href="logout.php" class="btn btn-login">Logout</a>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="welcome-banner">
                <div class="welcome-avatar"><i class="fas fa-user-graduate"></i></div>
                <div class="welcome-info">
                    <h2>Welcome back, <?= htmlspecialchars($student_name) ?>!</h2>
                    <p>Ready to ace your exams? Check your dashboard for the latest updates.</p>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $upcoming ?></span>
                        <span class="stat-label">Upcoming Exams</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-play-circle"></i></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $active ?></span>
                        <span class="stat-label">Active Now</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $completed ?></span>
                        <span class="stat-label">Exams Completed</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $avg_score ?>%</span>
                        <span class="stat-label">Avg. Score</span>
                    </div>
                </div>
            </div>

            <div class="upcoming-section">
                <h2>Upcoming Exams</h2>
                <div class="upcoming-list">
                    <?php if ($exams_q->num_rows > 0): ?>
                        <?php while ($exam = $exams_q->fetch_assoc()): ?>
                            <div class="upcoming-item">
                                <div class="exam-date">
                                    <span class="day"><?= date('d', strtotime($exam['exam_date'])) ?></span>
                                    <span class="month"><?= date('M', strtotime($exam['exam_date'])) ?></span>
                                </div>
                                <div class="exam-details">
                                    <h4><?= htmlspecialchars($exam['title']) ?></h4>
                                    <p><?= htmlspecialchars($exam['semester']) ?> • <?= $exam['total_questions'] ?> Questions • <?= $exam['duration_minutes'] ?> mins</p>
                                </div>
                                <span class="exam-status <?= $exam['status'] === 'active' ? 'exam-status-active' : '' ?>">
                                    <?= ucfirst($exam['status']) ?>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="upcoming-item">
                            <p style="color: var(--text-muted); padding: 1rem;">No upcoming exams at this time.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle menu">
        <i class="fas fa-bars"></i>
    </button>

    <script>
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }
    </script>
</body>
</html>
