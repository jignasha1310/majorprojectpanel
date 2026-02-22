<?php
declare(strict_types=1);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'exampro_db';

// Connect first without selecting DB, then ensure DB exists.
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.',
    ]);
    exit;
}

$conn->query("CREATE DATABASE IF NOT EXISTS `$database`");
if (!$conn->select_db($database)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unable to select database.',
    ]);
    exit;
}

$conn->set_charset('utf8mb4');
