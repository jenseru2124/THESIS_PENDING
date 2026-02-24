<?php
$host = 'localhost';
$dbname = 'billing_system';
$user = 'root';
$password = '';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Total sales per day
$sql_sales = "SELECT DATE(created_at) AS sale_date, SUM(total) AS total_sales
              FROM sales
              GROUP BY sale_date
              ORDER BY sale_date ASC";
$result_sales = $conn->query($sql_sales);

$sales_summary = [];
if ($result_sales && $result_sales->num_rows > 0) {
    while ($row = $result_sales->fetch_assoc()) {
        $sales_summary[] = [
            'sale_date' => $row['sale_date'],
            'total_sales' => (float) $row['total_sales']
        ];
    }
}

// 2. Sales per item per day
$sql_items = "SELECT
    si.item_name,
    DATE(s.created_at) AS sale_date,
    SUM(si.quantity) AS total_quantity,
    SUM(si.total) AS total_revenue
FROM sales_items si
JOIN sales s ON si.sale_id = s.sale_id
GROUP BY si.item_name, sale_date
ORDER BY sale_date ASC, si.item_name ASC";
$result_items = $conn->query($sql_items);

$item_summary = [];
if ($result_items && $result_items->num_rows > 0) {
    while ($row = $result_items->fetch_assoc()) {
        $item_summary[] = [
            'item_name' => $row['item_name'],
            'sale_date' => $row['sale_date'],
            'total_quantity' => (int) $row['total_quantity'],
            'total_revenue' => (float) $row['total_revenue']
        ];
    }
}

$response = [
    'sales_summary' => $sales_summary,
    'item_summary' => $item_summary,
];

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>