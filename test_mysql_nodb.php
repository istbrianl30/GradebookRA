<?php
$host = 'localhost';
$user = 'root';
$pass = '';

echo "Testing MySQL connection (no database)...<br>";
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
echo "Connected successfully!<br>";
echo "MySQL host info: " . $conn->host_info . "<br>";
$conn->close();
?>