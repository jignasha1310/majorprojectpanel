<?php
require_once __DIR__ . '/includes/bootstrap.php';

unset($_SESSION['teacher_id'], $_SESSION['teacher_name'], $_SESSION['teacher_email']);
teacherFlash('success', 'You have been logged out successfully.');
header('Location: login.php');
exit;
