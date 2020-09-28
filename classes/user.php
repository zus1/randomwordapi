<?php


class User
{
    const USER_SESSION_KEY = 'username';
    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'admin';
    private static $counter = 1;
    private $roleToDbRoleMapping = array(
        self::ROLE_USER => 1,
        self::ROLE_ADMIN => 2,
    );

    private $session;

    public function __construct(Session $session) {
        self::$counter++;
        $this->session = $session;
    }

    public function bla() {
        echo "blabla " . self::$counter;
    }

    public function addAdminAccount(string $email, string $username, string $password) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $existing = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT id FROM user WHERE email = ? OR username = ?",
            array("string", "string"), array($email, $username));
        if($existing) {
            throw new Exception("Admin account already exists");
        }

        Factory::getObject(Factory::TYPE_DATABASE, true)->execute("INSERT INTO user (email, username, password, hashed_password, role) VALUES (?,?,?,?,?)",
            array("string", "string", "string", "string", "integer") , array($email, $username, $password, $hashedPassword, $this->roleToDbRoleMapping[self::ROLE_ADMIN]));
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
        $userRole = Factory::getObject(Factory::TYPE_DATABASE)->select("SELECT role FROM user WHERE email = ?", array("string"), array($userEmail))[0]['role'];
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
            $role = Factory::getObject(Factory::TYPE_DATABASE)->select("SELECT role FROM user WHERE email = ?", array("string"), array($userEmail))[0]['role'];
        }

        if(intval($role) === $this->roleToDbRoleMapping[self::ROLE_ADMIN]) {
            return true;
        }

        return false;
    }
}