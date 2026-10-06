<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Your existing code continues below...

// 1. First, include the file that defines $pdo


// 2. Now you can safely use $pdo below

// ... rest of your code


header('Content-Type: application/json');
require 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and clean inputs from the form variables
    $fullName    = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $email       = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phoneNumber = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $password    = isset($_POST['password']) ? $_POST['password'] : '';

    // Quick structural validation
    if (empty($fullName) || empty($email) || empty($phoneNumber) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all layout form fields.']);
        exit;
    }

    try {
        // Check if email already exists in the database
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
            exit;
        }

        // Encrypt the password securely before saving
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert new customer record
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone_number, password_hash) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fullName, $email, $phoneNumber, $passwordHash]);

        echo json_encode(['success' => true, 'message' => 'Account created successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}else {
    // 5. If someone visits the page directly via GET request, show an error instead of a blank screen
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Please submit the registration form.']);
}
?>
