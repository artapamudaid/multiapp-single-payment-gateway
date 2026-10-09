<?php
require_once 'config.php';

// Helper: Kirim webhook ke App Client
function notify_app_client($app_data, $status, $plan_name, $activation, $expiry) {
    $webhook_url = $app_data['webhook_url'];
    $app_key = $app_data['app_key'];
    $app_secret = $app_data['app_secret'];
    
    if (empty($webhook_url)) return;

    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'sub_status'        => $status,
        'plan_name'         => $plan_name,
        'activation_date'   => $activation,
        'expiry_date'       => $expiry
    ]));
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-App-Key: ' . $app_key,
        'X-App-Secret: ' . $app_secret 
    ]);
    
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
    curl_exec($ch);
    curl_close($ch);
}

$rawBody = file_get_contents('php://input');
$notif = json_decode($rawBody, true);

// Log notif for debugging
error_log("Midtrans Webhook Hit: " . $rawBody);

if ($notif === null) {
    http_response_code(400);
    exit('Invalid JSON');
}

// Verifikasi Signature Midtrans
$order_id = $notif['order_id'] ?? '';
$status_code = $notif['status_code'] ?? '';
$gross_amount = $notif['gross_amount'] ?? '';
$signature_key = $notif['signature_key'] ?? '';

$hashed = hash('sha512', $order_id . $status_code . $gross_amount . MT_SERVER_KEY);
if ($signature_key !== $hashed) {
    error_log("Signature mismatch! Expected: $hashed, Got: $signature_key");
    http_response_code(403);
    exit('Invalid Signature');
}

if (isset($notif['order_id'])) {
    $order_id = $notif['order_id'];
    $status = $notif['transaction_status'];
    
    if ($status == 'settlement' || $status == 'capture') {
        $db_status = 'success';
    } else if ($status == 'pending') {
        $db_status = 'pending';
    } else {
        $db_status = 'failed';
    }

    // Cari app yang punya midtrans_sub_id == order_id ini
    $stmt = $pdo->prepare("SELECT * FROM apps WHERE midtrans_sub_id = ?");
    $stmt->execute([$order_id]);
    $app = $stmt->fetch();

    if ($app) {
        $transaction_id = $notif['transaction_id'] ?? $order_id;
        
        // 1. Simpan/Update invoice
        $stmt_inv = $pdo->prepare("SELECT id FROM invoices WHERE midtrans_transaction_id = ?");
        $stmt_inv->execute([$transaction_id]);
        if ($stmt_inv->fetch()) {
            $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE midtrans_transaction_id = ?");
            $stmt->execute([$db_status, $transaction_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO invoices (app_name, midtrans_transaction_id, plan_name, amount, status, billing_period) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$app['app_name'], $transaction_id, $app['plan_name'], $app['amount'], $db_status, date('Y-m')]);
        }

        // 2. Update expiry date di apps jika sukses
        if ($db_status === 'success') {
            $now = date('Y-m-d H:i:s');
            
            // Ambil durasi bulan dari custom_field2
            $duration = isset($notif['custom_field2']) ? (int)$notif['custom_field2'] : 1;
            
            // Logika perpanjangan masa aktif
            if ($app['sub_status'] === 'active' && $app['expiry_date'] && strtotime($app['expiry_date']) > time()) {
                $expiry = date('Y-m-d H:i:s', strtotime($app['expiry_date'] . " +{$duration} month"));
            } else {
                $expiry = date('Y-m-d H:i:s', strtotime("+{$duration} month"));
            }

            $stmt = $pdo->prepare("UPDATE apps SET sub_status = 'active', activation_date = ?, expiry_date = ?, next_billing_date = ? WHERE midtrans_sub_id = ?");
            $stmt->execute([$now, $expiry, date('Y-m-d', strtotime($expiry)), $order_id]);

            notify_app_client($app, 'active', $app['plan_name'], $now, $expiry);
        } else if ($status == 'expire' || $status == 'cancel' || $status == 'deny') {
            $stmt = $pdo->prepare("UPDATE apps SET sub_status = 'cancelled' WHERE midtrans_sub_id = ?");
            $stmt->execute([$order_id]);

            notify_app_client($app, 'inactive', '', '', '');
        }
    }
}

http_response_code(200);
echo "OK";
?>