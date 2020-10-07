<?php


class ApiUser extends User
{
    public function getAuthenticatedUserId() {
        return $this->getAuthenticatedUser(array("id"));
    }
}