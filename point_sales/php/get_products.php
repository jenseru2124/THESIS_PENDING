<?php
// Database connection
$servername = "localhost";
$db_user = "root";
$db_pass = "";
$database = "billing_system";

$conn = new mysqli($servername, $db_user, $db_pass, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to fetch product details
$sql = "
    SELECT 
        product_id,
        category,
        name,
        price,
        image_path,
        created_at
    FROM 
        products
    ORDER BY 
        created_at ASC
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $products = [];

    // Fetch all products into an array
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'product_id' => $row['product_id'],
            'category' => $row['category'],
            'name' => $row['name'],
            'price' => (float)$row['price'], // Convert to float for consistency
            'image' => $row['image_path'],
            'created_at' => $row['created_at'],
        ];
    }

    // Return data in JSON format
    header('Content-Type: application/json');
    echo json_encode($products, JSON_PRETTY_PRINT);
} else {
    // No products found
    echo json_encode([
        'status' => 'error',
        'message' => 'No products found.'
    ]);
}

// Close the database connection
$conn->close();
?>