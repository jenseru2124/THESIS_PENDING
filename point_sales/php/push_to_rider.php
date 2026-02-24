<?php
header('Content-Type: application/json');


$servername = "localhost";
$db_user = "root";
$db_pass = "";
$database = "billing_system";

try {
    if (!isset($_POST['order_id'])) {
        throw new Exception('Order ID required');
    }

    $order_id = intval($_POST['order_id']);

    $conn = new mysqli($servername, $db_user, $db_pass, $database);
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    // Get the order
    $stmt = $conn->prepare("
        SELECT order_id, username, order_items, total_amount, delivery_address, phone_number, payment_method, order_time
        FROM po_list 
        WHERE order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Order not found');
    }

    $po = $result->fetch_assoc();
    $items = json_decode($po['order_items'], true);

    // Update status to 'assigned' and set timestamp
    $update_stmt = $conn->prepare("
        UPDATE po_list 
        SET delivery_status = 'assigned', assigned_to_rider = NOW()
        WHERE order_id = ?
    ");
    $update_stmt->bind_param("i", $order_id);
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update order: ' . $update_stmt->error);
    }
    $update_stmt->close();

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Order pushed to rider',
        'order_id' => $po['order_id'],
        'order_data' => [
            'order_id' => $po['order_id'],
            'username' => $po['username'],
            'order_items' => $items,
            'total_amount' => $po['total_amount'],
            'delivery_address' => $po['delivery_address'],
            'phone_number' => $po['phone_number'],
            'payment_method' => $po['payment_method'],
            'order_time' => $po['order_time']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>