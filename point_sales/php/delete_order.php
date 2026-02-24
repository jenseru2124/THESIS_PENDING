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
    if ($order_id <= 0) {
        throw new Exception('Invalid Order ID');
    }

    $conn = new mysqli($servername, $db_user, $db_pass, $database);
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }

    // First check if order exists
    $check_stmt = $conn->prepare("SELECT order_id FROM po_list WHERE order_id = ?");
    if (!$check_stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $check_stmt->bind_param("i", $order_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Order #' . $order_id . ' not found');
    }
    
    $check_stmt->close();

    // Delete the order from po_list
    $stmt = $conn->prepare("DELETE FROM po_list WHERE order_id = ?");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $order_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete order: ' . $stmt->error);
    }

    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    if ($affected_rows === 0) {
        throw new Exception('Order could not be deleted');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Order #' . $order_id . ' deleted successfully',
        'order_id' => $order_id,
        'affected_rows' => $affected_rows
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>