<?php
/**
 * Công cụ kiểm tra kết nối API VNNIC
 * Dùng để kiểm tra cấu hình Authenticator sau khi cài Module
 */

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/ApiClient.php';

use W2w\\Vnnic\Lib\ApiClient;

if (php_sapi_name() !== 'cli' && !isset($_GET['admin_test'])) {
    die("Vui lòng truy cập qua SSH Terminal (CLI) hoặc thêm tham số ?admin_test=1 vào đường dẫn.");
}

echo "--------------------------------------------------------\n";
echo "BẮT ĐẦU KIỂM THỬ CẤU HÌNH API VNNIC TRÊN MÔI TRƯỜNG HIỆN TẠI\n";
echo "--------------------------------------------------------\n";

try {
    $api = new ApiClient();
    
    // Gọi một endpoint đơn giản để PING, ví dụ danh sách danh mục Tỉnh hoặc Quốc Gia
    $endpoint = '/categories/cities'; // Giả lập PING lấy cities
    
    echo "Đang kết nối (PING) tới cấu hình endpoint: " . $endpoint . "\n";
    echo "Đang xử lý Header Authentication Base64...\n";
    
    $response = $api->request('GET', $endpoint, [], 'Test Ping Connection');

    if ($response && is_array($response)) {
        echo "[ KẾT QUẢ ]: KẾT NỐI (PING) SANDBOX/PRODUCTION VNNIC ==> THÀNH CÔNG.\n";
        
        $countData = isset($response['data']) ? count($response['data']) : count($response);
        echo "Lấy thành công một phần dữ liệu chuẩn phản hồi: " . $countData . " dòng.\n";
        echo "Hãy check hệ thống WHMCS Log (System logs) từ Addon Module GTLD_VNNIC Dashboard để xem gói Payload 200.\n";
    } else {
        echo "[ KẾT QUẢ ]: LỖI! Tuy truy vấn được thực hiện nhưng không lấy được dữ liệu chuẩn xác mong đợi.\n";
        echo "Vui lòng check hệ thống Log Dashboard của VNNIC - GTLD trong tab Addon Modules WHMCS.\n";
    }

} catch (\Exception $e) {
    echo "\n[ LỖI MẠNG FATAL ]: " . $e->getMessage() . "\n";
    echo "Gợi ý kiểm tra: Extensions cURL của PHP 8.1 server hoặc Tường Lửa (Firewall).\n";
}
echo "--------------------------------------------------------\n";
