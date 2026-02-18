<?php
require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$exam = null;
$questions = [];

if ($exam_id > 0) {
    // Check if already taken
    $check = $conn->prepare("SELECT id FROM student_exams WHERE student_id = ? AND exam_id = ?");
    $check->bind_param("ii", $student_id, $exam_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        header('Location: my-results.php');
        exit;
    }

    // Get exam details
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $exam = $stmt->get_result()->fetch_assoc();

    if ($exam) {
        // Get questions (randomized order)
        $q_stmt = $conn->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY RAND()");
        $q_stmt->bind_param("i", $exam_id);
        $q_stmt->execute();
        $questions_result = $q_stmt->get_result();
        while ($q = $questions_result->fetch_assoc()) {
            $questions[] = $q;
        }
    }
}

// If no specific exam, show list of active exams
if (!$exam) {
    $active_exams = $conn->query("SELECT * FROM exams WHERE status = 'active' ORDER BY exam_date ASC");

    // Get already taken exams
    $taken_q = $conn->prepare("SELECT exam_id FROM student_exams WHERE student_id = ?");
    $taken_q->bind_param("i", $student_id);
    $taken_q->execute();
    $taken_result = $taken_q->get_result();
    $taken_exams = [];
    while ($row = $taken_result->fetch_assoc()) {
        $taken_exams[] = $row['exam_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $exam ? htmlspecialchars($exam['title']) : 'Take Exam' ?> - ExamPro</title>
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
            <a href="take-exam.php" class="nav-item active"><i class="fas fa-pen-fancy"></i><span>Take Exam</span></a>
            <a href="my-results.php" class="nav-item"><i class="fas fa-trophy"></i><span>My Results</span></a>
        </nav>
        <div class="sidebar-footer"><a href="logout.php" class="back-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
        <header class="topbar">
            <h1><?= $exam ? htmlspecialchars($exam['title']) : 'Take Exam' ?></h1>
            <div class="topbar-actions">
                <span class="user-greeting">Welcome, <?= htmlspecialchars($_SESSION['student_name']) ?></span>
                <a href="logout.php" class="btn btn-login">Logout</a>
            </div>
        </header>
        <div class="dashboard-content">
            <?php if ($exam && count($questions) > 0): ?>
                <!-- EXAM INTERFACE -->
                <div class="exam-container">
                    <div class="exam-header-bar">
                        <h3><i class="fas fa-file-alt"></i> <?= htmlspecialchars($exam['title']) ?> — <?= $exam['total_questions'] ?> Questions</h3>
                        <div class="timer-badge" id="timer">
                            <i class="fas fa-clock"></i>
                            <span id="timer-display"><?= sprintf('%02d:00', $exam['duration_minutes']) ?></span>
                        </div>
                    </div>

                    <form method="POST" action="submit-exam.php" id="exam-form">
                        <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
                        <?php foreach ($questions as $i => $q): ?>
                            <div class="question-card">
                                <div class="q-header">
                                    <span class="q-num">Q<?= $i + 1 ?></span>
                                    <span class="q-text"><?= htmlspecialchars($q['question_text']) ?></span>
                                </div>
                                <div class="options-list">
                                    <label class="option-label">
                                        <input type="radio" name="answer[<?= $q['id'] ?>]" value="A">
                                        <span>A. <?= htmlspecialchars($q['option_a']) ?></span>
                                    </label>
                                    <label class="option-label">
                                        <input type="radio" name="answer[<?= $q['id'] ?>]" value="B">
                                        <span>B. <?= htmlspecialchars($q['option_b']) ?></span>
                                    </label>
                                    <label class="option-label">
                                        <input type="radio" name="answer[<?= $q['id'] ?>]" value="C">
                                        <span>C. <?= htmlspecialchars($q['option_c']) ?></span>
                                    </label>
                                    <label class="option-label">
                                        <input type="radio" name="answer[<?= $q['id'] ?>]" value="D">
                                        <span>D. <?= htmlspecialchars($q['option_d']) ?></span>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="submit-bar">
                            <span class="q-count"><i class="fas fa-info-circle"></i> Answer all questions before submitting</span>
                            <button type="submit" class="btn-submit-exam"><i class="fas fa-paper-plane"></i> Submit Exam</button>
                        </div>
                    </form>
                </div>

                <script>
                // Timer
                let totalSeconds = <?= $exam['duration_minutes'] ?> * 60;
                const timerDisplay = document.getElementById('timer-display');
                const timerBadge = document.getElementById('timer');
                const examForm = document.getElementById('exam-form');

                const countdown = setInterval(() => {
                    totalSeconds--;
                    const mins = Math.floor(totalSeconds / 60);
                    const secs = totalSeconds % 60;
                    timerDisplay.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;

                    if (totalSeconds <= 60) {
                        timerBadge.className = 'timer-badge danger';
                    } else if (totalSeconds <= 120) {
                        timerBadge.className = 'timer-badge warning';
                    }

                    if (totalSeconds <= 0) {
                        clearInterval(countdown);
                        alert('Time is up! Your exam will be auto-submitted.');
                        examForm.submit();
                    }
                }, 1000);

                examForm.addEventListener('submit', (e) => {
                    clearInterval(countdown);
                });
                </script>

            <?php else: ?>
                <!-- EXAM LIST -->
                <div class="content-card">
                    <h2 class="section-title-inline">Active Exams - Ready to Take</h2>
                    <div class="upcoming-list">
                        <?php if (isset($active_exams) && $active_exams->num_rows > 0): ?>
                            <?php while ($ae = $active_exams->fetch_assoc()): ?>
                                <?php $already_taken = in_array($ae['id'], $taken_exams); ?>
                                <div class="upcoming-item">
                                    <div class="exam-date">
                                        <span class="day"><?= date('d', strtotime($ae['exam_date'])) ?></span>
                                        <span class="month"><?= date('M', strtotime($ae['exam_date'])) ?></span>
                                    </div>
                                    <div class="exam-details">
                                        <h4><?= htmlspecialchars($ae['title']) ?></h4>
                                        <p><?= $ae['total_questions'] ?> Questions • <?= $ae['duration_minutes'] ?> mins • MCQ</p>
                                    </div>
                                    <?php if (!$already_taken): ?>
                                        <span class="exam-status exam-status-active">Active</span>
                                        <a href="take-exam.php?exam_id=<?= $ae['id'] ?>" class="btn btn-primary-action"><i class="fas fa-play"></i> Start Exam</a>
                                    <?php else: ?>
                                        <span class="exam-status">Completed</span>
                                        <a href="my-results.php" class="btn-small btn-outline-purple">View Result</a>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="upcoming-item">
                                <p style="color: var(--text-muted); padding: 0.5rem;">No active exams available right now. Check back later!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p style="margin-top: 1rem; color: var(--text-muted);">The timer starts when you begin. Make sure you have a stable connection.</p>
                </div>
            <?php endif; ?>
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
