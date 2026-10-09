<?php
// app_client/middleware.php
require_once 'config.php';

// Ambil status langganan app dari app_settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM app_settings 
                      WHERE setting_key IN ('sub_status', 'sub_expiry_date', 'plan_name')");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$is_active = ($settings['sub_status'] ?? 'inactive') === 'active';
$is_expired = isset($settings['sub_expiry_date']) && strtotime($settings['sub_expiry_date']) < time();

// DEFINISI KONSTANTA GLOBAL UNTUK APLIKASI
define('APP_IS_PREMIUM', $is_active && !$is_expired);
define('APP_PLAN_NAME', $settings['plan_name'] ?? 'Free');
define('APP_EXPIRY_DATE', $settings['sub_expiry_date'] ?? null);
?>