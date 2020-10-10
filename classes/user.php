<?php


class User
{
    const USER_SESSION_KEY = 'username';
    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'admin';
    private $roleToDbRoleMapping = array(
        self::ROLE_USER => 1,
        self::ROLE_ADMIN => 2,
    );
    //private $dataset = array("id", "username", "email", "password", "role", "local");

    private $session;

    public function __construct(Session $session) {
        $this->session = $session;
    }

    public function getModel() {
        return Factory::getModel(Factory::MODEL_USER);
    }

    public function getAuthenticatedUser(?array $fields=array()) {
        $this->session->startSession();
        if(!isset($_SESSION[self::USER_SESSION_KEY])) {
            return array();
        }
        $userEmail = $_SESSION[self::USER_SESSION_KEY];

        $authUser = $this->getModel()->select($fields, array("email" => $userEmail));
        return $authUser[0];
    }

    public function setAuthenticatedUser(array $data) {
        $this->session->startSession();
        if(!isset($_SESSION[self::USER_SESSION_KEY])) {
            return false;
        }
        $userEmail = $_SESSION[self::USER_SESSION_KEY];

        $this->getModel()->update($data, array("email" => $userEmail));
    }

    public function addAdminAccount(string $email, string $username, string $password) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $existing = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT id FROM user WHERE email = ? OR username = ?",
            array("string", "string"), array($email, $username));
        if($existing) {
            throw new Exception("Admin account already exists");
        }

        $this->getModel()->insert(array(
            "email" => $email,
            "username" => $username,
            "password" => $password,
            "hashed_password" => $hashedPassword,
            "role" => $this->roleToDbRoleMapping[self::ROLE_ADMIN]
        ));
    }

    public function isAuthenticatedUser() {
        $this->session->startSession();
        if(isset($_SESSION[self::USER_SESSION_KEY])) {
            return true;
        }

        return false;
    }

    public function hasRole(string $roleStr) {
        $this->session->startSession();
        if(!$this->isAuthenticatedUser()) {
            return false;
        }

        $roleInt = $this->roleToDbRoleMapping[$roleStr];
        $userEmail = $_SESSION[self::USER_SESSION_KEY];
        $userRole = $this->getModel()->select(array("role"), array("email" => $userEmail))[0]["role"];
        if(intval($userRole) !== $roleInt) {
            return false;
        }

        return true;
    }

    public function isAdmin(?int $role=0) {
        $this->session->startSession();
        if(!$role) {
            if(!isset($_SESSION[self::USER_SESSION_KEY])) {
                return false;
            }
            $userEmail = $_SESSION[self::USER_SESSION_KEY];
            $role = $this->getModel()->select(array("role"), array("email" => $userEmail))[0]["role"];
        }

        if(intval($role) === $this->roleToDbRoleMapping[self::ROLE_ADMIN]) {
            return true;
        }

        return false;
    }
}