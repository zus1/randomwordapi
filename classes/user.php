<?php


class User
{
    const USER_SESSION_KEY = 'username';
    const USER_REMEMBER_ME_KEY = "remember_me";
    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'admin';
    const EMAIL_TYPE_VERIFICATION = "verification";
    const EMAIL_TYPE_PASSWORD = "password";
    private $roleToDbRoleMapping = array(
        self::ROLE_USER => 1,
        self::ROLE_ADMIN => 2,
    );

    private $session;
    private $userToken;
    private $guardian;
    private $cookie;

    public function __construct(Session $session, UserToken $token, Guardian $guardian, Cookie $cookie) {
        $this->session = $session;
        $this->userToken = $token;
        $this->guardian = $guardian;
        $this->cookie = $cookie;
    }

    public function getModel() {
        return Factory::getModel(Factory::MODEL_USER);
    }

    public function getIdFromUuid(string $uuid) {
        $id = $this->getUser(array("id"), array("uuid" => $uuid));
        return $id["id"];
    }

    public function getUuidFromId(int $id) {
        try {
            $uuid = $this->getUser(array("uuid"), array("id" => $id));
        } catch(Exception $e) {
            return null;
        }

        return $uuid["uuid"];
    }

    public function getAuthenticatedUser(?array $fields=array()) {
        $this->session->startSession();
        if(!isset($_SESSION[self::USER_SESSION_KEY])) {
            return array();
        }
        $userEmail = $_SESSION[self::USER_SESSION_KEY];

        return $this->getUser($fields, array("email" => $userEmail));
    }

    public function getUserById(int $userId, ?array $fields=array()) {
        return $this->getUser($fields, array("id" => $userId ));
    }

    public function getUserByEmail(string $email, ?array $fields=array()) {
        return $this->getUser($fields, array("email" => $email ));
    }

    public function getUser(?array $fields=array(), ?array $where=array()) {
        $user = $this->getModel()->select($fields, $where);
        if(!$user) {
            throw new Exception("User not found");
        }
        return $user[0];
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

    public function login(string $usernameOrEmail, string $password, string $rememberMe) {
        $user = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT role, email, hard_banned, email_verified, hashed_password, uuid FROM user WHERE (username = ? OR email = ?)",
            array("string", "string", "integer"), array($usernameOrEmail, $usernameOrEmail));

        if(!$user) {
            throw new Exception("User not found");
        }
        $user = $user[0];
        if(!password_verify($password, $user["hashed_password"])) {
            throw new Exception("User not found");
        }

        if((int)$user["hard_banned"] === 1) {
            throw new Exception("This user has bean banned");
        }
        if((int)$user["email_verified"] === 0) {
            throw new Exception("Please verify your email first");
        }

        $this->session->startUserSession($user['email']);
        if($rememberMe === "true") {
            $this->cookie->setCookie(self::USER_REMEMBER_ME_KEY, $user["uuid"], time() + (60*60*24*30), "/", "." . $_SERVER["SERVER_NAME"], 0, 1);
        }
        if($this->isAdmin(intval($user['role']))) {
            Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/adm/home.php");
        } else {
            Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/documentation.php");
        }
    }

    public function logout() {
        $this->session->endUserSession();
        $this->cookie->removeCookie(self::USER_REMEMBER_ME_KEY, "/", "." . $_SERVER["SERVER_NAME"], 0 , 1);
        /*if(isset($_COOKIE[self::USER_REMEMBER_ME_KEY])) {
            setcookie(self::USER_REMEMBER_ME_KEY, "", time() - 3600, "/", "." . $_SERVER["SERVER_NAME"], 0 , 1);
        }*/
    }

