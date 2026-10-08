<?php
$host = getenv('dpg-db30moks728c73auli3g-a');
$db   = getenv('ine_x2vs');
$user = getenv('ine_x2vs_user');
$pass = getenv('P1PcfS6UfDnd38Y5yTigVCjFr0zdvRSR');
$port = getenv('5432') ?: '3306';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
