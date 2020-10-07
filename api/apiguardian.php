<?php

class ApiGuardian extends Guardian
{
    private $accessTokenChars = "ABCDabcdEFGHefghIJKLijkl1234MNOPR56mnopr789stuvSTUVzZxXyYwW";

    public function createAccessToken() {
        $tokenParts = Config::get(Config::API_ACCESS_TOKEN_PARTS);
        $tokenPartSize = Config::get(Config::API_ACCESS_TOKEN_PART_SIZE);

        $accessToken = "";
        while($tokenParts > 0) {
            $part = "";
            $size = $tokenPartSize;
            while($size > 0) {
                $pos = rand(0, strlen($this->accessTokenChars) - 1);
                $char = $this->accessTokenChars[$pos];
                $part .= $char;
                $size--;
            }
            $accessToken .= $part . "-";
            $tokenParts--;
        }

        return substr($accessToken, 0, strlen($accessToken) - 1);
    }
}