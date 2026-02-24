<?php
session_start();
header('Content-Type: application/json');


$servername = "localhost";
$db_user = "root";
$db_pass = "";
$database = "billing_system";

try {
    if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
        throw new Exception('Missing parameters');
    }

    $order_id = intval($_POST['order_id']);
    $status = htmlspecialchars($_POST['status']);

    // Validate status
    $validStatuses = ['assigned', 'delivered'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Invalid status');
    }

    $conn = new mysqli($servername, $db_user, $db_pass, $database);
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    // Update delivery_status
    if ($status === 'delivered') {
        // Set delivered timestamp
        $sql = "UPDATE po_list SET delivery_status = ?, delivered_at = NOW() WHERE order_id = ?";
    } else {
        // Just update status to assigned
        $sql = "UPDATE po_list SET delivery_status = ?, assigned_to_rider = NOW() WHERE order_id = ?";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("si", $status, $order_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update order: ' . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception('Order not found');
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully',
        'order_id' => $order_id,
        'status' => $status
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>