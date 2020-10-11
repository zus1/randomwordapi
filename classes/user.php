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

    private $session;
    private $verificationMail;

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

    public function login(string $usernameOrEmail, string $password) {
        $hashedPassword = $hashedPassword = password_hash($password,PASSWORD_BCRYPT);

        $user = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT role, email, hard_banned, email_verified FROM user WHERE (username = ? OR email = ?) AND hashed_password = ?",
            array("string", "string", "integer"), array($usernameOrEmail, $usernameOrEmail, $hashedPassword))[0];

        if(!$user) {
            throw new Exception("User not found");
        }
        if((int)$user[0]["hard_banned"] === 1) {
            throw new Exception("This user has bean banned");
        }
        if((int)$user[0]["email_verified"] === 0) {
            throw new Exception("Please verify your email first");
        }

        $this->session->startUserSession($user['email']);
        if($this->isAdmin(intval($user['role']))) {
            Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/adm/home.php");
        } else {
            Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/documentation.php");
        }
    }

    public function register(string $email, string $username, string $password) {
        $hashedPassword = $hashedPassword = password_hash($password,PASSWORD_BCRYPT);

        $existing = $this->getModel()->select(array("username"), array("email" => $email));
        if($existing) {
            throw new Exception("User with this email already exists");
        }
        if($existing[0]["username"] === $username) {
            throw new Exception("User with this username already exists");
        }

        $id = $this->getModel()->insert(array(
            "username" => $username,
            //"password" => $password,
            "hashed_password" => $hashedPassword,
            "role" => $this->roleToDbRoleMapping[self::ROLE_USER]
        ));

        $this->sendVerificationEmail($email, $username);

        return $id;
    }

    public function sendVerificationEmail(string $email, string $username) {
        Factory::getObject(Factory::TYPE_ACCOUNT_VERIFICATION_MAIL)->setSender(["random.word.api@gmail.com", "RandomWordApi"])
            ->setAddress(["zus.ozus@gmaul.com", $username])
            ->setSubject("Verify you account")
            ->setResourceObject($this)
            ->setResourceDataId($email)
            ->setBody() //sets automatically depending on email type. Can be adjusted true CMS
            ->send();
    }
}