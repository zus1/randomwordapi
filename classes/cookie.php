<?php


class Cookie
{
    const DISCLAIMER_ALL = 1;
    const DISCLAIMER_NECESSARY = 2;
    const DISCLAIMER_DECLINE = 0;
    const DISCLAIMER_COOKIE_KEY = "cookies_ok";

    private $request;
    private $cookieVars;

    public function __construct(Request $request) {
        $this->request = $request;
        if(!empty($_COOKIE)) {
            array_walk($_COOKIE, function ($value, $key)  {
                $this->cookieVars[$key] = $value;
            });
        }
    }

    public function getModel() {
        return Factory::getModel(Factory::MODEL_COOKIE);
    }

    private function allowedDisclaimerStates() {
        return array(self::DISCLAIMER_NECESSARY, self::DISCLAIMER_ALL, self::DISCLAIMER_DECLINE);
    }

    public function setCookie(string $key, $value, int $expire, ?string $path="/", ?string $domain="", int $secure=0, int $httpOnly=0) {
        if($domain === "") {
            $domain = "." . $_SERVER["SERVER_NAME"];
        }
        setcookie($key, $value, $expire, $path, $domain, $secure, $httpOnly);
    }

    public function getCookie(string $key, ?string $default="") {
        if(array_key_exists($key, $this->cookieVars)) {
            return $this->cookieVars[$key];
        }
        return $default;
    }

    public function removeCookie(string $key, ?string $path="/", ?string $domain="", int $secure=0, int $httpOnly=0) {
        if(isset($_COOKIE[$key])) {
            if($domain === "") {
                $domain = "." . $_SERVER["SERVER_NAME"];
            }
            setcookie($key, "", time() - 3600, $path, $domain, $secure, $httpOnly);
        }
    }

    public function doDisclaimerAction(int $action) {
        if($action === self::DISCLAIMER_ALL) {
            $this->onAcceptAll();
        } elseif($action === self::DISCLAIMER_NECESSARY) {
            $this->onAcceptNecessary();
        } elseif($action === self::DISCLAIMER_DECLINE) {
            $this->onDecline();
        }

        throw new Exception("Unknown cookie disclaimer action", HttpCodes::HTTP_BAD_REQUEST);
    }

    private function onAcceptAll() {
        $this->addDisclaimerData(self::DISCLAIMER_ALL);
        $this->setCookie(self::DISCLAIMER_COOKIE_KEY, self::DISCLAIMER_ALL, time() + (60*60*24*30), "/");
    }

    private function onAcceptNecessary() {
        $this->addDisclaimerData(self::DISCLAIMER_NECESSARY);
        $this->setCookie(self::DISCLAIMER_COOKIE_KEY, self::DISCLAIMER_NECESSARY, time() + (60*60*24*30), "/");
    }

    private function onDecline() {
        Factory::getObject(Factory::TYPE_ROUTER)->redirect("www.google.com"); //for now lets just redirect user if he decline cookies
    }

    public function isDisclaimerState(int $state) {
        if(!in_array($state, $this->allowedDisclaimerStates())) {
            throw new Exception("Unknown cookie disclaimer state");
        }
        $cookieState = (int)$this->getCookie(self::DISCLAIMER_COOKIE_KEY);
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