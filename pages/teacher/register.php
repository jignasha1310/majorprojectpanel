<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/pdo.php';
require_once __DIR__ . '/../../config/teacher_module.php';

ensureTeacherModuleSchema($pdo);

if (isset($_SESSION['teacher_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$success = '';
$currentStep = (int) ($_SESSION['teacher_reg_step'] ?? 1);
if ($currentStep < 1 || $currentStep > 3) {
    $currentStep = 1;
}

$teacherRegId = (int) ($_SESSION['teacher_reg_teacher_id'] ?? 0);

$subjects = [
    'Mathematics',
    'Physics',
    'Chemistry',
    'English',
    'Computer Science',
    'Biology',
    'Economics',
    'Accountancy',
];

$form = [
    'email' => '',
    'phone' => '',
    'first_name' => '',
    'last_name' => '',
    'dob' => '',
    'gender' => '',
    'qualification' => '',
    'bed_status' => '',
    'university' => '',
    'passing_year' => '',
    'experience_years' => '',
    'subjects' => [],
];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirectRegister(int $step): void
{
    header('Location: register.php?step=' . $step);
    exit;
}

function handleUpload(array $file, array $allowedMimes, int $maxBytes, string $targetDir, string $filePrefix): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, 'File upload failed.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        return [false, 'Invalid file size.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    if (!isset($allowedMimes[$mime])) {
        return [false, 'Invalid file type.'];
    }

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        return [false, 'Unable to create upload directory.'];
    }

    $ext = $allowedMimes[$mime];
    $fileName = $filePrefix . '_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
    $targetPath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmp, $targetPath)) {
        return [false, 'Unable to save uploaded file.'];
    }

    return [true, $fileName];
}

if (isset($_GET['step'])) {
    $requested = (int) $_GET['step'];
    if ($requested >= 1 && $requested <= 3) {
        $currentStep = $requested;
    }
}

