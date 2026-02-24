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

// Get the category from the AJAX request (if provided)
$category = isset($_GET["category"]) ? $conn->real_escape_string($_GET["category"]) : "";

// Fetch items based on the category (if specified)
if (!empty($category)) {
    $sql = "SELECT * FROM products WHERE category = '$category' ORDER BY name ASC";
} else {
    $sql = "SELECT * FROM products ORDER BY name ASC";
}

$result = $conn->query($sql);

$items = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            "name" => $row["name"],
            "price" => $row["price"],
            // Provide a fallback placeholder image if image_path is empty
            "image" => !empty($row["image_path"]) ? $row["image_path"] : "https://placehold.co/60x60?text=" . urlencode(substr($row["name"], 0, 1))
        ];
    }
}

echo json_encode($items);

// Close the connection
$conn->close();
?>