<?php
require __DIR__ . '/config/db.php';

$newName = 'Raju Parmar';
$email = 'teacher@exampro.com';

$stmt = $conn->prepare('UPDATE teachers SET name = ? WHERE email = ?');
$stmt->bind_param('ss', $newName, $email);
$stmt->execute();
$stmt->close();

$check = $conn->prepare('SELECT name, email FROM teachers WHERE email = ? LIMIT 1');
$check->bind_param('s', $email);
$check->execute();
$row = $check->get_result()->fetch_assoc();
$check->close();

if ($row) {
    echo $row['name'] . ' | ' . $row['email'] . PHP_EOL;
}
?>
