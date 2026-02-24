<?php
// Database configuration
$host = 'localhost';
$dbname = 'billing_system';
$user = 'root';
$password = '';

// Create a connection to the database
$conn = new mysqli($host, $user, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the request is a POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve form data
    $category = $conn->real_escape_string($_POST["category"]);
    $name = $conn->real_escape_string($_POST["name"]);
    $price = $conn->real_escape_string($_POST["price"]);

    // Handle the image upload
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true); // Create the directory if it doesn't exist
        }

        $imageName = basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . uniqid() . "_" . $imageName; // Unique filename to avoid conflicts

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            // Insert the product into the database
            $stmt = $conn->prepare("INSERT INTO products (category, name, price, image_path) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssds", $category, $name, $price, $targetFilePath);

            if ($stmt->execute()) {
                echo "Product inserted successfully!";
            } else {
                echo "Error inserting product: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "Error uploading the image.";
        }
    } else {
        echo "Please upload a valid image.";
    }
}

// Close the database connection
$conn->close();
?>