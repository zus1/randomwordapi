<?php

class ApiValidator extends Validator
{

    const FILTER_MAX = "max";
    const FILTER_MIN = "min";
    const FILTER_EQUALS = "equals";

    const PARAM_MIN_LENGTH = "min_length";
    const PARAM_MAX_LENGTH = "max_length";
    const PARAM_LANGUAGE = "language";
    const PARAM_WORDS_NUM = "words_num";

    private $allowedApiParameters = array(self::PARAM_MIN_LENGTH, self::PARAM_MAX_LENGTH, self::PARAM_LANGUAGE, self::PARAM_WORDS_NUM);

    protected function getValidFilters()
    {
        $extended = array(self::FILTER_MAX, self::FILTER_MIN, self::FILTER_EQUALS);
        return array_merge(parent::getValidFilters(), $extended);
    }

    protected function getFilterToMethodMapping()
    {
        $extended = array(
            self::FILTER_MAX => "filterMax",
            self::FILTER_MIN => "filterMin",
            self::FILTER_EQUALS => "filterEquals"
        );
        return array_merge(parent::getFilterToMethodMapping(), $extended);
    }

    protected function getErrorMessagesDefinition()
    {
        $extended = array(
            self::FILTER_MAX => "Value for field {field} can't be grater then {num}",
            self::FILTER_MIN => "Value for field {field} can't be less then {num}",
            self::FILTER_EQUALS => "Value for field {field} must equals to {num}",
        );
        return array_merge(parent::getErrorMessagesDefinition(), $extended);
    }

    public function filterMax($value, int $max) {
        return ($value <= $max)? $value : $value - 1;
    }

    public function filterMin($value, int $min) {
        return ($value  >= $min)? $value : $value + 1;
    }

    public function filterEquals($value, int $equals) {
        return ($value == $equals)? $value : $value + 1;
    }

    public function validateQueryVariables(array $queryVariables) {
        $apiMinWordLength = Config::get(Config::API_MIN_WORD_LENGTH);
        $apiMaxWordLength = Config::get(Config::API_MAX_WORD_LENGTH);
        $apiMinWordsCount = Config::get(Config::API_MIN_WORDS);
        $apiMaxWordsCount = Config::get(Config::API_MAX_WORDS);
        $params = array_keys($queryVariables);
        $extraParams = array_diff($params, $this->allowedApiParameters);
        if(count($extraParams) > 0) {
            throw new Exception("Bad Request: Invalid parameters", HttpCodes::HTTP_BAD_REQUEST);
        }
        if(!isset($queryVariables[self::PARAM_MIN_LENGTH])) {
            throw new Exception("Bad Request: Min length missing", HttpCodes::HTTP_BAD_REQUEST);
        }
        if(!isset($queryVariables[self::PARAM_MAX_LENGTH])) {
            throw new Exception("Bad Request: Max length missing", HttpCodes::HTTP_BAD_REQUEST);
        }
        if(!isset($queryVariables[self::PARAM_WORDS_NUM])) {
            throw new Exception("Bad Request: Number of words missing", HttpCodes::HTTP_BAD_REQUEST);
        }
        if(!isset($queryVariables[self::PARAM_LANGUAGE])) {
            throw new Exception("Bad Request: Language missing", HttpCodes::HTTP_BAD_REQUEST);
        }

        if($this->validate("min_length", array(self::FILTER_NUMERIC), $queryVariables[self::PARAM_MIN_LENGTH])->validate("min_length", array(self::FILTER_MIN . ":" . $apiMinWordLength), $queryVariables[self::PARAM_MIN_LENGTH])->isFailed()) {
            throw new Exception("Bad Request: Min length malformed. Expected integer with min value 1", HttpCodes::HTTP_BAD_REQUEST);
        }
        if($this->validate("max_length", array(self::FILTER_NUMERIC), $queryVariables[self::PARAM_MAX_LENGTH])->validate("max_length", array(self::FILTER_MAX . ":" . $apiMaxWordLength), $queryVariables[self::PARAM_MAX_LENGTH])->isFailed()) {
            throw new Exception("Bad Request: Max length malformed. Expected integer with max value 10", HttpCodes::HTTP_BAD_REQUEST);
        }
        if($this->validate("words", array(self::FILTER_NUMERIC), $queryVariables[self::PARAM_WORDS_NUM])->validate("words_num", array(self::FILTER_MIN . ":" . $apiMinWordsCount), $queryVariables[self::PARAM_WORDS_NUM])->isFailed()) {
            throw new Exception("Bad Request: Number fo words malformed. Expected integer with min value 1 and min value 1", HttpCodes::HTTP_BAD_REQUEST);
        }
        if($this->validate("words_num", array(self::FILTER_MAX . ":" . $apiMaxWordsCount), $queryVariables[self::PARAM_WORDS_NUM])->isFailed()) {
            throw new Exception("Bad Request: Number fo words malformed. Expected integer with min value 1 and max value 10", HttpCodes::HTTP_BAD_REQUEST);
        }

        if((int)$queryVariables[self::PARAM_MAX_LENGTH] < (int)$queryVariables[self::PARAM_MIN_LENGTH]) {
            throw new Exception("Bad Request: Min length can't be grater then Max length", HttpCodes::HTTP_BAD_REQUEST);
        }
    }

    public function validateVersion(string $requestPath) {
        $activeVersion = Config::get(Config::API_VERSION);
        if(!strpos($requestPath, $activeVersion)) {
            throw new Exception("Version not valid. Please use latest api version " . $activeVersion, HttpCodes::HTTP_FORBIDDEN);
        }
    }
}