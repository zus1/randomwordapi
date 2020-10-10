<?php

class Guardian
{
    private $session;
    private $user;
    protected $request;
    protected $dateHandler;
    private $csrfTokenSize = 30;
    const CSRF_SESSION_KEY = "csrf_token";
    const CSRF_TOKEN_FIELD_NAME = "_csrf";

    const TOKEN_TYPE_VERIFICATION = "verification";
    const TOKEN_TYPE_PASSWORD_RESET = "password-reset";

    protected $csrfTokenChars = "abcdefg12345*+-()@ijkABCDEFG6789Yxhijklmnopr=&yxzqXYZQ";
    protected $tokenChars = "ABCDabcdEFGHefghIJKLijkl1234MNOPR56mnopr789stuvSTUVzZxXyYwW";

    public function __construct(Session $session, User $user, Request $request, DateHandler $dateHandler) {
        $this->session = $session;
        $this->user = $user;
        $this->request = $request;
        $this->dateHandler = $dateHandler;
    }

    private function getTokenSize(string $type) {
        return array(
            self::TOKEN_TYPE_VERIFICATION => Config::get(Config::VERIFICATION_TOKEN_SIZE),
            self::TOKEN_TYPE_PASSWORD_RESET => Config::get(Config::PASSWORD_RESET_TOKEN_SIZE),
        )[$type];
    }

    public function regenerateCsrfToken() {
        $this->session->startSession();
        $key = $this->getCsrfSessionKey();
        $csrfToken = $this->makeTokenString($this->csrfTokenSize, $this->csrfTokenChars);

        $_SESSION[$key] = $csrfToken;
    }

    public function checkCsrfToken() {
        $this->session->startSession();
        $token = $this->request->input(self::CSRF_TOKEN_FIELD_NAME);
        if(!$token) {
            throw new Exception("Missing csrf token", HttpCodes::HTTP_BAD_REQUEST);
        }
        $key = $this->getCsrfSessionKey();
        $sessionToken = $_SESSION[$key];
        if(!$sessionToken || $sessionToken !== $token) {
            throw new Exception("Unauthorized", HttpCodes::UNAUTHORIZED);
        }
    }

    public function getCsrfSessionKey() {
        if(!$this->user->isAuthenticatedUser()) {
            $key = self::CSRF_SESSION_KEY;
        } else {
            $userEmail = $_SESSION[User::USER_SESSION_KEY];
            $key = self::CSRF_SESSION_KEY . "_" . $userEmail;
        }

        return $key;
    }

    public function checkHardBan(array $app) {
        if((int)$app["hard_banned"] === 1) {
            throw new Exception("Banned", HttpCodes::HTTP_FORBIDDEN);
        }
    }

    public function getToken(string $type) {
        $size = (int)$this->getTokenSize($type);
        return $this->makeTokenString($size, $this->tokenChars);
    }

    protected function makeTokenString(int $size, string $charset) {
        $token = "";
        while($size > 0) {
            $pos = rand(0, strlen($charset) - 1);
            $char = $charset[$pos];
            $token .= $char;
            $size--;
        }

        return $token;
    }
}