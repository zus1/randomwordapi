<?php


class Mail implements InterfaceMail
{
    const SMTP = "smtp";
    const TLS = "tls";
    const MAIL_CMS_PAGE = "Mail";

    protected $guardian;
    protected $web;

    protected $mailer;
    protected $resourceObject;
    protected $resourceDataId;
    protected $mailContentsPath;

    protected $resource = ""; //override in child class
    protected $cmsHolders = array(); //override in child classes

    public function __construct(Guardian $guardian, Web $web) {
        $this->mailContentsPath = HttpParser::root() . "/resources/mail";
        $this->guardian = $guardian;
        $this->web = $web;
        $this->init();
    }

    public function getBodyContent() : string {
        return ""; // needs to be overriden in child class
    }

    public function addContentData(string $content) : string {
        return ""; // needs to be overriden in child class
    }

    public function setResourceObject(Object $object) {
        $this->resourceObject = $object;
        return $this;
    }

    public function setResourceDataId($id) {
        $this->resourceDataId = $id;
        return $this;
    }

    private function init() {
        $server = Config::get(Config::EMAIL_SMTP_SERVER);
        $username = Config::get(Config::EMAIL_USERNAME);
        $password = Config::get(Config::EMAIL_PASSWORD);
        $encription = Config::get(Config::EMAIL_ENCRIPTION);
        $port = Config::get(Config::EMAIL_PORT);

        $phpMailer = Factory::getLibrary(Factory::LIBRARY_PHP_MAILER);
        if($encription === self::SMTP) {
            $phpMailer->isSMTP();
            $phpMailer->SMTPAuth = true;
        }
        $phpMailer->isHTML(true);
        $phpMailer->Host = $server;
        $phpMailer->Username = $username;
        $phpMailer->Password = $password;
        $phpMailer->Port = $port;
        if($encription === self::SMTP) {
            $phpMailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif($encription === self::TLS) {
            $phpMailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $this->mailer = $phpMailer;
    }

    public function setAddress(array $addresses) {
        foreach($addresses as $address) {
            if(isset($address["name"])) {
                $this->mailer->addAddress($address["address"], $address["name"]);
            } else {
                $this->mailer->addAddress($address["address"]);
            }
        }

        return $this;
    }

    public function setSender(array $sender) {
        if(isset($sender["name"])) {
            $this->mailer->setFrom($sender["sender"], $sender["name"]);
        } else {
            $this->mailer->setFrom($sender["sender"]);
        }

        return $this;
    }

    public function setCC(array $ccs) {
        foreach($ccs as $cc) {
            if(isset($cc["name"])) {
                $this->mailer->addCC($cc["cc"], $cc["name"]);
            } else {
                $this->mailer->addCC($cc["cc"]);
            }
        }

        return $this;
    }

    public function setBcc(array $bccs) {
        foreach($bccs as $bcc) {
            if(isset($bcc["name"])) {
                $this->mailer->addCC($bcc["cc"], $bcc["name"]);
            } else {
                $this->mailer->addCC($bcc["cc"]);
            }
        }

        return $this;
    }

    public function setSubject(string $subject) {
        $this->mailer->Subject = $subject;
        return $this;
    }

    public function setText(string $text) {
        $this->mailer->isHTML(false);
        $this->mailer->Body = $text;

        return $this;
    }

    public function setBody() {
        $body = $this->getMailBody();
        $this->mailer->Body = $body;
        $this->mailer->AltBody = strip_tags($body);

        return $this;
    }

    public function send() {
        $this->mailer->send();
    }

    private function getMailBody() {
        $content = $this->getBodyContent();
        return $this->addContentData($content);
    }

}