<?php
require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['student_id'];

// Get all scheduled and active exams
$exams = $conn->query("SELECT * FROM exams WHERE status IN ('scheduled','active') ORDER BY exam_date ASC");

// Get exams already taken by this student
$taken_q = $conn->prepare("SELECT exam_id FROM student_exams WHERE student_id = ?");
$taken_q->bind_param("i", $student_id);
$taken_q->execute();
$taken_result = $taken_q->get_result();
$taken_exams = [];
while ($row = $taken_result->fetch_assoc()) {
    $taken_exams[] = $row['exam_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Exams - ExamPro Student Panel</title>
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
            <a href="student-profile.php" class="nav-item"><i class="fas fa-user"></i><span>My Profile</span></a>
            <a href="scheduled-exams.php" class="nav-item active"><i class="fas fa-calendar-check"></i><span>Scheduled Exams</span></a>
            <a href="take-exam.php" class="nav-item"><i class="fas fa-pen-fancy"></i><span>Take Exam</span></a>
            <a href="my-results.php" class="nav-item"><i class="fas fa-trophy"></i><span>My Results</span></a>
        </nav>
        <div class="sidebar-footer"><a href="logout.php" class="back-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
        <header class="topbar">
            <h1>Scheduled & Active Exams</h1>
            <div class="topbar-actions">
                <span class="user-greeting">Welcome, <?= htmlspecialchars($_SESSION['student_name']) ?></span>
                <a href="logout.php" class="btn btn-login">Logout</a>
            </div>
        </header>
        <div class="dashboard-content">
            <div class="content-card">
                <h2 class="section-title-inline">Your Scheduled Exams</h2>
                <div class="upcoming-list">
                    <?php if ($exams->num_rows > 0): ?>
                        <?php while ($exam = $exams->fetch_assoc()): ?>
                            <?php $already_taken = in_array($exam['id'], $taken_exams); ?>
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
                                    <?= $already_taken ? 'Completed' : ucfirst($exam['status']) ?>
                                </span>
                                <?php if ($exam['status'] === 'active' && !$already_taken): ?>
                                    <a href="take-exam.php?exam_id=<?= $exam['id'] ?>" class="btn-small">Start Exam</a>
                                <?php elseif ($already_taken): ?>
                                    <a href="my-results.php" class="btn-small btn-outline-purple">View Result</a>
                                <?php else: ?>
                                    <span class="btn-small btn-outline-purple" style="opacity:0.5; cursor:default;">Upcoming</span>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="upcoming-item">
                            <p style="color: var(--text-muted); padding: 0.5rem;">No exams scheduled at this time.</p>
                        </div>
                    <?php endif; ?>
                </div>
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
