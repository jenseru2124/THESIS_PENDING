<?php
// Database configuration

$host = 'localhost';
$dbname = 'billing_system';
$user = 'root';
$password = '';

// Create a connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Database connection failed: " . $conn->connect_error]));
}

// Retrieve JSON data from the request
$data = json_decode(file_get_contents("php://input"), true);

// Ensure valid data is received
if (!$data || !isset($data['items']) || !isset($data['total']) || !isset($data['cash'])) {
    echo json_encode(["success" => false, "error" => "Invalid input data"]);
    exit;
}

// Extract data
$items = $data['items'];
$total = $data['total'];
$cash = $data['cash'];
$change = $cash - $total;
$order_type = isset($data['order_type']) ? $data['order_type'] : "Dine-In";

// Start a transaction
$conn->begin_transaction();

try {
    // Insert the sale into the sales table
    $stmt = $conn->prepare("INSERT INTO sales (total, cash, `change`, order_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ddds", $total, $cash, $change, $order_type);
    $stmt->execute();
    $sale_id = $stmt->insert_id; // Get the last inserted sale ID
    $stmt->close();

    // Insert each item into the sales_items table
    $stmt = $conn->prepare("INSERT INTO sales_items (sale_id, item_name, price, quantity, total) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $item_name = $item['name'];
        $price = $item['price'];
        $quantity = $item['quantity'];
        $item_total = $price * $quantity;
        $stmt->bind_param("isddd", $sale_id, $item_name, $price, $quantity, $item_total);
        $stmt->execute();
    }
    $stmt->close();

    // Commit the transaction
    $conn->commit();

    // Send success response
    echo json_encode(["success" => true, "sale_id" => $sale_id]);
} catch (Exception $e) {
    // Rollback the transaction in case of an error
    $conn->rollback();
    echo json_encode(["success" => false, "error" => "Error saving data: " . $e->getMessage()]);
} finally {
    // Close the database connection
    $conn->close();
}
?>