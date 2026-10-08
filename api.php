<?php
// Start session and error reporting before ANY output or headers
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set clean, non-conflicting headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
// ... the rest of your file remains the same


$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. Fetch products for catalog or flash sales
if ($action == 'get_products') {
    $stmt = $pdo->query("SELECT * FROM products");
    $products = $stmt->fetchAll();
    
    // Format to match your exact JavaScript property configurations
    $formatted = [];
    foreach($products as $p) {
        $formatted[] = [
            'id' => (int)$p['id'],
            'name' => $p['name'],
            'category' => $p['category'],
            'price' => (float)$p['price'],
            'image' => $p['image_url'],
            'isSale' => (bool)$p['is_sale'],
            'salePrice' => $p['sale_price'] !== null ? (float)$p['sale_price'] : null
        ];
    }
    echo json_encode($formatted);
}

// 2. Handle customer checkouts securely
if ($action == 'place_order') {

    // CRITICAL SECURITY CHECK: Terminate immediately if user session isn't found
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized. You must log in to checkout.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if(!empty($data['cart']) && !empty($data['address'])) {
        try {
            $pdo->beginTransaction();
            
            // Insert primary order document
            $stmt = $pdo->prepare("INSERT INTO orders (delivery_address, payment_method, total_amount) VALUES (?, ?, ?)");
            $stmt->execute([$data['address'], $data['payment_method'], $data['total']]);
            $orderId = $pdo->lastInsertId();
            
            // Insert primary order document with explicit user binding assignment
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, fulfillment_method, delivery_address, delivery_date, payment_method, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'], 
                $data['fulfillment'], 
                $data['address'], 
                $data['delivery_date'], 
                $data['payment_method'], 
                $data['total']
            ]);
            $orderId = $pdo->lastInsertId();

            // Insert mapped cart row entries
            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
            foreach($data['cart'] as $item) {
                $itemStmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Order logged inside database!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?>
