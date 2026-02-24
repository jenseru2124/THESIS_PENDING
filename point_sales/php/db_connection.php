<?php
$servername = 'localhost';
$dbname = 'billing_system';
$username = 'root';
$password = '';

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
