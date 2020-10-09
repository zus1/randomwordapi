<?php


class ApiApp
{
    private $apiValidator;
    private $apiGuardian;
    private $apiUser;
    private $request;
    private $dateHandler;

    public function __construct(ApiValidator $validator, ApiGuardian $guardian, ApiUser $user, Request $request, DateHandler $dateHandler) {
        $this->apiValidator = $validator;
        $this->apiGuardian = $guardian;
        $this->apiUser = $user;
        $this->request = $request;
        $this->dateHandler = $dateHandler;
    }

    private $dataSet = array(
        "id", "name", "user_id", "access_token", "first_request", "rate_limit", "requests_spent",
        "requests_remaining", "time_left_until_reset", "deactivated", "soft_banned", "hard_banned",
        "limit_reached", "last_request", "soft_banned_at", "token_regenerated_at"
    );

    public function getDbTpResponseHeaderMapping() {
        return array(
            'rate_limit' => "X-Rate-Limit",
            "requests_spent" => "X-Requests-Spent",
            "requests_remaining" => "X-Requests-Remaining",
            "time_left_until_reset" => "X-Resets-In"
        );
    }

    /**
     * Performs checks for rate limit, soft and hard ban and if soft ban is expired
     */
    public function checkApp() {
        $authHeader = Config::get(Config::API_AUTHORIZATION_HEADER);
        $softBanInterval = (int)Config::get(Config::API_SOFT_BAN_INTERVAL);
        $rateLimitInterval = (int)Config::get(Config::API_RATE_LIMIT_TIME_RANGE);
        $rateLimit = (int)Config::get(Config::API_DEFAULT_RATE_LIMIT);
        $accessToken = $this->request->getHeader($authHeader);
        $fields = array(
            "id", "user_id", "first_request", "last_request", "rate_limit", "requests_spent", "requests_remaining", "limit_reached",
            "time_left_until_reset", "deactivated", "soft_banned", "hard_banned", "soft_banned_at"
        );

        $app = $this->getApiApp($fields, array("access_token" => $accessToken));
        if(!$app) {
            throw new Exception("Unauthorized", HttpCodes::UNAUTHORIZED);
        }
        $app = $app[0];

        $this->apiGuardian->checkHardBan($app);
        $this->apiGuardian->checkSoftBan($app, $softBanInterval);
        $this->checkAppDeactivated($app);
        $this->handleFirstRequest($app, $rateLimitInterval);
        $newInterval = $this->dateHandler->checkGreaterThenInterval($app["last_request"], date("Y-m-d H:i:s"), 0);
        $this->handleRateLimit($app, $accessToken, $newInterval);
        $limitReached = false;
        $this->handleNewInterval($app, $newInterval, $rateLimit, $rateLimitInterval);
        $this->handleRequest($app, $limitReached);
        $this->updateApiApp($app, array("access_token" => $accessToken));

        if($limitReached === true) {
            throw new Exception("Rate limit reached", HttpCodes::TO_MANY_REQUESTS);
        }
    }

    private function checkAppDeactivated(array &$app) {
        if((int)$app["deactivated"] === 1) {
            throw new Exception("App is not active", HttpCodes::HTTP_BAD_REQUEST);
        }
    }

    private function handleFirstRequest(array &$app, int $rateLimitInterval) {
        if(empty($app["first_request"])) {
            $app["first_request"] = date("Y-m-d H:i:s");
            $app["last_request"] = date("Y-m-d H:i:s", $this->dateHandler->convertDateToTimestamp(date("Y-m-d H:i:s")) + $rateLimitInterval *60);
        }
    }

    private function handleRateLimit(array $app, string $accessToken, bool $newInterval) {
        if((int)$app["limit_reached"] === 1) {
            if($newInterval === false) {
                $remaining = $this->dateHandler->calculateDateInterval(date("Y-m-d H:i:s"), $app["last_request"]);
                $app["time_left_until_reset"] = $remaining;
                $this->updateApiApp($app, array("access_token" => $accessToken));
                throw new Exception("Rate limit reached", HttpCodes::TO_MANY_REQUESTS);
            }
        }
    }

    private function handleNewInterval(array &$app, bool $newInterval, int $rateLimit, int $rateLimitInterval) {
        if($newInterval === true) {
            $app["time_left_until_reset"] = 0;
            $app["limit_reached"] = 0;
            $app["requests_spent"] = 0;
            $app["requests_remaining"] = $rateLimit;
            $app["rate_limit"] = $rateLimit;
            $app["first_request"] = date("Y-m-d H:i:s");
            $app["last_request"] = date("Y-m-d H:i:s", $this->dateHandler->convertDateToTimestamp($app["first_request"]) + $rateLimitInterval *60);
        }
    }

    private function handleRequest(&$app, bool &$limitReached) {
        $app["requests_spent"] = (int)$app["requests_spent"] + 1;
        $app["requests_remaining"] = (int)($app["requests_remaining"]) - 1;
        if((int)$app["requests_spent"] === (int)$app["rate_limit"]) {
            $app["limit_reached"] = 1;
            $app["time_left_until_reset"] = $this->dateHandler->calculateDateInterval(date("Y-m-d H:i:s"), $app["last_request"]);
            $limitReached = true;
        }
    }

