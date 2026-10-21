<?php
// 1. Initialize core session and error validation protocols
session_start();
require 'db.php'; // Pulls the clean Aiven MySQL PDO configuration

// Enforce authentication context wall
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); 
    exit;
}

// 2. Validate Order Parameter
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    die("Error: Invalid or missing Order ID parameter.");
}

try {
    // 3. Fetch Master Order Details alongside Customer metadata
    $orderStmt = $pdo->prepare("
        SELECT o.*, u.full_name, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $orderStmt->execute([$order_id]);
    $order = $orderStmt->fetch();

    if (!$order) {
        die("Error: Request tracking statement could not find Order #$order_id.");
    }

    // Security Check: Restrict access to order owner (or you can expand this for admins)
    if ($order['user_id'] != $_SESSION['user_id']) {
        die("Unauthorized Access: You do not have permissions to view this invoice receipt statement.");
    }

    // 4. Fetch Child Items bound to this order
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, p.name AS product_name 
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $itemsStmt->execute([$order_id]);
    $items = $itemsStmt->fetchAll();

} catch (PDOException $e) {
    die("Database engine query structural failure: " . $e->getMessage());
}

// 5. Check if download configuration header flag is active
if (isset($_GET['download']) && $_GET['download'] == '1') {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="Sukuma_Fresh_Receipt_#' . $order_id . '.html"');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sukuma Fresh Invoice - Order #<?php echo $order['id']; ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; background: #f4f7f6; margin: 0; padding: 20px; }
        .receipt-box { max-width: 700px; margin: 20px auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-top: 8px solid #2e7d32; }
        .no-print-zone { max-width: 700px; margin: 0 auto 15px auto; display: flex; justify-content: space-between; }
        .btn { text-decoration: none; padding: 10px 18px; border-radius: 4px; font-weight: bold; font-size: 14px; cursor: pointer; display: inline-block; }
        .btn-download { background: #2e7d32; color: #fff; border: none; }
        .btn-download:hover { background: #1b5e20; }
        .btn-back { background: #edf2f7; color: #4a5568; }
        .btn-back:hover { background: #cbd5e0; }
        .invoice-header { display: flex; justify-content: space-between; border-bottom: 2px solid #edf2f7; padding-bottom: 20px; margin-bottom: 20px; }
        .logo-area h2 { color: #2e7d32; margin: 0; font-size: 28px; }
        .meta-area { text-align: right; font-size: 14px; color: #718096; }
        .meta-area strong { color: #1a202c; font-size: 16px; }
        .details-grid { display: flex; justify-content: space-between; margin-bottom: 30px; gap: 20px; }
        .details-block { flex: 1; background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 14px; }
        .details-block h4 { margin: 0 0 8px 0; color: #2e7d32; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; text-align: left; }
        .receipt-table th { background: #f8fafc; padding: 12px; font-weight: 600; color: #4a5568; border-bottom: 2px solid #e2e8f0; }
        .receipt-table td { padding: 12px; border-bottom: 1px solid #edf2f7; font-size: 15px; }
        .totals-area { width: 40%; margin-left: auto; font-size: 15px; }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; }
        .grand-total { font-size: 18px; font-weight: bold; color: #2e7d32; border-top: 2px solid #e2e8f0; padding-top: 10px; margin-top: 6px; }
        .footer-note { text-align: center; color: #a0aec0; font-size: 12px; margin-top: 5px; border-top: 1px dashed #e2e8f0; padding-top: 20px; }
        
        /* Hide buttons completely when printed or downloaded as a local file document */
        @media print { .no-print-zone { display: none !important; } body { background: #fff; padding: 0; } .receipt-box { box-shadow: none; padding: 0; border: none; } }
    </style>
</head>
<body>

<!-- Only show action bar components inside active live server tracking layouts -->
<?php if (!isset($_GET['download'])): ?>
<div class="no-print-zone">
    <a href="profile.php" class="btn btn-back">⬅️ Back to Dashboard</a>
    <div>
        <button onclick="window.print();" class="btn btn-back" style="margin-right: 5px;">🖨️ Print Invoice</button>
        <a href="receipt.php?id=<?php echo $order['id']; ?>&download=1" class="btn btn-download">📥 Download Receipt File</a>
    </div>
</div>
<?php endif; ?>

<div class="receipt-box">
    <div class="invoice-header">
        <div class="logo-area">
            <h2>🥬 Sukuma Fresh</h2>
            <p style="margin: 4px 0 0 0; font-size: 13px; color: #718096;">Fresh Organic Vegetables Delivered</p>
        </div>
        <div class="meta-area">
            <strong>INVOICE RECEIPT</strong><br>
            Order Ref: #<?php echo htmlspecialchars($order['id']); ?><br>
            Issued: <?php echo htmlspecialchars($order['delivery_date'] ?? date('Y-m-d')); ?>
        </div>
    </div>

    <div class="details-grid">
        <div class="details-block">
            <h4>Billed To:</h4>
            <strong><?php echo htmlspecialchars($order['full_name']); ?></strong><br>
            Email: <?php echo htmlspecialchars($order['email']); ?><br>
            Account Status: Verified Customer Context
        </div>
        <div class="details-block">
            <h4>Logistics & Fulfillment:</h4>
            <strong>Method:</strong> <?php echo htmlspecialchars(ucfirst($order['fulfillment_method'] ?? 'deliver')); ?><br>
            <strong>Destination Address:</strong><br>
            <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
        </div>
        <div class="details-block">
            <h4>Transaction Mechanics:</h4>
            <strong>Payment Protocol:</strong> <?php echo strtoupper(htmlspecialchars($order['payment_method'])); ?><br>
            <strong>Status:</strong> Processed & Recorded In Database
        </div>
    </div>

    <table class="receipt-table">
        <thead>
            <tr>
                <th>Itemized Product Description</th>
                <th style="text-align: center;">Unit Price</th>
                <th style="text-align: center;">Quantity</th>
                <th style="text-align: right;">Line Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $calculated_subtotal = 0;
            foreach ($items as $item): 
                $line_total = $item['price_at_purchase'] * $item['quantity'];
                $calculated_subtotal += $line_total;
            ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                    <td style="text-align: center;">Ksh <?php echo number_format($item['price_at_purchase'], 2); ?></td>
                    <td style="text-align: center;">x <?php echo $item['quantity']; ?></td>
                    <td style="text-align: right; font-weight: 500;">Ksh <?php echo number_format($line_total, 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals-area">
        <div class="totals-row">
            <span>Items Subtotal:</span>
            <span>Ksh <?php echo number_format($calculated_subtotal, 2); ?></span>
        </div>
        <div class="totals-row">
            <?php 
            // Calculate dynamic surcharge logistics metrics automatically
            $surcharge = (float)$order['total_amount'] - $calculated_subtotal;
            ?>
            <span>Fulfillment Surcharge:</span>
            <span>Ksh <?php echo number_format(max(0, $surcharge), 2); ?></span>
        </div>
        <div class="totals-row grand-total">
            <span>Total Paid:</span>
            <span>Ksh <?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
    </div>

    <div class="footer-note">
        <p>Thank you for shopping organically with <strong>Sukuma Fresh</strong>!</p>
        <p>&copy; 2026 Sukuma Fresh Store distributions pipeline system.</p>
    </div>
</div>

</body>
</html>
