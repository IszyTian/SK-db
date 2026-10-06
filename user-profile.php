<?php
// 1. Initialise core application security checks
session_start();
require 'db.php'; // Inherit explicit local connection structures

// Enforce authentication context walls
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

// 2. Process form adjustments sent via post streams
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Action A: Update generic address and demographic tracking fields
    if ($action == 'update_profile') {
        $phone = trim($_POST['phone_number']);
        $address = trim($_POST['default_address']);
        $payment = trim($_POST['default_payment_method']);

        try {
            $stmt = $pdo->prepare("UPDATE users SET phone_number = ?, default_address = ?, default_payment_method = ? WHERE id = ?");
            $stmt->execute([$phone, $address, $payment, $user_id]);
            $success_message = "Profile configurations saved successfully!";
        } catch (PDOException $e) {
            $error_message = "Profile update failed: " . $e->getMessage();
        }
    }

    // Action B: Process password migration securely
    if ($action == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "All password fields are required parameters.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New password confirmation does not match.";
        } else {
            try {
                // Fetch the authenticated user's current password cryptographic hash blueprint
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();

                if ($user && password_verify($current_password, $user['password_hash'])) {
                    // Re-hash new passwords safely prior to writing to long-term storage
                    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                    
                    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $updateStmt->execute([$new_hash, $user_id]);
                    $success_message = "Password modified safely! Use your new credentials on next sign-in.";
                } else {
                    $error_message = "The current password you provided is invalid.";
                }
            } catch (PDOException $e) {
                $error_message = "Database verification error: " . $e->getMessage();
            }
        }
    }
}

// 3. Always pull the latest operational record sets for clean interface generation
try {
    $stmt = $pdo->prepare("SELECT full_name, email, phone_number, default_address, default_payment_method FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
} catch (PDOException $e) {
    die("Fatal engine configuration breakdown: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sukuma Fresh - Profile Management Settings</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f8; margin: 0; padding: 20px; color: #333; }
        .dashboard-container { max-width: 750px; margin: 30px auto; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); padding: 35px; }
        .header-section { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 30px; }
        h1 { color: #2e7d32; margin: 0; font-size: 26px; }
        .nav-btn { text-decoration: none; color: #4a5568; font-weight: 600; padding: 8px 16px; border-radius: 6px; background: #edf2f7; transition: all 0.2s; }
        .nav-btn:hover { background: #cbd5e0; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 25px; font-weight: 500; font-size: 14px; }
        .alert-success { background-color: #c6f6d5; color: #22543d; border-left: 5px solid #38a169; }
        .alert-danger { background-color: #fed7d7; color: #742a2a; border-left: 5px solid #e53e3e; }
        .section-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 25px; margin-bottom: 30px; }
        .section-card h2 { margin-top: 0; color: #1a202c; font-size: 18px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
        .form-row { margin-bottom: 18px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; color: #4a5568; font-size: 14px; }
        input[type="text"], input[type="email"], input[type="password"], select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; background-color: #fff; font-size: 14px; transition: border 0.15s; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #38a169; box-shadow: 0 0 0 3px rgba(56,161,105,0.15); }
        input[disabled] { background-color: #e2e8f0; cursor: not-allowed; }
        textarea { resize: vertical; height: 75px; }
        .submit-btn { background-color: #2e7d32; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: background 0.2s; }
        .submit-btn:hover { background-color: #1b5e20; }
        .danger-btn { background-color: #e53e3e; }
        .danger-btn:hover { background-color: #9b2c2c; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="header-section">
        <h1>Account Dashboard Settings</h1>
        <div>
            <a href="profile.php" class="nav-btn">📋 View Orders</a>
            <a href="logout.php" class="nav-btn" style="color: #e53e3e;">Sign Out</a>
        </div>
    </div>

    <!-- Feedback banner loops -->
    <?php if(!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if(!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- CARD 1: Core demographic metadata tracking profiles -->
    <div class="section-card">
        <h2>Delivery & Contact Blueprint Settings</h2>
        <form method="POST" action="user-profile.php">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="form-row">
                <label>Full Name</label>
                <input type="text" value="<?php echo htmlspecialchars($profile['full_name']); ?>" disabled>
            </div>
            
            <div class="form-row">
                <label>Registered Email Address</label>
                <input type="email" value="<?php echo htmlspecialchars($profile['email']); ?>" disabled>
            </div>

            <div class="form-row">
                <label for="phone_number">Primary Mobile Contact (For Delivery Coordination / M-Pesa)</label>
                <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($profile['phone_number'] ?? ''); ?>" placeholder="e.g. 0712345678">
            </div>

            <div class="form-row">
                <label for="default_address">Default Physical Delivery Address / Apartment / Estate</label>
                <textarea id="default_address" name="default_address" placeholder="e.g. Kilimani Broadview Apartments, Tower B, House 3A, Nairobi"><?php echo htmlspecialchars($profile['default_address'] ?? ''); ?></textarea>
            </div>

            <div class="form-row">
                <label for="default_payment_method">Preferred Saved Payment Strategy</label>
                <select id="default_payment_method" name="default_payment_method">
                    <option value="mpesa" <?php echo ($profile['default_payment_method'] == 'mpesa') ? 'selected' : ''; ?>>M-Pesa Express</option>
                    <option value="cod" <?php echo ($profile['default_payment_method'] == 'cod') ? 'selected' : ''; ?>>Cash / Lipa na M-Pesa on Delivery</option>
                </select>
            </div>

            <button type="submit" class="submit-btn">Save Profile Customisations</button>
        </form>
    </div>

    <!-- CARD 2: Security boundary migrations -->
    <div class="section-card">
        <h2>Security Strategy - Change Access Password</h2>
        <form method="POST" action="user-profile.php">
            <input type="hidden" name="action" value="change_password">
            
            <div class="form-row">
                <label for="current_password">Current Active Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-row">
                <label for="new_password">New Cryptographic Replacement Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
            </div>

            <div class="form-row">
                <label for="confirm_password">Verify Replacement String Choice</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="submit-btn danger-btn">Execute Security Credential Reset</button>
        </form>
    </div>
</div>

</body>
</html>
