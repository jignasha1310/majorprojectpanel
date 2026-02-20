<?php
require_once __DIR__ . '/bootstrap.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
