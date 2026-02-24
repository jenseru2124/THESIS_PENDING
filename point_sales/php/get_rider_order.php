<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$db_user = "root";
$db_pass = "";
$database = "billing_system";

try {
    $conn = new mysqli($servername, $db_user, $db_pass, $database);
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    // Get all orders assigned to riders
    $sql = "
        SELECT 
            order_id, 
            user_id,
            username, 
            order_items, 
            total_amount, 
            delivery_address, 
            phone_number, 
            payment_method,
            delivery_status, 
            order_time,
            assigned_to_rider,
            delivered_at
        FROM po_list 
        WHERE delivery_status IN ('assigned', 'delivered')
        ORDER BY order_time DESC
    ";

    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // Decode JSON order items
        if (is_string($row['order_items'])) {
            $decoded = json_decode($row['order_items'], true);
            $row['order_items'] = is_array($decoded) ? $decoded : [];
        } else {
            $row['order_items'] = [];
        }
        
        $orders[] = $row;
    }

    $conn->close();
    echo json_encode($orders);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>