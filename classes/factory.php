<?php

/*include_once($_SERVER['DOCUMENT_ROOT'] . "/classes/controller.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/classes/database.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/classes/htmlparser.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/classes/router.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/classes/user.php");*/

class Factory
{
    const TYPE_CONTROLLER = "controller";
    const TYPE_DATABASE = "database";
    const TYPE_HTML_PARSER = "htmlparser";
    const TYPE_ROUTER = "router";
    const TYPE_USER = "user";
    const TYPE_HTTP_PARSER = "httpparser";
    const TYPE_METHOD_MAPPING = array(
        self::TYPE_CONTROLLER => "getController",
        self::TYPE_DATABASE => "getDatabase",
        self::TYPE_HTML_PARSER => "getHtmlParser",
        self::TYPE_ROUTER => "getRouter",
        self::TYPE_USER => "getUser",
        self::TYPE_HTTP_PARSER => "getHttpParser",
    );
    private static $instances = array();

    /**
     * @param string $type
     * @param bool $singleton
     * @return Controller|Database|HtmlParser|Router|User
     */
    public static function getObject(string $type, bool $singleton=false) {
        if(!array_key_exists($type, self::TYPE_METHOD_MAPPING)) {
            return null;
        }
        if($singleton === true) {
            if(array_key_exists($type, self::$instances)) {
                return self::$instances[$type];
            } else {
                $object = call_user_func([Factory::class, self::TYPE_METHOD_MAPPING[$type]]);
                self::$instances[$type] = $object;
                return $object;
            }
        }

        return call_user_func([Factory::class, self::TYPE_METHOD_MAPPING[$type]]);
    }

    private function getController() {
        return new Controller();
    }

    private function getDatabase() {
        return new Database();
    }

    private function getHtmlParser() {
        return new HtmlParser();
    }

    private function getRouter() {
        return new Router();
    }

    private function getUser() {
        return new User();
    }

    private function getHttpParser() {
        return new HttpParser();
    }
}