<?php
require_once '../config/db.php';

$error = '';

if (isset($_SESSION['student_id'])) {
    header('Location: student-panel.php');
    exit;
}

function ensure_student_profiles_table(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS student_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL UNIQUE,
        first_name VARCHAR(60) NOT NULL,
        last_name VARCHAR(60) NOT NULL,
        dob DATE NOT NULL,
        gender ENUM('Male', 'Female', 'Other') NOT NULL,
        address TEXT NOT NULL,
        city VARCHAR(100) NOT NULL,
        zip_code VARCHAR(12) NOT NULL,
        mobile_country_code VARCHAR(8) NOT NULL,
        mobile_number VARCHAR(20) NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        signature_path VARCHAR(255) NOT NULL,
        branch VARCHAR(100) NOT NULL,
        course_program VARCHAR(120) NOT NULL,
        current_semester VARCHAR(40) NOT NULL,
        selected_subjects TEXT NOT NULL,
        exam_medium VARCHAR(40) NOT NULL,
        marksheet_path VARCHAR(255) DEFAULT NULL,
        terms_accepted TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $conn->query($sql);
}

function set_new_captcha(): void
{
    $_SESSION['register_captcha_a'] = random_int(1, 9);
    $_SESSION['register_captcha_b'] = random_int(1, 9);
}

