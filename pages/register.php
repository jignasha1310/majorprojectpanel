<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function json_response(int $statusCode, bool $success, string $message, array $extra = []): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra));
    exit;
}

function ensure_registration_table(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS student_registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(80) NOT NULL,
        middle_name VARCHAR(80) DEFAULT NULL,
        last_name VARCHAR(80) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        contact_number VARCHAR(20) NOT NULL,
        gender ENUM('Male', 'Female', 'Other') NOT NULL,
        dob DATE NOT NULL,
        roll_number VARCHAR(80) NOT NULL UNIQUE,
        course_program VARCHAR(100) NOT NULL,
        current_semester_year VARCHAR(50) NOT NULL,
        previous_cgpa DECIMAL(4,2) DEFAULT NULL,
        examination_name VARCHAR(120) NOT NULL,
        subjects TEXT NOT NULL,
        exam_language ENUM('English', 'Hindi') NOT NULL,
        id_proof_path VARCHAR(255) NOT NULL,
        declaration_accepted TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$conn->query($sql)) {
        json_response(500, false, 'Failed to initialize registration table: ' . mysqli_error($conn));
    }
}

function email_or_roll_exists(mysqli $conn, string $email, string $rollNumber): bool
{
    $checkStmt = $conn->prepare('SELECT id FROM student_registrations WHERE email = ? OR roll_number = ? LIMIT 1');
    if (!$checkStmt) {
        json_response(500, false, 'Failed to prepare duplicate check query: ' . mysqli_error($conn));
    }

    $checkStmt->bind_param('ss', $email, $rollNumber);
    if (!$checkStmt->execute()) {
        $checkStmt->close();
        json_response(500, false, 'Failed to run duplicate check query: ' . mysqli_error($conn));
    }

    $result = $checkStmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $checkStmt->close();
    return $exists;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensure_registration_table($conn);

    $requiredFields = [
        'firstName',
        'lastName',
        'email',
        'contact',
        'gender',
        'dob',
        'rollNumber',
        'courseProgram',
        'semesterYear',
        'examName',
        'examLanguage',
    ];

    $errors = [];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
            $errors[] = "$field is required.";
        }
    }

    $firstName = trim((string) ($_POST['firstName'] ?? ''));
    $middleName = trim((string) ($_POST['middleName'] ?? ''));
    $lastName = trim((string) ($_POST['lastName'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $contact = trim((string) ($_POST['contact'] ?? ''));
    $gender = trim((string) ($_POST['gender'] ?? ''));
    $dob = trim((string) ($_POST['dob'] ?? ''));
    $rollNumber = trim((string) ($_POST['rollNumber'] ?? ''));
    $courseProgram = trim((string) ($_POST['courseProgram'] ?? ''));
    $semesterYear = trim((string) ($_POST['semesterYear'] ?? ''));
    $cgpaRaw = trim((string) ($_POST['cgpa'] ?? ''));
    $examName = trim((string) ($_POST['examName'] ?? ''));
    $examLanguage = trim((string) ($_POST['examLanguage'] ?? ''));
    $subjects = $_POST['subjects'] ?? [];
    $declaration = isset($_POST['declaration']) ? 1 : 0;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    if (!preg_match('/^[0-9+\-()\s]{7,15}$/', $contact)) {
        $errors[] = 'Invalid contact number format.';
    }

    if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
        $errors[] = 'Invalid gender selected.';
    }

    if (!in_array($examLanguage, ['English', 'Hindi'], true)) {
        $errors[] = 'Invalid exam language selected.';
    }

    if (!is_array($subjects) || count($subjects) === 0) {
        $errors[] = 'At least one subject is required.';
    }

    if ($declaration !== 1) {
        $errors[] = 'You must agree to the declaration.';
    }

    $cgpa = null;
    if ($cgpaRaw !== '') {
        if (!is_numeric($cgpaRaw)) {
            $errors[] = 'CGPA must be a valid number.';
        } else {
            $cgpa = (float) $cgpaRaw;
            if ($cgpa < 0 || $cgpa > 10) {
                $errors[] = 'CGPA must be between 0 and 10.';
            }
        }
    }

    if (!isset($_FILES['idProof'])) {
        $errors[] = 'ID proof file is required.';
    }

    $storedRelativePath = '';
    $uploadTargetPath = '';

    if (isset($_FILES['idProof'])) {
        $idProof = $_FILES['idProof'];

        if (($idProof['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'ID proof upload failed.';
        } else {
            $originalName = (string) ($idProof['name'] ?? '');
            $tmpName = (string) ($idProof['tmp_name'] ?? '');
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];

            if (!in_array($extension, $allowedExtensions, true)) {
                $errors[] = 'Only JPG, PNG, and PDF files are allowed.';
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $finfo ? (string) finfo_file($finfo, $tmpName) : '';
            if ($finfo) {
                finfo_close($finfo);
            }

            $allowedMimeTypes = [
                'image/jpeg',
                'image/png',
                'application/pdf',
            ];

            if ($mimeType !== '' && !in_array($mimeType, $allowedMimeTypes, true)) {
                $errors[] = 'Invalid file MIME type for ID proof.';
            }

            if (empty($errors)) {
                $uploadDir = __DIR__ . '/../uploads';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                    $errors[] = 'Unable to create upload directory.';
                } else {
                    $safeName = 'id_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                    $uploadTargetPath = $uploadDir . '/' . $safeName;
                    $storedRelativePath = 'uploads/' . $safeName;
                }
            }
        }
    }

    if (!empty($errors)) {
        json_response(422, false, 'Validation failed.', ['errors' => $errors]);
    }

    if (email_or_roll_exists($conn, $email, $rollNumber)) {
        json_response(409, false, 'Email or roll number already exists.');
    }

    if (!move_uploaded_file($_FILES['idProof']['tmp_name'], $uploadTargetPath)) {
        json_response(500, false, 'Failed to store uploaded ID proof.');
    }

    $subjectsCsv = implode(',', array_map(static function ($subject): string {
        return trim((string) $subject);
    }, $subjects));

    $stmt = $conn->prepare('INSERT INTO student_registrations (
        first_name,
        middle_name,
        last_name,
        email,
        contact_number,
        gender,
        dob,
        roll_number,
        course_program,
        current_semester_year,
        previous_cgpa,
        examination_name,
        subjects,
        exam_language,
        id_proof_path,
        declaration_accepted
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

    if (!$stmt) {
        if ($uploadTargetPath !== '' && is_file($uploadTargetPath)) {
            @unlink($uploadTargetPath);
        }
        json_response(500, false, 'Failed to prepare registration query: ' . mysqli_error($conn));
    }

    $middleNameOrNull = $middleName !== '' ? $middleName : null;
    $cgpaOrNull = $cgpa;

    $stmt->bind_param(
        'ssssssssssdssssi',
        $firstName,
        $middleNameOrNull,
        $lastName,
        $email,
        $contact,
        $gender,
        $dob,
        $rollNumber,
        $courseProgram,
        $semesterYear,
        $cgpaOrNull,
        $examName,
        $subjectsCsv,
        $examLanguage,
        $storedRelativePath,
        $declaration
    );

    if (!$stmt->execute()) {
        if ($uploadTargetPath !== '' && is_file($uploadTargetPath)) {
            @unlink($uploadTargetPath);
        }

        if ((int) $stmt->errno === 1062) {
            json_response(409, false, 'Email or roll number already exists.');
        }

        json_response(500, false, 'Registration failed: ' . mysqli_error($conn));
    }

    json_response(201, true, 'Registration completed successfully.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Examination System - Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: radial-gradient(circle at top left, #e0f2fe, #f1f5f9 45%, #eef2ff 100%);
            min-height: 100vh;
        }

        .step-pane {
            display: none;
            opacity: 0;
            transform: translateY(8px);
        }

        .step-pane.active {
            display: block;
            animation: fadeInStep 0.35s ease forwards;
        }

        @keyframes fadeInStep {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="font-sans p-4 sm:p-6">
    <main class="mx-auto w-full max-w-4xl">
        <section class="rounded-2xl bg-white/95 shadow-xl shadow-slate-200/60 backdrop-blur-sm border border-slate-200">
            <div class="px-5 pt-6 pb-4 sm:px-8 sm:pt-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-800">Student Registration</h1>
                <p class="mt-1 text-sm sm:text-base text-slate-500">Online Examination System</p>
            </div>

            <div class="px-5 sm:px-8 pb-6">
                <div class="grid grid-cols-3 gap-2 sm:gap-4" id="progressTracker">
                    <div class="step-indicator flex flex-col items-center text-center" data-step="1">
                        <div class="step-dot h-9 w-9 rounded-full border-2 border-slate-300 bg-white text-slate-600 flex items-center justify-center font-semibold text-sm">1</div>
                        <p class="mt-2 text-xs sm:text-sm text-slate-600">Personal</p>
                    </div>
                    <div class="step-indicator flex flex-col items-center text-center" data-step="2">
                        <div class="step-dot h-9 w-9 rounded-full border-2 border-slate-300 bg-white text-slate-600 flex items-center justify-center font-semibold text-sm">2</div>
                        <p class="mt-2 text-xs sm:text-sm text-slate-600">Academic</p>
                    </div>
                    <div class="step-indicator flex flex-col items-center text-center" data-step="3">
                        <div class="step-dot h-9 w-9 rounded-full border-2 border-slate-300 bg-white text-slate-600 flex items-center justify-center font-semibold text-sm">3</div>
                        <p class="mt-2 text-xs sm:text-sm text-slate-600">Exam</p>
                    </div>
                </div>
            </div>

            <form id="registrationForm" class="px-5 pb-8 sm:px-8" method="POST" enctype="multipart/form-data" novalidate>
                <div id="globalError" class="hidden mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
                <div id="globalSuccess" class="hidden mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"></div>

                <section class="step-pane active" data-step="1">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Step 1: Personal Details</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="firstName" class="block text-sm font-medium text-slate-700 mb-1">First Name</label>
                            <input id="firstName" name="firstName" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                        </div>
                        <div>
                            <label for="middleName" class="block text-sm font-medium text-slate-700 mb-1">Middle Name</label>
                            <input id="middleName" name="middleName" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                        </div>
                        <div>
                            <label for="lastName" class="block text-sm font-medium text-slate-700 mb-1">Last Name</label>
                            <input id="lastName" name="lastName" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
                            <input id="email" name="email" type="email" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                        </div>
                        <div>
                            <label for="contact" class="block text-sm font-medium text-slate-700 mb-1">Contact Number</label>
                            <input id="contact" name="contact" type="tel" required pattern="^[0-9+\-()\s]{7,15}$" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none" placeholder="e.g. +91 9876543210">
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="gender" class="block text-sm font-medium text-slate-700 mb-1">Gender</label>
                            <select id="gender" name="gender" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="dob" class="block text-sm font-medium text-slate-700 mb-1">Date of Birth</label>
                            <input id="dob" name="dob" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="button" class="next-btn rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition">Next -></button>
                    </div>
                </section>

                <section class="step-pane" data-step="2">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Step 2: Academic Details</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="rollNumber" class="block text-sm font-medium text-slate-700 mb-1">Roll Number / Enrollment ID</label>
                            <input id="rollNumber" name="rollNumber" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                        </div>
                        <div>
                            <label for="courseProgram" class="block text-sm font-medium text-slate-700 mb-1">Course / Program</label>
                            <select id="courseProgram" name="courseProgram" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                                <option value="">Select Course</option>
                                <option value="BCA">BCA</option>
                                <option value="BBA">BBA</option>
                                <option value="BCOM">BCOM</option>
                                <option value="MCA">MCA</option>
                                <option value="B.Tech">B.Tech</option>
                                <option value="MBA">MBA</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="semesterYear" class="block text-sm font-medium text-slate-700 mb-1">Current Semester / Year</label>
                            <select id="semesterYear" name="semesterYear" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                                <option value="">Select</option>
                                <option>Semester 1</option>
                                <option>Semester 2</option>
                                <option>Semester 3</option>
                                <option>Semester 4</option>
                                <option>Semester 5</option>
                                <option>Semester 6</option>
                                <option>Year 1</option>
                                <option>Year 2</option>
                                <option>Year 3</option>
                                <option>Year 4</option>
                            </select>
                        </div>
                        <div>
                            <label for="cgpa" class="block text-sm font-medium text-slate-700 mb-1">Previous CGPA (Optional)</label>
                            <input id="cgpa" name="cgpa" type="number" min="0" max="10" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none" placeholder="e.g. 8.25">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <button type="button" class="back-btn rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"><- Back</button>
                        <button type="button" class="next-btn rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition">Next -></button>
                    </div>
                </section>

                <section class="step-pane" data-step="3">
                    <h2 class="text-lg font-semibold text-slate-800 mb-4">Step 3: Examination Specifics</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="examName" class="block text-sm font-medium text-slate-700 mb-1">Select Examination Name</label>
                            <select id="examName" name="examName" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                                <option value="">Select Examination</option>
                                <option value="Mid Semester Exam">Mid Semester Exam</option>
                                <option value="End Semester Exam">End Semester Exam</option>
                                <option value="Competitive Mock Test">Competitive Mock Test</option>
                                <option value="Entrance Practice Test">Entrance Practice Test</option>
                            </select>
                        </div>
                        <div>
                            <label for="idProof" class="block text-sm font-medium text-slate-700 mb-1">Upload ID Proof</label>
                            <input id="idProof" name="idProof" type="file" required accept=".pdf,.jpg,.jpeg,.png" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-blue-700 hover:file:bg-blue-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="block text-sm font-medium text-slate-700 mb-2">Subjects (Select one or more)</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 rounded-lg border border-slate-200 p-3 bg-slate-50">
                            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="subjects[]" value="Mathematics" class="h-4 w-4">Mathematics</label>
                            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="subjects[]" value="Physics" class="h-4 w-4">Physics</label>
                            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="subjects[]" value="Chemistry" class="h-4 w-4">Chemistry</label>
                            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="subjects[]" value="Computer Science" class="h-4 w-4">Computer Science</label>
                            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="subjects[]" value="English" class="h-4 w-4">English</label>
                            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="subjects[]" value="Economics" class="h-4 w-4">Economics</label>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="block text-sm font-medium text-slate-700 mb-2">Preferred Exam Language</p>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="radio" name="examLanguage" value="English" required class="h-4 w-4">English</label>
                            <label class="flex items-center gap-2 text-sm text-slate-700"><input type="radio" name="examLanguage" value="Hindi" required class="h-4 w-4">Hindi</label>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="flex items-start gap-2 text-sm text-slate-700">
                            <input id="declaration" name="declaration" value="1" type="checkbox" required class="mt-1 h-4 w-4">
                            <span>I agree that all provided information is correct and can be used for exam registration.</span>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <button type="button" class="back-btn rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"><- Back</button>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition">Submit Registration</button>
                    </div>
                </section>
            </form>
        </section>
    </main>

    <script>
        (function () {
            const form = document.getElementById('registrationForm');
            const panes = Array.from(document.querySelectorAll('.step-pane'));
            const stepIndicators = Array.from(document.querySelectorAll('.step-indicator'));
            const globalError = document.getElementById('globalError');
            const globalSuccess = document.getElementById('globalSuccess');
            let currentStep = 1;

            const stepFields = {
                1: ['firstName', 'lastName', 'email', 'contact', 'gender', 'dob'],
                2: ['rollNumber', 'courseProgram', 'semesterYear'],
                3: ['examName', 'idProof', 'declaration']
            };

            function clearMessages() {
                globalError.classList.add('hidden');
                globalSuccess.classList.add('hidden');
                globalError.textContent = '';
                globalSuccess.textContent = '';
            }

            function updateTracker() {
                stepIndicators.forEach((indicator, index) => {
                    const step = index + 1;
                    const dot = indicator.querySelector('.step-dot');
                    const label = indicator.querySelector('p');

                    dot.className = 'step-dot h-9 w-9 rounded-full border-2 flex items-center justify-center font-semibold text-sm';
                    label.className = 'mt-2 text-xs sm:text-sm';

                    if (step < currentStep) {
                        dot.classList.add('border-emerald-600', 'bg-emerald-600', 'text-white');
                        dot.textContent = 'OK';
                        label.classList.add('text-emerald-700', 'font-semibold');
                    } else if (step === currentStep) {
                        dot.classList.add('border-blue-600', 'bg-blue-600', 'text-white');
                        dot.textContent = String(step);
                        label.classList.add('text-blue-700', 'font-semibold');
                    } else {
                        dot.classList.add('border-slate-300', 'bg-white', 'text-slate-600');
                        dot.textContent = String(step);
                        label.classList.add('text-slate-600');
                    }
                });
            }

            function showStep(step) {
                panes.forEach((pane) => {
                    pane.classList.remove('active');
                    if (Number(pane.dataset.step) === step) {
                        pane.classList.add('active');
                    }
                });

                currentStep = step;
                clearMessages();
                updateTracker();
            }

            function validateStep(step) {
                const fieldIds = stepFields[step] || [];
                for (const id of fieldIds) {
                    const field = document.getElementById(id);
                    if (!field.checkValidity()) {
                        field.reportValidity();
                        return false;
                    }
                }

                if (step === 3) {
                    const subjectChecks = form.querySelectorAll('input[name="subjects[]"]:checked');
                    if (subjectChecks.length === 0) {
                        globalError.textContent = 'Please select at least one subject before submitting.';
                        globalError.classList.remove('hidden');
                        return false;
                    }

                    const langSelection = form.querySelector('input[name="examLanguage"]:checked');
                    if (!langSelection) {
                        globalError.textContent = 'Please choose your preferred exam language.';
                        globalError.classList.remove('hidden');
                        return false;
                    }
                }

                return true;
            }

            form.addEventListener('click', (event) => {
                const nextBtn = event.target.closest('.next-btn');
                const backBtn = event.target.closest('.back-btn');

                if (nextBtn) {
                    if (validateStep(currentStep)) {
                        showStep(Math.min(currentStep + 1, panes.length));
                    }
                }

                if (backBtn) {
                    showStep(Math.max(currentStep - 1, 1));
                }
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearMessages();

                if (!validateStep(3)) {
                    return;
                }

                const formData = new FormData(form);

                try {
                    const response = await fetch('register.php', {
                        method: 'POST',
                        body: formData,
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        const errorText = Array.isArray(data.errors) && data.errors.length
                            ? data.errors.join(' ')
                            : (data.message || 'Registration failed.');
                        globalError.textContent = errorText;
                        globalError.classList.remove('hidden');
                        return;
                    }

                    globalSuccess.textContent = data.message || 'Registration completed successfully.';
                    globalSuccess.classList.remove('hidden');
                    form.reset();
                    showStep(1);
                } catch (err) {
                    globalError.textContent = 'Unable to submit form. Please try again.';
                    globalError.classList.remove('hidden');
                }
             });

            updateTracker();
        })();
    </script>
</body>
</html>
