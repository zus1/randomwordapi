<?php


class Session
{
    const LOCAL_KEY = "local";

    public function startSession() {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function startUserSession(string $userEmail) {
        $this->startSession();
        if(!isset($_SESSION[User::USER_SESSION_KEY])) {
            $_SESSION[User::USER_SESSION_KEY] = $userEmail;
        }
    }

    public function endUserSession()  {
        $this->startSession();
        if(isset($_SESSION[User::USER_SESSION_KEY])) {
            unset($_SESSION[User::USER_SESSION_KEY]);
        }
    }
}