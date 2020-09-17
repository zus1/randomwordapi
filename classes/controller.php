<?php


class Controller
{
    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function webApiDocs() {
        return "added";
    }

    public function login() {
        return "logged";
    }

    public function doLogin() {

    }

    public function error() {
        $error = $this->request->error;
        $code = $this->request->code;
        return "error: " . $error . ", " . $code;
    }
}