<?php
include_once("include.php");
Factory::getObject(Factory::TYPE_INIT)->onInit();
Factory::getObject(Factory::TYPE_ROUTER)->routeAll();

