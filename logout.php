<?php
session_start();

// Unset all session keys
$_SESSION = array();

// Destroy the tracking cookie on the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the server session file
session_destroy();

// Send back an explicit JSON confirmation
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Logged out successfully!']);
exit;
?>
<?php
session_start();
session_unset();
session_destroy();
header("Location: login.html");
exit;
?>

