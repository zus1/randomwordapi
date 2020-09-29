<?php


class Session
{
    public function startSession() {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function startUserSession(string $userEmail) {
        $this->startSession();
        $_SESSION[User::USER_SESSION_KEY] = $userEmail;
    }

    //public function addOldRequest()
}