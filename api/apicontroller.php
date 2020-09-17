<?php


class ApiController
{
    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function generateWords() {
        return array("data" => "ok");
    }
}