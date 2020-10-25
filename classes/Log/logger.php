<?php


class Logger implements LoggerInterface
{
    const LOGGER_API = "api";
    const LOGGER_WEB = "web";
    const LOGGER_DEFAULT = "log";

    protected $type = "log";
    protected $availableTypes = array(self::LOGGER_API, self::LOGGER_WEB);

    public function setType(string $type) {
        if(!in_array($type, $this->availableTypes)) {
            throw new Exception("Logger not supported");
        }
        $this->type = $type;

        return $this;
    }

    public function getLoggerSettings(string $type) : array {
        throw new Exception("If you are here, something is wrong", HttpCodes::INTERNAL_SERVER_ERROR); //needs to be overriden in child class
    }

    public function logException(Exception $e) : void {
        throw new Exception("If you are here, something is wrong", HttpCodes::INTERNAL_SERVER_ERROR); //needs to be overriden in child class
    }

    protected function formatExceptionTrace(Exception $e) {
        $trace = $e->getTraceAsString();
        $trace = explode("#", $trace);
        array_shift($trace);
        $trace = array_map(function($value) {
            return "#" . $value;
        }, $trace);

        return implode("\n", $trace);
    }
}