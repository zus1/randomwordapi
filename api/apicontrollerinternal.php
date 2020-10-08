<?php

class ApiControllerInternal
{
    private $request;
    private $apiValidator;
    private $apiApp;
    private $apiUser;
    private $htmlParser;

    public function __construct(Request $request, ApiValidator $validator, ApiApp $app, ApiUser $apiUser, HtmlParser $parser) {
        $this->request = $request;
        $this->apiValidator = $validator;
        $this->apiApp = $app;
        $this->apiUser = $apiUser;
        $this->htmlParser = $parser;
    }

    public function getApp() {
        $userId = $this->apiUser->getAuthenticatedUserId();
        try {
            $existingApps = $this->apiApp->getApiApp(array("id", 'name', "access_token", "rate_limit", "limit_reached", "requests_spent"), array('user_id' => $userId));
        } catch(Exception $e) {
            Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/error.php", $e->getCode(),
                array("error" => $e->getMessage(), "code" => $e->getCode()));
        }

        return $this->htmlParser->parseView("app", array("apps" => $existingApps));
    }

    public function addApp() {
        $appName = $this->request->input("app-name");

        if($this->apiValidator->validate("app-name", array(Validator::FILTER_ALPHA_NUM))->isFailed()) {
            $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, $this->apiValidator->getMessages()[0]);
            Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/api/app.php");
        }

        try {
            $this->apiApp->addApiApp($appName);
        } catch(Exception $e) {
            $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, $e->getMessage());
            Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/api/app.php");
        }

        $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_SUCCESS_KEY, "New application created");
        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/api/app.php");
    }

    public function regenerateToken() {
        $appId = $this->request->input("app_id");
        $currentToken = $this->request->input("current_token");

        if($this->apiValidator->validate("app_id", array(Validator::FILTER_NUMERIC))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->apiValidator->getMessages()[0]));
        }
        $this->apiValidator->resetMessages();
        if($this->apiValidator->validate("current_token", array(Validator::FILTER_ALPHA_NUM_UNDERSCORE))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->apiValidator->getMessages()[0]));
        }

        try {
            $newToken = $this->apiApp->regenerateAccessToken($appId, $currentToken);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "Token updated", "new_token" => $newToken, 'id' => $appId));
    }

    public function deleteApp() {
        $appId = $this->request->input("app_id");

        if($this->apiValidator->validate("app_id", array(Validator::FILTER_NUMERIC))->isFailed()) {
            return json_encode(array("error" => 1, "message" => $this->apiValidator->getMessages()[0]));
        }

        try {
            $this->apiApp->deleteApp($appId);
        } catch(Exception $e) {
            return json_encode(array("error" => 1, "message" => $e->getMessage()));
        }

        return json_encode(array("error" => 0, "message" => "App Deleted"));
    }
}