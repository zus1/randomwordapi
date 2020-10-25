<?php


class ExceptionHandler
{
    const EXCEPTION_DEFAULT = "default";

    private $extender;
    private $logger;

    public function __construct(ExceptionHandlerExtender $extender, LoggerInterface $logger) {
        $this->extender = $extender;
        $this->logger = $logger;
    }

    private function getTypeTOHandlerMapping() {
        $defaultMapping = array(
            self::EXCEPTION_DEFAULT => "handleException",
        );
        $extended = $this->extender->extend();

        return array_merge($defaultMapping, $extended);
    }

    public function handle(Exception $e, ?string $type="", $return=false) {
        if($type === "") {
            $type = self::EXCEPTION_DEFAULT;
        }
        if(!array_key_exists($type, $this->getTypeTOHandlerMapping())) {
            $this->logger->logException($e);
            throw $e;
        }

        $method = $this->getTypeTOHandlerMapping()[$type];
        if(!method_exists($this, $method)) {
            $ret = call_user_func_array([$this->extender, $method], array($e));
        } else {
            $ret = call_user_func_array([$this, $method], array($e));
        }

        if($return === true) {
            return $ret;
        }
        return null;
    }

    private function handleException(Exception $e) {
        $this->logger->logException($e);
        Factory::getObject(Factory::TYPE_ROUTER)->redirect(HttpParser::baseUrl() . "views/error.php?error=" . $e->getMessage() . "&code=" . $e->getCode(), $e->getCode());
    }
}