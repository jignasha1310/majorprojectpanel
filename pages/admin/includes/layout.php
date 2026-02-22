<?php
function adminRenderHeader(string $title, string $activeNav): void
{
    $adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator');
    $isDashboard = $activeNav === 'dashboard';
    $isStudents = $activeNav === 'students';
    $isTeachers = $activeNav === 'teachers';
    $isExams = $activeNav === 'exams';
    $isResults = $activeNav === 'results';
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($title) ?> - ExamPro Admin</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="assets/admin.css" rel="stylesheet">
    </head>
    <body>
    <div class="d-lg-flex admin-layout">
        <aside class="admin-sidebar d-flex flex-column justify-content-between">
            <div>
                <div class="sidebar-brand">
                    <div class="d-flex align-items-center gap-2 fw-bold fs-2">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span>ExamPro</span>
                    </div>
                    <div class="small mt-2">Admin Panel</div>
                </div>
                <nav class="nav flex-column py-3">
                    <a class="sidebar-link nav-link <?= $isDashboard ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fa-solid fa-gauge me-2"></i> Dashboard
                    </a>
                    <a class="sidebar-link nav-link <?= $isStudents ? 'active' : '' ?>" href="students.php">
                        <i class="fa-solid fa-user-graduate me-2"></i> Students
                    </a>
                    <a class="sidebar-link nav-link <?= $isTeachers ? 'active' : '' ?>" href="teachers.php">
                        <i class="fa-solid fa-chalkboard-user me-2"></i> Teachers
                    </a>
                    <a class="sidebar-link nav-link <?= $isExams ? 'active' : '' ?>" href="exams.php">
                        <i class="fa-solid fa-file-pen me-2"></i> Exams
                    </a>
                    <a class="sidebar-link nav-link <?= $isResults ? 'active' : '' ?>" href="results.php">
                        <i class="fa-solid fa-square-poll-vertical me-2"></i> Results
                    </a>
                    <a class="sidebar-link nav-link" href="#">
                        <i class="fa-solid fa-building-columns me-2"></i> Classes
                    </a>
                    <a class="sidebar-link nav-link" href="#">
                        <i class="fa-solid fa-chart-line me-2"></i> Reports
                    </a>
                    <a class="sidebar-link nav-link" href="logout.php">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                    </a>
                </nav>
            </div>
            <div class="p-3 border-top border-light border-opacity-25">
                <a href="../../index.html" class="sidebar-link nav-link">
                    <i class="fa-solid fa-arrow-left me-2"></i> Back to Home
                </a>
            </div>
        </aside>

        <section class="admin-content">
            <header class="admin-topbar p-3 p-md-4 d-flex justify-content-between align-items-center">
                <h1 class="h2 fw-bold mb-0"><?= htmlspecialchars($title) ?></h1>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-secondary">Welcome, <?= $adminName ?></span>
                    <a href="logout.php" class="btn btn-indigo">Logout</a>
                </div>
            </header>

            <main class="p-3 p-md-4">
    <?php
}

function adminRenderFooter(): void
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
