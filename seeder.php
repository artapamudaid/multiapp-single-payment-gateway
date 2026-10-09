<?php
// seeder_ultimate.php
// ⚠️ HAPUS FILE INI SETELAH SELESAI!

function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}
loadEnv(__DIR__ . '/.env');

echo "<h2>🚀 Ultimate Seeder: Memastikan Semua Tabel & Data Ada</h2>";

try {
    $pdoOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    
    // ==========================================
    // 1. SETUP BILLING DB
    // ==========================================
    $hostB = $_ENV['DB_BILLING_HOST'] ?? '127.0.0.1';
    $dbB   = $_ENV['DB_BILLING_NAME'] ?? 'midtrans_billing_db';
    $userB = $_ENV['DB_BILLING_USER'] ?? 'root';
    $passB = $_ENV['DB_BILLING_PASS'] ?? 'root';
    
    $pdoB = new PDO("mysql:host=$hostB;dbname=$dbB;charset=utf8mb4", $userB, $passB, $pdoOptions);
    
    // AMAN: Hanya mengosongkan data, TIDAK menghapus tabel
    $pdoB->exec("TRUNCATE TABLE invoices");
    $pdoB->exec("TRUNCATE TABLE subscriptions");
    $pdoB->exec("TRUNCATE TABLE apps");

    $key1 = 'APP_KEY_' . bin2hex(random_bytes(8));
    $secret1 = 'APP_SECRET_' . bin2hex(random_bytes(16));
    $key2 = 'APP_KEY_' . bin2hex(random_bytes(8));
    $secret2 = 'APP_SECRET_' . bin2hex(random_bytes(16));

    $stmtApp = $pdoB->prepare("INSERT INTO apps (app_name, app_key, app_secret, webhook_url, app_status, plan_name, amount, sub_status, activation_date, expiry_date, next_billing_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // APP 1: Active
    $stmtApp->execute([
        'app1', $key1, $secret1, 'http://app-client1.test/webhook.php', 'active',
        'Pro Plan', 150000, 'active',
        date('Y-m-d H:i:s', strtotime('-1 month')),
        date('Y-m-d H:i:s', strtotime('+1 month')),
        date('Y-m-d', strtotime('+1 month'))
    ]);
    $appId1 = $pdoB->lastInsertId();

    // APP 2: Suspended
    $stmtApp->execute([
        'app2', $key2, $secret2, 'http://app-client2.test/webhook.php', 'suspended',
        null, 0, 'inactive', null, null, null
    ]);

    // Insert Dummy Subscription & Invoice untuk App 1 (Agar terlihat ada datanya)
    $stmtSub = $pdoB->prepare("INSERT INTO subscriptions (app_name, midtrans_sub_id, plan_name, amount, sub_status, next_billing_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtSub->execute(['app1', 'sb-sub-TEST123', 'Pro Plan', 150000, 'active', date('Y-m-d', strtotime('+1 month'))]);
    $subId = $pdoB->lastInsertId();

    $stmtInv = $pdoB->prepare("INSERT INTO invoices (app_name, midtrans_transaction_id, plan_name, amount, status, billing_period) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtInv->execute(['app1', 'sb-trx-TEST999', 'Pro Plan', 150000, 'success', date('Y-m')]);

    echo "✅ [midtrans_billing_db] Tabel apps, subscriptions, invoices diisi dengan aman.<br>";


    // ==========================================
    // 2. SETUP APP 1 DB
    // ==========================================
    $host1 = $_ENV['DB_APP1_HOST'] ?? '127.0.0.1';
    $db1   = $_ENV['DB_APP1_NAME'] ?? 'app1_db';
    $user1 = $_ENV['DB_APP1_USER'] ?? 'root';
    $pass1 = $_ENV['DB_APP1_PASS'] ?? 'root';

    $pdo1 = new PDO("mysql:host=$host1;dbname=$db1;charset=utf8mb4", $user1, $pass1, $pdoOptions);
    $pdo1->exec("TRUNCATE TABLE users");
    $pdo1->exec("TRUNCATE TABLE app_settings");

    $pw = password_hash('password123', PASSWORD_DEFAULT);
    $stmtUser = $pdo1->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmtUser->execute(['Budi (Admin)', 'budi@app1.com', $pw]);

    $stmtSet = $pdo1->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)");
    $stmtSet->execute(['billing_app_key', $key1]);
    $stmtSet->execute(['billing_app_secret', $secret1]);
    $stmtSet->execute(['sub_status', 'active']);
    $stmtSet->execute(['plan_name', 'Pro Plan']);
    $stmtSet->execute(['sub_activation_date', date('Y-m-d H:i:s', strtotime('-1 month'))]);
    $stmtSet->execute(['sub_expiry_date', date('Y-m-d H:i:s', strtotime('+1 month'))]);

    echo "✅ [app1_db] Users & app_settings diisi.<br>";


    // ==========================================
    // 3. SETUP APP 2 DB
    // ==========================================
    $host2 = $_ENV['DB_APP2_HOST'] ?? '127.0.0.1';
    $db2   = $_ENV['DB_APP2_NAME'] ?? 'app2_db';
    $user2 = $_ENV['DB_APP2_USER'] ?? 'root';
    $pass2 = $_ENV['DB_APP2_PASS'] ?? 'root';

    $pdo2 = new PDO("mysql:host=$host2;dbname=$db2;charset=utf8mb4", $user2, $pass2, $pdoOptions);
    $pdo2->exec("TRUNCATE TABLE users");
    $pdo2->exec("TRUNCATE TABLE app_settings");

    $stmtUser2 = $pdo2->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmtUser2->execute(['Andi (Admin)', 'andi@app2.com', $pw]);

    $stmtSet2 = $pdo2->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)");
    $stmtSet2->execute(['billing_app_key', $key2]);
    $stmtSet2->execute(['billing_app_secret', $secret2]);
    $stmtSet2->execute(['sub_status', 'inactive']);
    $stmtSet2->execute(['plan_name', '']);
    $stmtSet2->execute(['sub_activation_date', '']);
    $stmtSet2->execute(['sub_expiry_date', '']);

    echo "✅ [app2_db] Users & app_settings diisi.<br>";

} catch (PDOException $e) {
    echo "<p style='color:red; font-weight:bold;'>❌ ERROR: " . $e->getMessage() . "</p>";
    echo "<p>Pastikan database <code>midtrans_billing_db</code>, <code>app1_db</code>, dan <code>app2_db</code> sudah dibuat manual di phpMyAdmin sebelum menjalankan ini.</p>";
}

echo "<hr><h3>🎉 SEEDING BERHASIL & AMAN!</h3>";
echo "<p>Tabel <code>subscriptions</code> di midtrans_billing_db sekarang sudah ada dan berisi data dummy.</p>";
echo "<p style='color:red; font-weight:bold; background:#ffebee; padding:10px;'>⚠️ PENTING: HAPUS FILE <code>seeder_ultimate.php</code> INI SEKARANG JUGA!</p>";
?>