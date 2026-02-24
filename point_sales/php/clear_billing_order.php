<?php
header('Content-Type: application/json');
session_start();

try {
    // Unset the billing order session variables
    if (isset($_SESSION['billing_order_id'])) {
        unset($_SESSION['billing_order_id']);
    }
    
    if (isset($_SESSION['billing_order_data'])) {
        unset($_SESSION['billing_order_data']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Billing order session cleared'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>