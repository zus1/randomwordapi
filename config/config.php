<?php

class Config
{
    const ADMIN_HOME = 'admin_home';
    const USER_HOME = 'user_home';
    const DB_USERNAME = 'db_username';
    const DB_PASSWORD = 'db_password';
    const DB_NAME = 'db_name';
    const DB_HOST = 'db_host';
    const DB_CHARSET = 'db_charset';
    private static $_initialized = false;
    private static $_configArray = array(
        self::ADMIN_HOME => "",
        self::USER_HOME => "",
        self::DB_USERNAME => "",
        self::DB_PASSWORD => "",
        self::DB_NAME => "",
        self::DB_HOST => "",
        self::DB_CHARSET => ""
    );

    private static function init() {
        $initFile = HttpParser::root() . "/init.ini";
        if(!file_exists($initFile)) {
            throw new Exception("No init file", HttpCodes::INTERNAL_SERVER_ERROR);
        }
        $initVariables = parse_ini_file($initFile);
        self::$_configArray[self::DB_USERNAME] = $initVariables['DB_USERNAME'];
        self::$_configArray[self::DB_PASSWORD] = $initVariables["DB_PASSWORD"];
        self::$_configArray[self::DB_HOST] = $initVariables["DB_HOST"];
        self::$_configArray[self::DB_NAME] = $initVariables["DB_NAME"];
        self::$_configArray[self::DB_CHARSET] = $initVariables["DB_CHARSET"];
        self::$_configArray[self::ADMIN_HOME] = HttpParser::baseUrl() . "views/adm/home.php";
        self::$_configArray[self::USER_HOME] = HttpParser::baseUrl() . "views/documentation.php";

        self::$_initialized = true;
    }

    private static function setConfig(string $key, $value) {
        self::$_configArray[$key] = $value;
    }

    public static function get(string $key, $default=null) {
        if(self::$_initialized === false) {
            self::init();
        }
        if(isset(self::$_configArray[$key]) && !empty(self::$_configArray[$key])) {
            return self::$_configArray[$key];
        }

        return $default;
    }
}