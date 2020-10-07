<?php


class WordsJson extends Words
{
    public function action(string $payload, string $action) {
        $words = json_decode(trim($payload), true);
        if(!isset($words["words"])) {
            throw new Exception($this->validator->getFormattedErrorMessagesForDisplay(array("Json is not properly formatted")));
        }
        $words = $words["words"];
        $this->validateWords($words, "json");
        $this->doAction($words, $action);
    }
}