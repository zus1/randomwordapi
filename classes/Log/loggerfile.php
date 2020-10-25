<?php


class LoggerFile extends Logger implements LoggerInterface
{
    private $rootDirectory;

    public function __construct()
    {
        $this->rootDirectory = HttpParser::root() . "/logs/";
    }

    public function getLoggerSettings(string $type): array
    {
        return array(
            self::LOGGER_WEB => array("file" => $this->rootDirectory . "web.log"),
            self::LOGGER_API => array("file" => $this->rootDirectory . "api.log"),
            self::LOGGER_DEFAULT => array("file" => $this->rootDirectory . "log.log"),
        )[$type];
    }

    public function logException(Exception $e): void
    {
        if(!file_exists($this->rootDirectory)) {
            mkdir($this->rootDirectory, 0777);
            $owner = posix_getpwuid(fileowner($this->rootDirectory))["name"];
            $iAm = shell_exec("whoami");
            if($owner !== "www-data" && $iAm === "root") {
                chown($this->rootDirectory, "www-data");
            }
        }

        $setting = $this->getLoggerSettings($this->type);
        if(!$setting) {
            return;
        }
        $fh = fopen($setting["file"], "a+");
        if(!$fh) {
            throw new Exception("Could not open log file");
        }
        $line = $this->createLogExceptionLine($e);
        fwrite($fh, $line);
        fclose($fh);
    }

    private function createLogExceptionLine(Exception $e) {
        return sprintf("[%s]%s(%d)\n%s(%s)\n%s\n\n", date("Y-m-d H:i:s"), $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $this->formatExceptionTrace($e));
    }
}