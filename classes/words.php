<?php


class Words
{
    private $validator;
    private $language;

    const ACTION_INSERT = "insert";
    const ACTION_REMOVE = "remove";

    public function __construct(Validator $validator) {
        $this->validator = $validator;
    }

    public function setLanguage(string $language) {
        $this->language = $language;
        return $this;
    }

    public function bulkAction(string $payload, string $action) {
        $words = preg_split("/\n|\r\n|,/", $payload);
        $this->validateWords($words, "bulk");
        $this->call($words, $action);
    }

    public function csvAction(array $payload, string $action) {
        $allowedMimeTypes = array(
            'text/csv', "text/plain", "text/x-csv", "application/csv", "application/x-csv"
        );
        if($payload["error"] !== UPLOAD_ERR_OK ) {
            throw new Exception($this->validator->getFormattedErrorMessagesForDisplay(array("Error uploading file")));
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($payload["tmp_name"]);
        if(!in_array($mimeType, $allowedMimeTypes)) {
            throw new Exception($this->validator->getFormattedErrorMessagesForDisplay(array("File must me valid csv")));
        }
        $contents = trim(file_get_contents($payload["tmp_name"]));
        if(!$contents) {
            throw new Exception($this->validator->getFormattedErrorMessagesForDisplay(array("Error uploading file")));
        }
        $words = explode(",", $contents);
        $this->validateWords($words, "csv");
        $this->call($words, $action);
    }

    public function jsonAction(string $payload, string $action) {
        $words = json_decode(trim($payload), true);
        if(!isset($words["words"])) {
            throw new Exception($this->validator->getFormattedErrorMessagesForDisplay(array("Json is not properly formatted")));
        }
        $words = $words["words"];
        $this->validateWords($words, "json");
        $this->call($words, $action);
    }

    protected function call(array $words, string $action) {
        if($action === self::ACTION_INSERT) {
            $this->insert($words);
        } elseif($action === self::ACTION_REMOVE) {
            $this->remove($words);
        } else {
            throw new Exception("Invalid action", HttpCodes::INTERNAL_SERVER_ERROR);
        }
    }

    private function validateWords(array $words, string $field) {
        foreach($words as $word) {
            if($this->validator->validate($field, array(Validator::FILTER_ALPHA), $word)->isFailed()) {
                throw new Exception($this->validator->getFormattedErrorMessagesForDisplay());
            }
        }
    }

    public function insert(array $words) {
        $wordsByLength = $this->sortWordsByLength($words);
        foreach($wordsByLength as $len => $words) {
            $existing = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT words FROM words WHERE tag = ? AND length = ?",
                array("string", "string"), array($this->language, $len));
            if(!$existing) {
                $toInsert = json_encode($words);
                Factory::getObject(Factory::TYPE_DATABASE, true)->execute("INSERT INTO words (tag, length, words) VALUES (?,?,?)",
                    array("string", "integer", "string"), array($this->language, $len, $toInsert));
            } else {
                $existingDecoded = json_decode($existing[0]["words"], true);
                $diff = array_values(array_diff($words, $existingDecoded));
                if(!empty($diff)) {
                    $toInsert = json_encode(array_merge($existingDecoded, $diff));
                    Factory::getObject(Factory::TYPE_DATABASE, true)->execute("UPDATE words SET words = ? WHERE tag = ? AND length = ?",
                        array("string", "string", "integer"), array($toInsert, $this->language, $len));
                }
            }
        }
    }

    public function remove(array $words) {
        $wordsByLength = $this->sortWordsByLength($words);
        foreach($wordsByLength as $len => $words) {
            $existing = Factory::getObject(Factory::TYPE_DATABASE, true)->select("SELECT words FROM words WHERE tag = ? AND length = ?",
                array("string", "string"), array($this->language, $len));
            if(!$existing) {
                throw new Exception("Could not find words", HttpCodes::INTERNAL_SERVER_ERROR);
            }

            $existingDecoded = json_decode($existing[0]["words"], true);
            $diff = array_values(array_diff($existingDecoded, $words));
            if(!empty($diff)) {
                $toUpdate = json_encode($diff);
                Factory::getObject(Factory::TYPE_DATABASE, true)->execute("UPDATE words SET words = ? WHERE tag = ? AND length = ?",
                    array("string", "string", "integer"), array($toUpdate, $this->language, $len));
            } else {
                Factory::getObject(Factory::TYPE_DATABASE, true)->execute("DELETE FROM words WHERE tag = ? AND length = ?",
                    array("string", "integer"), array($this->language, $len));
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