if ($teacherRegId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM teachers WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $teacherRegId]);
    $row = $stmt->fetch();
    if ($row) {
        $form['email'] = (string) ($row['email'] ?? '');
        $form['phone'] = (string) ($row['phone'] ?? '');
        $form['first_name'] = (string) ($row['first_name'] ?? '');
        $form['last_name'] = (string) ($row['last_name'] ?? '');
        $form['dob'] = (string) ($row['dob'] ?? '');
        $form['gender'] = (string) ($row['gender'] ?? '');
        $form['qualification'] = (string) ($row['qualification'] ?? '');
        $form['bed_status'] = (string) ($row['bed_status'] ?? '');
        $form['university'] = (string) ($row['university'] ?? '');
        $form['passing_year'] = (string) ($row['passing_year'] ?? '');
        $form['experience_years'] = (string) ($row['experience_years'] ?? '');
        $savedSubjects = trim((string) ($row['subjects'] ?? ''));
        $form['subjects'] = $savedSubjects !== '' ? array_values(array_filter(array_map('trim', explode(',', $savedSubjects)))) : [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $csrf = (string) ($_POST['csrf_token'] ?? '');

    if (!teacherModuleVerifyCsrf($csrf)) {
        $errors[] = 'Invalid CSRF token. Please refresh and try again.';
    } elseif ($action === 'step1') {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $dob = trim((string) ($_POST['dob'] ?? ''));
        $gender = trim((string) ($_POST['gender'] ?? ''));

        $form['email'] = $email;
        $form['phone'] = $phone;
        $form['first_name'] = $firstName;
        $form['last_name'] = $lastName;
        $form['dob'] = $dob;
        $form['gender'] = $gender;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        }
        if (!preg_match('/^[0-9+\-()\s]{7,20}$/', $phone)) {
            $errors[] = 'Valid phone number is required.';
        }
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must be at least 8 characters and include upper, lower, and number.';
        }
        if (!hash_equals($password, $confirmPassword)) {
            $errors[] = 'Password and confirm password do not match.';
        }
        if ($firstName === '' || $lastName === '') {
            $errors[] = 'First and last name are required.';
        }
        if ($dob === '') {
            $errors[] = 'Date of birth is required.';
        }
        if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
            $errors[] = 'Please select a valid gender.';
        }

        if (!$errors) {
            $dupStmt = $pdo->prepare('SELECT id FROM teachers WHERE email = :email LIMIT 1');
            $dupStmt->execute([':email' => $email]);
            if ($dupStmt->fetch()) {
                $errors[] = 'This email is already registered.';
            }
        }

        if (!$errors) {
            $registrationId = generateTeacherRegistrationId($pdo);
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $fullName = trim($firstName . ' ' . $lastName);

            $insert = $pdo->prepare(
                'INSERT INTO teachers (registration_id, name, email, phone, password, first_name, last_name, dob, gender, status, registration_step)
                 VALUES (:registration_id, :name, :email, :phone, :password, :first_name, :last_name, :dob, :gender, :status, :registration_step)'
            );
            $insert->execute([
                ':registration_id' => $registrationId,
                ':name' => $fullName,
                ':email' => $email,
                ':phone' => $phone,
                ':password' => $passwordHash,
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':dob' => $dob,
                ':gender' => $gender,
                ':status' => 'pending',
                ':registration_step' => 1,
            ]);

            $_SESSION['teacher_reg_teacher_id'] = (int) $pdo->lastInsertId();
            $_SESSION['teacher_reg_step'] = 2;
            redirectRegister(2);
        }
    } elseif ($action === 'step2') {
        $teacherRegId = (int) ($_SESSION['teacher_reg_teacher_id'] ?? 0);
        if ($teacherRegId <= 0) {
            $errors[] = 'Session expired. Please restart registration.';
        } else {
            $qualification = trim((string) ($_POST['qualification'] ?? ''));
            $bedStatus = trim((string) ($_POST['bed_status'] ?? ''));
            $university = trim((string) ($_POST['university'] ?? ''));
            $passingYear = trim((string) ($_POST['passing_year'] ?? ''));
            $experience = trim((string) ($_POST['experience_years'] ?? ''));
            $subjectsInput = $_POST['subjects'] ?? [];
            $subjectsInput = is_array($subjectsInput) ? array_values(array_unique(array_map(static fn($v) => trim((string) $v), $subjectsInput))) : [];
            $subjectsInput = array_values(array_filter($subjectsInput, static fn($v) => $v !== ''));

            $form['qualification'] = $qualification;
            $form['bed_status'] = $bedStatus;
            $form['university'] = $university;
            $form['passing_year'] = $passingYear;
            $form['experience_years'] = $experience;
            $form['subjects'] = $subjectsInput;

            if ($qualification === '' || $university === '' || $passingYear === '' || !$subjectsInput) {
                $errors[] = 'All academic fields are required.';
            }
            if (!in_array($bedStatus, ['Yes', 'No', 'Pursuing'], true)) {
                $errors[] = 'Select a valid B.Ed status.';
            }
            if (!preg_match('/^\d{4}$/', $passingYear)) {
                $errors[] = 'Passing year must be 4 digits.';
            }
            if ($experience === '' || !is_numeric($experience) || (float) $experience < 0 || (float) $experience > 50) {
                $errors[] = 'Experience must be between 0 and 50 years.';
            }
            foreach ($subjectsInput as $subjectItem) {
                if (!in_array($subjectItem, $subjects, true)) {
                    $errors[] = 'Please select valid subjects only.';
                    break;
                }
            }

            if (!$errors) {
                $subjectsValue = implode(', ', $subjectsInput);
                $update = $pdo->prepare(
                    'UPDATE teachers SET qualification = :qualification, bed_status = :bed_status, university = :university, passing_year = :passing_year, experience_years = :experience_years, subjects = :subjects, registration_step = 2 WHERE id = :id AND status = :status'
                );
                $update->execute([
                    ':qualification' => $qualification,
                    ':bed_status' => $bedStatus,
                    ':university' => $university,
                    ':passing_year' => (int) $passingYear,
                    ':experience_years' => (float) $experience,
                    ':subjects' => $subjectsValue,
                    ':id' => $teacherRegId,
                    ':status' => 'pending',
                ]);

                $_SESSION['teacher_reg_step'] = 3;
                redirectRegister(3);
            }
        }
    } elseif ($action === 'step3') {
        $teacherRegId = (int) ($_SESSION['teacher_reg_teacher_id'] ?? 0);
        if ($teacherRegId <= 0) {
            $errors[] = 'Session expired. Please restart registration.';
        } else {
            $baseUploadDir = __DIR__ . '/../../uploads/teacher';
            $prefix = 'teacher_' . $teacherRegId;
            $storedFiles = [];

            $photoResult = handleUpload(
                $_FILES['photo'] ?? [],
                ['image/jpeg' => 'jpg', 'image/png' => 'png'],
                2 * 1024 * 1024,
                $baseUploadDir . '/photos',
                $prefix . '_photo'
            );
            if (!$photoResult[0]) {
                $errors[] = 'Photo: ' . $photoResult[1];
            } else {
                $storedFiles['photo_path'] = 'uploads/teacher/photos/' . $photoResult[1];
            }

            $signResult = handleUpload(
                $_FILES['signature'] ?? [],
                ['image/jpeg' => 'jpg', 'image/png' => 'png'],
                1 * 1024 * 1024,
                $baseUploadDir . '/signatures',
                $prefix . '_signature'
            );
            if (!$signResult[0]) {
                $errors[] = 'Signature: ' . $signResult[1];
            } else {
                $storedFiles['signature_path'] = 'uploads/teacher/signatures/' . $signResult[1];
            }

            $certResult = handleUpload(
                $_FILES['certificate'] ?? [],
                ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'],
                3 * 1024 * 1024,
                $baseUploadDir . '/certificates',
                $prefix . '_certificate'
            );
            if (!$certResult[0]) {
                $errors[] = 'Certificate: ' . $certResult[1];
            } else {
                $storedFiles['certificate_path'] = 'uploads/teacher/certificates/' . $certResult[1];
            }

            $idResult = handleUpload(
                $_FILES['id_proof'] ?? [],
                ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'],
                3 * 1024 * 1024,
                $baseUploadDir . '/idproof',
                $prefix . '_idproof'
            );
            if (!$idResult[0]) {
                $errors[] = 'ID Proof: ' . $idResult[1];
            } else {
                $storedFiles['id_proof_path'] = 'uploads/teacher/idproof/' . $idResult[1];
            }

            if ($errors) {
                foreach ($storedFiles as $relativePath) {
                    $absolutePath = __DIR__ . '/../../' . $relativePath;
                    if (is_file($absolutePath)) {
                        @unlink($absolutePath);
                    }
                }
            } else {
                $update = $pdo->prepare(
                    'UPDATE teachers
                     SET photo_path = :photo_path, signature_path = :signature_path, certificate_path = :certificate_path, id_proof_path = :id_proof_path, status = :status, registration_step = :registration_step, submitted_at = NOW()
                     WHERE id = :id AND status = :status_check'
                );
                $update->execute([
                    ':photo_path' => $storedFiles['photo_path'],
                    ':signature_path' => $storedFiles['signature_path'],
                    ':certificate_path' => $storedFiles['certificate_path'],
                    ':id_proof_path' => $storedFiles['id_proof_path'],
                    ':status' => 'pending',
                    ':registration_step' => 3,
                    ':id' => $teacherRegId,
                    ':status_check' => 'pending',
                ]);

                $teacherInfoStmt = $pdo->prepare('SELECT registration_id, first_name, last_name FROM teachers WHERE id = :id LIMIT 1');
                $teacherInfoStmt->execute([':id' => $teacherRegId]);
                $teacherInfo = $teacherInfoStmt->fetch();

                if ($teacherInfo) {
                    $fullName = trim((string) ($teacherInfo['first_name'] . ' ' . $teacherInfo['last_name']));
                    teacherNotification(
                        $pdo,
                        null,
                        'admin',
                        'New Teacher Application',
                        'Application ' . (string) $teacherInfo['registration_id'] . ' submitted by ' . $fullName . '.'
                    );
                }

                unset($_SESSION['teacher_reg_teacher_id'], $_SESSION['teacher_reg_step']);
                $success = 'Your application has been submitted successfully. Await admin approval.';
                $currentStep = 1;
            }
        }
    }
}

