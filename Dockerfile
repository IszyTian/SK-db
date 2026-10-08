<?php
// Read the master connection string injected automatically by Render
$url_str = getenv('DATABASE_URL') ?: '';

if (empty($url_str)) {
    header("Content-Type: text/plain");
    die("DOCKER_ENVIRONMENT_ERROR: DATABASE_URL variable is missing in Render environment settings.");
}

// Parse the Aiven URL components automatically
$db_config = parse_url($url_str);

$host = isset($db_config['host']) ? $db_config['host'] : '';
$port = isset($db_config['port']) ? $db_config['port'] : '3306';
$username = isset($db_config['user']) ? $db_config['user'] : '';
$password = isset($db_config['pass']) ? $db_config['pass'] : '';
$dbname = isset($db_config['path']) ? ltrim($db_config['path'], '/') : '';

// Clean up any extra URL queries from the database name string
if (($pos = strpos($dbname, '?')) !== false) {
    $dbname = substr($dbname, 0, $pos);
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    // Core SSL Configuration Block
    $options = [
        // CRITICAL FIX: Explicit path string pointing to Ubuntu/Debian root certificates
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt', 
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // Prevents container hostname verification failures
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    header("Content-Type: text/plain");
    die("AIVEN_CONNECTION_ERROR: " . $e->getMessage());
}
?>


