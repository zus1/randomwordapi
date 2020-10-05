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
    private $dataset = array("id", "username", "email", "password", "role", "local");

    private $session;

    public function __construct(Session $session) {
        self::$counter++;
        $this->session = $session;
    }

    private function checkIfAllowed(array $inputFields) {
        $notAllowed = array_diff($inputFields, $this->dataset);
        if(!empty($notAllowed)) {
            return false;
        }

        return true;
    }

    public function bla() {
        echo "blabla " . self::$counter;
    }

    public function getAuthenticatedUser(?array $fields=array()) {
        $this->session->startSession();
        if(!isset($_SESSION[self::USER_SESSION_KEY])) {
            return array();
        }
        if(!$this->checkIfAllowed($fields)) {
            return array();
        }

        $userEmail = $_SESSION[self::USER_SESSION_KEY];
        if(!empty($fields)) {
            $fields = array_map(function ($field) {
                return Factory::getObject(Factory::TYPE_VALIDATOR)->filterAlphaNumUnderscore($field);
            }, $fields);
            $fieldsStr = implode(",", $fields);
        } else {
            $fieldsStr = "*";
        }

        $authUser = Factory::getObject(Factory::TYPE_DATABASE)->select("SELECT " . $fieldsStr . " FROM user WHERE email = ?", array("string"), array($userEmail));

        return $authUser[0];
    }

    public function setAuthenticatedUser(array $data) {
        $this->session->startSession();
        if(!isset($_SESSION[self::USER_SESSION_KEY])) {
            return false;
        }

        $userEmail = $_SESSION[self::USER_SESSION_KEY];
        $fields = array_keys($data);
        $values = array_values($data);
        if(!$this->checkIfAllowed($fields)) {
            return false;
        }

        $fields = array_map(function($field) {
            return Factory::getObject(Factory::TYPE_VALIDATOR)->filterAlphaNumUnderscore($field);
        }, $fields);

        //$query = Factory::getObject(Factory::TYPE_DATABASE, true)->buildUpdateQuery($fields, array("email"));
        $query = "UPDATE user SET ";
        foreach($fields as $field) {
            $query .= sprintf("%s=?,", $field);
        }

        $query = substr($query, 0, strlen($query) - 1);
        $query .= " WHERE email = ?";
        $values[] = $userEmail;

        Factory::getObject(Factory::TYPE_DATABASE, true)->execute($query, array(), $values);
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