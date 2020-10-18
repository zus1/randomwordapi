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
    const TYPE_WORDS = "words";
    const TYPE_API_VALIDATOR = 'api_validator';
    const TYPE_WORDS_BULK = "words_bulk";
    const TYPE_WORDS_JSON = "words_json";
    const TYPE_WORDS_CSV = "words_csv";
    const TYPE_RESPONSE = "response";
    const TYPE_LOCALIZATION = 'localization';
    const TYPE_GUARDIAN = "guardian";
    const TYPE_CMS = "cms";
    const TYPE_WEB = "web";
    const TYPE_TRANSLATOR = 'translator';
    const TYPE_JSON_PARSER = "json-parser";
    const TYPE_API_GUARDIAN = "api-guardian";
    const TYPE_API_CONTROLLER_INTERNAL = 'api-controller-internal';
    const TYPE_API_USER  = "api-user";
    const TYPE_API_APP = "api-app";
    const TYPE_DATE_HANDLER = "date-handler";
    const TYPE_IP_CHECKER = "ip_checker";
    const TYPE_MAIL = "mail";
    const TYPE_RESET_PASSWORD_MAIL = "reset-password-mail";
    const TYPE_ACCOUNT_VERIFICATION_MAIL = "account-verification-mail";
    const TYPE_USER_TOKEN = "user-token";
    const TYPE_INIT = "init";
    const TYPE_COOKIE = "cookie";

    const EXTENDER_HTML_PARSER = "extender_html_parser";

    const MODEL_IP_CHECKER = "model-ip-checker";
    const MODEL_USER = "model_user";
    const MODEL_USER_TOKEN = "model-user-token";
    const MODEL_COOKIE = "model-cookie";

    const LIBRARY_PHP_MAILER = 'library-php-mailer';

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
        self::TYPE_WORDS => "getWords",
        self::TYPE_API_VALIDATOR => 'getApiValidator',
        self::TYPE_WORDS_BULK => "getWordsBulk",
        self::TYPE_WORDS_CSV => "getWordsCsv",
        self::TYPE_WORDS_JSON => "getWordsJson",
        self::TYPE_RESPONSE => "getResponse",
        self::TYPE_LOCALIZATION => "getLocalization",
        self::TYPE_GUARDIAN => "getGuardian",
        self::TYPE_CMS => "getCms",
        self::TYPE_WEB => "getWeb",
        self::TYPE_TRANSLATOR => "getTranslator",
        self::TYPE_JSON_PARSER => "getJsonParser",
        self::TYPE_API_GUARDIAN => "getApiGuardian",
        self::TYPE_API_CONTROLLER_INTERNAL => "getApiControllerInternal",
        self::TYPE_API_USER => "getApiUser",
        self::TYPE_API_APP => "getApiApp",
        self::TYPE_DATE_HANDLER => "getDateHandler",
        self::TYPE_IP_CHECKER => "getIpChecker",
        self::TYPE_MAIL => "getMail",
        self::TYPE_RESET_PASSWORD_MAIL => "getResetPasswordMail",
        self::TYPE_ACCOUNT_VERIFICATION_MAIL => "getAccountVerificationMail",
        self::TYPE_USER_TOKEN => "getUserToken",
        self::TYPE_INIT => "getInit",
        self::TYPE_COOKIE => "getCookie",
    );
    const EXTENDER_METHOD_MAPPING = array(
        self::EXTENDER_HTML_PARSER => "getExtenderHtmlParser",
    );
    const MODEL_TO_METHOD_MAPPING = array(
        self::MODEL_IP_CHECKER => "getModelIpChecker",
        self::MODEL_USER => "getModelUser",
        self::MODEL_USER_TOKEN => "getModelUserToken",
        self::MODEL_COOKIE => "getModelCookie",
    );
    const LIBRARY_TO_TYPE_MAPPING = array(
        self::LIBRARY_PHP_MAILER => "getLibraryPhpMailer",
    );
    private static $instances = array();

    /**
     * @param string $type
     * @param bool $singleton
     * @return Controller|Database|HtmlParser|Router|User|ApiController|ApiException|Request|Validator|Words|WordsBulk|WordsJson|WordsCsv|Response|Localization|Translator|Web|ApiGuardian|ApiApp|DateHandler|IpChecker|AccountVerificationMail|ResetPasswordMail|Init|Cookie
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

    /**
     * @param string $extenderType
     * @return HtmlParserExtender
     */
    public static function getExtender(string $extenderType) {
        if(!array_key_exists($extenderType, self::EXTENDER_METHOD_MAPPING)) {
            return null;
        }
        if(!isset(self::$instances[$extenderType])) {
            $object = call_user_func([new self(), self::EXTENDER_METHOD_MAPPING[$extenderType]]);
            self::$instances[$extenderType] = $object;
        }

        return self::$instances[$extenderType];
    }

    /**
     * @param string $modelType
     * @return IpCheckerModel
     */
    public static function getModel(string $modelType) {
        if(!array_key_exists($modelType, self::MODEL_TO_METHOD_MAPPING)) {
            return null;
        }
        if(!isset(self::$instances[$modelType])) {
            $object = call_user_func([new self(), self::MODEL_TO_METHOD_MAPPING[$modelType]]);
            self::$instances[$modelType] = $object;
        }

        return self::$instances[$modelType];
    }

    /**
     * @param string $libraryType
     * @return PHPMailer
     */
    public static function getLibrary(string $libraryType) {
        if(!array_key_exists($libraryType, self::LIBRARY_TO_TYPE_MAPPING)) {
            return null;
        }

        return call_user_func([new self(), self::LIBRARY_TO_TYPE_MAPPING[$libraryType]]);
    }

    private function getModelCookie() {
        return new CookieModel($this->getValidator());
    }

    private function getCookie() {
        return new Cookie($this->getRequest());
    }

    private function getInit() {
        return new Init($this->getCookie(), $this->getSession());
    }

    private function getUserToken() {
        return new UserToken($this->getGuardian(), $this->getDateHandler());
    }

    private function getModelUserToken() {
        return new UserTokenModel($this->getValidator());
    }

    private function getResetPasswordMail() {
        return new ResetPasswordMail($this->getGuardian(), $this->getWeb(), $this->getUserToken());
    }

    private function getAccountVerificationMail() {
        return new AccountVerificationMail($this->getGuardian(), $this->getWeb(), $this->getUserToken());
    }

    private function getMail() {
        return new Mail($this->getGuardian(), $this->getWeb(), $this->getUserToken());
    }

    private function getLibraryPhpMailer() {
        return new PHPMailer();
    }

    private function getModelUser() {
        return new UserModel($this->getValidator());
    }

    private function getModelIpChecker() {
        return new IpCheckerModel($this->getValidator());
    }

    private function getController() {
        return new Controller($this->getRequest(), $this->getHtmlParser(), $this->getValidator(), $this->getUser(), $this->getSession(), $this->getResponse(), $this->getLocalization(), $this->getCms(), $this->getWeb(), $this->getGuardian(), $this->getUserToken(), $this->getCookie());
    }

    private function getApiController() {
        return new ApiController($this->getRequest(), $this->getApiValidator(), $this->getWords(), $this->getApiApp(), $this->getApiGuardian(), $this->getIpChecker());
    }

    private function getDatabase() {
        return new Database();
    }

    private function getHtmlParser() {
        return new HtmlParser($this->getSession(), $this->getRequest(), $this->getGuardian(), $this->getExtenderHtmlParser());
    }

    private function getRouter() {
        return new Router($this->getUser(), $this->getGuardian());
    }

    private function getUser() {
        return new User($this->getSession(), $this->getUserToken(), $this->getGuardian(), $this->getCookie());
    }

    private function getHttpParser() {
        return new HttpParser();
    }

    private function getRequest() {
        return new Request($this->getSession());
    }

    private function getApiException() {
        return new ApiException($this->getApiApp());
    }

    private function getValidator() {
        return new Validator($this->getRequest(), $this->getHtmlParser());
    }

    private function getSession() {
        return new Session();
    }

    private function getWords() {
        return new Words($this->getValidator());
    }

    private function getApiValidator() {
        return new ApiValidator($this->getRequest(), $this->getHtmlParser());
    }

    private function getWordsBulk() {
        return new WordsBulk($this->getValidator());
    }

    private function getWordsCsv() {
        return new WordsCsv($this->getValidator());
    }

    private function getWordsJson() {
        return new WordsJson($this->getValidator());
    }

    private function getResponse() {
        return new Response($this->getSession(), $this->getHtmlParser(), $this->getRequest());
    }

    private function getLocalization() {
        return new Localization($this->getSession(), $this->getUser());
    }

    private function getGuardian() {
        return new Guardian($this->getSession(), $this->getRequest(), $this->getDateHandler());
    }

    private function getCms() {
        return new Cms($this->getValidator());
    }

    private function getWeb() {
        return new Web($this->getLocalization(), $this->getCms());
    }

    private function getExtenderHtmlParser() {
        return new HtmlParserExtender();
    }

    private function getTranslator() {
        return new Translator($this->getJsonParser());
    }

    private function getJsonParser() {
        return new JsonParser();
    }

    private function getApiGuardian() {
        return new ApiGuardian($this->getSession(), $this->getRequest(), $this->getDateHandler());
    }

    private function getApiControllerInternal() {
        return new ApiControllerInternal($this->getRequest(), $this->getApiValidator(), $this->getApiApp(), $this->getApiUser(), $this->getHtmlParser());
    }

    private function getApiUser() {
        return new ApiUser($this->getSession(), $this->getUserToken(), $this->getGuardian(), $this->getCookie());
    }

    private function getApiApp() {
        return new ApiApp($this->getApiValidator(), $this->getApiGuardian(), $this->getApiUser(), $this->getRequest(), $this->getDateHandler());
    }

    public function getDateHandler() {
        return new DateHandler();
    }

    public function getIpChecker() {
        return new IpChecker($this->getApiApp(), $this->getRequest(), $this->getApiValidator(), $this->getDateHandler(), $this->getApiGuardian());
    }
}