<?php
// app_billing/config.php
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

$host = $_ENV['DB_BILLING_HOST'] ?? "127.0.0.1";
$db   = $_ENV['DB_BILLING_NAME'] ?? "midtrans_billing_db";
$user = $_ENV['DB_BILLING_USER'] ?? "root";
$pass = $_ENV['DB_BILLING_PASS'] ?? "root";

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// Midtrans Keys
define('MT_SERVER_KEY', $_ENV['MT_SERVER_KEY'] ?? ''); 
define('MT_CLIENT_KEY', $_ENV['MT_CLIENT_KEY'] ?? ''); 
define('MT_IS_PROD', ($_ENV['MT_IS_PROD'] ?? 'false') === 'true');
define('MT_BASE_URL', MT_IS_PROD ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com');
?>