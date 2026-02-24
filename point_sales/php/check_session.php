<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$db_user = "root";
$db_pass = "";
$database = "billing_system";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['logged_in' => false]);
    exit;
}

try {
    $conn = new mysqli($servername, $db_user, $db_pass, $database);
    if ($conn->connect_error) {
        echo json_encode(['logged_in' => true, 'verified' => false]);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT user_id, username, name, role, is_rider FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['logged_in' => false]);
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    echo json_encode([
        'logged_in' => true,
        'user_id' => $user['user_id'],
        'username' => $user['username'],
        'name' => $user['name'],
        'role' => $user['role'],
        'is_rider' => $user['is_rider']
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        'logged_in' => true,
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'name' => $_SESSION['name'],
        'role' => $_SESSION['role'],
        'is_rider' => $_SESSION['is_rider']
    ]);
    exit;
}
?>