<?php
require_once 'config.php';
header('Content-Type: application/json');

// 1. Autentikasi App dari Header
$app_key = $_SERVER['HTTP_X_APP_KEY'] ?? '';
$app_secret = $_SERVER['HTTP_X_APP_SECRET'] ?? '';

if (!$app_key || !$app_secret) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing App Credentials']);
    exit;
}

// 2. Cek DB Billing: App terdaftar & tidak suspend?
$stmt = $pdo->prepare("SELECT * FROM apps WHERE app_key = ? AND app_secret = ?");
$stmt->execute([$app_key, $app_secret]);
$app = $stmt->fetch();

if (!$app) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid App Credentials']);
    exit;
}

if ($app['app_status'] === 'suspended') {
    http_response_code(403);
    echo json_encode(['error' => 'App is suspended. Contact administrator.']);
    exit;
}

$stmt = $pdo->query("SELECT plan_code, name, price, duration_months FROM plans ORDER BY price ASC");
$plans = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $plans]);
?>
