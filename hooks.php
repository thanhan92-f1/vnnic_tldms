<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * Hook: Inject JavaScript vào Front-End Client Area
 * Dùng để tuỳ biến các trường Input Text (City/State/Postcode) thành dạng Select Dropdown
 * tương tác thông qua dữ liệu đã đồng bộ từ VNNIC (mod_vnnic_tldms_locations)
 */
add_hook('ClientAreaHeadOutput', 1, function($vars) {
    // Chỉ kích hoạt ở các trang đăng ký/quản lý thông tin
    $targetPages = ['clientregister', 'clientareadetails', 'cart', 'checkout'];
    if (isset($vars['templatefile']) && in_array($vars['templatefile'], $targetPages)) {
        
        // TODO: Xây dựng một file ajax nội bộ để xử lý request: vnnic_tldms_ajax.php 
        // trả về danh sách huyện/xã dựa vào parent_id.
        // Dưới đây là skeleton Javascript nạp lên giao diện.
        
        $script = <<<HTML
<script>
document.addEventListener("DOMContentLoaded", function() {
    console.log("VNNIC - GTLD: Initialization of Dynamic Location Dropdowns...");
    
    // Giả lập Logic: Chặn input default của WHMCS và thay đổi thành Select HTML
    // const stateInput = document.querySelector('input[name="state"]');
    // const cityInput = document.querySelector('input[name="city"]');
    // ...
    // Khi state (Tỉnh) onchange -> gọi AJAX load city (Huyện) -> gọi tải phường (Xã)
});
</script>
HTML;
        return $script;
    }
});

/**
 * Hook: Chặn thông tin Form Đăng ký để xác thực với VNNIC
 * VD: Kiểm tra xem Tỉnh/Huyện/Xã khách hàng chọn có khớp với mã VNNIC ID trong DB không
 */
add_hook('ClientAreaRegisterValidation', 1, function($vars) {
    $errormessage = "";
    
    // $vars['state'], $vars['city'], $vars['address1']
    // Nếu chọn sai định dạng danh mục, trả về thông báo lỗi:
    // $errormessage .= "<li>Vui lòng chọn Tỉnh/Thành phố từ danh sách có sẵn.</li>";
    
    if ($errormessage) {
        return $errormessage;
    }
});

/**
 * Hook (VNNIC-07): Báo cáo biến động tự động với sự kiện ĐĂNG KÝ MỚI (ADD).
 * Sự kiện này kích hoạt sau khi WHMCS điều khiển Registrar API (VD Enom, Namecheap) tạo thành công tên miền quốc tế.
 */
add_hook('AfterModuleCreate', 1, function($vars) {
    if (isset($vars['params']['domain']) && $vars['params']['type'] === 'domain') {
        require_once __DIR__ . '/lib/Auth.php';
        require_once __DIR__ . '/lib/ApiClient.php';
        require_once __DIR__ . '/lib/ReportVNNIC07.php';

        $domainId = isset($vars['params']['domainid']) ? $vars['params']['domainid'] : 0;
        if ($domainId) {
            $reporter = new \W2w\\Vnnic\Lib\ReportVNNIC07();
            $reporter->sendFluctuationReport($domainId, 'ADD');
        }
    }
});

/**
 * Hook (VNNIC-07): Báo cáo biến động thay đổi thông tin hoặc tình trạng (CHANGE).
 * Kích hoạt khi tên miền bị chỉnh sửa/cập nhật thông qua WHMCS Admin hoặc ClientArea.
 */
add_hook('DomainEdit', 1, function($vars) {
    $domainId = isset($vars['domainid']) ? $vars['domainid'] : 0;
    if ($domainId) {
        require_once __DIR__ . '/lib/Auth.php';
        require_once __DIR__ . '/lib/ApiClient.php';
        require_once __DIR__ . '/lib/ReportVNNIC07.php';

        $reporter = new \W2w\\Vnnic\Lib\ReportVNNIC07();
        $reporter->sendFluctuationReport($domainId, 'CHANGE');
    }
});
