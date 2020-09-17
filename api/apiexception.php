<?php

class ApiException
{
    public function getApiException(Exception $e) {
        http_response_code($e->getCode());
        return json_encode(array("error" => 1, "message" => $e->getMessage()));
    }
}