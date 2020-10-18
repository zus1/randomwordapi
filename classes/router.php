<?php

class Router
{
    const REQUEST_POST = 'post';
    const REQUEST_GET = "get";
    private $user;
    private $guardian;

    private $supportedRequestMethods = array(self::REQUEST_GET, self::REQUEST_POST);

    public function __construct(User $user, Guardian $guardian) {
        $this->user = $user;
        $this->guardian = $guardian;
    }

    public function webRoutes() {
        return array(
            '/',
            '/views/adm/insert.php',
            '/views/adm/modify.php',
            '/views/documentation.php',
            '/views/auth/dologin.php',
            '/views/auth/login.php',
            '/views/error.php',
            '/views/adm/home.php',
            '/views/auth/logout.php',
            '/views/adm/addadmin.php',
            '/views/adm/doinsert.php',
            '/views/adm/ajaxlengths.php',
            '/views/adm/ajaxwords.php',
            '/views/adm/removesingle.php',
            '/views/adm/removemulti.php',
            '/views/adm/managelanguages.php',
            '/views/adm/addlanguage.php',
            '/views/adm/removelanguage.php',
            '/views/adm/updatelanguageresources.php',
            '/views/adm/doupdatelanguage.php',
            '/views/adm/doaddadmin.php',
            '/views/test.php',
            '/views/adm/localization.php',
            '/views/adm/addlocal.php',
            '/views/adm/removelocal.php',
            '/views/adm/changegetlocalactive.php',
            '/views/adm/changelocalactive.php',
            '/views/cms/pages.php',
            '/views/cms/content.php',
            '/views/cms/addpages.php',
            '/views/cms/getnameandholders.php',
            '/views/cms/editpages.php',
            '/views/cms/removepages.php',
            '/views/cms/getcontentplaceholders.php',
            '/views/cms/getplaceholdercontent.php',
            '/views/cms/editpagecontent.php',
            '/views/ajaxgetranslation',
            '/views/ajaxchangelocal',
            '/views/adm/translation.php',
            '/views/adm/translationgetkeys',
            '/views/adm/translationload',
            '/views/adm/edittranslation.php',
            '/views/adm/addtranslation.php',
            '/views/adm/removetranslation.php',
            '/views/api/app.php',
            '/views/api/addapp.php',
            '/views/api/regeneratetoken.php',
            '/views/api/deleteapp.php',
            '/views/auth/register.php',
            '/views/auth/doregister.php',
            '/views/auth/verifyemail.php',
            '/views/auth/resendemail.php',
            '/email/verify.php',
            '/views/auth/passwordnew.php',
            '/views/auth/passwordnewemail.php',
            '/views/auth/doresetpassword.php',
            '/views/auth/resetpassword.php',
            '/views/auth/resetpassworddone.php',
            '/views/auth/cookiedisclaimerenabled.php',
            '/views/auth/cookiedisclaimeraction.php',
            '/views/auth/cookiedisclaimerredirecturl.php',
        );
    }

    public function apiRoutes() {
        return array(
            '/api/v1/generate'
        );
    }

