<?php

namespace W2w\\Vnnic\Lib;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class Auth
{
    private $clientId;
    private $clientSecret;

    public function __construct($clientId, $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    /**
     * Tạo chuỗi Base64 cho header Basic Authentication
     *
     * @return string
     */
    public function getBasicAuthString()
    {
        $credentials = $this->clientId . ':' . $this->clientSecret;
        return 'Basic ' . base64_encode($credentials);
    }
}
