
<?php
// Read variables injected by Render at runtime
$host = getenv('mysql-1b2839ab-daisybaraka-0b08.b.aivencloud.com');
$port = getenv('23245');
$dbname = getenv('defaultdb');
$username = getenv('avnadmin');
$password = getenv('AVNS_sxGs59zbIjrHUKlp0J3');

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // CRITICAL: We print the exact message instead of masking it
    header("Content-Type: text/plain");
    echo "EXACT_ERROR: " . $e->getMessage();
    exit;
}
?>
