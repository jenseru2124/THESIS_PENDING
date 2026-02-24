<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();
header('Content-Type: application/json; charset=utf-8');


$servername = "localhost";
$db_user = "root";
$db_pass = "";
$database = "billing_system";

$response = array('success' => false, 'message' => '');

try {
    // Check session
    if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get input
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('No data received');
    }

    // Parse JSON
    $data = json_decode($input, true);
    if ($data === null) {
        throw new Exception('Invalid JSON');
    }

    // Validate fields
    if (!isset($data['items']) || !is_array($data['items']) || count($data['items']) == 0) {
        throw new Exception('Items array is empty');
    }
    if (!isset($data['total']) || !is_numeric($data['total'])) {
        throw new Exception('Invalid total');
    }
    if (!isset($data['payment_method'])) {
        throw new Exception('Payment method required');
    }
    if (!isset($data['delivery_address'])) {
        throw new Exception('Delivery address required');
    }
    if (!isset($data['phone_number'])) {
        throw new Exception('Phone number required');
    }

    // Connect to database
    $conn = new mysqli($servername, $db_user, $db_pass, $database);
    if ($conn->connect_error) {
        throw new Exception('Connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // Get and prepare values
    $user_id = intval($_SESSION['user_id']);
    $username = $_SESSION['username'];
    $items_json = json_encode($data['items']);
    $total = floatval($data['total']);
    $payment_method = strtolower(trim($data['payment_method']));
    $delivery_address = trim($data['delivery_address']);
    $phone_number = trim($data['phone_number']);
    $status = 'pending';
    $delivery_status = 'pending';

    // Validate payment method
    if (!in_array($payment_method, ['gcash', 'cod'])) {
        throw new Exception('Invalid payment method');
    }

    // Prepare statement with 9 parameters
    $sql = "INSERT INTO po_list (
        user_id,
        username,
        order_items,
        total_amount,
        order_time,
        status,
        payment_method,
        delivery_address,
        phone_number
    ) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare error: ' . $conn->error);
    }

    // CORRECT bind_param for 9 parameters: i, s, s, d, s, s, s, s, s
    $bind_result = $stmt->bind_param(
        'issdssss',
        $user_id,
        $username,
        $items_json,
        $total,
        $status,
        $payment_method,
        $delivery_address,
        $phone_number
    );

    if (!$bind_result) {
        throw new Exception('Bind error: ' . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception('Execute error: ' . $stmt->error);
    }

    $order_id = $stmt->insert_id;
    $stmt->close();
    
    // Delete cart if it exists
    $delete_stmt = $conn->prepare("DELETE FROM po_list WHERE user_id = ? AND status = 'cart'");
    if ($delete_stmt) {
        $delete_stmt->bind_param('i', $user_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
    
    $conn->close();

    // Success response
    $response['success'] = true;
    $response['message'] = 'Order placed successfully!';
    $response['order_id'] = $order_id;
    $response['payment_method'] = $payment_method;

} catch (Exception $e) {
    http_response_code(400);
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Place order error: ' . $e->getMessage());
}

ob_end_clean();
echo json_encode($response);
exit;
?>