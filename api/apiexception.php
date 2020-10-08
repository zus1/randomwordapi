<?php

class ApiException
{
    private $apiApp;

    public function __construct(ApiApp $app) {
        $this->apiApp = $app;
    }

    public function getApiException(Exception $e) {
        http_response_code($e->getCode());
        $this->apiApp->addResponseHeaders();
        return json_encode(array("error" => 1, "message" => $e->getMessage()));
    }
}