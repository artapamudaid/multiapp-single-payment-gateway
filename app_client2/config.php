<?php
// app_client/config.php
session_start();

function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}
loadEnv(__DIR__ . '/../.env');

$host = $_ENV['DB_APP2_HOST'] ?? "127.0.0.1";
$db   = $_ENV['DB_APP2_NAME'] ?? "app2_db";
$user = $_ENV['DB_APP2_USER'] ?? "root";
$pass = $_ENV['DB_APP2_PASS'] ?? "root";

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

define('MT_CLIENT_KEY', $_ENV['MT_CLIENT_KEY'] ?? ''); 
define('BILLING_API_URL', $_ENV['BILLING_API_URL'] ?? '');
?>