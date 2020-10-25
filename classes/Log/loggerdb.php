<?php


class LoggerDb extends Logger implements LoggerInterface
{
    public function getLoggerSettings(string $type): array
    {
        return array(
            self::LOGGER_WEB => array("model" => Factory::getModel(Factory::MODEL_LOGGER_WEB)),
            self::LOGGER_API => array("model" => Factory::getModel(Factory::MODEL_LOGGER_API)),
            self::LOGGER_DEFAULT => array("model" => Factory::getModel(Factory::MODEL_LOGGER)),
        )[$type];
    }

    public function logException(Exception $e): void
    {
        $settings = $this->getLoggerSettings($this->type);
        if(empty($settings)) {
            return;
        }
        $model = $settings["model"];
        $model->insert(array(
            "type" => "exception",
            'message' => $e->getMessage(),
            'code' => ($e->getCode())? $e->getCode() : null,
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $this->formatExceptionTrace($e)
        ));
    }
}