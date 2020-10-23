<?php


class Cookie
{
    const DISCLAIMER_ALL = 1;
    const DISCLAIMER_NECESSARY = 2;
    const DISCLAIMER_DECLINE = 3;

    const DISCLAIMER_COOKIE_KEY = "cookies_ok";
    const USER_REMEMBER_ME_COOKIE_KEY = "remember_me";

    private $request;
    private $cookieVars = array();

    private $necessaryCookies = array();

    public function __construct(Request $request) {
        $this->request = $request;
        if(!empty($_COOKIE)) {
            array_walk($_COOKIE, function ($value, $key)  {
                $this->cookieVars[$key] = $value;
            });
        }
        $this->necessaryCookies = explode(",", Config::get(Config::COOKIE_NECESSARY));
    }

    public function getModel() {
        return Factory::getModel(Factory::MODEL_COOKIE);
    }

    public function getDisclaimerDeclineRedirectUrl() {
        return Config::get(Config::COOKIE_DISCLAIMER_DECLINE_REDIRECT_URL, "https://www.google.com");
    }

    private function getCookieSettings() {
        return array(
            self::DISCLAIMER_COOKIE_KEY => array(
                "expires" => (int)Config::get(Config::COOKIE_DISCLAIMER_EXPIRES_DAYS, 20),
                "path" => "/",
                "http_only" => 0,
                "secure" => 0,
                "domain" => "." . $_SERVER["SERVER_NAME"],
            ),
            self::USER_REMEMBER_ME_COOKIE_KEY => array(
                "expires" => (int)Config::get(Config::COOKIE_REMEMBER_ME_EXPIRES_DAYS, 20),
                "path" => "/",
                "http_only" => 1,
                "secure" => 0,
                "domain" => "." . $_SERVER["SERVER_NAME"],
            ),
        );
    }

    private function doGetCookieSettings(string $key) {
        $cookieSettings = $this->getCookieSettings();
        if(!array_key_exists($key, $cookieSettings)) {
            throw new Exception("Unknown cookie", HttpCodes::INTERNAL_SERVER_ERROR);
        }

        return $cookieSettings[$key];
    }

    private function allowedDisclaimerStates() {
        return array(self::DISCLAIMER_NECESSARY, self::DISCLAIMER_ALL, self::DISCLAIMER_DECLINE);
    }

    public function setCookie(string $key, $value) {
        $cookieSettings = $this->doGetCookieSettings($key);
        $expire = time() + $cookieSettings["expires"]*24*60*60;
        setcookie($key, $value, $expire, $cookieSettings["path"], $cookieSettings["domain"], $cookieSettings["secure"], $cookieSettings["http_only"]);
    }

    public function getCookie(string $key, ?string $default="") {
        if(array_key_exists($key, $this->cookieVars)) {
            return $this->cookieVars[$key];
        }
        return $default;
    }

    public function removeCookie(string $key) {
        if(isset($_COOKIE[$key])) {
            $cookieSettings = $this->doGetCookieSettings($key);
            setcookie($key, "", time() - 3600, $cookieSettings["path"], $cookieSettings["domain"], $cookieSettings["secure"], $cookieSettings["http_only"]);
        }
    }

    public function doDisclaimerAction(int $action) {
        if($action === self::DISCLAIMER_ALL) {
            $this->onAcceptAll();
        } elseif($action === self::DISCLAIMER_NECESSARY) {
            $this->onAcceptNecessary();
        } elseif($action === self::DISCLAIMER_DECLINE) {
            $this->onDecline();
        } else {
            throw new Exception("Unknown cookie disclaimer action", HttpCodes::HTTP_BAD_REQUEST);
        }
    }

    private function onAcceptAll() {
        $this->addDisclaimerData(self::DISCLAIMER_ALL);
        $this->setCookie(self::DISCLAIMER_COOKIE_KEY, self::DISCLAIMER_ALL);
    }

    private function onAcceptNecessary() {
        $this->addDisclaimerData(self::DISCLAIMER_NECESSARY);
        $this->setCookie(self::DISCLAIMER_COOKIE_KEY, self::DISCLAIMER_NECESSARY);
        foreach($this->cookieVars as $key => $value) {
            if(!in_array($key, $this->necessaryCookies)) {
                $this->removeCookie($key);
            }
        }
    }

    private function onDecline() {
        Factory::getObject(Factory::TYPE_ROUTER)->redirect("www.google.com"); //for now lets just redirect user if he decline cookies
    }

    public function isDisclaimerState(int $state) {
        if(!in_array($state, $this->allowedDisclaimerStates())) {
            throw new Exception("Unknown cookie disclaimer state");
        }
        $cookieState = (int)$this->getCookie(self::DISCLAIMER_COOKIE_KEY);
        if($cookieState === "") {
            return true; //if user did not accept/decline cookies, business as usual
        }
        if($cookieState !== $state) {
            return false;
        }

        return true;
    }

    private function addDisclaimerData(int $accepted) {
        $ip = $this->request->getRequestIp();
        $this->getModel()->insert(array(
           "ip" => $ip,
           "accepted_at" => date("Y-m-d H:i:s"),
            "accepted" => $accepted
        ));
    }
}