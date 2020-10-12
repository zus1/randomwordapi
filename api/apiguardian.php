<?php

class ApiGuardian extends Guardian
{
    public function createAccessToken() {
        $tokenParts = Config::get(Config::API_ACCESS_TOKEN_PARTS);
        $tokenPartSize = Config::get(Config::API_ACCESS_TOKEN_PART_SIZE);

        $accessToken = "";
        while($tokenParts > 0) {
            $size = $tokenPartSize;
            $part = $this->makeTokenString($size, $this->tokenChars);
            $accessToken .= $part . "-";
            $tokenParts--;
        }

        return substr($accessToken, 0, strlen($accessToken) - 1);
    }

    public function checkAccessToken() {
        $authHeader = Config::get(Config::API_AUTHORIZATION_HEADER);
        $accessToken = $this->request->getHeader($authHeader);
        if(empty($accessToken)) {
            throw new Exception("Access token missing", HttpCodes::UNAUTHORIZED);
        }
    }

    public function checkSoftBan(array &$data, int $softBanInterval) {
        if((int)$data["soft_banned"] === 1) {
            $banExpired = $this->dateHandler->checkGreaterThenInterval($data["soft_banned_at"], date("Y-m-d H:i:s"), $softBanInterval *60);
            if($banExpired === false) {
                throw new Exception("Banned", HttpCodes::HTTP_FORBIDDEN);
            } else {
                $data["soft_banned"] = 0;
                $data["soft_banned_at"] = null;
            }
        }
    }
}