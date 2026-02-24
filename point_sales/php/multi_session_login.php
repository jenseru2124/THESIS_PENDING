<?php
session_start();

$servername = "localhost";
$db_user = "root";
$db_pass = "";
$database = "billing_system";

$response = array('success' => false, 'message' => '');

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception('Invalid request method');
    }

    $username = htmlspecialchars($_POST['username'] ?? '');
    $password = htmlspecialchars($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        throw new Exception('Username and password required');
    }

    // Connect to database
    $conn = new mysqli($servername, $db_user, $db_pass, $database);
    if ($conn->connect_error) {
        throw new Exception('Connection failed');
    }

    // Get user from database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        throw new Exception('Invalid username or password');
    }

    $user = $result->fetch_assoc();

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        throw new Exception('Invalid username or password');
    }

    // Create a completely new session
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['address'] = $user['address'];
    $_SESSION['cellphone_number'] = $user['cellphone_number'];
    $_SESSION['login_time'] = time();
    $_SESSION['session_id'] = session_id();

    $stmt->close();
    $conn->close();

    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['user'] = array(
        'user_id' => $user['user_id'],
        'username' => $user['username'],
        'name' => $user['name'],
        'role' => $user['role']
    );
    $response['session_id'] = session_id();
    $response['redirect'] = $user['role'] === 'admin' ? 'billing.html' : 'online_ordering.html';

} catch (Exception $e) {
    http_response_code(400);
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>