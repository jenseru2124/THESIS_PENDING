<?php
session_start();
header('Content-Type: application/json');

// --- CONFIGURE DATABASE CONNECTION --- //

$host = 'localhost';
$dbname = 'billing_system';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// --- CONNECT TO DATABASE --- //
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// --- CHECK LOGIN STATUS --- //
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// --- HANDLE ACTIONS --- //
$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

if ($action === 'get') {
    // --- FETCH USER PROFILE --- //
    $stmt = $pdo->prepare("SELECT user_id, name, username, email, address, cellphone_number, avatar, role, created_at, updated_at FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user) {
        if (empty($user['avatar'])) $user['avatar'] = 'avatar/default_avatar.png';
        echo json_encode($user);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
    exit;
}

if ($action === 'update') {
    // --- UPDATE PROFILE INFO AND AVATAR --- //
    $name    = $_POST['name'] ?? '';
    $email   = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $cell    = $_POST['cellphone_number'] ?? '';
    $fields = ['name' => $name, 'email' => $email, 'address' => $address, 'cellphone_number' => $cell];
    $set = [];
    $params = [];
    foreach ($fields as $k => $v) { $set[] = "$k=?"; $params[] = $v; }

    // --- HANDLE AVATAR UPLOAD --- //
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $avatar_dir = 'avatar/';
            if (!is_dir($avatar_dir)) mkdir($avatar_dir, 0777, true);
            $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $target = $avatar_dir . $filename;
            move_uploaded_file($_FILES['avatar']['tmp_name'], $target);

            // Remove old avatar if not default
            $stmt = $pdo->prepare("SELECT avatar FROM users WHERE user_id=?");
            $stmt->execute([$user_id]);
            $old = $stmt->fetchColumn();
            if ($old && $old !== 'avatar/default_avatar.png' && file_exists($old)) unlink($old);

            $set[] = "avatar=?";
            $params[] = $target;
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid avatar file type.']);
            exit;
        }
    }
    $params[] = $user_id;
    $sql = "UPDATE users SET " . implode(',', $set) . ", updated_at=NOW() WHERE user_id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Return updated user
    $stmt = $pdo->prepare("SELECT user_id, name, username, email, address, cellphone_number, avatar, role, created_at, updated_at FROM users WHERE user_id=?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user) {
        if (empty($user['avatar'])) $user['avatar'] = 'avatar/default_avatar.png';
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Update failed']);
    }
    exit;
}

if ($action === 'delete_avatar') {
    // --- DELETE AVATAR --- //
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE user_id=?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user && $user['avatar'] && $user['avatar'] !== 'avatar/default_avatar.png') {
        if (file_exists($user['avatar'])) unlink($user['avatar']);
    }
    $stmt = $pdo->prepare("UPDATE users SET avatar=NULL WHERE user_id=?");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true]);
    exit;
}

// --- INVALID ACTION --- //
http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
?>