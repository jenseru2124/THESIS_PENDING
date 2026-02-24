<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "billing_system");
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

// Fetch all pending orders with rider info if assigned
$result = $conn->query("
    SELECT 
        po.order_id,
        po.user_id,
        po.username,
        po.order_items,
        po.total_amount,
        po.order_time,
        po.status,
        po.payment_method,
        po.delivery_address,
        po.phone_number,
        po.delivery_status,
        po.rider_id,
        po.assigned_to_rider,
        r.name as rider_name,
        r.phone as rider_phone,
        r.vehicle as rider_vehicle
    FROM po_list po
    LEFT JOIN riders r ON po.rider_id = r.rider_id
    WHERE po.status = 'pending'
    ORDER BY po.order_time DESC
");

$orders = [];
while ($row = $result->fetch_assoc()) {
    $row['order_items'] = json_decode($row['order_items'], true) ?? [];
    $orders[] = $row;
}

echo json_encode($orders);
$conn->close();
?>