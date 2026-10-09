<?php
// app_client/process.php
require_once 'config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'NEW';
$plan_id = $input['plan_id'] ?? '';
$card_token = $input['card_token'] ?? '';

// 1. AMBIL SECRET KEY DARI DB LOKAL (TIDAK ADA midtrans_sub_id lagi)
$stmt = $pdo->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('billing_app_key', 'billing_app_secret')");
$keys = [];
while ($row = $stmt->fetch()) {
    $keys[$row['setting_key']] = $row['setting_value'];
}

$app_key = $keys['billing_app_key'] ?? null;
$app_secret = $keys['billing_app_secret'] ?? null;

if (!$app_key || !$app_secret) {
    http_response_code(500);
    echo json_encode(['error' => 'Server misconfiguration: Billing keys not found.']);
    exit;
}

// 2. KIRIM KE APP BILLING
$payload = [
    'action'      => $action,
    'plan_id'     => $plan_id,
    'card_token'  => $card_token,
    'admin_email' => $_SESSION['user_email'] ?? 'admin@app.com'
];

$ch = curl_init(BILLING_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-App-Key: ' . $app_key,
    'X-App-Secret: ' . $app_secret
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    // HAPUS logika save midtrans_sub_id ke app_settings
    echo json_encode(['success' => true, 'message' => $data['message'] ?? 'Berhasil']);
} else {
    $err = json_decode($response, true);
    echo json_encode(['error' => $err['error'] ?? 'Gagal menghubungi server billing']);
}
?>