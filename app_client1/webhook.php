<?php
// app_client/webhook.php
require_once 'config.php';

// 1. Ambil Header dari App Billing
$incoming_key = $_SERVER['HTTP_X_APP_KEY'] ?? '';
$incoming_secret = $_SERVER['HTTP_X_APP_SECRET'] ?? '';

// 2. Ambil Key & Secret dari Database Lokal (app_settings)
$stmt = $pdo->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('billing_app_key', 'billing_app_secret')");
$keys = [];
while ($row = $stmt->fetch()) {
    $keys[$row['setting_key']] = $row['setting_value'];
}
$local_key = $keys['billing_app_key'] ?? '';
$local_secret = $keys['billing_app_secret'] ?? '';

// 3. Validasi: Cocokkan Key & Secret dari Billing dengan DB Lokal
if (!$incoming_key || !$incoming_secret || $incoming_key !== $local_key || $incoming_secret !== $local_secret) {
    http_response_code(403);
    exit('Forbidden: Invalid App Credentials');
}

// 4. Jika cocok, proses data webhook
$input = json_decode(file_get_contents('php://input'), true);

function upsert_setting($pdo, $key, $value) {
    $stmt = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) 
                           ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

upsert_setting($pdo, 'sub_status', $input['sub_status'] ?? 'inactive');
upsert_setting($pdo, 'plan_name', $input['plan_name'] ?? '');
upsert_setting($pdo, 'sub_activation_date', $input['activation_date'] ?? '');
upsert_setting($pdo, 'sub_expiry_date', $input['expiry_date'] ?? '');

http_response_code(200);
echo "OK";
?>