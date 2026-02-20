<?php
require_once __DIR__ . '/includes/bootstrap.php';

unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email']);
adminFlash('success', 'You have been logged out successfully.');
header('Location: login.php');
exit;
