<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$db_user = "root";
$db_pass = "";
$database = "billing_system";

// Connect to the database
$conn = new mysqli($servername, $db_user, $db_pass, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $name = $conn->real_escape_string($_POST['name']);
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $address = $conn->real_escape_string($_POST['address']);
    $cellphone_number = $conn->real_escape_string($_POST['cellphone_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = "user"; // Default role

    // Validate password and confirm password
    if ($password !== $confirm_password) {
        die("<script>alert('Passwords do not match. Please try again.'); window.history.back();</script>");
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert the data into the database
    $sql = "INSERT INTO users (name, username, email, password_hash, address, cellphone_number, role)
            VALUES ('$name', '$username', '$email', '$hashed_password', '$address', '$cellphone_number', '$role')";

    if ($conn->query($sql) === TRUE) {
        // Show success alert and redirect to login page
        echo "<script>
                alert('Registration successful! Redirecting to login page.');
                window.location.href = 'login.html';
              </script>";
    } else {
        // Show error alert
        echo "<script>
                alert('Error: " . $conn->error . "');
                window.history.back();
              </script>";
    }
}

// Close the database connection
$conn->close();
?>