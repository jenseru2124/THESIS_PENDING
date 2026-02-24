<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "billing_system");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$username = $conn->real_escape_string($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Username and password required']);
    exit;
}

// Check if user exists and is a rider
$stmt = $conn->prepare("
    SELECT user_id, username, name, password_hash, is_rider 
    FROM users 
    WHERE username = ? AND is_rider = 1
    LIMIT 1
");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid rider credentials']);
    exit;
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid rider credentials']);
    exit;
}

// Set session
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['is_rider'] = 1;

echo json_encode(['success' => true]);
$stmt->close();
$conn->close();
?>