<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'billing_system';
$username = 'root';
$password = '';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = isset($_POST['step']) ? $_POST['step'] : '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($step === 'check_user') {
            $input = isset($_POST['username_or_email']) ? trim($_POST['username_or_email']) : '';
            if ($input === '') {
                echo json_encode(['error' => 'Please enter your username or email.']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$input, $input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['error' => 'User not found.']);
                exit;
            }

            echo json_encode(['success' => true]);
            exit;

        } elseif ($step === 'reset_password') {
            $input = isset($_POST['username_or_email']) ? trim($_POST['username_or_email']) : '';
            $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

            if ($input === '' || $new_password === '' || $confirm_password === '') {
                echo json_encode(['error' => 'Please fill out all fields.']);
                exit;
            }
            if ($new_password !== $confirm_password) {
                echo json_encode(['error' => 'Passwords do not match.']);
                exit;
            }
            if (strlen($new_password) < 6) {
                echo json_encode(['error' => 'Password must be at least 6 characters long.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$input, $input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['error' => 'User not found.']);
                exit;
            }

            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $update->execute([$hashed, $user['user_id']]);

            echo json_encode(['success' => 'Your password has been reset! You can now log in with your new password.']);
            exit;
        }

        echo json_encode(['error' => 'Invalid step.']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['error' => 'Invalid request.']);
}
?>