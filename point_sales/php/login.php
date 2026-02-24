<?php
session_start();

$servername = "localhost";
$db_user = "root";
$db_pass = "";
$database = "billing_system";

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception('Invalid request');
    }

    $username = htmlspecialchars($_POST['username'] ?? '');
    $password = htmlspecialchars($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        throw new Exception('Username and password required');
    }

    $conn = new mysqli($servername, $db_user, $db_pass, $database);
    if ($conn->connect_error) {
        throw new Exception('Connection failed');
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        throw new Exception('Invalid username or password');
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user['password_hash'])) {
        throw new Exception('Invalid username or password');
    }

    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['address'] = $user['address'];
    $_SESSION['cellphone_number'] = $user['cellphone_number'];
    $_SESSION['is_rider'] = $user['is_rider'];

    $stmt->close();
    $conn->close();

    // Redirect based on role
    if ($user['role'] === 'admin') {
        header("Location: billing.html");
    } elseif ($user['role'] === 'rider' || $user['is_rider'] == 1) {
        header("Location: rider_dashboard.html");
    } else {
        header("Location: online_ordering.html");
    }
    exit();

} catch (Exception $e) {
    header("Location: index.html?error=" . urlencode($e->getMessage()));
    exit();
}
?>