    public function routeMapping() {
        return array(
            '/' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'webRoot', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
            '/views/adm/home.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminHome', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/adm/insert.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminAddWords', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/adm/doinsert.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminDoAddWords', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true, "csrf_protection" => true),
            '/views/adm/modify.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminModifyWords', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/adm/ajaxlengths.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminModifyWordsLengths', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/adm/ajaxwords.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminModifyWordsGetWords', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/adm/removesingle.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminModifyWordsRemoveSingle', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/adm/removemulti.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminModifyWordsRemoveMulti', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/adm/managelanguages.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminManageLanguages', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/adm/addlanguage.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminAddLanguage', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/adm/removelanguage.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminRemoveLanguage', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/adm/updatelanguageresources.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminUpdateLanguageNameAndFilters', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/adm/doupdatelanguage.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminUpdateLanguage', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/adm/addadmin.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminAddAdmin', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/adm/doaddadmin.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminDoAddAdmin', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true, "csrf_protection" => true),
            '/views/adm/localization.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminLocalization', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/adm/addlocal.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminAddLocal', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/adm/removelocal.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminRemoveLocal', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/adm/changegetlocalactive.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminChangeLocalGetActive', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/adm/changelocalactive.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminChangeLocalActive', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/cms/pages.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'cmsPages', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/cms/addpages.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'cmsAddPages', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/cms/getnameandholders.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'cmsGetPageNameAndPlaceholders', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/cms/editpages.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'cmsEditPages', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/cms/removepages.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'cmsRemovePages', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/cms/content.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'cmsContent', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/cms/getcontentplaceholders.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'cmsGetContentPlaceholders', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/cms/getplaceholdercontent.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'cmsGetPlaceholderContent', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/cms/editpagecontent.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'cmsEditPageContent', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/documentation.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'webApiDocs', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
            '/views/auth/login.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'login', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false, 'redirect_auth' => true),
            '/views/auth/dologin.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'doLogin', 'request' => self::REQUEST_POST, 'role' => "", 'auth' => false),
            '/views/auth/register.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'register', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false, 'redirect_auth' => true),
            '/views/auth/doregister.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'doRegister', 'request' => self::REQUEST_POST, 'role' => "", 'auth' => false),
            '/views/auth/verifyemail.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'verifyEmail', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
            '/views/auth/resendemail.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'resendEmail', 'request' => self::REQUEST_POST, 'role' => "", 'auth' => false),
            '/email/verify.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'doVerifyEmail', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
            '/views/auth/logout.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'logout', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => true),
            '/views/auth/passwordnew.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'newPassword', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
            '/views/auth/cookiedisclaimerenabled.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'cookieDisclaimerEnabled', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
            '/views/auth/cookiedisclaimeraction.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'cookieDisclaimerAction', 'request' => self::REQUEST_POST, 'role' => "", 'auth' => false),
            '/views/auth/cookiedisclaimerredirecturl.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'cookieDisclaimerDeclineRedirectUrl', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
            '/views/auth/passwordnewemail.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'newPasswordEmail', 'request' => self::REQUEST_POST, 'role' => "", 'auth' => false),
            '/views/auth/resetpassword.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'resetPassword', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
            '/views/auth/doresetpassword.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'doResetPassword', 'request' => self::REQUEST_POST, 'role' => "", 'auth' => false),
            '/views/auth/resetpassworddone.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'resetPasswordDone', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
            '/views/error.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'error', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
            '/views/test.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'test', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
            '/views/ajaxgetranslation' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'ajaxGetTranslation', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
            '/views/ajaxchangelocal' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'ajaxChangeUserLocal', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
            '/views/adm/translation.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminTranslation', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/adm/translationgetkeys' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminTranslationGetKeys', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/adm/translationload' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminTranslationLoad', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/adm/edittranslation.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminEditTranslation', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/adm/addtranslation.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminAddTranslation', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/adm/removetranslation.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminRemoveTranslation', 'request' => self::REQUEST_POST, 'role' => "admin", 'auth' => true),
            '/views/api/app.php' => array('class' => Factory::TYPE_API_CONTROLLER_INTERNAL, 'method' => 'getApp', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => true),
            '/views/api/addapp.php' => array('class' => Factory::TYPE_API_CONTROLLER_INTERNAL, 'method' => 'addApp', 'request' => self::REQUEST_POST, 'role' => "", 'auth' => true),
            '/views/api/regeneratetoken.php' => array('class' => Factory::TYPE_API_CONTROLLER_INTERNAL, 'method' => 'regenerateToken', 'request' => self::REQUEST_POST, 'role' => "", 'auth' => true),
            '/views/api/deleteapp.php' => array('class' => Factory::TYPE_API_CONTROLLER_INTERNAL, 'method' => 'deleteApp', 'request' => self::REQUEST_POST, 'role' => "", 'auth' => true),
        );
    }

    public function apiRouteMapping() {
        return array(
            '/api/v1/generate' => array('class' => Factory::TYPE_API_CONTROLLER, 'method' => 'generateWords', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
        );
    }

    public function routeAll() {
        $requestUri = explode("?", strtolower($_SERVER["REQUEST_URI"]))[0];
        if(in_array($requestUri, $this->webRoutes())) {
            $this->route($requestUri);
        } else {
             $this->routeApi($requestUri);
        }
    }

    public function route($requestUri) {
        $requestMethod = strtolower($_SERVER["REQUEST_METHOD"]);
        $routes = $this->routeMapping();

        $route = array();
        try {
            $route = $this->validateRequest($requestUri, $routes, $requestMethod);
        } catch(Exception $e) {
            $this->redirect(HttpParser::baseUrl() . "views/error.php?error=" . $e->getMessage() . "&code=" . $e->getCode(), $e->getCode());
        }

        $this->guardian->regenerateCsrfToken();

        $classObject = Factory::getObject($route['class']);
        try {
            $this->validateClassMethod($classObject, $route['method']);
        } catch(Exception $e) {
            $this->redirect(HttpParser::baseUrl() . "views/error.php?error=" . $e->getMessage() . "&code=" . $e->getCode(), $e->getCode());
        }

        $result = "";
        try {
            $result = call_user_func([$classObject, $route['method']]);
        } catch(Exception $e) {
            $this->redirect(HttpParser::baseUrl() . "views/error.php?error=" . $e->getMessage() . "&code=" . $e->getCode(), $e->getCode());
        }

        $this->returnResult($result);
    }

    public function routeApi($requestUri) {
        $requestMethod = strtolower($_SERVER["REQUEST_METHOD"]);
        $routes = $this->apiRouteMapping();

        try {
            $route = $this->validateRequest($requestUri, $routes, $requestMethod);
        } catch(Exception $e) {
            echo Factory::getObject(Factory::TYPE_API_EXCEPTION)->getApiException($e);
            die();
        }

        $classObject = Factory::getObject($route['class']);
        try {
            $this->validateClassMethod($classObject, $route['method']);
        } catch(Exception $e) {
            echo Factory::getObject(Factory::TYPE_API_EXCEPTION)->getApiException($e);
            die();
        }

        try {
            $result = json_encode(call_user_func([$classObject, $route['method']]), JSON_UNESCAPED_UNICODE);
        } catch(Exception $e) {
            echo Factory::getObject(Factory::TYPE_API_EXCEPTION)->getApiException($e);
            die();
        }

        $this->returnResult($result);
    }

    private function validateRequest(string $requestUri, array $routes, string $requestMethod) {
        if(!array_key_exists($requestUri, $routes)) {
            throw new Exception("Page do not exists", HttpCodes::HTTP_NOT_FOUND);
        }
        $route = $routes[$requestUri];
        if(!in_array($route['request'], $this->supportedRequestMethods)) {
            throw new Exception("Method not supported", HttpCodes::METHOD_NOT_ALLOWED);
        }

        if($requestMethod !== $route['request']) {
            throw new Exception("Method invalid", HttpCodes::HTTP_FORBIDDEN);
        }

        if($route['auth'] === true) {
            if(!$this->user->isAuthenticatedUser()) {
                $this->redirect(HttpParser::baseUrl() . "views/auth/login.php", HttpCodes::HTTP_FORBIDDEN);
            }
        }
        if(isset($route['redirect_auth']) &&  $route['redirect_auth'] === true) {
            if($this->user->isAuthenticatedUser()) {
                if($this->user->isAdmin()) {
                    $this->redirect(Config::get(Config::ADMIN_HOME), HttpCodes::HTTP_FORBIDDEN);
                } else {
                    $this->redirect(Config::get(Config::USER_HOME), HttpCodes::HTTP_FORBIDDEN);
                }
            }
        }
        if(isset($route["csrf_protection"]) && $route["csrf_protection"] === true) {
            $this->guardian->checkCsrfToken();
        }
        if(!empty($route["role"])) {
            if(!$this->user->hasRole($route['role'])) {
                throw new Exception("Forbidden", HttpCodes::HTTP_FORBIDDEN);
            }
        }

        return $route;
    }

    private function validateClassMethod(object $classObject, string $classMethod) {
        if(!method_exists($classObject, $classMethod)) {
            throw new Exception("Method not found", HttpCodes::INTERNAL_SERVER_ERROR);
        }
    }

    private function returnResult(string $result) {
        http_response_code(HttpCodes::HTTP_OK);
        echo $result;
    }

    public function redirect(string $url, ?int $code=null, ?array $data = array(), $timeout=0) {
        $code = ($code)? $code : HttpCodes::HTTP_OK;
        http_response_code($code);
        if(!empty($data)) {
            $httpQuery = http_build_query($data);
            $url = $url . "?" . $httpQuery;
        }
        if($timeout > 0) {
            header(sprintf("refresh:%d;url=%s", $timeout, $url));
        } else  {
            header("Location: " . $url);
        }
        die();
    }
}