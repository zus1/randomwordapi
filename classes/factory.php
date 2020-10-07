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
    const EXTENDER_HTML_PARSER = "extender_html_parser";
    const TYPE_TRANSLATOR = 'translator';
    const TYPE_JSON_PARSER = "json-parser";
    const TYPE_API_GUARDIAN = "api-guardian";
    const TYPE_API_CONTROLLER_INTERNAL = 'api-controller-internal';
    const TYPE_API_USER  = "api-user";
    const TYPE_API_APP = "api-app";
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
    );
    const EXTENDER_METHOD_MAPPING = array(
        self::EXTENDER_HTML_PARSER => "getExtenderHtmlParser",
    );
    private static $instances = array();

    /**
     * @param string $type
     * @param bool $singleton
     * @return Controller|Database|HtmlParser|Router|User|ApiController|ApiException|Request|Validator|Words|WordsBulk|WordsJson|WordsCsv|Response|Localization|Translator|Web|ApiGuardian|ApiApp
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
        if(!in_array($extenderType, self::EXTENDER_METHOD_MAPPING)) {
            return null;
        }
        if(!isset(self::$instances[$extenderType])) {
            $object = call_user_func([new self(), self::EXTENDER_METHOD_MAPPING[$extenderType]]);
            self::$instances[$extenderType] = $object;
        }

        return self::$instances[$extenderType];
    }

    private function getController() {
        return new Controller($this->getRequest(), $this->getHtmlParser(), $this->getValidator(), $this->getUser(), $this->getSession(), $this->getResponse(), $this->getLocalization(), $this->getCms(), $this->getWeb());
    }

    private function getApiController() {
        return new ApiController($this->getRequest(), $this->getApiValidator(), $this->getWords());
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
        return new User($this->getSession());
    }

    private function getHttpParser() {
        return new HttpParser();
    }

    private function getRequest() {
        return new Request($this->getSession());
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
        return new Guardian($this->getSession(), $this->getUser(), $this->getRequest());
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
        return new ApiGuardian($this->getSession(), $this->getUser(), $this->getRequest());
    }

    private function getApiControllerInternal() {
        return new ApiControllerInternal($this->getRequest(), $this->getApiValidator(), $this->getApiApp(), $this->getApiUser(), $this->getHtmlParser());
    }

    private function getApiUser() {
        return new ApiUser($this->getSession());
    }

    private function getApiApp() {
        return new ApiApp($this->getApiValidator());
    }
}