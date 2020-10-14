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
    private $cms;
    private $web;
    private $guardian;
    private $userToken;

    public function __construct(Request $request, HtmlParser $htmlParser, Validator $validator, User $user, Session $session, Response $response, Localization $local, Cms $cms, Web $web, Guardian $guardian, UserToken $userToken) {
        $this->request = $request;
        $this->htmlParser = $htmlParser;
        $this->validator = $validator;
        $this->user = $user;
        $this->session = $session;
        $this->response = $response;
        $this->localization = $local;
        $this->cms = $cms;
        $this->web = $web;
        $this->guardian = $guardian;
        $this->userToken = $userToken;
    }

    public function webRoot() {
        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/documentation.php");
    }

    public function webApiDocs() {
        $pageData = $this->web->getPageData(Web::PAGE_DOCUMENTATION);
        return $this->htmlParser->parseView("documentation", $pageData);
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
        $usernameOrEmail = $this->request->input("user");
        $password = $this->request->input("password");

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

        try {
            $this->user->login($usernameOrEmail, $password);
        } catch(Exception $e) {
            $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, $e->getMessage());
            Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/auth/login.php");
        }
    }

    public function logout() {
        $this->session->startSession();
        unset($_SESSION[User::USER_SESSION_KEY]);
        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/documentation.php");
    }

    public function register() {
        $captcha = $this->guardian->generateCaptcha();
        return $this->response->withOldData()->returnView("auth:register", array("captcha" => $captcha));
    }

    public function doRegister() {
        try {
            $email = $this->request->inputOrThrow("email");
            $username = $this->request->inputOrThrow("username");
            $password = $this->request->inputOrThrow("password");
            $passwordConfirm = $this->request->inputOrThrow("password-confirm");
            $captcha = $this->request->input("captcha");

            if($this->validator->validate("email", array(Validator::FILTER_EMAIL))->isFailed()) {
                throw new Exception($this->validator->getMessages()[0]);
            }
            $this->validator->resetMessages();
            if($this->validator->validate("username", array(Validator::FILTER_ALPHA_NUM))->isFailed()) {
                throw new Exception($this->validator->getMessages()[0]);
            }
            $this->validator->resetMessages();
            if($this->validator->validate("password", array(Validator::FILTER_PASSWORD))->isFailed()) {
                throw new Exception($this->validator->getMessages()[0]);
            }
            $this->validator->resetMessages();
            if($this->validator->validate("confirm-password", array(Validator::FILTER_PASSWORD))->isFailed()) {
                throw new Exception($this->validator->getMessages()[0]);
            }
            $this->validator->resetMessages();
            if($this->validator->validate("captcha", array(Validator::FILTER_ALPHA_NUM))->isFailed()) {
                throw new Exception($this->validator->getMessages()[0]);
            }
            if($password !== $passwordConfirm) {
                throw new Exception("Passwords do not match");
            }
            $this->guardian->checkCaptcha($captcha);
        } catch(Exception $e) {
            $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, $e->getMessage());
            $this->response->withOldData()->returnRedirect(HttpParser::baseUrl() . "views/auth/register.php");
        }

        try {
            $newUserId = $this->user->register($email, $username, $password);
        } catch(Exception $e) {
            $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, $e->getMessage());
            $this->response->withOldData()->returnRedirect(HttpParser::baseUrl() . "views/auth/register.php");
        }

        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/auth/verifyemail.php", HttpCodes::HTTP_OK, array("uuid" => $newUserId["uuid"]));
    }

    public function verifyEmail() {
        return $this->htmlParser->parseView("auth:verify");
    }

    public function resendEmail() {
        try {
            $uuid = $this->request->inputOrThrow("uuid");

            if($this->validator->validate("uuid", array(Validator::FILTER_ALPHA_NUM))->isFailed()) {
                throw new Exception($this->validator->getMessages()[0]);
            }
            $userId = $this->user->getIdFromUuid($uuid);
            if($this->user->checkIfUserVerified($userId)) {
                throw new Exception("Already verified");
            }
            $this->user->resendEmail($userId, User::EMAIL_TYPE_VERIFICATION);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "Email sent"));
    }

    public function doVerifyEmail() {
        $status = 2; //ok
        $userId = 0;
        $noId = false;
        $message = "Account verified. Please wait few second until we redirect you to login page.";
        $token = $this->request->input("token");
        $uuid = $this->request->input("uuid");
        try {
            if(empty($uuid)) {
                $noId = true;
            }
            if(empty($token)) {
                $status = 1; //Invalid with email resend
                throw new Exception("Token missing from request");
            }
            if($this->validator->validate("token", array(Validator::FILTER_ALPHA_NUM))->isFailed()) {
                $status = 0; //invalid without email resend
                throw new Exception("Invalid Token");
            }

            if($this->validator->validate("uuid", array(Validator::FILTER_ALPHA_NUM))->isFailed()) {
                $noId = true;
            }

            try {
                $this->userToken->checkToken($token, UserToken::TOKEN_TYPE_ACCOUNT_VERIFICATION, $userId);
            } catch(Exception $e) {
                if($e->getMessage() === "Token expired") {
                    $status = 1; //Invalid with email resend
                }
                throw $e;
            }
        } catch(Exception $e) {
            $message = $e->getMessage();
        }

        if($status === 2 && $userId > 0) {
            $this->user->getModel()->update(array("email_verified" => 1), array("id" => $userId));
        }
        if($noId === true && $userId > 0) {
            $status = 0; //uuid missing or invalid we can't resend email
        }

        return $this->htmlParser->parseView("auth:verified", array("status" => $status, "message" => $message));
    }

    public function newPassword() {
        $pageData = $this->web->getPageData(Web::NEW_PASSWORD);
        return $this->htmlParser->parseView("auth:newPassword", $pageData);
    }

    public function newPasswordEmail() {
        $exception = false;
        try {
            $email = $this->request->inputOrThrow("email");

            if($this->validator->validate("email", array(Validator::FILTER_EMAIL))->isFailed()) {
                throw new Exception($this->validator->getMessages()[0]);
            }

            $this->user->passwordResetEmail($email);
        } catch(Exception $e) {
            $exception = true;
            $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, $e->getMessage());
        }

        if($exception === false) {
            $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_SUCCESS_KEY, "Email sent");
        }

        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/auth/passwordnew.php");
    }

    public function resetPassword() {
        $status = 2;
        $message = "";
        $email = "";
        $captcha = $this->guardian->generateCaptcha();
        try {
            $token = $this->request->inputOrThrow("token");

            if($this->validator->validate("token", array(Validator::FILTER_ALPHA_NUM))->isFailed()) {
                throw new Exception($this->validator->getErrorMessages()[0]);
            }

            $email = $this->user->resetPassword($token);
        } catch (Exception $e) {
            $status = 1;
            $message = $e->getMessage();
        }

        return $this->htmlParser->parseView("auth:resetPassword", array("status" => $status, "message" => $message, "email" => $email, "captcha" => $captcha));
    }

    public function doResetPassword() {
        $token = "";
        try {
            $token = $this->request->inputOrThrow("token");
            $email = $this->request->inputOrThrow("email");
            $newPassword = $this->request->inputOrThrow("password-new");
            $newPasswordConfirm = $this->request->inputOrThrow("password-confirm");
            $captcha = $this->request->inputOrThrow("captcha");

            if($this->validator->validate("email", array(Validator::FILTER_EMAIL))->isFailed()) {
                throw new Exception($this->validator->getMessages()[0]);
            }
            $this->validator->resetMessages();
            $this->validator->resetMessages();
            if($this->validator->validate("password-new", array(Validator::FILTER_PASSWORD))->isFailed()) {
                throw new Exception($this->validator->getMessages()[0]);
            }
            $this->validator->resetMessages();
            if($this->validator->validate("password-confirm", array(Validator::FILTER_PASSWORD))->isFailed()) {
                throw new Exception($this->validator->getMessages()[0]);
            }
            $this->validator->resetMessages();
            if($this->validator->validate("captcha", array(Validator::FILTER_ALPHA_NUM))->isFailed()) {
                throw new Exception($this->validator->getMessages()[0]);
            }
            $this->validator->resetMessages();
            if($this->validator->validate("token", array(Validator::FILTER_ALPHA_NUM))->isFailed()) {
                throw new Exception($this->validator->getErrorMessages()[0]);
            }
            $this->guardian->checkCaptcha($captcha);
            if($newPassword !== $newPasswordConfirm) {
                throw new Exception("Passwords do not match");
            }

            $this->user->doResetPassword($email, $newPassword, $token);
        } catch(Exception $e) {
            $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, $e->getMessage());
            Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/auth/resetpassword.php", HttpCodes::HTTP_BAD_REQUEST,
                array("token" => $token));
        }

        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/auth/resetpassworddone.php");
    }

    public function resetPasswordDone() {
        return $this->htmlParser->parseView("auth:resetPasswordDone", array("message" => "Password reset was successful. Please wait until we redirect you to login page"));
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
            $this->localization->adminChangeActive($tag, $active);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "New Local Activated, current moved to inactive"));
    }

    public function cmsPages() {
        $pages = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT name, id FROM pages", array(), array());
        if(!$pages) {
            $pages = array();
        }

        return $this->htmlParser->parseView("cms:pages", array("pages" => $pages));
    }

    public function cmsAddPages() {
        $name = $this->request->input("name");
        $placeholders = $this->request->input("placeholders");

        if($this->validator->validate("name", array(Validator::FILTER_ALPHA_NUM))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getFormattedErrorMessagesForDisplay()));
        }

        try {
            $this->cms->addPage($name, $placeholders);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "Page Added"));
    }

    public function cmsGetPageNameAndPlaceholders() {
        $id = $this->request->input("id");

        if($this->validator->validate("id", array(Validator::FILTER_NUMERIC))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getFormattedErrorMessagesForDisplay()));
        }

        $namePlaceholders = $this->cms->getPageNameAndPlaceholders($id);

        return json_encode(array("error" => 0, "name" => $namePlaceholders["name"], "placeholders" => $namePlaceholders["placeholders"]));
    }

    public function cmsEditPages() {
        $id = $this->request->input("id");
        $name = $this->request->input("name");
        $addHolders = $this->request->input("add-holders");
        $changeHolders = $this->request->input("change-holders");
        $decodedChangeHolders = json_decode($changeHolders, true);

        if(json_last_error() !== JSON_ERROR_NONE) {
            return json_encode(array("error" => 1, "message" => json_last_error_msg()));
        }
        if($this->validator->validate("id", array(Validator::FILTER_NUMERIC))->validate("name", array(Validator::FILTER_ALPHA_NUM))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getFormattedErrorMessagesForDisplay()));
        }

        $failedHoldersValidation = false;
        if(!empty($decodedChangeHolders)) {
            foreach($decodedChangeHolders as $holder) {
                if($this->validator->validate("change_placeholders", array(Validator::FILTER_ALPHA_NUM_UNDERSCORE), $holder)->isFailed()) {
                    $failedHoldersValidation = true;
                    break;
                }
            }
        }
        if($failedHoldersValidation === true) {
            return json_encode(array("error" => 1, "message" => "Change placeholders malformed"));
        }

        try {
            $this->cms->editPage($id, $name, $addHolders, $decodedChangeHolders);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "Page Edited"));
    }

    public function cmsRemovePages() {
        $id = $this->request->input("id");

        if($this->validator->validate("id", array(Validator::FILTER_NUMERIC))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getFormattedErrorMessagesForDisplay()));
        }

        try {
            $this->cms->removePage($id);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "Page Removed"));
    }

    public function cmsContent() {
        $pages = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT name FROM pages", array(), array());
        $locals = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT tag FROM local", array(), array());

        return $this->htmlParser->parseView("cms:content", array("pages" => $pages, "locals" => $locals));
    }

    public function cmsGetContentPlaceholders() {
        $pageName = $this->request->input("page-name");

        if($this->validator->validate("page-name", array(Validator::FILTER_ALPHA_NUM))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getFormattedErrorMessagesForDisplay()));
        }

        $placeholders = $this->cms->getPagePlaceholdersByName($pageName);

        return json_encode(array("error" => 0, "placeholders" => $placeholders));
    }

    public function cmsGetPlaceholderContent() {
        $pageName = $this->request->input("page-name");
        $local = $this->request->input("local");
        $placeholder = $this->request->input("placeholder");

        if(
            $this->validator->validate("page_name", array(Validator::FILTER_ALPHA_NUM))
                ->validate("local", array(Validator::FILTER_ALPHA))
                ->validate("placeholder", array(Validator::FILTER_ALPHA_NUM_UNDERSCORE))->isFailed()
        ) {
            return json_encode(array("error" => 1, "message" => $this->validator->getMessages()));
        }

        $placeholderContent = $this->cms->getContentForPlaceholder($pageName, $local, $placeholder);

        return json_encode(array("error" => 0, "content" => $placeholderContent));
    }

    public function cmsEditPageContent() {
        $pageName = $this->request->input("page-name");
        $local= $this->request->input("local");
        $placeholder = $this->request->input("placeholder");
        $content = $this->request->input("content");

        if($this->validator->validate("page-name", array(Validator::FILTER_ALPHA_NUM))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getMessages()[0]));
        }
        $this->validator->resetMessages();;
        if($this->validator->validate("local", array(Validator::FILTER_ALPHA))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getMessages()[0]));
        }
        $this->validator->resetMessages();;
        if($this->validator->validate("placeholder", array(Validator::FILTER_ALPHA_NUM_UNDERSCORE))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getMessages()[0]));
        }
        $this->validator->resetMessages();
        if(!empty($content)) {
            if($this->validator->validate("content", array(Validator::FILTER_HTML))->isFailed()) {
                return json_encode(array("error" => 1, "message" => $this->validator->getMessages()[0]));
            }
        }

        try {
            $this->cms->editPagePlaceholderContent($pageName, $local, $placeholder, $content);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        $occurredAction = $this->cms->getOccurredAction();
        if($occurredAction === Cms::ACTION_INSERT) {
            $message = "Placeholder content added";
        } elseif($occurredAction === Cms::ACTION_UPDATE) {
            $message = "Placeholder content edited";
        } else {
            $message = "Unknown action occurred";
        }

        return json_encode(array("error" => 0, "message" => $message));
    }

    public function adminTranslation() {
        $availableLocals = $this->localization->getAllLocals();
        return $this->htmlParser->parseView("admin:translation", array("available_locals" => $availableLocals));
    }

    public function adminTranslationGetKeys() {
        $local = $this->request->input("local");
        $source = $this->request->input("source");

        if($this->validator->validate("local", array(Validator::FILTER_ALPHA))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getMessages()[0]));
        }
        $this->validator->resetMessages();
        if($this->validator->validate("source", array(Validator::FILTER_ALPHA))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getMessages()[0]));
        }

        $keys = array();
        try {
            $translationMap = Factory::getObject(Factory::TYPE_TRANSLATOR)->getTranslationMap($local);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage(), "keys" => $keys));
        }

        if(!empty($translationMap)) {
            $keys = array_keys($translationMap);
        }

        return json_encode(array("error" => 0, "message" => "", "keys" => $keys, "source" => $source));
    }

    public function adminTranslationLoad() {
        $local = $this->request->input("local");
        $key = $this->request->input("key");

        if($this->validator->validate("local", array(Validator::FILTER_ALPHA))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getMessages()[0]));
        }
        $this->validator->resetMessages();
        if($this->validator->validate("key", array(Validator::FILTER_ALPHA_NUM_UNDERSCORE))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->validator->getMessages()[0]));
        }

        try {
            $map = Factory::getObject(Factory::TYPE_TRANSLATOR)->getTranslationMap($local);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        if(empty($map) || !array_key_exists($key, $map)) {
            return json_encode(array("error" => 1, "message" => "No translation found"));
        }

        return json_encode(array("error" => 0, "translation" => $map[$key]));
    }

    public function adminEditTranslation() {
        $local = $this->request->input("local");
        $key = $this->request->input("key");
        $translation = $this->request->input("translation");

        try {
            $this->validateTranslationLocalAndKey();
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        try {
            Factory::getObject(Factory::TYPE_TRANSLATOR)->changeTranslationMap($local, $key, $translation, Translator::ACTION_TRANSLATION_MAP_UPDATE);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "Translation Updated"));
    }

    public function adminAddTranslation() {
        $local = $this->request->input("local");
        $key = $this->request->input("key");
        $translation = $this->request->input("translation");

        try {
            $this->validateTranslationLocalAndKey();
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        try {
            Factory::getObject(Factory::TYPE_TRANSLATOR)->changeTranslationMap($local, $key, $translation, Translator::ACTION_TRANSLATION_MAP_INSERT);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "Translation Added"));
    }

    public function adminRemoveTranslation() {
        $local = $this->request->input("local");
        $key = $this->request->input("key");

        try {
            $this->validateLocalAndKey() ;
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        try {
            $translator = Factory::getObject(Factory::TYPE_TRANSLATOR);
            $translator->setUnlinkEmpty(true);
            $translator->changeTranslationMap($local, $key, "", Translator::ACTION_TRANSLATION_MAP_DELETE);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "Translation Removed"));
    }

    private function validateTranslationLocalAndKey() {
        if($this->validator->validate("local", array(Validator::FILTER_ALPHA))->isFailed()) {
            throw new Exception($this->validator->getMessages()[0]);
        }
        $this->validator->resetMessages();
        if($this->validator->validate("key", array(Validator::FILTER_ALPHA_NUM_UNDERSCORE))->isFailed()) {
            throw new Exception($this->validator->getMessages()[0]);
        }
        $this->validator->resetMessages();;
        if($this->validator->validate("translation", array(Validator::FILTER_ALPHA_NUM_DASH))->isFailed()) {
            throw new Exception($this->validator->getMessages()[0]);
        }
    }

    private function validateLocalAndKey() {
        if($this->validator->validate("local", array(Validator::FILTER_ALPHA))->isFailed()) {
            throw new Exception($this->validator->getMessages()[0]);
        }
        $this->validator->resetMessages();
        if($this->validator->validate("key", array(Validator::FILTER_ALPHA_NUM_UNDERSCORE))->isFailed()) {
            throw new Exception($this->validator->getMessages()[0]);
        }
    }

    public function ajaxGetTranslation() {
        $notifiKey = $this->request->input("notifi-key");
        $transKey = $this->request->input("trans-key");
        $target = $this->request->input("target");

        if($this->validator->validate("trans-key", array(Validator::FILTER_ALPHA_NUM_UNDERSCORE))->isFailed()) {
            return json_encode(array("translation" => "Translation missing, invalid key", "notifi_key" => $notifiKey, "target" => $target));
        }

        return json_encode(array("translation" => Translator::get($transKey), "notifi_key" => $notifiKey, "target" => $target));
    }

    public function ajaxChangeUserLocal() {
        $newLocal = $this->request->input("local");

        if($this->validator->validate("local", array(Validator::FILTER_ALPHA))->isFailed()) {
            return json_encode(array("error" => 1));
        }

        $this->localization->setActive($newLocal);
        return json_encode(array("error" => 0));
    }

    public function error() {
        $error = $this->request->error;
        $code = $this->request->code;
        return "error: " . $error . ", " . $code;
    }
}