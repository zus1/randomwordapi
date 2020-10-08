<?php


class ApiController
{
    private $request;
    private $apiValidator;
    private $words;
    private $apiApp;

    public function __construct(Request $request, ApiValidator $validator, Words $words, ApiApp $apiApp) {
        $this->request = $request;
        $this->apiValidator = $validator;
        $this->words = $words;
        $this->apiApp = $apiApp;
    }

    public function generateWords() {
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