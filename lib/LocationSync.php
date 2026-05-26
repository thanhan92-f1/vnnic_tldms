<?php

namespace HiTechCloud\\Vnnic\Lib;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

class LocationSync
{
    private $api;

    public function __construct()
    {
        $this->api = new ApiClient();
    }

    /**
     * Bắt đầu tiến trình đồng bộ (Thường được gọi bởi Cron)
     */
    public function syncAll()
    {
        // 1. Đồng bộ danh sách Tỉnh/Thành phố (Cấp 1)
        $this->syncLevel('city', '/categories/cities', null);

        // 2. Đồng bộ danh sách Quận/Huyện dựa theo Tỉnh/Thành đã có (Cấp 2)
        $cities = Capsule::table('mod_vnnic_tldms_locations')->where('type', 'city')->get();
        foreach ($cities as $city) {
            // Giả lập endpoint /categories/districts?cityId={id} dựa trên chuẩn REST chung
            $this->syncLevel('district', '/categories/districts?cityId=' . $city->vnnic_id, $city->vnnic_id);
        }

        // 3. Đồng bộ danh sách Phường/Xã dựa theo Quận/Huyện (Cấp 3)
        // Lưu ý: Việc lấy Phường Xã có khối lượng lớn, có thể gây timeout nếu chạy 1 lần. 
        // Trong môi trường production nên split chunk hoặc check updatedAt.
        $districts = Capsule::table('mod_vnnic_tldms_locations')->where('type', 'district')->get();
        foreach ($districts as $district) {
            $this->syncLevel('ward', '/categories/wards?districtId=' . $district->vnnic_id, $district->vnnic_id);
        }

        return true;
    }

    /**
     * Xử lý gọi API và Upsert vào Database chung
     *
     * @param string $type Loại địa danh: 'city', 'district', 'ward'
     * @param string $endpoint Endpoint gọi sang VNNIC API
     * @param string|null $parentId Mã VNNIC ID của cấp cha (VD: cityId của một district)
     */
    private function syncLevel($type, $endpoint, $parentId = null)
    {
        $response = $this->api->request('GET', $endpoint, [], "Sync $type");

        if (is_array($response) && isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $item) {
                // Giả định định dạng JSON trả về chứa 'id' và 'name'
                $vnnicId = isset($item['id']) ? $item['id'] : '';
                $name = isset($item['name']) ? $item['name'] : '';

                if (empty($vnnicId) || empty($name)) {
                    continue;
                }

                // Cập nhật hoặc Thêm mới (Upsert)
                $existing = Capsule::table('mod_vnnic_tldms_locations')
                    ->where('vnnic_id', $vnnicId)
                    ->where('type', $type)
                    ->first();

                if ($existing) {
                    Capsule::table('mod_vnnic_tldms_locations')
                        ->where('id', $existing->id)
                        ->update([
                            'name' => $name,
                            'parent_id' => $parentId,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                } else {
                    Capsule::table('mod_vnnic_tldms_locations')->insert([
                        'vnnic_id' => $vnnicId,
                        'name' => $name,
                        'type' => $type,
                        'parent_id' => $parentId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
    }
}
