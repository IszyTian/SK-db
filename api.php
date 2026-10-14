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
            'image' => $p['image_url'], // Maps database image_url column to JS product.image
            'isSale' => (bool)$p['is_sale'],
            'salePrice' => $p['sale_price'] !== null ? (float)$p['sale_price'] : null
        ];
    }
    echo json_encode($formatted);
    exit;
}


// 2. Handle customer checkouts securely
if ($action == 'place_order') {
    // CRITICAL SECURITY CHECK: Enforce logged-in authentication context
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized. You must log in to checkout.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['check_auth_only']) && $data['check_auth_only'] === true) {
        echo json_encode(['success' => true, 'message' => 'Authorized session validated.']);
        exit;
    }

    if(!empty($data['cart']) && !empty($data['address'])) {
        try {
            $pdo->beginTransaction();
            
            // FIXED: Added delivery_date to the column listing and bound the current system date below
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, delivery_address, payment_method, total_amount, delivery_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'], 
                $data['address'], 
                $data['payment_method'], 
                $data['total'],
                date('Y-m-d') // Automatically generates the current date (e.g., 2026-10-14)
            ]);
            $orderId = $pdo->lastInsertId();

            // Insert matching individual item columns
            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
            foreach($data['cart'] as $item) {
                $itemStmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Order logged inside database!']);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Database failure: ' . $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing cart items or delivery address detail configurations.']);
        exit;
    }
}

?>

