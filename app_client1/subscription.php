<?php
// app_client/subscription.php
require_once 'config.php';
require_once 'middleware.php'; // Untuk memastikan kita bisa baca status
session_start();

// Simulasi admin login (Ganti dengan logic login Anda yang sebenarnya)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Budi (Admin App)';
    $_SESSION['user_email'] = 'budi@app1.com';
}

// 1. Ambil status langganan dari DB lokal
$stmt = $pdo->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('sub_status', 'sub_expiry_date', 'plan_name')");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$sub_status = $settings['sub_status'] ?? 'inactive';
$expiry_date = $settings['sub_expiry_date'] ?? null;
$plan_name = $settings['plan_name'] ?? '';

$is_active = ($sub_status === 'active');
$is_expired = $expiry_date && strtotime($expiry_date) < time();

// Tentukan kondisi UI
if ($is_active && !$is_expired) {
    $ui_state = 'ACTIVE';
} elseif ($is_active && $is_expired) {
    $ui_state = 'NEED_RENEW';
} else {
    $ui_state = 'INACTIVE';
}

// Ambil API Keys untuk koneksi ke billing
$stmt_keys = $pdo->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('billing_app_key', 'billing_app_secret')");
$billing_keys = [];
while ($row = $stmt_keys->fetch()) {
    $billing_keys[$row['setting_key']] = $row['setting_value'];
}
$app_key = $billing_keys['billing_app_key'] ?? '';
$app_secret = $billing_keys['billing_app_secret'] ?? '';

// Ambil daftar paket dari server billing
$ch = curl_init('http://app-billing.test/api_plans.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-App-Key: ' . $app_key,
    'X-App-Secret: ' . $app_secret
]);
$response = curl_exec($ch);
curl_close($ch);