    public function addResponseHeaders() {
        header("Content-type: application/json");
        $dbToHeaderMapping = $this->getDbTpResponseHeaderMapping();
        $authHeader = Config::get(Config::API_AUTHORIZATION_HEADER);
        $accessToken = $this->request->getHeader($authHeader);
        $responseHeadersData = $this->getDataForResponseHeaders($accessToken);
        foreach($responseHeadersData as $dbKey => $value) {
            header(sprintf("%s: %s", $dbToHeaderMapping[$dbKey], $value));
        }
    }

    public function getDataForResponseHeaders(string $accessToken) {
        $fields = array("rate_limit", "requests_spent", "requests_remaining", "time_left_until_reset");
        $data = $this->getApiApp($fields, array("access_token" => $accessToken));
        if(!$data) {
            return array();
        }

        return $data[0];
    }

    public function regenerateAccessToken(int $appId, string $oldToken) {
        $app = $this->getApiApp(array("access_token"), array("id" => $appId));
        if(!$app) {
            throw new Exception("App with this access token do not exists");
        }

        if($oldToken !== $app[0]["access_token"]) {
            throw new Exception("Token mismatch");
        }

        $newToken = $this->apiGuardian->createAccessToken();

        if($this->updateApiApp(array("access_token" => $newToken, "token_regenerated_at" => date("Y-m-d H:i:s")), array("id" => $appId))) {
            return $newToken;
        }

        return $oldToken;
    }

    public function deleteApp(int $appId) {
        $userId = $this->apiUser->getAuthenticatedUserId();
        $appToDelete = $this->getApiApp(array("id"), array("id" => $appId, "user_id" => $userId));
        if(!$appToDelete) {
            throw new Exception("App not found");
        }

        Factory::getObject(Factory::TYPE_DATABASE, true)->execute("DELETE FROM api_app WHERE id = ?", array("integer"), array($appId));
    }

    public function addApiApp(string $appName) {
        $existing = $this->getApiApp(array("id"), array("name" => $appName));
        if($existing) {
            throw new Exception("App with this name already exists");
        }

        $userId = $this->apiUser->getAuthenticatedUserId();
        $this->canIAdd($userId);

        $accessToken = $this->apiGuardian->createAccessToken();
        $rateLimit = Config::get(Config::API_DEFAULT_RATE_LIMIT);
        $createdAt = date("Y-m-d H:i:s");

        Factory::getObject(Factory::TYPE_DATABASE, true)->execute("INSERT INTO api_app (name, user_id, access_token, rate_limit, created_at, requests_remaining) VALUES (?,?,?,?,?,?)",
            array("string", "integer", "string", "integer", "string", "integer"), array($appName, $userId, $accessToken, $rateLimit, $createdAt, $rateLimit));
    }

    private function canIAdd(int $userId) {
        $maxApps = (int)Config::get(Config::API_MAX_NUM_APPS);
        $usersApps = $this->getApiApp(array("id"), array("user_id" => $userId));

        if(count($usersApps) >= $maxApps) {
            throw new Exception("Max allowed number of apps reached. Number is " . $maxApps);
        }
    }

    public function updateApiApp(array $fields, ?array $where=array()) {
        $this->validateDataSets(array_keys($fields), array_keys($where));

        $query = "UPDATE api_app SET ";
        $values = array_values($fields);
        $fieldsKeys = array_keys($fields);
        array_walk($fieldsKeys, function($key) use (&$query) {
            $key = $this->apiValidator->filterAlphaNumUnderscore($key);
            $query .= sprintf("%s=?,", $key);
        });
        $query = substr($query, 0, strlen($query) - 1);

        if(!empty($where)) {
            $query .= " WHERE";
            $whereKeys = array_keys($where);
            $values = array_merge($values, array_values($where));
            array_walk($whereKeys, function($key) use(&$query) {
                $key = $this->apiValidator->filterAlphaNumUnderscore($key);
                $query .= sprintf(" %s=? AND", $key);
            });
            $query = substr($query, 0, strlen($query) - strlen(" AND"));
        }

        return Factory::getObject(Factory::TYPE_DATABASE, true)->execute($query, array(), $values);
    }

    public function getApiApp(?array $fields=array(), ?array $where=array()) {
        $this->validateDataSets($fields, array_keys($where));

        if(empty($fields)) {
            $fields = $this->dataSet;
        }

        $query = "SELECT ";
        $fields = array_map(function($value) {
            return $this->apiValidator->filterAlphaDash($value);
        }, $fields);
        $fieldsStr = implode(",", $fields);
        $query .= $fieldsStr . " FROM api_app";

        $values = array();
        if(!empty($where)) {
            $query .= " WHERE";
            $keys = array_keys($where);
            $values = array_values($where);
            $keys = array_map(function($value) {
                return $this->apiValidator->filterAlphaDash($value);
            }, $keys);
            array_walk($keys, function ($key) use(&$query) {
                $query .= sprintf(" %s=? AND", $key);
            });
            $query = substr($query, 0, strlen($query) - strlen(" AND"));
        }

        return Factory::getObject(Factory::TYPE_DATABASE, true)->select($query, array(), $values);
    }

    private function validateDataSets(array $fields, array $where) {
        $invalidFields = array_diff($fields, $this->dataSet);
        $invalidWhereKeys = array_diff($where, $this->dataSet);
        if(!empty($invalidFields)) {
            throw new Exception("Invalid fields", HttpCodes::INTERNAL_SERVER_ERROR);
        }
        if(!empty($invalidWhereKeys)) {
            throw new Exception("Invalid where", HttpCodes::INTERNAL_SERVER_ERROR);
        }
    }
}