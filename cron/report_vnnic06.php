<?php
/**
 * Cronjob: Gửi Báo cáo Duy trì toàn cụm (VNNIC-06)
 * Khuyến nghị: Chạy hàng loạt vào một ngày cố định lúc nữa đêm nếu không phải real-time.
 * Ví dụ cron trên máy chủ: 0 0 1 * * php -q /path/to/modules/addons/vnnic_tldms/cron/report_vnnic06.php
 */

require_once __DIR__ . '/../../../../init.php';
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/ApiClient.php';

use HiTechCloud\\Vnnic\Lib\ApiClient;
use WHMCS\Database\Capsule;

// Security layer: chặn web request, chỉ chấp nhận php-cli.
if (php_sapi_name() !== 'cli' && !isset($_GET['run_report'])) {
    die("Truy cập từ chối, script CLI.");
}

echo "Bắt đầu thu thập dữ liệu báo cáo duy trì hằng tháng/chu kỳ (VNNIC-06)...\n";

try {
    $api = new ApiClient();
    $endpoint = '/reports/maintain-domains'; // Vị trí Endpoint giả định

    // Truy vấn tất cả các domain QUỐC TẾ đang hoạt động bình thường.
    // LƯU Ý: Phải chặn các đuôi ".VN" ra nếu module này chỉ phục vụ TMQT (Tên miền quốc tế).
    $domains = Capsule::table('tbldomains')
        ->join('tblclients', 'tbldomains.userid', '=', 'tblclients.id')
        ->select('tbldomains.*', 'tblclients.firstname', 'tblclients.lastname', 'tblclients.companyname')
        ->where('tbldomains.status', 'Active')
        // Thêm filter: ->whereNotIn('tbldomains.domain', ['%.vn', '%.com.vn']) ...
        ->get();

    $batchPayload = [];
    foreach ($domains as $domain) {
        $batchPayload[] = [
            'domainName' => $domain->domain,
            'registrationDate' => $domain->registrationdate,
            'expiryDate' => $domain->nextduedate,
            'registrantName' => trim($domain->companyname ?: ($domain->firstname . ' ' . $domain->lastname)),
            // Các dữ liệu bắt buộc khác của biểu VNNIC-06 có thể thêm vào đây
        ];
    }

    if (!empty($batchPayload)) {
        // Build Data Tổng
        $payload = [
            'reportType' => 'VNNIC-06',
            'timestamp' => date('Y-m-d\TH:i:s\Z'),
            'totalDomains' => count($batchPayload),
            'domainsList' => $batchPayload
        ];

        // Submit API POST. Báo cáo chung này ta gán mục Domain vào log là 'Multiple'
        $response = $api->request('POST', $endpoint, $payload, 'VNNIC-06 (Báo cáo duy trì)', 'Multiple');
        if ($response) {
            echo "Thành công !! Đã đệ trình báo cáo mảng Duy trì với tổng số: " . count($batchPayload) . " tên miền cho hệ thống của VNNIC.\n";
        } else {
            echo "Lỗi: Không thể gửi! Tra Log từ WHMCS Admin Area.\n";
        }
    } else {
        echo "Cảnh báo: Không có Tên miền Active thoả mãn điều kiện báo cáo.\n";
    }

} catch (\Exception $e) {
    echo "Fatal Exception: " . $e->getMessage() . "\n";
}
