<?php


class AccountVerificationMail extends Mail implements InterfaceMail
{
    protected $resource = "confirmEmail";
    protected $cmsHolders = array("account_verification");

    public function getBodyContent() : string {
        $fullPath = $this->mailContentsPath . "/" . $this->resource . ".html";
        if(!file_exists($fullPath)) {
            throw new Exception("Resource not found", HttpCodes::INTERNAL_SERVER_ERROR);
        }

        $content = file_get_contents($fullPath);
        $pageData = $this->web->getPageData(Web::MAIL);
        if(empty($pageData)) {
            return $content;
        }
        $holders = array();
        $replacements = array();
        array_walk($pageData, function($value, $key) use(&$holders, &$replacements) {
            if(in_array($key, $this->cmsHolders)) {
                $holders[] = sprintf("{%s}", $key);
                $replacements[] = $value;
            }
        });

        return str_replace($holders, $replacements, $content);
    }

    public function addContentData(string $content) : string {
        $data = $this->resourceObject->getModel()->select(array("username"), array("email" => $this->resourceDataId));
        $token = $this->guardian->getToken(Guardian::TOKEN_TYPE_VERIFICATION);
        $url = HttpParser::baseUrl() . "email/verify.php?token=" . $token;
        return str_replace(array("{username}", "{url}"), array($data[0]["username"], $url), $content);
    }
}
