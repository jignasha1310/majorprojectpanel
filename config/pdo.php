<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdoDsn = 'mysql:host=localhost;dbname=exampro_db;charset=utf8mb4';
$pdoUser = 'root';
$pdoPassword = '';

$pdo = new PDO(
    $pdoDsn,
    $pdoUser,
    $pdoPassword,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

