<?php


class ApiController
{
    private $request;
    private $apiValidator;
    private $words;
    private $apiApp;
    private $apiGuardian;
    private $ipChecker;

    public function __construct(Request $request, ApiValidator $validator, Words $words, ApiApp $apiApp, ApiGuardian $guardian, IpChecker $ipChecker) {
        $this->request = $request;
        $this->apiValidator = $validator;
        $this->words = $words;
        $this->apiApp = $apiApp;
        $this->apiGuardian = $guardian;
        $this->ipChecker = $ipChecker;
    }

    public function generateWords() {
        $this->apiGuardian->checkAccessToken();
        $this->ipChecker->checkIp();
        $this->apiApp->checkApp();

        $queryVariables = array();
        $queryVariables = $this->request->getParsedRequestQuery($queryVariables);
        $requestPath = $this->request->getRequestPath();

        $this->apiValidator->validateQueryVariables($queryVariables);
        $this->apiValidator->validateVersion($requestPath);

        $tag = $queryVariables["language"];
        $minLength = (int)$queryVariables["min_length"];
        $maxLength = (int)$queryVariables["max_length"];
        $wordsNum = (int)$queryVariables["words_num"];
        $words = $this->words->getWords($tag, $minLength, $maxLength, $wordsNum);

        $this->apiApp->addResponseHeaders();
        return array("error" => 0, "words" => $words);
    }
}