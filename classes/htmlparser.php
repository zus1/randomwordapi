<?php

class HtmlParser
{
    private $viewsPath;
    private $includesPath;
    private $jsPath;
    private $jsUrl;
    private $cssUrl;

    const ONE_TIME_ERROR_KEY = "error";
    const ONE_TIME_SUCCESS_KEY = "success";
    const ONE_TIME_WARNING_KEY = "warning";
    const IF_TAG_AUTH = "@auth";
    const IF_TAG_ROLE = "@role";

    const LOOP_HOLDERS = array(
        'foreach' => "@foreach.(data)",
        'end_foreach' => '@foreach',
        'view_session' => "@session.(key)"
    );

    private $ifTagToCallMapping = array(
        self::IF_TAG_AUTH => array("object" => Factory::TYPE_USER, "method" => "isAuthenticatedUser"),
        self::IF_TAG_ROLE => array("object" => Factory::TYPE_USER, "method" => "hasRole")
    );

    private $possibleIfTags = array(
        self::IF_TAG_ROLE, self::IF_TAG_AUTH
    );

    private $session;
    private $request;
    private $guardian;
    private $extender;

    public function __construct(Session $session, Request $request, Guardian $guardian, HtmlParserExtender $extender) {
        $this->viewsPath = HttpParser::root() . "/views";
        $this->includesPath = HttpParser::root() . "/views/includes";
        $this->jsPath = HttpParser::root() . "/js";
        $this->jsUrl = HttpParser::baseUrl() . "js";
        $this->cssUrl = HttpParser::baseUrl() . "css";
        $this->session = $session;
        $this->request = $request;
        $this->guardian = $guardian;
        $this->extender = $extender;
    }

    private function getPlaceholderAndKey(string $contents, int $startIndex) {
        $endHolder = strpos($contents, ")", $startIndex);
        $holder = substr($contents, $startIndex, ($endHolder + 1) - $startIndex);
        $requestKey = $this->extractKeyFromHolder($holder);

        return array($holder, $requestKey);
    }

    private function extractKeyFromHolder(string $holder) {
        $holderParts = explode(".", $holder);
        return substr($holderParts[1], 1, strlen($holderParts[1]) - 2);
    }

    public function formatValidatorErrorMessages(array $errorMessages) {
        $startTag = "<ul>";
        $endTag = "</ul>";
        $liTags = "";
        array_walk($errorMessages, function($errorMessage) use (&$liTags) {
           $liTags .= "<li>" . $errorMessage . "</li>";
        });

        return $startTag . $liTags . $endTag;
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
        $contents = $this->handleIncludes($contents);
        foreach($this->possibleIfTags as $ifTag) {
            $contents = $this->handleIfTags($contents, $ifTag);
        }
        $contents = $this->includeOneTimeMessage($contents);
        $contents = $this->handleGeneralHolders($contents, $view);
        if(!empty($data)) {
            $contents = $this->handleViewSpecificHolders($contents, $data);
        }
        $contents = $this->handleOlRequestData($contents);
        $contents = $this->handleTranslations($contents);

        return $contents;
    }

    private function handleTranslations(string $contents) {
        if(!strpos($contents, "@trans.(") && substr($contents, 0, strlen("@trans.(")) !== "@trans.(") {
            return $contents;
        }
        $start = 0;
        while(($start = strpos($contents, "@trans.(", $start)) !== false) {
            list($holder, $translationKey) = $this->getPlaceholderAndKey($contents, $start);
            $translation = Translator::get($translationKey);
            if(!empty($translation)) {
                $contents = str_replace($holder, $translation, $contents);
            }
            $start++;
        }

        return $contents;
    }