$token = teacherModuleCsrfToken('teacher_register_csrf');
$activeStep = $success !== '' ? 3 : $currentStep;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teacher Registration - ExamPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #eef2ff, #f8fafc); min-height: 100vh; }
        .register-wrap { max-width: 960px; margin: 2rem auto; }
        .step-badge { width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; }
        .step-active { background: #4f46e5; color: #fff; }
        .step-done { background: #16a34a; color: #fff; }
        .step-pending { background: #e2e8f0; color: #475569; }
        .step-line { flex: 1; height: 2px; background: #cbd5e1; margin: 0 .5rem; }
        .card { border-radius: 16px; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>
<div class="container register-wrap">
    <div class="card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h1 class="h3 fw-bold mb-0">Teacher Registration</h1>
                <a href="login.php" class="btn btn-outline-primary btn-sm">Already registered? Login</a>
            </div>

            <div class="d-flex align-items-center mb-4">
                <div class="step-badge <?= $activeStep >= 1 ? ($activeStep > 1 ? 'step-done' : 'step-active') : 'step-pending' ?>">1</div>
                <div class="step-line"></div>
                <div class="step-badge <?= $activeStep >= 2 ? ($activeStep > 2 ? 'step-done' : 'step-active') : 'step-pending' ?>">2</div>
                <div class="step-line"></div>
                <div class="step-badge <?= $activeStep >= 3 ? 'step-active' : 'step-pending' ?>">3</div>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <?php if ($success === '' && $currentStep === 1): ?>
                <form method="post" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                    <input type="hidden" name="action" value="step1">
                    <h2 class="h5 mb-3">Step 1 - Account & Personal Info</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($form['email']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= e($form['phone']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" value="<?= e($form['first_name']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" value="<?= e($form['last_name']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label">DOB</label><input type="date" name="dob" class="form-control" value="<?= e($form['dob']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                                    <option value="<?= e($g) ?>" <?= $form['gender'] === $g ? 'selected' : '' ?>><?= e($g) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 text-end"><button type="submit" class="btn btn-primary">Next</button></div>
                </form>
            <?php endif; ?>

            <?php if ($success === '' && $currentStep === 2): ?>
                <form method="post" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                    <input type="hidden" name="action" value="step2">
                    <h2 class="h5 mb-3">Step 2 - Academic & Experience</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" value="<?= e($form['qualification']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label">B.Ed Status</label>
                            <select name="bed_status" class="form-select" required>
                                <option value="">Select</option>
                                <?php foreach (['Yes', 'No', 'Pursuing'] as $b): ?>
                                    <option value="<?= e($b) ?>" <?= $form['bed_status'] === $b ? 'selected' : '' ?>><?= e($b) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">University</label><input type="text" name="university" class="form-control" value="<?= e($form['university']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Passing Year</label><input type="number" name="passing_year" class="form-control" min="1980" max="2099" value="<?= e($form['passing_year']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Experience (Years)</label><input type="number" step="0.5" name="experience_years" class="form-control" min="0" max="50" value="<?= e($form['experience_years']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Subjects (Multi-select)</label>
                            <select name="subjects[]" class="form-select" multiple size="6" required>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= e($subject) ?>" <?= in_array($subject, $form['subjects'], true) ? 'selected' : '' ?>><?= e($subject) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Hold Ctrl (Windows) or Cmd (Mac) to select multiple subjects.</div>
                        </div>
                    </div>
                    <div class="mt-4 text-end"><button type="submit" class="btn btn-primary">Next</button></div>
                </form>
            <?php endif; ?>

            <?php if ($success === '' && $currentStep === 3): ?>
                <form method="post" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                    <input type="hidden" name="action" value="step3">
                    <h2 class="h5 mb-3">Step 3 - Upload Documents & Submit</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Photo (JPG/PNG, max 2MB)</label>
                            <input type="file" name="photo" accept=".jpg,.jpeg,.png" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Signature (JPG/PNG, max 1MB)</label>
                            <input type="file" name="signature" accept=".jpg,.jpeg,.png" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Certificate (PDF/JPG/PNG, max 3MB)</label>
                            <input type="file" name="certificate" accept=".pdf,.jpg,.jpeg,.png" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ID Proof (PDF/JPG/PNG, max 3MB)</label>
                            <input type="file" name="id_proof" accept=".pdf,.jpg,.jpeg,.png" class="form-control" required>
                        </div>
                    </div>
                    <div class="mt-4 text-end"><button type="submit" class="btn btn-success">Final Submit</button></div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
