<?php
session_start();
header('Content-Type: application/json');


$host = 'localhost';
$dbname = 'billing_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // DELETE USER
    if (isset($data['action']) && $data['action'] === 'delete' && isset($data['user_id'])) {
        $user_id = intval($data['user_id']);

        try {
            // Prevent deleting the last admin
            $stmt = $pdo->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['admin_count'] <= 1) {
                $stmt2 = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
                $stmt2->execute([$user_id]);
                $user = $stmt2->fetch(PDO::FETCH_ASSOC);

                if ($user && $user['role'] === 'admin') {
                    echo json_encode(['success' => false, 'error' => 'Cannot delete the last admin user']);
                    exit;
                }
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $success = $stmt->execute([$user_id]);
            echo json_encode(['success' => $success]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Error deleting user: ' . $e->getMessage()]);
        }
        exit;
    }

    // UPDATE USER
    elseif (isset($data['action']) && $data['action'] === 'update' && isset($data['user_id'])) {
        $user_id = intval($data['user_id']);
        $name = isset($data['name']) ? trim($data['name']) : '';
        $username = isset($data['username']) ? trim($data['username']) : '';
        $email = isset($data['email']) ? trim($data['email']) : '';
        $address = isset($data['address']) ? trim($data['address']) : '';
        $cellphone_number = isset($data['cellphone_number']) ? trim($data['cellphone_number']) : '';
        $role = isset($data['role']) ? trim($data['role']) : 'user';

        // Validate input
        if (empty($name) || empty($username) || empty($email)) {
            echo json_encode(['success' => false, 'error' => 'Name, username, and email are required']);
            exit;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email format']);
            exit;
        }

        // Validate role - must be one of the enum values
        if (!in_array($role, ['admin', 'user', 'rider'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid role. Must be admin, user, or rider']);
            exit;
        }

        try {
            // Check if email is already used by another user
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'error' => 'Email is already in use']);
                exit;
            }

            // Check if username is already used by another user
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'error' => 'Username is already in use']);
                exit;
            }

            // Set is_rider flag
            $is_rider = ($role === 'rider') ? 1 : 0;

            // Update user
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, 
                    username = ?, 
                    email = ?, 
                    address = ?, 
                    cellphone_number = ?, 
                    role = ?,
                    is_rider = ?,
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            
            $success = $stmt->execute([
                $name,
                $username,
                $email,
                $address,
                $cellphone_number,
                $role,
                $is_rider,
                $user_id
            ]);

            if ($success) {
                // If role changed to rider, create/update rider record
                if ($role === 'rider') {
                    try {
                        $checkRider = $pdo->prepare("SELECT rider_id FROM riders WHERE user_id = ?");
                        $checkRider->execute([$user_id]);
                        
                        if ($checkRider->rowCount() === 0) {
                            // Create new rider
                            $createRider = $pdo->prepare("
                                INSERT INTO riders (user_id, phone, status)
                                VALUES (?, ?, 'offline')
                            ");
                            $createRider->execute([$user_id, $cellphone_number]);
                        } else {
                            // Update existing rider's phone
                            $updateRider = $pdo->prepare("UPDATE riders SET phone = ? WHERE user_id = ?");
                            $updateRider->execute([$cellphone_number, $user_id]);
                        }
                    } catch (PDOException $e) {
                        error_log("Rider update error: " . $e->getMessage());
                    }
                }

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update user']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    } 
    
    else {
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
} 

// GET: List all users
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("
            SELECT 
                user_id, 
                name, 
                username, 
                email, 
                address, 
                cellphone_number, 
                COALESCE(role, 'user') as role, 
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created_at, 
                DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i') as updated_at 
            FROM users 
            ORDER BY created_at DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error fetching users: ' . $e->getMessage()]);
    }
    exit;
}

else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}
?>