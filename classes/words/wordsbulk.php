<?php


class WordsBulk extends Words
{
    public function action(string $payload, string $action) {
        $words = preg_split("/\n|\r\n|,/", $payload);
        $this->validateWords($words, "bulk");
        $this->doAction($words, $action);
    }
}