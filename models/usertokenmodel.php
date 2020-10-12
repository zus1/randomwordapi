<?php


class UserTokenModel extends Model
{
    protected $idField = 'id';
    protected $table = 'user_token';
    protected $dataSet = array(
        "user_id", "verification_token", "verification_token_created", "verification_token_expires",
        "password_reset_token", "password_reset_token_created", "password_reset_token_expires", "id",
    );
}