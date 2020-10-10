<?php


class AccountVerificationMail extends Mail implements MailInterface
{
    protected $resource = "confirmEmail";

    public function getBodyContent() : string {
        $fullPath = $this->mailContentsPath . "7" . $this->resource . ".html";
        if(!file_exists($fullPath)) {
            throw new Exception("Resource not found", HttpCodes::INTERNAL_SERVER_ERROR);
        }

        return file_get_contents($fullPath);
    }

    public function addContentData(string $content) : string {
        $data = $this->resourceObject->getModel()->select(array("username"), array("email" => $this->resourceDataId));
        $token = $this->guardian->getToken(Guardian::TOKEN_TYPE_VERIFICATION);
        $url = HttpParser::baseUrl() . "email/verify.php?token=" . $token;
        return str_replace(array("{username}", "{url}"), array($data[0]["username"], $url), $content);
    }
}
