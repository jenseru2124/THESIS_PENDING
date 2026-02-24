<?php
header('Content-Type: application/json');
session_start();


$servername = "localhost";
$db_user = "root";
$db_pass = "";
$database = "billing_system";

try {
    if (!isset($_POST['po_id'])) {
        throw new Exception('Order ID required');
    }

    $po_id = intval($_POST['po_id']);
    if ($po_id <= 0) {
        throw new Exception('Invalid PO ID');
    }

    $conn = new mysqli($servername, $db_user, $db_pass, $database);
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    // Get PO data - ONLY DELIVERED orders
    $stmt = $conn->prepare("
        SELECT 
            order_id, 
            username, 
            order_items, 
            total_amount, 
            delivery_address, 
            phone_number, 
            payment_method, 
            order_time,
            delivery_status
        FROM po_list 
        WHERE order_id = ? AND delivery_status = 'delivered'
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $po_id);
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Order #' . $po_id . ' not found or is not delivered');
    }

    $po = $result->fetch_assoc();
    
    // Decode items
    $items = json_decode($po['order_items'], true);
    if (!is_array($items)) {
        $items = [];
    }

    // Store in SESSION before deleting
    $_SESSION['billing_order_id'] = $po['order_id'];
    $_SESSION['billing_order_data'] = [
        'items' => $items,
        'total' => floatval($po['total_amount']),
        'cash' => 0,
        'order_type' => 'Delivery',
        'username' => $po['username'],
        'delivery_address' => $po['delivery_address'],
        'phone_number' => $po['phone_number'],
        'payment_method' => $po['payment_method'],
        'order_time' => $po['order_time']
    ];

    error_log("Session set for order: " . $po['order_id']);

    $stmt->close();
    $conn->close();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order transferred to billing (data stored in session)',
        'order_id' => $po['order_id'],
        'order_data' => $_SESSION['billing_order_data']
    ]);

} catch (Exception $e) {
    error_log("Transfer error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>