    private function handleIfTags(string $contents, string $tag) {
        //preventing errors with strpos, possible false value if all tags not present
        if(!strpos($contents, $tag . ".") || !strpos($contents, $tag . "?")) {
            return $contents;
        }
        $start = 0;
        while(($start = strpos($contents, $tag . ".", $start)) !== false) {
            $endFirst = strpos($contents, "?", $start);
            $first = substr($contents, $start, ($endFirst + 1) - $start);

            $startLast = strpos($contents, $tag . "?", $start);
            $ifElement = substr($contents, $endFirst, $startLast - $endFirst);
            $ifElement = $this->clearIfSubElements($ifElement); //clear all other if elements from sub element, to avoid detecting wrong tags
            $thirdPresent = false;
            if(strpos($ifElement, "@else")) {
                $thirdPresent = true;
                $secondStart = strpos($contents, "@else", $endFirst);
                $secondEnd = $secondStart + strlen("@else");
            } else {
                $secondStart = strpos($contents, $tag . "?", $endFirst);
                $secondEnd = $secondStart + strlen($tag . "?");
            }

            if($thirdPresent === true) {
                $thirdStart = strpos($contents, $tag . "?", $secondEnd);
                $thirdEnd = $thirdStart + strlen($tag . "?");
            }

            $beforeElement = substr($contents, 0, $start);
            $firstElement = substr($contents, $endFirst + 1, ($secondStart - 1) - $endFirst);
            if($thirdPresent === true) {
                $secondElement = substr($contents, $secondEnd + 1, ($thirdStart - 1) - $secondEnd);
                $afterElement = substr($contents, $thirdEnd);
            } else {
                $afterElement = substr($contents, $secondEnd);
            }

            list($result, $condition) = $this->extractIfTagCondition($first);
            $pass = $this->checkIfTagCondition($result, $condition, $tag);

            if($pass === true) {
                $contents = $beforeElement . $firstElement . $afterElement;
            } elseif($thirdPresent === true) {
                $contents = $beforeElement . $secondElement . $afterElement;
            } else {
                $contents = $beforeElement . $afterElement;
            }
            $start++;
        }

        return $contents;
    }

    private function clearIfSubElements(string $element) {
        foreach($this->possibleIfTags as $tag) {
            if(strpos($element, $tag . ".")) {
                $subFirstStart = strpos($element, $tag . ".");
                $subFirstEnd = strpos($element, "?", $subFirstStart);

                $subLastStart = strpos($element, $tag . "?", $subFirstEnd);
                $subLastEnd = $subLastStart + strlen($tag . "?");

                $beforeElement = substr($element, 0, $subFirstStart);
                $afterElement = substr($element, $subLastEnd);

                $element = $beforeElement . $afterElement;
            }
        }

        return $element;
    }

    private function extractIfTagCondition(string $holder) {
        $tagResultRawCondition = explode(".", $holder);
        if(empty($tagResultRawCondition[2])) {
            return array($tagResultRawCondition[1], "");
        }
        $result = $tagResultRawCondition[1];
        $condition = substr($tagResultRawCondition[2], 0, strlen($tagResultRawCondition[2]) - 1);
        return array($result, $condition);
    }

    private function checkIfTagCondition(string $result, string $condition, string $tag) {
        $result = array("true" => true, "false" => false)[$result];
        $callVariables = $this->ifTagToCallMapping[$tag];
        if($condition === "") {
            $checkResult = call_user_func([Factory::getObject($callVariables["object"]), $callVariables["method"]]);
        } else {
            $checkResult = call_user_func_array([Factory::getObject($callVariables["object"]), $callVariables["method"]], array($condition));
        }

        if($checkResult === $result) {
            return true;
        }

        return false;
    }

    private function handleOlRequestData(string $contents) {
       $oldRequestData = array();
       if(isset($_SESSION["old_request"])) {
           $oldRequestData = $_SESSION["old_request"];
       }

       if(empty($oldRequestData)) {
           $contents = $this->excludeOldDataHolders($contents);
       } else {
           $contents = $this->includeOldRequestData($contents, $oldRequestData);
           unset($_SESSION["old_request"]);
       }

       return $contents;
    }

