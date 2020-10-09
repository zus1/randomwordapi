<?php

class Config
{
    const ADMIN_HOME = 'ADMIN_HOME';
    const USER_HOME = 'USER_HOME';
    const DB_USERNAME = 'DB_USERNAME';
    const DB_PASSWORD = 'DB_PASSWORD';
    const DB_NAME = 'DB_NAME';
    const DB_HOST = 'DB_HOST';
    const DB_CHARSET = 'DB_CHARSET';
    const API_VERSION = 'API_VERSION';
    const API_MAX_WORD_LENGTH = 'API_MAX_WORD_LENGTH';
    const API_MAX_WORDS = 'API_MAX_WORDS';
    const API_MIN_WORD_LENGTH = "API_MIN_WORD_LENGTH";
    const API_MIN_WORDS = "API_MIN_WORDS";
    const LOCAL_DEFAULT = "LOCAL_DEFAULT";
    const TRANSLATION_DEFAULT = "TRANSLATION_DEFAULT";
    const API_ACCESS_TOKEN_PARTS = "API_ACCESS_TOKEN_PARTS";
    const API_ACCESS_TOKEN_PART_SIZE = "API_ACCESS_TOKEN_PART_SIZE";
    const API_DEFAULT_RATE_LIMIT = "API_DEFAULT_RATE_LIMIT";
    const API_RATE_LIMIT_TIME_RANGE = "API_RATE_LIMIT_TIME_RANGE";
    const API_MAX_NUM_APPS = "API_MAX_NUM_APPS";
    const API_AUTHORIZATION_HEADER = "API_AUTHORIZATION_HEADER";
    const API_SOFT_BAN_INTERVAL = "API_SOFT_BAN_INTERVAL";
    const RULE_CHECK_NUM_REQUESTS_IN_PERIOD_ACTIVE = "RULE_CHECK_NUM_REQUESTS_IN_PERIOD_ACTIVE";
    const RULE_PERIOD_FOR_NUM_REQUESTS = "RULE_PERIOD_FOR_NUM_REQUESTS";
    const RULE_NUM_REQUESTS = "RULE_NUM_REQUESTS";
    const API_MAX_SOFT_BANS = "API_MAX_SOFT_BANS";
    private static $_initialized = false;
    private static $_configArray = array();

    private static function init() {
        $initFile = HttpParser::root() . "/init.ini";
        if(!file_exists($initFile)) {
            throw new Exception("No init file", HttpCodes::INTERNAL_SERVER_ERROR);
        }
        $initVariables = parse_ini_file($initFile);

        $iniConfig = array();
        foreach($initVariables as $key => $value) {
            $iniConfig[$key] = $value;
        }
        $extraConfig = self::getExtraConfig();

        self::$_configArray = array_merge($iniConfig, $extraConfig);
        self::$_initialized = true;
    }

    public static function getExtraConfig() {
        return array(
            "ADMIN_HOME" => HttpParser::baseUrl() . "views/adm/home.php",
            "USER_HOME" => HttpParser::baseUrl() . "views/documentation.php",
        );
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