<?php
// 1. Session must start before ANY other file loading or output happens
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Suppress direct HTML error dumps to prevent parsing crashes in JS
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// 3. Enforce JSON headers immediately
header('Content-Type: application/json; charset=UTF-8');

try {
    require 'db.php'; 

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Look for the email field submitted by the form layout
        $email = isset($_POST['email']) ? trim($_POST['email']) : (isset($_POST['username']) ? trim($_POST['username']) : '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all input fields.']);
            exit;
        }

        // FIXED: Only query using the email column since 'username' does not exist in your schema
        $stmt = $pdo->prepare("SELECT id, full_name, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verify user existence and decrypt password safely 
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];

            echo json_encode([
                'success' => true, 
                'redirect' => 'profile.php', 
                'message' => 'Welcome back, ' . $user['full_name'] . '!'
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password credentials.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }
} catch (Exception $e) {
    // Gracefully catch database or file loading exceptions as JSON payloads instead of HTML strings
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
    exit;
}
?>
