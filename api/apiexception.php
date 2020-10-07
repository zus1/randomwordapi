<?php

class ApiException
{
    public function getApiException(Exception $e) {
        http_response_code($e->getCode());
        $this->addExceptionHeaders();
        return json_encode(array("error" => 1, "message" => $e->getMessage()));
    }

    private function addExceptionHeaders() {
        header("Content-type: application/json");
    }
}