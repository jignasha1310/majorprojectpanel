<?php
require_once __DIR__ . '/includes/auth.php';

$students = (int) ($conn->query('SELECT COUNT(*) AS total FROM students')->fetch_assoc()['total'] ?? 0);
$teachers = (int) ($conn->query('SELECT COUNT(*) AS total FROM teachers')->fetch_assoc()['total'] ?? 0);
$exams = (int) ($conn->query('SELECT COUNT(*) AS total FROM exams')->fetch_assoc()['total'] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - ExamPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">ExamPro Admin</a>
        <div class="ms-auto d-flex gap-2">
            <span class="navbar-text">Hi, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
            <a class="btn btn-outline-danger btn-sm" href="logout.php">Logout</a>
        </div>
    </div>
</nav>
<main class="container py-4">
    <h1 class="h3 mb-4">Dashboard</h1>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-0"><div class="card-body">
                <div class="text-secondary">Total Students</div>
                <div class="display-6 fw-bold"><?= $students ?></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0"><div class="card-body">
                <div class="text-secondary">Total Teachers</div>
                <div class="display-6 fw-bold"><?= $teachers ?></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0"><div class="card-body">
                <div class="text-secondary">Total Exams</div>
                <div class="display-6 fw-bold"><?= $exams ?></div>
            </div></div>
        </div>
    </div>
</main>
</body>
</html>
