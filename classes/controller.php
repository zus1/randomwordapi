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
            if(!empty($bulk)) {
               Factory::getObject(Factory::TYPE_WORDS)->setLanguage($language)->bulkAction($bulk, Words::ACTION_INSERT);
            }
            if(!empty($json)) {
                Factory::getObject(Factory::TYPE_WORDS)->setLanguage($language)->jsonAction($json, Words::ACTION_INSERT);
            }
            if(!empty($csv)) {
                Factory::getObject(Factory::TYPE_WORDS)->setLanguage($language)->csvAction($csv, Words::ACTION_INSERT);
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

    public function adminModifyWords() {
        $languages = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT tag, name FROM languages", array(), array());
        return $this->htmlParser->parseView("admin:modify", array("languages" => $languages));
    }

    public function adminModifyWordsLengths() {
        $tag = $this->request->input("language");
        $lengths = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT length FROM words WHERE tag = ?", array("string"), array($tag));
        if(!$lengths) {
            $lengths = array();
        }
        return json_encode(array("lengths" => $lengths));
    }

    public function adminModifyWordsGetWords() {
        $tag = $this->request->input("language");
        $length = $this->request->input("length");

        $words = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT words FROM words WHERE tag = ? AND length = ?",
            array("string", "string"), array($tag, $length));
        if(!$words) {
            $words = array();
        } else {
            $words = json_decode($words[0]["words"], true);
        }

        return json_encode(array("words" => $words));
    }

    public function adminModifyWordsRemoveSingle() {
        $tag = $this->request->input("language");
        $words = $this->request->input("words");

        $decodedWords = json_decode($words, true);
        if(json_last_error() !== JSON_ERROR_NONE) {
            return json_encode(array("error" => 1, "message" => json_last_error_msg()));
        }

        if($this->validator->validate("language", array(Validator::FILTER_ALPHA))->isFailed()) {
            $messages = $this->validator->getMessages();
            $this->validator->resetMessages();
            return json_encode(array("error" => 1, "message" => $messages[0]));
        }
        if($this->validator->validate("length", array(Validator::FILTER_NUMERIC))->isFailed()) {
            $messages = $this->validator->getMessages();
            $this->validator->resetMessages();
            return json_encode(array("error" => 1, "message" => $messages[0]));
        }
        foreach($decodedWords as $word) {
            if($this->validator->validate("words", array(Validator::FILTER_ALPHA), $word)->isFailed()) {
                return json_encode(array("error" => 1, "message" => "All words have to contain only letters"));
            }
        }

        try {
            Factory::getObject(Factory::TYPE_WORDS)->setLanguage($tag)->remove($decodedWords);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }
        return json_encode(array("error" => 0, "message" => "Words Removed"));
    }

    public function error() {
        $error = $this->request->error;
        $code = $this->request->code;
        return "error: " . $error . ", " . $code;
    }
}