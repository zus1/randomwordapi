<?php


class Controller
{
    private $request;
    private $htmlParser;
    private $validator;
    private $user;
    private $session;

    public function __construct(Request $request, HtmlParser $htmlParser, Validator $validator, User $user, Session $session) {
        $this->request = $request;
        $this->htmlParser = $htmlParser;
        $this->validator = $validator;
        $this->user = $user;
        $this->session = $session;
    }

    public function webRoot() {
        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/documentation.php");
    }

    public function webApiDocs() {
        //return "added";

        $arrayData = array("key1" => "array_value_1", "key2" => "array_value_2");
        $arrayData2 = array("two1" => array("bla" => "blaOne", "blu" => "blu1"), "two2" => array("bla" => "blaTwo", "blu" => "bluTwo"));
        //$this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_SUCCESS_KEY, "This is success message");
        $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, "This is error message");
        $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_WARNING_KEY, "This is warning message");
        return $this->htmlParser->parseView("admin:test", array("var1" => "value1", "var2" => "value2", "var3", "array_data" => $arrayData, "array_data2" => $arrayData2));
    }

    public function login() {
        return $this->htmlParser->parseView("auth:login");
    }

    public function doLogin() {
        if(strpos($this->request->input("user"), "@") && strpos($this->request->input("user"), ".")) {
            $this->validator->validate("user", array("email"));
        } else {
            $this->validator->validate("user", array("alpha_num"));
        }
        $this->validator->validate("password", array("password"));
        if($this->validator->isFailed()) {
            $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, $this->validator->getFormattedErrorMessagesForDisplay());
            Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/auth/login.php");
        }

        $usernameOrEmail = $this->request->input("user");
        $hashedPassword = password_hash($this->request->input("password"), PASSWORD_BCRYPT );
        $user = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT role, email FROM user WHERE (username = ? OR email = ?) AND hashed_password = ?",
            array("string", "string", "integer"), array($usernameOrEmail, $usernameOrEmail, $hashedPassword))[0];

        if(!empty($user)) {
            $this->session->startUserSession($user['email']);
            if($this->user->isAdmin(intval($user['role']))) {
                Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/adm/home.php");
            } else {
                Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/documentation.php");
            }
        }

        $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, "User Not Found");
        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/auth/login.php");
    }

    public function logout() {
        $this->session->startSession();
        unset($_SESSION[User::USER_SESSION_KEY]);
        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/auth/login.php");
    }

    public function adminHome() {
        return "home";
    }

    public function adminAddAdmin() {

    }

    public function adminAddWords() {
        $languages = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT tag, name FROM languages", array(), array());
        return $this->htmlParser->parseView("admin:insert", array('languages' => $languages));
    }

    public function adminDoAddWords() {
        $language = $this->request->input("language");
        $bulk = $this->request->input("words-bulk");
        $json = $this->request->input("words-json");
        $csv = $this->request->file("words-csv");

        $exception = false;
        try {
            if(empty($language)) {
                throw new Exception($this->validator->getFormattedErrorMessagesForDisplay(array("Please select language")));
            }
            Factory::getObject(Factory::TYPE_WORDS)->setLanguage($language);
            if(!empty($bulk)) {
                Factory::getObject(Factory::TYPE_WORDS)->bulkInsert($bulk);
            }
            if(!empty($json)) {
                Factory::getObject(Factory::TYPE_WORDS)->jsonInsert($json);
            }
            if(!empty($csv)) {
                Factory::getObject(Factory::TYPE_WORDS)->csvInsert($csv);
            }
        } catch(Exception $e) {
            $exception = true;
            $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, $e->getMessage());
        }

        if($exception === false) {
            $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_SUCCESS_KEY, "Words Added");
        }
        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/adm/insert.php");
    }

    public function error() {
        $error = $this->request->error;
        $code = $this->request->code;
        return "error: " . $error . ", " . $code;
    }
}