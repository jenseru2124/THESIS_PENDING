<?php

$host = 'localhost';
$dbname = 'billing_system';
$username = 'root';
$password = '';

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

// Handle image upload separately
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'upload_image') {
    if (isset($_FILES['image']) && isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        $image = $_FILES['image'];
        $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['error' => 'File type not allowed']);
            exit;
        }
        if ($image['size'] > 2 * 1024 * 1024) { // 2MB
            echo json_encode(['error' => 'File too large (max 2MB)']);
            exit;
        }
        $newname = uniqid() . "_" . preg_replace('/[^A-Za-z0-9_.-]/', '', $image['name']);
        $target = "uploads/" . $newname;
        if (!is_dir("uploads")) mkdir("uploads", 0777, true);
        if (move_uploaded_file($image['tmp_name'], $target)) {
            // Save path to DB
            $stmt = $pdo->prepare("UPDATE products SET image_path=? WHERE product_id=?");
            $stmt->execute([$target, $product_id]);
            echo json_encode(['success' => true, 'image_path' => $target]);
        } else {
            echo json_encode(['error' => 'Failed to move uploaded file']);
        }
    } else {
        echo json_encode(['error' => 'No image or product_id']);
    }
    exit;
}

// Handle update and delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['action']) && $data['action'] === 'delete' && isset($data['product_id'])) {
        // Delete product
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $result = $stmt->execute([$data['product_id']]);
        echo json_encode(['success' => $result]);
        exit;
    } elseif (isset($data['action']) && $data['action'] === 'update' && isset($data['product_id'])) {
        // Update product
        $stmt = $pdo->prepare("UPDATE products SET category=?, name=?, price=?, image_path=? WHERE product_id=?");
        $result = $stmt->execute([
            $data['category'],
            $data['name'],
            $data['price'],
            $data['image_path'],
            $data['product_id']
        ]);
        echo json_encode(['success' => $result]);
        exit;
    } else {
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }
} else {
    // GET: List products
    $stmt = $pdo->query("SELECT product_id, category, name, price, image_path, created_at FROM products ORDER BY product_id ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}
?>