<?php


class Controller
{
    private $request;
    private $htmlParser;

    public function __construct(Request $request, HtmlParser $htmlParser) {
        $this->request = $request;
        $this->htmlParser = $htmlParser;
    }

    public function webRoot() {
        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/documentation.php");
    }

    public function webApiDocs() {
        //return "added";

        $arrayData = array("key1" => "array_value_1", "key2" => "array_value_2");
        $arrayData2 = array("two1" => "array_two1", "two2" => "array_two2");
        //$this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_SUCCESS_KEY, "This is success message");
        $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_ERROR_KEY, "This is error message");
        $this->htmlParser->oneTimeMessage(HtmlParser::ONE_TIME_WARNING_KEY, "This is warning message");
        return $this->htmlParser->parseView("admin:test", array("var1" => "value1", "var2" => "value2", "var3", "array_data" => $arrayData, "array_data2" => $arrayData2));
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