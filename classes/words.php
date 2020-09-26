<?php


class Words
{
    private $validator;
    private $language;
    private $filters = array();
    private static $totalChange = 0;

    const ACTION_INSERT = "insert";
    const ACTION_REMOVE = "remove";

    public function __construct(Validator $validator) {
        $this->validator = $validator;
        $this->initFilters();
    }

    private function initFilters() {
        if(empty($this->filters)) {
            $tagsAndFilters = Factory::getObject(Factory::TYPE_DATABASE)->select("SELECT tag, filters FROM languages", array(), array());
            foreach($tagsAndFilters as $tagsAndFilter) {
                $this->filters[$tagsAndFilter["tag"]] = explode(",", $tagsAndFilter["filters"]);
            }
        }
    }

    public function setLanguage(string $language) {
        $this->language = $language;
        return $this;
    }

    public function getTotalChanged() {
        return self::$totalChange;
    }

    public function addLanguage(string $tag, string $name, array $filters) {
        $tag = strtolower($tag);
        if($this->validator->validate("tag", array(Validator::FILTER_ALPHA), $tag)->validate("name", array(Validator::FILTER_ALPHA), $name)->isFailed()) {
            throw new Exception($this->validator->getFormattedErrorMessagesForDisplay());
        }
        $availableFilters = $this->validator->getLanguageFilters();
        $notValid = array_diff($filters, $availableFilters);
        if(count($notValid) > 0) {
            throw new Exception("Invalid filters: " . implode(",", $notValid));
        }

        $filtersStr = implode(",", $filters);
        $exists = Factory::getObject(Factory::TYPE_DATABASE)->select("SELECT id FROM languages WHERE tag = ?", array("string"), array($tag));
        if($exists) {
            throw new Exception("Language already exists");
        }
        Factory::getObject(Factory::TYPE_DATABASE, true)->execute("INSERT INTO languages (tag, name, filters) VALUES (?,?,?)",
            array("string", "string", "string"), array($tag, $name, $filtersStr));
    }

    public function removeLanguage(string $tag) {
        if($this->validator->validate("tag", array(Validator::FILTER_ALPHA), $tag)->isFailed()) {
            throw new Exception($this->validator->getFormattedErrorMessagesForDisplay());
        }
        Factory::getObject(Factory::TYPE_DATABASE)->execute("DELETE FROM languages WHERE tag = ?", array("string"), array($tag));
        Factory::getObject(Factory::TYPE_DATABASE)->execute("DELETE FROM words WHERE tag = ?", array("string"), array($tag));
    }

    public function bulkAction(string $payload, string $action) {
        $words = preg_split("/\n|\r\n|,/", $payload);
        $this->validateWords($words, "bulk");
        $this->doAction($words, $action);
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
        $this->doAction($words, $action);
    }

    public function jsonAction(string $payload, string $action) {
        $words = json_decode(trim($payload), true);
        if(!isset($words["words"])) {
            throw new Exception($this->validator->getFormattedErrorMessagesForDisplay(array("Json is not properly formatted")));
        }
        $words = $words["words"];
        $this->validateWords($words, "json");
        $this->doAction($words, $action);
    }

    protected function doAction(array $words, string $action) {
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
            if($this->validator->validate($field, $this->filters[$this->language], $word)->isFailed()) {
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
                self::$totalChange += count($words);
                Factory::getObject(Factory::TYPE_DATABASE, true)->execute("INSERT INTO words (tag, length, words) VALUES (?,?,?)",
                    array("string", "integer", "string"), array($this->language, $len, $toInsert));
            } else {
                $existingDecoded = json_decode($existing[0]["words"], true);
                $diff = array_values(array_diff($words, $existingDecoded));
                self::$totalChange += count($diff);
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
                continue;
            }

            $existingDecoded = json_decode($existing[0]["words"], true);
            $diff = array_values(array_diff($existingDecoded, $words));
            $removed = count($existingDecoded) - count($diff);
            self::$totalChange += $removed;
            if(!empty($diff)) {
                if($removed === 0) {
                    continue;
                }
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