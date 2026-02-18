<?php
require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['student_id'];

// Stats
$total_exams_q = $conn->prepare("SELECT COUNT(*) as c FROM student_exams WHERE student_id = ?");
$total_exams_q->bind_param("i", $student_id);
$total_exams_q->execute();
$total_exams = $total_exams_q->get_result()->fetch_assoc()['c'];

$avg_q = $conn->prepare("SELECT COALESCE(AVG(percentage), 0) as avg_score FROM student_exams WHERE student_id = ?");
$avg_q->bind_param("i", $student_id);
$avg_q->execute();
$avg_score = round($avg_q->get_result()->fetch_assoc()['avg_score']);

// All results
$results_q = $conn->prepare("
    SELECT se.*, e.title, e.subject 
    FROM student_exams se 
    JOIN exams e ON se.exam_id = e.id 
    WHERE se.student_id = ? 
    ORDER BY se.submitted_at DESC
");
$results_q->bind_param("i", $student_id);
$results_q->execute();
$results = $results_q->get_result();

// Check if recently submitted
$just_submitted = isset($_GET['submitted']) && $_GET['submitted'] == '1';
$submitted_exam_id = intval($_GET['exam_id'] ?? 0);
$submitted_result = null;

if ($just_submitted && $submitted_exam_id > 0) {
    $sub_q = $conn->prepare("SELECT se.*, e.title FROM student_exams se JOIN exams e ON se.exam_id = e.id WHERE se.student_id = ? AND se.exam_id = ? ORDER BY se.id DESC LIMIT 1");
    $sub_q->bind_param("ii", $student_id, $submitted_exam_id);
    $sub_q->execute();
    $submitted_result = $sub_q->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results - ExamPro Student Panel</title>
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
            <a href="scheduled-exams.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Scheduled Exams</span></a>
            <a href="take-exam.php" class="nav-item"><i class="fas fa-pen-fancy"></i><span>Take Exam</span></a>
            <a href="my-results.php" class="nav-item active"><i class="fas fa-trophy"></i><span>My Results</span></a>
        </nav>
        <div class="sidebar-footer"><a href="logout.php" class="back-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
        <header class="topbar">
            <h1>My Results</h1>
            <div class="topbar-actions">
                <span class="user-greeting">Welcome, <?= htmlspecialchars($_SESSION['student_name']) ?></span>
                <a href="logout.php" class="btn btn-login">Logout</a>
            </div>
        </header>
        <div class="dashboard-content">
            <?php if ($submitted_result): ?>
                <div class="score-banner">
                    <h2><i class="fas fa-check-circle"></i> Exam Submitted Successfully!</h2>
                    <p class="score-label"><?= htmlspecialchars($submitted_result['title']) ?></p>
                    <div class="big-score"><?= $submitted_result['score'] ?>/<?= $submitted_result['total'] ?></div>
                    <p class="score-details">You scored <?= $submitted_result['percentage'] ?>%</p>
                </div>
            <?php endif; ?>

            <div class="stats-grid" style="max-width: 600px;">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $total_exams ?></span>
                        <span class="stat-label">Exams Taken</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $avg_score ?>%</span>
                        <span class="stat-label">Avg. Score</span>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <h2 class="section-title-inline">Your Exam Results</h2>
                <div class="recent-results-list">
                    <?php if ($results->num_rows > 0): ?>
                        <?php while ($r = $results->fetch_assoc()): ?>
                            <div class="recent-result-item">
                                <div class="result-exam-info">
                                    <h4><?= htmlspecialchars($r['title']) ?></h4>
                                    <p>Completed <?= date('M d, Y', strtotime($r['submitted_at'])) ?></p>
                                </div>
                                <div class="result-summary">
                                    <span class="result-pass"><?= $r['score'] ?>/<?= $r['total'] ?></span>
                                    <span class="result-avg"><?= $r['percentage'] ?>%</span>
                                    <a href="review-answers.php?se_id=<?= $r['id'] ?>" class="btn-small">Review Answers</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: var(--text-muted); padding: 1rem;">You haven't taken any exams yet. <a href="take-exam.php" style="color: #4F46E5;">Take an exam</a></p>
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
