<?php
require_once 'config.php';
require_once 'midtrans_helper.php';
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

// 3. Proses request Snap Token
$input = json_decode(file_get_contents('php://input'), true);
$plan_id = $input['plan_id'] ?? '';
$admin_email = $input['admin_email'] ?? ''; 

$stmt = $pdo->prepare("SELECT * FROM plans WHERE plan_code = ?");
$stmt->execute([$plan_id]);
$plan = $stmt->fetch();

if (!$plan) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid plan']);
    exit;
}

$order_id = 'SUB-' . $app['id'] . '-' . time();

$payload = [
    'transaction_details' => [
        'order_id' => $order_id,
        'gross_amount' => (int)$plan['price']
    ],
    'customer_details' => [
        'first_name' => $app['app_name'],
        'email' => $admin_email
    ],
    // Simpan informasi paket untuk webhook
    'custom_field1' => $plan['plan_code'],
    'custom_field2' => (string)$plan['duration_months']
];

$response = mt_snap_request($payload);

if ($response['code'] == 201 || $response['code'] == 200) {
    $snap_token = $response['data']['token'];
    
    // Simpan order_id sementara di midtrans_sub_id agar bisa dikenali saat webhook Snap masuk
    // Sebagai penanda pending invoice
    $stmt = $pdo->prepare("UPDATE apps SET midtrans_sub_id = ?, plan_name = ?, amount = ? WHERE id = ?");
    $stmt->execute([
        $order_id, $plan['name'], $plan['price'], $app['id']
    ]);

    echo json_encode(['success' => true, 'snap_token' => $snap_token, 'order_id' => $order_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $response['data']['error_messages'][0] ?? 'Midtrans Error']);
}
?>