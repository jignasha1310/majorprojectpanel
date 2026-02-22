<?php
require __DIR__ . '/config/db.php';

echo "DB_CONNECTED\n";
$res = $conn->query("SELECT id,name,email,COALESCE(profile_image,'') AS profile_image FROM students ORDER BY id ASC");
if (!$res) {
    echo "QUERY_FAIL: " . $conn->error . "\n";
    exit;
}
while ($row = $res->fetch_assoc()) {
    echo $row['id'] . ' | ' . $row['name'] . ' | ' . $row['email'] . ' | ' . $row['profile_image'] . "\n";
}
?>
