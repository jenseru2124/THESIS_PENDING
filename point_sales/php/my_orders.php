<?php
session_start();
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "billing_system");
if ($conn->connect_error) {
    echo json_encode(['success'=>false, 'error'=>'DB connection failed']);
    exit;
}
if (!isset($_SESSION['username'])) {
    echo json_encode(['success'=>false, 'error'=>'Not logged in']);
    exit;
}
$username = $conn->real_escape_string($_SESSION['username']);
$method = $_SERVER['REQUEST_METHOD'];

// ADD: Move selected order to cart, delete the order
if ($method === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    // Get the order (must be an 'order')
    $result = $conn->query("SELECT order_items, reservation_time, total_amount FROM po_list WHERE order_id=$order_id AND username='$username' AND status='order' LIMIT 1");
    if (!$result || !$result->num_rows) {
        echo json_encode(['success'=>false, 'error'=>'Order not found']);
        exit;
    }
    $order = $result->fetch_assoc();
    // Delete any existing cart for this user
    $conn->query("DELETE FROM po_list WHERE username='$username' AND status='cart'");
    // Delete this order from 'order' list
    $conn->query("DELETE FROM po_list WHERE order_id=$order_id AND username='$username' AND status='order'");
    // Insert as new cart
    $stmt = $conn->prepare("INSERT INTO po_list (username, order_items, reservation_time, total_amount, status) VALUES (?, ?, ?, ?, 'cart')");
    $stmt->bind_param("sssd", $username, $order['order_items'], $order['reservation_time'], $order['total_amount']);
    $success = $stmt->execute();
    $stmt->close();
    echo json_encode(['success'=>!!$success]);
    $conn->close();
    exit;
}

// DELETE: Remove an order (not cart)
if (
    ($method === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['order_id'])) ||
    ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['order_id']))
) {
    $order_id = intval($_POST['order_id'] ?? $_GET['order_id']);
    $res = $conn->query("DELETE FROM po_list WHERE order_id=$order_id AND username='$username' AND status='order'");
    echo json_encode(['success'=>!!$res]);
    $conn->close();
    exit;
}

// LIST: Get all previous orders (not cart)
$result = $conn->query("SELECT * FROM po_list WHERE username = '$username' AND status='order' ORDER BY order_time DESC");
$orders = [];
while ($row = $result->fetch_assoc()) {
    $row['order_items'] = json_decode($row['order_items'], true);
    $orders[] = $row;
}
echo json_encode($orders);
$conn->close();
?>