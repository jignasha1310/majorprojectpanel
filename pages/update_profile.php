<?php


require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student-profile.php');
    exit;
}

$studentId = (int) $_SESSION['student_id'];
$csrfToken = (string) ($_POST['csrf_token'] ?? '');
$sessionToken = (string) ($_SESSION['csrf_profile_token'] ?? '');

if ($csrfToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    $_SESSION['profile_error'] = 'Security validation failed. Please try again.';
    header('Location: student-profile.php');
    exit;
}

$firstName = trim((string) ($_POST['first_name'] ?? ''));
$lastName = trim((string) ($_POST['last_name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));

if ($firstName === '' || strlen($firstName) > 80 || !preg_match('/^[A-Za-z][A-Za-z\s\'-]{0,79}$/', $firstName)) {
    $_SESSION['profile_error'] = 'Please enter a valid first name.';
    header('Location: student-profile.php');
    exit;
}

if ($lastName === '' || strlen($lastName) > 80 || !preg_match('/^[A-Za-z][A-Za-z\s\'-]{0,79}$/', $lastName)) {
    $_SESSION['profile_error'] = 'Please enter a valid last name.';
    header('Location: student-profile.php');
    exit;
}

if ($phone !== '' && !preg_match('/^[0-9+\-()\s]{7,20}$/', $phone)) {
    $_SESSION['profile_error'] = 'Please enter a valid phone number.';
    header('Location: student-profile.php');
    exit;
}

$currentStmt = $conn->prepare('SELECT id, profile_image FROM students WHERE id = ? LIMIT 1');
if (!$currentStmt) {
    $_SESSION['profile_error'] = 'Unable to load profile. Please try again.';
    header('Location: student-profile.php');
    exit;
}

$currentStmt->bind_param('i', $studentId);
$currentStmt->execute();
$current = $currentStmt->get_result()->fetch_assoc();
$currentStmt->close();

if (!$current) {
    $_SESSION['profile_error'] = 'Invalid session. Please login again.';
    header('Location: logout.php');
    exit;
}

$newImagePath = (string) ($current['profile_image'] ?? '');
$uploadedNewImage = false;

if (isset($_FILES['profile_image']) && (int) ($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $fileError = (int) ($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($fileError !== UPLOAD_ERR_OK) {
        $_SESSION['profile_error'] = 'Profile image upload failed.';
        header('Location: student-profile.php');
        exit;
    }

    $tmpPath = (string) ($_FILES['profile_image']['tmp_name'] ?? '');
    $fileSize = (int) ($_FILES['profile_image']['size'] ?? 0);

    if ($fileSize <= 0 || $fileSize > (2 * 1024 * 1024)) {
        $_SESSION['profile_error'] = 'Image size must be 2MB or less.';
        header('Location: student-profile.php');
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = (string) $finfo->file($tmpPath);
    $imageInfo = @getimagesize($tmpPath);

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];
    if (!isset($allowedMime[$mimeType]) || $imageInfo === false) {
        $_SESSION['profile_error'] = 'Only JPG, JPEG, and PNG images are allowed.';
        header('Location: student-profile.php');
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/profile';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        $_SESSION['profile_error'] = 'Unable to prepare upload directory.';
        header('Location: student-profile.php');
        exit;
    }

    $extension = $allowedMime[$mimeType];
    $newFileName = 'user_' . $studentId . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . '/' . $newFileName;

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        $_SESSION['profile_error'] = 'Failed to save profile image.';
        header('Location: student-profile.php');
        exit;
    }

    $newImagePath = 'uploads/profile/' . $newFileName;
    $uploadedNewImage = true;
}

$fullName = trim($firstName . ' ' . $lastName);
$phoneValue = $phone !== '' ? $phone : null;
$imageValue = $newImagePath !== '' ? $newImagePath : null;

$update = $conn->prepare('UPDATE students SET name = ?, first_name = ?, last_name = ?, phone = ?, profile_image = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
if (!$update) {
    if ($uploadedNewImage) {
        @unlink(__DIR__ . '/../' . $newImagePath);
    }
    $_SESSION['profile_error'] = 'Unable to update profile now.';
    header('Location: student-profile.php');
    exit;
}

$update->bind_param('sssssi', $fullName, $firstName, $lastName, $phoneValue, $imageValue, $studentId);
$ok = $update->execute();
$update->close();

if (!$ok) {
    if ($uploadedNewImage) {
        @unlink(__DIR__ . '/../' . $newImagePath);
    }
    $_SESSION['profile_error'] = 'Profile update failed. Please try again.';
    header('Location: student-profile.php');
    exit;
}

$_SESSION['student_name'] = $fullName;

$oldImage = trim((string) ($current['profile_image'] ?? ''));
if ($uploadedNewImage && $oldImage !== '' && $oldImage !== $newImagePath) {
    if (preg_match('/^uploads\/profile\/[A-Za-z0-9._-]+$/', $oldImage) && basename($oldImage) !== 'default.png') {
        $oldAbsolute = realpath(__DIR__ . '/../' . $oldImage);
        $baseAbsolute = realpath(__DIR__ . '/../uploads/profile');
        if ($oldAbsolute && $baseAbsolute && strpos($oldAbsolute, $baseAbsolute) === 0 && is_file($oldAbsolute)) {
            @unlink($oldAbsolute);
        }
    }
}

$_SESSION['profile_success'] = 'Profile updated successfully.';
header('Location: student-profile.php');
exit;
?>
