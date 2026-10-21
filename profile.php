<?php
session_start();
require 'db.php'; // Pulls the clean Aiven MySQL PDO configuration

// Enforce authentication wall
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); 
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Customer';

// Pull recent order records bound to this authenticated user context
$orders = [];
try {
    $stmt = $pdo->prepare("SELECT id, delivery_date, payment_method, total_amount, fulfillment_method FROM orders WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    // Graceful error isolation so the whole page doesn't crash if orders table structure differs
    $error_note = "Could not load order tracking metrics.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sukuma Fresh - My Profile Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Embedded companion styles to isolate dashboard layout clean injection blocks */
        .dashboard-layout { max-width: 900px; margin: 40px auto; padding: 0 20px; font-family: Arial, sans-serif; }
        .welcome-card { background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: center; margin-bottom: 30px; }
        .action-flex { display: flex; gap: 15px; justify-content: center; margin-top: 20px; }
        .btn-green { text-decoration: none; background: #2e7d32; color: white; padding: 10px 20px; border-radius: 4px; font-weight: bold; transition: background 0.2s; }
        .btn-green:hover { background: #1b5e20; }
        .btn-gray { text-decoration: none; background: #edf2f7; color: #4a5568; padding: 10px 20px; border-radius: 4px; font-weight: bold; transition: background 0.2s; }
        .btn-gray:hover { background: #cbd5e0; }
        
        .history-card { background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .history-card h3 { color: #2e7d32; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-top: 0; }
        
        .history-table { width: 100%; border-collapse: collapse; margin-top: 15px; text-align: left; }
        .history-table th { background: #f8fafc; padding: 12px; font-weight: 600; color: #4a5568; border-bottom: 2px solid #e2e8f0; }
        .history-table td { padding: 12px; border-bottom: 1px solid #edf2f7; color: #333; font-size: 15px; }
        
        .status-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; background: #e2e8f0; color: #4a5568; }
        .empty-state { text-align: center; color: #718096; padding: 30px 0; font-style: italic; }
    </style>
</head>
<body>

    <header>
        <div class="logo">🥬 Sukuma Fresh</div>
        <nav id="nav-menu">
            <!-- Navigation items load dynamically via script1.js -->
        </nav>
    </header>

    <main class="dashboard-layout">
        <!-- Section A: Welcoming Credentials Dashboard Banner -->
        <div class="welcome-card">
            <h2>Welcome Back, <?php echo htmlspecialchars($user_name); ?>!</h2>
            <p>Ready to look into fresh vegetable distributions today?</p>
            
            <div class="action-flex">
                <a href="catalogue.html" class="btn-green">🛒 Open Catalog</a>
                <a href="user-profile.php" class="btn-gray">⚙️ Edit Profile Settings</a>
            </div>
        </div>

        <!-- Section B: Dynamic Order History Management Log -->
        <div class="history-card">
            <h3>📋 Your Recent Purchase Tracking Orders</h3>
            
            <?php if (!empty($error_note)): ?>
                <p style="color: #e53e3e;"><?php echo htmlspecialchars($error_note); ?></p>
            <?php elseif (empty($orders)): ?>
                <div class="empty-state">
                    <p>You haven't placed any orders yet. When you buy groceries, your invoice history will populate here safely!</p>
                </div>
            <?php else: ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Fulfillment Mode</th>
                            <th>Delivery Target Date</th>
                            <th>Payment Option</th>
                            <th>Total Cost</th>
                            <th>Receipt Actions</th> <!-- Add this column -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($order['id']); ?></strong></td>
                                <td>
                                    <span class="status-badge">
                                        <?php echo htmlspecialchars($order['fulfillment_method'] ?? 'deliver'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($order['delivery_date'] ?? 'Processing...'); ?></td>
                                <td><?php echo htmlspecialchars(strtoupper($order['payment_method'])); ?></td>
                                <td><span style="font-weight: bold; color: #2e7d32;">Ksh <?php echo number_format($order['total_amount'], 2); ?></span></td>
    <!-- Dynamic Receipt Access Links Injection Blocks -->
                               <td>
                                 <a href="receipt.php?id=<?php echo $order['id']; ?>" class="btn-gray" style="padding: 4px 8px; font-size: 13px; border-radius: 4px; margin-right: 5px;">🔍 View</a>
                                <a href="receipt.php?id=<?php echo $order['id']; ?>&download=1" class="btn-green" style="padding: 4px 8px; font-size: 13px; border-radius: 4px;">📥 Save</a>
                            </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Sukuma Fresh. All Rights Reserved.</p>
    </footer>

    <script src="script1.js"></script>
</body>
</html>
