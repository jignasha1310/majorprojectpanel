<?php
function teacherRenderHeader(string $title, string $activeNav): void
{
    $teacherName = htmlspecialchars($_SESSION['teacher_name'] ?? 'Teacher');
    $isDashboard = $activeNav === 'dashboard';
    $isExams = $activeNav === 'exams';
    $isProfile = $activeNav === 'profile';
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($title) ?> - ExamPro Teacher</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <link href="assets/teacher.css" rel="stylesheet">
    </head>
    <body>
    <div class="d-lg-flex teacher-layout">
        <aside class="teacher-sidebar d-flex flex-column justify-content-between">
            <div>
                <div class="sidebar-brand">
                    <div class="d-flex align-items-center gap-2 fw-bold fs-2">
                        <i class="bi bi-mortarboard-fill"></i>
                        <span>ExamPro</span>
                    </div>
                    <div class="small mt-2">Teacher Panel</div>
                </div>
                <nav class="nav flex-column py-3">
                    <a class="sidebar-link nav-link <?= $isDashboard ? 'active' : '' ?>" href="dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a class="sidebar-link nav-link <?= $isExams ? 'active' : '' ?>" href="exams.php">
                        <i class="bi bi-calendar2-check me-2"></i> Exam Scheduler
                    </a>
                    <a class="sidebar-link nav-link <?= $isProfile ? 'active' : '' ?>" href="profile.php">
                        <i class="bi bi-person-badge me-2"></i> My Profile
                    </a>
                </nav>
            </div>
            <div class="p-3 border-top border-light border-opacity-25">
                <a href="../../index.html" class="sidebar-link nav-link">
                    <i class="bi bi-arrow-left me-2"></i> Back to Home
                </a>
            </div>
        </aside>

        <section class="teacher-content">
            <header class="teacher-topbar p-3 p-md-4 d-flex justify-content-between align-items-center">
                <h1 class="h2 fw-bold mb-0"><?= htmlspecialchars($title) ?></h1>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-secondary">Welcome, <?= $teacherName ?></span>
                    <a href="logout.php" class="btn btn-indigo">Logout</a>
                </div>
            </header>

            <main class="p-3 p-md-4">
    <?php
}

function teacherRenderFooter(): void
{
    ?>
            </main>
        </section>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
