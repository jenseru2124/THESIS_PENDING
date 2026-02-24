<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "billing_system");
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $conn->real_escape_string($_SESSION['username'] ?? '');

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// LIST: Get all orders for logged-in user
if ($action === 'list') {
    $stmt = $conn->prepare("
        SELECT order_id, username, order_items, total_amount, order_time, 
               status, payment_method, delivery_address, phone_number 
        FROM po_list 
        WHERE user_id = ? AND status IN ('pending', 'completed') 
        ORDER BY order_time DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $row['order_items'] = json_decode($row['order_items'], true) ?? [];
        $orders[] = $row;
    }
    
    echo json_encode($orders);
    $stmt->close();
    exit;
}

// REORDER: Add order items back to cart
if ($action === 'reorder' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    
    // Get the order
    $stmt = $conn->prepare("
        SELECT order_items, delivery_address, phone_number, payment_method 
        FROM po_list 
        WHERE order_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    $order = $result->fetch_assoc();
    
    // Delete existing cart
    $delete_stmt = $conn->prepare("DELETE FROM po_list WHERE user_id = ? AND status = 'cart'");
    $delete_stmt->bind_param("i", $user_id);
    $delete_stmt->execute();
    
    // Create new cart entry
    $order_items = $order['order_items'];
    $delivery_address = $order['delivery_address'];
    $phone_number = $order['phone_number'];
    $payment_method = $order['payment_method'];
    $status = 'cart';
    
    $insert_stmt = $conn->prepare("
        INSERT INTO po_list (user_id, username, order_items, total_amount, status, 
                            delivery_address, phone_number, payment_method, order_time)
        VALUES (?, ?, ?, 0, ?, ?, ?, ?, NOW())
    ");
    
    // Calculate total from items
    $items = json_decode($order_items, true);
    $total = 0;
    foreach ($items as $item) {
        $total += ($item['price'] * $item['quantity']);
    }
    
    $insert_stmt->bind_param("issssss", $user_id, $username, $order_items, $status, 
                            $delivery_address, $phone_number, $payment_method);
    
    if ($insert_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create cart']);
    }
    
    $insert_stmt->close();
    $stmt->close();
    $conn->close();
    exit;
}

// DELETE: Remove an order
if ($action === 'delete' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    
    $stmt = $conn->prepare("
        DELETE FROM po_list 
        WHERE order_id = ? AND user_id = ? AND status IN ('pending', 'completed')
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
    $success = $stmt->execute();
    
    echo json_encode(['success' => !!$success]);
    $stmt->close();
    $conn->close();
    exit;
}

echo json_encode(['error' => 'Invalid action']);
$conn->close();
?>