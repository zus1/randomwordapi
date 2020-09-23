<?php


class Validator
{
    const FILTER_ALPHA_NUM = "alpha_num";
    const FILTER_ALPHA_NUM_DASH = "alpha_num_dash";
    const FILTER_NUMERIC = 'number';
    const FILTER_ALPHA = "alpha";
    const FILTER_EMAIL = "email";
    const FILTER_PASSWORD = "password";
    const FILTER_URL = 'url';
    const FILTER_CUSTOM = "custom";

    private $validFilters = array(
        self::FILTER_ALPHA_NUM, self::FILTER_ALPHA, self::FILTER_ALPHA_NUM_DASH, self::FILTER_NUMERIC,
        self::FILTER_URL, self::FILTER_EMAIL, self::FILTER_PASSWORD, self::FILTER_CUSTOM
    );

    private $filterToMethodMapping = array(
        self::FILTER_ALPHA_NUM => "filterAlphaNumeric",
        self::FILTER_ALPHA_NUM_DASH => "filterAlphaNumericDash",
        self::FILTER_NUMERIC => "filterNumeric",
        self::FILTER_ALPHA => 'filterAlpha',
        self::FILTER_EMAIL => 'filterEmail',
        self::FILTER_PASSWORD => 'filterPassword',
        self::FILTER_URL => 'filterUrl'
    );

    private $errorMessages = array(
        self::FILTER_ALPHA_NUM => "Field {field} can contain only letters and numbers",
        self::FILTER_ALPHA_NUM_DASH => "Field {field} can contain only latter, numbers and dashes",
        self::FILTER_NUMERIC => "Field {field} can contain only number and +- signs",
        self::FILTER_ALPHA => "Field {field} can contain only letters",
        self::FILTER_EMAIL => "Field {field} must be valid email",
        self::FILTER_PASSWORD => "Field {field} can contain only valid password characters",
        self::FILTER_CUSTOM => "Field {field} contains invalid characters",
        self::FILTER_URL => "Field {field} must be valid url"
    );

    private $messages = array();

    private $request;
    private $htmlParser;

    public function __construct(Request $request, HtmlParser $htmlParser) {
        $this->request = $request;
        $this->htmlParser = $htmlParser;
    }

    private function getErrorMessage(string $field, string $filter) {
        return str_replace("{field}", $field, $this->errorMessages[$filter]);
    }

    public function validate(string $field, array $filters, $value=null, ?string $customPattern="") {
        if(!$value) {
            $value = $this->request->input($field);
        }
        foreach($filters as $filter) {
            if(!in_array($filter, $this->validFilters)) {
                throw new Exception("Validator filter invalid", HttpCodes::INTERNAL_SERVER_ERROR);
            }
            if($filter === self::FILTER_CUSTOM) {
                if($customPattern === "") {
                    throw new Exception("Validator custom pattern missing", HttpCodes::INTERNAL_SERVER_ERROR);
                }
                $filtered = $this->filter($value, $customPattern);
            } else {
                $filtered = call_user_func_array([$this, $this->filterToMethodMapping[$filter]], array($value));
            }
            if($filtered !== $value) {
                $this->messages[] = $this->getErrorMessage($field, $filter);
            } else {
                $this->messages[] = "ok";
            }
        }

        return $this;
    }

    public function getMessages() {
        return $this->messages;
    }

    public function getErrorMessages() {
        return array_filter($this->messages, function($value) {
            return $value !== "ok";
        });
    }

    public function getFormattedErrorMessagesForDisplay(?array $errorMessages=array()) {
        if(empty($errorMessages)) {
            $errorMessages = $this->getErrorMessages();
        }
        if(!empty($errorMessages)) {
            return $this->htmlParser->formatValidatorErrorMessages($errorMessages);
        }
        return "";
    }

    public function isFailed() {
        $errorMessages = $this->getErrorMessages();

        if(!empty($errorMessages)) {
            return true;
        }

        return false;
    }

    public function resetMessages() {
        $this->messages = array();
    }

    public function filterAlphaNumeric($value) {
        return $this->filter($value, "/[^A-Za-z0-9]/");
    }

    public function filterAlphaNumericDash($value) {
        return $this->filter($value, "/[^A-Za-z0-9]_ /");
    }

    public function filterNumeric($value) {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    public function filterAlpha($value) {
        return $this->filter($value, "/[^A-Za-z]/");
    }

    public function filterUrl($value) {
        return filter_var($value, FILTER_SANITIZE_URL);
    }

    public function filterEmail($value) {
        $sanitizedEmail =  filter_var($value, FILTER_SANITIZE_EMAIL);
        if($sanitizedEmail !== $value) {
            return "";
        }
        if(!filter_var($sanitizedEmail, FILTER_VALIDATE_EMAIL)) {
            return "";
        }
        return $sanitizedEmail;
    }

    public function filterPassword($value) {
        return $this->filter($value, "/[^A-Za-z0-9_@?.!*-+<>]/");
    }

    public function filter($value, string $pattern) {
        return preg_replace($pattern, "", $value);
    }
}