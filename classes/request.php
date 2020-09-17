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

    public function input($key, $default) {
        if(isset($this->requestVars[$key])) {
            return $this->requestVars[$key];
        }

        return $default;
    }
}