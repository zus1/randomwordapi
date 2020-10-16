<?php

class Init
{
    private $cookie;
    private $session;

    public function __construct(Cookie $cookie, Session $session) {
        $this->cookie = $cookie;
        $this->session = $session;
    }

    public function onInit() {
        if(!$this->isApi()) {
            try {
                $this->onInitNotApi();
            } catch(Exception $e) {
                Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/error.php?error=" . $e->getMessage() . "&code=" . $e->getCode(), $e->getCode());
            }
        } else {
            try {
                $this->onInitApi();
            } catch(Exception $e) {
                echo Factory::getObject(Factory::TYPE_API_EXCEPTION)->getApiException($e);
                die();
            }
        }
    }

    private function onInitNotApi() {
        $this->checkRememberMe();
    }

    private function onInitApi() {

    }

    private function isApi() {
        $route = explode("?", $_SERVER["REQUEST_URI"])[0];
        if(strpos($route, "api")) {
            return true;
        }

        return false;
    }

    private function checkRememberMe() {
        $uuid = $this->cookie->getCookie(User::USER_REMEMBER_ME_KEY);
        if($uuid !== "") {
            try {
                $userObj = Factory::getObject(Factory::TYPE_USER);
                $userId = $userObj->getIdFromUuid($uuid);
                $user = $userObj->getUserById($userId, array("email"));
                $this->session->startUserSession($user["email"]);
            } catch (Exception $e) {
                if($e->getMessage() === "User not found") {
                    throw new Exception("User not found on init");
                }

                throw $e;
            }
        }
    }
}