<?php
// Upload this file next to api.php / login.php on your server (same folder).
// JSON order history for the logged-in user, used by the Android app's "My Orders" screen.
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to see your orders.']);
    exit;
}

require 'db.php';

try {
    $stmt = $pdo->prepare(
        "SELECT id, delivery_date, payment_method, total_amount, fulfillment_method
         FROM orders WHERE user_id = ? ORDER BY id DESC"
    );
    $stmt->execute([$_SESSION['user_id']]);
    echo json_encode(['success' => true, 'orders' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Could not load orders.']);
}
