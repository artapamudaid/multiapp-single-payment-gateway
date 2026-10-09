<?php
// app_client/index.php
require_once 'middleware.php'; // <-- INI KUNCINYA!
session_start();

// Simulasi user login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Budi (Admin App)';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Aplikasi</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 14px; font-weight: bold; }
        .badge-premium { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-free { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card.premium-locked { opacity: 0.6; pointer-events: none; position: relative; }
        .lock-overlay { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 15px 30px; border-radius: 8px; font-weight: bold; text-align: center; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .btn:hover { background: #0056b3; }
        .btn-warning { background: #ffc107; color: #212529; }
        .info-box { background: #e2e3e5; padding: 15px; border-radius: 5px; font-size: 14px; color: #383d41; margin-top: 30px; border-left: 4px solid #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div>
                <h2 style="margin: 0;">Dashboard App Client</h2>
                <p style="margin: 5px 0 0 0; color: #666;">Halo, <?= htmlspecialchars($_SESSION['user_name']) ?></p>
            </div>
            <div style="text-align: right;">
                <?php if (APP_IS_PREMIUM): ?>
                    <span class="badge badge-premium">✨ <?= htmlspecialchars(APP_PLAN_NAME) ?> (Aktif)</span>
                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #155724;">
                        Berlaku s/d: <?= date('d M Y', strtotime(APP_EXPIRY_DATE)) ?>
                    </p>
                <?php else: ?>
                    <span class="badge badge-free">🔒 Free Plan</span>
                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #721c24;">
                        Langganan tidak aktif atau sudah kedaluwarsa.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- FITUR GRATIS (SELALU MUNCUL) -->
        <div class="card">
            <h3>📊 Laporan Dasar (Gratis)</h3>
            <p>Fitur ini dapat diakses oleh semua aplikasi, baik yang berlangganan maupun tidak.</p>
            <ul>
                <li>Total Pengunjung Hari Ini: 1,234</li>
                <li>Transaksi Berhasil: 45</li>
            </ul>
        </div>

        <!-- FITUR PREMIUM (DIBLOKIR OLEH MIDDLEWARE) -->
        <?php if (!APP_IS_PREMIUM): ?>
            <div class="card premium-locked">
                <div class="lock-overlay">
                    🔒 Fitur Premium<br>
                    <small>Silakan aktifkan langganan untuk membuka akses.</small><br><br>
                    <a href="subscription.php" class="btn btn-warning">Upgrade Sekarang</a>
                </div>
                <h3>📈 Laporan Analitik Mendalam (Premium)</h3>
                <p>Fitur ini hanya untuk aplikasi yang memiliki langganan aktif.</p>
                <ul>
                    <li>Demografi Pengunjung Detail</li>
                    <li>Export Data ke Excel/PDF</li>
                    <li>API Access Unlimited</li>
                </ul>
            </div>
        <?php else: ?>
            <!-- Jika PREMIUM, tampilkan konten aslinya -->
            <div class="card" style="border-left: 4px solid #28a745;">
                <h3>📈 Laporan Analitik Mendalam (Premium)</h3>
                <p>Selamat! Aplikasi Anda memiliki akses penuh ke fitur premium.</p>
                <ul>
                    <li>✅ Demografi Pengunjung Detail</li>
                    <li>✅ Export Data ke Excel/PDF</li>
                    <li>✅ API Access Unlimited</li>
                </ul>
                <button class="btn" onclick="alert('Mengexport data...')">Download Laporan PDF</button>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="subscription.php" class="btn">⚙️ Kelola Langganan & Pembayaran</a>
        </div>
    </div>
</body>
</html>