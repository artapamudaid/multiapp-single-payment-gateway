<?php
// app_billing/index.php
require_once 'config.php';

// 1. Fetch Summary Stats
$stmt_revenue = $pdo->query("SELECT SUM(amount) FROM invoices WHERE status = 'success'");
$total_revenue = $stmt_revenue->fetchColumn() ?: 0;

$stmt_apps = $pdo->query("SELECT COUNT(*) FROM apps WHERE sub_status = 'active'");
$active_apps = $stmt_apps->fetchColumn() ?: 0;

$stmt_total_apps = $pdo->query("SELECT COUNT(*) FROM apps");
$total_apps = $stmt_total_apps->fetchColumn() ?: 0;

// 2. Fetch Apps
$stmt_apps_list = $pdo->query("SELECT * FROM apps ORDER BY id DESC");
$apps = $stmt_apps_list->fetchAll();

// 3. Fetch Recent Invoices
$stmt_invoices = $pdo->query("SELECT * FROM invoices ORDER BY id DESC LIMIT 10");
$invoices = $stmt_invoices->fetchAll();

// Helper
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Monitoring Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f172a;
            --bg-card: rgba(30, 41, 59, 0.7);
            --text-light: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #3b82f6;
            --primary-glow: rgba(59, 130, 246, 0.5);
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-light);
            min-height: 100vh;
            padding: 2rem;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(16, 185, 129, 0.15), transparent 25%);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(to right, #60a5fa, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }

        .stat-title {
            font-size: 0.875rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-light);
        }
        
        .stat-value.primary { color: #60a5fa; }
        .stat-value.success { color: #34d399; }

        /* Tables Grid */
        .tables-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        @media (min-width: 1024px) {
            .tables-container {
                grid-template-columns: 2fr 1fr;
            }
        }

        .table-card {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.5rem;
            overflow-x: auto;
        }

        .table-card h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: var(--text-light);
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            padding: 1rem 0.5rem;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 1rem 0.5rem;
            font-size: 0.875rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        tbody tr:hover {
            background: rgba(255,255,255,0.02);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge.active, .badge.success { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .badge.inactive, .badge.failed, .badge.suspended { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .badge.pending, .badge.warning { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }

        .key-mask {
            font-family: monospace;
            background: rgba(0,0,0,0.3);
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
            color: #94a3b8;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Master Billing Dashboard</h1>
            <div>
                <span style="color: var(--text-muted); font-size: 0.875rem;">Sistem Pembayaran Terpusat</span>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Pendapatan (Sukses)</div>
                <div class="stat-value success"><?= formatRupiah($total_revenue) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Aplikasi Klien</div>
                <div class="stat-value primary"><?= $total_apps ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Aplikasi Langganan Aktif</div>
                <div class="stat-value"><?= $active_apps ?></div>
            </div>
        </section>

        <section class="tables-container">
            <!-- Data Aplikasi Klien -->
            <div class="table-card">
                <h2>📱 Aplikasi Klien Terhubung</h2>
                <table>
                    <thead>
                        <tr>
                            <th>App Name</th>
                            <th>Status Akun</th>
                            <th>Status Langganan</th>
                            <th>Paket Saat Ini</th>
                            <th>Kedaluwarsa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($apps as $a): ?>
                        <tr>
                            <td style="font-weight: 500; color: #fff;"><?= htmlspecialchars($a['app_name']) ?></td>
                            <td>
                                <span class="badge <?= $a['app_status'] == 'active' ? 'success' : 'suspended' ?>">
                                    <?= strtoupper($a['app_status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $a['sub_status'] == 'active' ? 'active' : 'inactive' ?>">
                                    <?= strtoupper($a['sub_status'] ?? 'INACTIVE') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($a['plan_name'] ?: '-') ?></td>
                            <td style="color: var(--text-muted);">
                                <?= $a['expiry_date'] ? date('d M Y', strtotime($a['expiry_date'])) : '-' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($apps) == 0): ?>
                        <tr><td colspan="5" style="text-align:center; color: var(--text-muted);">Belum ada aplikasi yang terdaftar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Riwayat Transaksi -->
            <div class="table-card">
                <h2>💸 Transaksi Terbaru</h2>
                <table>
                    <thead>
                        <tr>
                            <th>App</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($invoices as $inv): ?>
                        <tr>
                            <td><?= htmlspecialchars($inv['app_name']) ?></td>
                            <td style="font-weight: 600;"><?= formatRupiah($inv['amount']) ?></td>
                            <td>
                                <span class="badge <?= $inv['status'] ?>">
                                    <?= strtoupper($inv['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($invoices) == 0): ?>
                        <tr><td colspan="3" style="text-align:center; color: var(--text-muted);">Belum ada transaksi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>
