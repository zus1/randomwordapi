<?php

class Request
{
    private $session;

    private $requestVars = array();
    private static $_requestLoaded = false;

    public function __construct(Session $session) {
        $this->session = $session;
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

    public function getAll() {
        return $this->requestVars;
    }

    public function getHeaders() {
        return getallheaders();
    }

    public function getHeader(string $key, $default=null) {
        $allHeaders = $this->getHeaders();
        if(array_key_exists($key, $allHeaders)) {
            return $allHeaders[$key];
        }

        return ($default !== null)? $default : "";
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

    public function getParsedRequestUrl() {
        return parse_url(strtolower($_SERVER["REQUEST_URI"]));
    }

    public function getParsedRequestQuery(array $output) {
        $parsedUrl = $this->getParsedRequestUrl();
        if(!$parsedUrl || !isset($parsedUrl["query"])) {
            return $output;
        }

         parse_str($parsedUrl["query"], $output);

        return $output;
    }

    public function getRequestPath() {
        $parsedUrl = $this->getParsedRequestUrl();
        if(!$parsedUrl || !isset($parsedUrl["path"])) {
            return "";
        }

        return $parsedUrl["path"];
    }
}