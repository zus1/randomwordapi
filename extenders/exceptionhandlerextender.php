<?php

class ExceptionHandlerExtender
{
    private $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function extend() {
        return array(
            "web" => "handleWebException",
            "api" => "handleApiException"
        );
    }

    public function handleWebException(Exception $e) {
        $this->logger->setType(Logger::LOGGER_WEB);
        $this->logger->logException($e);
        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/error.php?error=" . $e->getMessage() . "&code=" . $e->getCode(), $e->getCode());
    }

    public function handleApiException(Exception $e) {
        $this->logger->setType(Logger::LOGGER_API);
        $this->logger->logException($e);
        echo Factory::getObject(Factory::TYPE_API_EXCEPTION)->getApiException($e);
        die();
    }
}