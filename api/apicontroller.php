<?php


class ApiController
{
    private $request;
    private $apiValidator;
    private $apiException;

    public function __construct(Request $request, ApiValidator $validator, ApiException $exception) {
        $this->request = $request;
        $this->apiValidator = $validator;
        $this->apiException = $exception;
    }

    public function generateWords() {
        $queryVariables = array();
        $queryVariables = $this->request->getParsedRequestQuery($queryVariables);
        $requestPath = $this->request->getRequestPath();

        $this->apiValidator->validateQueryVariables($queryVariables);
        $this->apiValidator->validateVersion($requestPath);
    }
}