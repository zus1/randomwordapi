<?php


class Words
{
    private $validator;
    private $language;

    public function __construct(Validator $validator) {
        $this->validator = $validator;
    }

    public function setLanguage(string $language) {
        $this->language = $language;
    }

    public function bulkInsert(string $payload) {
        $words = preg_split("/\r|\n|\r\n|,/", $payload);
        $this->validateWords($words, "bulk");
        $this->insert($words);
    }

    public function csvInsert(array $payload) {
        if($payload["error"] !== UPLOAD_ERR_OK ) {
            throw new Exception($this->validator->getFormattedErrorMessagesForDisplay(array("Error uploading file")));
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($payload["tmp_name"]);
        if($mimeType !== "text/csv") {
            throw new Exception($this->validator->getFormattedErrorMessagesForDisplay(array("File must me valid csv")));
        }
        $contents = file_get_contents($payload["tmp_name"]);
        if(!$contents) {
            throw new Exception($this->validator->getFormattedErrorMessagesForDisplay(array("Error uploading file")));
        }
        $words = explode(",", $contents);
        $this->validateWords($words, "csv");
        $this->insert($words);
    }

    public function jsonInsert(string $payload) {
        $words = json_decode($payload, true);
        if(!isset($words["words"])) {
            throw new Exception($this->validator->getFormattedErrorMessagesForDisplay(array("Json is not properly formatted")));
        }
        $words = $words["words"];
        $this->validateWords($words, "json");
        $this->insert($words);
    }

    private function validateWords(array $words, string $field) {
        foreach($words as $word) {
            if($this->validator->validate($field, array(Validator::FILTER_ALPHA), $word)->isFailed()) {
                throw new Exception($this->validator->getFormattedErrorMessagesForDisplay());
            }
        }
    }

    private function insert(array $words) {
        $wordsByLength = $this->sortWordsByLength($words);
        foreach($wordsByLength as $len => $words) {
            $existing = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT words FROM words WHERE tag = ? AND length = ?",
                array("string", "string"), array($this->language, $len));
            if(!$existing) {
                $toInsert = json_encode($words);
                Factory::getObject(Factory::TYPE_DATABASE, true)->execute("INSERT INTO words (tag, length, words) VALUES (?,?,?)",
                    array("string", "integer", "string"), array($this->language, $len, json_encode($toInsert)));
            } else {
                $existingDecoded = json_decode($existing[0]["words"], true);
                $diff = array_diff($words, $existingDecoded);
                if(!empty($diff)) {
                    $toInsert = json_encode(array_merge($existingDecoded, $diff));
                    Factory::getObject(Factory::TYPE_DATABASE, true)->execute("UPDATE words SET words = ? WHERE tag = ? AND length = ?",
                        array("string", "string", "integer"), array($toInsert, $this->language, $len));
                }
            }
        }
    }

    private function sortWordsByLength(array $words) {
        $wordsByLength = array();
        array_walk($words, function($value) use(&$wordsByLength) {
            $wordLen = strlen($value);
            if(array_key_exists($wordLen, $wordsByLength)) {
                $wordsByLength[$wordLen][] = $value;
            } else {
                $wordsByLength[$wordLen] = array($value);
           }
        });

        return $wordsByLength;
    }
}