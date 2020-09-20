<?php

class Factory
{
    const TYPE_CONTROLLER = "controller";
    const TYPE_DATABASE = "database";
    const TYPE_HTML_PARSER = "htmlparser";
    const TYPE_ROUTER = "router";
    const TYPE_USER = "user";
    const TYPE_HTTP_PARSER = "httpparser";
    const TYPE_API_CONTROLLER = 'apicontroler';
    const TYPE_REQUEST = 'request';
    const TYPE_API_EXCEPTION = 'apiexception';
    const TYPE_VALIDATOR = 'validator';
    const TYPE_SESSION = "session";
    const TYPE_METHOD_MAPPING = array(
        self::TYPE_CONTROLLER => "getController",
        self::TYPE_DATABASE => "getDatabase",
        self::TYPE_HTML_PARSER => "getHtmlParser",
        self::TYPE_ROUTER => "getRouter",
        self::TYPE_USER => "getUser",
        self::TYPE_HTTP_PARSER => "getHttpParser",
        self::TYPE_API_CONTROLLER => 'getApiController',
        self::TYPE_REQUEST => 'getRequest',
        self::TYPE_API_EXCEPTION => 'getApiException',
        self::TYPE_VALIDATOR => 'getValidator',
        self::TYPE_SESSION => "getSession",
    );
    private static $instances = array();

    /**
     * @param string $type
     * @param bool $singleton
     * @return Controller|Database|HtmlParser|Router|User|ApiController|ApiException|Request|Validator
     */
    public static function getObject(string $type, bool $singleton=false) {
        if(!array_key_exists($type, self::TYPE_METHOD_MAPPING)) {
            return null;
        }
        if($singleton === true) {
            if(array_key_exists($type, self::$instances)) {
                return self::$instances[$type];
            } else {
                $object = call_user_func([new self(), self::TYPE_METHOD_MAPPING[$type]]);
                self::$instances[$type] = $object;
                return $object;
            }
        }

        return call_user_func([new self(), self::TYPE_METHOD_MAPPING[$type]]);
    }

    private function getController() {
        return new Controller($this->getRequest(), $this->getHtmlParser(), $this->getValidator(), $this->getUser(), $this->getSession());
    }

    private function getApiController() {
        return new ApiController($this->getRequest());
    }

    private function getDatabase() {
        return new Database();
    }

    private function getHtmlParser() {
        return new HtmlParser($this->getSession());
    }

    private function getRouter() {
        return new Router($this->getUser());
    }

    private function getUser() {
        return new User($this->getSession());
    }

    private function getHttpParser() {
        return new HttpParser();
    }

    private function getRequest() {
        return new Request();
    }

    private function getApiException() {
        return new ApiException();
    }

    private function getValidator() {
        return new Validator($this->getRequest(), $this->getHtmlParser());
    }

    private function getSession() {
        return new Session();
    }
}