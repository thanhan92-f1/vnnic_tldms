<?php
/**
 * VNNIC - GTLD WHMCS Addon Module
 *
 * Tích hợp hệ thống quản lý và báo cáo tên miền quốc tế VNNIC.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * Define module configuration.
 *
 * @return array
 */
function vnnic_tldms_config()
{
    return [
        // Display name for your module
        'name' => 'VNNIC - GTLD Management System',
        // Description displayed within the admin interface
        'description' => 'Tự động báo cáo biến động và duy trì tên miền quốc tế với hệ thống VNNIC.',
        // Module author name
        'author' => 'Nguyễn Thanh An',
        // Default language
        'language' => 'english',
        // Version number
        'version' => '1.1.0',
        'fields' => [
            'environment' => [
                'FriendlyName' => 'Môi trường (Environment)',
                'Type' => 'dropdown',
                'Options' => 'ote,production',
                'Default' => 'ote',
                'Description' => 'Chọn OTE để thử nghiệm hoặc Production để chạy chính thức.',
            ],
            'client_id' => [
                'FriendlyName' => 'Client ID',
                'Type' => 'text',
                'Size' => '50',
                'Default' => '',
                'Description' => 'Client ID do VNNIC cấp',
            ],
            'client_secret' => [
                'FriendlyName' => 'Client Secret',
                'Type' => 'password',
                'Size' => '50',
                'Default' => '',
                'Description' => 'Client Secret do VNNIC cấp',
            ],
        ]
    ];
}

/**
 * Activate.
 *
 * @return array Optional success/pass message
 */
function vnnic_tldms_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_vnnic_tldms_locations')) {
            Capsule::schema()->create(
                'mod_vnnic_tldms_locations',
                function ($table) {
                    /** @var \Illuminate\Database\Schema\Blueprint $table */
                    $table->increments('id');
                    $table->string('vnnic_id', 50)->index();
                    $table->string('name', 255);
                    $table->string('type', 20); // country, city, district, ward
                    $table->string('parent_id', 50)->nullable()->index();
                    $table->timestamps();
                }
            );
        }

        if (!Capsule::schema()->hasTable('mod_vnnic_tldms_logs')) {
            Capsule::schema()->create(
                'mod_vnnic_tldms_logs',
                function ($table) {
                    /** @var \Illuminate\Database\Schema\Blueprint $table */
                    $table->increments('id');
                    $table->string('action', 50);
                    $table->string('endpoint', 255);
                    $table->text('request_payload')->nullable();
                    $table->text('response_body')->nullable();
                    $table->integer('status_code')->nullable();
                    $table->string('domain_name', 255)->nullable();
                    $table->timestamps();
                }
            );
        }

        return [
            'status' => 'success',
            'description' => 'Module đã được cài đặt và tạo bảng CSDL thành công.',
        ];
    } catch (\Exception $e) {
        return [
            'status' => "error",
            'description' => 'Không thể tạo bảng CSDL: ' . $e->getMessage(),
        ];
    }
}

/**
 * Deactivate.
 *
 * @return array Optional success/pass message
 */
function vnnic_tldms_deactivate()
{
    try {
        // Tuỳ chọn: Có xoá bảng config hay không. 
        // Thường không nên xoá CSDL log và danh mục để tránh mất dữ liệu khi deactive nhầm.
        // Capsule::schema()->dropIfExists('mod_vnnic_tldms_locations');
        // Capsule::schema()->dropIfExists('mod_vnnic_tldms_logs');

        return [
            'status' => 'success',
            'description' => 'Module đã được vô hiệu hóa an toàn.',
        ];
    } catch (\Exception $e) {
        return [
            'status' => "error",
            'description' => 'Lỗi khi vô hiệu hóa: ' . $e->getMessage(),
        ];
    }
}

/**
 * Admin Area Output.
 *
 * @param array $vars
 */
function vnnic_tldms_output($vars)
{
    // Lấy thông tin logs gần nhất
    $logs = Capsule::table('mod_vnnic_tldms_logs')
        ->orderBy('id', 'desc')
        ->limit(50)
        ->get();

    echo '<h2>VNNIC - GTLD Log Dashboard</h2>';
    echo '<p>Hệ thống tự động chạy cronjob mỗi 5 phút. Phiên bản PHP hiện tại: ' . phpversion() . '</p>';
    
    echo '<table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">';
    echo '<tr><th>ID</th><th>Action</th><th>Endpoint</th><th>Domain</th><th>Status</th><th>Thời gian</th></tr>';
    
    if (count($logs) > 0) {
        foreach ($logs as $log) {
            $statusColor = ($log->status_code >= 200 && $log->status_code < 300) ? 'green' : 'red';
            echo '<tr>';
            echo '<td>' . $log->id . '</td>';
            echo '<td>' . htmlspecialchars($log->action) . '</td>';
            echo '<td>' . htmlspecialchars($log->endpoint) . '</td>';
            echo '<td>' . htmlspecialchars($log->domain_name) . '</td>';
            echo '<td style="color:' . $statusColor . '">' . $log->status_code . '</td>';
            echo '<td>' . $log->created_at . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6" align="center">Chưa có bản ghi log nào.</td></tr>';
    }
    
    echo '</table>';
}
