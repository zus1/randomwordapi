<?php


class UserToken
{
    const TOKEN_TYPE_ACCOUNT_VERIFICATION = "account-verification";
    const TOKEN_TYPE_PASSWORD_RESET = "password-reset";

    private $guardian;
    private $dateHandler;

    public function __construct(Guardian $guardian, DateHandler $handler) {
        $this->guardian = $guardian;
        $this->dateHandler = $handler;
    }

    public function getModel() {
        return Factory::getModel(Factory::MODEL_USER_TOKEN);
    }

    private function getTokenSettings() {
        return array(
            self::TOKEN_TYPE_ACCOUNT_VERIFICATION => array(
                "duration" => Config::get(Config::VERIFICATION_TOKEN_EXPIRES), "db_field" => "verification_token", "guardian_type" => Guardian::TOKEN_TYPE_VERIFICATION),
            self::TOKEN_TYPE_PASSWORD_RESET => array(
                "duration" => Config::get(Config::PASSWORD_RESET_TOKEN_EXPIRES), "db_field" => "password_reset_token", "guardian_type" => Guardian::TOKEN_TYPE_PASSWORD_RESET),
        );
    }

    public function addToken(string $type, int $userId) {
        if(!array_key_exists($type, $this->getTokenSettings())) {
            throw new Exception("Could not generate token");
        }
        $settings = $this->getTokenSettings()[$type];
        $created = date("Y-m-d H:i:s");
        $expires = $this->calculateTokenExpires($created, $settings["duration"]);
        $durationDbFields = $this->makeTokenDurationFields($settings["db_field"]);
        $verificationToken = $this->guardian->getToken($settings["guardian_type"]);

        $existing = $this->getModel()->select(array("id"), array("user_id" => $userId));
        if(!$existing) {
            $this->getModel()->insert(array(
                "user_id" => $userId,
                $settings["db_field"] => $verificationToken,
                $durationDbFields["created"] => $created,
                $durationDbFields["expires"] => $expires
            ));
        } else {
            $this->getModel()->update(array(
                $settings["db_field"] => $verificationToken,
                $durationDbFields["created"] => $created,
                $durationDbFields["expires"] => $expires
            ), array(
                "user_id" => $userId
            ));
        }
    }

    public function getToken(string $type, int $userId) {
        if(!array_key_exists($type, $this->getTokenSettings())) {
            throw new Exception("Could not get token");
        }
        $settings = $this->getTokenSettings()[$type];
        $durationDbFields = $this->makeTokenDurationFields($settings["db_field"]);

        $tokenData = $this->getModel()->select(array($settings["db_field"], $durationDbFields["expires"]), array("user_id" => $userId));
        if(!$tokenData) {
            throw new Exception("Could not get token");
        }

        return $tokenData[0][$settings["db_field"]];
    }

    public function checkToken(string $inputToken, string $type, int &$userId) {
        if(!array_key_exists($type, $this->getTokenSettings())) {
            throw new Exception("Invalid token type");
        }
        $settings = $this->getTokenSettings()[$type];
        $durationDbFields = $this->makeTokenDurationFields($settings["db_field"]);

        $tokenData = $this->getModel()->select(array("user_id", $durationDbFields["expires"], $settings["db_field"]), array($settings["db_field"] => $inputToken));
        if(!$tokenData) {
            throw new Exception("Token invalid");
        }
        $tokenData = $tokenData[0];
        $userId = $tokenData["user_id"];
        if($this->dateHandler->checkGreaterThenInterval($tokenData[$durationDbFields["expires"]], date("Y-m-d H:i:s"), 0)) {
            throw new Exception("Token expired");
        }
    }

    private function calculateTokenExpires(string $created, int $duration) {
        $createdTs = $this->dateHandler->convertDateToTimestamp($created);
        return date("Y-m-d H:i:s", $createdTs + $duration *60);
    }

    private function makeTokenDurationFields(string $tokenField) {
        return array(
            "created" => sprintf("%s_created", $tokenField),
            "expires" => sprintf("%s_expires", $tokenField),
        );
    }
}