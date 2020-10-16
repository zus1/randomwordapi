<?php


class CookieModel extends Model
{
    protected $idField = 'id';
    protected $table = 'cookie_disclaimer';
    protected $dataSet = array(
        "id", "ip", "accepted_at", "accepted",
    );
}