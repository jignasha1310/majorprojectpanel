<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'exampro_db';

// First connect without database to create it if needed
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS `$database`");
$conn->select_db($database);
$conn->set_charset("utf8mb4");

// Auto-create tables if they don't exist
$tables_check = $conn->query("SHOW TABLES LIKE 'students'");
if ($tables_check->num_rows === 0) {
    // Import the SQL schema
    $sql_file = __DIR__ . '/../database.sql';
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        // Remove the CREATE DATABASE and USE lines since we already selected it
        $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
        $sql = preg_replace('/USE.*?;/i', '', $sql);
        $conn->multi_query($sql);
        // Flush all results
        while ($conn->next_result()) {
            if ($res = $conn->store_result()) {
                $res->free();
            }
        }
    }
}
?>
