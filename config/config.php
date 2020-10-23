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
    const DB_CONNECTION = "DB_CONNECTION";
    const DB_PORT = "DB_PORT";
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
    const EMAIL_SMTP_SERVER = "EMAIL_SMTP_SERVER";
    const EMAIL_USERNAME = "EMAIL_USERNAME";
    const EMAIL_PASSWORD = "EMAIL_PASSWORD";
    const EMAIL_PORT = "EMAIL_PORT";
    const EMAIL_ENCRIPTION = "EMAIL_ENCRIPTION";
    const VERIFICATION_TOKEN_SIZE = "VERIFICATION_TOKEN_SIZE";
    const PASSWORD_RESET_TOKEN_SIZE = "PASSWORD_RESET_TOKEN_SIZE";
    const CAPTCHA_SIZE = "CAPTCHA_SIZE";
    const VERIFICATION_TOKEN_EXPIRES = "VERIFICATION_TOKEN_EXPIRES";
    const PASSWORD_RESET_TOKEN_EXPIRES = "PASSWORD_RESET_TOKEN_EXPIRES";
    const EMAIL_SMTP = "EMAIL_SMTP";
    const UUID_SIZE = "UUID_SIZE";
    const COOKIE_DISCLAIMER_ON = "COOKIE_DISCLAIMER_ON";
    const COOKIE_DISCLAIMER_EXPIRES_DAYS = "COOKIE_DISCLAIMER_EXPIRES_DAYS";
    const COOKIE_REMEMBER_ME_EXPIRES_DAYS = "COOKIE_REMEMBER_ME_EXPIRES_DAYS";
    const COOKIE_NECESSARY = "COOKIE_NECESSARY";
    const COOKIE_DISCLAIMER_DECLINE_REDIRECT_URL = "COOKIE_DISCLAIMER_DECLINE_REDIRECT_URL";

    private static $_initialized = false;
    private static $_configArray = array();
    private static $_typeInit = "ini";
    private static $_typeEnv = "env";

    private static function getAvailableConfigFileTypes()  {
        return array(self::$_typeInit, self::$_typeEnv);
    }

    private static function getFileTypeToMethodMapping() {
        return array(
            self::$_typeEnv => "loadConfigFromEnv",
            self::$_typeInit => "loadConfigFromIni",
        );
    }

    public static function init(?string $configFile="") {
        list($configFile, $extension) = self::getConfigFile($configFile);

        $configArray = call_user_func_array(["Config", self::getFileTypeToMethodMapping()[$extension]], array($configFile));
        $extraConfig = self::getExtraConfig();

        self::$_configArray = array_merge($configArray, $extraConfig);
        self::$_initialized = true;
    }

    private static function getConfigFile(string $configFile) {
        $extension = "";
        if($configFile !== "") {
            if(!file_exists($configFile)) {
                throw new Exception("Config file not found");
            }
            $extension = explode(".", $configFile)[1];
            if(!in_array($extension, self::getAvailableConfigFileTypes())) {
                throw new Exception("Unsupported config file type");
            }
        } else {
            foreach(self::getAvailableConfigFileTypes() as $fileType) {
                if($fileType === "env") {
                    $fullPath = HttpParser::root() . "/." . $fileType;
                } else {
                    $fullPath = HttpParser::root() . "/init." . $fileType;
                }
                if(file_exists($fullPath)) {
                    $extension = $fileType;
                    $configFile = $fullPath;
                    break;
                }
            }
        }
        if($configFile === "") {
            throw new Exception("Unsupported config file type");
        }

        return array($configFile, $extension);
    }

    private static function loadConfigFromIni(string $iniFile) {
        $initVariables = parse_ini_file($iniFile);

        $iniConfig = array();
        foreach($initVariables as $key => $value) {
            $iniConfig[$key] = $value;
        }

        return $iniConfig;
    }

    private static function loadConfigFromEnv(string $envFile) {
        $envConfig = array();
        $envContents = file_get_contents($envFile);
        if($envContents && $envContents !== "") {
            $envContentsArray = preg_split("/\n|\r\n/", $envContents);
            array_walk($envContentsArray, function($value) use(&$envConfig) {
                $value = trim($value);
                if($value !== "") {
                    if(!strpos($value, "=")) {
                        throw new Exception("Env file malformed");
                    }
                    $envLineParts = explode("=", $value);
                    if(count($envLineParts) !== 2) {
                        throw new Exception("Env file malformed");
                    }
                    $envConfig[$envLineParts[0]] = (is_int($envLineParts[1]))? (int)$envLineParts[1] : $envLineParts[1];
                }
            });
        }

        return $envConfig;
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