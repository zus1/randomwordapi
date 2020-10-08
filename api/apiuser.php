<?php


class ApiUser extends User
{
    public function getAuthenticatedUserId() {
        $user = $this->getAuthenticatedUser(array("id"));
        if(!$user) {
            return 0;
        }

        return $user['id'];
    }
}