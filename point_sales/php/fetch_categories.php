<?php
header('Content-Type: application/json');

// Database configuration
$host = 'localhost';
$dbname = 'billing_system';
$user = 'root';
$password = '';

// Create a connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

// Fetch unique categories from the database
$sql = "SELECT DISTINCT category FROM products ORDER BY category ASC";
$result = $conn->query($sql);

$categories = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            "name" => $row["category"]
            // Optionally add "icon" => "🍹" etc. if you have icons per category
        ];
    }
}

// Output as JSON
echo json_encode($categories);

// Close the connection
$conn->close();
?>