<?php

namespace HiTechCloud\\Vnnic\Lib;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use WHMCS\Database\Capsule;

class ApiClient
{
    private $client;
    private $auth;
    private $baseUrl;

    const OTE_URL = 'https://gtldapi-ote.vnnic.vn/v1'; // Sẽ cần cập nhật endppoint chính xác dựa trên API Doc
    const PROD_URL = 'https://gtld-api.vnnic.vn/v1'; // Sẽ cần cập nhật endppoint chính xác

    public function __construct()
    {
        $moduleParams = $this->getModuleParams();
        
        $env = isset($moduleParams['environment']) ? $moduleParams['environment'] : 'ote';
        $this->baseUrl = ($env === 'production') ? self::PROD_URL : self::OTE_URL;

        $clientId = isset($moduleParams['client_id']) ? $moduleParams['client_id'] : '';
        $clientSecret = isset($moduleParams['client_secret']) ? $moduleParams['client_secret'] : '';

        $this->auth = new Auth($clientId, $clientSecret);

        // Khởi tạo Guzzle Client tương thích PHP 8.1
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30.0,
        ]);
    }

    /**
     * Gửi HTTP Request
     *
     * @param string $method POST, GET, v.v.
     * @param string $endpoint Điểm cuối API (vd: /locations/cities)
     * @param array $payload Dữ liệu JSON (đối với POST/PUT)
     * @param string|null $actionName Tên action để log
     * @param string|null $domain Tên miền gắn với log này
     * @return array|null Trả về JSON được decode
     */
    public function request($method, $endpoint, $payload = [], $actionName = 'API Call', $domain = null)
    {
        $options = [
            'headers' => [
                'Authorization' => $this->auth->getBasicAuthString(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'http_errors' => false // Bỏ qua exception để bắt status code thủ công
        ];

        if (!empty($payload)) {
            $options['json'] = $payload;
        }

        try {
            $response = $this->client->request($method, $endpoint, $options);
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            $this->logActivity($actionName, $endpoint, $payload, $body, $statusCode, $domain);

            return json_decode($body, true);

        } catch (RequestException $e) {
            $this->logActivity($actionName, $endpoint, $payload, $e->getMessage(), 500, $domain);
            return null;
        }
    }

    /**
     * Lấy cấu hình của module đang lưu trong WHMCS
     */
    private function getModuleParams()
    {
        $params = [];
        $settings = Capsule::table('tbladdonmodules')
            ->where('module', 'vnnic_tldms')
            ->get();
            
        foreach ($settings as $setting) {
            $params[$setting->setting] = $setting->value;
        }
        return $params;
    }

    /**
     * Ghi log vào cơ sở dữ liệu
     */
    private function logActivity($action, $endpoint, $payload, $responseBody, $statusCode, $domain = null)
    {
        Capsule::table('mod_vnnic_tldms_logs')->insert([
            'action' => $action,
            'endpoint' => $endpoint,
            'request_payload' => is_array($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : $payload,
            'response_body' => is_string($responseBody) ? $responseBody : json_encode($responseBody, JSON_UNESCAPED_UNICODE),
            'status_code' => $statusCode,
            'domain_name' => $domain,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
