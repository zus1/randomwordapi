<?php


class Controller
{
    private $request;
    private $response;
    private $htmlParser;
    private $validator;
    private $user;
    private $session;
    private $localization;

    public function __construct(Request $request, HtmlParser $htmlParser, Validator $validator, User $user, Session $session, Response $response, Localization $local) {
        $this->request = $request;
        $this->htmlParser = $htmlParser;
        $this->validator = $validator;
        $this->user = $user;
        $this->session = $session;
        $this->response = $response;
        $this->localization = $local;
    }

    public function webRoot() {
        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/documentation.php");
    }

    public function webApiDocs() {
        return $this->htmlParser->parseView("documentation");
    }

    public function test() {
        //return "added";

        $arrayData = array("key1" => "array_value_1", "key2" => "array_value_2");
        $arrayData2 = array("two1" => array("bla" => "blaOne", "blu" => "blu1"), "two2" => array("bla" => "blaTwo", "blu" => "bluTwo"));
        //$this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_SUCCESS_KEY, "This is success message");
        $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, "This is error message");
        $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_WARNING_KEY, "This is warning message");
        //return "bla";
        return $this->response->returnView("admin:test", array("var1" => "value1", "var2" => "value2", "var3", "array_data" => $arrayData, "array_data2" => $arrayData2));
        //return $this->htmlParser->parseView("admin:test", array("var1" => "value1", "var2" => "value2", "var3", "array_data" => $arrayData, "array_data2" => $arrayData2));
        //Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/documentation.php");
        //$this->response->withOldData()->returnRedirect(HttpParser::baseUrl() . "views/documentation.php");
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
        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/documentation.php");
    }

    public function adminHome() {
        $this->response->returnRedirect(HttpParser::baseUrl() . "views/adm/insert.php");
    }

    public function adminAddAdmin() {
        return $this->htmlParser->parseView("admin:addAdmin");
    }

    public function adminDoAddAdmin() {
        $email = $this->request->input("email");
        $userName = $this->request->input("username");
        $password = $this->request->input("password");
        $confirmPassword = $this->request->input("password-confirm");

        try {
            if(empty($email)) {
                throw new Exception("Email can't be empty");
            }
            if(empty($userName)) {
                throw new Exception("Username can't be empty");
            }
            if(empty($password)) {
                throw new Exception("Password can't be empty");
            }
            if(empty($confirmPassword)) {
                throw new Exception("Pleas confirm password");
            }
            if(
                $this->validator->validate("email", array(Validator::FILTER_EMAIL))
                ->validate("username", array(Validator::FILTER_ALPHA_NUM))
                ->validate("password", array(Validator::FILTER_PASSWORD))
                ->validate("password-confirm", array(Validator::FILTER_PASSWORD))
                ->isFailed()
            ) {
                throw new Exception($this->validator->getFormattedErrorMessagesForDisplay());
            }
            if($password !== $confirmPassword) {
                throw new Exception("Passwords do not match");
            }
        } catch (Exception $e) {
            $this->onAddAdminException($e);
        }

        try {
            $this->user->addAdminAccount($email, $userName, $password);
        } catch(Exception $e) {
            $this->onAddAdminException($e);
        }


        $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_SUCCESS_KEY, "Admin account added");
        $this->response->withOldData()->returnRedirect(HttpParser::baseUrl() . "views/adm/addadmin.php");
    }

    private function onAddAdminException(Exception $e) {
        $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, $e->getMessage());
        $this->response->withOldData()->returnRedirect(HttpParser::baseUrl() . "views/adm/addadmin.php");
    }

    public function adminAddWords() {
        $languages = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT tag, name FROM languages", array(), array());
        return $this->htmlParser->parseView("admin:insert", array('languages' => $languages));
    }

    public function adminDoAddWords() {
        list($language, $bulk, $json, $csv) = $this->getMultipleWordsParameters();

        $exception = false;
        try {
            if(empty($language)) {
                throw new Exception($this->validator->getFormattedErrorMessagesForDisplay(array("Please select language")));
            }
            if(!empty($bulk)) {
                Factory::getObject(Factory::TYPE_WORDS_BULK)->setLanguage($language)->action($bulk, Words::ACTION_INSERT);
            }
            if(!empty($json)) {
                Factory::getObject(Factory::TYPE_WORDS_JSON)->setLanguage($language)->action($json, Words::ACTION_INSERT);
            }
            if(!empty($csv)) {
                Factory::getObject(Factory::TYPE_WORDS_CSV)->setLanguage($language)->action($csv, Words::ACTION_INSERT);
            }
        } catch(Exception $e) {
            $exception = true;
            $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, $e->getMessage());
        }

        if($exception === false) {
            $totalAdded = Factory::getObject(Factory::TYPE_WORDS)->getTotalChanged();
            $suffix = ($totalAdded > 1 || $totalAdded === 0)? " Words" : " Word";
            $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_SUCCESS_KEY, "Added " . $totalAdded . $suffix);
        }
        $this->response->withOldData()->returnRedirect(HttpParser::baseUrl() . "views/adm/insert.php");
    }

    private function getMultipleWordsParameters() {
        $language = $this->request->input("language");
        $bulk = $this->request->input("words-bulk");
        $json = $this->request->input("words-json");
        $csv = $this->request->file("words-csv");

        return array($language, $bulk, $json, $csv);
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

    public function adminModifyWordsRemoveMulti() {
        list($language, $bulk, $json, $csv) = $this->getMultipleWordsParameters();

        try {
            if(empty($language)) {
                throw new Exception($this->validator->getFormattedErrorMessagesForDisplay(array("Please select language")));
            }
            if(!empty($bulk)) {
                Factory::getObject(Factory::TYPE_WORDS_BULK)->setLanguage($language)->action($bulk, Words::ACTION_REMOVE);
            }
            if(!empty($json)) {
                Factory::getObject(Factory::TYPE_WORDS_JSON)->setLanguage($language)->action($json, Words::ACTION_REMOVE);
            }
            if(!empty($csv)) {
                Factory::getObject(Factory::TYPE_WORDS_CSV)->setLanguage($language)->action($csv, Words::ACTION_REMOVE);
            }
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        $totalRemoved = Factory::getObject(Factory::TYPE_WORDS)->getTotalChanged();
        $suffix = ($totalRemoved > 1 || $totalRemoved === 0)? " Words" : " Word";
        return json_encode(array("error" => 0, "message" => "Removed " . $totalRemoved . $suffix));
    }

    public function adminManageLanguages() {
        $availableFilters = $this->validator->getLanguageFilters();
        $languages = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT tag, name FROM languages", array(), array());
        if(!$languages) {
            $languages = array();
        }

        return $this->htmlParser->parseView("admin:language", array("available_filters" => $availableFilters, "languages" => $languages));
    }

    public function adminAddLanguage() {
        $tag = $this->request->input("tag");
        $name = $this->request->input("name");
        $filters = $this->request->input("filters");
        $decodedFilters = json_decode($filters, true);

        if(json_last_error() !== JSON_ERROR_NONE) {
            return json_encode(array("error" => 1, "message" => json_last_error_msg()));
        }

        try {
            Factory::getObject(Factory::TYPE_WORDS)->addLanguage($tag, $name, $decodedFilters);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "Language Added"));
    }

    public function adminRemoveLanguage() {
        $tag = $this->request->input("tag");
        try {
            Factory::getObject(Factory::TYPE_WORDS)->removeLanguage($tag);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "Language Removed"));
    }

    public function adminUpdateLanguageNameAndFilters() {
        $tag = $this->request->input("tag");
        if($this->validator->validate("tag", array(Validator::FILTER_ALPHA))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getFormattedErrorMessagesForDisplay()));
        }

        try {
            $data = Factory::getObject(Factory::TYPE_WORDS)->getNameAndFiltersForUpdate($tag);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array_merge(array("error" => 0), $data));
    }

    public function adminUpdateLanguage() {
        $tag = $this->request->input("tag");
        $newName = $this->request->input("name");
        $newFilters = $this->request->input("filters");
        $decodedNewFilters = json_decode($newFilters, true);

        if(json_last_error() !== JSON_ERROR_NONE) {
            return json_encode(array("error" => 1, "message" => json_last_error_msg()));
        }
        if($this->validator->validate("tag", array(Validator::FILTER_ALPHA))->validate("name", array(Validator::FILTER_ALPHA))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getFormattedErrorMessagesForDisplay()));
        }
        foreach($decodedNewFilters as $newFilter) {
            if($this->validator->validate("filters", array(Validator::FILTER_ALPHA_DASH), $newFilter)->isFailed()) {
                return json_encode(array("error" => 1, "message" => $this->validator->getFormattedErrorMessagesForDisplay()));
            }
        }

        try {
            Factory::getObject(Factory::TYPE_WORDS)->updateLanguage($tag, $newName, $decodedNewFilters);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, 'message' => "Language updated"));
    }

    public function adminLocalization() {
        $availableLocals = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT tag FROM local", array(), array());
        if(!$availableLocals) {
            $availableLocals = array();
        }

        return $this->htmlParser->parseView("admin:localization", array("locals" => $availableLocals));
    }

    public function adminAddLocal() {
        $tag = $this->request->input("tag");
        if($this->validator->validate("tag", array(Validator::FILTER_ALPHA))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getFormattedErrorMessagesForDisplay()));
        }
        try {
            $this->localization->addLocal($tag);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "Local added"));
    }

    public function adminRemoveLocal() {
        $tag = $this->request->input("tag");
        if($this->validator->validate("tag", array(Validator::FILTER_ALPHA))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getFormattedErrorMessagesForDisplay()));
        }
        try {
            $this->localization->removeLocal($tag);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "Local removed"));
    }

    public function adminChangeLocalGetActive() {
        $tag = $this->request->input("tag");
        if($this->validator->validate("tag", array(Validator::FILTER_ALPHA))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getFormattedErrorMessagesForDisplay()));
        }
        try {
            $activeState = $this->localization->getLocalActiveState($tag);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "active" => $activeState));
    }

    public function adminChangeLocalActive() {
        $tag = $this->request->input("tag");
        $active = (int)$this->request->input("active");

        if($this->validator->validate("tag", array(Validator::FILTER_ALPHA))->validate("active", array(Validator::FILTER_NUMERIC))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getFormattedErrorMessagesForDisplay()));
        }
        try {
            $this->localization->changeActive($tag, $active);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "New Local Activated, current moved to inactive"));
    }

    public function error() {
        $error = $this->request->error;
        $code = $this->request->code;
        return "error: " . $error . ", " . $code;
    }
}