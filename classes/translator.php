<?php

class Translator
{
    const ACTION_TRANSLATION_MAP_INSERT = "insert";
    const ACTION_TRANSLATION_MAP_UPDATE = "update";
    const ACTION_TRANSLATION_MAP_DELETE = "delete";

    private static $_default;
    private static $_translationsPath;
    private static $_initialized = false;

    private $jsonParser;

    public function __construct(JsonParser $jsonParser) {
        $this->jsonParser = $jsonParser;
    }

    private function getTranslationMapActionToMethodMapping() {
        return array(
            self::ACTION_TRANSLATION_MAP_INSERT => "translationMapInsert",
            self::ACTION_TRANSLATION_MAP_UPDATE => "translationMapUpdate",
            self::ACTION_TRANSLATION_MAP_DELETE => "translationMapDelete"
        );
    }

    private static function init() {
        self::$_default = Config::get(Config::TRANSLATION_DEFAULT);
        self::$_translationsPath = $_SERVER["DOCUMENT_ROOT"] . "/resources/translations";
        self::$_initialized = true;
    }

    public static function get(string $key, ?string $default = "") {
        if(self::$_initialized === false) {
            self::init();
            self::$_initialized = true;
        }

        $localObj = Factory::getObject(Factory::TYPE_LOCALIZATION);
        $local = $localObj->getActive();
        $filename = self::$_translationsPath . "/" . $local . ".json";
        $defaultLocal = $localObj->getDefault();
        $defaultFilename = self::$_translationsPath . "/" . $defaultLocal . ".json";

        try {
            list($localExists, $defaultExists) = self::checkTranslationFiles($filename, $defaultFilename);
        } catch(Exception $e) {
            return self::getDefaultTranslation($default);
        }

        $localOk = true;
        $defaultOk = true;
        $localContentArray = self::getTranslationFileContent($localExists, $filename, $localOk);
        $defaultContentArray = self::getTranslationFileContent($defaultExists, $defaultFilename, $defaultOk);

        $translation = "";
        self::getTranslation($localOk, $key, $localContentArray,$translation);
        self::getTranslation($defaultOk, $key, $defaultContentArray,$translation);
        if(empty($translation)) {
            $translation = self::getDefaultTranslation($default);
        }

        return $translation;
    }

    private static function checkTranslationFiles(string $localFileName, string $defaultFileName) {
        $localExists = file_exists($localFileName);
        $defaultExists = file_exists($defaultFileName);

        if(!$localExists && !$defaultExists) {
            throw new Exception("No files");
        }

        return array($localExists, $defaultExists);
    }

    private static function getTranslationFileContent(bool $exists, string $filename, bool &$jsonOk) {
        $contents = "";
        if($exists) {
            $contents = file_get_contents($filename);
        }

        $contentsArray = json_decode($contents, true);
        if(json_last_error() !== JSON_ERROR_NONE || !is_array($contentsArray)) {
            $jsonOk = false;
        }

        return $contentsArray;
    }

    private static function getTranslation(bool $jsonOk, string $key, $translationHaystack, string &$translation) {
        if(empty($translation)) {
            if($jsonOk === true) {
                $translation = $translationHaystack[$key];
            }
        }
    }

    private static function getDefaultTranslation(string $default) {
        if($default === "") {
            $translation = self::$_default;
        } else {
            $translation = $default;
        }

        return $translation;
    }

    public function getTranslationMap(string $local) {
        if(self::$_initialized === false) {
            self::init();
        }

        $fullPath = self::$_translationsPath . "/" . $local . ".json";
        $map = $this->jsonParser->parseFromFile($fullPath, true);
        if($this->jsonParser->isError() && $this->jsonParser->getLastErrorMessage() !== JsonParser::ERROR_FILE_NOT_FOUND) {
            throw new Exception($this->jsonParser->getLastErrorMessage());
        }

        return $map;
    }

    public function changeTranslationMap(string $local, string $key, string $translation, string $action) {
        $mapping = $this->getTranslationMapActionToMethodMapping();
        if(!array_key_exists($action, $mapping)) {
            throw new Exception("Action unknown");
        }

        call_user_func_array([$this, $mapping[$action]], array($local, $key, $translation));
    }

    private function translationMapInsert(string $local, string $key, string $translation) {

    }

    private function translationMapUpdate(string $local, string $key, string $translation) {
        $currentMap = $this->getTranslationMap($local);
        if(empty($currentMap) || !array_key_exists($key, $currentMap)) {
            throw new Exception("Translation missing");
        }
        $currentTranslation = $currentMap[$key];
        if($currentTranslation === $translation) {
            throw new Exception("No change");
        }

        $currentMap[$key] = $translation;
        $this->jsonParser->resetErrors();
        $this->setTranslationMap($local, $currentMap);
    }

    private function translationMapDelete(string $local, string $key, string $translation) {

    }

    public function setTranslationMap(string $local, array $contents, ?bool $new=false) {
        $fullPath = self::$_translationsPath . "/" . $local . ".json";
        $this->jsonParser->putToFile($fullPath, $contents, $new);
        if($this->jsonParser->isError()) {
            throw new Exception($this->jsonParser->getLastErrorMessage());
        }
    }
}