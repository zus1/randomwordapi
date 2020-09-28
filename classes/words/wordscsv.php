<?php


class WordsCsv extends Words
{
    public function action(array $payload, string $action) {
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
}