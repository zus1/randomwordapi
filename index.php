<?php
include_once("config.php");
//echo $_SERVER["REQUEST_URI"];
Factory::getObject(Factory::TYPE_ROUTER)->routeAll();