    private function includeOldRequestData(string $contents, array $oldRequestData) {
        $start = 0;
        while(($start = strpos($contents, "@old.(", $start)) !== false) {
            list($holder, $requestKey) = $this->getPlaceholderAndKey($contents, $start);
            if(array_key_exists($requestKey, $oldRequestData)) {
                $contents = str_replace($holder, $oldRequestData[$requestKey], $contents);
            }
        }

        return $contents;
    }

    private function excludeOldDataHolders(string $contents) {
        $start = 0;
        while(($start = strpos($contents, "@old.(", $start)) !== false) {
            $holderEnd = strpos($contents, ")", $start);
            $holder = substr($contents, $start, ($holderEnd + 1) - $start);
            $contents = str_replace($holder, "", $contents);
            $start++;
        }

        return $contents;
    }

    private function handleIncludes(string $contents) {
        $start = 0;
        while(($start = strpos($contents, "@include.(", $start)) !== false) {
            list($holder, $fileName) = $this->getPlaceholderAndKey($contents, $start);
            $path = $this->includesPath . "/" . $fileName . ".html";
            if(!file_exists($path)) {
                throw new Exception("File not found", HttpCodes::INTERNAL_SERVER_ERROR);
            }
            $includeContents = file_get_contents($path);

            $contents = str_replace($holder, $includeContents, $contents);

            $start++;
        }

        return $contents;
    }

    private function includeOneTimeMessage(string $contents) {
        $this->session->startSession();
        if((!strpos($contents, "@session") && substr($contents, 0, strlen("@session")) !== "@session") && !isset($_SESSION['view'])) {
            return $contents;
        }
        $contents = $this->checkOneTimeMessageElements($contents);
        if(!isset($_SESSION['view'])) {
            return $contents;
        }
        $start = 0;
        while(($start = strpos($contents, "@session.(", $start)) !== false) {
            $contents = $this->doIncludeOneTimeMessage($contents, $start);
            $start++;
        }

        unset($_SESSION['view']);

        return $contents;
    }

    private function checkOneTimeMessageElements(string $contents) {
        if(strpos($contents, "@session.success?") && !isset($_SESSION['view'][self::ONE_TIME_SUCCESS_KEY])) {
            $start = 0;
            while(($start = strpos($contents, "@session.success?", $start)) !== false) {
                $contents = $this->excludeNotUseOneTimeMessageElement($contents, $start);
                $start++;
            }
        } else {
            $contents = $this->excludeNotNeededOnTimeMessageCheckHolder($contents, "@session.success?");
        }
        if(strpos($contents, "@session.error?") && !isset($_SESSION['view'][self::ONE_TIME_ERROR_KEY])) {
            $start = 0;
            while(($start = strpos($contents, "@session.error?", $start)) !== false) {
                $contents = $this->excludeNotUseOneTimeMessageElement($contents, $start);
                $start++;
            }
        } else {
            $contents = $this->excludeNotNeededOnTimeMessageCheckHolder($contents, "@session.error?");
        }
        if(strpos($contents, "@session.warning?") && !isset($_SESSION['view'][self::ONE_TIME_WARNING_KEY])) {
            $start = 0;
            while(($start = strpos($contents, "@session.warning?", $start)) !== false) {
                $contents = $this->excludeNotUseOneTimeMessageElement($contents, $start);
                $start++;
            }
        } else {
            $contents = $this->excludeNotNeededOnTimeMessageCheckHolder($contents, "@session.warning?");
        }

        return $contents;
    }

    private function excludeNotUseOneTimeMessageElement(string $contents, int $startIndex) {
        $previousContents = substr($contents, 0, $startIndex);
        $endIndex = strpos($contents, "@session?", $startIndex);
        $nextContents = substr($contents, $endIndex + strlen("@session?"));

        return $previousContents . $nextContents;
    }

