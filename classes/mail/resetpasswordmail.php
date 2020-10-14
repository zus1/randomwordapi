<?php


class ResetPasswordMail extends Mail implements InterfaceMail
{
    protected $resource = "passwordResetEmail";
    protected $cmsHolders = array("password_reset");

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
        $user = $this->resourceObject->getModel()->select(array("username"), array("id" => $this->resourceDataId));
        $token = $this->userToken->getToken(UserToken::TOKEN_TYPE_PASSWORD_RESET, $this->resourceDataId);
        $url = sprintf("%sviews/auth/resetpassword.php?token=%s", HttpParser::baseUrl(), $token);

        return str_replace(array("{username}", "{url}"), array($user[0]["username"], $url), $content);
    }
}