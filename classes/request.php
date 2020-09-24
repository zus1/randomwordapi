<?php

class Request
{
    private $requestVars = array();

    public function __construct() {
        array_walk($_REQUEST, function($value, $key) {
           $this->requestVars[$key] = $value;
        });
    }

    public function __get($name) {
        return $this->requestVars[$name];
    }

    public function input($key, ?string $default="") {
        if(isset($this->requestVars[$key])) {
            return $this->requestVars[$key];
        }

        return $default;
    }

    public function file($key) {
        $file = null;
        if(!empty($_FILES) && isset($_FILES[$key])) {
            $file = $_FILES[$key];
        }

        if(is_null($file)) {
            return array();
        }

        if(!is_array($file['name'])) {
            if($file["error"] === UPLOAD_ERR_NO_FILE ) {
                return array();
            }
            return $file;
        }

        //TODO handle dealing with multiple file uploads
        return array();
    }
}