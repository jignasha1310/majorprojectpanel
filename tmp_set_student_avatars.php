<?php
require __DIR__ . '/config/db.php';

$avatarPriya = 'uploads/profile/priya_profile.jpg';
$avatarRahul = 'uploads/profile/rahul_profile.jpg';

$stmt1 = $conn->prepare('UPDATE students SET profile_image = ? WHERE email = ?');
$email1 = 'priya@student.com';
$stmt1->bind_param('ss', $avatarPriya, $email1);
$stmt1->execute();
$stmt1->close();

$stmt2 = $conn->prepare('UPDATE students SET profile_image = ? WHERE email = ?');
$email2 = 'rahul@student.com';
$stmt2->bind_param('ss', $avatarRahul, $email2);
$stmt2->execute();
$stmt2->close();

$res = $conn->query("SELECT name,email,profile_image FROM students WHERE email IN ('priya@student.com','rahul@student.com') ORDER BY name ASC");
while ($row = $res->fetch_assoc()) {
    echo $row['name'] . ' | ' . $row['profile_image'] . "\n";
}
?>
