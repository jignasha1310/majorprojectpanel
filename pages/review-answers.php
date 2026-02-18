<?php
require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$se_id = intval($_GET['se_id'] ?? 0);

if ($se_id <= 0) {
    header('Location: my-results.php');
    exit;
}

// Get student exam result
$se_q = $conn->prepare("SELECT se.*, e.title, e.subject FROM student_exams se JOIN exams e ON se.exam_id = e.id WHERE se.id = ? AND se.student_id = ?");
$se_q->bind_param("ii", $se_id, $student_id);
$se_q->execute();
$exam_result = $se_q->get_result()->fetch_assoc();

if (!$exam_result) {
    header('Location: my-results.php');
    exit;
}

// Get questions with student's answers
$qa_q = $conn->prepare("
    SELECT q.*, sa.selected_option 
    FROM questions q 
    LEFT JOIN student_answers sa ON sa.question_id = q.id AND sa.student_exam_id = ?
    WHERE q.exam_id = ?
    ORDER BY q.id ASC
");
$qa_q->bind_param("ii", $se_id, $exam_result['exam_id']);
$qa_q->execute();
$questions = $qa_q->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Answers - ExamPro</title>
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
            <h1>Review Answers</h1>
            <div class="topbar-actions">
                <span class="user-greeting">Welcome, <?= htmlspecialchars($_SESSION['student_name']) ?></span>
                <a href="logout.php" class="btn btn-login">Logout</a>
            </div>
        </header>
        <div class="dashboard-content">
            <a href="my-results.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Results</a>

            <div class="review-header">
                <h3><?= htmlspecialchars($exam_result['title']) ?></h3>
                <div class="review-score"><?= $exam_result['score'] ?>/<?= $exam_result['total'] ?> (<?= $exam_result['percentage'] ?>%)</div>
            </div>

            <?php $i = 0; while ($q = $questions->fetch_assoc()): $i++; ?>
                <?php
                    $selected = strtoupper($q['selected_option'] ?? '');
                    $correct = strtoupper($q['correct_option']);
                    $options = ['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']];
                ?>
                <div class="question-review">
                    <div class="q-header">
                        <span class="q-num">Q<?= $i ?></span>
                        <span class="q-text"><?= htmlspecialchars($q['question_text']) ?></span>
                    </div>
                    <?php foreach ($options as $key => $text): ?>
                        <?php
                            $class = 'neutral';
                            $icon = '';
                            if ($key === $correct) {
                                $class = 'correct';
                                $icon = '<i class="fas fa-check-circle"></i>';
                            } elseif ($key === $selected && $selected !== $correct) {
                                $class = 'wrong';
                                $icon = '<i class="fas fa-times-circle"></i>';
                            }
                        ?>
                        <div class="option-review <?= $class ?>">
                            <?= $key ?>. <?= htmlspecialchars($text) ?> <?= $icon ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endwhile; ?>
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
