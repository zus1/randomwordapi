<?php

class ApiControllerInternal
{
    private $request;
    private $paiValidator;
    private $apiApp;
    private $apiUser;
    private $htmlParser;

    public function __construct(Request $request, ApiValidator $validator, ApiApp $app, ApiUser $apiUser, HtmlParser $parser) {
        $this->request = $request;
        $this->paiValidator = $validator;
        $this->apiApp = $app;
        $this->apiUser = $apiUser;
        $this->htmlParser = $parser;
    }

    public function getApp() {
        $userId = $this->apiUser->getAuthenticatedUserId();
        try {
            $existingApps = $this->apiApp->getApiApp(array("id", 'name', "access_token", "rate_limit", "limit_reached", "requests_spent"), array('user_id' => $userId['id']));
        } catch(Exception $e) {
            Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/error.php", $e->getCode(),
                array("error" => $e->getMessage(), "code" => $e->getCode()));
        }

        return $this->htmlParser->parseView("app", array("apps" => $existingApps));
    }

    public function addApp() {

    }
}