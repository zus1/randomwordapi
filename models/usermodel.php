<?php


class UserModel extends Model
{
    protected $idField = 'id';
    protected $table = 'user';
    protected $dataSet = array(
        "id", "username", "email", "password", "role", "local", "hard_banned", "hashed_password", "email_verified", "uuid",
    );
}