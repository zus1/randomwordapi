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
    const API_VERSION = 'api_version';
    const API_MAX_WORD_LENGTH = 'api_max_word_length';
    const API_MAX_WORDS = 'api_max_words';
    const API_MIN_WORD_LENGTH = "min_word_length";
    const API_MIN_WORDS = "api_min_words";
    const LOCAL_DEFAULT = "local_default";
    const TRANSLATION_DEFAULT = "translation_default";
    private static $_initialized = false;
    private static $_configArray = array(
        self::ADMIN_HOME => "",
        self::USER_HOME => "",
        self::DB_USERNAME => "",
        self::DB_PASSWORD => "",
        self::DB_NAME => "",
        self::DB_HOST => "",
        self::DB_CHARSET => "",
        self::API_VERSION => "",
        self::API_MAX_WORD_LENGTH => "",
        self::API_MAX_WORDS => "",
        self::API_MIN_WORD_LENGTH => "",
        self::API_MIN_WORDS => "",
        self::LOCAL_DEFAULT => "",
        self::TRANSLATION_DEFAULT => "",
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
        self::$_configArray[self::API_VERSION] = $initVariables["API_VERSION"];
        self::$_configArray[self::API_MAX_WORD_LENGTH] = $initVariables["API_MAX_WORD_LENGTH"];
        self::$_configArray[self::API_MAX_WORDS] = $initVariables["API_MAX_WORDS"];
        self::$_configArray[self::API_MIN_WORD_LENGTH] = $initVariables["API_MIN_WORD_LENGTH"];
        self::$_configArray[self::API_MIN_WORDS] = $initVariables["API_MIN_WORDS"];
        self::$_configArray[self::LOCAL_DEFAULT] = $initVariables["LOCAL_DEFAULT"];
        self::$_configArray[self::TRANSLATION_DEFAULT] = $initVariables["TRANSLATION_DEFAULT"];

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