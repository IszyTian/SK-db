<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); // Boot unauthenticated requests
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sukuma Fresh - My Profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <div class="logo">🥬 Sukuma Fresh</div>
        <nav id="nav-menu">
            <!-- Navigation will load dynamically via JS -->
        </nav>
    </header>

    <section class="page-section">
        <div class="form-container" style="max-width: 600px; text-align: center;">
            <h2>Welcome to Your Dashboard!</h2>
            <div style="margin: 20px 0; font-size: 18px;">
                <p><strong>Account Holder:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
            </div>
            <p>Ready to shop for fresh vegetables?</p>
            <a href="catalogue.html" class="btn" style="display: inline-block; text-decoration: none;">Browse Catalogue</a>
        </div>
    </section>

    <footer>
        <p>&copy; 2026 Sukuma Fresh. All Rights Reserved.</p>
    </footer>

    <script src="script1.js"></script>
</body>
</html>
