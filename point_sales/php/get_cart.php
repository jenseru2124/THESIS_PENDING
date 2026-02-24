<?php
session_start();
header('Content-Type: application/json');

// DATABASE CONNECTION (InfinityFree)
$conn = new mysqli(
    "localhost",
    "root",
    "",
    "billing_system"
);

if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

if (!isset($_SESSION['username'])) {
    echo json_encode(["error" => "User not logged in"]);
    exit;
}

$username = $conn->real_escape_string($_SESSION['username']);

$sql = "SELECT order_items 
        FROM po_list 
        WHERE username='$username' 
        AND status='cart' 
        LIMIT 1";

$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    $items = json_decode($row['order_items'], true);
    echo json_encode($items ?: []);
} else {
    echo json_encode([]);
}

$conn->close();
?>