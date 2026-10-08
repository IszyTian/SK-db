
<?php
// Read variables injected by Render at runtime
$host = getenv('mysql-1b2839ab-daisybaraka-0b08.b.aivencloud.com');
$port = getenv('23245');
$dbname = getenv('defaultdb');
$username = getenv('avnadmin');
$password = getenv('AVNS_sxGs59zbIjrHUKlp0J3');

// Validate that environment variables are loaded to prevent fallback to localhost
if (!$host || !$dbname || !$username || !$password) {
    die("Database configuration variables are missing in the Render Environment settings.");
}

try {
    // Aiven mandates secure SSL connections
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => true, // Enforces SSL network communication
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Prints clean debug tracking if connection drops
    die("Database connection failed: " . $e->getMessage());
}
?>
