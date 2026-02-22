<?php

require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

$studentId = (int) $_SESSION['student_id'];
$success = (string) ($_SESSION['profile_success'] ?? '');
$error = (string) ($_SESSION['profile_error'] ?? '');
unset($_SESSION['profile_success'], $_SESSION['profile_error']);

if (empty($_SESSION['csrf_profile_token'])) {
    $_SESSION['csrf_profile_token'] = bin2hex(random_bytes(32));
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$stmt = $conn->prepare('SELECT id, name, first_name, last_name, email, phone, roll_number, department, profile_image, updated_at FROM students WHERE id = ? LIMIT 1');
if (!$stmt) {
    header('Location: logout.php');
    exit;
}

$stmt->bind_param('i', $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    header('Location: logout.php');
    exit;
}

$firstName = trim((string) ($student['first_name'] ?? ''));
$lastName = trim((string) ($student['last_name'] ?? ''));
if ($firstName === '' || $lastName === '') {
    $nameParts = preg_split('/\s+/', trim((string) ($student['name'] ?? '')), 2);
    $firstName = $firstName !== '' ? $firstName : trim((string) ($nameParts[0] ?? ''));
    $lastName = $lastName !== '' ? $lastName : trim((string) ($nameParts[1] ?? ''));
}

$displayName = trim($firstName . ' ' . $lastName);
if ($displayName === '') {
    $displayName = trim((string) ($student['name'] ?? 'Student'));
}

$initial = strtoupper(substr($displayName, 0, 1));
$roleLabel = 'Student';
$lastUpdatedRaw = (string) ($student['updated_at'] ?? '');
$lastUpdated = 'Not available';
if ($lastUpdatedRaw !== '' && $lastUpdatedRaw !== '0000-00-00 00:00:00') {
    $ts = strtotime($lastUpdatedRaw);
    if ($ts !== false) {
        $lastUpdated = date('d M Y, h:i A', $ts);
    }
}

$defaultImageUrl = '../uploads/profile/default.png';
$profileImageUrl = $defaultImageUrl;
$storedImagePath = trim((string) ($student['profile_image'] ?? ''));
if ($storedImagePath !== '' && preg_match('/^uploads\/profile\/[A-Za-z0-9._-]+$/', $storedImagePath)) {
    $absPath = __DIR__ . '/../' . $storedImagePath;
    if (is_file($absPath)) {
        $profileImageUrl = '../' . $storedImagePath;
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
    <style>
        .profile-shell {
            max-width: 1080px;
            margin: 0 auto;
        }

        .profile-modern-card {
            background: linear-gradient(165deg, #ffffff 0%, #f8faff 100%);
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 20px 35px rgba(15, 23, 42, 0.07);
            overflow: hidden;
        }

        .profile-header-band {
            padding: 1.2rem 1.4rem;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(90deg, #eef2ff 0%, #f0f9ff 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.6rem;
        }

        .badge-role {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: #4f46e5;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .updated-chip {
            color: #475569;
            background: #ffffff;
            border: 1px solid #dbeafe;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 500;
        }

        .profile-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 1.25rem;
            padding: 1.25rem;
        }

        .profile-summary {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.2rem;
            background: #f8fafc;
            text-align: center;
        }

        .profile-avatar {
            width: 134px;
            height: 134px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #dbeafe;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.16);
            margin-bottom: 0.7rem;
        }

        .profile-avatar-fallback {
            width: 134px;
            height: 134px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.7rem;
            background: linear-gradient(135deg, #c7d2fe, #e0e7ff);
            color: #312e81;
            font-size: 2rem;
            font-weight: 700;
            border: 4px solid #dbeafe;
        }

        .meta-line {
            font-size: 0.82rem;
            color: #64748b;
            margin-top: 0.35rem;
        }

        .readonly-input {
            background: #f1f5f9 !important;
            color: #64748b;
            cursor: not-allowed;
        }

        .field-hint {
            margin-top: 0.35rem;
            font-size: 0.75rem;
            color: #94a3b8;
        }

        @media (max-width: 980px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }

            .profile-summary {
                text-align: left;
            }

            .profile-avatar,
            .profile-avatar-fallback {
                margin-left: 0;
            }
        }
    </style>
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
                <span class="user-greeting">Welcome, <?= esc($displayName) ?></span>
                <a href="logout.php" class="btn btn-login">Logout</a>
            </div>
        </header>

        <div class="dashboard-content">
            <section class="profile-shell">
                <?php if ($success !== ''): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= esc($success) ?></div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= esc($error) ?></div>
                <?php endif; ?>

                <article class="profile-modern-card">
                    <div class="profile-header-band">
                        <h2 class="section-title-inline" style="margin-bottom:0;">Profile Management</h2>
                        <div style="display:flex; gap:0.55rem; flex-wrap:wrap; align-items:center;">
                            <span class="badge-role"><i class="fas fa-user-shield"></i> <?= esc($roleLabel) ?></span>
                            <span class="updated-chip"><i class="fas fa-clock"></i> Last updated: <?= esc($lastUpdated) ?></span>
                        </div>
                    </div>

                    <div class="profile-layout">
                        <aside class="profile-summary">
                            <?php if ($profileImageUrl !== $defaultImageUrl): ?>
                                <img class="profile-avatar" src="<?= esc($profileImageUrl) ?>" alt="Profile image">
                            <?php elseif (is_file(__DIR__ . '/../uploads/profile/default.png')): ?>
                                <img class="profile-avatar" src="<?= esc($defaultImageUrl) ?>" alt="Profile image">
                            <?php else: ?>
                                <div class="profile-avatar-fallback"><?= esc($initial) ?></div>
                            <?php endif; ?>
                            <h3><?= esc($displayName) ?></h3>
                            <p class="meta-line"><?= esc((string) $student['email']) ?></p>
                            <p class="field-hint">Accepted formats: JPG/JPEG/PNG | Max size: 2MB</p>
                        </aside>

                        <form class="exam-form" method="POST" action="update_profile.php" enctype="multipart/form-data" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= esc((string) $_SESSION['csrf_profile_token']) ?>">

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name</label>
                                    <input id="first_name" type="text" name="first_name" maxlength="80" value="<?= esc($firstName) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input id="last_name" type="text" name="last_name" maxlength="80" value="<?= esc($lastName) ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input id="email" type="email" class="readonly-input" value="<?= esc((string) $student['email']) ?>" readonly>
                                    <p class="field-hint">Email cannot be changed from this screen.</p>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input id="phone" type="tel" name="phone" maxlength="20" value="<?= esc((string) ($student['phone'] ?? '')) ?>" placeholder="e.g. +91 9876543210">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="roll_number">Roll Number</label>
                                    <input id="roll_number" type="text" class="readonly-input" value="<?= esc((string) ($student['roll_number'] ?? '')) ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="department">Department</label>
                                    <input id="department" type="text" class="readonly-input" value="<?= esc((string) ($student['department'] ?? '')) ?>" readonly>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="profile_image">Profile Image</label>
                                    <input id="profile_image" type="file" name="profile_image" accept="image/jpeg,image/png,.jpg,.jpeg,.png">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-action"><i class="fas fa-save"></i> Update Profile</button>
                        </form>
                    </div>
                </article>
            </section>
        </div>
    </main>

    <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>

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