    private function excludeNotNeededOnTimeMessageCheckHolder(string $contents, $checkStartHolder) {
        $start = 0;
        while(($start = strpos($contents, $checkStartHolder, $start)) !== false) {
            $previousStart = substr($contents, 0, $start);
            $endHolderIndex = strpos($contents, "@session?", $start);
            $messageElement = substr($contents, $start + strlen($checkStartHolder), ($endHolderIndex) - ($start + strlen($checkStartHolder)));
            $nextEnd = substr($contents, $endHolderIndex + strlen("@session?"));
            $contents = $previousStart . $messageElement . $nextEnd;
            $start++;
        }

        return $contents;
    }

    private function doIncludeOneTimeMessage(string $contents, int $startIndex) {
        list($holder, $sessionKey) = $this->getPlaceholderAndKey($contents, $startIndex);
        $message = "";
        if(isset($_SESSION['view'][$sessionKey])) {
            $message = $_SESSION['view'][$sessionKey];
        }
        if($message) {
            $contents = str_replace($holder, $message, $contents);
        }

        return $contents;
    }

    public function oneTimeMessage($key, $message) {
        $this->session->startSession();
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
        $singleData = array();
        array_walk($data, function ($value, $key) use (&$contents, &$loopData, &$singleData) {
           if(is_array($value)) {
               $loopData[$key] = $value;
           } else {
               $singleData[$key] = $value;
           }
        });

        if(!empty($loopData)) {
            $contents = $this->handleLoops($contents, $loopData);
        }
        if(!empty($singleData)) {
            $contents = $this->handleSingle($contents, $singleData);
        }

        return $contents;
    }

    private function handleSingle(string $contents, array $singleData) {
        array_walk($singleData, function($value, $key) use (&$contents) {
            if(strpos($contents, "{" . $key . "}")) {
                $contents = str_replace("{" . $key . "}", $value, $contents);
            }
        });

        return $contents;
    }

    private function getGeneralHolders(string $view) {
        $defaultHolders = array(
            "{bootstrap_css}" => $this->cssUrl . "/bootstrap.css",
            "{main_css}" => $this->cssUrl . "/main.css",
            "{bootstrap_js}" => $this->jsUrl . "/bootstrap.js",
            "{base_url}" => HttpParser::baseUrl(),
            "{csrf_token}" => $this->generateCsrfTokenField()
        );
        $extend = $this->extender->includeToAllViews();
        $extendViewSpecific = $this->extender->includeToSpecificView($view);

        $defaultHolders = array_merge($defaultHolders, $extend);
        $defaultHolders = array_merge($defaultHolders, $extendViewSpecific);
        return $defaultHolders; //if keys overlap, override defaults with extended ones
    }

    private function handleGeneralHolders(string $contents, string $view) {
        $holders = $this->getGeneralHolders($view);
        array_walk($holders, function ($value, $key) use (&$contents) {
           if(strpos($contents, $key) || substr($contents, 0, strlen($key)) === $key) {
               $contents = str_replace($key, $value, $contents);
           }
        });

        return $contents;
    }

    private function generateCsrfTokenField() {
        $this->session->startSession();
        $sessionKey = $this->guardian->getCsrfSessionKey();
        $value = $_SESSION[$sessionKey];
        $name = Guardian::CSRF_TOKEN_FIELD_NAME;

        return sprintf("<input type='hidden' name='%s' value='%s'>", $name, $value);
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
            if(is_array($value)) {
                $tempElement = $this->handleParseArrayValueForLoops($tempElement, $value);
            } else {
                $tempElement = str_replace("@value", $value, $tempElement);
            }
            $tempElement = str_replace("@key", $key, $tempElement);
            $previousContents .= $tempElement;
            $tempElement = $element;
        }

        return $previousContents . $nextContents;
    }

    private function handleParseArrayValueForLoops(string $element, array $subArray) {
        $start = 0;
        while(($start = strpos($element, "@value.(", $start)) !== false) {
            list($holder, $key) = $this->getPlaceholderAndKey($element, $start);
            if(array_key_exists($key, $subArray)) {
                $element = str_replace($holder, $subArray[$key], $element);
            }

            $start++;
        }

        return $element;
    }
}