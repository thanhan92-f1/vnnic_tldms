<?php

namespace HiTechCloud\\Vnnic\Lib;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

class ReportVNNIC07
{
    private $api;

    public function __construct()
    {
        $this->api = new ApiClient();
    }

    /**
     * Thu thập thông tin Domain và gửi Request dạng Biến động (Fluctuation) VNNIC-07
     *
     * @param int $domainId ID tên miền trong hệ thống bảng tbldomains của WHMCS
     * @param string $actionType Mã hành động: ADD (Cấp mới), REM (Trả lại/Thu hồi), CHANGE (Cập nhật trạng thái/thông tin)
     * @return array|null
     */
    public function sendFluctuationReport($domainId, $actionType)
    {
        // 1. Lọc thông tin tên miền
        $domain = Capsule::table('tbldomains')->where('id', $domainId)->first();
        if (!$domain) {
            return null;
        }

        // 2. Tìm kiếm thông tin liên quan của khách hàng
        $client = Capsule::table('tblclients')->where('id', $domain->userid)->first();
        if (!$client) {
            return null;
        }

        // 3. Mapping dữ liệu thành JSON Payload chuẩn quy định VNNIC-07
        // (Đây là JSON payload giả định - Sẽ cần phải điều chỉnh field name chính xác dựa vào API Doc chính thức của VNNIC).
        $payload = [
            'domainName' => $domain->domain,
            'action' => $actionType, 
            'registrationDate' => $domain->registrationdate,
            'expiryDate' => $domain->nextduedate,
            'status' => $domain->status,
            'registrant' => [
                'name' => trim($client->companyname ?: ($client->firstname . ' ' . $client->lastname)),
                'email' => $client->email,
                'phoneNumber' => $client->phonenumber,
                'address' => trim($client->address1 . ' ' . $client->address2),
                'city' => $client->city,
                // TODO: Truy vấn các custom field nếu bạn đã sử dụng Client Custom Field để lưu ID VNNIC ở Giai đoạn 2.
            ]
        ];

        // 4. Định hình lại Endpoint API
        $endpoint = '/reports/fluctuation-domains'; // URL này có thể là tuỳ theo version api/v1/...
        
        // 5. Submit qua phương thức ApiClient::request(), thao tác này tự động lưu lại trong bảng Log để tiện Track.
        return $this->api->request('POST', $endpoint, $payload, "VNNIC-07 ($actionType)", $domain->domain);
    }
}