    public function register(string $email, string $username, string $password) {
        $hashedPassword = password_hash($password,PASSWORD_BCRYPT);

        $existingEmail = $this->getModel()->select(array("id"), array("email" => $email));
        if($existingEmail) {
            throw new Exception("User with this email already exists");
        }
        $existingUsername = $this->getModel()->select(array("id"), array("username" => $username));
        if($existingUsername) {
            throw new Exception("User with this username already exists");
        }

        $uuid = $this->guardian->generateUUid();

        Factory::getObject(Factory::TYPE_DATABASE, true)->beginTransaction();
        try {
            $id = $this->getModel()->insert(array(
                "username" => $username,
                "email" => $email,
                //"password" => $password,
                "hashed_password" => $hashedPassword,
                "role" => $this->roleToDbRoleMapping[self::ROLE_USER],
                'uuid' => $uuid
            ), true);

            $this->userToken->addToken(UserToken::TOKEN_TYPE_ACCOUNT_VERIFICATION, $id);
            Factory::getObject(Factory::TYPE_DATABASE, true)->commit();
        } catch(Exception $e) {
            Factory::getObject(Factory::TYPE_DATABASE, true)->rollBack();
            throw $e;
        }

        $this->sendUserEmail($email, $username, $id, Factory::TYPE_ACCOUNT_VERIFICATION_MAIL, "Verify you account");

        return array("id" => $id, "uuid" => $uuid);
    }

    public function passwordResetEmail(string $email) {
        $user = $this->getUserByEmail($email, array("id", "username"));
        $this->userToken->addToken(UserToken::TOKEN_TYPE_PASSWORD_RESET, $user["id"]);
        $this->sendUserEmail($email, $user["username"], $user["id"], Factory::TYPE_RESET_PASSWORD_MAIL, "Reset password");
    }

    public function resetPassword(string $token) {
        $userId = 0;
        $this->userToken->checkToken($token, UserToken::TOKEN_TYPE_PASSWORD_RESET, $userId);
        $user = $this->getUserById($userId, array("email"));

        return $user["email"];
    }

    public function doResetPassword(string $email, string $newPassword, $token) {
        $userId = $this->getUserByEmail($email, array("id"));//this will throw exception if user not found
        $dbToken = $this->userToken->getToken(UserToken::TOKEN_TYPE_PASSWORD_RESET, $userId["id"]);
        if($dbToken !== $token) {
            throw new Exception("Token mismatch");
        }
        $newHash = password_hash($newPassword,PASSWORD_BCRYPT);
        $this->getModel()->update(array("hashed_password" => $newHash), array("email" => $email));
    }

    public function resendEmail(int $userId, string $type) {
        $user = $this->getUserById($userId, array("username", "email"));
        if($type === self::EMAIL_TYPE_VERIFICATION) {
            $this->userToken->addToken(UserToken::TOKEN_TYPE_ACCOUNT_VERIFICATION, $userId);
            $this->sendUserEmail($user["email"], $user["username"], $userId, Factory::TYPE_ACCOUNT_VERIFICATION_MAIL, "Verify you account");
        }
    }

    public function checkIfUserVerified(int $userId) {
        $verified = $this->getUser(array("email_verified"), array("id" => $userId));
        if((int) $verified["email_verified"] === 1) {
            return true;
        }

        return false;
    }

    public function sendUserEmail(string $email, string $username, int $id, string $type, string $subject) {
        Factory::getObject($type)->setSender(["sender" => "random.word.api@gmail.com", "name" => "RandomWordApi"])
            ->setAddress(array(["address" => $email, "name" => $username]))
            ->setSubject($subject)
            ->setResourceObject($this)
            ->setResourceDataId($id)
            ->setBody() //sets automatically depending on email type. Can be adjusted true CMS
            ->send();
    }

    /*public function sendVerificationEmail(string $email, string $username, int $id) {
        Factory::getObject(Factory::TYPE_ACCOUNT_VERIFICATION_MAIL)->setSender(["sender" => "random.word.api@gmail.com", "name" => "RandomWordApi"])
            ->setAddress(array(["address" => $email, "name" => $username]))
            ->setSubject("Verify you account")
            ->setResourceObject($this)
            ->setResourceDataId($id)
            ->setBody() //sets automatically depending on email type. Can be adjusted true CMS
            ->send();
    }

    public function sendPasswordResetEmail(string $email, string $username, int $id) {
        Factory::getObject(Factory::TYPE_RESET_PASSWORD_MAIL)->setSender(["sender" => "random.word.api@gmail.com", "name" => "RandomWordApi"])
            ->setAddress(array(["address" => $email, "name" => $username]))
            ->setSubject("Reset password")
            ->setResourceObject($this)
            ->setResourceDataId($id)
            ->setBody() //sets automatically depending on email type. Can be adjusted true CMS
            ->send();
    }*/
}