<?php
/**
 * Cronjob Đồng bộ dữ liệu địa danh VNNIC
 * 
 * Khuyến nghị cài đặt trên Cpanel/DirectAdmin:
 * */5 * * * * php -q /path-to-whmcs/modules/addons/vnnic_tldms/cron/sync_data.php
 */

require_once __DIR__ . '/../../../../init.php';
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/ApiClient.php';
require_once __DIR__ . '/../lib/LocationSync.php';

use HiTechCloud\\Vnnic\Lib\LocationSync;
use WHMCS\Database\Capsule;

// Chỉ cấp quyền chạy qua CLI hoặc nếu có khoá bảo mật.
if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die("This script can only be run from the command line.");
}

// Logic chống gọi API liên tục (Cooldown 24 giờ cho danh mục địa danh tĩnh)
$settingKey = 'vnnic_tldms_last_sync';
$lastSync = Capsule::table('tbladdonmodules')
    ->where('module', 'vnnic_tldms')
    ->where('setting', $settingKey)
    ->value('value');

$now = time();
$cooldownSeconds = 24 * 3600; // 24 tiếng

if ($lastSync && ($now - (int)$lastSync) < $cooldownSeconds) {
    echo "Cooldown active. Skipping sync. Next sync available in " . ($cooldownSeconds - ($now - (int)$lastSync)) . " seconds.\n";
    exit;
}

echo "Bắt đầu đồng bộ danh mục địa danh VNNIC...\n";

try {
    $sync = new LocationSync();
    $sync->syncAll();
    
    // Cập nhật timestamp lần sync cuối
    if ($lastSync === null) {
        Capsule::table('tbladdonmodules')->insert([
            'module' => 'vnnic_tldms',
            'setting' => $settingKey,
            'value' => (string)$now
        ]);
    } else {
        Capsule::table('tbladdonmodules')
            ->where('module', 'vnnic_tldms')
            ->where('setting', $settingKey)
            ->update(['value' => (string)$now]);
    }

    echo "Đồng bộ hoàn tất thành công.\n";

} catch (\Exception $e) {
    echo "Lỗi trong quá trình đồng bộ: " . $e->getMessage() . "\n";
}
