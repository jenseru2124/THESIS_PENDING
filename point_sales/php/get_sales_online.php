<?php
// Database connection
$servername = 'localhost';
$dbname = 'billing_system';
$username = 'root';
$password = '';

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch sales data with corresponding sales item details
$sql = "
    SELECT 
        sales.sale_id,
        sales.total AS sale_total,
        sales.cash,
        sales.change AS sale_change,
        sales.order_type,
        sales.created_at,
        sales_item.item_name,
        sales_item.price,
        sales_item.quantity,
        sales_item.total AS item_total
    FROM
        sales
    LEFT JOIN
        sales_item ON sales.sale_id = sales_item.sale_id
    ORDER BY
        sales.created_at ASC, sales.sale_id ASC
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $salesData = [];

    // Organize data into a nested structure
    while ($row = $result->fetch_assoc()) {
        $saleId = $row['sale_id'];

        // If sale_id is not added yet, initialize it
        if (!isset($salesData[$saleId])) {
            $salesData[$saleId] = [
                'sale_id' => $saleId,
                'total' => $row['sale_total'],
                'cash' => $row['cash'],
                'change' => $row['sale_change'],
                'order_type' => $row['order_type'],
                'created_at' => $row['created_at'],
                'items' => []
            ];
        }

        // Add sales item details to the corresponding sale
        $salesData[$saleId]['items'][] = [
            'item_name' => $row['item_name'],
            'price' => $row['price'],
            'quantity' => $row['quantity'],
            'total' => $row['item_total']
        ];
    }

    // Reset array keys for JSON encoding
    $salesData = array_values($salesData);

    // Return data in JSON format
    header('Content-Type: application/json');
    echo json_encode($salesData, JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'No sales data found.'
    ]);
}

// Close the database connection
$conn->close();
?>