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

    protected $tokenChars = "abcdefg12345*+-()@ijkABCDEFG6789Yxhijklmnopr=&yxzqXYZQ";

    public function __construct(Session $session, User $user, Request $request, DateHandler $dateHandler) {
        $this->session = $session;
        $this->user = $user;
        $this->request = $request;
        $this->dateHandler = $dateHandler;
    }

    public function regenerateCsrfToken() {
        $this->session->startSession();
        $key = $this->getCsrfSessionKey();
        $csrfToken = "";
        while($this->csrfTokenSize > 0) {
            $pos = rand(0, strlen($this->tokenChars) - 1);
            $csrfToken .= $this->tokenChars[$pos];
            $this->csrfTokenSize--;
        }

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
}