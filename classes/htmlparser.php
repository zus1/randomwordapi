<?php

class HtmlParser
{
    private $viewsPath;
    private $includesPath;
    private $jsUrl;
    private $cssUrl;

    const ONE_TIME_ERROR_KEY = "error";
    const ONE_TIME_SUCCESS_KEY = "success";
    const ONE_TIME_WARNING_KEY = "warning";

    const LOOP_HOLDERS = array(
        'foreach' => "@foreach.(data)",
        'end_foreach' => '@foreach',
        'view_session' => "@session.(key)"
    );

    public function __construct() {
        $this->viewsPath = HttpParser::root() . "/views";
        $this->includesPath = HttpParser::root() . "/views/includes";
        $this->jsUrl = HttpParser::baseUrl() . "js";
        $this->cssUrl = HttpParser::baseUrl() . "css";
    }

    private function startSession() {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function handleDirectoryStructure(string $view) {
        if(!strpos($view, ":")) {
            return $view;
        }

        $dirParts = explode(":", $view);
        $dir = "";
        foreach($dirParts as $part) {
            $dir .= $part . "/";
        }

        return substr($dir, 0, strlen($dir) - 1);
    }

    private function handleHeaderOfFooterFilename(string $holder) {
        $holder = substr($holder, 0, strlen($holder) - 1); //now exclude ; we need it only for holder
        $holderParts = explode("-", $holder);
        $firstPart = substr($holderParts[0], 1);
        if(count($holderParts) === 1) {
            return $firstPart . ".html";
        }

        return $firstPart . ucfirst($holderParts[1]) . ".html";
    }

    public function parseView(string $view, ?array $data = array()) {
        $path =$this->viewsPath . "/" . $this->handleDirectoryStructure($view) . ".html";
        if(!file_exists($path)) {
            throw new Exception("View not found", HttpCodes::INTERNAL_SERVER_ERROR);
        }
        $contents = file_get_contents($path);
        if(!$contents) {
            return "";
        }

        $contents = $this->includeHeaderAndFooter($contents);
        $contents = $this->includeOneTimeMessage($contents);
        $contents = $this->handleGeneralHolders($contents);
        if(!empty($data)) {
            $contents = $this->handleViewSpecificHolders($contents, $data);
        }

        return $contents;
    }

    private function includeOneTimeMessage(string $contents) {
        $this->startSession();
        if((!strpos($contents, "@session") && substr($contents, 0, strlen("@session")) !== "@session") || !isset($_SESSION['view'])) {
            return $contents;
        }

        $start = 0;
        while(($start = strpos($contents, "@session", $start)) !== false) {
            $contents = $this->doIncludeOneTimeMessage($contents, $start);
        }

        unset($_SESSION['view']);

        return $contents;
    }

    private function doIncludeOneTimeMessage(string $contents, int $startIndex) {
        $endIndex = strpos($contents, ")", $startIndex) + 1;
        $holder = substr($contents, $startIndex, $endIndex - $startIndex);
        $sessionKey = $this->extractKeyFromHolder($holder);
        $message = $_SESSION['view'][$sessionKey];
        if($message) {
            $contents = str_replace($holder, $message, $contents);
        }

        return $contents;
    }

    public function oneTimeMessage($key, $message) {
        $this->startSession();
        if($key === self::ONE_TIME_ERROR_KEY) {
            $_SESSION["view"][self::ONE_TIME_ERROR_KEY] = $message;
        } elseif($key === self::ONE_TIME_SUCCESS_KEY) {
            $_SESSION["view"][self::ONE_TIME_SUCCESS_KEY] = $message;
        } elseif($key === self::ONE_TIME_WARNING_KEY) {
            $_SESSION["view"][self::ONE_TIME_WARNING_KEY] = $message;
        }
    }

    private function includeHeaderAndFooter(string $contents) {
        if(strpos($contents, "@header") || substr($contents, 0, strlen("@header")) === "@header") {
            $contents = $this->doIncludeHeaderOrFooter($contents, "@header", "Header not found");
        }
        if(strpos($contents, "@footer")) {
            $contents = $this->doIncludeHeaderOrFooter($contents, "@footer", "Footer not found");
        }

        return $contents;
    }

    private function doIncludeHeaderOrFooter(string $contents, string $baseHolder, string $exception) {
        $holder = $this->extractHeaderOrFooterHolder($contents, $baseHolder);
        if($holder !== "") {
            $fileName = $this->handleHeaderOfFooterFilename($holder);
            $filePath = $this->includesPath . "/" . $fileName;
            if(!file_exists($filePath)) {
                throw new Exception($exception, HttpCodes::INTERNAL_SERVER_ERROR);
            }
            $includeContents = file_get_contents($filePath);
            if($includeContents) {
                $contents = str_replace($holder, $includeContents, $contents);
            }
        }

        return $contents;
    }

    private function extractHeaderOrFooterHolder(string $contents, $baseHolder) {
        $start = strpos($contents, $baseHolder);
        $end = strpos($contents, ";", $start) + 1; //also include ;
        $holder = substr($contents, $start, $end - $start);

        return $holder;
    }

    private function handleViewSpecificHolders(string $contents, array $data) {
        $loopData = array();
        array_walk($data, function ($value, $key) use (&$contents, &$loopData) {
           if(is_array($value)) {
               $loopData[$key] = $value;
           }  else {
               if(strpos($contents, "{" . $key . "}")) {
                   $contents = str_replace("{" . $key . "}", $value, $contents);
               }
           }
        });

        if(!empty($loopData)) {
            $contents = $this->handleLoops($contents, $loopData);
        }

        return $contents;
    }

    private function handleGeneralHolders(string $contents) {
        $holders = array(
            "{bootstrap_css}" => $this->cssUrl . "/bootstrap.css",
            "{main_css}" => $this->cssUrl . "/main.css",
            "{bootstrap_js}" => $this->jsUrl . "/bootstrap.js",
            "{base_url}" => HttpParser::baseUrl(),
        );
        array_walk($holders, function ($value, $key) use (&$contents) {
           if(strpos($contents, $key)) {
               $contents = str_replace($key, $value, $contents);
           }
        });

        return $contents;
    }

    private function handleLoops(string $contents, array $loopData) {
        if(strpos($contents, "@foreach")) {
            $start = 0;
            while(($start = strpos($contents, "@foreach", $start)) !== false) {
                $contents = $this->handleLoopForeachElement($contents, $loopData, $start);
            }
        }

        return $contents;
    }

    private function handleLoopForeachElement(string $contents, array $loopData, int $startIndex) {
        $previousContents = substr($contents, 0, $startIndex);

        $endHolder = strpos($contents, ")", $startIndex) + 1; //include )
        $holder = substr($contents, $startIndex, $endHolder - $startIndex);

        $startElement = $endHolder;
        $endElement = strpos($contents, "@endforeach", $startElement);
        $element = substr($contents, $startElement, $endElement - $startElement);

        $nextContents = substr($contents, $endElement + strlen("@endforeach"));

        $loopDataKey = $this->extractKeyFromHolder($holder);
        $data = $loopData[$loopDataKey];

        $tempElement = $element;
        foreach($data as $key => $value) {
            $tempElement = str_replace("@key", $key, $tempElement);
            $tempElement = str_replace("@value", $value, $tempElement);
            $previousContents .= $tempElement;
            $tempElement = $element;
        }

        return $previousContents . $nextContents;
    }

    private function extractKeyFromHolder(string $holder) {
        $holderParts = explode(".", $holder);
        return substr($holderParts[1], 1, strlen($holderParts[1]) - 2);
    }
}