function validate_upload(array $file, array $allowedExts, int $maxBytes, bool $required, string $label): array
{
    if (!isset($file['error']) || !isset($file['name']) || !isset($file['tmp_name'])) {
        return [false, "$label upload data is missing.", null, null];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        if ($required) {
            return [false, "$label is required.", null, null];
        }
        return [true, '', null, null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [false, "$label upload failed.", null, null];
    }

    if (($file['size'] ?? 0) > $maxBytes) {
        return [false, "$label must be smaller than " . (int) ($maxBytes / (1024 * 1024)) . "MB.", null, null];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        return [false, "$label format is invalid.", null, null];
    }

    return [true, '', $file['tmp_name'], $ext];
}

ensure_student_profiles_table($conn);

if (!isset($_SESSION['register_captcha_a'], $_SESSION['register_captcha_b'])) {
    set_new_captcha();
}

$subjectOptions = ['Mathematics', 'Physics', 'Chemistry', 'Computer Science', 'English', 'Economics'];
$mediumOptions = ['English', 'Hindi', 'Bilingual'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $name = trim($first_name . ' ' . $last_name);
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile_country_code = trim($_POST['mobile_country_code'] ?? '+91');
    $mobile_number = trim($_POST['mobile_number'] ?? '');

    $roll_number = trim($_POST['roll_number'] ?? '');
    $department = trim($_POST['department'] ?? 'BCA');
    $branch = trim($_POST['branch'] ?? '');
    $course_program = trim($_POST['course_program'] ?? '');
    $current_semester = trim($_POST['current_semester'] ?? 'Semester 1');

    $subjects = $_POST['subjects'] ?? [];
    $exam_medium = trim($_POST['exam_medium'] ?? 'English');

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $captcha_answer = trim($_POST['captcha_answer'] ?? '');
    $terms = isset($_POST['terms']) ? 1 : 0;

    $errors = [];

    if (
        $first_name === '' || $last_name === '' || $dob === '' || $gender === '' || $address === '' || $city === '' ||
        $zip_code === '' || $email === '' || $mobile_country_code === '' || $mobile_number === '' ||
        $roll_number === '' || $department === '' || $branch === '' || $course_program === '' ||
        $current_semester === '' || $password === '' || $confirm_password === '' || $captcha_answer === ''
    ) {
        $errors[] = 'Please fill in all required fields.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!preg_match('/^\+\d{1,4}$/', $mobile_country_code)) {
        $errors[] = 'Country code must be like +91.';
    }

    if (!preg_match('/^\d{7,15}$/', $mobile_number)) {
        $errors[] = 'Mobile number must contain 7 to 15 digits.';
    }

    if (!preg_match('/^[A-Za-z0-9\/-]{3,50}$/', $roll_number)) {
        $errors[] = 'Roll number format is invalid.';
    }

    if (!preg_match('/^\d{4,10}$/', $zip_code)) {
        $errors[] = 'Zip code must contain 4 to 10 digits.';
    }

    $dobDate = DateTime::createFromFormat('Y-m-d', $dob);
    $today = new DateTime('today');
    if (!$dobDate || $dobDate->format('Y-m-d') !== $dob || $dobDate > $today) {
        $errors[] = 'Please enter a valid date of birth.';
    }

    if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
        $errors[] = 'Please select a valid gender.';
    }

    if (!in_array($exam_medium, $mediumOptions, true)) {
        $errors[] = 'Please select a valid exam medium.';
    }

    if (!is_array($subjects) || count($subjects) === 0) {
        $errors[] = 'Select at least one subject.';
    } else {
        foreach ($subjects as $subject) {
            if (!in_array($subject, $subjectOptions, true)) {
                $errors[] = 'Invalid subject selection.';
                break;
            }
        }
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/\d/', $password) ||
        !preg_match('/[^A-Za-z0-9]/', $password)
    ) {
        $errors[] = 'Password must be at least 8 characters and include uppercase, lowercase, number and special character.';
    }

    $expectedCaptcha = (int) ($_SESSION['register_captcha_a'] ?? -1) + (int) ($_SESSION['register_captcha_b'] ?? -1);
    if ((int) $captcha_answer !== $expectedCaptcha) {
        $errors[] = 'Captcha answer is incorrect.';
    }

    if ($terms !== 1) {
        $errors[] = 'You must accept the declaration and terms.';
    }

    [$okPhoto, $photoErr, $photoTmp, $photoExt] = validate_upload($_FILES['photo'] ?? [], ['jpg', 'jpeg', 'png'], 2 * 1024 * 1024, true, 'Photograph');
    [$okSign, $signErr, $signTmp, $signExt] = validate_upload($_FILES['signature'] ?? [], ['jpg', 'jpeg', 'png'], 2 * 1024 * 1024, true, 'Signature');
    [$okMarksheet, $marksheetErr, $marksheetTmp, $marksheetExt] = validate_upload($_FILES['marksheet'] ?? [], ['pdf', 'jpg', 'jpeg', 'png'], 5 * 1024 * 1024, false, 'Marksheet');

    if (!$okPhoto) { $errors[] = $photoErr; }
    if (!$okSign) { $errors[] = $signErr; }
    if (!$okMarksheet) { $errors[] = $marksheetErr; }

    $savedFiles = [];

    if (empty($errors)) {
        $check = $conn->prepare('SELECT id FROM students WHERE email = ? OR roll_number = ?');
        $check->bind_param('ss', $email, $roll_number);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = 'An account with this email or roll number already exists.';
        }
        $check->close();
    }

    if (empty($errors)) {
        $baseUploadDir = __DIR__ . '/uploads/student_docs';
        $photoDir = $baseUploadDir . '/photos';
        $signDir = $baseUploadDir . '/signatures';
        $marksheetDir = $baseUploadDir . '/marksheets';

        if (!is_dir($photoDir) && !mkdir($photoDir, 0755, true) && !is_dir($photoDir)) { $errors[] = 'Could not create photo upload directory.'; }
        if (!is_dir($signDir) && !mkdir($signDir, 0755, true) && !is_dir($signDir)) { $errors[] = 'Could not create signature upload directory.'; }
        if (!is_dir($marksheetDir) && !mkdir($marksheetDir, 0755, true) && !is_dir($marksheetDir)) { $errors[] = 'Could not create marksheet upload directory.'; }

        if (empty($errors)) {
            $photoFileName = 'photo_' . uniqid('', true) . '.' . $photoExt;
            $signFileName = 'sign_' . uniqid('', true) . '.' . $signExt;
            $marksheetFileName = $marksheetTmp ? ('marksheet_' . uniqid('', true) . '.' . $marksheetExt) : null;

            $photoDiskPath = $photoDir . '/' . $photoFileName;
            $signDiskPath = $signDir . '/' . $signFileName;
            $marksheetDiskPath = $marksheetFileName ? ($marksheetDir . '/' . $marksheetFileName) : null;

            if (!move_uploaded_file($photoTmp, $photoDiskPath)) {
                $errors[] = 'Failed to save photograph file.';
            } else {
                $savedFiles[] = $photoDiskPath;
            }

            if (!move_uploaded_file($signTmp, $signDiskPath)) {
                $errors[] = 'Failed to save signature file.';
            } else {
                $savedFiles[] = $signDiskPath;
            }

            if ($marksheetTmp && $marksheetDiskPath) {
                if (!move_uploaded_file($marksheetTmp, $marksheetDiskPath)) {
                    $errors[] = 'Failed to save marksheet file.';
                } else {
                    $savedFiles[] = $marksheetDiskPath;
                }
            }

            if (empty($errors)) {
                $photoRelPath = 'uploads/student_docs/photos/' . $photoFileName;
                $signRelPath = 'uploads/student_docs/signatures/' . $signFileName;
                $marksheetRelPath = $marksheetFileName ? ('uploads/student_docs/marksheets/' . $marksheetFileName) : null;

                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $subjectsJson = json_encode(array_values($subjects), JSON_UNESCAPED_UNICODE);

                $conn->begin_transaction();

                $studentInserted = false;
                $profileInserted = false;

                $studentStmt = $conn->prepare('INSERT INTO students (name, email, roll_number, department, password) VALUES (?, ?, ?, ?, ?)');
                if ($studentStmt) {
                    $studentStmt->bind_param('sssss', $name, $email, $roll_number, $department, $hashed);
                    $studentInserted = $studentStmt->execute();
                }

                if ($studentInserted) {
                    $studentId = $conn->insert_id;
                    $profileStmt = $conn->prepare('INSERT INTO student_profiles (
                        student_id, first_name, last_name, dob, gender, address, city, zip_code,
                        mobile_country_code, mobile_number, photo_path, signature_path,
                        branch, course_program, current_semester, selected_subjects, exam_medium,
                        marksheet_path, terms_accepted
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

                    if ($profileStmt) {
                        $profileStmt->bind_param('isssssssssssssssssi', $studentId, $first_name, $last_name, $dob, $gender, $address, $city, $zip_code, $mobile_country_code, $mobile_number, $photoRelPath, $signRelPath, $branch, $course_program, $current_semester, $subjectsJson, $exam_medium, $marksheetRelPath, $terms);
                        $profileInserted = $profileStmt->execute();
                        $profileStmt->close();
                    }
                }

                if ($studentStmt) { $studentStmt->close(); }

                if ($studentInserted && $profileInserted) {
                    $conn->commit();
                    set_new_captcha();
                    header('Location: login.php?registered=1');
                    exit;
                }

                $conn->rollback();
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }

    if (!empty($errors)) {
        foreach ($savedFiles as $savedFile) {
            if (is_file($savedFile)) { @unlink($savedFile); }
        }
        $error = implode(' ', array_unique($errors));
    }

    set_new_captcha();
}

$captchaA = (int) ($_SESSION['register_captcha_a'] ?? 0);
$captchaB = (int) ($_SESSION['register_captcha_b'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - ExamPro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #c8ceee, #b9c1e6);
            padding: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-shell {
            width: 100%;
            max-width: 1240px;
            min-height: 86vh;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 22px 60px rgba(15, 23, 42, 0.28);
            background: #101321;
        }
        .form-pane {
            background: linear-gradient(180deg, #191c2a, #151825);
            padding: 2rem 1.9rem 1.5rem;
            color: #e5e7eb;
            overflow-y: auto;
        }
        .pane-inner { position: relative; z-index: 2; }

        .form-title { font-size: 2.2rem; font-weight: 700; margin-bottom: 0.25rem; }
        .form-subtitle { color: #9ca3af; font-size: 0.95rem; margin-bottom: 1rem; }
        .section-title {
            margin: 1rem 0 0.7rem;
            color: #e5e7eb;
            font-size: 0.84rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border-bottom: 1px solid #2f3343;
            padding-bottom: 0.35rem;
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem 0.8rem; }
        .form-group { margin-bottom: 0.1rem; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 500; color: #cbd5e1; margin-bottom: 0.25rem; }
        .input-wrap { position: relative; }
        .input-wrap i {
            position: absolute;
            left: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.85rem;
        }
        .input-wrap .fa-lock { color: #94a3b8; font-size: 0.9rem; }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.62rem 0.8rem 0.62rem 2.2rem;
            border: 1px solid #343a4f;
            border-radius: 8px;
            font-size: 0.84rem;
            color: #172f4f;
            background: #f7f9fe;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-group textarea { min-height: 78px; resize: vertical; padding-left: 0.8rem; }
        .form-group input[type="file"] { padding-left: 0.8rem; background: #ffffff; }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.16);
        }
        .form-group select { appearance: none; cursor: pointer; }
        .select-wrap::after {
            content: '\f107';
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #7c8ea6;
            pointer-events: none;
            font-size: 0.82rem;
        }
        .full-width { grid-column: 1 / -1; }
        .helper { color: #9ca3af; font-size: 0.73rem; margin-top: 0.18rem; }
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.32rem 0.5rem;
            padding: 0.55rem;
            border: 1px solid #343a4f;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.06);
        }
        .checkbox-grid label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            color: #e2e8f0;
            font-size: 0.79rem;
            margin: 0;
        }
        .checkbox-grid input { accent-color: #8b5cf6; width: auto; }

        .captcha-box { display: flex; gap: 0.55rem; align-items: center; }
        .captcha-question {
            min-width: 115px;
            padding: 0.55rem;
            border-radius: 8px;
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            border: 1px dashed rgba(255, 255, 255, 0.35);
            font-weight: 600;
            font-size: 0.84rem;
        }
        .declaration {
            display: flex;
            gap: 0.5rem;
            color: #cbd5e1;
            font-size: 0.79rem;
            align-items: flex-start;
        }
        .declaration input { margin-top: 0.14rem; accent-color: #8b5cf6; }
        .strength { font-size: 0.75rem; margin-top: 0.22rem; font-weight: 600; color: #64748b; }

        .btn-register {
            width: 100%;
            border: none;
            min-height: 46px;
            border-radius: 9px;
            font-size: 0.93rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(90deg, #8b5cf6, #7c4bd9);
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .btn-register:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(124, 75, 217, 0.3); }

        .alert { padding: 0.7rem 0.9rem; border-radius: 8px; font-size: 0.84rem; margin-bottom: 0.9rem; }
        .alert-error { background: #fff1f2; color: #dc2626; border: 1px solid #fecdd3; }

        .register-footer { margin-top: 1rem; text-align: center; color: #9ca3af; font-size: 0.86rem; }
        .register-footer a { color: #c4b5fd; text-decoration: none; font-weight: 600; }
        .register-footer a:hover { text-decoration: underline; }

        @media (max-width: 1050px) {
            .register-shell { min-height: unset; }
            .form-pane { max-height: none; }
        }
        @media (max-width: 760px) {
            .form-pane { padding: 1rem; }
            .form-row { grid-template-columns: 1fr; }
            .checkbox-grid { grid-template-columns: 1fr; }
            .captcha-box { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
    <div class="register-shell">
        <section class="form-pane">
            <h1 class="form-title">Register</h1>
            <p class="form-subtitle">Enter your account details</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" id="student-register-form" novalidate>
                <h3 class="section-title">Personal Details</h3>
                <div class="form-row">
                    <div class="form-group"><label for="first_name">First Name</label><div class="input-wrap"><i class="fas fa-user"></i><input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required maxlength="60"></div></div>
                    <div class="form-group"><label for="last_name">Last Name</label><div class="input-wrap"><i class="fas fa-user"></i><input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required maxlength="60"></div></div>
                    <div class="form-group"><label for="dob">Date of Birth</label><div class="input-wrap"><i class="fas fa-calendar"></i><input type="date" id="dob" name="dob" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>" max="<?= date('Y-m-d') ?>" required></div></div>
                    <div class="form-group"><label for="gender">Gender</label><div class="input-wrap select-wrap"><i class="fas fa-venus-mars"></i><select id="gender" name="gender" required><option value="">Select gender</option><option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option><option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option><option value="Other" <?= (($_POST['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option></select></div></div>
                    <div class="form-group full-width"><label for="address">Address</label><textarea id="address" name="address" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea></div>
                    <div class="form-group"><label for="city">City</label><div class="input-wrap"><i class="fas fa-city"></i><input type="text" id="city" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" required maxlength="100"></div></div>
                    <div class="form-group"><label for="zip_code">Zip Code</label><div class="input-wrap"><i class="fas fa-map-pin"></i><input type="text" id="zip_code" name="zip_code" pattern="\d{4,10}" value="<?= htmlspecialchars($_POST['zip_code'] ?? '') ?>" required></div></div>
                    <div class="form-group"><label for="email">Email Address</label><div class="input-wrap"><i class="fas fa-envelope"></i><input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required></div></div>
                    <div class="form-group"><label for="mobile_number">Mobile Number</label><div class="form-row" style="grid-template-columns: 115px 1fr; gap: 0.45rem;"><div class="input-wrap"><i class="fas fa-globe"></i><input type="text" id="mobile_country_code" name="mobile_country_code" value="<?= htmlspecialchars($_POST['mobile_country_code'] ?? '+91') ?>" pattern="\+\d{1,4}" required></div><div class="input-wrap"><i class="fas fa-phone"></i><input type="text" id="mobile_number" name="mobile_number" value="<?= htmlspecialchars($_POST['mobile_number'] ?? '') ?>" pattern="\d{7,15}" required></div></div></div>
                    <div class="form-group"><label for="photo">Photograph</label><input type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png" required><div class="helper">JPG/PNG, max 2MB</div></div>
                    <div class="form-group"><label for="signature">Signature</label><input type="file" id="signature" name="signature" accept=".jpg,.jpeg,.png" required><div class="helper">JPG/PNG, max 2MB</div></div>
                </div>

                <h3 class="section-title">Academic Details</h3>
                <div class="form-row">
                    <div class="form-group"><label for="roll_number">Roll Number / Student ID</label><div class="input-wrap"><i class="fas fa-id-card"></i><input type="text" id="roll_number" name="roll_number" value="<?= htmlspecialchars($_POST['roll_number'] ?? '') ?>" required maxlength="50"></div></div>
                    <div class="form-group"><label for="department">Department / Branch</label><div class="input-wrap select-wrap"><i class="fas fa-building"></i><select id="department" name="department" required><option value="BCA" <?= (($_POST['department'] ?? 'BCA') === 'BCA') ? 'selected' : '' ?>>BCA</option><option value="BBA" <?= (($_POST['department'] ?? '') === 'BBA') ? 'selected' : '' ?>>BBA</option><option value="BSc" <?= (($_POST['department'] ?? '') === 'BSc') ? 'selected' : '' ?>>BSc</option><option value="BCom" <?= (($_POST['department'] ?? '') === 'BCom') ? 'selected' : '' ?>>BCom</option></select></div></div>
                    <div class="form-group"><label for="branch">Branch</label><div class="input-wrap"><i class="fas fa-code-branch"></i><input type="text" id="branch" name="branch" value="<?= htmlspecialchars($_POST['branch'] ?? '') ?>" required maxlength="100"></div></div>
                    <div class="form-group"><label for="course_program">Course / Program</label><div class="input-wrap"><i class="fas fa-book"></i><input type="text" id="course_program" name="course_program" value="<?= htmlspecialchars($_POST['course_program'] ?? '') ?>" required maxlength="120"></div></div>
                    <div class="form-group full-width">
                        <label for="current_semester">Current Semester / Year</label>
                        <div class="input-wrap select-wrap"><i class="fas fa-layer-group"></i><select id="current_semester" name="current_semester" required>
                            <?php
                            $semesters = ['Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Semester 5', 'Semester 6', 'Year 1', 'Year 2', 'Year 3', 'Year 4'];
                            $selectedSemester = $_POST['current_semester'] ?? 'Semester 1';
                            foreach ($semesters as $semester):
                            ?>
                                <option value="<?= htmlspecialchars($semester) ?>" <?= ($selectedSemester === $semester) ? 'selected' : '' ?>><?= htmlspecialchars($semester) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    </div>
                </div>

                <h3 class="section-title">Examination Specifics</h3>
                <div class="form-row">
                    <div class="form-group full-width"><label>Subject Selection</label><div class="checkbox-grid"><?php foreach ($subjectOptions as $subject): ?><label><input type="checkbox" name="subjects[]" value="<?= htmlspecialchars($subject) ?>" <?= in_array($subject, $_POST['subjects'] ?? [], true) ? 'checked' : '' ?>><span><?= htmlspecialchars($subject) ?></span></label><?php endforeach; ?></div></div>
                    <div class="form-group"><label for="exam_medium">Exam Medium / Language</label><div class="input-wrap select-wrap"><i class="fas fa-language"></i><select id="exam_medium" name="exam_medium" required><?php $selectedMedium = $_POST['exam_medium'] ?? 'English'; foreach ($mediumOptions as $medium): ?><option value="<?= htmlspecialchars($medium) ?>" <?= ($selectedMedium === $medium) ? 'selected' : '' ?>><?= htmlspecialchars($medium) ?></option><?php endforeach; ?></select></div></div>
                    <div class="form-group"><label for="marksheet">Previous Semester Marksheet</label><input type="file" id="marksheet" name="marksheet" accept=".pdf,.jpg,.jpeg,.png"><div class="helper">Optional, max 5MB</div></div>
                </div>

                <h3 class="section-title">Security & Submission</h3>
                <div class="form-row">
                    <div class="form-group"><label for="password">Password</label><div class="input-wrap"><i class="fas fa-lock"></i><input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"></div><div id="password-strength" class="strength">Strength: -</div></div>
                    <div class="form-group"><label for="confirm_password">Confirm Password</label><div class="input-wrap"><i class="fas fa-lock"></i><input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password"></div></div>
                    <div class="form-group full-width"><label for="captcha_answer">Captcha Verification</label><div class="captcha-box"><div class="captcha-question"><?= $captchaA ?> + <?= $captchaB ?> = ?</div><div class="input-wrap" style="flex: 1;"><i class="fas fa-shield"></i><input type="number" id="captcha_answer" name="captcha_answer" min="0" required></div></div></div>
                    <div class="form-group full-width"><label class="declaration" for="terms"><input type="checkbox" id="terms" name="terms" value="1" <?= isset($_POST['terms']) ? 'checked' : '' ?> required><span>I declare that the information provided is correct and I agree to the Terms & Conditions.</span></label></div>
                    <div class="full-width"><button type="submit" class="btn-register">Submit Registration</button></div>
                </div>
            </form>

            <div class="register-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <a href="../index.html"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </section>

    </div>
    <script>
        (function () {
            var form = document.getElementById('student-register-form');
            var passwordInput = document.getElementById('password');
            var confirmInput = document.getElementById('confirm_password');
            var strengthEl = document.getElementById('password-strength');

            function passwordScore(value) {
                var score = 0;
                if (value.length >= 8) score++;
                if (/[A-Z]/.test(value)) score++;
                if (/[a-z]/.test(value)) score++;
                if (/\d/.test(value)) score++;
                if (/[^A-Za-z0-9]/.test(value)) score++;
                return score;
            }

            function updatePasswordStrength() {
                var value = passwordInput.value;
                var score = passwordScore(value);
                var label = 'Weak';

                if (score >= 5) label = 'Strong';
                else if (score >= 4) label = 'Medium';

                strengthEl.textContent = 'Strength: ' + (value ? label : '-');
                strengthEl.style.color = '#64748b';

                if (value && score < 5) {
                    passwordInput.setCustomValidity('Use 8+ chars with uppercase, lowercase, number and special character.');
                } else {
                    passwordInput.setCustomValidity('');
                }
            }

            function validateConfirmPassword() {
                if (confirmInput.value && confirmInput.value !== passwordInput.value) {
                    confirmInput.setCustomValidity('Passwords do not match.');
                } else {
                    confirmInput.setCustomValidity('');
                }
            }

            passwordInput.addEventListener('input', function () {
                updatePasswordStrength();
                validateConfirmPassword();
            });
            confirmInput.addEventListener('input', validateConfirmPassword);

            form.addEventListener('submit', function (e) {
                updatePasswordStrength();
                validateConfirmPassword();

                var subjects = form.querySelectorAll('input[name="subjects[]"]:checked');
                if (!subjects.length) {
                    e.preventDefault();
                    alert('Please select at least one subject.');
                    return;
                }

                if (!form.checkValidity()) {
                    e.preventDefault();
                    form.reportValidity();
                }
            });
        })();
    </script>
</body>
</html>
