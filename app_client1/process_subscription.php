<?php
// app_client/process_subscription.php
require_once 'config.php';
session_start();
header('Content-Type: application/json');

// 1. Validasi: Pastikan user adalah admin yang login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized: Silakan login terlebih dahulu.']);
    exit;
}

// 2. Baca input dari frontend
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'NEW'; // 'NEW' atau 'RENEW'
$plan_id = $input['plan_id'] ?? '';

if (!$plan_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap.']);
    exit;
}

// 3. AMBIL SECRET KEY DARI DATABASE LOKAL (app_settings)
$stmt = $pdo->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('billing_app_key', 'billing_app_secret')");
$keys = [];
while ($row = $stmt->fetch()) {
    $keys[$row['setting_key']] = $row['setting_value'];
}

$app_key = $keys['billing_app_key'] ?? null;
$app_secret = $keys['billing_app_secret'] ?? null;

if (!$app_key || !$app_secret) {
    http_response_code(500);
    echo json_encode(['error' => 'Server misconfiguration: Kunci billing tidak ditemukan di database.']);
    exit;
}

// 4. Siapkan payload untuk dikirim ke APP BILLING
$payload = [
    'action'      => $action,
    'plan_id'     => $plan_id,
    'admin_email' => $_SESSION['user_email'] ?? 'admin@mail.com'
];

// 5. Kirim Request ke App Billing (Server-to-Server)
$ch = curl_init(BILLING_API_URL); // URL didefinisikan di config.php
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

// 6. Kembalikan hasil ke frontend
if ($httpCode === 200) {
    $data = json_decode($response, true);
    // Berhasil mendapatkan token Snap, teruskan ke frontend
    echo json_encode([
        'success' => true, 
        'snap_token' => $data['snap_token'] ?? null,
        'message' => 'Lanjutkan pembayaran'
    ]);
} else {
    $err = json_decode($response, true);
    echo json_encode([
        'error' => $err['error'] ?? 'Gagal menghubungi server billing. Silakan coba lagi.'
    ]);
}
?>