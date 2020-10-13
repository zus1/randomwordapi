<?php

class Response
{
    private $session;
    private $htmlParser;
    private $request;

    private $timeout = 0;

    public function __construct(Session $session, HtmlParser $parser, Request $request) {
        $this->session = $session;
        $this->htmlParser = $parser;
        $this->request = $request;
    }

    public function timeout(int $timeout) {
        $this->timeout = $timeout;
    }

    public function withOldData() {
        $this->session->startSession();
        $requestData = $this->request->getAll();
        if(!empty($requestData)) {
            $_SESSION["old_request"] = $requestData;
        }
        return $this;
    }

    public function returnView(string $view, ?array $data=array()) {
        return $this->htmlParser->parseView($view, $data);
    }

    public function returnRedirect(string $url, ?int $code=null, ?array $data=array()) {
        Factory::getObject(Factory::TYPE_ROUTER)->redirect($url, $code, $data, $this->timeout);
    }
}