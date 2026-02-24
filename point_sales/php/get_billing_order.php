<?php
header('Content-Type: application/json');
session_start();

try {
    // Check if order data exists in session
    if (!isset($_SESSION['billing_order_id']) || !isset($_SESSION['billing_order_data'])) {
        // This is normal - just return success: false without error code
        http_response_code(200);  // ← Changed from 400 to 200
        echo json_encode([
            'success' => false,
            'error' => 'No order found in session. Please transfer an order first.'
        ]);
        exit;
    }

    $order_data = $_SESSION['billing_order_data'];
    $order_id = $_SESSION['billing_order_id'];

    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'order_data' => $order_data
    ]);

} catch (Exception $e) {
    http_response_code(200);  // ← Changed from 400 to 200
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>