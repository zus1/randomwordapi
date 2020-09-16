<?php

class HttpParser
{
    public static function baseUrl() {
        $https = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off")? "https" : "http";
        $server = $_SERVER["SERVER_NAME"];
        return sprintf("%s://%s/", $https, $server);
    }

    public static function root() {
        return $_SERVER["DOCUMENT_ROOT"];
    }
}