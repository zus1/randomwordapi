<?php


class User
{
    private static $counter = 1;

    public function __construct() {
        self::$counter++;
    }

    public function bla() {
        echo "blabla " . self::$counter;
    }
}