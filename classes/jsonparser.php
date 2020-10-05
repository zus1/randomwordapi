<?php

class JsonParser
{
    private $error = false;
    private $errorMessages = array();
    private $lastError;

    const ERROR_FILE_NOT_FOUND = "File not found";
    const ERROR_FAILED_PUT_CONTENTS = "Failed to put contents";

    public function isError() {
        return $this->error;
    }

    public function getErrorMessages() {
        return $this->errorMessages;
    }

    public function getLastErrorMessage() {
        return $this->lastError;
    }

    public function parseFromFile(string $fullPath, ?bool $decode=true) {
        if(!file_exists($fullPath)) {
            $this->addError(self::ERROR_FILE_NOT_FOUND);
            $contents = "[]";
        } else {
            $contents = file_get_contents($fullPath);
            if(empty($contents)) {
                $contents = "[]";
            }
        }

        if($decode === true) {
            $contents = json_decode($contents, true);
            if(json_last_error() !== JSON_ERROR_NONE) {
                $this->addError(json_last_error_msg());
                $contents = array();
            }
        }

        return $contents;
    }

    public function putToFile(string $fullPath, array $contents, bool $new=false) {
        if(!file_exists($fullPath) && $new === false) {
            $this->addError(self::ERROR_FILE_NOT_FOUND);
            return;
        }

        $encoded = json_encode($contents, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        if(json_last_error() !== JSON_ERROR_NONE) {
            $this->addError(json_last_error_msg());
            return;
        }

        if(!file_put_contents($fullPath, $encoded)) {
            $this->addError(self::ERROR_FAILED_PUT_CONTENTS);
        }
    }

    private function addError(string $errorMessage) {
        $this->errorMessages[] = $errorMessage;
        $this->lastError = $errorMessage;
        $this->error = true;
    }

    public function resetErrors() {
        $this->errorMessages = array();
        $this->lastError = "";
        $this->error = false;
    }
}