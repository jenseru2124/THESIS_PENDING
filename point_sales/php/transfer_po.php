<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "billing_system");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$po_id = intval($_POST['po_id'] ?? 0);
if ($po_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid PO ID']);
    exit;
}

// Get PO data
$result = $conn->query("SELECT * FROM po_list WHERE order_id = $po_id");
$po = $result->fetch_assoc();
if (!$po) {
    echo json_encode(['success' => false, 'error' => 'PO not found']);
    exit;
}

$items = json_decode($po['order_items'], true);

// Update status to 'assigned' instead of deleting
$update_query = "
    UPDATE po_list 
    SET delivery_status = 'assigned', assigned_to_rider = NOW()
    WHERE order_id = $po_id
";

if ($conn->query($update_query)) {
    echo json_encode([
        'success' => true,
        'message' => 'Order transferred to rider dashboard',
        'po' => [
            'order_id' => $po['order_id'],
            'username' => $po['username'],
            'order_time' => $po['order_time'],
            'payment_method' => $po['payment_method'],
            'total_amount' => $po['total_amount'],
            'delivery_address' => $po['delivery_address'],
            'phone_number' => $po['phone_number'],
            'order_items' => $items
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to transfer order']);
}

$conn->close();
?>