$plans_api = json_decode($response, true);
$plans = [];
if ($plans_api && $plans_api['success']) {
    foreach ($plans_api['data'] as $p) {
        $plans[$p['plan_code']] = [
            'name' => $p['name'],
            'price' => $p['price'],
            'duration' => $p['duration_months']
        ];
    }
} else {
    // Fallback jika API billing bermasalah
    $plans = [
        'basic_1m' => ['name' => 'Basic Plan (1 Bulan)', 'price' => 50000, 'duration' => 1],
        'basic_1y' => ['name' => 'Basic Plan (1 Tahun)', 'price' => 500000, 'duration' => 12]
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Langganan</title>
    <!-- Midtrans Snap JS -->
    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<?= MT_CLIENT_KEY ?>"></script>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; }
        .status-box { padding: 20px; border-radius: 8px; margin-bottom: 25px; }
        .active { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .inactive { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        input, select { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 12px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; font-weight: bold; }
        button:hover { background: #0056b3; }
        button:disabled { background: #ccc; cursor: not-allowed; }
        .result-msg { margin-top: 20px; padding: 15px; border-radius: 4px; display: none; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <a href="index.php" class="back-link">← Kembali ke Dashboard</a>
    <h2>⚙️ Kelola Langganan Aplikasi</h2>
    <p>Halo, <b><?= htmlspecialchars($_SESSION['user_name']) ?></b></p>

    <!-- ========================================== -->
    <!-- KONDISI 1: SUDAH AKTIF                     -->
    <!-- ========================================== -->
    <!-- ========================================== -->
    <!-- KONDISI 1: SUDAH AKTIF                     -->
    <!-- ========================================== -->
    <?php if ($ui_state === 'ACTIVE'): ?>
        <div class="status-box active">
            <h3>✅ Langganan Anda Aktif</h3>
            <p><b>Paket:</b> <?= htmlspecialchars($plan_name) ?></p>
            <p><b>Berlaku hingga:</b> <?= date('d F Y', strtotime($expiry_date)) ?></p>
        </div>
        <p style="font-size: 14px; color: #666; margin-bottom: 20px;">
            Anda tetap dapat memperpanjang langganan sekarang. Masa aktif akan otomatis ditambahkan ke sisa hari yang Anda miliki.
        </p>

    <!-- ========================================== -->
    <!-- KONDISI 2 & 3: PERLU RENEW / INACTIVE      -->
    <!-- ========================================== -->
    <?php else: ?>
        <div class="status-box <?= $ui_state === 'NEED_RENEW' ? 'warning' : 'inactive' ?>">
            <h3><?= $ui_state === 'NEED_RENEW' ? '⚠️ Langganan Perlu Diperpanjang' : '❌ Belum Ada Langganan Aktif' ?></h3>
            <p><?= $ui_state === 'NEED_RENEW' ? 'Masa langganan Anda telah berakhir.' : 'Aktifkan fitur premium untuk aplikasi Anda sekarang.' ?></p>
        </div>
    <?php endif; ?>

    <form id="paymentForm" onsubmit="event.preventDefault(); processPayment();">
        <input type="hidden" id="action-type" value="<?= ($ui_state === 'ACTIVE' || $ui_state === 'NEED_RENEW') ? 'RENEW' : 'NEW' ?>">
        
        <div class="form-group">
            <label>Pilih Paket Langganan:</label>
            <select id="plan-id" required>
                <?php foreach ($plans as $id => $p): ?>
                    <option value="<?= $id ?>" <?= ($plan_name == $p['name']) ? 'selected' : '' ?>>
                        <?= $p['name'] ?> - Rp <?= number_format($p['price'], 0, ',', '.') ?> / bulan
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" id="payButton">
            <?= ($ui_state === 'ACTIVE' || $ui_state === 'NEED_RENEW') ? 'Perpanjang / Upgrade Paket' : 'Langganan Sekarang' ?>
        </button>
    </form>

    <!-- Area Pesan Hasil -->
    <div id="result-message" class="result-msg"></div>

    <script>
        function processPayment() {
            var payBtn = document.getElementById('payButton');
            var msgDiv = document.getElementById('result-message');
            
            // Disable button & show loading
            payBtn.disabled = true;
            payBtn.innerText = "Memproses...";
            msgDiv.style.display = 'none';

            var actionType = document.getElementById('action-type').value;
            var planId = document.getElementById('plan-id').value;

            // 1. Minta Token Snap ke Backend Kita Sendiri
            fetch('process_subscription.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: actionType,
                    plan_id: planId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.snap_token) {
                    // 2. Tampilkan Popup Snap Midtrans
                    snap.pay(data.snap_token, {
                        onSuccess: function(result) {
                            msgDiv.style.display = 'block';
                            msgDiv.className = 'result-msg success';
                            msgDiv.innerHTML = "✅ Pembayaran berhasil! <br><br><a href='index.php' style='color:#155724; font-weight:bold;'>Klik di sini untuk kembali ke dashboard</a>";
                        },
                        onPending: function(result) {
                            msgDiv.style.display = 'block';
                            msgDiv.className = 'result-msg warning';
                            msgDiv.innerHTML = "⚠️ Menunggu pembayaran Anda. <br><br><a href='index.php' style='color:#856404; font-weight:bold;'>Kembali ke dashboard</a>";
                        },
                        onError: function(result) {
                            msgDiv.style.display = 'block';
                            msgDiv.className = 'result-msg error';
                            msgDiv.innerHTML = "❌ Pembayaran gagal.";
                            payBtn.disabled = false;
                            payBtn.innerText = actionType === 'RENEW' ? 'Perpanjang Langganan' : 'Langganan Sekarang';
                        },
                        onClose: function() {
                            payBtn.disabled = false;
                            payBtn.innerText = actionType === 'RENEW' ? 'Perpanjang Langganan' : 'Langganan Sekarang';
                        }
                    });
                } else {
                    msgDiv.style.display = 'block';
                    msgDiv.className = 'result-msg error';
                    msgDiv.innerHTML = "❌ Gagal: " + (data.error || "Terjadi kesalahan mendapatkan token pembayaran.");
                    payBtn.disabled = false;
                    payBtn.innerText = actionType === 'RENEW' ? 'Perpanjang Langganan' : 'Langganan Sekarang';
                }
            })
            .catch(err => {
                msgDiv.style.display = 'block';
                msgDiv.className = 'result-msg error';
                msgDiv.innerHTML = "❌ Gagal menghubungi server.";
                payBtn.disabled = false;
                payBtn.innerText = "Coba Lagi";
            });
        }
    </script>
</body>
</html>