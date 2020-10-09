<?php


class IpChecker
{
    private $apiApp;
    private $request;
    private $ip;
    private $apiValidator;
    private $dateHandler;
    private $apiGuardian;

    public function __construct(ApiApp $app, Request $request, ApiValidator $validator, DateHandler $dateHandler, ApiGuardian $guardian) {
        $this->apiApp = $app;
        $this->request = $request;
        $this->apiValidator = $validator;
        $this->dateHandler = $dateHandler;
        $this->apiGuardian = $guardian;
    }

    private function getModel() {
        return Factory::getModel(Factory::MODEL_IP_CHECKER);
    }

    private function getRules() {
        return array(
            ["method" => "ruleNumberOfRequestsInPeriod", "active" => Config::get(Config::RULE_CHECK_NUM_REQUESTS_IN_PERIOD_ACTIVE)],
        );
    }

    private function getIgnoredException() {
        return array();
    }

    public function checkIp() {
        $this->ip = $this->request->getRequestIp();
        $currentPeriodData = $this->currentDataFetch();
        try {
            foreach($this->getRules() as $rule) {
                if((int)$rule["active"] === 1) {
                    call_user_func_array([$this, $rule["method"]], array($currentPeriodData));
                }
            }
        } catch(Exception $e) {
            if(!in_array($e->getMessage(), $this->getIgnoredException())) {
                throw $e;
            }
        }
    }

    private function currentDataFetch() {
        $periodLimit = Config::get(Config::RULE_NUM_REQUESTS);
        $currentIpData = $this->getModel()->select([], array('ip_address' => $this->ip));
        if(empty($currentIpData)) {
            $this->getModel()->insert(array("ip_address" => $this->ip, "period_limit" => $periodLimit));
            $currentIpData = $this->getModel()->select([], array('ip_address' => $this->ip));
        }

        return $currentIpData[0];
    }

    private function ruleNumberOfRequestsInPeriod(array $ipData) {
        $softBanInterval = (int)Config::get(Config::API_SOFT_BAN_INTERVAL);
        $checkPeriod = Config::get(Config::RULE_PERIOD_FOR_NUM_REQUESTS);
        $periodLimit = Config::get(Config::RULE_NUM_REQUESTS);
        $maxSoftBans = Config::get(Config::API_MAX_SOFT_BANS);

        $this->apiGuardian->checkHardBan($ipData);
        $this->apiGuardian->checkSoftBan($ipData, $softBanInterval);

        if(empty($ipData["period_start"])) {
            $this->handleNewPeriod($ipData, $checkPeriod);
        }
        $newInterval = $this->dateHandler->checkGreaterThenInterval($ipData["period_end"], date("Y-m-d H:i:s"), 0);
        if($newInterval === true) {
            $ipData["requests_period"] = 0;
            $ipData["period_limit"] = $periodLimit;
            $this->handleNewPeriod($ipData, $checkPeriod);
        }

        $banned = false;
        $ipData["requests_period"] = (int) $ipData["requests_period"] + 1;
        $ipData["total_requests"] = (int) $ipData["total_requests"] + 1;
        if((int)$ipData["requests_period"] > (int)$ipData["period_limit"]) {
            $banned = true;
            $ipData["soft_ban_count"] = (int)$ipData["soft_ban_count"] + 1;
            if((int)$ipData["soft_ban_count"] >= $maxSoftBans) {
                $ipData["hard_banned"] = 1;
            } else {
                $ipData["soft_banned"] = 1;
                $ipData["soft_banned_at"] = date("Y-m-d H:i:s");
            }
        }

        $this->getModel()->update($ipData, array("ip_address" => $this->ip));
        if($banned === true) {
            $this->handleApiAppDataOnIpBan($ipData);
            throw new Exception("Banned", HttpCodes::HTTP_FORBIDDEN);
        }
    }

    private function handleNewPeriod(array &$ipData, int $checkPeriod) {
        $ipData["period_start"] = date("Y-m-d H:i:s");
        $ipData["period_end"] = date("Y-m-d H:i:s", $this->dateHandler->convertDateToTimestamp($ipData["period_start"]) + $checkPeriod *60);
    }

    private function handleApiAppDataOnIpBan(array $ipData) {
        $authHeader = Config::get(Config::API_AUTHORIZATION_HEADER);
        $accessToken = $this->request->getHeader($authHeader);
        $apiApp = $this->apiApp->getApiApp(array("user_id", "soft_banned", "hard_banned", "soft_banned_at"), array("access_token" => $accessToken));
        if(!$apiApp) {
            return;
        }
        $apiApp = $apiApp[0];
        $userId = $apiApp["user_id"];
        unset($apiApp["user_id"]);

        if((int)$ipData["soft_banned"] === 1) {
            $apiApp["soft_banned"] = 1;
            $apiApp["soft_banned_at"] = $ipData["soft_banned_at"];
        }
        if((int)$ipData["hard_banned"] === 1) {
            $apiApp["hard_banned"] = 1;
        }

        $this->apiApp->updateApiApp($apiApp, array("user_id" => $userId));
    }
}