<?php


class Mail implements MailInterface
{
    const SMTP = "smtp";
    const TLS = "tls";

    protected $guardian;

    protected $mailer;
    protected $resourceObject;
    protected $resourceDataId;
    protected $mailContentsPath;
    protected $resource = ""; //override in child class

    public function __construct(Guardian $guardian) {
        $this->mailContentsPath = HttpParser::root() . "/resources/mail";
        $this->guardian = $guardian;
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
    }

    public function setResourceDataId($id) {
        $this->resourceDataId = $id;
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
    }

    public function setSender(array $sender) {
        if(isset($sender["name"])) {
            $this->mailer->setFrom($sender["sender"], $sender["name"]);
        } else {
            $this->mailer->setFrom($sender["sender"]);
        }
    }

    public function setCC(array $ccs) {
        foreach($ccs as $cc) {
            if(isset($cc["name"])) {
                $this->mailer->addCC($cc["cc"], $cc["name"]);
            } else {
                $this->mailer->addCC($cc["cc"]);
            }
        }
    }

    public function setBcc(array $bccs) {
        foreach($bccs as $bcc) {
            if(isset($bcc["name"])) {
                $this->mailer->addCC($bcc["cc"], $bcc["name"]);
            } else {
                $this->mailer->addCC($bcc["cc"]);
            }
        }
    }

    public function setSubject(string $subject) {
        $this->mailer->Subject = $subject;
    }

    public function setText(string $text) {
        $this->mailer->isHTML(false);
        $this->mailer->Body = $text;
    }

    public function setBody() {
        $body = $this->getMailBody();
        $this->mailer->Body = $body;
        $this->mailer->AltBody = strip_tags($body);
    }

    public function send() {
        $this->mailer->send();
    }

    private function getMailBody() {
        $content = $this->getBodyContent();
        return $this->addContentData($content);
    }

}