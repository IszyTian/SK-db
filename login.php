<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require 'db.php'; 

// Start session tracking for logged-in users
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all layout form fields.']);
        exit;
    }

    try {
        // Query the record based on the provided email
        $stmt = $pdo->prepare("SELECT id, full_name, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verify user existence and decrypt password safely 
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];

            echo json_encode([
                'success' => true, 
                'redirect' => 'profile.php', // Updated target pointer
                'message' => 'Welcome back, ' . $user['full_name'] . '!'
            ]);
        } else {
            // Give a generic error message for security reasons
            echo json_encode(['success' => false, 'message' => 'Invalid email or password credentials.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
